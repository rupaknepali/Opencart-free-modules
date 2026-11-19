<?php
namespace Opencart\Catalog\Model\Extension\Faqschema\Module;
class Faqschema extends \Opencart\System\Engine\Model {
	/**
	 * FAQs for one page: the page's own questions first, then the ones shared by every page of the type.
	 */
	public function getFaqs(string $type, int $target_id): array {
		return $this->db->query("SELECT fd.`question`, fd.`answer` FROM `" . DB_PREFIX . "faq` f LEFT JOIN `" . DB_PREFIX . "faq_description` fd ON (fd.`faq_id` = f.`faq_id`) WHERE f.`type` = '" . $this->db->escape($type) . "' AND f.`target_id` IN ('" . (int)$target_id . "', '0') AND f.`status` = '1' AND fd.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND fd.`question` != '' ORDER BY (f.`target_id` = 0), f.`sort_order`, f.`faq_id`")->rows;
	}
}
