<?php
namespace Opencart\Catalog\Model\Extension\AbandonedCart\Module;
/**
 * Class AbandonedCart
 *
 * Status values: 0 open, 1 recovered, 2 ordered without email, 3 unsubscribed, 4 expired
 *
 * @package Opencart\Catalog\Model\Extension\AbandonedCart\Module
 */
class AbandonedCart extends \Opencart\System\Engine\Model {
	/**
	 * Get Session Cart Rows
	 *
	 * Raw rows from oc_cart for the current visitor, so options can be re-added exactly.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getSessionCartRows(): array {
		$query = $this->db->query("SELECT `cart_id`, `product_id`, `subscription_plan_id`, `option`, `quantity` FROM `" . DB_PREFIX . "cart` WHERE `store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `customer_id` = '" . (int)$this->customer->getId() . "' AND `session_id` = '" . $this->db->escape($this->session->getId()) . "'");

		return $query->rows;
	}

	/**
	 * Get Open Cart
	 *
	 * @param int    $store_id
	 * @param string $email
	 *
	 * @return array<string, mixed>
	 */
	public function getOpenCart(int $store_id, string $email): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "abandoned_cart` WHERE `store_id` = '" . (int)$store_id . "' AND `email` = '" . $this->db->escape(oc_strtolower($email)) . "' AND `status` = '0' ORDER BY `date_modified` DESC LIMIT 1");

		return $query->row;
	}

	/**
	 * Get Cart By Token
	 *
	 * @param string $token
	 *
	 * @return array<string, mixed>
	 */
	public function getCartByToken(string $token): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "abandoned_cart` WHERE `token` = '" . $this->db->escape($token) . "'");

		return $query->row;
	}

	/**
	 * Add Cart
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function addCart(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "abandoned_cart` SET " . $this->getFields($data) . ", `token` = '" . $this->db->escape(oc_token(32)) . "', `stage` = '0', `status` = '0', `date_added` = NOW(), `date_modified` = NOW()");

		return $this->db->getLastId();
	}

	/**
	 * Edit Cart
	 *
	 * @param int                  $abandoned_cart_id
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	public function editCart(int $abandoned_cart_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "abandoned_cart` SET " . $this->getFields($data) . ", `date_modified` = NOW() WHERE `abandoned_cart_id` = '" . (int)$abandoned_cart_id . "'");
	}

	/**
	 * Delete Open Cart
	 *
	 * Used when the customer empties their cart before any email was sent.
	 *
	 * @param int $abandoned_cart_id
	 *
	 * @return void
	 */
	public function deleteOpenCart(int $abandoned_cart_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "abandoned_cart` WHERE `abandoned_cart_id` = '" . (int)$abandoned_cart_id . "' AND `status` = '0' AND `stage` = '0'");
	}

	/**
	 * Add Click
	 *
	 * @param int $abandoned_cart_id
	 *
	 * @return void
	 */
	public function addClick(int $abandoned_cart_id): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "abandoned_cart` SET `clicks` = (`clicks` + 1) WHERE `abandoned_cart_id` = '" . (int)$abandoned_cart_id . "'");
	}

	/**
	 * Close Cart
	 *
	 * Marks the open cart for this email as recovered (1) if a reminder was sent, otherwise ordered without email (2).
	 *
	 * @param int    $store_id
	 * @param string $email
	 * @param int    $order_id
	 * @param float  $order_total in the default currency
	 *
	 * @return void
	 */
	public function closeCart(int $store_id, string $email, int $order_id, float $order_total): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "abandoned_cart` SET `status` = IF(`stage` > 0, '1', '2'), `order_id` = '" . (int)$order_id . "', `order_total` = '" . (float)$order_total . "' WHERE `store_id` = '" . (int)$store_id . "' AND `email` = '" . $this->db->escape(oc_strtolower($email)) . "' AND `status` IN ('0', '4')");
	}

	/**
	 * Get Order Since
	 *
	 * Safety check before sending: has this email placed an order since the cart was created?
	 *
	 * @param int    $store_id
	 * @param string $email
	 * @param string $date
	 *
	 * @return array<string, mixed>
	 */
	public function getOrderSince(int $store_id, string $email, string $date): array {
		$query = $this->db->query("SELECT `order_id`, `total`, `currency_value` FROM `" . DB_PREFIX . "order` WHERE `store_id` = '" . (int)$store_id . "' AND LCASE(`email`) = '" . $this->db->escape(oc_strtolower($email)) . "' AND `order_status_id` > '0' AND `date_added` >= '" . $this->db->escape($date) . "' ORDER BY `order_id` DESC LIMIT 1");

		return $query->row;
	}

	/**
	 * Get Due Carts
	 *
	 * @param int   $min_delay hours of the earliest enabled stage
	 * @param int   $max_stage highest enabled stage
	 * @param float $min_total
	 * @param bool  $consent
	 * @param int   $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getDueCarts(int $min_delay, int $max_stage, float $min_total, bool $consent, int $limit = 100): array {
		// Idle time is calculated by MySQL so PHP and database time zones can't disagree.
		$sql = "SELECT *, TIMESTAMPDIFF(MINUTE, `date_modified`, NOW()) AS `idle` FROM `" . DB_PREFIX . "abandoned_cart` WHERE `status` = '0' AND `stage` < '" . (int)$max_stage . "' AND `date_modified` <= DATE_SUB(NOW(), INTERVAL " . (int)$min_delay . " HOUR) AND `total` >= '" . (float)$min_total . "'";

		if ($consent) {
			$sql .= " AND `consent` = '1'";
		}

		$sql .= " ORDER BY `date_modified` ASC LIMIT " . (int)$limit;

		return $this->db->query($sql)->rows;
	}

	/**
	 * Edit Stage
	 *
	 * @param int    $abandoned_cart_id
	 * @param int    $stage
	 * @param string $coupon_code
	 *
	 * @return void
	 */
	public function editStage(int $abandoned_cart_id, int $stage, string $coupon_code = ''): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "abandoned_cart` SET `stage` = '" . (int)$stage . "', `emails` = (`emails` + 1), `coupon_code` = '" . $this->db->escape($coupon_code) . "', `date_sent` = NOW() WHERE `abandoned_cart_id` = '" . (int)$abandoned_cart_id . "'");
	}

	/**
	 * Edit Status
	 *
	 * @param int $abandoned_cart_id
	 * @param int $status
	 *
	 * @return void
	 */
	public function editStatus(int $abandoned_cart_id, int $status): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "abandoned_cart` SET `status` = '" . (int)$status . "' WHERE `abandoned_cart_id` = '" . (int)$abandoned_cart_id . "'");
	}

	/**
	 * Expire And Clean
	 *
	 * @param int $retention days
	 *
	 * @return void
	 */
	public function expireAndClean(int $retention): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "abandoned_cart` SET `status` = '4' WHERE `status` = '0' AND `date_modified` < DATE_SUB(NOW(), INTERVAL 14 DAY)");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "abandoned_cart` WHERE `date_added` < DATE_SUB(NOW(), INTERVAL " . max(15, $retention) . " DAY)");
	}

	/**
	 * Is Unsubscribed
	 *
	 * @param string $email
	 *
	 * @return bool
	 */
	public function isUnsubscribed(string $email): bool {
		$query = $this->db->query("SELECT `email` FROM `" . DB_PREFIX . "abandoned_cart_unsubscribe` WHERE `email` = '" . $this->db->escape(oc_strtolower($email)) . "'");

		return (bool)$query->num_rows;
	}

	/**
	 * Unsubscribe
	 *
	 * @param string $email
	 *
	 * @return void
	 */
	public function unsubscribe(string $email): void {
		$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "abandoned_cart_unsubscribe` SET `email` = '" . $this->db->escape(oc_strtolower($email)) . "', `date_added` = NOW()");
		$this->db->query("UPDATE `" . DB_PREFIX . "abandoned_cart` SET `status` = '3' WHERE `email` = '" . $this->db->escape(oc_strtolower($email)) . "' AND `status` = '0'");
	}

	/**
	 * Add Coupon
	 *
	 * Creates a unique single-use percentage coupon.
	 *
	 * @param float $discount
	 * @param int   $days
	 *
	 * @return array<string, string>
	 */
	public function addCoupon(float $discount, int $days): array {
		do {
			$code = 'AC' . strtoupper(oc_token(8));

			$query = $this->db->query("SELECT `coupon_id` FROM `" . DB_PREFIX . "coupon` WHERE `code` = '" . $this->db->escape($code) . "'");
		} while ($query->num_rows);

		$date_end = date('Y-m-d', strtotime('+' . max(1, $days) . ' days'));

		$this->db->query("INSERT INTO `" . DB_PREFIX . "coupon` SET `name` = '" . $this->db->escape('Abandoned cart ' . $code) . "', `code` = '" . $this->db->escape($code) . "', `type` = 'P', `discount` = '" . (float)$discount . "', `logged` = '0', `shipping` = '0', `total` = '0', `date_start` = '" . date('Y-m-d', strtotime('-1 day')) . "', `date_end` = '" . $this->db->escape($date_end) . "', `uses_total` = '1', `uses_customer` = '1', `status` = '1', `date_added` = NOW()");

		return [
			'code'     => $code,
			'date_end' => $date_end
		];
	}

	/**
	 * Get Coupon
	 *
	 * @param string $code
	 *
	 * @return array<string, mixed>
	 */
	public function getCoupon(string $code): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "coupon` WHERE `code` = '" . $this->db->escape($code) . "'");

		return $query->row;
	}

	/**
	 * Get Fields
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return string
	 */
	private function getFields(array $data): string {
		return "`store_id` = '" . (int)$data['store_id'] . "', `customer_id` = '" . (int)$data['customer_id'] . "', `session_id` = '" . $this->db->escape($data['session_id']) . "', `email` = '" . $this->db->escape(oc_strtolower($data['email'])) . "', `firstname` = '" . $this->db->escape(oc_substr($data['firstname'], 0, 32)) . "', `language_id` = '" . (int)$data['language_id'] . "', `language_code` = '" . $this->db->escape($data['language_code']) . "', `currency_code` = '" . $this->db->escape($data['currency_code']) . "', `products` = '" . $this->db->escape(json_encode($data['products'])) . "', `total` = '" . (float)$data['total'] . "', `total_text` = '" . $this->db->escape($data['total_text']) . "', `consent` = '" . (int)$data['consent'] . "'";
	}
}
