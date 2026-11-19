<?php
namespace Opencart\Admin\Model\Extension\Csp\Module;
/**
 * Content Security Policy: violation report table.
 */
class Csp extends \Opencart\System\Engine\Model {
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "csp_report` (
			`report_id` INT(11) NOT NULL AUTO_INCREMENT,
			`hash` CHAR(32) NOT NULL,
			`policy` VARCHAR(8) NOT NULL,
			`directive` VARCHAR(40) NOT NULL,
			`blocked_uri` VARCHAR(255) NOT NULL,
			`document_uri` VARCHAR(255) NOT NULL,
			`source_file` VARCHAR(255) NOT NULL,
			`line` INT(11) NOT NULL DEFAULT '0',
			`sample` VARCHAR(255) NOT NULL,
			`count` INT(11) NOT NULL DEFAULT '1',
			`date_added` DATETIME NOT NULL,
			`date_modified` DATETIME NOT NULL,
			PRIMARY KEY (`report_id`),
			UNIQUE KEY `hash` (`hash`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function uninstall(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "csp_report`");
	}

	public function getReports(int $start = 0, int $limit = 10): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "csp_report` ORDER BY `date_modified` DESC, `report_id` DESC LIMIT " . max(0, $start) . "," . max(1, $limit));

		return $query->rows;
	}

	public function getTotalReports(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "csp_report`");

		return (int)$query->row['total'];
	}

	public function getReport(int $report_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "csp_report` WHERE `report_id` = '" . (int)$report_id . "'");

		return $query->row;
	}

	public function deleteReport(int $report_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "csp_report` WHERE `report_id` = '" . (int)$report_id . "'");
	}

	/**
	 * After allowing a source, its other reports (other pages, either policy) are resolved too.
	 */
	public function deleteReportsByBlocked(string $directive_prefix, string $blocked): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "csp_report` WHERE `directive` LIKE '" . $this->db->escape(addcslashes($directive_prefix, '%_\\')) . "%' AND (`blocked_uri` = '" . $this->db->escape($blocked) . "' OR `blocked_uri` LIKE '" . $this->db->escape(addcslashes($blocked, '%_\\')) . "/%')");
	}

	public function clearReports(): void {
		$this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "csp_report`");
	}
}
