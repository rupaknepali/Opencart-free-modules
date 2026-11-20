<?php
namespace Opencart\Catalog\Model\Extension\Testimonial\Module;
class Testimonial extends \Opencart\System\Engine\Model {
	/**
	 * Enabled testimonials in the shopper's language. (The OpenCart 3 version also showed disabled ones.)
	 */
	public function getTestimonials(int $limit): array {
		return $this->db->query("SELECT t.`testimonial_id`, t.`image`, t.`rating`, td.`name`, td.`description` FROM `" . DB_PREFIX . "testimonial` t LEFT JOIN `" . DB_PREFIX . "testimonial_description` td ON (td.`testimonial_id` = t.`testimonial_id`) WHERE t.`status` = '1' AND td.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND td.`name` != '' ORDER BY t.`sort_order`, t.`testimonial_id` DESC LIMIT " . max(1, $limit))->rows;
	}
}
