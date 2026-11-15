<?php
namespace Opencart\Catalog\Controller\Extension\Quickview\Event;
/**
 * Product Quick View: adds the button to product cards and the popup to every page.
 */
class Quickview extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/product/thumb/after
	 * Adds a "Quick view" button over the product card's image.
	 */
	public function thumb(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_quickview_status') || empty($args['product_id'])) {
			return;
		}

		$this->load->language('extension/quickview/module/quickview');

		$label = htmlspecialchars((string)($this->config->get('module_quickview_label') ?: $this->language->get('qv_button_quickview')), ENT_QUOTES, 'UTF-8');

		$button = '<button type="button" class="oc-quickview" data-product-id="' . (int)$args['product_id'] . '" aria-label="' . $label . '"><i class="fa-solid fa-eye" aria-hidden="true"></i> <span>' . $label . '</span></button>';

		// Default theme: put the button inside the card, so it sits over the image.
		if (preg_match('/<div class="product-thumb[^"]*">/', $output, $match, PREG_OFFSET_CAPTURE)) {
			$position = $match[0][1] + strlen($match[0][0]);
			$output = substr_replace($output, $button, $position, 0);
		} else {
			// Other themes: wrap the card so the button still has something to sit on.
			$output = '<div class="oc-quickview-wrap">' . $button . $output . '</div>';
		}
	}

	/**
	 * Trigger: catalog/view/common/footer/after
	 * Adds the popup, its styles and its script once per page.
	 */
	public function footer(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_quickview_status')) {
			return;
		}

		$this->load->language('extension/quickview/module/quickview');

		$data['info'] = $this->url->link('extension/quickview/module/quickview.info', 'language=' . $this->config->get('config_language'));

		$html = $this->load->view('extension/quickview/module/quickview_modal', $data);

		$position = strripos($output, '</body>');

		if ($position !== false) {
			$output = substr_replace($output, $html, $position, 0);
		} else {
			$output .= $html;
		}
	}
}
