<?php
namespace Opencart\Catalog\Model\Extension\AiShopAssistant\Module;
/**
 * Class AiShopAssistant
 *
 * @package Opencart\Catalog\Model\Extension\AiShopAssistant\Module
 */
class AiShopAssistant extends \Opencart\System\Engine\Model {
	/**
	 * Add Log
	 *
	 * @param string             $session  hashed session ID, groups one conversation
	 * @param string             $ip
	 * @param string             $question
	 * @param array<int, string> $products names of products shown
	 * @param int                $input_tokens
	 * @param int                $output_tokens
	 *
	 * @return void
	 */
	public function addLog(string $session, string $ip, string $question, array $products, int $input_tokens, int $output_tokens): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "ai_assistant_log` SET `session` = '" . $this->db->escape($session) . "', `ip` = '" . $this->db->escape($ip) . "', `question` = '" . $this->db->escape(oc_substr($question, 0, 1000)) . "', `products` = '" . $this->db->escape(oc_substr(implode(', ', $products), 0, 1000)) . "', `input_tokens` = '" . (int)$input_tokens . "', `output_tokens` = '" . (int)$output_tokens . "', `date_added` = NOW()");
	}

	/**
	 * Get Total Recent
	 *
	 * @param string $field ip or session
	 * @param string $value
	 * @param int    $minutes
	 *
	 * @return int
	 */
	public function getTotalRecent(string $field, string $value, int $minutes): int {
		$field = $field == 'session' ? 'session' : 'ip';

		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "ai_assistant_log` WHERE `" . $field . "` = '" . $this->db->escape($value) . "' AND `date_added` > DATE_SUB(NOW(), INTERVAL " . (int)$minutes . " MINUTE)");

		return (int)$query->row['total'];
	}

	/**
	 * Clean
	 *
	 * @param int $days
	 *
	 * @return void
	 */
	public function clean(int $days): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "ai_assistant_log` WHERE `date_added` < DATE_SUB(NOW(), INTERVAL " . (int)$days . " DAY)");
	}
}
