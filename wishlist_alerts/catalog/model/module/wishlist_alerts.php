<?php
namespace Opencart\Catalog\Model\Extension\WishlistAlerts\Module;
/**
 * Class WishlistAlerts
 *
 * @package Opencart\Catalog\Model\Extension\WishlistAlerts\Module
 */
class WishlistAlerts extends \Opencart\System\Engine\Model {
	/**
	 * Get Customers
	 *
	 * Active customers with at least one wishlist item who haven't had an alert within the cooldown.
	 *
	 * Pages by customer ID rather than offset, because customers drop out of the result once they are emailed.
	 *
	 * @param bool $newsletter  only subscribed customers
	 * @param int  $cooldown    days
	 * @param int  $customer_id return customers after this ID
	 * @param int  $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getCustomers(bool $newsletter, int $cooldown, int $customer_id, int $limit): array {
		$sql = "SELECT `c`.`customer_id`, `c`.`customer_group_id`, `c`.`store_id`, `c`.`language_id`, `c`.`firstname`, `c`.`email` FROM `" . DB_PREFIX . "customer` `c` WHERE `c`.`customer_id` > '" . (int)$customer_id . "' AND `c`.`status` = '1' AND EXISTS (SELECT 1 FROM `" . DB_PREFIX . "customer_wishlist` `cw` WHERE `cw`.`customer_id` = `c`.`customer_id`) AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "wishlist_alert_log` `l` WHERE `l`.`customer_id` = `c`.`customer_id` AND `l`.`date_added` > DATE_SUB(NOW(), INTERVAL " . (int)$cooldown . " DAY))";

		if ($newsletter) {
			$sql .= " AND `c`.`newsletter` = '1'";
		}

		$sql .= " ORDER BY `c`.`customer_id` ASC LIMIT " . (int)$limit;

		return $this->db->query($sql)->rows;
	}

	/**
	 * Get Items
	 *
	 * @param int $customer_id
	 *
	 * @return array<int, array<string, mixed>> product_id with the last price and quantity seen (null if never checked)
	 */
	public function getItems(int $customer_id): array {
		$query = $this->db->query("SELECT `cw`.`product_id`, `wa`.`price` AS `last_price`, `wa`.`quantity` AS `last_quantity` FROM `" . DB_PREFIX . "customer_wishlist` `cw` LEFT JOIN `" . DB_PREFIX . "wishlist_alert` `wa` ON (`wa`.`customer_id` = `cw`.`customer_id` AND `wa`.`product_id` = `cw`.`product_id`) WHERE `cw`.`customer_id` = '" . (int)$customer_id . "'");

		return $query->rows;
	}

	/**
	 * Edit Snapshot
	 *
	 * @param int   $customer_id
	 * @param int   $product_id
	 * @param float $price
	 * @param int   $quantity
	 *
	 * @return void
	 */
	public function editSnapshot(int $customer_id, int $product_id, float $price, int $quantity): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "wishlist_alert` SET `customer_id` = '" . (int)$customer_id . "', `product_id` = '" . (int)$product_id . "', `price` = '" . (float)$price . "', `quantity` = '" . (int)$quantity . "', `date_checked` = NOW() ON DUPLICATE KEY UPDATE `price` = VALUES(`price`), `quantity` = VALUES(`quantity`), `date_checked` = NOW()");
	}

	/**
	 * Add Log
	 *
	 * @param int                $customer_id
	 * @param string             $email
	 * @param array<int, string> $products
	 *
	 * @return void
	 */
	public function addLog(int $customer_id, string $email, array $products): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "wishlist_alert_log` SET `customer_id` = '" . (int)$customer_id . "', `email` = '" . $this->db->escape($email) . "', `products` = '" . $this->db->escape(implode(', ', $products)) . "', `date_added` = NOW()");
	}

	/**
	 * Clean
	 *
	 * Removes snapshots of products that are no longer on the wishlist, and logs older than a year.
	 *
	 * @return void
	 */
	public function clean(): void {
		$this->db->query("DELETE `wa` FROM `" . DB_PREFIX . "wishlist_alert` `wa` LEFT JOIN `" . DB_PREFIX . "customer_wishlist` `cw` ON (`cw`.`customer_id` = `wa`.`customer_id` AND `cw`.`product_id` = `wa`.`product_id`) WHERE `cw`.`product_id` IS NULL");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "wishlist_alert_log` WHERE `date_added` < DATE_SUB(NOW(), INTERVAL 365 DAY)");
	}
}
