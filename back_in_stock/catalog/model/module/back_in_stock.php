<?php
namespace Opencart\Catalog\Model\Extension\BackInStock\Module;
/**
 * Class BackInStock
 *
 * Status values: 0 waiting, 1 notified, 2 purchased
 *
 * @package Opencart\Catalog\Model\Extension\BackInStock\Module
 */
class BackInStock extends \Opencart\System\Engine\Model {
	/**
	 * Get Waiting
	 *
	 * @param int    $store_id
	 * @param int    $product_id
	 * @param string $email
	 *
	 * @return array<string, mixed>
	 */
	public function getWaiting(int $store_id, int $product_id, string $email): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "back_in_stock` WHERE `store_id` = '" . (int)$store_id . "' AND `product_id` = '" . (int)$product_id . "' AND `email` = '" . $this->db->escape(oc_strtolower($email)) . "' AND `status` = '0'");

		return $query->row;
	}

	/**
	 * Get Total Recent By IP
	 *
	 * @param string $ip
	 *
	 * @return int subscriptions from this IP address in the last hour
	 */
	public function getTotalRecentByIp(string $ip): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "back_in_stock` WHERE `ip` = '" . $this->db->escape($ip) . "' AND `date_added` > DATE_SUB(NOW(), INTERVAL 1 HOUR)");

		return (int)$query->row['total'];
	}

	/**
	 * Add Subscription
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function addSubscription(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "back_in_stock` SET `store_id` = '" . (int)$data['store_id'] . "', `product_id` = '" . (int)$data['product_id'] . "', `customer_id` = '" . (int)$data['customer_id'] . "', `email` = '" . $this->db->escape(oc_strtolower($data['email'])) . "', `firstname` = '" . $this->db->escape(oc_substr($data['firstname'], 0, 32)) . "', `product_name` = '" . $this->db->escape(oc_substr($data['product_name'], 0, 255)) . "', `language_code` = '" . $this->db->escape($data['language_code']) . "', `currency_code` = '" . $this->db->escape($data['currency_code']) . "', `ip` = '" . $this->db->escape($data['ip']) . "', `token` = '" . $this->db->escape(oc_token(32)) . "', `status` = '0', `date_added` = NOW()");

		return $this->db->getLastId();
	}

	/**
	 * Get Due Subscriptions
	 *
	 * Waiting subscriptions whose product is enabled, available in that store and back in stock.
	 *
	 * @param int $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getDueSubscriptions(int $limit = 100): array {
		$query = $this->db->query("SELECT `bis`.* FROM `" . DB_PREFIX . "back_in_stock` `bis` LEFT JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `bis`.`product_id`) LEFT JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `bis`.`product_id` AND `p2s`.`store_id` = `bis`.`store_id`) WHERE `bis`.`status` = '0' AND `p`.`quantity` > '0' AND `p`.`status` = '1' AND `p`.`date_available` <= NOW() AND `p2s`.`product_id` IS NOT NULL ORDER BY `bis`.`date_added` ASC LIMIT " . max(1, $limit));

		return $query->rows;
	}

	/**
	 * Get Subscription By Token
	 *
	 * @param string $token
	 *
	 * @return array<string, mixed>
	 */
	public function getSubscriptionByToken(string $token): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "back_in_stock` WHERE `token` = '" . $this->db->escape($token) . "'");

		return $query->row;
	}

	/**
	 * Edit Notified
	 *
	 * @param int $back_in_stock_id
	 *
	 * @return void
	 */
	public function editNotified(int $back_in_stock_id): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "back_in_stock` SET `status` = '1', `date_notified` = NOW() WHERE `back_in_stock_id` = '" . (int)$back_in_stock_id . "'");
	}

	/**
	 * Add Click
	 *
	 * @param int $back_in_stock_id
	 *
	 * @return void
	 */
	public function addClick(int $back_in_stock_id): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "back_in_stock` SET `clicks` = (`clicks` + 1) WHERE `back_in_stock_id` = '" . (int)$back_in_stock_id . "'");
	}

	/**
	 * Edit Purchased
	 *
	 * Credits an order to notifications sent for any of its products in the last 30 days.
	 *
	 * @param int             $store_id
	 * @param string          $email
	 * @param array<int, int> $product_ids
	 * @param int             $order_id
	 * @param float           $order_total in the default currency
	 *
	 * @return void
	 */
	public function editPurchased(int $store_id, string $email, array $product_ids, int $order_id, float $order_total): void {
		$product_ids = array_filter(array_map('intval', $product_ids));

		if (!$product_ids) {
			return;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "back_in_stock` SET `status` = '2', `order_id` = '" . (int)$order_id . "', `order_total` = '" . (float)$order_total . "' WHERE `store_id` = '" . (int)$store_id . "' AND `email` = '" . $this->db->escape(oc_strtolower($email)) . "' AND `status` = '1' AND `product_id` IN (" . implode(',', $product_ids) . ") AND `date_notified` > DATE_SUB(NOW(), INTERVAL 30 DAY)");
	}

	/**
	 * Clean
	 *
	 * @param int $retention days
	 *
	 * @return void
	 */
	public function clean(int $retention): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "back_in_stock` WHERE `date_added` < DATE_SUB(NOW(), INTERVAL " . max(30, $retention) . " DAY)");
	}
}
