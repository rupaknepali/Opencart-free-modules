<?php
class ModelDesignTestimonial extends Model {
	public function getTestimonial($testimonial_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "testimonial_image bi LEFT JOIN " . DB_PREFIX . "testimonial_image_description bid ON (bi.testimonial_image_id  = bid.testimonial_image_id) WHERE bi.testimonial_id = '" . (int)$testimonial_id . "' AND bid.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY bi.sort_order ASC");
		return $query->rows;
	}
}