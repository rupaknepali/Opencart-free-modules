<?php
namespace Opencart\Catalog\Controller\Extension\Loadmore\Event;
/**
 * Load More Products: adds the script to product listing pages.
 */
class Loadmore extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/common/footer/after
	 */
	public function index(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_loadmore_status')) {
			return;
		}

		// Only the pages that list products with OpenCart's pagination.
		$routes = ['product/category', 'product/search', 'product/special', 'product/manufacturer.info', 'product/manufacturer|info'];

		if (!in_array($this->request->get['route'] ?? '', $routes)) {
			return;
		}

		$this->load->language('extension/loadmore/module/loadmore');

		$data['mode'] = $this->config->get('module_loadmore_mode') == 'scroll' ? 'scroll' : 'button';
		$data['auto'] = max(0, (int)($this->config->get('module_loadmore_auto') ?? 3));
		$data['label'] = $this->config->get('module_loadmore_label') ?: $this->language->get('lm_button');

		$html = $this->load->view('extension/loadmore/module/loadmore', $data);

		$position = strripos($output, '</body>');

		if ($position !== false) {
			$output = substr_replace($output, $html, $position, 0);
		} else {
			$output .= $html;
		}
	}
}
