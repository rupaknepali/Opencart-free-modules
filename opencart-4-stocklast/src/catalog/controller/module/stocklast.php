<?php
namespace Opencart\Catalog\Controller\Extension\Stocklast\Module;
/**
 * Out of Stock Last: startup action. Runs on every storefront request and, on the pages you chose,
 * swaps in a database wrapper that sorts (or hides) out-of-stock products in product lists.
 */
class Stocklast extends \Opencart\System\Engine\Controller {
	public function index(): void {
		if (!$this->config->get('module_stocklast_status')) {
			return;
		}

		$route = (string)($this->request->get['route'] ?? 'common/home');

		$pages = [
			'product/category'     => 'category',
			'product/search'       => 'search',
			'product/manufacturer' => 'manufacturer'
		];

		if (str_starts_with($route, 'product/manufacturer')) {
			$key = 'manufacturer';
		} else {
			$key = $pages[$route] ?? 'modules';
		}

		// Never touch the cart, checkout, account pages or APIs.
		if ($key == 'modules' && preg_match('#^(checkout/|account/|api/|extension/opencart/payment|extension/.*/payment)#', $route)) {
			return;
		}

		if (!$this->config->get('module_stocklast_' . $key)) {
			return;
		}

		$mode = $this->config->get('module_stocklast_mode') == 'hide' ? 'hide' : 'last';

		require_once DIR_EXTENSION . 'stocklast/system/library/stocklast.php';

		$this->registry->set('db', new \Opencart\Extension\Stocklast\System\Library\Stocklast($this->registry->get('db'), $mode));
	}
}
