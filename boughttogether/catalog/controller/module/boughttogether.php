<?php
namespace Opencart\Catalog\Controller\Extension\Boughttogether\Module;
/**
 * Frequently bought together: a layout module for the Product layout.
 *
 * Shows the current product plus the products most often ordered with it,
 * with checkboxes, a live total and one "Add selected to cart" button.
 */
class Boughttogether extends \Opencart\System\Engine\Controller {
	public function index(): string {
		// Only on product pages.
		if (($this->request->get['route'] ?? '') != 'product/product' || empty($this->request->get['product_id'])) {
			return '';
		}

		$product_id = (int)$this->request->get['product_id'];

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if (!$product_info) {
			return '';
		}

		$limit = min(6, max(1, (int)($this->config->get('module_boughttogether_limit') ?: 3)));
		$min_orders = max(1, (int)($this->config->get('module_boughttogether_min_orders') ?: 2));
		$days = max(1, (int)($this->config->get('module_boughttogether_days') ?: 365));

		$this->load->model('extension/boughttogether/module/boughttogether');

		$items = [];

		foreach ($this->model_extension_boughttogether_module_boughttogether->getProducts($product_id, $limit, $min_orders, $days) as $row) {
			// getProduct() skips disabled products, products from other stores and products not yet available.
			$info = $this->model_catalog_product->getProduct((int)$row['product_id']);

			if ($info) {
				$items[] = $info;
			}

			if (count($items) >= $limit) {
				break;
			}
		}

		if (!$items) {
			return '';
		}

		$this->load->language('extension/boughttogether/module/boughttogether');
		$this->load->model('tool/image');

		$data['heading'] = (string)($this->config->get('module_boughttogether_heading') ?: $this->language->get('text_heading'));
		$data['show_price'] = $this->customer->isLogged() || !$this->config->get('config_customer_price');

		$data['products'] = [];
		$total = 0.0;

		foreach (array_merge([$product_info], $items) as $i => $info) {
			$product = $this->product($info, $i === 0);

			if ($product['selectable']) {
				$total += $product['value'] * $product['quantity'];
			}

			$data['products'][] = $product;
		}

		$currency = $this->session->data['currency'];

		// What the JavaScript needs to format the live total like OpenCart does.
		$data['currency_left'] = $this->currency->getSymbolLeft($currency);
		$data['currency_right'] = $this->currency->getSymbolRight($currency);
		$data['currency_decimals'] = (int)$this->currency->getDecimalPlace($currency);
		$data['decimal_point'] = $this->language->get('decimal_point');
		$data['thousand_point'] = $this->language->get('thousand_point');
		$data['total'] = $this->currency->format($total, $currency, 1.0);

		$data['cart_add'] = $this->url->link('checkout/cart.add', 'language=' . $this->config->get('config_language'));
		$data['cart_info'] = $this->url->link('common/cart.info', 'language=' . $this->config->get('config_language'));
		$data['cart'] = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'));

		return $this->load->view('extension/boughttogether/module/boughttogether', $data);
	}

	/**
	 * Template data for one product in the bundle.
	 */
	private function product(array $info, bool $current): array {
		$image = ($info['image'] && is_file(DIR_IMAGE . html_entity_decode($info['image'], ENT_QUOTES, 'UTF-8'))) ? $info['image'] : 'placeholder.png';
		$width = (int)$this->config->get('config_image_related_width') ?: 200;
		$height = (int)$this->config->get('config_image_related_height') ?: 200;

		$currency = $this->session->data['currency'];
		$base = (float)$info['special'] ? (float)$info['special'] : (float)$info['price'];
		$unit = $this->tax->calculate($base, $info['tax_class_id'], $this->config->get('config_tax'));

		// Products with required options or subscription plans can't be added from here:
		// the customer has to choose on the product page.
		$needs_choice = (bool)$this->model_catalog_product->getSubscriptions((int)$info['product_id']);

		foreach ($this->model_catalog_product->getOptions((int)$info['product_id']) as $option) {
			if ($option['required']) {
				$needs_choice = true;
				break;
			}
		}

		return [
			'product_id' => (int)$info['product_id'],
			'name'       => $info['name'],
			'thumb'      => $this->model_tool_image->resize($image, $width, $height),
			'href'       => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $info['product_id']),
			'price'      => $this->currency->format($unit, $currency),
			'value'      => (float)$this->currency->format($unit, $currency, 0, false),
			'quantity'   => max(1, (int)$info['minimum']),
			'current'    => $current,
			'selectable' => !$needs_choice
		];
	}
}
