<?php
namespace Opencart\Catalog\Controller\Extension\Csp\Event;
/**
 * Content Security Policy: sends an enforced and/or report-only CSP header on storefront pages,
 * with an optional nonce for the store's own inline scripts.
 */
class Csp extends \Opencart\System\Engine\Controller {
	public const DIRECTIVES = ['default-src', 'script-src', 'style-src', 'img-src', 'font-src', 'connect-src', 'media-src', 'object-src', 'frame-src', 'worker-src', 'manifest-src', 'base-uri', 'form-action', 'frame-ancestors'];

	/**
	 * Trigger: catalog/view/*\/before (sort order 999: after OCMOD, the theme editor and other extensions)
	 *
	 * Runs for every template, so it only sends the header for responses that render HTML.
	 */
	public function view(string &$route, array &$args, string &$code): void {
		if (!$this->config->get('module_csp_status')) {
			return;
		}

		$state = $this->state();

		if (!$state->active || !$state->nonce) {
			return;
		}

		// The nonce goes into the template SOURCE, never into the rendered HTML, so a <script> that arrives
		// through data (a review, a setting, a hacked database row) does not get one and stays blocked.
		$source = $code;

		if ($source === '') {
			$file = $this->templateFile($route);

			if (!$file) {
				return;
			}

			$source = (string)file_get_contents($file);
		}

		// A Twig variable rather than the nonce itself, so Twig compiles each template once, not once per visitor.
		$nonced = preg_replace('/<script\b(?![^>]*\snonce\s*=)/i', '<script nonce="{{ csp_nonce }}"', $source);

		// Inline event handlers (onchange="location = this.value;" in OpenCart's sort boxes) can't carry a nonce.
		// Static ones, written in the template file itself, are allowed by their hash with 'unsafe-hashes'.
		if (preg_match_all('/\son[a-z]+\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $source, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$handler = ($match[1] ?? '') !== '' ? $match[1] : ($match[2] ?? '');

				if ($handler === '' || str_contains($handler, '{{') || str_contains($handler, '{%')) {
					continue;
				}

				$handler = html_entity_decode($handler, ENT_QUOTES | ENT_HTML5, 'UTF-8');

				$state->hashes["'sha256-" . base64_encode(hash('sha256', $handler, true)) . "'"] = true;
			}
		}

		if ($nonced !== $source) {
			$code = $nonced;
		}

		$args['csp_nonce'] = $state->nonce;
	}

	/**
	 * One state object per request: decides whether this route gets a policy and registers the header callback.
	 */
	private function state(): object {
		if ($this->registry->has('csp_state')) {
			return $this->registry->get('csp_state');
		}

		$state = new \stdClass();
		$state->active = false;
		$state->nonce = '';
		$state->hashes = [];

		$this->registry->set('csp_state', $state);

		$route = (string)($this->request->get['route'] ?? '');

		foreach (preg_split('/\R/', (string)$this->config->get('module_csp_skip')) as $skip) {
			$skip = trim($skip);

			if ($skip !== '' && $route !== '' && str_starts_with($route, $skip)) {
				return $state;
			}
		}

		$enforce = $this->config->get('module_csp_enforce_status') ? (array)$this->config->get('module_csp_enforce') : [];
		$report = $this->config->get('module_csp_report_status') ? (array)$this->config->get('module_csp_report') : [];

		if (!$enforce && !$report) {
			return $state;
		}

		$state->active = true;

		if ($this->config->get('module_csp_nonce')) {
			// One nonce per session, not per page: OpenCart loads checkout steps and payment buttons with
			// jQuery .load(), and the scripts in those fragments must carry the same nonce as the page.
			if (empty($this->session->data['csp_nonce'])) {
				$this->session->data['csp_nonce'] = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
			}

			$state->nonce = $this->session->data['csp_nonce'];
		}

		$https = (!empty($this->request->server['HTTPS']) && $this->request->server['HTTPS'] !== 'off') || (($this->request->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

		$reporting = (string)$this->config->get('module_csp_reporting');
		$endpoint = ['enforce' => '', 'report' => ''];

		if ($reporting == 'local') {
			$base = rtrim((string)$this->config->get('config_url'), '/') . '/index.php?route=extension/csp/report&policy=';

			$endpoint = ['enforce' => $base . 'enforce', 'report' => $base . 'report'];
		} elseif ($reporting == 'remote' && preg_match('#^https?://#i', (string)$this->config->get('module_csp_report_url'))) {
			$endpoint = ['enforce' => (string)$this->config->get('module_csp_report_url'), 'report' => (string)$this->config->get('module_csp_report_url')];
		}

		$upgrade = ['enforce' => (bool)$this->config->get('module_csp_enforce_upgrade'), 'report' => (bool)$this->config->get('module_csp_report_upgrade')];

		// The header is built when PHP sends headers, after every template has rendered, so it includes
		// the hashes of handlers found in templates rendered later in the request (footer, modules).
		header_register_callback(static function () use ($state, $enforce, $report, $endpoint, $upgrade, $https): void {
			if (headers_sent()) {
				return;
			}

			foreach (['enforce' => $enforce, 'report' => $report] as $type => $policy) {
				if (!$policy) {
					continue;
				}

				$value = self::build($policy, $upgrade[$type] && $https, $state->nonce, array_keys($state->hashes), $endpoint[$type]);

				if ($value !== '') {
					header(($type == 'enforce' ? 'Content-Security-Policy: ' : 'Content-Security-Policy-Report-Only: ') . $value, false);
				}
			}
		});

		return $state;
	}

	/**
	 * Turns the saved directives into a header value.
	 */
	public static function build(array $policy, bool $upgrade, string $nonce, array $hashes, string $endpoint): string {
		$directives = [];

		foreach (self::DIRECTIVES as $name) {
			// Semicolons, commas and line breaks would start a new directive, a new policy or a new header.
			$value = trim(preg_replace('/\s+/', ' ', str_replace([';', ',', "\r", "\n", "\0"], ' ', (string)($policy[$name] ?? ''))));

			if ($value !== '') {
				$directives[$name] = $value;
			}
		}

		if (!$directives) {
			return '';
		}

		// The nonce and handler hashes go where the browser looks for script rules: script-src, or default-src without it.
		$script = isset($directives['script-src']) ? 'script-src' : (isset($directives['default-src']) ? 'default-src' : '');

		if ($script && $nonce) {
			$directives[$script] .= " 'nonce-" . $nonce . "'";

			if ($hashes) {
				$directives[$script] .= " 'unsafe-hashes' " . implode(' ', $hashes);
			}
		}

		// A short sample of blocked inline code makes reports much easier to act on.
		if ($endpoint) {
			foreach (['script-src', 'style-src'] as $name) {
				if (isset($directives[$name]) && stripos($directives[$name], "'report-sample'") === false && stripos($directives[$name], "'none'") === false) {
					$directives[$name] .= " 'report-sample'";
				}
			}
		}

		$parts = [];

		foreach ($directives as $name => $value) {
			$parts[] = $name . ' ' . $value;
		}

		if ($upgrade) {
			$parts[] = 'upgrade-insecure-requests';
		}

		// report-uri only: Chrome ignores report-uri when report-to is present, then batches
		// Reporting API reports for up to a minute (or drops them), and Firefox doesn't send them at all.
		if ($endpoint) {
			$parts[] = 'report-uri ' . $endpoint;
		}

		return implode('; ', $parts);
	}

	/**
	 * Finds the .twig file OpenCart's Twig adaptor would load for a route.
	 */
	private function templateFile(string $route): string {
		if (str_starts_with($route, 'extension/ocmod/')) {
			$file = DIR_EXTENSION . 'ocmod/catalog/view/template/' . substr($route, 16) . '.twig';
		} elseif (str_starts_with($route, 'extension/')) {
			$part = explode('/', $route, 3);

			$file = isset($part[2]) ? DIR_EXTENSION . $part[1] . '/catalog/view/template/' . $part[2] . '.twig' : '';
		} else {
			$file = DIR_TEMPLATE . $route . '.twig';
		}

		return $file && is_file($file) ? $file : '';
	}
}
