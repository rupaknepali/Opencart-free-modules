<?php
namespace Opencart\Admin\Model\Extension\OrderWebhooks\Module;
/**
 * Class OrderWebhooks
 *
 * Delivery status values: 0 pending, 1 delivered, 2 failed (gave up)
 *
 * @package Opencart\Admin\Model\Extension\OrderWebhooks\Module
 */
class OrderWebhooks extends \Opencart\System\Engine\Model {
	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "webhook_delivery` (
			`webhook_delivery_id` int(11) NOT NULL AUTO_INCREMENT,
			`message_id` varchar(40) NOT NULL,
			`url` varchar(255) NOT NULL,
			`event` varchar(64) NOT NULL,
			`payload` mediumtext NOT NULL,
			`status` tinyint(1) NOT NULL DEFAULT '0',
			`attempts` int(11) NOT NULL DEFAULT '0',
			`response_code` int(11) NOT NULL DEFAULT '0',
			`response_body` varchar(1000) NOT NULL DEFAULT '',
			`next_attempt` datetime DEFAULT NULL,
			`date_added` datetime NOT NULL,
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`webhook_delivery_id`),
			UNIQUE KEY `message_id` (`message_id`),
			KEY `due` (`status`, `next_attempt`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "webhook_delivery`");
	}

	/**
	 * Add Delivery
	 *
	 * @param string $url
	 * @param string $event
	 * @param string $payload
	 *
	 * @return int
	 */
	public function addDelivery(string $url, string $event, string $payload): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "webhook_delivery` SET `message_id` = 'msg_" . $this->db->escape(bin2hex(random_bytes(12))) . "', `url` = '" . $this->db->escape($url) . "', `event` = '" . $this->db->escape($event) . "', `payload` = '" . $this->db->escape($payload) . "', `status` = '0', `attempts` = '0', `next_attempt` = NOW(), `date_added` = NOW(), `date_modified` = NOW()");

		return $this->db->getLastId();
	}

	/**
	 * Get Delivery
	 *
	 * @param int $webhook_delivery_id
	 *
	 * @return array<string, mixed>
	 */
	public function getDelivery(int $webhook_delivery_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "webhook_delivery` WHERE `webhook_delivery_id` = '" . (int)$webhook_delivery_id . "'");

		return $query->row;
	}

	/**
	 * Edit Result
	 *
	 * Records a delivery attempt made from the admin (test or resend). Admin attempts don't schedule retries.
	 *
	 * @param int                                          $webhook_delivery_id
	 * @param array{code: int, body: string, error: string} $result
	 *
	 * @return void
	 */
	public function editResult(int $webhook_delivery_id, array $result): void {
		$delivered = $result['code'] >= 200 && $result['code'] < 300;

		$response = $result['error'] ? 'Error: ' . $result['error'] : $result['body'];

		$this->db->query("UPDATE `" . DB_PREFIX . "webhook_delivery` SET `status` = '" . ($delivered ? 1 : 2) . "', `attempts` = `attempts` + 1, `response_code` = '" . (int)$result['code'] . "', `response_body` = '" . $this->db->escape(oc_substr($response, 0, 1000)) . "', `next_attempt` = NULL, `date_modified` = NOW() WHERE `webhook_delivery_id` = '" . (int)$webhook_delivery_id . "'");
	}

	/**
	 * Get Deliveries
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getDeliveries(array $data = []): array {
		$sql = "SELECT `webhook_delivery_id`, `message_id`, `url`, `event`, `status`, `attempts`, `response_code`, `response_body`, `next_attempt`, `date_added`, `date_modified` FROM `" . DB_PREFIX . "webhook_delivery`";

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " WHERE `status` = '" . (int)$data['filter_status'] . "'";
		}

		$sql .= " ORDER BY `webhook_delivery_id` DESC LIMIT " . max(0, (int)($data['start'] ?? 0)) . "," . max(1, (int)($data['limit'] ?? 10));

		return $this->db->query($sql)->rows;
	}

	/**
	 * Get Total Deliveries
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function getTotalDeliveries(array $data = []): int {
		$sql = "SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "webhook_delivery`";

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " WHERE `status` = '" . (int)$data['filter_status'] . "'";
		}

		return (int)$this->db->query($sql)->row['total'];
	}
}
