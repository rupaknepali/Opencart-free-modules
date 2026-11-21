<?php
namespace Opencart\Catalog\Controller\Extension\Tiktok\Event;
/**
 * TikTok Pixel (browser) and Events API (server).
 *
 * Browser: PageView on every page, ViewContent, AddToCart, InitiateCheckout and Purchase.
 * Server:  Purchase, with the same event_id as the browser's, so TikTok counts it once.
 */
class Tiktok extends \Opencart\System\Engine\Controller {
	private string $endpoint = 'https://business-api.tiktok.com/open_api/v1.3/event/track/';

	private function enabled(): bool {
		return $this->config->get('module_tiktok_status') && $this->config->get('module_tiktok_pixel_id') && !$this->declined();
	}

	/**
	 * The visitor said no: TikTok isn't loaded at all, and nothing is tracked or saved.
	 */
	private function declined(): bool {
		$mode = (string)$this->config->get('module_tiktok_consent');

		return $mode != 'none' && ($this->request->cookie[$mode == 'custom' ? 'tiktok_consent' : 'policy'] ?? null) === '0';
	}

	/**
	 * Has this visitor agreed to marketing cookies? Read on the server for the Events API.
	 */
	private function consented(): bool {
		switch ($this->config->get('module_tiktok_consent')) {
			case 'none':
				return true;
			case 'custom':
				return ($this->request->cookie['tiktok_consent'] ?? '') === '1';
			default:
				return ($this->request->cookie['policy'] ?? '') === '1';
		}
	}

	/**
	 * A price in the shopper's currency, as a number (TikTok wants value and price as numbers).
	 */
	private function money(float $amount, int $tax_class_id = 0, string $currency = '', float $rate = 0): float {
		$currency = $currency ?: $this->session->data['currency'];

		if ($tax_class_id) {
			$amount = $this->tax->calculate($amount, $tax_class_id, $this->config->get('config_tax'));
		}

		return round((float)$this->currency->format($amount, $currency, $rate, false), 2);
	}

	private function contentId(array $product): string {
		return $this->config->get('module_tiktok_content_id') == 'model' && !empty($product['model']) ? html_entity_decode((string)$product['model'], ENT_QUOTES, 'UTF-8') : (string)$product['product_id'];
	}

	private function script(string $js): string {
		return '<script type="text/javascript">if (window.ttq) { ' . $js . ' }</script>';
	}

	private function track(string $event, array $properties, string $event_id): string {
		return 'ttq.track(' . json_encode($event) . ', ' . json_encode($properties, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ', {event_id: ' . json_encode($event_id) . '});';
	}

	private function hash(string $value): string {
		return $value === '' ? '' : hash('sha256', $value);
	}

	/**
	 * Trigger: catalog/view/common/header/after. The pixel base code, consent handling and PageView.
	 */
	public function header(string &$route, array &$args, string &$output): void {
		// Visitors who declined still get the consent function, so another consent tool can switch tracking back on.
		if ($this->config->get('module_tiktok_status') && $this->declined() && $this->config->get('module_tiktok_consent') == 'custom') {
			$output = str_replace('</head>', '<script type="text/javascript">window.ocTikTokConsent = function(granted) { document.cookie = \'tiktok_consent=\' + (granted ? 1 : 0) + \'; path=/; max-age=31536000; SameSite=Lax\'; if (granted) { location.reload(); } };</script>' . "\n</head>", $output);
		}

		if (!$this->enabled()) {
			return;
		}

		// TikTok adds ttclid to links from its ads. Kept in the session, and saved with the order only with consent.
		if (!empty($this->request->get['ttclid'])) {
			$this->session->data['tiktok_ttclid'] = oc_substr((string)$this->request->get['ttclid'], 0, 255);
		}

		$mode = (string)$this->config->get('module_tiktok_consent');

		// 1 = agreed, 0 = declined, -1 = not decided yet.
		$cookie = $this->request->cookie[$mode == 'custom' ? 'tiktok_consent' : 'policy'] ?? null;
		$choice = $cookie === '1' ? 1 : ($cookie === '0' ? 0 : -1);
		$pixel = (string)$this->config->get('module_tiktok_pixel_id');

		$identify = '';

		if ($this->config->get('module_tiktok_matching') && $this->customer->isLogged()) {
			$identify = 'ttq.identify(' . json_encode(['email' => $this->hash(oc_strtolower(trim($this->customer->getEmail()))), 'external_id' => $this->hash((string)$this->customer->getId())]) . ');';
		}

		$js = <<<JS
<script type="text/javascript">
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};n=d.createElement("script");n.type="text/javascript",n.async=!0,n.src=r+"?sdkid="+e+"&lib="+t;e=d.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};

  var mode = {$this->jsString($mode)};

  // Consent: events wait (holdConsent) until the visitor agrees, and are dropped if they decline.
  // The choice is read on the server: OpenCart's "policy" cookie is HttpOnly, so scripts can't see it.
  if (mode !== 'none') {
    ttq.holdConsent();
    if ({$choice} === 1) { ttq.grantConsent(); } else if ({$choice} === 0) { ttq.revokeConsent(); }
  }

  ttq.load({$this->jsString($pixel)});
  {$identify}
  ttq.page();

  // For other consent tools: call ocTikTokConsent(true) or ocTikTokConsent(false) when the visitor decides.
  w.ocTikTokConsent = function(granted) {
    d.cookie = 'tiktok_consent=' + (granted ? 1 : 0) + '; path=/; max-age=31536000; SameSite=Lax';
    granted ? ttq.grantConsent() : ttq.revokeConsent();
  };

  // OpenCart's own cookie bar: its buttons link to common/cookie.confirm with agree=1 or agree=0.
  if (mode === 'opencart') {
    d.addEventListener('click', function(e) {
      var b = e.target.closest('#cookie [href*="agree="], #cookie [value*="agree="]');
      if (b) { /agree=1/.test(b.getAttribute('href') || b.value) ? ttq.grantConsent() : ttq.revokeConsent(); }
    }, true);
  }

  // AddToCart: every add to cart in OpenCart goes through checkout/cart.add, which this extension extends.
  if (w.jQuery) {
    jQuery(d).ajaxSuccess(function(e, xhr, settings) {
      var json = xhr.responseJSON;
      if (settings.url.indexOf('checkout/cart.add') > -1 && json && json.tiktok) {
        ttq.track('AddToCart', json.tiktok.properties, {event_id: json.tiktok.event_id});
      }
    });
  }
}(window, document, 'ttq');
</script>
JS;

		$output = str_replace('</head>', $js . "\n</head>", $output);
	}

	private function jsString(string $value): string {
		return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
	}

	/**
	 * Trigger: catalog/view/product/product/after. ViewContent.
	 */
	public function product(string &$route, array &$args, string &$output): void {
		if (!$this->enabled() || empty($args['product_id'])) {
			return;
		}

		$this->load->model('catalog/product');

		$product = $this->model_catalog_product->getProduct((int)$args['product_id']);

		if (!$product) {
			return;
		}

		$price = $this->money((float)($product['special'] ?: $product['price']), (int)$product['tax_class_id']);

		$properties = [
			'contents' => [[
				'content_id'   => $this->contentId($product),
				'content_type' => 'product',
				'content_name' => html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
				'price'        => $price,
				'quantity'     => 1
			]],
			'value'    => $price,
			'currency' => $this->session->data['currency']
		];

		$output = str_replace('</body>', $this->script($this->track('ViewContent', $properties, 'view_' . bin2hex(random_bytes(8)))) . '</body>', $output);
	}

	/**
	 * Trigger: catalog/controller/checkout/cart.add/after. Adds the AddToCart data to the JSON answer;
	 * the script in the header sends it to TikTok.
	 */
	public function cart(string &$route, array &$args, mixed &$output): void {
		if (!$this->enabled()) {
			return;
		}

		$json = json_decode($this->response->getOutput(), true);

		if (empty($json['success'])) {
			return;
		}

		$product_id = (int)($this->request->post['product_id'] ?? 0);
		$quantity = max(1, (int)($this->request->post['quantity'] ?? 1));

		// The cart line has the real unit price, options included.
		foreach (array_reverse($this->cart->getProducts()) as $line) {
			if ((int)$line['product_id'] == $product_id) {
				$price = $this->money((float)$line['price'], (int)$line['tax_class_id']);

				$json['tiktok'] = [
					'event_id'   => 'cart_' . bin2hex(random_bytes(8)),
					'properties' => [
						'contents' => [[
							'content_id'   => $this->contentId($line),
							'content_type' => 'product',
							'content_name' => html_entity_decode($line['name'], ENT_QUOTES, 'UTF-8'),
							'price'        => $price,
							'quantity'     => $quantity
						]],
						'value'    => round($price * $quantity, 2),
						'currency' => $this->session->data['currency']
					]
				];

				$this->response->setOutput(json_encode($json));

				break;
			}
		}
	}

	/**
	 * Trigger: catalog/view/checkout/checkout/after. InitiateCheckout with the cart's contents.
	 */
	public function checkout(string &$route, array &$args, string &$output): void {
		if (!$this->enabled() || !$this->cart->hasProducts()) {
			return;
		}

		$contents = [];
		$value = 0.0;

		foreach ($this->cart->getProducts() as $line) {
			$price = $this->money((float)$line['price'], (int)$line['tax_class_id']);

			$contents[] = ['content_id' => $this->contentId($line), 'content_type' => 'product', 'content_name' => html_entity_decode($line['name'], ENT_QUOTES, 'UTF-8'), 'price' => $price, 'quantity' => (int)$line['quantity']];

			$value += $price * (int)$line['quantity'];
		}

		$properties = ['contents' => $contents, 'value' => round($value, 2), 'currency' => $this->session->data['currency']];

		$output = str_replace('</body>', $this->script($this->track('InitiateCheckout', $properties, 'checkout_' . bin2hex(random_bytes(8)))) . '</body>', $output);
	}

	/**
	 * Trigger: catalog/controller/checkout/success/before. OpenCart forgets the order ID on this page,
	 * so remember it for the Purchase event below.
	 */
	public function successBefore(string &$route, array &$args): void {
		if ($this->enabled() && !empty($this->session->data['order_id'])) {
			$this->session->data['tiktok_success'] = (int)$this->session->data['order_id'];
		}
	}

	/**
	 * Trigger: catalog/view/common/success/after (the checkout success page uses the shared success template). Purchase in the browser, with the event_id the server uses.
	 */
	public function success(string &$route, array &$args, string &$output): void {
		if (!$this->enabled() || empty($this->session->data['tiktok_success'])) {
			return;
		}

		$order_id = (int)$this->session->data['tiktok_success'];

		unset($this->session->data['tiktok_success']);

		$this->load->model('checkout/order');

		$order = $this->model_checkout_order->getOrder($order_id);

		// Orders waiting for payment (bank transfer, for example) aren't purchases yet. The server sends
		// Purchase when the order reaches a processing or complete status.
		if (!$order || !$this->isPaid((int)$order['order_status_id'])) {
			return;
		}

		$js = '';

		if ($this->config->get('module_tiktok_matching')) {
			$js .= 'ttq.identify(' . json_encode(array_filter(['email' => $this->hash(oc_strtolower(trim($order['email']))), 'external_id' => $order['customer_id'] ? $this->hash((string)$order['customer_id']) : ''])) . ');';
		}

		$js .= $this->track('Purchase', $this->orderProperties($order), 'purchase_' . $order_id);

		$output = str_replace('</body>', $this->script($js) . '</body>', $output);
	}

	private function isPaid(int $order_status_id): bool {
		return in_array($order_status_id, array_map('intval', array_merge((array)$this->config->get('config_processing_status'), (array)$this->config->get('config_complete_status'))));
	}

	private function orderProperties(array $order): array {
		$this->load->model('checkout/order');

		$contents = [];

		foreach ($this->model_checkout_order->getProducts((int)$order['order_id']) as $line) {
			$contents[] = [
				'content_id'   => $this->contentId($line),
				'content_type' => 'product',
				'content_name' => html_entity_decode($line['name'], ENT_QUOTES, 'UTF-8'),
				'price'        => $this->money((float)$line['price'] + ($this->config->get('config_tax') ? (float)$line['tax'] : 0), 0, $order['currency_code'], (float)$order['currency_value']),
				'quantity'     => (int)$line['quantity']
			];
		}

		return [
			'contents' => $contents,
			'value'    => $this->money((float)$order['total'], 0, $order['currency_code'], (float)$order['currency_value']),
			'currency' => $order['currency_code']
		];
	}

	/**
	 * Trigger: catalog/model/checkout/order.addOrder/after. Saves consent, the TikTok click ID and the _ttp
	 * cookie with the order, for the server-side Purchase.
	 */
	public function orderAdd(string &$route, array &$args, mixed &$output): void {
		if (!$this->config->get('module_tiktok_status') || !(int)$output) {
			return;
		}

		$consent = $this->consented();

		$this->db->query("REPLACE INTO `" . DB_PREFIX . "tiktok_order` SET `order_id` = '" . (int)$output . "', `consent` = '" . (int)$consent . "', `ttclid` = '" . $this->db->escape($consent ? (string)($this->session->data['tiktok_ttclid'] ?? '') : '') . "', `ttp` = '" . $this->db->escape($consent ? oc_substr((string)($this->request->cookie['_ttp'] ?? ''), 0, 255) : '') . "', `sent` = '0', `message` = '', `date_added` = NOW()");
	}

	/**
	 * Trigger: catalog/model/checkout/order.addHistory/after. Sends Purchase from the server the first time
	 * the order reaches a processing or complete status, whoever triggers it (checkout, payment gateway callback).
	 */
	public function history(string &$route, array &$args, mixed &$output): void {
		$order_id = (int)($args[0] ?? 0);
		$status_id = (int)($args[1] ?? 0);

		if (!$this->config->get('module_tiktok_status') || !$this->config->get('module_tiktok_pixel_id') || !$this->config->get('module_tiktok_access_token') || !$this->isPaid($status_id)) {
			return;
		}

		$row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "tiktok_order` WHERE `order_id` = '" . $order_id . "'")->row;

		if (!$row || $row['sent'] || !$row['consent']) {
			return;
		}

		$this->load->model('checkout/order');

		$order = $this->model_checkout_order->getOrder($order_id);

		if (!$order) {
			return;
		}

		$phone = preg_replace('/[^0-9+]/', '', (string)$order['telephone']);

		$user = array_filter([
			'email'       => $this->hash(oc_strtolower(trim((string)$order['email']))),
			// TikTok matches phone numbers in E.164 format (+14155550100); others are left out.
			'phone'       => str_starts_with($phone, '+') ? $this->hash($phone) : '',
			'external_id' => $order['customer_id'] ? $this->hash((string)$order['customer_id']) : '',
			'ip'          => (string)$order['ip'],
			'user_agent'  => (string)$order['user_agent'],
			'ttclid'      => (string)$row['ttclid'],
			'ttp'         => (string)$row['ttp']
		]);

		$body = [
			'event_source'    => 'web',
			'event_source_id' => (string)$this->config->get('module_tiktok_pixel_id'),
			'data'            => [[
				'event'      => 'Purchase',
				'event_time' => time(),
				'event_id'   => 'purchase_' . $order_id,
				'user'       => $user,
				'properties' => ['content_type' => 'product'] + $this->orderProperties($order),
				'page'       => ['url' => rtrim((string)$order['store_url'], '/') . '/index.php?route=checkout/success']
			]]
		];

		if ($this->config->get('module_tiktok_test_code')) {
			$body['test_event_code'] = (string)$this->config->get('module_tiktok_test_code');
		}

		$curl = curl_init((string)$this->config->get('module_tiktok_endpoint') ?: $this->endpoint);

		curl_setopt_array($curl, [
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
			CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Access-Token: ' . $this->config->get('module_tiktok_access_token')],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 3,
			CURLOPT_TIMEOUT        => 5
		]);

		$response = json_decode((string)curl_exec($curl), true);
		$error = curl_error($curl);

		curl_close($curl);

		// TikTok answers {"code": 0, "message": "OK"} on success.
		$ok = is_array($response) && isset($response['code']) && (int)$response['code'] === 0;
		$message = $ok ? 'OK' : ($error ?: (is_array($response) ? ($response['code'] ?? '') . ' ' . ($response['message'] ?? '') : 'No answer'));

		$this->db->query("UPDATE `" . DB_PREFIX . "tiktok_order` SET `sent` = '" . ($ok ? 1 : 2) . "', `message` = '" . $this->db->escape(oc_substr(trim($message), 0, 255)) . "', `date_sent` = NOW() WHERE `order_id` = '" . $order_id . "'");
	}
}
