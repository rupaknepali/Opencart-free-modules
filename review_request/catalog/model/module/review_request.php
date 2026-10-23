<?php
namespace Opencart\Catalog\Model\Extension\ReviewRequest\Module;
/**
 * Class ReviewRequest
 *
 * Status values: 1 sent, 2 reviewed, 3 unsubscribed, 4 skipped
 *
 * @package Opencart\Catalog\Model\Extension\ReviewRequest\Module
 */
class ReviewRequest extends \Opencart\System\Engine\Model {
	/**
	 * Get Due Orders
	 *
	 * Orders that reached one of the statuses $delay days ago (with a 14 day window) and have no request yet.
	 *
	 * @param array<int, int> $order_status_ids
	 * @param int             $delay days
	 * @param string          $start Y-m-d
	 * @param int             $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getDueOrders(array $order_status_ids, int $delay, string $start, int $limit = 50): array {
		$order_status_ids = array_filter(array_map('intval', $order_status_ids));

		if (!$order_status_ids) {
			return [];
		}

		$statuses = implode(',', $order_status_ids);

		// Status dates are compared by MySQL so PHP and database time zones can't disagree.
		$query = $this->db->query("SELECT `o`.`order_id`, `o`.`store_id`, `o`.`customer_id`, `o`.`email`, `o`.`firstname`, `o`.`lastname`, `o`.`language_code`, MIN(`oh`.`date_added`) AS `status_date` FROM `" . DB_PREFIX . "order` `o` INNER JOIN `" . DB_PREFIX . "order_history` `oh` ON (`oh`.`order_id` = `o`.`order_id` AND `oh`.`order_status_id` IN (" . $statuses . ")) LEFT JOIN `" . DB_PREFIX . "review_request` `rr` ON (`rr`.`order_id` = `o`.`order_id`) WHERE `rr`.`order_id` IS NULL AND `o`.`order_status_id` IN (" . $statuses . ") AND `o`.`email` != '' GROUP BY `o`.`order_id`, `o`.`store_id`, `o`.`customer_id`, `o`.`email`, `o`.`firstname`, `o`.`lastname`, `o`.`language_code` HAVING `status_date` <= DATE_SUB(NOW(), INTERVAL " . (int)$delay . " DAY) AND `status_date` > DATE_SUB(NOW(), INTERVAL " . ((int)$delay + 14) . " DAY) AND `status_date` >= '" . $this->db->escape($start) . "' ORDER BY `status_date` ASC LIMIT " . max(1, $limit));

		return $query->rows;
	}

	/**
	 * Get Order Products
	 *
	 * @param int $order_id
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getOrderProducts(int $order_id): array {
		$query = $this->db->query("SELECT `product_id`, `master_id`, `name` FROM `" . DB_PREFIX . "order_product` WHERE `order_id` = '" . (int)$order_id . "' ORDER BY `order_product_id` ASC");

		return $query->rows;
	}

	/**
	 * Has Reviewed
	 *
	 * @param int $customer_id
	 * @param int $product_id
	 *
	 * @return bool
	 */
	public function hasReviewed(int $customer_id, int $product_id): bool {
		if (!$customer_id) {
			return false;
		}

		$query = $this->db->query("SELECT `review_id` FROM `" . DB_PREFIX . "review` WHERE `customer_id` = '" . (int)$customer_id . "' AND `product_id` = '" . (int)$product_id . "' LIMIT 1");

		return (bool)$query->num_rows;
	}

	/**
	 * Add Request
	 *
	 * @param array<string, mixed> $data
	 * @param int                  $status
	 *
	 * @return string token
	 */
	public function addRequest(array $data, int $status): string {
		$token = oc_token(32);

		$this->db->query("INSERT INTO `" . DB_PREFIX . "review_request` SET `order_id` = '" . (int)$data['order_id'] . "', `store_id` = '" . (int)$data['store_id'] . "', `customer_id` = '" . (int)$data['customer_id'] . "', `email` = '" . $this->db->escape(oc_strtolower($data['email'])) . "', `firstname` = '" . $this->db->escape(oc_substr($data['firstname'], 0, 32)) . "', `author` = '" . $this->db->escape(oc_substr($data['author'], 0, 64)) . "', `language_code` = '" . $this->db->escape($data['language_code']) . "', `products` = '" . $this->db->escape(json_encode($data['products'])) . "', `token` = '" . $this->db->escape($token) . "', `status` = '" . (int)$status . "', `emails` = '0', `date_added` = NOW()");

		return $token;
	}

	/**
	 * Delete Request
	 *
	 * Used when the first email could not be sent, so the order is tried again next hour.
	 *
	 * @param string $token
	 *
	 * @return void
	 */
	public function deleteRequest(string $token): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "review_request` WHERE `token` = '" . $this->db->escape($token) . "' AND `emails` = '0'");
	}

	/**
	 * Get Request By Token
	 *
	 * @param string $token
	 *
	 * @return array<string, mixed>
	 */
	public function getRequestByToken(string $token): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "review_request` WHERE `token` = '" . $this->db->escape($token) . "'");

		return $query->row;
	}

	/**
	 * Get Due Reminders
	 *
	 * @param int $delay days after the first email
	 * @param int $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getDueReminders(int $delay, int $limit = 50): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "review_request` WHERE `status` = '1' AND `reviews` = '0' AND `emails` = '1' AND `date_sent` <= DATE_SUB(NOW(), INTERVAL " . (int)$delay . " DAY) ORDER BY `date_sent` ASC LIMIT " . max(1, $limit));

		return $query->rows;
	}

	/**
	 * Edit Sent
	 *
	 * @param int $review_request_id
	 *
	 * @return void
	 */
	public function editSent(int $review_request_id): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "review_request` SET `emails` = (`emails` + 1), `date_sent` = IF(`date_sent` IS NULL, NOW(), `date_sent`) WHERE `review_request_id` = '" . (int)$review_request_id . "'");
	}

	/**
	 * Add Click
	 *
	 * @param int $review_request_id
	 *
	 * @return void
	 */
	public function addClick(int $review_request_id): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "review_request` SET `clicks` = (`clicks` + 1) WHERE `review_request_id` = '" . (int)$review_request_id . "'");
	}

	/**
	 * Add Reviewed
	 *
	 * @param int                              $review_request_id
	 * @param array<int, array<string, mixed>> $products with the new review_id and rating filled in
	 * @param int                              $rating
	 *
	 * @return void
	 */
	public function addReviewed(int $review_request_id, array $products, int $rating): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "review_request` SET `products` = '" . $this->db->escape(json_encode($products)) . "', `reviews` = (`reviews` + 1), `rating_total` = (`rating_total` + " . (int)$rating . "), `status` = IF(`status` = '3', '3', '2'), `date_reviewed` = NOW() WHERE `review_request_id` = '" . (int)$review_request_id . "'");
	}

	/**
	 * Edit Review
	 *
	 * Links the review to the customer who placed the order and sets its status.
	 *
	 * @param int $review_id
	 * @param int $customer_id
	 * @param int $status
	 *
	 * @return void
	 */
	public function editReview(int $review_id, int $customer_id, int $status): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "review` SET `customer_id` = '" . (int)$customer_id . "', `status` = '" . (int)$status . "' WHERE `review_id` = '" . (int)$review_id . "'");
	}

	/**
	 * Edit Product Rating
	 *
	 * Same calculation as the admin review form, which normally keeps product.rating up to date.
	 *
	 * @param int $product_id
	 *
	 * @return void
	 */
	public function editProductRating(int $product_id): void {
		$query = $this->db->query("SELECT AVG(`rating`) AS `total` FROM `" . DB_PREFIX . "review` WHERE `product_id` = '" . (int)$product_id . "' AND `status` = '1'");

		$this->db->query("UPDATE `" . DB_PREFIX . "product` SET `rating` = '" . (int)$query->row['total'] . "', `date_modified` = NOW() WHERE `product_id` = '" . (int)$product_id . "'");

		$this->cache->delete('product');
	}

	/**
	 * Is Unsubscribed
	 *
	 * @param string $email
	 *
	 * @return bool
	 */
	public function isUnsubscribed(string $email): bool {
		$query = $this->db->query("SELECT `email` FROM `" . DB_PREFIX . "review_request_unsubscribe` WHERE `email` = '" . $this->db->escape(oc_strtolower($email)) . "'");

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
		$this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "review_request_unsubscribe` SET `email` = '" . $this->db->escape(oc_strtolower($email)) . "', `date_added` = NOW()");
		$this->db->query("UPDATE `" . DB_PREFIX . "review_request` SET `status` = '3' WHERE `email` = '" . $this->db->escape(oc_strtolower($email)) . "' AND `status` = '1'");
	}

	/**
	 * Clean
	 *
	 * @param int $retention days
	 *
	 * @return void
	 */
	public function clean(int $retention): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "review_request` WHERE `date_added` < DATE_SUB(NOW(), INTERVAL " . max(90, $retention) . " DAY)");
	}
}
