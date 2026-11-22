<?php
namespace Opencart\Catalog\Controller\Extension\Megamenu\Module;
/**
 * Mega Menu: replaces the top category menu with wide panels (subcategories, third level, category image,
 * a featured product) that open on hover on desktop and expand on tap on phones.
 */
class Megamenu extends \Opencart\System\Engine\Controller {
	public function index(): string {
		return '';
	}

	/**
	 * catalog/controller/common/menu/after: swap the default menu for the mega menu.
	 */
	public function menu(string &$route, array &$args, mixed &$output): void {
		if (!$this->config->get('module_megamenu_status')) {
			return;
		}

		$html = $this->render();

		if ($html !== '') {
			$output = $html;
		}
	}

	private function render(): string {
		$this->load->language('extension/megamenu/module/megamenu');

		// The menu is built on every page, so keep the finished data (3 levels of categories) in the cache.
		$key = 'megamenu.' . (int)$this->config->get('config_store_id') . '.' . (int)$this->config->get('config_language_id') . '.' . preg_replace('/[^A-Z]/', '', (string)($this->session->data['currency'] ?? '')) . '.' . (int)$this->config->get('config_customer_group_id') . '.' . (int)$this->customer->isLogged();

		$data = $this->cache->get($key);

		// The file cache returns an empty array for a missing key.
		if (!is_array($data) || !isset($data['items'])) {
			$data = $this->build();

			$this->cache->set($key, $data, 3600);
		}

		if (!$data['items']) {
			return '';
		}

		$data['text_category'] = $this->language->get('text_category');
		$data['text_all'] = $this->language->get('text_all');
		$data['text_more'] = $this->language->get('text_more');
		$data['text_featured'] = $this->language->get('text_featured');
		$data['text_open'] = $this->language->get('text_open');

		return $this->load->view('extension/megamenu/module/megamenu', $data);
	}

	private function build(): array {
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$settings = (array)$this->config->get('module_megamenu_categories');
		$language = 'language=' . $this->config->get('config_language');
		$third = (bool)$this->config->get('module_megamenu_third');
		$limit = max(1, (int)$this->config->get('module_megamenu_limit') ?: 5);
		$show_image = (bool)$this->config->get('module_megamenu_image');

		$data = ['items' => []];

		foreach ($this->model_catalog_category->getCategories(0) as $category) {
			$id = (int)$category['category_id'];
			$setting = $settings[$id] ?? [];

			if (isset($setting['show']) && !$setting['show']) {
				continue;
			}

			$children = [];

			foreach ($this->model_catalog_category->getCategories($id) as $child) {
				$grandchildren = [];
				$more = false;

				if ($third) {
					$level3 = $this->model_catalog_category->getCategories((int)$child['category_id']);

					foreach (array_slice($level3, 0, $limit) as $grandchild) {
						$grandchildren[] = [
							'name' => $grandchild['name'],
							'href' => $this->url->link('product/category', $language . '&path=' . $id . '_' . $child['category_id'] . '_' . $grandchild['category_id'])
						];
					}

					$more = count($level3) > $limit;
				}

				$children[] = [
					'name'     => $child['name'],
					'href'     => $this->url->link('product/category', $language . '&path=' . $id . '_' . $child['category_id']),
					'children' => $grandchildren,
					'more'     => $more
				];
			}

			$featured = [];
			$product_id = (int)($setting['product_id'] ?? 0);

			if ($product_id && ($product = $this->model_catalog_product->getProduct($product_id))) {
				$price = '';
				$special = '';

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

					if (!empty($product['special']) && (float)$product['special'] < (float)$product['price']) {
						$special = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					}
				}

				$featured = [
					'name'    => $product['name'],
					'thumb'   => $this->model_tool_image->resize($product['image'] ?: 'placeholder.png', 220, 220),
					'price'   => $price,
					'special' => $special,
					'href'    => $this->url->link('product/product', $language . '&product_id=' . $product_id)
				];
			}

			$data['items'][] = [
				'id'       => $id,
				'name'     => $category['name'],
				'href'     => $this->url->link('product/category', $language . '&path=' . $id),
				'children' => $children,
				'columns'  => max(1, min(4, (int)($setting['columns'] ?? 3))),
				'image'    => $show_image && !$featured && $category['image'] && is_file(DIR_IMAGE . $category['image']) ? $this->model_tool_image->resize($category['image'], 220, 220) : '',
				'featured' => $featured
			];
		}

		// Extra links (Specials, Brands, Blog...) after the categories.
		$language_id = (int)$this->config->get('config_language_id');

		foreach ((array)$this->config->get('module_megamenu_links') as $link) {
			$title = trim((string)($link['title'][$language_id] ?? ''));
			$href = trim((string)($link['href'] ?? ''));

			if ($title === '' || $href === '') {
				continue;
			}

			// Routes like product/special become store links; full addresses are used as they are.
			if (!preg_match('#^(https?:)?//|^/#', $href)) {
				$href = $this->url->link($href, $language);
			}

			$data['items'][] = ['id' => 0, 'name' => $title, 'href' => $href, 'children' => [], 'columns' => 1, 'image' => '', 'featured' => []];
		}

		return $data;
	}
}
