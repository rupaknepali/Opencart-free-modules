<?php
namespace Opencart\Admin\Model\Extension\Requestquote\Module;
/**
 * Request a Quote: table and admin queries.
 */
class Requestquote extends \Opencart\System\Engine\Model {
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "quote_request` (
			`quote_request_id` int(11) NOT NULL AUTO_INCREMENT,
			`product_id` int(11) NOT NULL,
			`store_id` int(11) NOT NULL DEFAULT '0',
			`customer_id` int(11) NOT NULL DEFAULT '0',
			`name` varchar(64) NOT NULL,
			`email` varchar(96) NOT NULL,
			`telephone` varchar(32) NOT NULL,
			`company` varchar(64) NOT NULL,
			`quantity` int(11) NOT NULL DEFAULT '1',
			`message` text NOT NULL,
			`status` tinyint(1) NOT NULL DEFAULT '0',
			`ip` varchar(40) NOT NULL,
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`quote_request_id`),
			KEY `status` (`status`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function getRequests(string $filter, int $start, int $limit): array {
		return $this->db->query("SELECT `q`.*, `pd`.`name` AS `product` FROM `" . DB_PREFIX . "quote_request` `q` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `q`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "')" . $this->where($filter) . " ORDER BY `q`.`date_added` DESC LIMIT " . max(0, $start) . "," . max(1, $limit))->rows;
	}

	public function getTotalRequests(string $filter = ''): int {
		return (int)$this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "quote_request` `q`" . $this->where($filter))->row['total'];
	}

	private function where(string $filter): string {
		if ($filter == 'new') {
			return " WHERE `q`.`status` = '0'";
		}

		if ($filter == 'replied') {
			return " WHERE `q`.`status` = '1'";
		}

		return '';
	}

	public function getRequest(int $quote_request_id): array {
		return $this->db->query("SELECT `q`.*, `pd`.`name` AS `product` FROM `" . DB_PREFIX . "quote_request` `q` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `q`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `q`.`quote_request_id` = '" . (int)$quote_request_id . "'")->row;
	}

	public function editStatus(int $quote_request_id, int $status): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "quote_request` SET `status` = '" . (int)$status . "' WHERE `quote_request_id` = '" . (int)$quote_request_id . "'");
	}

	public function deleteRequest(int $quote_request_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "quote_request` WHERE `quote_request_id` = '" . (int)$quote_request_id . "'");
	}
}
