<?php
namespace Opencart\Admin\Model\Extension\AiAltText\Module;
/**
 * Class AiAltText
 *
 * image_key is the image path without its extension, because OpenCart can serve a resized copy as .webp.
 *
 * @package Opencart\Admin\Model\Extension\AiAltText\Module
 */
class AiAltText extends \Opencart\System\Engine\Model {
	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "image_alt` (
			`image_key` varchar(255) NOT NULL,
			`language_id` int(11) NOT NULL,
			`alt` varchar(255) NOT NULL,
			`source` varchar(8) NOT NULL DEFAULT 'manual',
			`date_modified` datetime NOT NULL,
			PRIMARY KEY (`image_key`, `language_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "image_alt`");
	}

	/**
	 * Get Products
	 *
	 * @param array<string, mixed> $data filter_name, filter_missing, start, limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getProducts(array $data): array {
		$sql = "SELECT `p`.`product_id`, `p`.`image`, `pd`.`name` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "')" . $this->getWhere($data) . " ORDER BY `pd`.`name` ASC LIMIT " . max(0, (int)$data['start']) . "," . max(1, (int)$data['limit']);

		return $this->db->query($sql)->rows;
	}

	/**
	 * Get Total Products
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return int
	 */
	public function getTotalProducts(array $data): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`p`.`product_id` = `pd`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "')" . $this->getWhere($data));

		return (int)$query->row['total'];
	}

	/**
	 * Get Where
	 *
	 * @param array<string, mixed> $data
	 *
	 * @return string
	 */
	private function getWhere(array $data): string {
		$where = " WHERE `p`.`image` != ''";

		if (!empty($data['filter_name'])) {
			$where .= " AND `pd`.`name` LIKE '%" . $this->db->escape((string)$data['filter_name']) . "%'";
		}

		if (!empty($data['filter_missing'])) {
			$where .= " AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "image_alt` `ia` WHERE `ia`.`image_key` = SUBSTRING(`p`.`image`, 1, CHAR_LENGTH(`p`.`image`) - CHAR_LENGTH(SUBSTRING_INDEX(`p`.`image`, '.', -1)) - 1) AND `ia`.`language_id` = '" . (int)$this->config->get('config_language_id') . "')";
		}

		return $where;
	}

	/**
	 * Get Images
	 *
	 * Main image first, then the additional images.
	 *
	 * @param int    $product_id
	 * @param string $main
	 *
	 * @return array<int, string>
	 */
	public function getImages(int $product_id, string $main): array {
		$images = $main !== '' ? [$main] : [];

		$query = $this->db->query("SELECT `image` FROM `" . DB_PREFIX . "product_image` WHERE `product_id` = '" . (int)$product_id . "' AND `image` != '' ORDER BY `sort_order` ASC");

		foreach ($query->rows as $row) {
			if (!in_array($row['image'], $images)) {
				$images[] = $row['image'];
			}
		}

		return $images;
	}

	/**
	 * Get Alts
	 *
	 * @param array<int, string> $image_keys
	 *
	 * @return array<string, array<int, array<string, string>>> image_key => language_id => [alt, source]
	 */
	public function getAlts(array $image_keys): array {
		if (!$image_keys) {
			return [];
		}

		$keys = array_map(fn ($key) => "'" . $this->db->escape($key) . "'", $image_keys);

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "image_alt` WHERE `image_key` IN (" . implode(',', $keys) . ")");

		$alts = [];

		foreach ($query->rows as $row) {
			$alts[$row['image_key']][(int)$row['language_id']] = ['alt' => $row['alt'], 'source' => $row['source']];
		}

		return $alts;
	}

	/**
	 * Edit Alt
	 *
	 * An empty alt deletes the row, so the theme's default (the product name) is used again.
	 *
	 * @param string $image_key
	 * @param int    $language_id
	 * @param string $alt
	 * @param string $source ai or manual
	 *
	 * @return void
	 */
	public function editAlt(string $image_key, int $language_id, string $alt, string $source): void {
		if (trim($alt) === '') {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "image_alt` WHERE `image_key` = '" . $this->db->escape($image_key) . "' AND `language_id` = '" . (int)$language_id . "'");

			return;
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "image_alt` SET `image_key` = '" . $this->db->escape($image_key) . "', `language_id` = '" . (int)$language_id . "', `alt` = '" . $this->db->escape(oc_substr(trim($alt), 0, 255)) . "', `source` = '" . $this->db->escape($source) . "', `date_modified` = NOW() ON DUPLICATE KEY UPDATE `alt` = VALUES(`alt`), `source` = VALUES(`source`), `date_modified` = NOW()");
	}
}
