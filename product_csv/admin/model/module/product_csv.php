<?php
namespace Opencart\Admin\Model\Extension\ProductCsv\Module;
/**
 * Class ProductCsv
 *
 * @package Opencart\Admin\Model\Extension\ProductCsv\Module
 */
class ProductCsv extends \Opencart\System\Engine\Model {
	/**
	 * Get Products
	 *
	 * One row per product with its SKU from the product identifiers (product_code table).
	 *
	 * @param int $start
	 * @param int $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getProducts(int $start, int $limit): array {
		$query = $this->db->query("SELECT `p`.`product_id`, `p`.`master_id`, `p`.`model`, (SELECT `pc`.`value` FROM `" . DB_PREFIX . "product_code` `pc` WHERE `pc`.`product_id` = `p`.`product_id` AND `pc`.`code` = 'SKU' LIMIT 1) AS `sku`, `pd`.`name`, `p`.`price`, `p`.`quantity`, `p`.`status` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') ORDER BY `p`.`product_id` ASC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	/**
	 * Find Products
	 *
	 * @param string $key   product_id, model or sku
	 * @param string $value
	 *
	 * @return array<int, array<string, mixed>> all matching products (more than one means the key is not unique)
	 */
	public function findProducts(string $key, string $value): array {
		$select = "SELECT `p`.`product_id`, `p`.`master_id`, `p`.`model`, `pd`.`name`, `p`.`price`, `p`.`quantity`, `p`.`status` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "')";

		if ($key == 'product_id') {
			$query = $this->db->query($select . " WHERE `p`.`product_id` = '" . (int)$value . "'");
		} elseif ($key == 'model') {
			$query = $this->db->query($select . " WHERE `p`.`model` = '" . $this->db->escape($value) . "'");
		} else {
			$query = $this->db->query($select . " WHERE `p`.`product_id` IN (SELECT `product_id` FROM `" . DB_PREFIX . "product_code` WHERE `code` = 'SKU' AND `value` = '" . $this->db->escape($value) . "')");
		}

		return $query->rows;
	}

	/**
	 * Edit Product
	 *
	 * @param int                  $product_id
	 * @param array<string, mixed> $data price, quantity and/or status
	 *
	 * @return void
	 */
	public function editProduct(int $product_id, array $data): void {
		$set = [];

		if (isset($data['price'])) {
			$set[] = "`price` = '" . (float)$data['price'] . "'";
		}

		if (isset($data['quantity'])) {
			$set[] = "`quantity` = '" . (int)$data['quantity'] . "'";
		}

		if (isset($data['status'])) {
			$set[] = "`status` = '" . (int)$data['status'] . "'";
		}

		if ($set) {
			$this->db->query("UPDATE `" . DB_PREFIX . "product` SET " . implode(', ', $set) . ", `date_modified` = NOW() WHERE `product_id` = '" . (int)$product_id . "'");
		}
	}
}
