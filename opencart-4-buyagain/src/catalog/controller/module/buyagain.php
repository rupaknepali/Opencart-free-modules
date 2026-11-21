<?php
namespace Opencart\Catalog\Controller\Extension\Buyagain\Module;
/**
 * Buy It Again: products the logged-in customer has bought before, as standard product cards.
 * Add it to a layout in Design > Layouts (it's also shown on My Account if enabled).
 */
class Buyagain extends \Opencart\System\Engine\Controller {
	public function index(): string {
		if (!$this->config->get('module_buyagain_status') || !$this->customer->isLogged()) {
			return '';
		}

		$this->load->model('extension/buyagain/module/buyagain');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$limit = max(1, (int)$this->config->get('module_buyagain_limit') ?: 8);
		$size = max(40, (int)$this->config->get('module_buyagain_image') ?: 200);

		$data['products'] = [];

		// Ask for extra rows: some products may have been disabled since.
		foreach ($this->model_extension_buyagain_module_buyagain->getPurchased((int)$this->customer->getId(), $limit * 2) as $row) {
			if (count($data['products']) >= $limit) {
				break;
			}

			// getProduct() only returns enabled products available in this store.
			$result = $this->model_catalog_product->getProduct((int)$row['product_id']);

			if (!$result) {
				continue;
			}

			$image = $result['image'] && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')) ? $result['image'] : 'placeholder.png';

			$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
			$special = (float)$result['special'] ? $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : false;
			$tax = $this->config->get('config_tax') ? $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']) : false;

			$data['products'][] = $this->load->controller('product/thumb', [
				'product_id'  => $result['product_id'],
				'thumb'       => $this->model_tool_image->resize(html_entity_decode($image, ENT_QUOTES, 'UTF-8'), $size, $size),
				'name'        => $result['name'],
				'description' => oc_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, (int)$this->config->get('config_product_description_length')) . '..',
				'price'       => $price,
				'special'     => $special,
				'tax'         => $tax,
				'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
				'rating'      => $result['rating'],
				'href'        => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $result['product_id'])
			]);
		}

		if (!$data['products']) {
			return '';
		}

		$this->load->language('extension/buyagain/module/buyagain');

		// Settings are stored HTML-encoded, which is what the template needs.
		$data['heading'] = (string)$this->config->get('module_buyagain_heading') ?: $this->language->get('ba_heading');

		return $this->load->view('extension/buyagain/module/buyagain', $data);
	}
}
