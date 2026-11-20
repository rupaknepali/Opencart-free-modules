<?php
namespace Opencart\Catalog\Model\Extension\Allreviews\Product;
class Allreviews extends \Opencart\System\Engine\Model {
	/**
	 * Only approved reviews (status 1) of enabled, available products in this store.
	 * The OpenCart 3 version listed every review, including ones waiting for approval.
	 */
	private function where(): string {
		return " FROM `" . DB_PREFIX . "review` r
			LEFT JOIN `" . DB_PREFIX . "product` p ON (p.`product_id` = r.`product_id`)
			LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (pd.`product_id` = r.`product_id` AND pd.`language_id` = '" . (int)$this->config->get('config_language_id') . "')
			LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p2s.`product_id` = r.`product_id` AND p2s.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
			WHERE r.`status` = '1' AND p.`status` = '1' AND p.`date_available` <= NOW() AND p2s.`store_id` IS NOT NULL";
	}

	public function getReviews(int $start, int $limit): array {
		return $this->db->query("SELECT r.`review_id`, r.`product_id`, r.`author`, r.`text`, r.`rating`, r.`date_added`, p.`image`, pd.`name`" . $this->where() . " ORDER BY r.`date_added` DESC, r.`review_id` DESC LIMIT " . max(0, $start) . "," . max(1, $limit))->rows;
	}

	public function getTotalReviews(): int {
		return (int)$this->db->query("SELECT COUNT(*) AS `total`" . $this->where())->row['total'];
	}

	public function getAverage(): array {
		return $this->db->query("SELECT AVG(r.`rating`) AS `average`, COUNT(*) AS `total`" . $this->where())->row;
	}
}
