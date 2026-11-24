<?php
namespace Opencart\Catalog\Model\Extension\Preorder\Module;
/**
 * Pre-order: which products are on pre-order right now.
 */
class Preorder extends \Opencart\System\Engine\Model {
	private static array $cache = [];

	/**
	 * Pre-order settings for products that have pre-order switched on AND no stock left.
	 * When stock comes back, the product sells normally again without touching the setting.
	 *
	 * @param int[] $product_ids
	 *
	 * @return array product_id => ['ship_date' => 'Y-m-d' or '', 'note' => string]
	 */
	public function getActive(array $product_ids): array {
		$product_ids = array_unique(array_map('intval', $product_ids));
		$missing = array_diff($product_ids, array_keys(self::$cache));

		if ($missing) {
			foreach ($missing as $product_id) {
				self::$cache[$product_id] = false;
			}

			$query = $this->db->query("SELECT po.`product_id`, po.`ship_date`, po.`note` FROM `" . DB_PREFIX . "preorder` po LEFT JOIN `" . DB_PREFIX . "product` p ON (p.`product_id` = po.`product_id`) WHERE po.`status` = '1' AND p.`quantity` <= '0' AND po.`product_id` IN (" . implode(',', $missing) . ")");

			foreach ($query->rows as $row) {
				self::$cache[(int)$row['product_id']] = [
					'ship_date' => ($row['ship_date'] && $row['ship_date'] != '0000-00-00') ? $row['ship_date'] : '',
					'note'      => $row['note']
				];
			}
		}

		$active = [];

		foreach ($product_ids as $product_id) {
			if (self::$cache[$product_id]) {
				$active[$product_id] = self::$cache[$product_id];
			}
		}

		return $active;
	}

	public function addOrderLines(int $order_id, array $lines): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "preorder_order` WHERE `order_id` = '" . (int)$order_id . "'");

		foreach ($lines as $line) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "preorder_order` SET `order_id` = '" . (int)$order_id . "', `product_id` = '" . (int)$line['product_id'] . "', `name` = '" . $this->db->escape($line['name']) . "', `quantity` = '" . (int)$line['quantity'] . "', `ship_date` = " . ($line['ship_date'] ? "'" . $this->db->escape($line['ship_date']) . "'" : "NULL"));
		}
	}

	public function getOrderLines(int $order_id): array {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "preorder_order` WHERE `order_id` = '" . (int)$order_id . "' ORDER BY `preorder_order_id`")->rows;
	}
}
