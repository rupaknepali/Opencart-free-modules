<?php
namespace Opencart\Catalog\Controller\Extension\Requestquote\Event;
/**
 * Request a Quote: the button and form on product pages.
 */
class Requestquote extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/product/product/after
	 */
	public function index(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_requestquote_status') || empty($args['product_id'])) {
			return;
		}

		$this->load->model('catalog/product');

		$product = $this->model_catalog_product->getProduct((int)$args['product_id']);

		if (!$product) {
			return;
		}

		// "zero" mode: only products with no price, where the quote replaces Add to Cart.
		$replace = false;

		if ($this->config->get('module_requestquote_mode') == 'zero') {
			if ((float)$product['price'] > 0) {
				return;
			}

			$replace = true;
		}

		$this->load->language('extension/requestquote/module/requestquote');

		$data['product_id'] = (int)$product['product_id'];
		$data['product'] = $args['heading_title'];
		$data['minimum'] = max(1, (int)$product['minimum']);
		$data['send'] = $this->url->link('extension/requestquote/module/requestquote.send', 'language=' . $this->config->get('config_language'));
		$data['replace'] = $replace;

		// Logged-in customers get their details filled in.
		$data['name'] = $this->customer->isLogged() ? $this->customer->getFirstName() . ' ' . $this->customer->getLastName() : '';
		$data['email'] = $this->customer->isLogged() ? $this->customer->getEmail() : '';
		$data['telephone'] = $this->customer->isLogged() ? $this->customer->getTelephone() : '';

		$html = $this->load->view('extension/requestquote/module/requestquote', $data);

		// After the Add to Cart form, inside the product column.
		$form_end = strpos($output, '</form>', (int)strpos($output, 'id="form-product"'));

		if ($form_end === false) {
			return;
		}

		$output = substr_replace($output, $html, $form_end + 7, 0);

		if ($replace) {
			// "Price on request": hide the quantity box, Add to Cart and the $0.00 price.
			$output = str_replace('<form id="form-product">', '<form id="form-product" class="oc-rq-hidden">', $output);

			// The price list (the <ul> holding "price-new") would show $0.00.
			$price = strpos($output, 'price-new');

			if ($price !== false && ($ul = strrpos(substr($output, 0, $price), '<ul class="list-unstyled">')) !== false) {
				$output = substr_replace($output, '<ul class="list-unstyled oc-rq-hidden">', $ul, strlen('<ul class="list-unstyled">'));
			}
		}
	}
}
