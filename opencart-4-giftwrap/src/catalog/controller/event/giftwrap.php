<?php
namespace Opencart\Catalog\Controller\Extension\Giftwrap\Event;
/**
 * Gift wrap: keeps the gift message with the order and shows it in the customer's order email.
 */
class Giftwrap extends \Opencart\System\Engine\Controller {
	/**
	 * catalog/model/checkout/order.addOrder/after: $output is the new order ID.
	 */
	public function addOrder(string &$route, array &$args, mixed &$output): void {
		$this->store((int)$output);
	}

	/**
	 * catalog/model/checkout/order.editOrder/after: checkout updates the same order when the customer goes back.
	 */
	public function editOrder(string &$route, array &$args, mixed &$output): void {
		$this->store((int)($args[0] ?? 0));
	}

	private function store(int $order_id): void {
		if (!$order_id) {
			return;
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "giftwrap_order` WHERE `order_id` = '" . $order_id . "'");

		if (!empty($this->session->data['giftwrap']) && $this->config->get('total_giftwrap_status')) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "giftwrap_order` SET `order_id` = '" . $order_id . "', `message` = '" . $this->db->escape((string)($this->session->data['giftwrap_message'] ?? '')) . "', `date_added` = NOW()");
		}
	}

	/**
	 * catalog/controller/checkout/success/before: the order is done, so the next one starts unwrapped.
	 */
	public function success(string &$route, array &$args): void {
		if (isset($this->session->data['order_id'])) {
			unset($this->session->data['giftwrap'], $this->session->data['giftwrap_message']);
		}
	}

	/**
	 * catalog/view/mail/order_add/before: the gift message in the customer's order confirmation email.
	 */
	public function mail(string &$route, array &$args, string &$code): void {
		$order_id = (int)($args['order_id'] ?? 0);

		$row = $order_id ? $this->db->query("SELECT `message` FROM `" . DB_PREFIX . "giftwrap_order` WHERE `order_id` = '" . $order_id . "'")->row : [];

		if (!$row) {
			return;
		}

		$this->load->language('extension/giftwrap/checkout/giftwrap', 'giftwrap');

		$note = '<strong>' . $this->language->get('giftwrap_text_total') . '</strong>' . ($row['message'] !== '' ? ': ' . nl2br(htmlspecialchars($row['message'])) : '');

		$args['comment'] = (!empty($args['comment']) ? $args['comment'] . '<br/><br/>' : '') . $note;
	}
}
