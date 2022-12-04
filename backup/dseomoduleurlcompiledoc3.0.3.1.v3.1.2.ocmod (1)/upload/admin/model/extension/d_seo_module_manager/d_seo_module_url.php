<?php
class ModelExtensionDSEOModuleManagerDSEOModuleURL extends Model {
	private $codename = 'd_seo_module_url';
	
	/*
	*	Return List Elements for Manager.
	*/
	public function getListElements($data) {			
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
				
		if ($data['sheet_code'] == 'category') {
			$implode = array();
			$implode[] = "c.category_id";
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'url_keyword') {
						$implode[] = "uk.keyword as url_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
							
						if (VERSION >= '3.0.0.0') {
							$add .= " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('category_id=', c.category_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = '" . (int)$data['language_id'] . "') LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = CONCAT('category_id=', c.category_id) AND uk2.store_id = '" . (int)$url_keyword_store_id . "')";
						} else {
							$add .= " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('category_id=', c.category_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = '" . (int)$data['language_id'] . "') LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = CONCAT('category_id=', c.category_id) AND uk2.store_id = '" . (int)$url_keyword_store_id . "')";
						}
					}
				}
			}
			
			$sql = "SELECT " . implode(', ', $implode) . " FROM " . DB_PREFIX . "category c" . $add;
			
			$implode = array();
			
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'url_keyword') {
						$implode[] = "uk2.keyword LIKE '%" . $this->db->escape($filter) . "%'";
					}
				}
			}
						
			if ($implode) {
				$sql .= " WHERE " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY c.category_id";
			
			$query = $this->db->query($sql);
			
			$categories = array();
			
			foreach ($query->rows as $result) {
				$categories[$result['category_id']] = $result;
			}

			return $categories;	
		}
		
		if ($data['sheet_code'] == 'product') {
			$implode = array();
			$implode[] = "p.product_id";
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'category_id') {
						$implode[] = "pc.category_id";
					}
					
					if ($field['code'] == 'url_keyword') {
						$implode[] = "uk.keyword as url_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
							
						if (VERSION >= '3.0.0.0') {
							$add .= " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('product_id=', p.product_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = '" . (int)$data['language_id'] . "') LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = CONCAT('product_id=', p.product_id) AND uk2.store_id = '" . (int)$url_keyword_store_id . "')";
						} else {
							$add .= " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('product_id=', p.product_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = '" . (int)$data['language_id'] . "') LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = CONCAT('product_id=', p.product_id) AND uk2.store_id = '" . (int)$url_keyword_store_id . "')";
						}
					}
				}
			}
			
			$sql = "SELECT " . implode(', ', $implode) . " FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "d_product_category pc ON (pc.product_id = p.product_id)" . $add;
			
			$implode = array();
			
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'category_id') {
						$implode[] = "pc.category_id = '" . $this->db->escape($filter) . "'";
					}
					
					if ($field_code == 'url_keyword') {
						$implode[] = "uk2.keyword LIKE '%" . $this->db->escape($filter) . "%'";
					}
				}
			}
									
			if ($implode) {
				$sql .= " WHERE " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY p.product_id";
			
			$query = $this->db->query($sql);
			
			$products = array();
			
			foreach ($query->rows as $result) {
				$products[$result['product_id']] = $result;
			}

			return $products;	
		}
		
		if ($data['sheet_code'] == 'manufacturer') {
			$implode = array();
			$implode[] = "m.manufacturer_id";
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'url_keyword') {
						$implode[] = "uk.keyword as url_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
							
						if (VERSION >= '3.0.0.0') {
							$add .= " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = '" . (int)$data['language_id'] . "') LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk2.store_id = '" . (int)$url_keyword_store_id . "')";
						} else {
							$add .= " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = '" . (int)$data['language_id'] . "') LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk2.store_id = '" . (int)$url_keyword_store_id . "')";
						}
					}
				}
			}
			
			$sql = "SELECT " . implode(', ', $implode) . " FROM " . DB_PREFIX . "manufacturer m" . $add;
			
			$implode = array();
			
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'url_keyword') {
						$implode[] = "uk2.keyword LIKE '%" . $this->db->escape($filter) . "%'";
					}
				}
			}
			
			if ($implode) {
				$sql .= " WHERE " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY m.manufacturer_id";
			
			$query = $this->db->query($sql);
			
			$manufacturers = array();
			
			foreach ($query->rows as $result) {
				$manufacturers[$result['manufacturer_id']] = $result;
			}

			return $manufacturers;	
		}
		
		if ($data['sheet_code'] == 'information') {
			$implode = array();
			$implode[] = "i.information_id";
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'url_keyword') {
						$implode[] = "uk.keyword as url_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
							
						if (VERSION >= '3.0.0.0') {
							$add = " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('information_id=', i.information_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = '" . (int)$data['language_id'] . "') LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = CONCAT('information_id=', i.information_id) AND uk2.store_id = '" . (int)$url_keyword_store_id . "')";
						} else {
							$add = " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('information_id=', i.information_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = '" . (int)$data['language_id'] . "') LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = CONCAT('information_id=', i.information_id) AND uk2.store_id = '" . (int)$url_keyword_store_id . "')";
						}
					}
				}
			}
			
			$sql = "SELECT " . implode(', ', $implode) . " FROM " . DB_PREFIX . "information i" . $add;
			
			$implode = array();
			
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'url_keyword') {
						$implode[] = "uk2.keyword LIKE '%" . $this->db->escape($filter) . "%'";
					}
				}
			}
			
			if ($implode) {
				$sql .= " WHERE " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY i.information_id";
			
			$query = $this->db->query($sql);
			
			$informations = array();
			
			foreach ($query->rows as $result) {
				$informations[$result['information_id']] = $result;
			}

			return $informations;	
		}
	}
	
	/*
	*	Edit Element Field for Manager.
	*/
	public function editElementField($element) {				
		$this->load->model('extension/module/' . $this->codename);
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
				
		if ($element['sheet_code'] == 'category') {
			if (($element['field_code'] == 'url_keyword') && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store']) && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status'])) {
				if ($element['store_id'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status']) {
					$url_keyword_store_id = $element['store_id'];	
				} else {
					$url_keyword_store_id = 0;
				}
				
				if (VERSION >= '3.0.0.0') {
					$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$element['element_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$element['language_id'] . "'");
					
					if (trim($element['value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'category_id=" . (int)$element['element_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$element['language_id'] . "', keyword = '" . $this->db->escape($element['value']) . "'");
					}
				} else {
					$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'category_id=" . (int)$element['element_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$element['language_id'] . "'");
					
					if (trim($element['value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'category_id=" . (int)$element['element_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$element['language_id'] . "', keyword = '" . $this->db->escape($element['value']) . "'");
					}
					
					if (($url_keyword_store_id == 0) && ($element['language_id'] == (int)$this->config->get('config_language_id'))) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'category_id=" . (int)$element['element_id'] . "'");
						
						if (trim($element['value'])) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$element['element_id'] . "', keyword = '" . $this->db->escape($element['value']) . "'");
						}
					}
				}

				$cache_data = array(
					'route' => 'category_id=' . $element['element_id'],
					'store_id' => $element['store_id'],
					'language_id' => $element['language_id']
				);

				$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
			}
		}
		
		if ($element['sheet_code'] == 'product') {
			if ($element['field_code'] == 'category_id') {
				$this->db->query("DELETE FROM " . DB_PREFIX . "d_product_category WHERE product_id='" . (int)$element['element_id'] . "'");
				
				$this->db->query("INSERT INTO " . DB_PREFIX . "d_product_category SET product_id = '" . (int)$element['element_id'] . "', " . $this->db->escape($element['field_code']) . " = '" . (int)$element['value'] . "'");
			}
			
			if (($element['field_code'] == 'url_keyword') && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store']) && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status'])) {
				if ($element['store_id'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status']) {
					$url_keyword_store_id = $element['store_id'];	
				} else {
					$url_keyword_store_id = 0;
				}
				
				if (VERSION >= '3.0.0.0') {
					$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$element['element_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$element['language_id'] . "'");
					
					if (trim($element['value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'product_id=" . (int)$element['element_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$element['language_id'] . "', keyword = '" . $this->db->escape($element['value']) . "'");
					}
				} else {
					$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'product_id=" . (int)$element['element_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$element['language_id'] . "'");
					
					if (trim($element['value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'product_id=" . (int)$element['element_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$element['language_id'] . "', keyword = '" . $this->db->escape($element['value']) . "'");
					}
					
					if (($url_keyword_store_id == 0) && ($element['language_id'] == (int)$this->config->get('config_language_id'))) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$element['element_id'] . "'");
						
						if (trim($element['value'])) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int)$element['element_id'] . "', keyword = '" . $this->db->escape($element['value']) . "'");
						}
					}
				}
				
				$cache_data = array(
					'route' => 'product_id=' . $element['element_id'],
					'store_id' => $element['store_id'],
					'language_id' => $element['language_id']
				);
				
				$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
			}
		}
		
		if ($element['sheet_code'] == 'manufacturer') {
			if (($element['field_code'] == 'url_keyword') && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store']) && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status'])) {
				if ($element['store_id'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status']) {
					$url_keyword_store_id = $element['store_id'];	
				} else {
					$url_keyword_store_id = 0;
				}
				
				if (VERSION >= '3.0.0.0') {
					$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'manufacturer_id=" . (int)$element['element_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$element['language_id'] . "'");
					
					if (trim($element['value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'manufacturer_id=" . (int)$element['element_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$element['language_id'] . "', keyword = '" . $this->db->escape($element['value']) . "'");
					}
				} else {
					$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'manufacturer_id=" . (int)$element['element_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$element['language_id'] . "'");
					
					if (trim($element['value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'manufacturer_id=" . (int)$element['element_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$element['language_id'] . "', keyword = '" . $this->db->escape($element['value']) . "'");
					}
					
					if (($url_keyword_store_id == 0) && ($element['language_id'] == (int)$this->config->get('config_language_id'))) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'manufacturer_id=" . (int)$element['element_id'] . "'");
						
						if (trim($element['value'])) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'manufacturer_id=" . (int)$element['element_id'] . "', keyword = '" . $this->db->escape($element['value']) . "'");
						}
					}
				}
				
				$cache_data = array(
					'route' => 'manufacturer_id=' . $element['element_id'],
					'store_id' => $element['store_id'],
					'language_id' => $element['language_id']
				);
				
				$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
			}
		}
		
		if ($element['sheet_code'] == 'information') {
			if (($element['field_code'] == 'url_keyword') && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store']) && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status'])) {
				if ($element['store_id'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status']) {
					$url_keyword_store_id = $element['store_id'];	
				} else {
					$url_keyword_store_id = 0;
				}
				
				if (VERSION >= '3.0.0.0') {
					$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'information_id=" . (int)$element['element_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$element['language_id'] . "'");
					
					if (trim($element['value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'information_id=" . (int)$element['element_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$element['language_id'] . "', keyword = '" . $this->db->escape($element['value']) . "'");
					}
				} else {
					$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'information_id=" . (int)$element['element_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$element['language_id'] . "'");
					
					if (trim($element['value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'information_id=" . (int)$element['element_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$element['language_id'] . "', keyword = '" . $this->db->escape($element['value']) . "'");
					}
					
					if (($url_keyword_store_id == 0) && ($element['language_id'] == (int)$this->config->get('config_language_id'))) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'information_id=" . (int)$element['element_id'] . "'");
						
						if (trim($element['value'])) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'information_id=" . (int)$element['element_id'] . "', keyword = '" . $this->db->escape($element['value']) . "'");
						}
					}
				}

				$cache_data = array(
					'route' => 'information_id=' . $element['element_id'],
					'store_id' => $element['store_id'],
					'language_id' => $element['language_id']
				);
				
				$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
			}
		}
	}
	
	/*
	*	Return Export Elements for Manager.
	*/
	public function getExportElements($data) {		
		$this->load->model('extension/module/' . $this->codename);
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
				
		if ($data['sheet_code'] == 'category') {
			$categories = array();
			$implode = array();
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'url_keyword') {
						$implode[] = "uk.keyword as url_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
						
						if (VERSION >= '3.0.0.0') {
							$add .= " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('category_id=', c.category_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = cd.language_id)";
						} else {
							$add .= " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('category_id=', c.category_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = cd.language_id)";
						}				
					}
				}
			}
									
			if ($implode) {				
				$query = $this->db->query("SELECT c.category_id, cd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (cd.category_id = c.category_id)" . $add . " GROUP BY c.category_id, cd.language_id");
								
				foreach ($query->rows as $result) {
					$categories[$result['category_id']]['category_id'] = $result['category_id'];
				
					foreach ($result as $field => $value) {
						if (($field != 'category_id') && ($field != 'language_id')) {
							$categories[$result['category_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}
						
			return $categories;	
		}
		
		if ($data['sheet_code'] == 'product') {		
			$products = array();
			$implode = array();
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'category_id') {
						$implode[] = "pc.category_id";
					}
				
					if ($field['code'] == 'url_keyword') {
						$implode[] = "uk.keyword as url_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
						
						if (VERSION >= '3.0.0.0') {
							$add .= " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('product_id=', p.product_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = pd.language_id)";
						} else {
							$add .= " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('product_id=', p.product_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = pd.language_id)";
						}
					}
				}
			}
			
			if ($implode) {				
				$query = $this->db->query("SELECT p.product_id, pd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "d_product_category pc ON (pc.product_id = p.product_id)" . $add . " GROUP BY p.product_id, pd.language_id");
													
				foreach ($query->rows as $result) {
					$products[$result['product_id']]['product_id'] = $result['product_id'];
					$products[$result['product_id']]['category_id'] = $result['category_id'];
					
					foreach ($result as $field => $value) {
						if (($field != 'product_id') && ($field != 'language_id') && ($field != 'category_id')) {
							$products[$result['product_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}
									
			return $products;	
		}
		
		if ($data['sheet_code'] == 'manufacturer') {
			$manufacturers = array();
			$implode = array();
			$add = '';
						
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'url_keyword') {
						$implode[] = "uk.keyword as url_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
						
						if (VERSION >= '3.0.0.0') {
							$add .= " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = l.language_id)";
						} else {
							$add .= " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = l.language_id)";
						}
					}
				}
			}
			
			if ($implode) {				
				$query = $this->db->query("SELECT m.manufacturer_id, l.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "manufacturer m CROSS JOIN " . DB_PREFIX . "language l" . $add . " GROUP BY m.manufacturer_id, l.language_id");
													
				foreach ($query->rows as $result) {
					$manufacturers[$result['manufacturer_id']]['manufacturer_id'] = $result['manufacturer_id'];
					
					foreach ($result as $field => $value) {
						if (($field != 'manufacturer_id') && ($field != 'language_id')) {
							$manufacturers[$result['manufacturer_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}

			return $manufacturers;	
		}
		
		if ($data['sheet_code'] == 'information') {
			$informations = array();
			$implode = array();
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'url_keyword') {
						$implode[] = "uk.keyword as url_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
						
						if (VERSION >= '3.0.0.0') {
							$add .= " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('information_id=', i.information_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = id.language_id)";
						} else {
							$add .= " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('information_id=', i.information_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = id.language_id)";
						}
					}
				}
			}
			
			if ($implode) {				
				$query = $this->db->query("SELECT i.information_id, id.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (id.information_id = i.information_id)" . $add . " GROUP BY i.information_id, id.language_id");
								
				foreach ($query->rows as $result) {
					$informations[$result['information_id']]['information_id'] = $result['information_id'];
					
					foreach ($result as $field => $value) {
						if (($field != 'information_id') && ($field != 'language_id')) {
							$informations[$result['information_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}

			return $informations;	
		}
	}
	
	/*
	*	Save Import Elements for Manager.
	*/
	public function saveImportElements($data) {		
		$this->load->model('extension/module/' . $this->codename);
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
						
		if ($data['sheet_code'] == 'category') {
			$categories = array();
			$implode = array();
			$add = '';
						
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'url_keyword') {
						$implode[] = "uk.keyword as url_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
						
						if (VERSION >= '3.0.0.0') {
							$add .= " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('category_id=', c.category_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = cd.language_id)";
						} else {
							$add .= " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('category_id=', c.category_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = cd.language_id)";
						}				
					}
				}
			}
									
			if ($implode) {				
				$query = $this->db->query("SELECT c.category_id, cd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (cd.category_id = c.category_id)" . $add . " GROUP BY c.category_id, cd.language_id");
									
				foreach ($query->rows as $result) {
					$categories[$result['category_id']]['category_id'] = $result['category_id'];
				
					foreach ($result as $field => $value) {
						if (($field != 'category_id') && ($field != 'language_id')) {
							$categories[$result['category_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}
													
			foreach ($data['elements'] as $element) {
				if (isset($categories[$element['category_id']])) {
					$category = $categories[$element['category_id']];
					
					foreach ($languages as $language) {
						if (isset($element['url_keyword'][$language['language_id']])) {
							if ((isset($category['url_keyword'][$language['language_id']]) && ($element['url_keyword'][$language['language_id']] != $category['url_keyword'][$language['language_id']])) || !isset($category['url_keyword'][$language['language_id']])) {
								if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
									$url_keyword_store_id = $data['store_id'];
								} else {
									$url_keyword_store_id = 0;
								}
								
								if (VERSION >= '3.0.0.0') {
									$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$category['category_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
										
									if (trim($element['url_keyword'][$language['language_id']])) {
										$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'category_id=" . (int)$category['category_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($element['url_keyword'][$language['language_id']]) . "'");
									}
								} else {
									$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'category_id=" . (int)$category['category_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
									
									if (trim($element['url_keyword'][$language['language_id']])) {
										$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'category_id=" . (int)$category['category_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($element['url_keyword'][$language['language_id']]) . "'");
									}	
								
									if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
										$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'category_id=" . (int)$category['category_id'] . "'");
											
										if (trim($element['url_keyword'][$language['language_id']])) {	
											$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$category['category_id'] . "', keyword = '" . $this->db->escape($element['url_keyword'][$language['language_id']]) . "'");
										}
									}
								}
							}
						}
					}
				}	
			}
			
			$cache_data = array(
				'route' => 'category_id=%',
				'store_id' => $data['store_id']
			);
			
			$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
		}
		
		if ($data['sheet_code'] == 'product') {
			$products = array();
			$implode = array();
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'category_id') {
						$implode[] = "pc.category_id";
					}
				
					if ($field['code'] == 'url_keyword') {
						$implode[] = "uk.keyword as url_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
						
						if (VERSION >= '3.0.0.0') {
							$add .= " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('product_id=', p.product_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = pd.language_id)";
						} else {
							$add .= " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('product_id=', p.product_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = pd.language_id)";
						}
					}
				}
			}
			
			if ($implode) {				
				$query = $this->db->query("SELECT p.product_id, pd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "d_product_category pc ON (pc.product_id = p.product_id)" . $add . " GROUP BY p.product_id, pd.language_id");
									
				foreach ($query->rows as $result) {
					$products[$result['product_id']]['product_id'] = $result['product_id'];
					$products[$result['product_id']]['category_id'] = $result['category_id'];
					
					foreach ($result as $field => $value) {
						if (($field != 'product_id') && ($field != 'language_id') && ($field != 'category_id')) {
							$products[$result['product_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}
			
			foreach ($data['elements'] as $element) {
				if (isset($products[$element['product_id']])) {
					$product = $products[$element['product_id']];
										
					if (isset($element['category_id']) && isset($product['category_id'])) {
						if ($element['category_id'] != $product['category_id']) {							
							$this->db->query("DELETE FROM " . DB_PREFIX . "d_product_category WHERE product_id='" . (int)$product['product_id'] . "'");
			
							$this->db->query("INSERT INTO " . DB_PREFIX . "d_product_category SET product_id = '" . (int)$product['product_id'] . "', category_id = '" . (int)$element['category_id'] . "'");
						}
					}
										
					foreach ($languages as $language) {
						if (isset($element['url_keyword'][$language['language_id']])) {
							if ((isset($product['url_keyword'][$language['language_id']]) && ($element['url_keyword'][$language['language_id']] != $product['url_keyword'][$language['language_id']])) || !isset($product['url_keyword'][$language['language_id']])) {
								if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
									$url_keyword_store_id = $data['store_id'];
								} else {
									$url_keyword_store_id = 0;
								}
								
								if (VERSION >= '3.0.0.0') {
									$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product['product_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
										
									if (trim($element['url_keyword'][$language['language_id']])) {
										$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'product_id=" . (int)$product['product_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($element['url_keyword'][$language['language_id']]) . "'");
									}
								} else {
									$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'product_id=" . (int)$product['product_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
									
									if (trim($element['url_keyword'][$language['language_id']])) {
										$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'product_id=" . (int)$product['product_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($element['url_keyword'][$language['language_id']]) . "'");
									}	
								
									if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
										$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$product['product_id'] . "'");
											
										if (trim($element['url_keyword'][$language['language_id']])) {	
											$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int)$product['product_id'] . "', keyword = '" . $this->db->escape($element['url_keyword'][$language['language_id']]) . "'");
										}
									}
								}
							}
						}
					}
				}	
			}
			
			$cache_data = array(
				'route' => 'product_id=%',
				'store_id' => $data['store_id']
			);
			
			$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
		}
		
		if ($data['sheet_code'] == 'manufacturer') {
			$manufacturers = array();
			$implode = array();
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'url_keyword') {
						$implode[] = "uk.keyword as url_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
						
						if (VERSION >= '3.0.0.0') {
							$add .= " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = l.language_id)";
						} else {
							$add .= " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = l.language_id)";
						}
					}
				}
			}
			
			if ($implode) {				
				$query = $this->db->query("SELECT m.manufacturer_id, l.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "manufacturer m CROSS JOIN " . DB_PREFIX . "language l" . $add . " GROUP BY m.manufacturer_id, l.language_id");
									
				foreach ($query->rows as $result) {
					$manufacturers[$result['manufacturer_id']]['manufacturer_id'] = $result['manufacturer_id'];
					
					foreach ($result as $field => $value) {
						if (($field != 'manufacturer_id') && ($field != 'language_id')) {
							$manufacturers[$result['manufacturer_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}	
				
			foreach ($data['elements'] as $element) {
				if (isset($manufacturers[$element['manufacturer_id']])) {
					$manufacturer = $manufacturers[$element['manufacturer_id']];
					
					foreach ($languages as $language) {
						if (isset($element['url_keyword'][$language['language_id']])) {
							if ((isset($manufacturer['url_keyword'][$language['language_id']]) && ($element['url_keyword'][$language['language_id']] != $manufacturer['url_keyword'][$language['language_id']])) || !isset($manufacturer['url_keyword'][$language['language_id']])) {
								if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
									$url_keyword_store_id = $data['store_id'];
								} else {
									$url_keyword_store_id = 0;
								}
								
								if (VERSION >= '3.0.0.0') {
									$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
										
									if (trim($element['url_keyword'][$language['language_id']])) {
										$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($element['url_keyword'][$language['language_id']]) . "'");
									}
								} else {
									$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
									
									if (trim($element['url_keyword'][$language['language_id']])) {
										$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($element['url_keyword'][$language['language_id']]) . "'");
									}	
								
									if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
										$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "'");
											
										if (trim($element['url_keyword'][$language['language_id']])) {	
											$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "', keyword = '" . $this->db->escape($element['url_keyword'][$language['language_id']]) . "'");
										}
									}
								}
							}
						}
					}
				}	
			}
			
			$cache_data = array(
				'route' => 'manufacturer_id=%',
				'store_id' => $data['store_id']
			);
			
			$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
		}
		
		if ($data['sheet_code'] == 'information') {
			$informations = array();
			$implode = array();
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'url_keyword') {
						$implode[] = "uk.keyword as url_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
						
						if (VERSION >= '3.0.0.0') {
							$add .= " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('information_id=', i.information_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = id.language_id)";
						} else {
							$add .= " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('information_id=', i.information_id) AND uk.store_id = '" . (int)$url_keyword_store_id . "' AND uk.language_id = id.language_id)";
						}
					}
				}
			}
			
			if ($implode) {				
				$query = $this->db->query("SELECT i.information_id, id.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (id.information_id = i.information_id)" . $add . " GROUP BY i.information_id, id.language_id");
									
				foreach ($query->rows as $result) {
					$informations[$result['information_id']]['information_id'] = $result['information_id'];
					
					foreach ($result as $field => $value) {
						if (($field != 'information_id') && ($field != 'language_id')) {
							$informations[$result['information_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}	
			
			foreach ($data['elements'] as $element) {
				if (isset($informations[$element['information_id']])) {
					$information = $informations[$element['information_id']];
					
					foreach ($languages as $language) {
						if (isset($element['url_keyword'][$language['language_id']])) {
							if ((isset($information['url_keyword'][$language['language_id']]) && ($element['url_keyword'][$language['language_id']] != $information['url_keyword'][$language['language_id']])) || !isset($information['url_keyword'][$language['language_id']])) {
								if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
									$url_keyword_store_id = $data['store_id'];
								} else {
									$url_keyword_store_id = 0;
								}
								
								if (VERSION >= '3.0.0.0') {
									$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'information_id=" . (int)$information['information_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
										
									if (trim($element['url_keyword'][$language['language_id']])) {
										$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'information_id=" . (int)$information['information_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($element['url_keyword'][$language['language_id']]) . "'");
									}
								} else {
									$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'information_id=" . (int)$information['information_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
									
									if (trim($element['url_keyword'][$language['language_id']])) {
										$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'information_id=" . (int)$information['information_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($element['url_keyword'][$language['language_id']]) . "'");
									}	
								
									if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
										$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'information_id=" . (int)$information['information_id'] . "'");
											
										if (trim($element['url_keyword'][$language['language_id']])) {	
											$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'information_id=" . (int)$information['information_id'] . "', keyword = '" . $this->db->escape($element['url_keyword'][$language['language_id']]) . "'");
										}
									}
								}
							}
						}
					}
				}	
			}

			$cache_data = array(
				'route' => 'information_id=%',
				'store_id' => $data['store_id']
			);
					
			$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
		}
	}
}
?>