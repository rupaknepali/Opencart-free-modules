<?php
namespace Opencart\Catalog\Controller\Extension\Ga4Ecommerce\Analytics;
/**
 * Class Ga4Ecommerce
 *
 * GA4 Ecommerce Events for OpenCart 4.1.0.x
 *
 * Analytics:
 *   index                                             -> gtag.js snippet in <head> (only after cookie consent)
 * Events:
 *   catalog/model/catalog/product.getProducts/after   -> productList (collects view_item_list items)
 *   catalog/view/product/product/after                -> product (view_item)
 *   catalog/controller/checkout/cart.add/after        -> cartAdd (adds add_to_cart data to the JSON response)
 *   catalog/view/checkout/cart/after                  -> cart (view_cart)
 *   catalog/view/checkout/checkout/after              -> checkout (begin_checkout)
 *   catalog/controller/checkout/success/before        -> success (stores purchase before OpenCart clears the order ID)
 *   catalog/view/common/footer/after                  -> footer (view_item_list, purchase and the add_to_cart listener)
 *
 * Every event script checks that gtag exists, so nothing is sent when the visitor has not accepted cookies.
 *
 * @package Opencart\Catalog\Controller\Extension\Ga4Ecommerce\Analytics
 */
class Ga4Ecommerce extends \Opencart\System\Engine\Controller {
	/**
	 * Products shown in lists on this page. product_id => item
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static array $list = [];

	/**
	 * Index
	 *
	 * @param mixed $status
	 *
	 * @return string
	 */
	public function index(mixed $status = ''): string {
		if (!$this->isActive()) {
			return '';
		}

		$measurement_id = $this->config->get('analytics_ga4_ecommerce_measurement_id');

		$config = [];

		if ($this->config->get('analytics_ga4_ecommerce_debug')) {
			$config['debug_mode'] = true;
		}

		$html  = '<script async src="https://www.googletagmanager.com/gtag/js?id=' . urlencode($measurement_id) . '"></script>' . "\n";
		$html .= '<script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag(\'js\', new Date()); gtag(\'config\', ' . $this->json($measurement_id) . ($config ? ', ' . $this->json($config) : '') . ');</script>';

		return $html;
	}

	/**
	 * Product List
	 *
	 * Remembers products returned by getProducts() so the footer can send one view_item_list event.
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args
	 * @param mixed             $output
	 *
	 * @return void
	 */
	public function productList(string &$route, array &$args, &$output): void {
		if (!$this->isActive() || !$this->config->get('analytics_ga4_ecommerce_list') || !is_array($output)) {
			return;
		}

		try {
			foreach ($output as $product) {
				if (!empty($product['product_id']) && !isset(self::$list[(int)$product['product_id']]) && count(self::$list) < 50) {
					$item = $this->getItem($product, 1);
					$item['index'] = count(self::$list);

					self::$list[(int)$product['product_id']] = $item;
				}
			}
		} catch (\Throwable $e) {
			$this->log->write('GA4 Ecommerce Events: ' . $e->getMessage());
		}
	}

	/**
	 * Product
	 *
	 * Sends view_item on the product page.
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function product(string &$route, array &$data, string &$output): void {
		if (!$this->isActive() || empty($data['product_id'])) {
			return;
		}

		try {
			$this->load->model('catalog/product');

			$product_info = $this->model_catalog_product->getProduct((int)$data['product_id']);

			if ($product_info) {
				$item = $this->getItem($product_info, 1);

				$output .= $this->script('view_item', [
					'currency' => $this->getCurrency(),
					'value'    => $item['price'],
					'items'    => [$item]
				]);
			}
		} catch (\Throwable $e) {
			$this->log->write('GA4 Ecommerce Events: ' . $e->getMessage());
		}
	}

	/**
	 * Cart Add
	 *
	 * Adds a "ga4" key to the JSON that checkout/cart.add returns. The footer script sends it to GA4.
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args
	 * @param mixed             $output
	 *
	 * @return void
	 */
	public function cartAdd(string &$route, array &$args, &$output): void {
		if (!$this->isActive()) {
			return;
		}

		try {
			$json = json_decode($this->response->getOutput(), true);

			if (!is_array($json) || empty($json['success'])) {
				return;
			}

			$this->load->model('catalog/product');

			$product_info = $this->model_catalog_product->getProduct((int)($this->request->post['product_id'] ?? 0));

			if (!$product_info) {
				return;
			}

			$quantity = max(1, (int)($this->request->post['quantity'] ?? 1));

			$item = $this->getItem($product_info, $quantity);

			$json['ga4'] = [
				'event'  => 'add_to_cart',
				'params' => [
					'currency' => $this->getCurrency(),
					'value'    => round($item['price'] * $quantity, 2),
					'items'    => [$item]
				]
			];

			$this->response->setOutput(json_encode($json));
		} catch (\Throwable $e) {
			$this->log->write('GA4 Ecommerce Events: ' . $e->getMessage());
		}
	}

	/**
	 * Cart
	 *
	 * Sends view_cart on the shopping cart page.
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function cart(string &$route, array &$data, string &$output): void {
		$this->cartEvent('view_cart', $output);
	}

	/**
	 * Checkout
	 *
	 * Sends begin_checkout on the checkout page.
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function checkout(string &$route, array &$data, string &$output): void {
		$this->cartEvent('begin_checkout', $output);
	}

	/**
	 * Success
	 *
	 * Runs before checkout/success clears the order ID from the session. The purchase is sent by the footer.
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args
	 *
	 * @return void
	 */
	public function success(string &$route, array &$args): void {
		if (!$this->isActive() || empty($this->session->data['order_id'])) {
			return;
		}

		try {
			$this->load->model('checkout/order');

			$order_info = $this->model_checkout_order->getOrder((int)$this->session->data['order_id']);

			if (!$order_info || !$order_info['order_status_id']) {
				return;
			}

			$currency = $order_info['currency_code'];
			$currency_value = (float)$order_info['currency_value'];

			$items = [];

			foreach ($this->model_checkout_order->getProducts((int)$order_info['order_id']) as $product) {
				$price = (float)$product['price'];

				if ($this->config->get('analytics_ga4_ecommerce_tax')) {
					$price += (float)$product['tax'];
				}

				$items[] = [
					'item_id'   => $this->getItemId((int)$product['product_id'], (string)$product['model']),
					'item_name' => html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
					'price'     => (float)$this->currency->format($price, $currency, $currency_value, false),
					'quantity'  => (int)$product['quantity']
				];
			}

			$tax = 0.0;
			$shipping = 0.0;
			$coupon = '';

			foreach ($this->model_checkout_order->getTotals((int)$order_info['order_id']) as $total) {
				if ($total['code'] == 'tax') {
					$tax += (float)$total['value'];
				} elseif ($total['code'] == 'shipping') {
					$shipping += (float)$total['value'];
				} elseif ($total['code'] == 'coupon' && preg_match('/\(([^)]+)\)/', $total['title'], $match)) {
					$coupon = $match[1];
				}
			}

			$params = [
				'transaction_id' => (string)$order_info['order_id'],
				'currency'       => $currency,
				'value'          => (float)$this->currency->format((float)$order_info['total'], $currency, $currency_value, false),
				'tax'            => (float)$this->currency->format($tax, $currency, $currency_value, false),
				'shipping'       => (float)$this->currency->format($shipping, $currency, $currency_value, false),
				'items'          => $items
			];

			if ($coupon !== '') {
				$params['coupon'] = $coupon;
			}

			$this->session->data['ga4_ecommerce_purchase'] = $params;
		} catch (\Throwable $e) {
			$this->log->write('GA4 Ecommerce Events: ' . $e->getMessage());
		}
	}

	/**
	 * Footer
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function footer(string &$route, array &$data, string &$output): void {
		if (!$this->isActive()) {
			return;
		}

		$html = '';

		if (self::$list) {
			$list_name = $this->getListName();

			$items = array_values(self::$list);

			foreach ($items as &$item) {
				$item['item_list_name'] = $list_name;
			}

			unset($item);

			$html .= $this->script('view_item_list', [
				'item_list_name' => $list_name,
				'items'          => $items
			]);

			self::$list = [];
		}

		if (!empty($this->session->data['ga4_ecommerce_purchase'])) {
			$html .= $this->script('purchase', $this->session->data['ga4_ecommerce_purchase']);

			unset($this->session->data['ga4_ecommerce_purchase']);
		}

		// Product forms and listing buttons add to cart with jQuery AJAX. cartAdd() puts the event data in the response.
		$html .= '<script>if (window.jQuery) { jQuery(document).ajaxSuccess(function(event, xhr) { var json = xhr.responseJSON; if (json && json.ga4 && typeof gtag === \'function\') { gtag(\'event\', json.ga4.event, json.ga4.params); } }); }</script>';

		$position = strripos($output, '</body>');

		if ($position !== false) {
			$output = substr_replace($output, $html, $position, 0);
		} else {
			$output .= $html;
		}
	}

	/**
	 * Cart Event
	 *
	 * @param string $event
	 * @param string $output
	 *
	 * @return void
	 */
	private function cartEvent(string $event, string &$output): void {
		if (!$this->isActive() || !$this->cart->hasProducts()) {
			return;
		}

		try {
			$items = [];
			$value = 0.0;

			foreach ($this->cart->getProducts() as $product) {
				$item = $this->getItem($product, (int)$product['quantity']);

				$items[] = $item;

				$value += $item['price'] * $item['quantity'];
			}

			$params = [
				'currency' => $this->getCurrency(),
				'value'    => round($value, 2),
				'items'    => $items
			];

			if ($event == 'begin_checkout' && !empty($this->session->data['coupon'])) {
				$params['coupon'] = (string)$this->session->data['coupon'];
			}

			$output .= $this->script($event, $params);
		} catch (\Throwable $e) {
			$this->log->write('GA4 Ecommerce Events: ' . $e->getMessage());
		}
	}

	/**
	 * Get Item
	 *
	 * Builds a GA4 item from a product or cart row. Prices are in the visitor's currency.
	 *
	 * @param array<string, mixed> $product
	 * @param int                  $quantity
	 *
	 * @return array<string, mixed>
	 */
	private function getItem(array $product, int $quantity): array {
		// Cart rows already have the final unit price. Product rows may have a special.
		if (isset($product['cart_id'])) {
			$price = (float)$product['price'];
		} else {
			$price = (float)(!empty($product['special']) ? $product['special'] : $product['price']);
		}

		if ($this->config->get('analytics_ga4_ecommerce_tax')) {
			$price = $this->tax->calculate($price, (int)$product['tax_class_id'], true);
		}

		$item = [
			'item_id'   => $this->getItemId((int)$product['product_id'], (string)($product['model'] ?? '')),
			'item_name' => html_entity_decode((string)$product['name'], ENT_QUOTES, 'UTF-8'),
			'price'     => (float)$this->currency->format($price, $this->getCurrency(), '', false),
			'quantity'  => $quantity
		];

		if (!empty($product['manufacturer'])) {
			$item['item_brand'] = html_entity_decode((string)$product['manufacturer'], ENT_QUOTES, 'UTF-8');
		}

		if (!empty($product['option']) && is_array($product['option'])) {
			$variant = [];

			foreach ($product['option'] as $option) {
				$variant[] = $option['name'] . ': ' . $option['value'];
			}

			$item['item_variant'] = html_entity_decode(implode(', ', $variant), ENT_QUOTES, 'UTF-8');
		}

		return $item;
	}

	/**
	 * Get Item ID
	 *
	 * @param int    $product_id
	 * @param string $model
	 *
	 * @return string
	 */
	private function getItemId(int $product_id, string $model): string {
		$setting = $this->config->get('analytics_ga4_ecommerce_item_id');

		if ($setting == 'model' && $model !== '') {
			return $model;
		}

		if ($setting == 'sku') {
			$query = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "product_code` WHERE `product_id` = '" . (int)$product_id . "' AND `code` = 'SKU' AND `value` != '' LIMIT 1");

			if ($query->num_rows) {
				return (string)$query->row['value'];
			}
		}

		return (string)$product_id;
	}

	/**
	 * Get List Name
	 *
	 * @return string
	 */
	private function getListName(): string {
		$route = (string)($this->request->get['route'] ?? 'common/home');

		$names = [
			'common/home'                  => 'Home',
			'product/category'             => 'Category',
			'product/search'               => 'Search Results',
			'product/special'              => 'Specials',
			'product/manufacturer.info'    => 'Brand',
			'product/product'              => 'Product Page'
		];

		return $names[$route] ?? $route;
	}

	/**
	 * Get Currency
	 *
	 * @return string
	 */
	private function getCurrency(): string {
		return (string)($this->session->data['currency'] ?? $this->config->get('config_currency'));
	}

	/**
	 * Script
	 *
	 * @param string               $event
	 * @param array<string, mixed> $params
	 *
	 * @return string
	 */
	private function script(string $event, array $params): string {
		return '<script>if (typeof gtag === \'function\') { gtag(\'event\', ' . $this->json($event) . ', ' . $this->json($params) . '); }</script>';
	}

	/**
	 * JSON
	 *
	 * Encodes a value so it is safe inside a <script> tag.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	private function json(mixed $value): string {
		return (string)json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
	}

	/**
	 * Is Active
	 *
	 * @return bool
	 */
	private function isActive(): bool {
		return $this->config->get('analytics_ga4_ecommerce_status') && $this->config->get('analytics_ga4_ecommerce_measurement_id');
	}
}
