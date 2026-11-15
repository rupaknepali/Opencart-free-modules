<?php
namespace Opencart\Catalog\Model\Extension\Boughttogether\Module;
/**
 * Products that appear in the same orders as a given product.
 */
class Boughttogether extends \Opencart\System\Engine\Model {
	/**
	 * @return array<int, array{product_id: int, orders: int}> most frequent first
	 */
	public function getProducts(int $product_id, int $limit, int $min_orders, int $days): array {
		// Count only real orders: the store's "processing" and "complete" statuses
		// (System > Settings > Option). Cancelled and failed orders are never in those lists.
		$complete = array_map('intval', (array)$this->config->get('config_complete_status'));

		$statuses = array_unique(array_merge(array_map('intval', (array)$this->config->get('config_processing_status')), $complete));

		// OpenCart's default processing list includes the status new orders start with
		// (usually "Pending"), which is often an unpaid order. Leave it out unless the
		// store counts it as complete.
		$new_status = (int)$this->config->get('config_order_status_id');

		if (!in_array($new_status, $complete)) {
			$statuses = array_diff($statuses, [$new_status]);
		}

		$statuses = array_values(array_filter($statuses));

		if (!$statuses) {
			return [];
		}

		$key = 'boughttogether.' . md5(implode(':', [$product_id, $limit, $min_orders, $days, (int)$this->config->get('config_store_id'), implode(',', $statuses)]));

		// OpenCart's file cache returns [] for a missing key, so the result is stored in a
		// wrapper: that way "not cached" and "cached, but no products" can be told apart.
		$cached = $this->cache->get($key);

		if (is_array($cached) && array_key_exists('rows', $cached)) {
			$rows = $cached['rows'];
		} else {
			$query = $this->db->query("SELECT `op2`.`product_id`, COUNT(DISTINCT `op2`.`order_id`) AS `orders`
				FROM `" . DB_PREFIX . "order_product` `op1`
				INNER JOIN `" . DB_PREFIX . "order_product` `op2` ON (`op2`.`order_id` = `op1`.`order_id` AND `op2`.`product_id` != `op1`.`product_id`)
				INNER JOIN `" . DB_PREFIX . "order` `o` ON (`o`.`order_id` = `op1`.`order_id`)
				WHERE `op1`.`product_id` = '" . (int)$product_id . "'
				AND `o`.`store_id` = '" . (int)$this->config->get('config_store_id') . "'
				AND `o`.`order_status_id` IN (" . implode(',', $statuses) . ")
				AND `o`.`date_added` >= DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)
				GROUP BY `op2`.`product_id`
				HAVING `orders` >= " . (int)$min_orders . "
				ORDER BY `orders` DESC, `op2`.`product_id` ASC
				LIMIT " . (int)($limit * 3));

			$rows = $query->rows;

			// Default cache lifetime is one hour: new orders show up without hammering the database.
			$this->cache->set($key, ['rows' => $rows]);
		}

		return $rows;
	}
}
