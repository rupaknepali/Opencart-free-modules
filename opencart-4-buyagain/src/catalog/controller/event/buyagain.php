<?php
namespace Opencart\Catalog\Controller\Extension\Buyagain\Event;
class Buyagain extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/account/order_info/after
	 * An "Add all to cart" button above the order's products. It sends each line through OpenCart's own
	 * checkout/cart.add, the same way the per-line Buy Again buttons do, so options, stock and disabled
	 * products are checked exactly as usual.
	 */
	public function order(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_buyagain_status') || !$this->config->get('module_buyagain_reorder') || empty($args['products'])) {
			return;
		}

		$this->load->language('extension/buyagain/module/buyagain');

		$html = $this->load->view('extension/buyagain/module/buyagain_reorder', [
			'count'    => count($args['products']),
			'language' => $this->config->get('config_language'),
			'cart'     => $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'))
		]);

		// Above the products table: the first table that holds the per-line reorder forms.
		if (preg_match('#<div class="table-responsive">\s*<table[^>]*>\s*<thead>(?:(?!</table>).)*?form-product-#s', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1], 0);
		}
	}

	/**
	 * Trigger: catalog/view/account/account/after
	 */
	public function account(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_buyagain_account')) {
			return;
		}

		$html = $this->load->controller('extension/buyagain/module/buyagain');

		if ($html === '' || $html === null) {
			return;
		}

		// At the end of the page content.
		if (preg_match('#<div[^>]*\bid="content"[^>]*>#', $output, $match, PREG_OFFSET_CAPTURE)) {
			$offset = $match[0][1] + strlen($match[0][0]);
			$depth = 1;

			while ($depth > 0 && preg_match('#<div\b|</div>#i', $output, $tag, PREG_OFFSET_CAPTURE, $offset)) {
				$depth += (strtolower($tag[0][0]) == '</div>') ? -1 : 1;
				$offset = $tag[0][1] + strlen($tag[0][0]);

				if ($depth == 0) {
					$output = substr_replace($output, $html, $tag[0][1], 0);
				}
			}
		}
	}
}
