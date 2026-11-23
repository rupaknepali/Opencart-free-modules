<?php
namespace Opencart\Catalog\Controller\Extension\Webocreationtheme\Module;
/**
 * Webocreation Category Showcase: a category's heading with its brands, a tall promo banner, and its products
 * as OpenCart's own product cards.
 */
class Webocreationshowcase extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$category_id = (int)($setting['category_id'] ?? 0);

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$category = $this->model_catalog_category->getCategory($category_id);

		if (!$category) {
			return '';
		}

		$language = 'language=' . $this->config->get('config_language');
		$path = $this->path($category_id);

		$title = (string)($setting['title'][(int)$this->config->get('config_language_id')] ?? '');

		$data['title'] = $title !== '' ? htmlspecialchars($title) : $category['name'];
		$data['href'] = $this->url->link('product/category', $language . '&path=' . $path);

		// The icon the theme uses for this category in its icon bar, or the automatic one.
		$icons = (array)$this->config->get('theme_webocreationtheme_cat_icons');
		$top = (int)explode('_', $path)[0];

		$data['icon'] = $icons[$top] ?? \Opencart\Catalog\Controller\Extension\Webocreationtheme\Startup\Webocreationtheme::iconFor(html_entity_decode($category['name'], ENT_QUOTES, 'UTF-8'));

		$limit = max(1, min(12, (int)($setting['limit'] ?? 8)));

		$sort = in_array($setting['sort'] ?? '', ['p.date_added', 'p.sort_order', 'p.viewed', 'rating']) ? $setting['sort'] : 'p.date_added';

		$filter = ['filter_category_id' => $category_id, 'filter_sub_category' => true, 'sort' => $sort, 'order' => $sort == 'p.sort_order' ? 'ASC' : 'DESC', 'start' => 0, 'limit' => $limit];

		$data['products'] = [];

		$width = (int)$this->config->get('config_image_product_width') ?: 250;
		$height = (int)$this->config->get('config_image_product_height') ?: 250;

		foreach ($this->model_catalog_product->getProducts($filter) as $product) {
			$data['products'][] = $this->load->controller('product/thumb', $this->card($product, $width, $height));
		}

		if (!$data['products']) {
			return '';
		}

		// Brands of the products in this category, linking to their brand pages.
		$data['brands'] = [];

		if (!empty($setting['brands'])) {
			$query = $this->db->query("SELECT DISTINCT m.`manufacturer_id`, m.`name` FROM `" . DB_PREFIX . "product_to_category` p2c INNER JOIN `" . DB_PREFIX . "category_path` cp ON (cp.`category_id` = p2c.`category_id`) INNER JOIN `" . DB_PREFIX . "product` p ON (p.`product_id` = p2c.`product_id` AND p.`status` = '1') INNER JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p2s.`product_id` = p.`product_id` AND p2s.`store_id` = '" . (int)$this->config->get('config_store_id') . "') INNER JOIN `" . DB_PREFIX . "manufacturer` m ON (m.`manufacturer_id` = p.`manufacturer_id`) WHERE cp.`path_id` = '" . $category_id . "' ORDER BY m.`sort_order`, m.`name` LIMIT 8");

			foreach ($query->rows as $row) {
				$data['brands'][] = ['name' => $row['name'], 'href' => $this->url->link('product/manufacturer.info', $language . '&manufacturer_id=' . (int)$row['manufacturer_id'])];
			}
		}

		$image = html_entity_decode((string)($setting['image'] ?? ''), ENT_QUOTES, 'UTF-8');

		$data['banner'] = $image && is_file(DIR_IMAGE . $image) ? $this->model_tool_image->resize($image, 380, 760) : '';
		$data['banner_link'] = (string)($setting['link'] ?? '') ?: $data['href'];

		return $this->load->view('extension/webocreationtheme/module/webocreationshowcase', $data);
	}

	private function path(int $category_id): string {
		$query = $this->db->query("SELECT GROUP_CONCAT(`path_id` ORDER BY `level` SEPARATOR '_') AS path FROM `" . DB_PREFIX . "category_path` WHERE `category_id` = '" . $category_id . "'");

		return (string)($query->row['path'] ?? $category_id) ?: (string)$category_id;
	}

	/**
	 * The same product card data OpenCart's Featured module builds, so every card looks alike.
	 */
	private function card(array $product, int $width, int $height): array {
		$image = $this->model_tool_image->resize(html_entity_decode($product['image'] ?: 'placeholder.png', ENT_QUOTES, 'UTF-8'), $width, $height);

		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			$price = $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
		} else {
			$price = false;
		}

		$special = (float)$product['special'] ? $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']) : false;
		$tax = $this->config->get('config_tax') ? $this->currency->format((float)$product['special'] ? $product['special'] : $product['price'], $this->session->data['currency']) : false;

		return [
			'product_id'  => $product['product_id'],
			'thumb'       => $image,
			'name'        => $product['name'],
			'description' => oc_substr(trim(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'))), 0, (int)$this->config->get('config_product_description_length')) . '..',
			'price'       => $price,
			'special'     => $special,
			'tax'         => $tax,
			'minimum'     => $product['minimum'] > 0 ? $product['minimum'] : 1,
			'rating'      => $product['rating'],
			'href'        => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $product['product_id'])
		];
	}
}
