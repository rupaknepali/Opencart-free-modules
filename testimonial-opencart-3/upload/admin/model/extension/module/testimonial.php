<?php
class ModelExtensionModuleTestimonial extends Model
{
	/** addTestimonial method is to add testimonial which is called from controller like $this->model_extension_module_testimonial->addTestimonial($this->request->post);. Data is inserted in the oc_testimonial table, oc_testimonail_description, oc_testimonial_to_store and oc_testimonial_to_layout and cache is cleared for the testimonial variable ***/
	public function addTestimonial($data)
	{
		$this->db->query("INSERT INTO " . DB_PREFIX . "testimonial SET sort_order = '" . (int) $data['sort_order'] . "', status = '" . (int) $data['status'] . "', date_modified = NOW(), date_added = NOW()");
		$testimonial_id = $this->db->getLastId();
		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "testimonial SET image = '" . $this->db->escape($data['image']) . "' WHERE testimonial_id = '" . (int) $testimonial_id . "'");
		}
		//Testimonial Descritpion added
		foreach ($data['testimonial_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "testimonial_description SET testimonial_id = '" . (int) $testimonial_id . "', language_id = '" . (int) $language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "'");
		}
		//Set which store to use with this testimonial
		if (isset($data['testimonial_store'])) {
			foreach ($data['testimonial_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "testimonial_to_store SET testimonial_id = '" . (int) $testimonial_id . "', store_id = '" . (int) $store_id . "'");
			}
		}
		// Set which layout to use with this testimonial
		if (isset($data['testimonial_layout'])) {
			foreach ($data['testimonial_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "testimonial_to_layout SET testimonial_id = '" . (int) $testimonial_id . "', store_id = '" . (int) $store_id . "', layout_id = '" . (int) $layout_id . "'");
			}
		}
		$this->cache->delete('testimonial');
		return $testimonial_id;
	}
	/** editTestimonial method is to update the testimonial which is called from controller like $this->model_extension_module_testimonial->editTestimonial($this->request->post);. Data is updated in the oc_testimonial table, oc_testimonail_description, oc_testimonial_to_store and oc_testimonial_to_layout and cache is cleared for the testimonial variable ***/
	public function editTestimonial($testimonial_id, $data)
	{
		$this->db->query("UPDATE " . DB_PREFIX . "testimonial SET sort_order = '" . (int) $data['sort_order'] . "', status = '" . (int) $data['status'] . "', date_modified = NOW() WHERE testimonial_id = '" . (int) $testimonial_id . "'");
		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "testimonial SET image = '" . $this->db->escape($data['image']) . "' WHERE testimonial_id = '" . (int) $testimonial_id . "'");
		}
		$this->db->query("DELETE FROM " . DB_PREFIX . "testimonial_description WHERE testimonial_id = '" . (int) $testimonial_id . "'");
		foreach ($data['testimonial_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "testimonial_description SET testimonial_id = '" . (int) $testimonial_id . "', language_id = '" . (int) $language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "'");
		}
		$this->db->query("DELETE FROM " . DB_PREFIX . "testimonial_to_store WHERE testimonial_id = '" . (int) $testimonial_id . "'");
		if (isset($data['testimonial_store'])) {
			foreach ($data['testimonial_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "testimonial_to_store SET testimonial_id = '" . (int) $testimonial_id . "', store_id = '" . (int) $store_id . "'");
			}
		}
		$this->db->query("DELETE FROM " . DB_PREFIX . "testimonial_to_layout WHERE testimonial_id = '" . (int) $testimonial_id . "'");
		if (isset($data['testimonial_layout'])) {
			foreach ($data['testimonial_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "testimonial_to_layout SET testimonial_id = '" . (int) $testimonial_id . "', store_id = '" . (int) $store_id . "', layout_id = '" . (int) $layout_id . "'");
			}
		}
		$this->cache->delete('testimonial');
	}
	/** deleteTestimonial method is to delete the testimonial which is called from controller like $this->model_extension_module_testimonial->deleteTestimonial($testimonial_id);. Data is removed from the oc_testimonial table, oc_testimonail_description, oc_testimonial_to_store and oc_testimonial_to_layout and cache is cleared for the testimonial variable ***/
	public function deleteTestimonial($testimonial_id)
	{

		$this->db->query("DELETE FROM " . DB_PREFIX . "testimonial WHERE testimonial_id = '" . (int) $testimonial_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "testimonial_description WHERE testimonial_id = '" . (int) $testimonial_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "testimonial_to_store WHERE testimonial_id = '" . (int) $testimonial_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "testimonial_to_layout WHERE testimonial_id = '" . (int) $testimonial_id . "'");

		$this->cache->delete('testimonial');
	}
	/** getTestimonial method is to retrieve the testimonial which is called from controller like $testimonialcrud_info = $this->model_extension_module_testimonial->getTestimonial($this->request->get['testimonial_id']);. Only one testimonial with that testimonial_id is returned  ***/
	public function getTestimonial($testimonial_id)
	{
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "testimonial c LEFT JOIN " . DB_PREFIX . "testimonial_description cd2 ON (c.testimonial_id = cd2.testimonial_id) WHERE c.testimonial_id = '" . (int) $testimonial_id . "' AND cd2.language_id = '" . (int) $this->config->get('config_language_id') . "'");
		return $query->row;
	}
	/** getTestimonials method is to retrieve the testimonials which is called from controller like $results = $this->model_extension_module_testimonial->getTestimonials($filter_data);. $data is the filtering parameter. Multiple testimonials are returned  ***/
	public function getTestimonials($data = array())
	{
		$sql = "SELECT * FROM " . DB_PREFIX . "testimonial c1 LEFT JOIN " . DB_PREFIX . "testimonial_description cd2 ON (c1.testimonial_id = cd2.testimonial_id) WHERE cd2.language_id ='" . (int) $this->config->get('config_language_id') . "'";
		$sort_data = array(
			'name',
			'sort_order'
		);
		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY sort_order";
		}
		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}
		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}
			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}
			$sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
		}
		$query = $this->db->query($sql);
		return $query->rows;
	}
	/** getTestimonialDescriptions method is to retrieve the testimonials' description as per the language ***/
	public function getTestimonialDescriptions($testimonial_id)
	{
		$testimonial_description_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "testimonial_description WHERE testimonial_id = '" . (int) $testimonial_id . "'");
		foreach ($query->rows as $result) {
			$testimonial_description_data[$result['language_id']] = array(
				'name'             => $result['name'],
				'description'      => $result['description']
			);
		}
		return $testimonial_description_data;
	}
	/** getTestimonialStores method is to retrieve the testimonials' store of the testimonial***/
	public function getTestimonialStores($testimonial_id)
	{
		$testimonial_store_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "testimonial_to_store WHERE testimonial_id = '" . (int) $testimonial_id . "'");
		foreach ($query->rows as $result) {
			$testimonial_store_data[] = $result['store_id'];
		}
		return $testimonial_store_data;
	}
	/** getTestimonialLayouts method is to retrieve the testimonials' layout of the testimonial***/
	public function getTestimonialLayouts($testimonial_id)
	{
		$testimonial_layout_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "testimonial_to_layout WHERE testimonial_id = '" . (int) $testimonial_id . "'");
		foreach ($query->rows as $result) {
			$testimonial_layout_data[$result['store_id']] = $result['layout_id'];
		}
		return $testimonial_layout_data;
	}
	/** getTotalTestimonials method is to count the total number of testimonials ***/
	public function getTotalTestimonials()
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "testimonial");
		return $query->row['total'];
	}
	/** getTotalTestimonialsByLayoutId method is to count the total number of testimonials as per layout id ***/
	public function getTotalTestimonialsByLayoutId($layout_id)
	{
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "testimonial_to_layout WHERE layout_id = '" . (int) $layout_id . "'");
		return $query->row['total'];
	}
}