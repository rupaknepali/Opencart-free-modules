<?php
namespace Opencart\Admin\Model\Extension\Productqa\Module;
/**
 * Product Questions & Answers: questions table and admin queries.
 */
class Productqa extends \Opencart\System\Engine\Model {
	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_question` (
			`question_id` int(11) NOT NULL AUTO_INCREMENT,
			`product_id` int(11) NOT NULL,
			`store_id` int(11) NOT NULL DEFAULT '0',
			`language_id` int(11) NOT NULL,
			`customer_id` int(11) NOT NULL DEFAULT '0',
			`name` varchar(64) NOT NULL,
			`question` text NOT NULL,
			`answer` text NOT NULL,
			`status` tinyint(1) NOT NULL DEFAULT '0',
			`date_added` datetime NOT NULL,
			`date_answered` datetime DEFAULT NULL,
			PRIMARY KEY (`question_id`),
			KEY `product_status` (`product_id`, `status`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function getQuestions(array $data = []): array {
		$sql = "SELECT `q`.*, `pd`.`name` AS `product` FROM `" . DB_PREFIX . "product_question` `q` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `q`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "')";

		$sql .= $this->filter($data);

		$sql .= " ORDER BY `q`.`date_added` DESC LIMIT " . max(0, (int)($data['start'] ?? 0)) . "," . max(1, (int)($data['limit'] ?? 10));

		return $this->db->query($sql)->rows;
	}

	public function getTotalQuestions(array $data = []): int {
		return (int)$this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "product_question` `q`" . $this->filter($data))->row['total'];
	}

	private function filter(array $data): string {
		$filter = $data['filter'] ?? '';

		if ($filter == 'unanswered') {
			return " WHERE `q`.`answer` = ''";
		}

		if ($filter == 'published') {
			return " WHERE `q`.`status` = '1'";
		}

		if ($filter == 'hidden') {
			return " WHERE `q`.`status` = '0' AND `q`.`answer` != ''";
		}

		return '';
	}

	public function getQuestion(int $question_id): array {
		return $this->db->query("SELECT `q`.*, `pd`.`name` AS `product` FROM `" . DB_PREFIX . "product_question` `q` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `q`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `q`.`question_id` = '" . (int)$question_id . "'")->row;
	}

	public function editQuestion(int $question_id, array $data): void {
		// date_answered is set the first time an answer is saved, and kept after that.
		$this->db->query("UPDATE `" . DB_PREFIX . "product_question` SET
			`name` = '" . $this->db->escape((string)$data['name']) . "',
			`question` = '" . $this->db->escape((string)$data['question']) . "',
			`answer` = '" . $this->db->escape((string)$data['answer']) . "',
			`status` = '" . (int)$data['status'] . "',
			`date_answered` = IF(`answer` = '', NULL, COALESCE(`date_answered`, NOW()))
			WHERE `question_id` = '" . (int)$question_id . "'");
	}

	public function deleteQuestion(int $question_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_question` WHERE `question_id` = '" . (int)$question_id . "'");
	}
}
