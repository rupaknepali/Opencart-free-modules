<?php
class ModelDesignTestimonial extends Model {
	public function addTestimonial($data) {



		$this->db->query("INSERT INTO " . DB_PREFIX . "testimonial SET name = '" . $this->db->escape($data['name']) . "', status = '" . (int)$data['status'] . "'");

		$testimonial_id = $this->db->getLastId();

		if (isset($data['testimonial_image'])) {
			foreach ($data['testimonial_image'] as $testimonial_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "testimonial_image SET testimonial_id = '" . (int)$testimonial_id . "', link = '" .  $this->db->escape($testimonial_image['link']) . "', image = '" .  $this->db->escape($testimonial_image['image']) . "', sort_order = '0'");

				$testimonial_image_id = $this->db->getLastId();

				foreach ($testimonial_image['testimonial_image_description'] as $language_id => $testimonial_image_description) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "testimonial_image_description SET testimonial_image_id = '" . (int)$testimonial_image_id . "', language_id = '" . (int)$language_id . "', testimonial_id = '" . (int)$testimonial_id . "', title = '" .  $this->db->escape($testimonial_image_description['title']) . "', message = '" .  $this->db->escape($testimonial_image_description['message']) . "', name = '" .  $this->db->escape($testimonial_image_description['name']) . "', position = '" .  $this->db->escape($testimonial_image_description['position']) . "'");
				}
			}
		}

		return $testimonial_id;
	}

	public function editTestimonial($testimonial_id, $data) {



		$this->db->query("UPDATE " . DB_PREFIX . "testimonial SET name = '" . $this->db->escape($data['name']) . "', status = '" . (int)$data['status'] . "' WHERE testimonial_id = '" . (int)$testimonial_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "testimonial_image WHERE testimonial_id = '" . (int)$testimonial_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "testimonial_image_description WHERE testimonial_id = '" . (int)$testimonial_id . "'");

		if (isset($data['testimonial_image'])) {
			foreach ($data['testimonial_image'] as $testimonial_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "testimonial_image SET testimonial_id = '" . (int)$testimonial_id . "', link = '" .  $this->db->escape($testimonial_image['link']) . "', image = '" .  $this->db->escape($testimonial_image['image']) . "', sort_order = '" . (int)$testimonial_image['sort_order'] . "'");

				$testimonial_image_id = $this->db->getLastId();

				foreach ($testimonial_image['testimonial_image_description'] as $language_id => $testimonial_image_description) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "testimonial_image_description SET testimonial_image_id = '" . (int)$testimonial_image_id . "', language_id = '" . (int)$language_id . "', testimonial_id = '" . (int)$testimonial_id . "', title = '" .  $this->db->escape($testimonial_image_description['title']) . "', message = '" .  $this->db->escape($testimonial_image_description['message']) . "', name = '" .  $this->db->escape($testimonial_image_description['name']) . "', position = '" .  $this->db->escape($testimonial_image_description['position']) . "'");
				}
			}
		}
	}

	public function deleteTestimonial($testimonial_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "testimonial WHERE testimonial_id = '" . (int)$testimonial_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "testimonial_image WHERE testimonial_id = '" . (int)$testimonial_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "testimonial_image_description WHERE testimonial_id = '" . (int)$testimonial_id . "'");
	}

	public function getTestimonial($testimonial_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "testimonial WHERE testimonial_id = '" . (int)$testimonial_id . "'");

		return $query->row;
	}

	public function getTestimonials($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "testimonial";

		$sort_data = array(
			'name',
			'status'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
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

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTestimonialImages($testimonial_id) {
		$testimonial_image_data = array();

		$testimonial_image_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "testimonial_image WHERE testimonial_id = '" . (int)$testimonial_id . "' ORDER BY sort_order ASC");

		foreach ($testimonial_image_query->rows as $testimonial_image) {
			$testimonial_image_description_data = array();

			$testimonial_image_description_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "testimonial_image_description WHERE testimonial_image_id = '" . (int)$testimonial_image['testimonial_image_id'] . "' AND testimonial_id = '" . (int)$testimonial_id . "'");

			foreach ($testimonial_image_description_query->rows as $testimonial_image_description) {
				$testimonial_image_description_data[$testimonial_image_description['language_id']] = array('title' => $testimonial_image_description['title'], 'message' => $testimonial_image_description['message'], 'name' => $testimonial_image_description['name'], 'position' => $testimonial_image_description['position']);
			}

			$testimonial_image_data[] = array(
				'testimonial_image_description' => $testimonial_image_description_data,
				'link'                     => $testimonial_image['link'],
				'image'                    => $testimonial_image['image'],
				'sort_order'               => $testimonial_image['sort_order']
			);
		}

		return $testimonial_image_data;
	}

	public function getTotalTestimonials() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "testimonial");

		return $query->row['total'];
	}
}
