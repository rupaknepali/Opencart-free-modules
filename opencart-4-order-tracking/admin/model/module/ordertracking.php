<?php
namespace Opencart\Admin\Model\Extension\Ordertracking\Module;
class Ordertracking extends \Opencart\System\Engine\Model {
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "order_tracking` (
			`order_tracking_id` INT(11) NOT NULL AUTO_INCREMENT,
			`order_id` INT(11) NOT NULL,
			`carrier` VARCHAR(64) NOT NULL,
			`tracking_number` VARCHAR(64) NOT NULL,
			`url` VARCHAR(255) NOT NULL,
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`order_tracking_id`),
			KEY `order_id` (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function addTracking(int $order_id, string $carrier, string $tracking_number, string $url): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order_tracking` SET `order_id` = '" . (int)$order_id . "', `carrier` = '" . $this->db->escape(oc_substr($carrier, 0, 64)) . "', `tracking_number` = '" . $this->db->escape(oc_substr($tracking_number, 0, 64)) . "', `url` = '" . $this->db->escape(oc_substr($url, 0, 255)) . "', `date_added` = NOW()");

		return $this->db->getLastId();
	}

	public function deleteTracking(int $order_id, int $order_tracking_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "order_tracking` WHERE `order_tracking_id` = '" . (int)$order_tracking_id . "' AND `order_id` = '" . (int)$order_id . "'");
	}

	public function getTrackings(int $order_id): array {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_tracking` WHERE `order_id` = '" . (int)$order_id . "' ORDER BY `date_added` ASC, `order_tracking_id` ASC")->rows;
	}
}
