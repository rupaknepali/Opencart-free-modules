<?php
namespace Opencart\Admin\Model\Extension\AiShopAssistant\Module;
/**
 * Class AiShopAssistant
 *
 * @package Opencart\Admin\Model\Extension\AiShopAssistant\Module
 */
class AiShopAssistant extends \Opencart\System\Engine\Model {
	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ai_assistant_log` (
			`ai_assistant_log_id` int(11) NOT NULL AUTO_INCREMENT,
			`session` varchar(64) NOT NULL,
			`ip` varchar(40) NOT NULL,
			`question` varchar(1000) NOT NULL,
			`products` varchar(1000) NOT NULL DEFAULT '',
			`input_tokens` int(11) NOT NULL DEFAULT '0',
			`output_tokens` int(11) NOT NULL DEFAULT '0',
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`ai_assistant_log_id`),
			KEY `session` (`session`, `date_added`),
			KEY `ip` (`ip`, `date_added`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ai_assistant_log`");
	}

	/**
	 * Get Logs
	 *
	 * @param int $start
	 * @param int $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getLogs(int $start, int $limit): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ai_assistant_log` ORDER BY `ai_assistant_log_id` DESC LIMIT " . max(0, $start) . "," . max(1, $limit));

		return $query->rows;
	}

	/**
	 * Get Total Logs
	 *
	 * @return int
	 */
	public function getTotalLogs(): int {
		return (int)$this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "ai_assistant_log`")->row['total'];
	}

	/**
	 * Get Statistics
	 *
	 * @param int $days
	 *
	 * @return array<string, int>
	 */
	public function getStatistics(int $days): array {
		$query = $this->db->query("SELECT COUNT(*) AS `questions`, COUNT(DISTINCT `session`) AS `conversations`, COALESCE(SUM(`input_tokens`), 0) AS `input_tokens`, COALESCE(SUM(`output_tokens`), 0) AS `output_tokens` FROM `" . DB_PREFIX . "ai_assistant_log` WHERE `date_added` > DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)");

		return array_map('intval', $query->row);
	}
}
