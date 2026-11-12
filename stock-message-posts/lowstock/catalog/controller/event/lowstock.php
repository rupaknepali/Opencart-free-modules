<?php
namespace Opencart\Catalog\Controller\Extension\Lowstock\Event;

class Lowstock extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/product/product/before
	 *
	 * @param string $route 'product/product'
	 * @param array  $args  the template data the product controller built
	 */
	public function index(string &$route, array &$args): void {
		if (!$this->config->get('module_lowstock_status') || empty($args['product_id'])) {
			return;
		}

		$this->load->model('catalog/product');

		$product = $this->model_catalog_product->getProduct((int)$args['product_id']);

		if (!$product) {
			return;
		}

		// "Subtract Stock: No" means the quantity isn't tracked, so it means nothing.
		if (isset($product['subtract']) && !$product['subtract']) {
			return;
		}

		$quantity  = (int)$product['quantity'];
		$threshold = (int)$this->config->get('module_lowstock_threshold') ?: 5;

		// Out of stock keeps OpenCart's stock status ("Out Of Stock", "2-3 Days", "Pre-Order").
		if ($quantity <= 0 || $quantity > $threshold) {
			return;
		}

		$text = $this->config->get('module_lowstock_text') ?: 'Only %s left in stock – order soon';

		$args['stock'] = '<span class="text-danger fw-bold">'
			. htmlspecialchars(sprintf($text, $quantity), ENT_QUOTES, 'UTF-8')
			. '</span>';
	}
}
