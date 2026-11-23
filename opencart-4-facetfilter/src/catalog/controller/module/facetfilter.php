<?php
namespace Opencart\Catalog\Controller\Extension\Facetfilter\Module;
/**
 * Faceted Filter: the filter box for category pages (add it to the Category layout in Design > Layouts).
 */
class Facetfilter extends \Opencart\System\Engine\Controller {
	public function index(): string {
		if (!$this->config->get('module_facetfilter_status') || ($this->request->get['route'] ?? '') != 'product/category' || empty($this->request->get['path'])) {
			return '';
		}

		$parts = explode('_', (string)$this->request->get['path']);
		$category_id = (int)end($parts);

		$this->load->language('extension/facetfilter/module/facetfilter');
		$this->load->model('extension/facetfilter/module/facetfilter');

		$model = $this->model_extension_facetfilter_module_facetfilter;

		$products = $model->getCategoryProducts($category_id);

		if (count($products) < 2) {
			return '';
		}

		$settings = $this->settings();
		$filter = (string)($this->request->get['filter'] ?? '');
		$facets = $model->parse($filter);
		$core_groups = $model->getFilterGroups();

		// How many products each option would show: everything else that's ticked, minus the option's own group.
		$count = function(string $group, callable $has) use ($products, $facets, $core_groups, $model): int {
			$n = 0;

			foreach ($products as $product) {
				if ($has($product) && $model->matches($product, $facets, $core_groups, $group)) {
					$n++;
				}
			}

			return $n;
		};

		$plain = fn($text) => html_entity_decode((string)$text, ENT_QUOTES, 'UTF-8');
		$groups = [];

		// Price
		if ($settings['price']) {
			$prices = [];

			foreach ($products as $product) {
				if ($model->matches($product, $facets, $core_groups, 'price')) {
					$prices[] = $model->shownPrice($product);
				}
			}

			if ($prices && min($prices) < max($prices)) {
				$data['price'] = [
					'min'    => floor(min($prices)),
					'max'    => ceil(max($prices)),
					'from'   => $facets['price'] ? $facets['price'][0] : '',
					'to'     => $facets['price'] ? $facets['price'][1] : '',
					'symbol' => $this->currency->getSymbolLeft($this->session->data['currency']) ?: $this->currency->getSymbolRight($this->session->data['currency'])
				];
			}
		}

		// Brands
		if ($settings['brand']) {
			$options = [];

			foreach ($model->getManufacturerNames(array_unique(array_column($products, 'manufacturer_id'))) as $manufacturer_id => $name) {
				$options[] = [
					'value'   => 'b' . $manufacturer_id,
					'name'    => $plain($name),
					'count'   => $count('brand', fn($p) => $p['manufacturer_id'] == $manufacturer_id),
					'checked' => in_array($manufacturer_id, $facets['brand'])
				];
			}

			if (count($options) > 1 || $facets['brand']) {
				$groups[] = ['key' => 'brand', 'name' => $this->language->get('text_brand'), 'options' => $options];
			}
		}

		// Attributes
		if ($settings['attribute']) {
			foreach ($model->getFacetAttributes(array_keys($products), $settings['attributes']) as $attribute_id => $attribute) {
				$options = [];

				foreach ($attribute['values'] as $crc => $text) {
					$options[] = [
						'value'   => 'a' . $attribute_id . 'x' . $crc,
						'name'    => $plain($text),
						'count'   => $count('a' . $attribute_id, fn($p) => ($p['attr'][$attribute_id] ?? null) === $crc),
						'checked' => in_array($crc, $facets['attr'][$attribute_id] ?? [])
					];
				}

				if (count($options) > 1 || !empty($facets['attr'][$attribute_id])) {
					$groups[] = ['key' => 'a' . $attribute_id, 'name' => $plain($attribute['name']), 'options' => $options];
				}
			}
		}

		// OpenCart's own filters assigned to this category
		if ($settings['filters']) {
			foreach ($model->getCategoryFilters($category_id) as $group_id => $group) {
				$options = [];

				foreach ($group['filters'] as $filter_id => $name) {
					$options[] = [
						'value'   => 'f' . $filter_id,
						'name'    => $plain($name),
						'count'   => $count('f' . $group_id, fn($p) => in_array($filter_id, $p['core'])),
						'checked' => in_array($filter_id, $facets['core'])
					];
				}

				$groups[] = ['key' => 'f' . $group_id, 'name' => $plain($group['name']), 'options' => $options];
			}
		}

		// Availability
		if ($settings['stock']) {
			$in_stock = $count('stock', fn($p) => $p['quantity'] > 0);

			if ($in_stock < count($products) || $facets['stock']) {
				$groups[] = ['key' => 'stock', 'name' => $this->language->get('text_availability'), 'options' => [[
					'value'   => 's1',
					'name'    => $this->language->get('text_in_stock'),
					'count'   => $in_stock,
					'checked' => $facets['stock']
				]]];
			}
		}

		// Rating
		if ($settings['rating'] && max(array_column($products, 'rating')) > 0) {
			$options = [];

			foreach ([4, 3, 2] as $stars) {
				$options[] = [
					'value'   => 'r' . $stars,
					'name'    => sprintf($this->language->get('text_stars'), $stars),
					'stars'   => $stars,
					'count'   => $count('rating', fn($p) => $p['rating'] >= $stars),
					'checked' => $facets['rating'] == $stars
				];
			}

			$groups[] = ['key' => 'rating', 'name' => $this->language->get('text_rating'), 'options' => $options, 'single' => true];
		}

		$data['groups'] = $groups;
		$data['limit'] = $settings['limit'];
		$data['active'] = $filter !== '';

		// The category link without "filter" and "page", keeping sort, order and limit.
		$url = '';

		foreach (['sort', 'order', 'limit'] as $key) {
			if (isset($this->request->get[$key])) {
				$url .= '&' . $key . '=' . urlencode(html_entity_decode((string)$this->request->get[$key], ENT_QUOTES, 'UTF-8'));
			}
		}

		$data['base'] = $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $this->request->get['path'] . $url);
		$data['total'] = $model->getTotalProducts(['filter_category_id' => $category_id, 'filter_filter' => $filter]);

		return $this->load->view('extension/facetfilter/module/facetfilter', $data);
	}

	/**
	 * Settings with defaults for a fresh install (Config::get() returns '' for keys that were never saved).
	 */
	private function settings(): array {
		$defaults = ['price' => 1, 'brand' => 1, 'attribute' => 1, 'filters' => 1, 'stock' => 1, 'rating' => 1, 'attributes' => [], 'limit' => 6];
		$settings = [];

		foreach ($defaults as $key => $default) {
			$value = $this->config->get('module_facetfilter_' . $key);
			$settings[$key] = ($value === null || $value === '') ? $default : $value;
		}

		$settings['attributes'] = array_map('intval', (array)$settings['attributes']);
		$settings['limit'] = max(3, (int)$settings['limit']);

		return $settings;
	}
}
