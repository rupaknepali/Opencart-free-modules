<?php
namespace Opencart\Catalog\Model\Extension\Productbadges\Module;
/**
 * Product Badges: finds the store's best-selling products.
 */
class Productbadges extends \Opencart\System\Engine\Model {
	/**
	 * Product IDs of the top sellers by units sold, newest orders only, cached for an hour.
	 *
	 * @return array<int, int>
	 */
	public function getBestsellerIds(int $limit, int $days): array {
		$store_id = (int)$this->config->get('config_store_id');

		$key = 'productbadges.bestsellers.' . $store_id . '.' . $limit . '.' . $days;

		// OpenCart's file cache returns [] for a missing key, so wrap the result to tell "empty" from "not cached".
		$cached = $this->cache->get($key);

		if (is_array($cached) && array_key_exists('ids', $cached)) {
			return $cached['ids'];
		}

		$statuses = array_map('intval', array_merge((array)$this->config->get('config_processing_status'), (array)$this->config->get('config_complete_status')));

		// Orders still at the starting status (usually Pending) are often unpaid: leave them out,
		// unless that status is one the store treats as complete.
		if (!in_array((int)$this->config->get('config_order_status_id'), array_map('intval', (array)$this->config->get('config_complete_status')))) {
			$statuses = array_diff($statuses, [(int)$this->config->get('config_order_status_id')]);
		}

		$ids = [];

		if ($statuses) {
			$query = $this->db->query("SELECT `op`.`product_id`, SUM(`op`.`quantity`) AS `units`
				FROM `" . DB_PREFIX . "order_product` `op`
				INNER JOIN `" . DB_PREFIX . "order` `o` ON (`o`.`order_id` = `op`.`order_id`)
				WHERE `o`.`store_id` = '" . $store_id . "'
				AND `o`.`order_status_id` IN (" . implode(',', array_unique($statuses)) . ")
				AND `o`.`date_added` >= DATE_SUB(NOW(), INTERVAL " . max(1, $days) . " DAY)
				GROUP BY `op`.`product_id`
				ORDER BY `units` DESC
				LIMIT " . max(1, $limit));

			$ids = array_map('intval', array_column($query->rows, 'product_id'));
		}

		$this->cache->set($key, ['ids' => $ids], 3600);

		return $ids;
	}
}
