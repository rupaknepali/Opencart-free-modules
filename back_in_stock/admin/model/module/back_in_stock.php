<?php
namespace Opencart\Admin\Model\Extension\BackInStock\Module;
/**
 * Class BackInStock
 *
 * Status values: 0 waiting, 1 notified, 2 purchased
 *
 * @package Opencart\Admin\Model\Extension\BackInStock\Module
 */
class BackInStock extends \Opencart\System\Engine\Model {
	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "back_in_stock` (
			`back_in_stock_id` int(11) NOT NULL AUTO_INCREMENT,
			`store_id` int(11) NOT NULL DEFAULT '0',
			`product_id` int(11) NOT NULL,
			`customer_id` int(11) NOT NULL DEFAULT '0',
			`email` varchar(96) NOT NULL,
			`firstname` varchar(32) NOT NULL DEFAULT '',
			`product_name` varchar(255) NOT NULL DEFAULT '',
			`language_code` varchar(16) NOT NULL DEFAULT '',
			`currency_code` varchar(3) NOT NULL DEFAULT '',
			`ip` varchar(40) NOT NULL DEFAULT '',
			`token` varchar(32) NOT NULL,
			`status` tinyint(1) NOT NULL DEFAULT '0',
			`clicks` int(11) NOT NULL DEFAULT '0',
			`order_id` int(11) NOT NULL DEFAULT '0',
			`order_total` decimal(15,4) NOT NULL DEFAULT '0.0000',
			`date_added` datetime NOT NULL,
			`date_notified` datetime DEFAULT NULL,
			PRIMARY KEY (`back_in_stock_id`),
			UNIQUE KEY `token` (`token`),
			KEY `product_status` (`product_id`, `status`),
			KEY `email` (`email`),
			KEY `ip` (`ip`, `date_added`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "back_in_stock`");
	}

	/**
	 * Get Subscriptions
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getSubscriptions(array $data = []): array {
		$sql = "SELECT `bis`.*, `p`.`quantity` FROM `" . DB_PREFIX . "back_in_stock` `bis` LEFT JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `bis`.`product_id`)";

		$implode = $this->getFilter($data);

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$sql .= " ORDER BY `bis`.`date_added` DESC";

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
	 * Get Total Subscriptions
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function getTotalSubscriptions(array $data = []): int {
		$sql = "SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "back_in_stock` `bis`";

		$implode = $this->getFilter($data);

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		return (int)$this->db->query($sql)->row['total'];
	}

	/**
	 * Get Most Wanted
	 *
	 * Products with the most people waiting, so you know what to reorder first.
	 *
	 * @param int $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getMostWanted(int $limit = 20): array {
		$query = $this->db->query("SELECT `bis`.`product_id`, MAX(`bis`.`product_name`) AS `product_name`, COUNT(*) AS `waiting`, MIN(`bis`.`date_added`) AS `oldest`, `p`.`model`, `p`.`quantity` FROM `" . DB_PREFIX . "back_in_stock` `bis` LEFT JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `bis`.`product_id`) WHERE `bis`.`status` = '0' GROUP BY `bis`.`product_id`, `p`.`model`, `p`.`quantity` ORDER BY `waiting` DESC, `oldest` ASC LIMIT " . max(1, $limit));

		return $query->rows;
	}

	/**
	 * Get Statistics
	 *
	 * @return array<string, mixed>
	 */
	public function getStatistics(): array {
		$query = $this->db->query("SELECT COALESCE(SUM(`status` = '0'), 0) AS `waiting`, COALESCE(SUM(`status` > '0'), 0) AS `notified`, COALESCE(SUM(`status` = '2'), 0) AS `purchased`, COALESCE(SUM(`clicks` > 0 AND `status` > '0'), 0) AS `clicked`, COALESCE(SUM(CASE WHEN `status` = '2' THEN `order_total` ELSE 0 END), 0) AS `revenue` FROM `" . DB_PREFIX . "back_in_stock`");

		return $query->row;
	}

	/**
	 * Delete Subscription
	 *
	 * @param int $back_in_stock_id
	 *
	 * @return void
	 */
	public function deleteSubscription(int $back_in_stock_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "back_in_stock` WHERE `back_in_stock_id` = '" . (int)$back_in_stock_id . "'");
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
			$implode[] = "`bis`.`status` = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_email'])) {
			$implode[] = "`bis`.`email` LIKE '" . $this->db->escape(oc_strtolower($data['filter_email'])) . "%'";
		}

		if (!empty($data['filter_product'])) {
			$implode[] = "`bis`.`product_name` LIKE '%" . $this->db->escape($data['filter_product']) . "%'";
		}

		if (!empty($data['filter_product_id'])) {
			$implode[] = "`bis`.`product_id` = '" . (int)$data['filter_product_id'] . "'";
		}

		return $implode;
	}
}
