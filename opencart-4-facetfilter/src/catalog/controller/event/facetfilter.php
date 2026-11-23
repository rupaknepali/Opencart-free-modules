<?php
namespace Opencart\Catalog\Controller\Extension\Facetfilter\Event;
/**
 * Faceted Filter events.
 */
class Facetfilter extends \Opencart\System\Engine\Controller {
	/**
	 * catalog/model/catalog/product.getProducts/before and .getTotalProducts/before
	 *
	 * The loader picks the model from $route after the "before" event runs, so pointing $route at this
	 * extension's model answers the call there. Only category listings with a "filter" value are rerouted
	 * (facets, OpenCart filter IDs or both, so the counts in the filter box always agree with the listing);
	 * every other product list goes to the core model as usual.
	 */
	public function route(string &$route, array &$args): void {
		if (!$this->config->get('module_facetfilter_status')) {
			return;
		}

		$data = $args[0] ?? [];

		if (empty($data['filter_category_id']) || !empty($data['filter_search']) || !empty($data['filter_tag']) || empty($data['filter_filter'])) {
			return;
		}

		$route = 'extension/facetfilter/module/facetfilter.' . substr($route, strrpos($route, '.') + 1);
	}

	/**
	 * catalog/view/common/header/after: filtered category pages are "noindex, follow". The canonical link
	 * already points at the unfiltered category, so only the plain category pages get indexed.
	 */
	public function noindex(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_facetfilter_status') || ($this->request->get['route'] ?? '') != 'product/category' || empty($this->request->get['filter'])) {
			return;
		}

		$output = str_replace('</head>', '<meta name="robots" content="noindex, follow"/>' . "\n" . '</head>', $output);
	}
}
