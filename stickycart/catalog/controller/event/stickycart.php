<?php
namespace Opencart\Catalog\Controller\Extension\Stickycart\Event;
/**
 * Sticky Add to Cart: a bar with price and Add to Cart that stays on screen on product pages.
 */
class Stickycart extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/product/product/after
	 */
	public function product(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_stickycart_status') || empty($args['product_id'])) {
			return;
		}

		// Without OpenCart's own Add to Cart button (out of stock with checkout blocked, or a theme without it), there's nothing to mirror.
		if (strpos($output, 'id="button-cart"') === false) {
			return;
		}

		$required = false;

		foreach ((array)($args['options'] ?? []) as $option) {
			if (!empty($option['required'])) {
				$required = true;
				break;
			}
		}

		$this->load->language('extension/stickycart/module/stickycart');

		$data['name'] = $args['heading_title'];
		$data['thumb'] = $args['thumb'] ?? '';
		$data['price'] = !empty($args['special']) ? $args['special'] : ($args['price'] ?? '');
		$data['old_price'] = !empty($args['special']) ? $args['price'] : '';
		$data['label'] = $this->config->get('module_stickycart_label') ?: $this->language->get('sc_button');
		$data['required'] = $required;
		$data['desktop'] = (bool)$this->config->get('module_stickycart_desktop');

		$html = $this->load->view('extension/stickycart/module/stickycart', $data);

		$position = strripos($output, '</body>');

		$output = $position !== false ? substr_replace($output, $html, $position, 0) : $output . $html;
	}
}
