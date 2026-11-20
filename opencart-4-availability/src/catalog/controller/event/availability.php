<?php
namespace Opencart\Catalog\Controller\Extension\Availability\Event;
/**
 * Product Availability on Product Cards: the same availability text as the product page, on every card.
 */
class Availability extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/product/thumb/after
	 */
	public function thumb(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_availability_status') || empty($args['product_id'])) {
			return;
		}

		// One small query instead of getProduct(), which also loads specials, discounts and ratings.
		$query = $this->db->query("SELECT p.`quantity`, ss.`name` AS stock_status FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "stock_status` ss ON (ss.`stock_status_id` = p.`stock_status_id` AND ss.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE p.`product_id` = '" . (int)$args['product_id'] . "'");

		if (!$query->num_rows) {
			return;
		}

		$this->load->language('extension/availability/module/availability');

		$in_stock = (int)$query->row['quantity'] > 0;

		if (!$in_stock) {
			$text = $query->row['stock_status'];
		} elseif ($this->config->get('module_availability_show') == 'out') {
			return;
		} elseif ($this->config->get('config_stock_display')) {
			$text = (int)$query->row['quantity'];
		} else {
			$text = $this->language->get('av_instock');
		}

		$html = '<p class="oc-availability small mb-1"><span class="text-muted">' . $this->language->get('av_label') . '</span> <span class="' . ($in_stock ? 'text-success' : 'text-danger') . '">' . htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8') . '</span></p>';

		// Above the price; above the buttons if the product has no price shown.
		if (($position = strpos($output, '<div class="price">')) === false) {
			$position = strpos($output, '<form');
		}

		if ($position !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}
	}
}
