<?php
namespace Opencart\Catalog\Controller\Extension\Producttabs\Module;
/**
 * Product Tabs: extra tabs (Shipping, Warranty, Care...) on product pages, for all products, chosen categories
 * or chosen products.
 */
class Producttabs extends \Opencart\System\Engine\Controller {
	public function index(): string {
		return '';
	}

	/**
	 * catalog/view/product/product/after: add the tabs next to Description, Specification and Reviews.
	 */
	public function tabs(string &$route, array &$args, mixed &$output): void {
		$product_id = (int)($args['product_id'] ?? $this->request->get['product_id'] ?? 0);

		if (!$this->config->get('module_producttabs_status') || !$product_id) {
			return;
		}

		$language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("SELECT t.`tab_id`, td.`title`, td.`content` FROM `" . DB_PREFIX . "producttab` t
			INNER JOIN `" . DB_PREFIX . "producttab_description` td ON (td.`tab_id` = t.`tab_id` AND td.`language_id` = '" . $language_id . "')
			WHERE t.`status` = '1' AND td.`title` != '' AND (
				t.`scope` = 'all'
				OR (t.`scope` = 'product' AND t.`tab_id` IN (SELECT `tab_id` FROM `" . DB_PREFIX . "producttab_to_product` WHERE `product_id` = '" . $product_id . "'))
				OR (t.`scope` = 'category' AND t.`tab_id` IN (SELECT tc.`tab_id` FROM `" . DB_PREFIX . "producttab_to_category` tc INNER JOIN `" . DB_PREFIX . "category_path` cp ON (cp.`path_id` = tc.`category_id`) INNER JOIN `" . DB_PREFIX . "product_to_category` p2c ON (p2c.`category_id` = cp.`category_id`) WHERE p2c.`product_id` = '" . $product_id . "'))
			)
			ORDER BY t.`sort_order`, td.`title`");

		if (!$query->num_rows) {
			return;
		}

		// {name}, {model} and {brand} in the content are filled in for each product.
		$product = $this->db->query("SELECT pd.`name`, p.`model`, m.`name` AS brand FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.`product_id` = p.`product_id` AND pd.`language_id` = '" . $language_id . "') LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON (m.`manufacturer_id` = p.`manufacturer_id`) WHERE p.`product_id` = '" . $product_id . "'")->row;

		$replace = [
			'{name}'  => htmlspecialchars(html_entity_decode((string)($product['name'] ?? ''), ENT_QUOTES, 'UTF-8')),
			'{model}' => htmlspecialchars((string)($product['model'] ?? '')),
			'{brand}' => htmlspecialchars((string)($product['brand'] ?? ''))
		];

		$items = '';
		$panes = '';

		foreach ($query->rows as $row) {
			$id = 'tab-producttab-' . (int)$row['tab_id'];

			$items .= '<li class="nav-item"><a href="#' . $id . '" data-bs-toggle="tab" class="nav-link">' . $row['title'] . '</a></li>';
			$panes .= '<div id="' . $id . '" class="tab-pane fade mb-4">' . strtr(html_entity_decode($row['content'], ENT_QUOTES, 'UTF-8'), $replace) . '</div>';
		}

		// The default theme: <ul class="nav nav-tabs"> ... </ul> <div class="tab-content">.
		$nav = strpos($output, '<ul class="nav nav-tabs">');
		$end = $nav !== false ? strpos($output, '</ul>', $nav) : false;
		$content = $end !== false ? strpos($output, '<div class="tab-content">', $end) : false;

		if ($nav !== false && $end !== false && $content !== false) {
			$output = substr_replace($output, $panes, $content + strlen('<div class="tab-content">'), 0);
			$output = substr_replace($output, $items, $end, 0);

			return;
		}

		// Themes without tabs: show the tabs as sections after the description.
		$sections = '';

		foreach ($query->rows as $row) {
			$sections .= '<section class="producttab mb-4"><h2 class="h4">' . $row['title'] . '</h2>' . strtr(html_entity_decode($row['content'], ENT_QUOTES, 'UTF-8'), $replace) . '</section>';
		}

		$pos = strripos($output, '<footer');

		if ($pos !== false) {
			$output = substr_replace($output, '<div class="container">' . $sections . '</div>', $pos, 0);
		}
	}
}
