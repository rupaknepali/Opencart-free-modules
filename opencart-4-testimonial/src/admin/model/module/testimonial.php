<?php
namespace Opencart\Admin\Model\Extension\Testimonial\Module;
class Testimonial extends \Opencart\System\Engine\Model {
	/**
	 * Same table names and columns as the OpenCart 3 tutorial module, so existing testimonials carry over.
	 * The rating column is new and is added if it's missing.
	 */
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "testimonial` (
			`testimonial_id` INT(11) NOT NULL AUTO_INCREMENT,
			`image` VARCHAR(255) NOT NULL DEFAULT '',
			`rating` TINYINT(1) NOT NULL DEFAULT '5',
			`sort_order` INT(3) NOT NULL DEFAULT '0',
			`status` TINYINT(1) NOT NULL DEFAULT '1',
			`date_added` DATETIME NOT NULL,
			`date_modified` DATETIME NOT NULL,
			PRIMARY KEY (`testimonial_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		if (!$this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "testimonial` LIKE 'rating'")->num_rows) {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "testimonial` ADD `rating` TINYINT(1) NOT NULL DEFAULT '5' AFTER `image`");
		}

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "testimonial_description` (
			`testimonial_id` INT(11) NOT NULL,
			`language_id` INT(11) NOT NULL,
			`name` VARCHAR(255) NOT NULL,
			`description` TEXT NOT NULL,
			PRIMARY KEY (`testimonial_id`, `language_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function addTestimonial(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "testimonial` SET `image` = '" . $this->db->escape((string)$data['image']) . "', `rating` = '" . (int)$data['rating'] . "', `sort_order` = '" . (int)$data['sort_order'] . "', `status` = '" . (int)$data['status'] . "', `date_added` = NOW(), `date_modified` = NOW()");

		$testimonial_id = $this->db->getLastId();

		$this->saveDescriptions($testimonial_id, $data['testimonial_description']);

		return $testimonial_id;
	}

	public function editTestimonial(int $testimonial_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "testimonial` SET `image` = '" . $this->db->escape((string)$data['image']) . "', `rating` = '" . (int)$data['rating'] . "', `sort_order` = '" . (int)$data['sort_order'] . "', `status` = '" . (int)$data['status'] . "', `date_modified` = NOW() WHERE `testimonial_id` = '" . (int)$testimonial_id . "'");

		$this->saveDescriptions($testimonial_id, $data['testimonial_description']);
	}

	private function saveDescriptions(int $testimonial_id, array $descriptions): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "testimonial_description` WHERE `testimonial_id` = '" . (int)$testimonial_id . "'");

		foreach ($descriptions as $language_id => $value) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "testimonial_description` SET `testimonial_id` = '" . (int)$testimonial_id . "', `language_id` = '" . (int)$language_id . "', `name` = '" . $this->db->escape($value['name']) . "', `description` = '" . $this->db->escape($value['description']) . "'");
		}
	}

	public function deleteTestimonial(int $testimonial_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "testimonial` WHERE `testimonial_id` = '" . (int)$testimonial_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "testimonial_description` WHERE `testimonial_id` = '" . (int)$testimonial_id . "'");
	}

	public function getTestimonial(int $testimonial_id): array {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "testimonial` WHERE `testimonial_id` = '" . (int)$testimonial_id . "'")->row;
	}

	public function getDescriptions(int $testimonial_id): array {
		$descriptions = [];

		foreach ($this->db->query("SELECT * FROM `" . DB_PREFIX . "testimonial_description` WHERE `testimonial_id` = '" . (int)$testimonial_id . "'")->rows as $row) {
			$descriptions[$row['language_id']] = ['name' => $row['name'], 'description' => $row['description']];
		}

		return $descriptions;
	}

	public function getTestimonials(int $start, int $limit): array {
		return $this->db->query("SELECT t.*, td.`name`, td.`description` FROM `" . DB_PREFIX . "testimonial` t LEFT JOIN `" . DB_PREFIX . "testimonial_description` td ON (td.`testimonial_id` = t.`testimonial_id` AND td.`language_id` = '" . (int)$this->config->get('config_language_id') . "') ORDER BY t.`sort_order`, t.`testimonial_id` DESC LIMIT " . max(0, $start) . "," . max(1, $limit))->rows;
	}

	public function getTotalTestimonials(): int {
		return (int)$this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "testimonial`")->row['total'];
	}
}
