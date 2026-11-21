<?php
namespace Opencart\Catalog\Model\Extension\Buyagain\Module;
class Buyagain extends \Opencart\System\Engine\Model {
	/**
	 * Products the customer bought in this store, most recently bought first.
	 * Only orders in a processing or complete status count (not pending, cancelled or refunded).
	 */
	public function getPurchased(int $customer_id, int $limit): array {
		$statuses = array_map('intval', array_merge((array)$this->config->get('config_processing_status'), (array)$this->config->get('config_complete_status')));

		if (!$customer_id || !$statuses) {
			return [];
		}

		return $this->db->query("SELECT op.`product_id`, MAX(o.`date_added`) AS last_bought FROM `" . DB_PREFIX . "order_product` op LEFT JOIN `" . DB_PREFIX . "order` o ON (o.`order_id` = op.`order_id`) WHERE o.`customer_id` = '" . (int)$customer_id . "' AND o.`store_id` = '" . (int)$this->config->get('config_store_id') . "' AND o.`order_status_id` IN (" . implode(',', $statuses) . ") GROUP BY op.`product_id` ORDER BY last_bought DESC LIMIT " . max(1, $limit))->rows;
	}
}
