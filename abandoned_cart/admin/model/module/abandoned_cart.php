<?php
namespace Opencart\Admin\Model\Extension\AbandonedCart\Module;
/**
 * Class AbandonedCart
 *
 * @package Opencart\Admin\Model\Extension\AbandonedCart\Module
 */
class AbandonedCart extends \Opencart\System\Engine\Model {
	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "abandoned_cart` (
			`abandoned_cart_id` int(11) NOT NULL AUTO_INCREMENT,
			`store_id` int(11) NOT NULL DEFAULT '0',
			`customer_id` int(11) NOT NULL DEFAULT '0',
			`session_id` varchar(32) NOT NULL DEFAULT '',
			`email` varchar(96) NOT NULL,
			`firstname` varchar(32) NOT NULL DEFAULT '',
			`language_id` int(11) NOT NULL DEFAULT '0',
			`language_code` varchar(16) NOT NULL DEFAULT '',
			`currency_code` varchar(3) NOT NULL DEFAULT '',
			`products` mediumtext NOT NULL,
			`total` decimal(15,4) NOT NULL DEFAULT '0.0000',
			`total_text` varchar(64) NOT NULL DEFAULT '',
			`token` varchar(32) NOT NULL,
			`stage` tinyint(1) NOT NULL DEFAULT '0',
			`emails` int(11) NOT NULL DEFAULT '0',
			`status` tinyint(1) NOT NULL DEFAULT '0',
			`consent` tinyint(1) NOT NULL DEFAULT '0',
			`coupon_code` varchar(20) NOT NULL DEFAULT '',
			`order_id` int(11) NOT NULL DEFAULT '0',
			`order_total` decimal(15,4) NOT NULL DEFAULT '0.0000',
			`clicks` int(11) NOT NULL DEFAULT '0',
			`date_added` datetime NOT NULL,
			`date_modified` datetime NOT NULL,
			`date_sent` datetime DEFAULT NULL,
			PRIMARY KEY (`abandoned_cart_id`),
			UNIQUE KEY `token` (`token`),
			KEY `email` (`email`),
			KEY `status` (`status`, `date_modified`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "abandoned_cart_unsubscribe` (
			`email` varchar(96) NOT NULL,
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`email`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "abandoned_cart`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "abandoned_cart_unsubscribe`");
	}

	/**
	 * Get Carts
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getCarts(array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "abandoned_cart`";

		$implode = $this->getFilter($data);

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$sql .= " ORDER BY `date_modified` DESC";

		if (isset($data['start']) || isset($data['limit'])) {
			$start = max(0, (int)($data['start'] ?? 0));
			$limit = (int)($data['limit'] ?? 20);

			if ($limit < 1) {
				$limit = 20;
			}

			$sql .= " LIMIT " . $start . "," . $limit;
		}

		return $this->db->query($sql)->rows;
	}

	/**
	 * Get Total Carts
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function getTotalCarts(array $data = []): int {
		$sql = "SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "abandoned_cart`";

		$implode = $this->getFilter($data);

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		return (int)$this->db->query($sql)->row['total'];
	}

	/**
	 * Get Statistics
	 *
	 * @return array<string, mixed>
	 */
	public function getStatistics(): array {
		$query = $this->db->query("SELECT COUNT(*) AS `captured`, COALESCE(SUM(`emails`), 0) AS `emails`, COALESCE(SUM(`stage` > 0), 0) AS `emailed`, COALESCE(SUM(`status` = '1'), 0) AS `recovered`, COALESCE(SUM(CASE WHEN `status` = '1' THEN `order_total` ELSE 0 END), 0) AS `revenue` FROM `" . DB_PREFIX . "abandoned_cart`");

		return $query->row;
	}

	/**
	 * Delete Cart
	 *
	 * @param int $abandoned_cart_id
	 *
	 * @return void
	 */
	public function deleteCart(int $abandoned_cart_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "abandoned_cart` WHERE `abandoned_cart_id` = '" . (int)$abandoned_cart_id . "'");
	}

	/**
	 * Get Filter
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, string>
	 */
	private function getFilter(array $data): array {
		$implode = [];

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$implode[] = "`status` = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_email'])) {
			$implode[] = "`email` LIKE '" . $this->db->escape(oc_strtolower($data['filter_email'])) . "%'";
		}

		return $implode;
	}
}
