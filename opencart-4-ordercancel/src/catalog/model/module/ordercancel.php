<?php
namespace Opencart\Catalog\Model\Extension\Ordercancel\Module;
/**
 * Customer Order Cancellation: who can cancel which order, and saving the cancellation.
 */
class Ordercancel extends \Opencart\System\Engine\Model {
	public function getCancellation(int $order_id): array {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "ordercancel` WHERE `order_id` = '" . $order_id . "'")->row;
	}

	/**
	 * The latest time the order can be cancelled (Unix time), 0 for no limit, or -1 when it cannot be cancelled.
	 * $order_info comes from account/order getOrder(), which already checks it belongs to the logged-in customer.
	 */
	public function getDeadline(array $order_info): int {
		if (!$this->config->get('module_ordercancel_status') || !$order_info) {
			return -1;
		}

		if (!in_array((string)$order_info['order_status_id'], array_map('strval', (array)$this->config->get('module_ordercancel_statuses')), true)) {
			return -1;
		}

		$hours = (int)$this->config->get('module_ordercancel_hours');

		if (!$hours) {
			return 0;
		}

		$deadline = strtotime($order_info['date_added']) + $hours * 3600;

		return $deadline > time() ? $deadline : -1;
	}

	public function addCancellation(int $order_id, int $customer_id, int $previous_status_id, string $reason, string $comment): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "ordercancel` SET `order_id` = '" . $order_id . "', `customer_id` = '" . $customer_id . "', `previous_status_id` = '" . $previous_status_id . "', `reason` = '" . $this->db->escape($reason) . "', `comment` = '" . $this->db->escape($comment) . "', `ip` = '" . $this->db->escape((string)($this->request->server['REMOTE_ADDR'] ?? '')) . "', `date_added` = NOW()");
	}
}
