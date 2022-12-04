<?php
class ModelExtensionDSEOModuleDSEOModule extends Model {
	private $codename = 'd_seo_module';
		
	/*
	*	Add Language.
	*/
	public function addLanguage($data) {
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
		
		$add = '';
		
		if ($custom_page_exception_routes) {
			$add = " AND route NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
		}
				
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_target_keyword WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' AND (route LIKE 'category_id=%' OR route LIKE 'product_id=%' OR route LIKE 'manufacturer_id=%' OR route LIKE 'information_id=%' OR (route LIKE '%/%'" . $add . "))");
								
		foreach ($query->rows as $result) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = '" . $this->db->escape($result['route']) . "', store_id = '" . (int)$result['store_id'] . "', language_id = '" . (int)$data['language_id'] . "', sort_order = '" . (int)$result['sort_order'] . "', keyword = '" . $this->db->escape($result['keyword']) . "'");				
		}
	}
	
	/*
	*	Delete Language.
	*/
	public function deleteLanguage($data) {
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
		
		$add = '';
		
		if ($custom_page_exception_routes) {
			$add = " AND route NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
		}
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE language_id = '" . (int)$data['language_id'] . "' AND (route LIKE 'category_id=%' OR route LIKE 'product_id=%' OR route LIKE 'manufacturer_id=%' OR route LIKE 'information_id=%' OR (route LIKE '%/%'" . $add . "))");
	}
	
	/*
	*	Delete Store.
	*/
	public function deleteStore($data) {
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
		
		$add = '';
		
		if ($custom_page_exception_routes) {
			$add = " AND route NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
		}
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE store_id = '" . (int)$data['store_id'] . "' AND (route LIKE 'category_id=%' OR route LIKE 'product_id=%' OR route LIKE 'manufacturer_id=%' OR route LIKE 'information_id=%' OR (route LIKE '%/%'" . $add . "))");
	}
	
	/*
	*	Save Home Target Keyword.
	*/
	public function saveHomeTargetKeyword($data) {						
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'common/home' AND store_id = '" . (int)$data['store_id'] . "'");
		
		if (isset($data['target_keyword'])) {
			foreach ($data['target_keyword'] as $language_id => $keywords) {
				$sort_order = 1;
				
				foreach ($keywords as $keyword) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'common/home', store_id = '" . (int)$data['store_id'] . "', language_id = '" . (int)$language_id . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
					
					$sort_order++;
				}
			}
		}
	}
		
	/*
	*	Save Category Target Keyword.
	*/
	public function saveCategoryTargetKeyword($data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'category_id=" . (int)$data['category_id'] . "'");
						
		if (isset($data['target_keyword'])) {
			foreach ($data['target_keyword'] as $store_id => $language_target_keyword) {
				foreach ($language_target_keyword as $language_id => $keywords) {
					$sort_order = 1;
				
					foreach ($keywords as $keyword) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'category_id=" . (int)$data['category_id'] . "', store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
					
						$sort_order++;
					}
				}
			}
		}
	}
	
	/*
	*	Save Product Target Keyword.
	*/
	public function saveProductTargetKeyword($data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'product_id=" . (int)$data['product_id'] . "'");
						
		if (isset($data['target_keyword'])) {
			foreach ($data['target_keyword'] as $store_id => $language_target_keyword) {
				foreach ($language_target_keyword as $language_id => $keywords) {
					$sort_order = 1;
				
					foreach ($keywords as $keyword) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'product_id=" . (int)$data['product_id'] . "', store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
					
						$sort_order++;
					}
				}
			}
		}
	}
	
	/*
	*	Save Manufacturer Target Keyword.
	*/
	public function saveManufacturerTargetKeyword($data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'manufacturer_id=" . (int)$data['manufacturer_id'] . "'");
						
		if (isset($data['target_keyword'])) {
			foreach ($data['target_keyword'] as $store_id => $language_target_keyword) {
				foreach ($language_target_keyword as $language_id => $keywords) {
					$sort_order = 1;
				
					foreach ($keywords as $keyword) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'manufacturer_id=" . (int)$data['manufacturer_id'] . "', store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
					
						$sort_order++;
					}
				}
			}
		}
	}
	
	/*
	*	Save Information Target Keyword.
	*/
	public function saveInformationTargetKeyword($data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'information_id=" . (int)$data['information_id'] . "'");
						
		if (isset($data['target_keyword'])) {
			foreach ($data['target_keyword'] as $store_id => $language_target_keyword) {
				foreach ($language_target_keyword as $language_id => $keywords) {
					$sort_order = 1;
				
					foreach ($keywords as $keyword) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = 'information_id=" . (int)$data['information_id'] . "', store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
					
						$sort_order++;
					}
				}
			}
		}
	}
	
	/*
	*	Delete Category Target Keyword.
	*/
	public function deleteCategoryTargetKeyword($data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'category_id=" . (int)$data['category_id'] . "'");
	}
	
	/*
	*	Delete Product Target Keyword.
	*/
	public function deleteProductTargetKeyword($data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'product_id=" . (int)$data['product_id'] . "'");
	}
	
	/*
	*	Delete Manufacturer Target Keyword.
	*/
	public function deleteManufacturerTargetKeyword($data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'manufacturer_id=" . (int)$data['manufacturer_id'] . "'");
	}
	
	/*
	*	Delete Information Target Keyword.
	*/
	public function deleteInformationTargetKeyword($data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'information_id=" . (int)$data['information_id'] . "'");
	}

	/*
	*	Return Home Target Keyword.
	*/
	public function getHomeTargetKeyword($store_id = 0) {
		$target_keyword = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'common/home' AND store_id = '" . (int)$store_id . "' ORDER BY sort_order");
		
		foreach($query->rows as $result) {
			$target_keyword[$result['language_id']][$result['sort_order']] = $result['keyword'];
		}
		
		return $target_keyword;
	}	
		
	/*
	*	Return Category Target Keyword.
	*/
	public function getCategoryTargetKeyword($category_id) {
		$target_keyword = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'category_id=" . (int)$category_id . "' ORDER BY sort_order");
		
		foreach($query->rows as $result) {
			$target_keyword[$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
		}
		
		return $target_keyword;
	}
	
	/*
	*	Return Product Target Keyword.
	*/
	public function getProductTargetKeyword($product_id) {
		$target_keyword = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'product_id=" . (int)$product_id . "' ORDER BY sort_order");
		
		foreach($query->rows as $result) {
			$target_keyword[$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
		}
		
		return $target_keyword;
	}
	
	/*
	*	Return Manufacturer Target Keyword.
	*/
	public function getManufacturerTargetKeyword($manufacturer_id) {
		$target_keyword = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'manufacturer_id=" . (int)$manufacturer_id . "' ORDER BY sort_order");
		
		foreach($query->rows as $result) {
			$target_keyword[$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
		}
		
		return $target_keyword;
	}
	
	/*
	*	Return Information Target Keyword.
	*/
	public function getInformationTargetKeyword($information_id) {
		$target_keyword = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_target_keyword WHERE route = 'information_id=" . (int)$information_id . "' ORDER BY sort_order");
		
		foreach($query->rows as $result) {
			$target_keyword[$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
		}
		
		return $target_keyword;
	}
	
	/*
	*	Return Target Elements.
	*/	
	public function getTargetElements($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .=  'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .=  'user_token=' . $this->session->data['user_token'];
		}
		
		$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
		
		$target_elements = array();	
						
		if ($data['sheet_code'] == 'category') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
				$target_keyword_store_id = $data['store_id'];
			} else {
				$target_keyword_store_id = 0;
			}
						
			$sql = "SELECT tk.route, tk.language_id, tk.sort_order, tk.keyword, c.category_id FROM " . DB_PREFIX . "d_target_keyword tk LEFT JOIN " . DB_PREFIX . "category c ON (CONCAT('category_id=', c.category_id) = tk.route) LEFT JOIN " . DB_PREFIX . "d_target_keyword tk2 ON (tk2.route = tk.route AND tk2.store_id = '" . (int)$target_keyword_store_id . "') WHERE tk.route LIKE 'category_id=%' AND tk.store_id = '" . (int)$target_keyword_store_id . "'";
			
			$implode = array();
			$implode_language = array();
						
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'route') {
						$implode[] = "tk2.route = '" . $this->db->escape($filter) . "'";
					}
										
					if ($field_code == 'target_keyword') {
						foreach ($filter as $language_id => $value) {
							if (!empty($value)) {
								$implode_language[] = "(tk2.language_id = '" . (int)$language_id . "' AND tk2.keyword LIKE '%" . $this->db->escape($value) . "%')";
							}
						}
					}
				}
			}
			
			if ($implode_language) {
				$implode[] = '(' . implode(' OR ', $implode_language) . ')';
			}
			
			if ($implode) {
				$sql .= " AND " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY tk.route, tk.language_id, tk.sort_order";
			
			$query = $this->db->query($sql);
						
			foreach ($query->rows as $result) {
				$target_elements[$result['route']]['route'] = $result['route'];
				$target_elements[$result['route']]['target_keyword'][$result['language_id']][$result['sort_order']] = $result['keyword'];
					
				if ($result['category_id']) {
					$target_elements[$result['route']]['link'] = $this->url->link('catalog/category/edit', $url_token . '&category_id=' . $result['category_id'], true);
				}
			}
					
			return $target_elements;
		}
				
		if ($data['sheet_code'] == 'product') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
				$target_keyword_store_id = $data['store_id'];
			} else {
				$target_keyword_store_id = 0;
			}
						
			$sql = "SELECT tk.route, tk.language_id, tk.sort_order, tk.keyword, p.product_id FROM " . DB_PREFIX . "d_target_keyword tk LEFT JOIN " . DB_PREFIX . "product p ON (CONCAT('product_id=', p.product_id) = tk.route) LEFT JOIN " . DB_PREFIX . "d_target_keyword tk2 ON (tk2.route = tk.route AND tk2.store_id = '" . (int)$target_keyword_store_id . "') WHERE tk.route LIKE 'product_id=%' AND tk.store_id = '" . (int)$target_keyword_store_id . "'";
			
			$implode = array();
			$implode_language = array();
						
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'route') {
						$implode[] = "tk2.route = '" . $this->db->escape($filter) . "'";
					}
										
					if ($field_code == 'target_keyword') {
						foreach ($filter as $language_id => $value) {
							if (!empty($value)) {
								$implode_language[] = "(tk2.language_id = '" . (int)$language_id . "' AND tk2.keyword LIKE '%" . $this->db->escape($value) . "%')";
							}
						}
					}
				}
			}
			
			if ($implode_language) {
				$implode[] = '(' . implode(' OR ', $implode_language) . ')';
			}
			
			if ($implode) {
				$sql .= " AND " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY tk.route, tk.language_id, tk.sort_order";
			
			$query = $this->db->query($sql);
						
			foreach ($query->rows as $result) {
				$target_elements[$result['route']]['route'] = $result['route'];
				$target_elements[$result['route']]['target_keyword'][$result['language_id']][$result['sort_order']] = $result['keyword'];
					
				if ($result['product_id']) {
					$target_elements[$result['route']]['link'] = $this->url->link('catalog/product/edit', $url_token . '&product_id=' . $result['product_id'], true);
				}
			}
					
			return $target_elements;	
		}
		
		if ($data['sheet_code'] == 'manufacturer') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
				$target_keyword_store_id = $data['store_id'];
			} else {
				$target_keyword_store_id = 0;
			}
						
			$sql = "SELECT tk.route, tk.language_id, tk.sort_order, tk.keyword, m.manufacturer_id FROM " . DB_PREFIX . "d_target_keyword tk LEFT JOIN " . DB_PREFIX . "manufacturer m ON (CONCAT('manufacturer_id=', m.manufacturer_id) = tk.route) LEFT JOIN " . DB_PREFIX . "d_target_keyword tk2 ON (tk2.route = tk.route AND tk2.store_id = '" . (int)$target_keyword_store_id . "') WHERE tk.route LIKE 'manufacturer_id=%' AND tk.store_id = '" . (int)$target_keyword_store_id . "'";
			
			$implode = array();
			$implode_language = array();
						
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'route') {
						$implode[] = "tk2.route = '" . $this->db->escape($filter) . "'";
					}
										
					if ($field_code == 'target_keyword') {
						foreach ($filter as $language_id => $value) {
							if (!empty($value)) {
								$implode_language[] = "(tk2.language_id = '" . (int)$language_id . "' AND tk2.keyword LIKE '%" . $this->db->escape($value) . "%')";
							}
						}
					}
				}
			}
			
			if ($implode_language) {
				$implode[] = '(' . implode(' OR ', $implode_language) . ')';
			}
			
			if ($implode) {
				$sql .= " AND " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY tk.route, tk.language_id, tk.sort_order";
			
			$query = $this->db->query($sql);
						
			foreach ($query->rows as $result) {
				$target_elements[$result['route']]['route'] = $result['route'];
				$target_elements[$result['route']]['target_keyword'][$result['language_id']][$result['sort_order']] = $result['keyword'];
				
				if ($result['manufacturer_id']) {
					$target_elements[$result['route']]['link'] = $this->url->link('catalog/manufacturer/edit', $url_token . '&manufacturer_id=' . $result['manufacturer_id'], true);
				}
			}
					
			return $target_elements;	
		}
		
		if ($data['sheet_code'] == 'information') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
				$target_keyword_store_id = $data['store_id'];
			} else {
				$target_keyword_store_id = 0;
			}
						
			$sql = "SELECT tk.route, tk.language_id, tk.sort_order, tk.keyword, i.information_id FROM " . DB_PREFIX . "d_target_keyword tk LEFT JOIN " . DB_PREFIX . "information i ON (CONCAT('information_id=', i.information_id) = tk.route) LEFT JOIN " . DB_PREFIX . "d_target_keyword tk2 ON (tk2.route = tk.route AND tk2.store_id = '" . (int)$target_keyword_store_id . "') WHERE tk.route LIKE 'information_id=%' AND tk.store_id = '" . (int)$target_keyword_store_id . "'";
			
			$implode = array();
			$implode_language = array();
						
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'route') {
						$implode[] = "tk2.route = '" . $this->db->escape($filter) . "'";
					}
										
					if ($field_code == 'target_keyword') {
						foreach ($filter as $language_id => $value) {
							if (!empty($value)) {
								$implode_language[] = "(tk2.language_id = '" . (int)$language_id . "' AND tk2.keyword LIKE '%" . $this->db->escape($value) . "%')";
							}
						}
					}
				}
			}
			
			if ($implode_language) {
				$implode[] = '(' . implode(' OR ', $implode_language) . ')';
			}
			
			if ($implode) {
				$sql .= " AND " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY tk.route, tk.language_id, tk.sort_order";
			
			$query = $this->db->query($sql);
						
			foreach ($query->rows as $result) {
				$target_elements[$result['route']]['route'] = $result['route'];
				$target_elements[$result['route']]['target_keyword'][$result['language_id']][$result['sort_order']] = $result['keyword'];
				
				if ($result['information_id']) {
					$target_elements[$result['route']]['link'] = $this->url->link('catalog/information/edit', $url_token . '&information_id=' . $result['information_id'], true);
				}
			}
					
			return $target_elements;	
		}
		
		if ($data['sheet_code'] == 'custom_page') {			
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
				$target_keyword_store_id = $data['store_id'];
			} else {
				$target_keyword_store_id = 0;
			}
			
			$sql = "SELECT tk.route, tk.language_id, tk.sort_order, tk.keyword FROM " . DB_PREFIX . "d_target_keyword tk LEFT JOIN " . DB_PREFIX . "d_target_keyword tk2 ON (tk2.route = tk.route AND tk2.store_id = '" . (int)$target_keyword_store_id . "') WHERE tk.route LIKE '%/%' AND tk.store_id = '" . (int)$target_keyword_store_id . "'";
			
			$implode = array();
			$implode_language = array();
						
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'route') {
						$implode[] = "tk2.route = '" . $this->db->escape($filter) . "'";
					}
										
					if ($field_code == 'target_keyword') {
						foreach ($filter as $language_id => $value) {
							if (!empty($value)) {
								$implode_language[] = "(tk2.language_id = '" . (int)$language_id . "' AND tk2.keyword LIKE '%" . $this->db->escape($value) . "%')";
							}
						}
					}
				}
			}
			
			if ($implode_language) {
				$implode[] = '(' . implode(' OR ', $implode_language) . ')';
			}
			
			if ($implode) {
				$sql .= " AND " . implode(' AND ', $implode);
			}

			$sql .= " GROUP BY tk.route, tk.language_id, tk.sort_order";
			
			$query = $this->db->query($sql);
						
			foreach ($query->rows as $result) {
				if (!in_array($result['route'], $custom_page_exception_routes)) {
					$target_elements[$result['route']]['route'] = $result['route'];
					$target_elements[$result['route']]['target_keyword'][$result['language_id']][$result['sort_order']] = $result['keyword'];
				}
			}
									
			return $target_elements;
		}
	}
					
	/*
	*	Add Target Element.
	*/
	public function addTargetElement($data) {
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
		
		if (isset($data['route']) && isset($data['store_id']) && isset($data['target_keyword'])) {
			if ((strpos($data['route'], 'category_id') === 0) || (strpos($data['route'], 'product_id') === 0) || (strpos($data['route'], 'manufacturer_id') === 0) || (strpos($data['route'], 'information_id') === 0) || (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $data['route']) && !in_array($data['route'], $custom_page_exception_routes))) {	
				$target_keyword_store_id = 0;
				
				if (strpos($data['route'], 'category_id') === 0) {
					if (isset($field_info['sheet']['category']['field']['target_keyword']['multi_store']) && $field_info['sheet']['category']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['category']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['category']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				if (strpos($data['route'], 'product_id') === 0) {
					if (isset($field_info['sheet']['product']['field']['target_keyword']['multi_store']) && $field_info['sheet']['product']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['product']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['product']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				if (strpos($data['route'], 'manufacturer_id') === 0) {
					if (isset($field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store']) && $field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				if (strpos($data['route'], 'information_id') === 0) {
					if (isset($field_info['sheet']['information']['field']['target_keyword']['multi_store']) && $field_info['sheet']['information']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['information']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['information']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				if (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $data['route'])) {
					if (isset($field_info['sheet']['custom_page']['field']['target_keyword']['multi_store']) && $field_info['sheet']['custom_page']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['custom_page']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['custom_page']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
						
				foreach ($data['target_keyword'] as $language_id => $target_keyword) {
					preg_match_all('/\[[^]]+\]/', $target_keyword, $keywords);
				
					$sort_order = 1;
		
					foreach ($keywords[0] as $keyword) {
						$keyword = substr($keyword, 1, strlen($keyword) - 2);
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = '" . $this->db->escape($data['route']) . "', store_id = '" . (int)$target_keyword_store_id . "', language_id = '" . (int)$language_id . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
					
						$sort_order++;
					}
				}
			}
		}
	}
	
	/*
	*	Edit Target Element.
	*/
	public function editTargetElement($data) {
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
		
		if (isset($data['route']) && isset($data['store_id']) && isset($data['language_id']) && isset($data['target_keyword'])) {
			if ((strpos($data['route'], 'category_id') === 0) || (strpos($data['route'], 'product_id') === 0) || (strpos($data['route'], 'manufacturer_id') === 0) || (strpos($data['route'], 'information_id') === 0) || (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $data['route']) && !in_array($data['route'], $custom_page_exception_routes))) {	
				$target_keyword_store_id = 0;
				
				if (strpos($data['route'], 'category_id') === 0) {
					if (isset($field_info['sheet']['category']['field']['target_keyword']['multi_store']) && $field_info['sheet']['category']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['category']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['category']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				if (strpos($data['route'], 'product_id') === 0) {
					if (isset($field_info['sheet']['product']['field']['target_keyword']['multi_store']) && $field_info['sheet']['product']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['product']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['product']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				if (strpos($data['route'], 'manufacturer_id') === 0) {
					if (isset($field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store']) && $field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				if (strpos($data['route'], 'information_id') === 0) {
					if (isset($field_info['sheet']['information']['field']['target_keyword']['multi_store']) && $field_info['sheet']['information']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['information']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['information']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				if (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $data['route'])) {
					if (isset($field_info['sheet']['custom_page']['field']['target_keyword']['multi_store']) && $field_info['sheet']['custom_page']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['custom_page']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['custom_page']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
		
				$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = '" . $this->db->escape($data['route']) . "' AND store_id = '" . (int)$target_keyword_store_id . "' AND language_id = '" . (int)$data['language_id'] . "'");
				
				if ($data['target_keyword']) {
					preg_match_all('/\[[^]]+\]/', $data['target_keyword'], $keywords);
				
					$sort_order = 1;
		
					foreach ($keywords[0] as $keyword) {
						$keyword = substr($keyword, 1, strlen($keyword) - 2);
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = '" . $this->db->escape($data['route']) . "', store_id = '" . (int)$target_keyword_store_id . "', language_id = '" . (int)$data['language_id'] . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
					
						$sort_order++;
					}
				}
			}
		}
	}
	
	/*
	*	Delete Target Element.
	*/
	public function deleteTargetElement($data) {
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
		
		if (isset($data['route']) && isset($data['store_id'])) {
			if ((strpos($data['route'], 'category_id') === 0) || (strpos($data['route'], 'product_id') === 0) || (strpos($data['route'], 'manufacturer_id') === 0) || (strpos($data['route'], 'information_id') === 0) || (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $data['route']) && !in_array($data['route'], $custom_page_exception_routes))) {	
				$target_keyword_store_id = 0;
				
				if (strpos($data['route'], 'category_id') === 0) {
					if (isset($field_info['sheet']['category']['field']['target_keyword']['multi_store']) && $field_info['sheet']['category']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['category']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['category']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				if (strpos($data['route'], 'product_id') === 0) {
					if (isset($field_info['sheet']['product']['field']['target_keyword']['multi_store']) && $field_info['sheet']['product']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['product']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['product']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				if (strpos($data['route'], 'manufacturer_id') === 0) {
					if (isset($field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store']) && $field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				if (strpos($data['route'], 'information_id') === 0) {
					if (isset($field_info['sheet']['information']['field']['target_keyword']['multi_store']) && $field_info['sheet']['information']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['information']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['information']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				if (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $data['route'])) {
					if (isset($field_info['sheet']['custom_page']['field']['target_keyword']['multi_store']) && $field_info['sheet']['custom_page']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['custom_page']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['custom_page']['field']['target_keyword']['multi_store_status']) {
						$target_keyword_store_id = $data['store_id'];
					} else {
						$target_keyword_store_id = 0;
					}
				}
				
				$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = '" . $this->db->escape($data['route']) . "' AND store_id = '" . (int)$target_keyword_store_id . "'");
			}
		}
	}
	
	/*
	*	Return Export Target Elements.
	*/
	public function getExportTargetElements($data) {	
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
		
		$target_elements = array();	
		
		if ($data['sheet_code'] == 'category') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
				$target_keyword_store_id = $data['store_id'];
			} else {
				$target_keyword_store_id = 0;
			}
						
			$query = $this->db->query("SELECT route, language_id, CONCAT('[', GROUP_CONCAT(DISTINCT keyword ORDER BY sort_order SEPARATOR ']['), ']') as target_keyword FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE 'category_id=%' AND store_id = '" . (int)$target_keyword_store_id . "' GROUP BY route, language_id");	
							
			foreach ($query->rows as $result) {
				$target_elements[$result['route']]['route'] = $result['route'];
				$target_elements[$result['route']]['target_keyword'][$result['language_id']] = $result['target_keyword'];
			}
					
			return $target_elements;
		}
		
		if ($data['sheet_code'] == 'product') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
				$target_keyword_store_id = $data['store_id'];
			} else {
				$target_keyword_store_id = 0;
			}
						
			$query = $this->db->query("SELECT route, language_id, CONCAT('[', GROUP_CONCAT(DISTINCT keyword ORDER BY sort_order SEPARATOR ']['), ']') as target_keyword FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE 'product_id=%' AND store_id = '" . (int)$target_keyword_store_id . "' GROUP BY route, language_id");
							
			foreach ($query->rows as $result) {
				$target_elements[$result['route']]['route'] = $result['route'];
				$target_elements[$result['route']]['target_keyword'][$result['language_id']] = $result['target_keyword'];
			}
					
			return $target_elements;	
		}
		
		if ($data['sheet_code'] == 'manufacturer') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
				$target_keyword_store_id = $data['store_id'];
			} else {
				$target_keyword_store_id = 0;
			}
						
			$query = $this->db->query("SELECT route, language_id, CONCAT('[', GROUP_CONCAT(DISTINCT keyword ORDER BY sort_order SEPARATOR ']['), ']') as target_keyword FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE 'manufacturer_id=%' AND store_id = '" . (int)$target_keyword_store_id . "' GROUP BY route, language_id");	
							
			foreach ($query->rows as $result) {
				$target_elements[$result['route']]['route'] = $result['route'];
				$target_elements[$result['route']]['target_keyword'][$result['language_id']] = $result['target_keyword'];
			}
					
			return $target_elements;	
		}
		
		if ($data['sheet_code'] == 'information') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
				$target_keyword_store_id = $data['store_id'];
			} else {
				$target_keyword_store_id = 0;
			}
						
			$query = $this->db->query("SELECT route, language_id, CONCAT('[', GROUP_CONCAT(DISTINCT keyword ORDER BY sort_order SEPARATOR ']['), ']') as target_keyword FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE 'information_id=%' AND store_id = '" . (int)$target_keyword_store_id . "' GROUP BY route, language_id");	
							
			foreach ($query->rows as $result) {
				$target_elements[$result['route']]['route'] = $result['route'];
				$target_elements[$result['route']]['target_keyword'][$result['language_id']] = $result['target_keyword'];
			}
					
			return $target_elements;	
		}
		
		if ($data['sheet_code'] == 'custom_page') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['target_keyword']['multi_store_status']) {
				$target_keyword_store_id = $data['store_id'];
			} else {
				$target_keyword_store_id = 0;
			}
			
			$query = $this->db->query("SELECT route, language_id, CONCAT('[', GROUP_CONCAT(DISTINCT keyword ORDER BY sort_order SEPARATOR ']['), ']') as target_keyword FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE '%/%' AND store_id = '" . (int)$target_keyword_store_id . "' GROUP BY route, language_id");
									
			foreach ($query->rows as $result) {
				if (!in_array($result['route'], $custom_page_exception_routes)) {
					$target_elements[$result['route']]['route'] = $result['route'];
					$target_elements[$result['route']]['target_keyword'][$result['language_id']] = $result['target_keyword'];
				}
			}
									
			return $target_elements;
		}
	}
	
	/*
	*	Save Import Target Elements.
	*/
	public function saveImportTargetElements($data) {		
		$this->load->model('extension/module/' . $this->codename);
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
		
		$target_elements = array();	
		
		if ($data['store_id'] && $field_info['sheet']['category']['field']['target_keyword']['multi_store'] && $field_info['sheet']['category']['field']['target_keyword']['multi_store_status']) {
			$target_keyword_store_id = $data['store_id'];
		} else {
			$target_keyword_store_id = 0;
		}
						
		$query = $this->db->query("SELECT route, language_id, CONCAT('[', GROUP_CONCAT(DISTINCT keyword ORDER BY sort_order SEPARATOR ']['), ']') as target_keyword FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE 'category_id=%' AND store_id = '" . (int)$target_keyword_store_id . "' GROUP BY route, language_id");	
							
		foreach ($query->rows as $result) {
			$target_elements[$result['route']]['route'] = $result['route'];
			$target_elements[$result['route']]['target_keyword'][$result['language_id']] = $result['target_keyword'];
		}
				
		if ($data['store_id'] && $field_info['sheet']['product']['field']['target_keyword']['multi_store'] && $field_info['sheet']['product']['field']['target_keyword']['multi_store_status']) {
			$target_keyword_store_id = $data['store_id'];
		} else {
			$target_keyword_store_id = 0;
		}
						
		$query = $this->db->query("SELECT route, language_id, CONCAT('[', GROUP_CONCAT(DISTINCT keyword ORDER BY sort_order SEPARATOR ']['), ']') as target_keyword FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE 'product_id=%' AND store_id = '" . (int)$target_keyword_store_id . "' GROUP BY route, language_id");
							
		foreach ($query->rows as $result) {
			$target_elements[$result['route']]['route'] = $result['route'];
			$target_elements[$result['route']]['target_keyword'][$result['language_id']] = $result['target_keyword'];
		}
		
		if ($data['store_id'] && $field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store'] && $field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store_status']) {
			$target_keyword_store_id = $data['store_id'];
		} else {
			$target_keyword_store_id = 0;
		}
						
		$query = $this->db->query("SELECT route, language_id, CONCAT('[', GROUP_CONCAT(DISTINCT keyword ORDER BY sort_order SEPARATOR ']['), ']') as target_keyword FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE 'manufacturer_id=%' AND store_id = '" . (int)$target_keyword_store_id . "' GROUP BY route, language_id");	
							
		foreach ($query->rows as $result) {
			$target_elements[$result['route']]['route'] = $result['route'];
			$target_elements[$result['route']]['target_keyword'][$result['language_id']] = $result['target_keyword'];
		}
					
		if ($data['store_id'] && $field_info['sheet']['information']['field']['target_keyword']['multi_store'] && $field_info['sheet']['information']['field']['target_keyword']['multi_store_status']) {
			$target_keyword_store_id = $data['store_id'];
		} else {
			$target_keyword_store_id = 0;
		}
						
		$query = $this->db->query("SELECT route, language_id, CONCAT('[', GROUP_CONCAT(DISTINCT keyword ORDER BY sort_order SEPARATOR ']['), ']') as target_keyword FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE 'information_id=%' AND store_id = '" . (int)$target_keyword_store_id . "' GROUP BY route, language_id");	
							
		foreach ($query->rows as $result) {
			$target_elements[$result['route']]['route'] = $result['route'];
			$target_elements[$result['route']]['target_keyword'][$result['language_id']] = $result['target_keyword'];
		}
		
		if ($data['store_id'] && $field_info['sheet']['custom_page']['field']['target_keyword']['multi_store'] && $field_info['sheet']['custom_page']['field']['target_keyword']['multi_store_status']) {
			$target_keyword_store_id = $data['store_id'];
		} else {
			$target_keyword_store_id = 0;
		}
					
		$query = $this->db->query("SELECT route, language_id, CONCAT('[', GROUP_CONCAT(DISTINCT keyword ORDER BY sort_order SEPARATOR ']['), ']') as target_keyword FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE '%/%' AND store_id = '" . (int)$target_keyword_store_id . "' GROUP BY route, language_id");
									
		foreach ($query->rows as $result) {
			if (!in_array($result['route'], $custom_page_exception_routes)) {
				$target_elements[$result['route']]['route'] = $result['route'];
				$target_elements[$result['route']]['target_keyword'][$result['language_id']] = $result['target_keyword'];
			}
		}
		
		foreach ($data['target_elements'] as $target_element) {
			$sheet_code = '';
			
			if (strpos($target_element['route'], 'category_id') === 0) $sheet_code = 'category';			
			if (strpos($target_element['route'], 'product_id') === 0) $sheet_code = 'product';
			if (strpos($target_element['route'], 'manufacturer_id') === 0) $sheet_code = 'manufacturer';
			if (strpos($target_element['route'], 'information_id') === 0) $sheet_code = 'information';
			if (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $target_element['route']) && !($custom_page_exception_routes && in_array($target_element['route'], $custom_page_exception_routes))) $sheet_code = 'custom_page';
							
			if ($sheet_code) {
				foreach ($languages as $language) {
					if (isset($target_element['target_keyword'][$language['language_id']])) {
						if ((isset($target_elements[$target_element['route']]['target_keyword'][$language['language_id']]) && ($target_element['target_keyword'][$language['language_id']] != $target_elements[$target_element['route']]['target_keyword'][$language['language_id']])) || !isset($target_elements[$target_element['route']]['target_keyword'][$language['language_id']])) {
							if ($data['store_id'] && $field_info['sheet'][$sheet_code]['field']['target_keyword']['multi_store'] && $field_info['sheet'][$sheet_code]['field']['target_keyword']['multi_store_status']) {
								$target_keyword_store_id = $data['store_id'];
							} else {
								$target_keyword_store_id = 0;
							}
															
							$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route = '" . $this->db->escape($target_element['route']) . "' AND store_id = '" . (int)$target_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");	
								
							if ($target_element['target_keyword'][$language['language_id']]) {
								preg_match_all('/\[[^]]+\]/', $target_element['target_keyword'][$language['language_id']], $keywords);
									
								$sort_order = 1;
									
								foreach ($keywords[0] as $keyword) {
									$keyword = substr($keyword, 1, strlen($keyword) - 2);
									
									$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword SET route = '" . $this->db->escape($target_element['route']) . "', store_id = '" . (int)$target_keyword_store_id . "', language_id = '" . (int)$language['language_id'] . "', sort_order = '" . $sort_order . "', keyword = '" .  $this->db->escape($keyword) . "'");
									
									$sort_order++;
								}
							}
						}
					}
				}	
			}
		}
	}
			
	/*
	*	Return Field Elements.
	*/
	public function getFieldElements($data) {				
		if ($data['field_code'] == 'target_keyword') {
			$this->load->model('extension/module/' . $this->codename);
		
			$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
		
			$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
			$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
			
			$field_elements = array();
					
			$sql = "SELECT * FROM " . DB_PREFIX . "d_target_keyword";
			
			$implode = array();
				
			foreach ($data['filter'] as $filter_code => $filter) {
				if (!empty($filter)) {
					if ($filter_code == 'route') {
						if (strpos($filter, '%') !== false) {
							$implode[] = "route LIKE '" . $this->db->escape($filter) . "'";
						} else {
							$implode[] = "route = '" . $this->db->escape($filter) . "'";
						}
					}
													
					if ($filter_code == 'language_id' ) {
						$implode[] = "language_id = '" . (int)$filter . "'";
					}
						
					if ($filter_code == 'sort_order') {
						$implode[] = "sort_order = '" . (int)$filter . "'";
					}
						
					if ($filter_code == 'keyword') {
						$implode[] = "keyword = '" . $this->db->escape($filter) . "'";
					}
				}
			}
		
			if ($implode) {
				$sql .= " WHERE " . implode(' AND ', $implode);
			}
		
			$sql .= " ORDER BY sort_order";
				
			$query = $this->db->query($sql);
										
			foreach ($query->rows as $result) {
				if (strpos($result['route'], 'category_id') === 0) {
					if (isset($field_info['sheet']['category']['field']['target_keyword']['multi_store']) && $field_info['sheet']['category']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['category']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['category']['field']['target_keyword']['multi_store_status']) {
						if ((isset($data['filter']['store_id']) && ($result['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
							$field_elements[$result['route']][$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
						}
					} elseif ($result['store_id'] == 0) {
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$result['route']][$store['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
							}
						}
					}
				}
					
				if (strpos($result['route'], 'product_id') === 0) {
					if (isset($field_info['sheet']['product']['field']['target_keyword']['multi_store']) && $field_info['sheet']['product']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['product']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['product']['field']['target_keyword']['multi_store_status']) {
						if ((isset($data['filter']['store_id']) && ($result['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
							$field_elements[$result['route']][$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
						}
					} elseif ($result['store_id'] == 0) {
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$result['route']][$store['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
							}
						}
					}
				}
					
				if (strpos($result['route'], 'manufacturer_id') === 0) {
					if (isset($field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store']) && $field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store_status']) {
						if ((isset($data['filter']['store_id']) && ($result['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
							$field_elements[$result['route']][$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
						}
					} elseif ($result['store_id'] == 0) {
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$result['route']][$store['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
							}
						}
					}
				}
					
				if (strpos($result['route'], 'information_id') === 0) {
					if (isset($field_info['sheet']['information']['field']['target_keyword']['multi_store']) && $field_info['sheet']['information']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['information']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['information']['field']['target_keyword']['multi_store_status']) {
						if ((isset($data['filter']['store_id']) && ($result['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
							$field_elements[$result['route']][$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
						}
					} elseif ($result['store_id'] == 0) {
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$result['route']][$store['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
							}
						}
					}
				}
					
				if (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $result['route']) && !($custom_page_exception_routes && in_array($result['route'], $custom_page_exception_routes))) {
					if (isset($field_info['sheet']['custom_page']['field']['target_keyword']['multi_store']) && $field_info['sheet']['custom_page']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['custom_page']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['custom_page']['field']['target_keyword']['multi_store_status']) {
						if ((isset($data['filter']['store_id']) && ($result['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
							$field_elements[$result['route']][$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
						}
					} elseif ($result['store_id'] == 0) {
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$result['route']][$store['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
							}
						}
					}
				}
			}
				
			return $field_elements;
		}
		
		if ($data['field_code'] == 'url_keyword') {
			$this->load->model('extension/module/' . $this->codename);
		
			$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
			$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
			$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
			$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
			
			if (!(isset($field_info['sheet']['category']['field']['url_keyword']['multi_store']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store']) && $field_info['sheet']['product']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store']) && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store']) && $field_info['sheet']['information']['field']['url_keyword']['multi_store']) {
				if (VERSION >= '3.0.0.0') {
					$sql = "SELECT * FROM " . DB_PREFIX . "seo_url";
				} else {
					$sql = "SELECT * FROM " . DB_PREFIX . "url_alias";
				}
				
				$implode = array();
				
				foreach ($data['filter'] as $filter_code => $filter) {
					if (!empty($filter)) {
						if ($filter_code == 'route') {
							if (strpos($filter, '%') !== false) {
								$implode[] = "query LIKE '" . $this->db->escape($filter) . "'";
							} else {
								$implode[] = "query = '" . $this->db->escape($filter) . "'";
							}
						}
												
						if (VERSION >= '3.0.0.0') {						
							if ($filter_code == 'language_id' ) {
								$implode[] = "language_id = '" . (int)$filter . "'";
							}
						}
												
						if ($filter_code == 'keyword') {
							$implode[] = "keyword = '" . $this->db->escape($filter) . "'";
						}
					}
				}
		
				if ($implode) {
					$sql .= " WHERE " . implode(' AND ', $implode);
				}
							
				$query = $this->db->query($sql);
										
				foreach ($query->rows as $result) {
					if ((strpos($result['route'], 'category_id') === 0) || (strpos($result['route'], 'product_id') === 0) || (strpos($result['route'], 'manufacturer_id') === 0) || (strpos($result['route'], 'information_id') === 0) || (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $result['route']) && !($custom_page_exception_routes && in_array($result['route'], $custom_page_exception_routes)))) {	
						if (VERSION >= '3.0.0.0') {	
							if ((isset($data['filter']['store_id']) && ($result['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$result['route']][$result['store_id']][$result['language_id']] = $result['keyword'];
							}
						} else {
							foreach ($stores as $store) {
								foreach ($languages as $language) {
									if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
										$field_elements[$result['route']][$store['store_id']][$language['language_id']] = $result['keyword'];
									}
								}
							}
						}
					}
				}				
				
				return $field_elements;
			}
		}
		
		if ($data['field_code'] == 'meta_data') {
			$this->load->model('extension/module/' . $this->codename);
			$this->load->model('setting/setting');
			
			$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
			$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
			
			$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
			$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_meta/getCustomPageExceptionRoutes');
			
			$field_elements = array();
			
			if (!(isset($field_info['sheet']['category']['field']['meta_title']['multi_store']) && $field_info['sheet']['category']['field']['meta_title']['multi_store'] && isset($field_info['sheet']['product']['field']['meta_title']['multi_store']) && $field_info['sheet']['product']['field']['meta_title']['multi_store'] && isset($field_info['sheet']['manufacturer']['field']['meta_title']['multi_store']) && $field_info['sheet']['manufacturer']['field']['meta_title']['multi_store'] && isset($field_info['sheet']['information']['field']['meta_title']['multi_store']) && $field_info['sheet']['information']['field']['meta_title']['multi_store'])) {
				if ((isset($data['filter']['route']) && (strpos($data['filter']['route'], 'category_id') === 0)) || !isset($data['filter']['route'])) {
					$sql = "SELECT * FROM " . DB_PREFIX . "category_description";
			
					$implode = array();
				
					foreach ($data['filter'] as $filter_code => $filter) {
						if (!empty($filter)) {
							if ($filter_code == 'route') {
								$route_arr = explode('category_id=', $filter);
			
								if (isset($route_arr[1]) && ($route_arr[1] != '%')) {
									$category_id = $route_arr[1];
									$implode[] = "category_id = '" . (int)$category_id . "'";
								}
							}
													
							if ($filter_code == 'language_id' ) {
								$implode[] = "language_id = '" . (int)$filter . "'";
							}
											
							if ($filter_code == 'name') {
								$implode[] = "name = '" . $this->db->escape($filter) . "'";
							}
										
							if ($filter_code == 'description') {
								$implode[] = "description = '" . $this->db->escape($filter) . "'";
							}
					
							if ($filter_code == 'meta_title') {
								$implode[] = "meta_title = '" . $this->db->escape($filter) . "'";
							}
					
							if ($filter_code == 'meta_description') {
								$implode[] = "meta_description = '" . $this->db->escape($filter) . "'";
							}
					
							if ($filter_code == 'meta_keyword') {
								$implode[] = "meta_keyword = '" . $this->db->escape($filter) . "'";
							}
						}
					}
					
					if ($implode) {
						$sql .= " WHERE " . implode(' AND ', $implode);
					}
						
					$query = $this->db->query($sql);
					
					foreach ($query->rows as $result) {
						$route = 'category_id=' . $result['category_id'];
				
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$route][$store['store_id']][$result['language_id']]['name'] = $result['name'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['description'] = $result['description'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['meta_title'] = $result['meta_title'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['meta_description'] = $result['meta_description'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['meta_keyword'] = $result['meta_keyword'];
							}
						}			
					}
				}
			
				if ((isset($data['filter']['route']) && (strpos($data['filter']['route'], 'product_id') === 0)) || !isset($data['filter']['route'])) {
					$sql = "SELECT * FROM " . DB_PREFIX . "product_description";
			
					$implode = array();
								
					foreach ($data['filter'] as $filter_code => $filter) {
						if (!empty($filter)) {
							if ($filter_code == 'route') {
								$route_arr = explode('product_id=', $filter);
			
								if (isset($route_arr[1]) && ($route_arr[1] != '%')) {
									$product_id = $route_arr[1];
									$implode[] = "product_id = '" . (int)$product_id . "'";
								}
							}
													
							if ($filter_code == 'language_id' ) {
								$implode[] = "language_id = '" . (int)$filter . "'";
							}
											
							if ($filter_code == 'name') {
								$implode[] = "name = '" . $this->db->escape($filter) . "'";
							}
										
							if ($filter_code == 'description') {
								$implode[] = "description = '" . $this->db->escape($filter) . "'";
							}
					
							if ($filter_code == 'meta_title') {
								$implode[] = "meta_title = '" . $this->db->escape($filter) . "'";
							}
					
							if ($filter_code == 'meta_description') {
								$implode[] = "meta_description = '" . $this->db->escape($filter) . "'";
							}
					
							if ($filter_code == 'meta_keyword') {
								$implode[] = "meta_keyword = '" . $this->db->escape($filter) . "'";
							}
					
							if ($filter_code == 'tag') {
								$implode[] = "tag = '" . $this->db->escape($filter) . "'";
							}
						}
					}
					
					if ($implode) {
						$sql .= " WHERE " . implode(' AND ', $implode);
					}
						
					$query = $this->db->query($sql);
										
					foreach ($query->rows as $result) {
						$route = 'product_id=' . $result['product_id'];
							
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$route][$store['store_id']][$result['language_id']]['name'] = $result['name'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['description'] = $result['description'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['meta_title'] = $result['meta_title'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['meta_description'] = $result['meta_description'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['meta_keyword'] = $result['meta_keyword'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['tag'] = $result['tag'];
							}
						}
					}
				}
				
				if ((isset($data['filter']['route']) && (strpos($data['filter']['route'], 'manufacturer_id') === 0)) || !isset($data['filter']['route'])) {
					$sql = "SELECT * FROM " . DB_PREFIX . "manufacturer";
			
					$implode = array();
				
					foreach ($data['filter'] as $filter_code => $filter) {
						if (!empty($filter)) {
							if ($filter_code == 'route') {
								$route_arr = explode('manufacturer_id=', $filter);
			
								if (isset($route_arr[1]) && ($route_arr[1] != '%')) {
									$manufacturer_id = $route_arr[1];
									$implode[] = "manufacturer_id = '" . (int)$manufacturer_id . "'";
								}
							}
																								
							if ($filter_code == 'name') {
								$implode[] = "name = '" . $this->db->escape($filter) . "'";
							}
						}
					}
			
					if ($implode) {
						$sql .= " WHERE " . implode(' AND ', $implode);
					}
						
					$query = $this->db->query($sql);
										
					foreach ($query->rows as $result) {
						$route = 'manufacturer_id=' . $result['manufacturer_id'];
						
						foreach ($stores as $store) {
							foreach ($languages as $language) {						
								if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
									$field_elements[$route][$store['store_id']][$language['language_id']]['name'] = $result['name'];
								}
							}
						}
					}
				}
		
				if ((isset($data['filter']['route']) && (strpos($data['filter']['route'], 'information_id') === 0)) || !isset($data['filter']['route'])) {
					$sql = "SELECT * FROM " . DB_PREFIX . "information_description";
			
					$implode = array();
				
					foreach ($data['filter'] as $filter_code => $filter) {
						if (!empty($filter)) {
							if ($filter_code == 'route') {
								$route_arr = explode('information_id=', $filter);
			
								if (isset($route_arr[1]) && ($route_arr[1] != '%')) {
									$information_id = $route_arr[1];
									$implode[] = "information_id = '" . (int)$information_id . "'";
								}
							}
													
							if ($filter_code == 'language_id' ) {
								$implode[] = "language_id = '" . (int)$filter . "'";
							}
											
							if ($filter_code == 'title') {
								$implode[] = "title = '" . $this->db->escape($filter) . "'";
							}
										
							if ($filter_code == 'description') {
								$implode[] = "description = '" . $this->db->escape($filter) . "'";
							}
					
							if ($filter_code == 'meta_title') {
								$implode[] = "meta_title = '" . $this->db->escape($filter) . "'";
							}
					
							if ($filter_code == 'meta_description') {
								$implode[] = "meta_description = '" . $this->db->escape($filter) . "'";
							}
					
							if ($filter_code == 'meta_keyword') {
								$implode[] = "meta_keyword = '" . $this->db->escape($filter) . "'";
							}
						}
					}
				
					if ($implode) {
						$sql .= " WHERE " . implode(' AND ', $implode);
					}
						
					$query = $this->db->query($sql);
										
					foreach ($query->rows as $result) {
						$route = 'information_id=' . $result['information_id'];
							
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$route][$store['store_id']][$result['language_id']]['title'] = $result['title'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['description'] = $result['description'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['meta_title'] = $result['meta_title'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['meta_description'] = $result['meta_description'];
								$field_elements[$route][$store['store_id']][$result['language_id']]['meta_keyword'] = $result['meta_keyword'];
							}
						}
					}	
				}
			
				if ((isset($data['filter']['route']) && ($data['filter']['route'] == 'common/home')) || !isset($data['filter']['route'])) {
					$route = 'common/home';
					
					foreach ($stores as $store) {
						if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
							if ($store['store_id'] == 0) {
								$meta_title = $this->config->get('config_meta_title');
								$meta_description = $this->config->get('config_meta_description');
								$meta_keyword = $this->config->get('config_meta_keyword');
							} else {
								$store_info = $this->model_setting_setting->getSetting('config', $store['store_id']);
									
								$meta_title = isset($store_info['meta_title']) ? $store_info['meta_title'] : '';
								$meta_description = isset($store_info['meta_description']) ? $store_info['meta_description'] : '';
								$meta_keyword = isset($store_info['meta_keyword']) ? $store_info['meta_keyword'] : '';
							}
							
							if (((isset($data['filter']['meta_title']) && ($meta_title == $data['filter']['meta_title'])) || !isset($data['filter']['meta_title'])) && ((isset($data['filter']['meta_description']) && ($meta_description == $data['filter']['meta_description'])) || !isset($data['filter']['meta_description'])) && ((isset($data['filter']['meta_keyword']) && ($meta_keyword == $data['filter']['meta_keyword'])) || !isset($data['filter']['meta_keyword']))) {
								foreach ($languages as $language) {
									$field_elements[$route][$store['store_id']][$language['language_id']]['meta_title'] = $meta_title;
									$field_elements[$route][$store['store_id']][$language['language_id']]['meta_description'] = $meta_description;
									$field_elements[$route][$store['store_id']][$language['language_id']]['meta_keyword'] = $meta_keyword;
								}
							}
						}
					}
				}
			}
				
			return $field_elements;
		}		
		
		return false;
	}
}
?>