<?php
namespace Opencart\Catalog\Controller\Extension\Speedbooster\Module;
/**
 * Speed Booster: full-page cache for guests, lazy-loaded images, HTML minify and preconnect hints.
 *
 * startup() runs as a startup action (oc_startup), after the session, language, currency, customer and
 * cart are known but before any page controller. A cached page is served by switching the route to
 * serve(), so maintenance mode and the other startups still run as usual.
 */
class Speedbooster extends \Opencart\System\Engine\Controller {
	private static string $key = '';
	private static string $page = '';
	private static array $tokens = [];
	private static array $session = [];
	private static string $route = '';
	private static float $start = 0;

	public function index(): string {
		return '';
	}

	// ---------------------------------------------------------------- startup

	public function startup(): void {
		if (!$this->config->get('module_speedbooster_status') || ($this->request->server['REQUEST_METHOD'] ?? 'GET') != 'GET') {
			return;
		}

		self::$start = microtime(true);
		self::$route = (string)($this->request->get['route'] ?? $this->config->get('action_default'));

		// Every HTML page gets the optimisations; only some are also cached.
		$this->event->register('controller/' . self::$route . '/after', new \Opencart\System\Engine\Action('extension/speedbooster/module/speedbooster.after'), 999);

		if (!$this->cacheable()) {
			return;
		}

		self::$key = $this->cacheKey();
		self::$session = $this->session->data;

		$file = $this->file(self::$key);
		$ttl = max(1, (int)$this->config->get('module_speedbooster_cache_ttl')) * 60;

		if (is_file($file) && filemtime($file) + $ttl > time()) {
			$content = (string)file_get_contents($file);
			$pos = strpos($content, "\n");

			if ($pos !== false) {
				$meta = json_decode(substr($content, 0, $pos), true);

				if (is_array($meta)) {
					self::$tokens = (array)($meta['tokens'] ?? []);
					self::$page = substr($content, $pos + 1);

					// Serve it: the original page controller never runs.
					$this->request->get['route'] = 'extension/speedbooster/module/speedbooster.serve';

					$this->event->unregister('controller/' . self::$route . '/after', 'extension/speedbooster/module/speedbooster.after');
				}
			}
		}
	}

	/**
	 * Only anonymous visitors with an empty cart, wishlist and compare list, on routes that aren't excluded.
	 */
	private function cacheable(): bool {
		if (!$this->config->get('module_speedbooster_cache_status') || $this->config->get('config_maintenance')) {
			return false;
		}

		if ($this->customer->isLogged() || $this->cart->hasProducts() || !empty($this->session->data['wishlist']) || !empty($this->session->data['compare'])) {
			return false;
		}

		// Affiliate tracking links set a cookie and an admin-preview flag, so let them through.
		if (isset($this->request->get['tracking']) || isset($this->request->get['nocache']) || isset($this->session->data['user_id'])) {
			return false;
		}

		if (strlen((string)($_SERVER['REQUEST_URI'] ?? '')) > 1024) {
			return false;
		}

		foreach (preg_split('/\R/', (string)$this->config->get('module_speedbooster_cache_exclude')) as $pattern) {
			$pattern = trim($pattern);

			if ($pattern !== '' && fnmatch($pattern, self::$route)) {
				return false;
			}
		}

		return true;
	}

	private function cacheKey(): string {
		// $_SERVER directly: OpenCart's request object HTML-encodes it.
		$uri = (string)($_SERVER['REQUEST_URI'] ?? '/');

		$query = [];

		parse_str((string)parse_url($uri, PHP_URL_QUERY), $query);

		// Tracking parameters don't change the page.
		foreach (array_keys($query) as $name) {
			if (preg_match('/^(utm_.*|gclid|gbraid|wbraid|fbclid|msclkid|mc_cid|mc_eid|_ga|_gl|ttclid|nocache)$/i', (string)$name)) {
				unset($query[$name]);
			}
		}

		ksort($query);

		$cookies = [];

		foreach (preg_split('/[\s,]+/', (string)$this->config->get('module_speedbooster_vary_cookies')) as $name) {
			if ($name !== '') {
				$cookies[$name] = $_COOKIE[$name] ?? null;
			}
		}

		return sha1(json_encode([
			(int)$this->config->get('config_store_id'),
			!empty($this->request->server['HTTPS']),
			strtolower((string)($_SERVER['HTTP_HOST'] ?? '')),
			(string)parse_url($uri, PHP_URL_PATH),
			$query,
			$this->config->get('config_language'),
			$this->session->data['currency'] ?? '',
			$cookies
		]));
	}

	// ---------------------------------------------------------------- serve and store

	public function serve(): void {
		$html = self::$page;

		// Form tokens (review, upload) were swapped for placeholders when the page was stored: give this visitor fresh ones.
		foreach (self::$tokens as $name => $length) {
			$token = oc_token(max(16, (int)$length));

			$this->session->data[$name] = $token;

			$html = str_replace('%%SPEEDBOOSTER_' . $name . '%%', $token, $html);
		}

		$this->response->addHeader('X-Speed-Booster: HIT');
		$this->response->setOutput($html);
	}

	/**
	 * After the page controller: optimise the HTML and, if this request is cacheable, store it.
	 */
	public function after(string &$route, array &$args, mixed &$output): void {
		$html = $this->response->getOutput();

		if (!str_contains($html, '</html>') || !$this->isHtml()) {
			return;
		}

		$html = $this->optimize($html);

		$this->response->setOutput($html);

		$stored = false;

		if (self::$key && $this->canStore()) {
			$stored = $this->store($html);
		}

		$this->response->addHeader('X-Speed-Booster: ' . ($stored ? 'MISS' : 'BYPASS') . '; ' . round((microtime(true) - self::$start) * 1000) . 'ms');
	}

	private function isHtml(): bool {
		foreach ($this->response->getHeaders() as $header) {
			if (stripos($header, 'Content-Type:') === 0 && stripos($header, 'text/html') === false) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Only plain 200 pages, and only if the page controller didn't log someone in, fill the cart or set a cookie.
	 */
	private function canStore(): bool {
		if ($this->customer->isLogged() || $this->cart->hasProducts() || http_response_code() != 200) {
			return false;
		}

		foreach ($this->response->getHeaders() as $header) {
			if (preg_match('/^(HTTP\/|Status:|Location:|Refresh:)/i', $header) || preg_match('/^Cache-Control:.*(no-store|private)/i', $header)) {
				return false;
			}
		}

		$allowed = [$this->config->get('session_name'), 'currency', 'language'];

		foreach (headers_list() as $header) {
			if (preg_match('/^Set-Cookie:\s*([^=]+)=/i', $header, $match) && !in_array(trim($match[1]), $allowed)) {
				return false;
			}
		}

		return true;
	}

	private function store(string $html): bool {
		$tokens = [];

		// Any session token created while rendering this page (review_token, upload_token...) becomes a placeholder.
		foreach ($this->session->data as $name => $value) {
			if (is_string($value) && strlen($value) >= 16 && str_ends_with((string)$name, '_token') && ($value !== (self::$session[$name] ?? null)) && str_contains($html, $value)) {
				$html = str_replace($value, '%%SPEEDBOOSTER_' . $name . '%%', $html);

				$tokens[$name] = strlen($value);
			}
		}

		$file = $this->file(self::$key);

		if (!is_dir(dirname($file)) && !@mkdir(dirname($file), 0755, true) && !is_dir(dirname($file))) {
			return false;
		}

		$tmp = $file . '.' . getmypid() . '.tmp';

		if (@file_put_contents($tmp, json_encode(['route' => self::$route, 'tokens' => $tokens, 'time' => time()]) . "\n" . $html) === false || !@rename($tmp, $file)) {
			@unlink($tmp);

			return false;
		}

		// Product pages are indexed by product ID, so an order can refresh just those pages (stock, price).
		if (self::$route == 'product/product' && !empty($this->request->get['product_id'])) {
			$index = DIR_CACHE . 'speedbooster/index/';

			if (is_dir($index) || @mkdir($index, 0755, true)) {
				@file_put_contents($index . 'product-' . (int)$this->request->get['product_id'] . '.txt', self::$key . "\n", FILE_APPEND | LOCK_EX);
			}
		}

		// Now and then, remove expired pages.
		if (mt_rand(1, 500) == 1) {
			$this->gc();
		}

		return true;
	}

	private function file(string $key): string {
		return DIR_CACHE . 'speedbooster/' . substr($key, 0, 2) . '/' . $key . '.html';
	}

	private function gc(): void {
		$ttl = max(1, (int)$this->config->get('module_speedbooster_cache_ttl')) * 60;

		foreach (glob(DIR_CACHE . 'speedbooster/*/*.html') ?: [] as $file) {
			if (filemtime($file) + $ttl < time()) {
				@unlink($file);
			}
		}
	}

	// ---------------------------------------------------------------- optimise

	private function optimize(string $html): string {
		if ($this->config->get('module_speedbooster_lazy_status')) {
			$html = $this->lazy($html, (int)$this->config->get('module_speedbooster_lazy_skip'));
		}

		$hints = '';

		foreach (preg_split('/\R/', (string)$this->config->get('module_speedbooster_preconnect')) as $origin) {
			$origin = trim($origin);

			if (preg_match('#^https?://[a-z0-9.-]+(:\d+)?$#i', $origin)) {
				$hints .= '<link rel="preconnect" href="' . htmlspecialchars($origin, ENT_QUOTES) . '" crossorigin>' . "\n";
			}
		}

		if ($hints && ($pos = stripos($html, '</head>')) !== false) {
			$html = substr_replace($html, $hints, $pos, 0);
		}

		if ($this->config->get('module_speedbooster_minify_status')) {
			$html = $this->minify($html);
		}

		return $html;
	}

	/**
	 * loading="lazy" on images below the first few (the logo and the main image should load straight away:
	 * lazy-loading them hurts Largest Contentful Paint), and on every iframe.
	 */
	private function lazy(string $html, int $skip): string {
		$body = stripos($html, '<body');

		if ($body === false) {
			return $html;
		}

		$count = 0;

		$after = preg_replace_callback('/<(img|iframe)\b([^>]*)>/i', function(array $match) use (&$count, $skip): string {
			if (preg_match('/\sloading\s*=/i', $match[2])) {
				return $match[0];
			}

			if (strtolower($match[1]) == 'img' && ++$count <= $skip) {
				return $match[0];
			}

			return '<' . $match[1] . ' loading="lazy"' . (strtolower($match[1]) == 'img' && !preg_match('/\sdecoding\s*=/i', $match[2]) ? ' decoding="async"' : '') . $match[2] . '>';
		}, substr($html, $body));

		return $after === null ? $html : substr($html, 0, $body) . $after;
	}

	/**
	 * Collapse whitespace and remove comments, leaving <pre>, <textarea>, <script> and <style> untouched.
	 */
	private function minify(string $html): string {
		$keep = [];

		$min = preg_replace_callback('#<(pre|textarea|script|style)\b[^>]*>.*?</\1\s*>#is', function(array $match) use (&$keep): string {
			$key = "\x01" . count($keep) . "\x01";

			$keep[$key] = $match[0];

			return $key;
		}, $html);

		if ($min === null) {
			return $html;
		}

		// Comments, but not conditional comments.
		$min = preg_replace('/<!--(?!\[if|\s*\[endif).*?-->/s', '', $min) ?? $min;

		$min = preg_replace('/\s{2,}|[\r\n\t]+/', ' ', $min) ?? $min;
		$min = preg_replace('/>\s+</', '> <', $min) ?? $min;

		return strtr($min, $keep);
	}

	// ---------------------------------------------------------------- events

	/**
	 * catalog/model/checkout/order.addHistory/after: stock and prices may have changed, so refresh the pages
	 * of the products in this order.
	 */
	public function order(string &$route, array &$args, mixed &$output): void {
		$order_id = (int)($args[0] ?? 0);

		if (!$order_id) {
			return;
		}

		$query = $this->db->query("SELECT DISTINCT `product_id` FROM `" . DB_PREFIX . "order_product` WHERE `order_id` = '" . $order_id . "'");

		foreach ($query->rows as $row) {
			$index = DIR_CACHE . 'speedbooster/index/product-' . (int)$row['product_id'] . '.txt';

			if (!is_file($index)) {
				continue;
			}

			foreach (array_unique(array_filter(explode("\n", (string)file_get_contents($index)))) as $key) {
				if (preg_match('/^[a-f0-9]{40}$/', $key) && is_file($this->file($key))) {
					@unlink($this->file($key));
				}
			}

			@unlink($index);
		}
	}
}
