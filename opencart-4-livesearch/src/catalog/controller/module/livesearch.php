<?php
namespace Opencart\Catalog\Controller\Extension\Livesearch\Module;
/**
 * Live Search: product suggestions under the header search box as the shopper types.
 */
class Livesearch extends \Opencart\System\Engine\Controller {
	/**
	 * catalog/controller/common/header/before: load the script and stylesheet on every page.
	 */
	public function assets(string &$route, array &$args): void {
		if (!$this->config->get('module_livesearch_status')) {
			return;
		}

		$this->document->addStyle('extension/livesearch/catalog/view/stylesheet/livesearch.css?v=1.0.0');
		$this->document->addScript('extension/livesearch/catalog/view/javascript/livesearch.js?v=1.0.0', 'header');
	}

	/**
	 * catalog/view/common/search/after: tell the script where to ask, and its settings, on the search form itself.
	 */
	public function form(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_livesearch_status')) {
			return;
		}

		$this->load->language('extension/livesearch/module/livesearch');

		$attributes = [
			'data-livesearch' => $this->url->link('extension/livesearch/module/livesearch.suggest', 'language=' . $this->config->get('config_language'), true),
			'data-min'        => (string)$this->setting('min', 2),
			'data-loading'    => $this->language->get('text_loading'),
			'data-empty'      => $this->language->get('text_empty'),
			'data-label'      => $this->language->get('text_label')
		];

		$html = '';

		foreach ($attributes as $name => $value) {
			$html .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
		}

		$output = preg_replace('/<form\b/', '<form' . $html, $output, 1);
	}

	/**
	 * GET index.php?route=extension/livesearch/module/livesearch.suggest&search=... : JSON for the dropdown.
	 */
	public function suggest(): void {
		$json = ['products' => [], 'categories' => [], 'all' => null];

		$search = trim(html_entity_decode((string)($this->request->get['search'] ?? ''), ENT_QUOTES, 'UTF-8'));
		$search = oc_substr($search, 0, 64);

		if ($this->config->get('module_livesearch_status') && oc_strlen($search) >= (int)$this->setting('min', 2)) {
			$this->load->language('extension/livesearch/module/livesearch');
			$this->load->model('catalog/product');
			$this->load->model('tool/image');

			$language = 'language=' . $this->config->get('config_language');
			$size = (int)$this->setting('image_size', 60);

			// OpenCart's own search, so results match the search page (name, tags, model, and description if enabled).
			$filter_data = [
				'filter_search'      => $search,
				'filter_tag'         => $search,
				'filter_description' => (bool)$this->setting('description', 0),
				'start'              => 0,
				'limit'              => (int)$this->setting('limit', 5)
			];

			$show_price = $this->setting('price', 1) && ($this->customer->isLogged() || !$this->config->get('config_customer_price'));

			foreach ($this->model_catalog_product->getProducts($filter_data) as $result) {
				$price = '';
				$special = '';

				if ($show_price) {
					$price = $this->currency->format($this->tax->calculate((float)$result['price'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

					if ($result['special'] !== null && (float)$result['special'] >= 0) {
						$special = $this->currency->format($this->tax->calculate((float)$result['special'], (int)$result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					}
				}

				$json['products'][] = [
					'name'    => html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'),
					'model'   => html_entity_decode((string)$result['model'], ENT_QUOTES, 'UTF-8'),
					'thumb'   => $this->setting('image', 1) ? $this->model_tool_image->resize(is_file(DIR_IMAGE . $result['image']) ? $result['image'] : 'placeholder.png', $size, $size) : '',
					'price'   => $price,
					'special' => $special,
					'href'    => $this->url->link('product/product', $language . '&product_id=' . (int)$result['product_id'], true)
				];
			}

			if ($json['products']) {
				$total = $this->model_catalog_product->getTotalProducts($filter_data);

				$json['all'] = [
					'text' => sprintf($this->language->get('text_all'), $total, $search),
					'href' => $this->url->link('product/search', $language . '&search=' . urlencode($search) . ($filter_data['filter_description'] ? '&description=true' : ''), true)
				];
			}

			if ($this->setting('categories', 1)) {
				$query = $this->db->query("SELECT `c`.`category_id`, `cd`.`name` FROM `" . DB_PREFIX . "category` `c` LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`cd`.`category_id` = `c`.`category_id`) LEFT JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c2s`.`category_id` = `c`.`category_id`) WHERE `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND `c2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `c`.`status` = '1' AND `cd`.`name` LIKE '" . $this->db->escape('%' . $search . '%') . "' ORDER BY `c`.`sort_order`, LCASE(`cd`.`name`) LIMIT 3");


				foreach ($query->rows as $row) {
					$json['categories'][] = [
						'name' => html_entity_decode($row['name'], ENT_QUOTES, 'UTF-8'),
						'href' => $this->url->link('product/category', $language . '&path=' . $this->path((int)$row['category_id']), true)
					];
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->addHeader('Cache-Control: private, max-age=60');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * The category's full path (e.g. 20_27), so the link matches the one in the menu.
	 */
	private function path(int $category_id): string {
		$query = $this->db->query("SELECT GROUP_CONCAT(`path_id` ORDER BY `level` SEPARATOR '_') AS `path` FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = '" . (int)$category_id . "'");

		return $query->row['path'] ?: (string)$category_id;
	}

	/**
	 * A setting, or its default when it was never saved (Config::get() returns '' for those).
	 */
	private function setting(string $key, $default) {
		$value = $this->config->get('module_livesearch_' . $key);

		return ($value === null || $value === '') ? $default : $value;
	}
}
