<?php
namespace Opencart\Catalog\Model\Extension\Productqa\Module;
/**
 * Product Questions & Answers: storefront queries.
 */
class Productqa extends \Opencart\System\Engine\Model {
	public function getQuestions(int $product_id): array {
		return $this->db->query("SELECT `name`, `question`, `answer`, `date_added`, `date_answered` FROM `" . DB_PREFIX . "product_question` WHERE `product_id` = '" . (int)$product_id . "' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "' AND `status` = '1' AND `answer` != '' ORDER BY `date_added` DESC")->rows;
	}

	public function addQuestion(int $product_id, string $name, string $question): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "product_question` SET
			`product_id` = '" . (int)$product_id . "',
			`store_id` = '" . (int)$this->config->get('config_store_id') . "',
			`language_id` = '" . (int)$this->config->get('config_language_id') . "',
			`customer_id` = '" . (int)$this->customer->getId() . "',
			`name` = '" . $this->db->escape($name) . "',
			`question` = '" . $this->db->escape($question) . "',
			`answer` = '',
			`status` = '0',
			`date_added` = NOW()");
	}
}
