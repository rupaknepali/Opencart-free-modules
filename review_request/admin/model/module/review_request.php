<?php
namespace Opencart\Admin\Model\Extension\ReviewRequest\Module;
/**
 * Class ReviewRequest
 *
 * Status values: 1 sent, 2 reviewed, 3 unsubscribed, 4 skipped
 *
 * @package Opencart\Admin\Model\Extension\ReviewRequest\Module
 */
class ReviewRequest extends \Opencart\System\Engine\Model {
	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "review_request` (
			`review_request_id` int(11) NOT NULL AUTO_INCREMENT,
			`order_id` int(11) NOT NULL,
			`store_id` int(11) NOT NULL DEFAULT '0',
			`customer_id` int(11) NOT NULL DEFAULT '0',
			`email` varchar(96) NOT NULL,
			`firstname` varchar(32) NOT NULL DEFAULT '',
			`author` varchar(64) NOT NULL DEFAULT '',
			`language_code` varchar(16) NOT NULL DEFAULT '',
			`products` mediumtext NOT NULL,
			`token` varchar(32) NOT NULL,
			`status` tinyint(1) NOT NULL DEFAULT '1',
			`emails` int(11) NOT NULL DEFAULT '0',
			`clicks` int(11) NOT NULL DEFAULT '0',
			`reviews` int(11) NOT NULL DEFAULT '0',
			`rating_total` int(11) NOT NULL DEFAULT '0',
			`date_added` datetime NOT NULL,
			`date_sent` datetime DEFAULT NULL,
			`date_reviewed` datetime DEFAULT NULL,
			PRIMARY KEY (`review_request_id`),
			UNIQUE KEY `order_id` (`order_id`),
			UNIQUE KEY `token` (`token`),
			KEY `email` (`email`),
			KEY `status` (`status`, `date_sent`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "review_request_unsubscribe` (
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
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "review_request`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "review_request_unsubscribe`");
	}

	/**
	 * Get Requests
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getRequests(array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "review_request`";

		$implode = $this->getFilter($data);

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$sql .= " ORDER BY `date_added` DESC";

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
	 * Get Total Requests
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function getTotalRequests(array $data = []): int {
		$sql = "SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "review_request`";

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
		$query = $this->db->query("SELECT COALESCE(SUM(`emails` > 0), 0) AS `requested`, COALESCE(SUM(`emails`), 0) AS `emails`, COALESCE(SUM(`clicks` > 0), 0) AS `clicked`, COALESCE(SUM(`reviews` > 0), 0) AS `responded`, COALESCE(SUM(`reviews`), 0) AS `reviews`, COALESCE(SUM(`rating_total`), 0) AS `rating_total` FROM `" . DB_PREFIX . "review_request`");

		return $query->row;
	}

	/**
	 * Delete Request
	 *
	 * @param int $review_request_id
	 *
	 * @return void
	 */
	public function deleteRequest(int $review_request_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "review_request` WHERE `review_request_id` = '" . (int)$review_request_id . "'");
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

		if (!empty($data['filter_order_id'])) {
			$implode[] = "`order_id` = '" . (int)$data['filter_order_id'] . "'";
		}

		return $implode;
	}
}
