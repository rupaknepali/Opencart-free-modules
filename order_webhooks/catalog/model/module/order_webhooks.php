<?php
namespace Opencart\Catalog\Model\Extension\OrderWebhooks\Module;
/**
 * Class OrderWebhooks
 *
 * Delivery status values: 0 pending, 1 delivered, 2 failed (gave up)
 *
 * @package Opencart\Catalog\Model\Extension\OrderWebhooks\Module
 */
class OrderWebhooks extends \Opencart\System\Engine\Model {
	/**
	 * Minutes to wait after each failed attempt. After the last one the delivery is marked failed.
	 *
	 * @var array<int, int>
	 */
	private array $backoff = [1, 5, 30, 120, 300, 600, 1440];

	/**
	 * Add Delivery
	 *
	 * @param string $url
	 * @param string $event
	 * @param string $payload JSON
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
	 * Get Due Deliveries
	 *
	 * @param int $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getDueDeliveries(int $limit): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "webhook_delivery` WHERE `status` = '0' AND `next_attempt` <= NOW() ORDER BY `webhook_delivery_id` ASC LIMIT " . (int)$limit);

		return $query->rows;
	}

	/**
	 * Edit Result
	 *
	 * @param array<string, mixed>                       $delivery
	 * @param array{code: int, body: string, error: string} $result
	 *
	 * @return bool true if delivered
	 */
	public function editResult(array $delivery, array $result): bool {
		$attempts = (int)$delivery['attempts'] + 1;

		$delivered = $result['code'] >= 200 && $result['code'] < 300;

		if ($delivered) {
			$status = 1;
			$next = "NULL";
		} elseif ($attempts > count($this->backoff)) {
			$status = 2;
			$next = "NULL";
		} else {
			$status = 0;
			$next = "DATE_ADD(NOW(), INTERVAL " . (int)$this->backoff[$attempts - 1] . " MINUTE)";
		}

		$response = $result['error'] ? 'Error: ' . $result['error'] : $result['body'];

		$this->db->query("UPDATE `" . DB_PREFIX . "webhook_delivery` SET `status` = '" . (int)$status . "', `attempts` = '" . (int)$attempts . "', `response_code` = '" . (int)$result['code'] . "', `response_body` = '" . $this->db->escape(oc_substr($response, 0, 1000)) . "', `next_attempt` = " . $next . ", `date_modified` = NOW() WHERE `webhook_delivery_id` = '" . (int)$delivery['webhook_delivery_id'] . "'");

		return $delivered;
	}

	/**
	 * Clean
	 *
	 * @param int $days
	 *
	 * @return void
	 */
	public function clean(int $days): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "webhook_delivery` WHERE `status` != '0' AND `date_added` < DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)");
	}
}
