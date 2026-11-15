<?php
namespace Opencart\Admin\Model\Extension\Sizechart\Module;
/**
 * Size Chart: tables and admin queries.
 */
class Sizechart extends \Opencart\System\Engine\Model {
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "size_chart` (
			`size_chart_id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(64) NOT NULL,
			`content` text NOT NULL,
			`note` text NOT NULL,
			`sort_order` int(3) NOT NULL DEFAULT '0',
			`status` tinyint(1) NOT NULL DEFAULT '1',
			PRIMARY KEY (`size_chart_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "size_chart_to_category` (
			`size_chart_id` int(11) NOT NULL,
			`category_id` int(11) NOT NULL,
			PRIMARY KEY (`size_chart_id`, `category_id`),
			KEY `category_id` (`category_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function addChart(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "size_chart` SET " . $this->fields($data));

		$size_chart_id = $this->db->getLastId();

		$this->saveCategories($size_chart_id, $data['category'] ?? []);

		return $size_chart_id;
	}

	public function editChart(int $size_chart_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "size_chart` SET " . $this->fields($data) . " WHERE `size_chart_id` = '" . (int)$size_chart_id . "'");

		$this->saveCategories($size_chart_id, $data['category'] ?? []);
	}

	private function fields(array $data): string {
		return "`name` = '" . $this->db->escape((string)$data['name']) . "', `content` = '" . $this->db->escape((string)$data['content']) . "', `note` = '" . $this->db->escape((string)($data['note'] ?? '')) . "', `sort_order` = '" . (int)($data['sort_order'] ?? 0) . "', `status` = '" . (int)!empty($data['status']) . "'";
	}

	private function saveCategories(int $size_chart_id, array $categories): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "size_chart_to_category` WHERE `size_chart_id` = '" . (int)$size_chart_id . "'");

		foreach (array_unique(array_map('intval', $categories)) as $category_id) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "size_chart_to_category` SET `size_chart_id` = '" . (int)$size_chart_id . "', `category_id` = '" . (int)$category_id . "'");
		}
	}

	public function deleteChart(int $size_chart_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "size_chart` WHERE `size_chart_id` = '" . (int)$size_chart_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "size_chart_to_category` WHERE `size_chart_id` = '" . (int)$size_chart_id . "'");
	}

	public function getChart(int $size_chart_id): array {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "size_chart` WHERE `size_chart_id` = '" . (int)$size_chart_id . "'")->row;
	}

	public function getCharts(): array {
		return $this->db->query("SELECT `sc`.*, (SELECT COUNT(*) FROM `" . DB_PREFIX . "size_chart_to_category` `s2c` WHERE `s2c`.`size_chart_id` = `sc`.`size_chart_id`) AS `categories` FROM `" . DB_PREFIX . "size_chart` `sc` ORDER BY `sc`.`sort_order`, `sc`.`name`")->rows;
	}

	public function getCategories(int $size_chart_id): array {
		return array_column($this->db->query("SELECT `category_id` FROM `" . DB_PREFIX . "size_chart_to_category` WHERE `size_chart_id` = '" . (int)$size_chart_id . "'")->rows, 'category_id');
	}
}
