<?php
namespace Opencart\Admin\Model\Extension\Faqschema\Module;
class Faqschema extends \Opencart\System\Engine\Model {
	public function install(): void {
		// type: product, category or information. target_id 0 = every page of that type.
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "faq` (
			`faq_id` INT(11) NOT NULL AUTO_INCREMENT,
			`type` VARCHAR(16) NOT NULL,
			`target_id` INT(11) NOT NULL DEFAULT '0',
			`sort_order` INT(3) NOT NULL DEFAULT '0',
			`status` TINYINT(1) NOT NULL DEFAULT '1',
			`date_added` DATETIME NOT NULL,
			`date_modified` DATETIME NOT NULL,
			PRIMARY KEY (`faq_id`),
			KEY `type_target` (`type`, `target_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "faq_description` (
			`faq_id` INT(11) NOT NULL,
			`language_id` INT(11) NOT NULL,
			`question` VARCHAR(255) NOT NULL,
			`answer` TEXT NOT NULL,
			PRIMARY KEY (`faq_id`, `language_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	public function addFaq(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "faq` SET `type` = '" . $this->db->escape($data['type']) . "', `target_id` = '" . (int)$data['target_id'] . "', `sort_order` = '" . (int)$data['sort_order'] . "', `status` = '" . (int)$data['status'] . "', `date_added` = NOW(), `date_modified` = NOW()");

		$faq_id = $this->db->getLastId();

		$this->saveDescriptions($faq_id, $data['faq_description']);

		return $faq_id;
	}

	public function editFaq(int $faq_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "faq` SET `type` = '" . $this->db->escape($data['type']) . "', `target_id` = '" . (int)$data['target_id'] . "', `sort_order` = '" . (int)$data['sort_order'] . "', `status` = '" . (int)$data['status'] . "', `date_modified` = NOW() WHERE `faq_id` = '" . (int)$faq_id . "'");

		$this->saveDescriptions($faq_id, $data['faq_description']);
	}

	private function saveDescriptions(int $faq_id, array $descriptions): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "faq_description` WHERE `faq_id` = '" . (int)$faq_id . "'");

		foreach ($descriptions as $language_id => $value) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "faq_description` SET `faq_id` = '" . (int)$faq_id . "', `language_id` = '" . (int)$language_id . "', `question` = '" . $this->db->escape($value['question']) . "', `answer` = '" . $this->db->escape($value['answer']) . "'");
		}
	}

	public function deleteFaq(int $faq_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "faq` WHERE `faq_id` = '" . (int)$faq_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "faq_description` WHERE `faq_id` = '" . (int)$faq_id . "'");
	}

	public function getFaq(int $faq_id): array {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "faq` WHERE `faq_id` = '" . (int)$faq_id . "'")->row;
	}

	public function getDescriptions(int $faq_id): array {
		$descriptions = [];

		foreach ($this->db->query("SELECT * FROM `" . DB_PREFIX . "faq_description` WHERE `faq_id` = '" . (int)$faq_id . "'")->rows as $row) {
			$descriptions[$row['language_id']] = ['question' => $row['question'], 'answer' => $row['answer']];
		}

		return $descriptions;
	}

	/**
	 * FAQs with the question in the admin language and the name of the page they're on.
	 */
	public function getFaqs(array $filter = []): array {
		$language_id = (int)$this->config->get('config_language_id');

		$sql = "SELECT f.*, fd.`question`,
			CASE f.`type`
				WHEN 'product' THEN (SELECT pd.`name` FROM `" . DB_PREFIX . "product_description` pd WHERE pd.`product_id` = f.`target_id` AND pd.`language_id` = '" . $language_id . "')
				WHEN 'category' THEN (SELECT cd.`name` FROM `" . DB_PREFIX . "category_description` cd WHERE cd.`category_id` = f.`target_id` AND cd.`language_id` = '" . $language_id . "')
				WHEN 'information' THEN (SELECT id.`title` FROM `" . DB_PREFIX . "information_description` id WHERE id.`information_id` = f.`target_id` AND id.`language_id` = '" . $language_id . "')
			END AS `target_name`
			FROM `" . DB_PREFIX . "faq` f LEFT JOIN `" . DB_PREFIX . "faq_description` fd ON (fd.`faq_id` = f.`faq_id` AND fd.`language_id` = '" . $language_id . "')";

		if (!empty($filter['type'])) {
			$sql .= " WHERE f.`type` = '" . $this->db->escape($filter['type']) . "'";
		}

		$sql .= " ORDER BY f.`type`, f.`target_id`, f.`sort_order`, f.`faq_id`";

		$start = max(0, (int)($filter['start'] ?? 0));
		$limit = max(1, (int)($filter['limit'] ?? 20));

		return $this->db->query($sql . " LIMIT " . $start . "," . $limit)->rows;
	}

	public function getTotalFaqs(array $filter = []): int {
		$sql = "SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "faq`";

		if (!empty($filter['type'])) {
			$sql .= " WHERE `type` = '" . $this->db->escape($filter['type']) . "'";
		}

		return (int)$this->db->query($sql)->row['total'];
	}
}
