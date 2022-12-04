<?php
class ModelExtensionDSEOModuleManagerDSEOModule extends Model {
	private $codename = 'd_seo_module';	
	
	/*
	*	Return List Elements for Manager.
	*/
	public function getListElements($data) {		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
				
		if ($data['sheet_code'] == 'category') {
			$categories = array();
			$implode = array();
			$implode[] = "c.category_id";
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'target_keyword') {
						$implode[] = "CONCAT('[', GROUP_CONCAT(DISTINCT tk.keyword ORDER BY tk.sort_order SEPARATOR ']['), ']') as target_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$target_keyword_store_id = $data['store_id'];
						} else {
							$target_keyword_store_id = 0;
						}
						
						$add .= " LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('category_id=', c.category_id) AND tk.store_id = '" . (int)$target_keyword_store_id . "' AND tk.language_id = '" . (int)$data['language_id'] . "') LEFT JOIN " . DB_PREFIX . "d_target_keyword tk2 ON (tk2.route = CONCAT('category_id=', c.category_id) AND tk2.store_id = '" . (int)$target_keyword_store_id . "')";				
					}
				}
			}		
			
			$sql = "SELECT " . implode(', ', $implode) . " FROM " . DB_PREFIX . "category c" . $add;
						
			$implode = array();
			
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'target_keyword') {
						$implode[] = "tk2.keyword LIKE '%" . $this->db->escape($filter) . "%'";
					}
				}
			}
			
			if ($implode) {
				$sql .= " WHERE " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY c.category_id";
			
			$query = $this->db->query($sql);
						
			foreach ($query->rows as $result) {
				$categories[$result['category_id']] = $result;
			}

			return $categories;	
		}
		
		if ($data['sheet_code'] == 'product') {
			$products = array();
			$implode = array();
			$implode[] = "p.product_id";
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'target_keyword') {
						$implode[] = "CONCAT('[', GROUP_CONCAT(DISTINCT tk.keyword ORDER BY tk.sort_order SEPARATOR ']['), ']') as target_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$target_keyword_store_id = $data['store_id'];
						} else {
							$target_keyword_store_id = 0;
						}
						
						$add .= " LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('product_id=', p.product_id) AND tk.store_id = '" . (int)$target_keyword_store_id . "' AND tk.language_id = '" . (int)$data['language_id'] . "') LEFT JOIN " . DB_PREFIX . "d_target_keyword tk2 ON (tk2.route = CONCAT('product_id=', p.product_id) AND tk2.store_id = '" . (int)$target_keyword_store_id . "')";						
					}
				}
			}		
			
			$sql = "SELECT " . implode(', ', $implode) . " FROM " . DB_PREFIX . "product p" . $add;
						
			$implode = array();
			
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'target_keyword') {
						$implode[] = "tk2.keyword LIKE '%" . $this->db->escape($filter) . "%'";
					}
				}
			}
			
			if ($implode) {
				$sql .= " WHERE " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY p.product_id";
			
			$query = $this->db->query($sql);
						
			foreach ($query->rows as $result) {
				$products[$result['product_id']] = $result;
			}

			return $products;	
		}
		
		if ($data['sheet_code'] == 'manufacturer') {
			$manufacturers = array();
			$implode = array();
			$implode[] = "m.manufacturer_id";
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'target_keyword') {
						$implode[] = "CONCAT('[', GROUP_CONCAT(DISTINCT tk.keyword ORDER BY tk.sort_order SEPARATOR ']['), ']') as target_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$target_keyword_store_id = $data['store_id'];
						} else {
							$target_keyword_store_id = 0;
						}
						
						$add = "LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND tk.store_id = '" . (int)$target_keyword_store_id . "' AND tk.language_id = '" . (int)$data['language_id'] . "') LEFT JOIN " . DB_PREFIX . "d_target_keyword tk2 ON (tk2.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND tk2.store_id = '" . (int)$target_keyword_store_id . "')";						
					}
				}
			}		
			
			$sql = "SELECT " . implode(', ', $implode) . " FROM " . DB_PREFIX . "manufacturer m " . $add;
						
			$implode = array();
			
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'target_keyword') {
						$implode[] = "tk2.keyword LIKE '%" . $this->db->escape($filter) . "%'";
					}
				}
			}
			
			if ($implode) {
				$sql .= " WHERE " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY m.manufacturer_id";
			
			$query = $this->db->query($sql);
						
			foreach ($query->rows as $result) {
				$manufacturers[$result['manufacturer_id']] = $result;
			}

			return $manufacturers;	
		}
		
		if ($data['sheet_code'] == 'information') {
			$informations = array();
			$implode = array();
			$implode[] = "i.information_id";
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'target_keyword') {
						$implode[] = "CONCAT('[', GROUP_CONCAT(DISTINCT tk.keyword ORDER BY tk.sort_order SEPARATOR ']['), ']') as target_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$target_keyword_store_id = $data['store_id'];
						} else {
							$target_keyword_store_id = 0;
						}
						
						$add .= " LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('information_id=', i.information_id) AND tk.store_id = '" . (int)$target_keyword_store_id . "' AND tk.language_id = '" . (int)$data['language_id'] . "') LEFT JOIN " . DB_PREFIX . "d_target_keyword tk2 ON (tk2.route = CONCAT('information_id=', i.information_id) AND tk2.store_id = '" . (int)$target_keyword_store_id . "')";					
					}
				}
			}		
			
			$sql = "SELECT " . implode(', ', $implode) . " FROM " . DB_PREFIX . "information i" . $add;
						
			$implode = array();
			
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'target_keyword') {
						$implode[] = "tk2.keyword LIKE '%" . $this->db->escape($filter) . "%'";
					}
				}
			}
			
			if ($implode) {
				$sql .= " WHERE " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY i.information_id";
			
			$query = $this->db->query($sql);
						
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
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
				
		if ($element['sheet_code'] == 'category') {
			if (($element['field_code'] == 'target_keyword') && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store']) && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status'])) {
				if ($element['store_id'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status']) {
					$target_keyword_store_id = $element['store_id'];	
				} else {
					$target_keyword_store_id = 0;
				}
					
				$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'category_id=" . (int)$element['element_id'] . "' AND store_id = '" . (int)$target_keyword_store_id . "' AND language_id = '" . (int)$element['language_id'] . "'");
				
				if ($element['value']) {
					preg_match_all('/\[[^]]+\]/', $element['value'], $keywords);
				
					$sort_order = 1;
					$this->request->post['value'] = '';
				
					foreach ($keywords[0] as $keyword) {
						$keyword = substr($keyword, 1, strlen($keyword) - 2);
						
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'category_id=" . (int)$element['element_id'] . "', store_id='" . (int)$target_keyword_store_id . "', language_id = '" . (int)$element['language_id'] . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
					
						$sort_order++;
						$this->request->post['value'] .= '[' . $keyword . ']';
					}
				}
			}
		}
		
		if ($element['sheet_code'] == 'product') {
			if (($element['field_code'] == 'target_keyword') && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store']) && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status'])) {
				if ($element['store_id'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status']) {
					$target_keyword_store_id = $element['store_id'];	
				} else {
					$target_keyword_store_id = 0;
				}
					
				$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'product_id=" . (int)$element['element_id'] . "' AND store_id = '" . (int)$target_keyword_store_id . "' AND language_id = '" . (int)$element['language_id'] . "'");
				
				if ($element['value']) {				
					preg_match_all('/\[[^]]+\]/', $element['value'], $keywords);
				
					$sort_order = 1;
					$this->request->post['value'] = '';
				
					foreach ($keywords[0] as $keyword) {
						$keyword = substr($keyword, 1, strlen($keyword) - 2);
						
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'product_id=" . (int)$element['element_id'] . "', 	store_id='" . (int)$target_keyword_store_id . "', language_id = '" . (int)$element['language_id'] . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
					
						$sort_order++;
						$this->request->post['value'] .= '[' . $keyword . ']';
					}
				}
			}
		}
		
		if ($element['sheet_code'] == 'manufacturer') {
			if (($element['field_code'] == 'target_keyword') && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store']) && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status'])) {
				if ($element['store_id'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status']) {
					$target_keyword_store_id = $element['store_id'];	
				} else {
					$target_keyword_store_id = 0;
				}
					
				$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'manufacturer_id=" . (int)$element['element_id'] . "' AND store_id = '" . (int)$target_keyword_store_id . "' AND language_id = '" . (int)$element['language_id'] . "'");
					
				if ($element['value']) {
					preg_match_all('/\[[^]]+\]/', $element['value'], $keywords);
				
					$sort_order = 1;
					$this->request->post['value'] = '';
				
					foreach ($keywords[0] as $keyword) {
						$keyword = substr($keyword, 1, strlen($keyword) - 2);
						
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'manufacturer_id=" . (int)$element['element_id'] . "', store_id='" . (int)$target_keyword_store_id . "', language_id = '" . (int)$element['language_id'] . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
					
						$sort_order++;
						$this->request->post['value'] .= '[' . $keyword . ']';
					}
				}
			}
		}
		
		if ($element['sheet_code'] == 'information') {
			if (($element['field_code'] == 'target_keyword') && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store']) && isset($field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status'])) {
				if ($element['store_id'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store'] && $field_info['sheet'][$element['sheet_code']]['field'][$element['field_code']]['multi_store_status']) {
					$target_keyword_store_id = $element['store_id'];	
				} else {
					$target_keyword_store_id = 0;
				}
					
				$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'information_id=" . (int)$element['element_id'] . "' AND store_id = '" . (int)$target_keyword_store_id . "' AND language_id = '" . (int)$element['language_id'] . "'");
				
				if ($element['value']) {				
					preg_match_all('/\[[^]]+\]/', $element['value'], $keywords);
				
					$sort_order = 1;
					$this->request->post['value'] = '';
				
					foreach ($keywords[0] as $keyword) {
						$keyword = substr($keyword, 1, strlen($keyword) - 2);
						
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'information_id=" . (int)$element['element_id'] . "', store_id='" . (int)$target_keyword_store_id . "', language_id = '" . (int)$element['language_id'] . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
					
						$sort_order++;
						$this->request->post['value'] .= '[' . $keyword . ']';
					}
				}
			}
		}
	}
	
	/*
	*	Return Export Elements for Manager.
	*/
	public function getExportElements($data) {		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
						
		if ($data['sheet_code'] == 'category') {
			$categories = array();
			$implode = array();
			$add = '';
						
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'target_keyword') {
						$implode[] = "CONCAT('[', GROUP_CONCAT(DISTINCT tk.keyword ORDER BY tk.sort_order SEPARATOR ']['), ']') as target_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$target_keyword_store_id = $data['store_id'];
						} else {
							$target_keyword_store_id = 0;
						}
						
						$add .= " LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('category_id=', c.category_id) AND tk.store_id = '" . (int)$target_keyword_store_id . "' AND tk.language_id = cd.language_id)";					
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
					if ($field['code'] == 'target_keyword') {
						$implode[] = "CONCAT('[', GROUP_CONCAT(DISTINCT tk.keyword ORDER BY tk.sort_order SEPARATOR ']['), ']') as target_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$target_keyword_store_id = $data['store_id'];
						} else {
							$target_keyword_store_id = 0;
						}
						
						$add .= " LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('product_id=', p.product_id) AND tk.store_id = '" . (int)$target_keyword_store_id . "' AND tk.language_id = pd.language_id)";
					}
				}
			}
			
			if ($implode) {
				$query = $this->db->query("SELECT p.product_id, pd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = p.product_id)" . $add . " GROUP BY p.product_id, pd.language_id");
				
				foreach ($query->rows as $result) {
					$products[$result['product_id']]['product_id'] = $result['product_id'];
				
					foreach ($result as $field => $value) {
						if (($field != 'product_id') && ($field != 'language_id')) {
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
					if ($field['code'] == 'target_keyword') {
						$implode[] = "CONCAT('[', GROUP_CONCAT(DISTINCT tk.keyword ORDER BY tk.sort_order SEPARATOR ']['), ']') as target_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$target_keyword_store_id = $data['store_id'];
						} else {
							$target_keyword_store_id = 0;
						}
						
						$add .= " LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND tk.store_id = '" . (int)$target_keyword_store_id . "' AND tk.language_id = l.language_id)";
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
					if ($field['code'] == 'target_keyword') {
						$implode[] = "CONCAT('[', GROUP_CONCAT(DISTINCT tk.keyword ORDER BY tk.sort_order SEPARATOR ']['), ']') as target_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$target_keyword_store_id = $data['store_id'];
						} else {
							$target_keyword_store_id = 0;
						}
						
						$add .= " LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('information_id=', i.information_id) AND tk.store_id = '" . (int)$target_keyword_store_id . "' AND tk.language_id = id.language_id)";
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
					if ($field['code'] == 'target_keyword') {
						$implode[] = "CONCAT('[', GROUP_CONCAT(DISTINCT tk.keyword ORDER BY tk.sort_order SEPARATOR ']['), ']') as target_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$target_keyword_store_id = $data['store_id'];
						} else {
							$target_keyword_store_id = 0;
						}
						
						$add .= " LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('category_id=', c.category_id) AND tk.store_id = '" . (int)$target_keyword_store_id . "' AND tk.language_id = cd.language_id)";				
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
						if (isset($element['target_keyword'][$language['language_id']])) {
							if ((isset($category['target_keyword'][$language['language_id']]) && ($element['target_keyword'][$language['language_id']] != $category['target_keyword'][$language['language_id']])) || !isset($category['target_keyword'][$language['language_id']])) {
								if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
									$target_keyword_store_id = $data['store_id'];
								} else {
									$target_keyword_store_id = 0;
								}
								
								$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'category_id=" . (int)$category['category_id'] . "' AND store_id = '" . (int)$target_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");	
								
								if ($element['target_keyword'][$language['language_id']]) {
									preg_match_all('/\[[^]]+\]/', $element['target_keyword'][$language['language_id']], $keywords);
									
									$sort_order = 1;
									
									foreach ($keywords[0] as $keyword) {
										$keyword = substr($keyword, 1, strlen($keyword) - 2);
										
										$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'category_id=" . (int)$category['category_id'] . "', store_id = '" . (int)$target_keyword_store_id . "', language_id = '" . (int)$language['language_id'] . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
										
										$sort_order++;
									}
								}
							}
						}
					}
				}	
			}
		}
		
		if ($data['sheet_code'] == 'product') {
			$products = array();
			$implode = array();
			$add = '';
						
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'target_keyword') {
						$implode[] = "CONCAT('[', GROUP_CONCAT(DISTINCT tk.keyword ORDER BY tk.sort_order SEPARATOR ']['), ']') as target_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$target_keyword_store_id = $data['store_id'];
						} else {
							$target_keyword_store_id = 0;
						}
						
						$add .= " LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('product_id=', p.product_id) AND tk.store_id = '" . (int)$target_keyword_store_id . "' AND tk.language_id = pd.language_id)";
					}
				}
			}
			
			if ($implode) {
				$query = $this->db->query("SELECT p.product_id, pd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = p.product_id)" . $add . " GROUP BY p.product_id, pd.language_id");
				
				foreach ($query->rows as $result) {
					$products[$result['product_id']]['product_id'] = $result['product_id'];
				
					foreach ($result as $field => $value) {
						if (($field != 'product_id') && ($field != 'language_id')) {
							$products[$result['product_id']][$field][$result['language_id']] = $value;
						}
					}
				}	
			}
			
			foreach ($data['elements'] as $element) {
				if (isset($products[$element['product_id']])) {
					$product = $products[$element['product_id']];
					
					foreach ($languages as $language) {
						if (isset($element['target_keyword'][$language['language_id']])) {
							if ((isset($product['target_keyword'][$language['language_id']]) && ($element['target_keyword'][$language['language_id']] != $product['target_keyword'][$language['language_id']])) || !isset($product['target_keyword'][$language['language_id']])) {
								if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
									$target_keyword_store_id = $data['store_id'];
								} else {
									$target_keyword_store_id = 0;
								}
								
								$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'product_id=" . (int)$product['product_id'] . "' AND store_id = '" . (int)$target_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");	
								
								if ($element['target_keyword'][$language['language_id']]) {
									preg_match_all('/\[[^]]+\]/', $element['target_keyword'][$language['language_id']], $keywords);
									
									$sort_order = 1;
									
									foreach ($keywords[0] as $keyword) {
										$keyword = substr($keyword, 1, strlen($keyword) - 2);
										
										$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'product_id=" . (int)$product['product_id'] . "', store_id = '" . (int)$target_keyword_store_id . "', language_id = '" . (int)$language['language_id'] . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
										
										$sort_order++;
									}
								}
							}
						}
					}
				}	
			}
		}
		
		if ($data['sheet_code'] == 'manufacturer') {
			$manufacturers = array();
			$implode = array();
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'target_keyword') {
						$implode[] = "CONCAT('[', GROUP_CONCAT(DISTINCT tk.keyword ORDER BY tk.sort_order SEPARATOR ']['), ']') as target_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$target_keyword_store_id = $data['store_id'];
						} else {
							$target_keyword_store_id = 0;
						}
						
						$add .= " LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND tk.store_id = '" . (int)$target_keyword_store_id . "' AND tk.language_id = l.language_id)";
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
						if (isset($element['target_keyword'][$language['language_id']])) {
							if ((isset($manufacturer['target_keyword'][$language['language_id']]) && ($element['target_keyword'][$language['language_id']] != $manufacturer['target_keyword'][$language['language_id']])) || !isset($manufacturer['target_keyword'][$language['language_id']])) {
								if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
									$target_keyword_store_id = $data['store_id'];
								} else {
									$target_keyword_store_id = 0;
								}
								
								$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "' AND store_id = '" . (int)$target_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");	
								
								if ($element['target_keyword'][$language['language_id']]) {
									preg_match_all('/\[[^]]+\]/', $element['target_keyword'][$language['language_id']], $keywords);
									
									$sort_order = 1;
									
									foreach ($keywords[0] as $keyword) {
										$keyword = substr($keyword, 1, strlen($keyword) - 2);
										
										$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "', store_id = '" . (int)$target_keyword_store_id . "', language_id = '" . (int)$language['language_id'] . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
										
										$sort_order++;
									}
								}
							}
						}
					}
				}	
			}
		}
		
		if ($data['sheet_code'] == 'information') {
			$informations = array();
			$implode = array();
			$add = '';
			
			foreach ($data['fields'] as $field) {
				if (isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store']) && isset($field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status'])) {
					if ($field['code'] == 'target_keyword') {
						$implode[] = "CONCAT('[', GROUP_CONCAT(DISTINCT tk.keyword ORDER BY tk.sort_order SEPARATOR ']['), ']') as target_keyword";
						
						if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field'][$field['code']]['multi_store_status']) {
							$target_keyword_store_id = $data['store_id'];
						} else {
							$target_keyword_store_id = 0;
						}
						
						$add .= " LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('information_id=', i.information_id) AND tk.store_id = '" . (int)$target_keyword_store_id . "' AND tk.language_id = id.language_id)";
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
						if (isset($element['target_keyword'][$language['language_id']])) {
							if ((isset($information['target_keyword'][$language['language_id']]) && ($element['target_keyword'][$language['language_id']] != $information['target_keyword'][$language['language_id']])) || !isset($information['target_keyword'][$language['language_id']])) {
								if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
									$target_keyword_store_id = $data['store_id'];
								} else {
									$target_keyword_store_id = 0;
								}
								
								$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'information_id=" . (int)$information['information_id'] . "' AND store_id = '" . (int)$target_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");	
								
								if ($element['target_keyword'][$language['language_id']]) {
									preg_match_all('/\[[^]]+\]/', $element['target_keyword'][$language['language_id']], $keywords);
									
									$sort_order = 1;
									
									foreach ($keywords[0] as $keyword) {
										$keyword = substr($keyword, 1, strlen($keyword) - 2);
										
										$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'information_id=" . (int)$information['information_id'] . "', store_id = '" . (int)$target_keyword_store_id . "', language_id = '" . (int)$language['language_id'] . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
										
										$sort_order++;
									}
								}
							}
						}
					}
				}	
			}
		}
	}
}
?>