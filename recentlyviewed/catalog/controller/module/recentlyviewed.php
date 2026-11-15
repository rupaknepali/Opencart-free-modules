<?php
namespace Opencart\Catalog\Controller\Extension\Recentlyviewed\Module;
/**
 * Recently Viewed Products: a layout module that lists the products this shopper viewed last.
 */
class Recentlyviewed extends \Opencart\System\Engine\Controller {
	public function index(): string {
		if (!$this->config->get('module_recentlyviewed_status')) {
			return '';
		}

		// The event stores product IDs, newest first, as "42-30-47".
		$ids = [];

		foreach (explode('-', (string)($this->request->cookie['oc_recently_viewed'] ?? '')) as $id) {
			if ((int)$id > 0 && !in_array((int)$id, $ids)) {
				$ids[] = (int)$id;
			}
		}

		// Never show the product that's on screen.
		if (($this->request->get['route'] ?? '') == 'product/product' && !empty($this->request->get['product_id'])) {
			$ids = array_diff($ids, [(int)$this->request->get['product_id']]);
		}

		if (!$ids) {
			return '';
		}

		$this->load->language('extension/recentlyviewed/module/recentlyviewed');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$limit = min(20, max(1, (int)$this->config->get('module_recentlyviewed_limit') ?: 4));
		$width = (int)$this->config->get('module_recentlyviewed_width') ?: 200;
		$height = (int)$this->config->get('module_recentlyviewed_height') ?: 200;

		$data['products'] = [];

		foreach ($ids as $product_id) {
			// getProduct() returns nothing for disabled products and products from other stores.
			$product = $this->model_catalog_product->getProduct($product_id);

			if (!$product) {
				continue;
			}

			$image = ($product['image'] && is_file(DIR_IMAGE . html_entity_decode($product['image'], ENT_QUOTES, 'UTF-8'))) ? html_entity_decode($product['image'], ENT_QUOTES, 'UTF-8') : 'placeholder.png';

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$price = false;
			}

			if ($price !== false && (float)$product['special']) {
				$special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$special = false;
			}

			if ($price !== false && $this->config->get('config_tax')) {
				$tax = $this->currency->format((float)$product['special'] ? $product['special'] : $product['price'], $this->session->data['currency']);
			} else {
				$tax = false;
			}

			// Rendered with OpenCart's own product card, so it matches the theme (and other card extensions apply).
			$data['products'][] = $this->load->controller('product/thumb', [
				'product_id'  => $product['product_id'],
				'thumb'       => $this->model_tool_image->resize($image, $width, $height),
				'name'        => $product['name'],
				'description' => oc_substr(trim(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('config_product_description_length')) . '..',
				'price'       => $price,
				'special'     => $special,
				'tax'         => $tax,
				'minimum'     => $product['minimum'] > 0 ? $product['minimum'] : 1,
				'rating'      => $product['rating'],
				'href'        => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $product['product_id'])
			]);

			if (count($data['products']) >= $limit) {
				break;
			}
		}

		if (!$data['products']) {
			return '';
		}

		$data['heading'] = $this->config->get('module_recentlyviewed_heading') ?: $this->language->get('rv_heading');
		$data['clear'] = (bool)($this->config->get('module_recentlyviewed_clear') ?? 1);

		return $this->load->view('extension/recentlyviewed/module/recentlyviewed', $data);
	}
}
