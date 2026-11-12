<?php
namespace Opencart\Catalog\Controller\Extension\Autorelated\Module;

class Autorelated extends \Opencart\System\Engine\Controller {
	/**
	 * Layout module: Design -> Layouts -> Cart (or any layout) -> Content Top/Bottom.
	 *
	 * Shows products related to what's in the cart. Products without hand-picked
	 * related items fall back to their category through the getRelated event.
	 */
	public function index(): string {
		// The product page already shows its own Related Products block.
		if (($this->request->get['route'] ?? '') == 'product/product') {
			return '';
		}

		if (!$this->cart->hasProducts()) {
			return '';
		}

		$limit = (int)$this->config->get('module_autorelated_limit');

		if ($limit < 1) {
			$limit = 4;
		}

		$this->load->language('extension/autorelated/module/autorelated');

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$in_cart = [];

		foreach ($this->cart->getProducts() as $product) {
			$in_cart[(int)$product['product_id']] = true;
		}

		// Related products of the first few cart items, skipping anything already in the cart.
		$results = [];

		foreach (array_slice(array_keys($in_cart), 0, 5) as $product_id) {
			foreach ($this->model_catalog_product->getRelated($product_id) as $result) {
				$id = (int)$result['product_id'];

				if (!isset($in_cart[$id]) && !isset($results[$id])) {
					$results[$id] = $result;
				}
			}

			if (count($results) >= $limit) {
				break;
			}
		}

		if (!$results) {
			return '';
		}

		$width = (int)$this->config->get('config_image_related_width') ?: 200;
		$height = (int)$this->config->get('config_image_related_height') ?: 200;

		$data['products'] = [];

		foreach (array_slice($results, 0, $limit) as $result) {
			if ($result['image'] && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
				$image = $this->model_tool_image->resize(html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'), $width, $height);
			} else {
				$image = $this->model_tool_image->resize('placeholder.png', $width, $height);
			}

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$price = false;
			}

			if ((float)$result['special']) {
				$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			} else {
				$special = false;
			}

			if ($this->config->get('config_tax')) {
				$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
			} else {
				$tax = false;
			}

			$description = trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')));

			$product_data = [
				'product_id'  => $result['product_id'],
				'thumb'       => $image,
				'name'        => $result['name'],
				'description' => mb_substr($description, 0, (int)$this->config->get('config_product_description_length') ?: 100) . '..',
				'price'       => $price,
				'special'     => $special,
				'tax'         => $tax,
				'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
				'rating'      => $result['rating'],
				'href'        => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $result['product_id'])
			] + $result; // 4.1's product/thumb reads extra fields from the product row

			// Same product card the theme uses everywhere else.
			$data['products'][] = $this->load->controller('product/thumb', $product_data);
		}

		return $this->load->view('extension/autorelated/module/autorelated', $data);
	}
}
