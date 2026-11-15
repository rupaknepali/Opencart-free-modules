<?php
namespace Opencart\Catalog\Controller\Extension\Quickview\Module;
/**
 * Product Quick View: the popup's content, loaded with AJAX.
 */
class Quickview extends \Opencart\System\Engine\Controller {
	/**
	 * This extension isn't a layout block. If it's added to a layout anyway, show nothing.
	 */
	public function index(): string {
		return '';
	}

	/**
	 * Route: extension/quickview/module/quickview.info&product_id=N
	 */
	public function info(): void {
		$this->load->language('extension/quickview/module/quickview');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$product_id = (int)($this->request->get['product_id'] ?? 0);

		$product_info = $this->config->get('module_quickview_status') ? $this->model_catalog_product->getProduct($product_id) : [];

		if (!$product_info) {
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
			$this->response->setOutput('<p class="p-3">' . $this->language->get('qv_error_product') . '</p>');

			return;
		}

		$language = 'language=' . $this->config->get('config_language');
		$width = (int)$this->config->get('config_image_popup_width') ?: 500;
		$height = (int)$this->config->get('config_image_popup_height') ?: 500;
		$thumb_width = (int)$this->config->get('config_image_additional_width') ?: 74;
		$thumb_height = (int)$this->config->get('config_image_additional_height') ?: 74;

		// Images: the main image plus up to four additional ones.
		$data['images'] = [];

		$files = [$product_info['image']];

		foreach (array_slice($this->model_catalog_product->getImages($product_id), 0, 4) as $image) {
			$files[] = $image['image'];
		}

		foreach ($files as $file) {
			if ($file && is_file(DIR_IMAGE . html_entity_decode($file, ENT_QUOTES, 'UTF-8'))) {
				$data['images'][] = [
					'popup' => $this->model_tool_image->resize($file, $width, $height),
					'thumb' => $this->model_tool_image->resize($file, $thumb_width, $thumb_height)
				];
			}
		}

		if (!$data['images']) {
			$data['images'][] = [
				'popup' => $this->model_tool_image->resize('placeholder.png', $width, $height),
				'thumb' => $this->model_tool_image->resize('placeholder.png', $thumb_width, $thumb_height)
			];
		}

		// Price, the same way the category page calculates it.
		$currency = $this->session->data['currency'];

		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$data['price'] = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $currency);
		} else {
			$data['price'] = false;
		}

		if ($data['price'] && (float)$product_info['special']) {
			$data['special'] = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $currency);
		} else {
			$data['special'] = false;
		}

		// Stock, exactly as OpenCart 4.1's product page works it out.
		if ($product_info['quantity'] <= 0) {
			$stock_status_id = (int)$product_info['stock_status_id'];
		} elseif (!$this->config->get('config_stock_display')) {
			$stock_status_id = (int)$this->config->get('config_stock_status_id');
		} else {
			$stock_status_id = 0;
		}

		$this->load->model('localisation/stock_status');

		$stock_status_info = $stock_status_id ? $this->model_localisation_stock_status->getStockStatus($stock_status_id) : [];

		$data['stock'] = $stock_status_info ? $stock_status_info['name'] : $product_info['quantity'];
		$data['in_stock'] = $product_info['quantity'] > 0;

		// Brand: getProduct() returns only the ID.
		$data['manufacturer'] = '';

		if ($product_info['manufacturer_id']) {
			$this->load->model('catalog/manufacturer');

			$manufacturer_info = $this->model_catalog_manufacturer->getManufacturer((int)$product_info['manufacturer_id']);

			if ($manufacturer_info) {
				$data['manufacturer'] = $manufacturer_info['name'];
			}
		}

		// Short description.
		$description = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8'))));

		if (mb_strlen($description) > 300) {
			$description = rtrim(mb_substr($description, 0, 299)) . '…';
		}

		$data['description'] = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');

		// Products with required options or subscriptions need the full product page.
		$needs_choice = (bool)$this->model_catalog_product->getSubscriptions($product_id);

		foreach ($this->model_catalog_product->getOptions($product_id) as $option) {
			if ($option['required']) {
				$needs_choice = true;
				break;
			}
		}

		$data['product_id'] = $product_id;
		$data['name'] = $product_info['name'];
		$data['model'] = $product_info['model'];
		$data['minimum'] = max(1, (int)$product_info['minimum']);
		$data['needs_choice'] = $needs_choice;
		$data['href'] = $this->url->link('product/product', $language . '&product_id=' . $product_id);
		$data['cart_add'] = $this->url->link('checkout/cart.add', $language);
		$data['cart'] = $this->url->link('common/cart.info', $language);

		$this->response->setOutput($this->load->view('extension/quickview/module/quickview_info', $data));
	}
}
