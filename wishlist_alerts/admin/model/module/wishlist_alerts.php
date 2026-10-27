<?php
namespace Opencart\Admin\Model\Extension\WishlistAlerts\Module;
/**
 * Class WishlistAlerts
 *
 * @package Opencart\Admin\Model\Extension\WishlistAlerts\Module
 */
class WishlistAlerts extends \Opencart\System\Engine\Model {
	/**
	 * Install
	 *
	 * wishlist_alert keeps the price and stock we last saw for each wishlist item.
	 * wishlist_alert_log records every email sent.
	 *
	 * @return void
	 */
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "wishlist_alert` (
			`customer_id` int(11) NOT NULL,
			`product_id` int(11) NOT NULL,
			`price` decimal(15,4) NOT NULL DEFAULT '0.0000',
			`quantity` int(11) NOT NULL DEFAULT '0',
			`date_checked` datetime NOT NULL,
			PRIMARY KEY (`customer_id`, `product_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "wishlist_alert_log` (
			`wishlist_alert_log_id` int(11) NOT NULL AUTO_INCREMENT,
			`customer_id` int(11) NOT NULL,
			`email` varchar(96) NOT NULL,
			`products` text NOT NULL,
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`wishlist_alert_log_id`),
			KEY `customer` (`customer_id`, `date_added`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "wishlist_alert`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "wishlist_alert_log`");
	}

	/**
	 * Get Statistics
	 *
	 * @return array<string, int>
	 */
	public function getStatistics(): array {
		$wishlist = $this->db->query("SELECT COUNT(*) AS `items`, COUNT(DISTINCT `customer_id`) AS `customers` FROM `" . DB_PREFIX . "customer_wishlist`");
		$sent = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "wishlist_alert_log` WHERE `date_added` > DATE_SUB(NOW(), INTERVAL 30 DAY)");

		return [
			'items'     => (int)$wishlist->row['items'],
			'customers' => (int)$wishlist->row['customers'],
			'sent'      => (int)$sent->row['total']
		];
	}

	/**
	 * Get Most Wished
	 *
	 * @param int $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getMostWished(int $limit): array {
		$query = $this->db->query("SELECT `cw`.`product_id`, `pd`.`name`, `p`.`model`, `p`.`price`, `p`.`quantity`, COUNT(*) AS `total` FROM `" . DB_PREFIX . "customer_wishlist` `cw` LEFT JOIN `" . DB_PREFIX . "product` `p` ON (`cw`.`product_id` = `p`.`product_id`) LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`cw`.`product_id` = `pd`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') GROUP BY `cw`.`product_id` ORDER BY `total` DESC LIMIT " . (int)$limit);

		return $query->rows;
	}
}
