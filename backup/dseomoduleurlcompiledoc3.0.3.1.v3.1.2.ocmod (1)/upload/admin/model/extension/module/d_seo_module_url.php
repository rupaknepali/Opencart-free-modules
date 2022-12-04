<?php
class ModelExtensionModuleDSEOModuleURL extends Model {
	private $codename = 'd_seo_module_url';
	private $route = 'extension/module/d_seo_module_url';
	private $config_file = 'd_seo_module_url';
		
	/*
	*	Create Default URL Elements.
	*/
	public function createDefaultURLElements($default_url_keywords, $store_id = 0) {
		$languages = $this->getLanguages();
		
		if (VERSION >= '3.0.0.0') {		
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query LIKE '%/%' AND store_id = '" . (int)$store_id . "'");
		} else {
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE '%/%' AND store_id = '" . (int)$store_id . "'");
			
			if ($store_id == 0) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query LIKE '%/%'");
			}
		}
		
		foreach ($languages as $language) {
			$implode1 = array();
			$implode2 = array();
			
			foreach ($default_url_keywords as $route => $url_keyword) {
				$implode1[] = "('" . $route . "', '" . (int)$store_id . "', '" . (int)$language['language_id'] . "', '" . $url_keyword . "')";
				$implode2[] = "('" . $route . "', '" . $url_keyword . "')";
			}
			
			if ($implode1 && $implode2) {
				if (VERSION >= '3.0.0.0') {	
					$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url (query, store_id, language_id, keyword) VALUES " . implode(', ', $implode1));
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword (route, store_id, language_id, keyword) VALUES " . implode(', ', $implode1));
				
					if (($store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias (query, keyword) VALUES " . implode(', ', $implode2));
					}
				}
			}
		}
		
		$cache_data = array(
			'route' => '%/%',
			'store_id' => $store_id
		);
		
		$this->refreshURLCache($cache_data);
	}
				
	/*
	*	Save Redirects.
	*/
	public function saveRedirects($redirects, $store_id = 0) {
		$store = $this->getStore($store_id);
		$store_url_info = $this->getURLInfo($store['url']);
		$store_url = $store_url_info['host'] . $store_url_info['port'] . $store_url_info['path'];
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_redirect WHERE url_from LIKE '%" . $this->db->escape($store_url) . "%'");
		
		foreach ($redirects as $redirect) {
			$implode = array();
		
			foreach ($redirect as $field_code => $value) {
				$implode[] = $this->db->escape($field_code) . " = '" . $this->db->escape($value) . "'";						
			}
		
			$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_redirect SET " . implode(', ', $implode));
		}
	}
		
	/*
	*	Add Redirect.
	*/
	public function addRedirect($data) {
		$implode = array();
		
		foreach ($data as $field_code => $value) {
			$implode[] = $this->db->escape($field_code) . " = '" . $this->db->escape($value) . "'";						
		}
		
		$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_redirect SET " . implode(', ', $implode));
	}
	
	/*
	*	Edit Redirect.
	*/
	public function editRedirect($data) {		
		$query = $this->db->query("UPDATE " . DB_PREFIX . "d_url_redirect SET " . $this->db->escape($data['field_code']) . " = '" . $this->db->escape($data['value']) . "' WHERE url_redirect_id = '" . (int)$data['url_redirect_id'] . "'");
	}
	
	/*
	*	Delete Redirect.
	*/
	public function deleteRedirect($url_redirect_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_redirect WHERE url_redirect_id = '" . (int)$url_redirect_id . "'");
	}
	
	/*
	*	Return Redirects.
	*/
	public function getRedirects($data = array()) {
		$redirects = array();
		
		$sql = "SELECT * FROM " . DB_PREFIX . "d_url_redirect";
		
		$implode = array();
		
		if (isset($data['filter'])) {
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					$implode[] = $this->db->escape($field_code) . " LIKE '%" . $this->db->escape($filter) . "%'";
				}
			}
		}
		
		if ($implode) {
			$sql .= " WHERE " .  implode(' AND ', $implode);
		}
		
		if (isset($data['sort'])) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY url_from";
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
	
	/*
	*	Return Total Redirects.
	*/
	public function getTotalRedirects($data = array()) {
		$redirects = array();
		
		$sql = "SELECT COUNT(DISTINCT url_redirect_id) AS total FROM " . DB_PREFIX . "d_url_redirect";

		$implode = array();
		
		if (isset($data['filter'])) {
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					$implode[] = $this->db->escape($field_code) . " LIKE '%" . $this->db->escape($filter) . "%'";
				}
			}
		}
		
		if ($implode) {
			$sql .= " WHERE " .  implode(' AND ', $implode);
		}

		$query = $this->db->query($sql);
								
		return $query->row['total'];
	}
	
	/*
	*	Refresh URL Cache.
	*/
	public function refreshURLCache($data = array()) {				
		$this->load->model('setting/setting');
		
		// Register Cache
		if (!$this->registry->has('d_cache') && file_exists(DIR_SYSTEM . 'library/d_cache.php')) {
			$this->registry->set('d_cache', new d_cache());
		}
						
		if (!$this->registry->has('d_cache')) return;
		
		$stores = $this->getStores();
		$languages = $this->getLanguages();
				
		// Setting 				
		$this->config->load($this->config_file);
				
		foreach ($stores as $store) {
			$data['setting'][$store['store_id']] = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
			
			$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $store['store_id']);
			$setting = isset($setting['module_' . $this->codename . '_setting']) ? $setting['module_' . $this->codename . '_setting'] : array();
		
			if (!empty($setting)) {
				$data['setting'][$store['store_id']] = array_replace_recursive($data['setting'][$store['store_id']], $setting);
			}
		}
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
		
		$category_short_url = true;
		$product_short_url = true;
		
		foreach ($stores as $store) {
			if (isset($data['store_id']) && ($data['store_id'] != $store['store_id'])) continue;
			if (!$data['setting'][$store['store_id']]['sheet']['category']['short_url']) $category_short_url = false;
			if (!$data['setting'][$store['store_id']]['sheet']['product']['short_url']) $product_short_url = false;
		}
				
		if (isset($data['route'])) {
			if (strpos($data['route'], 'category_id') === 0) {
				$category_id = str_replace('category_id=', '', $data['route']);
			}
			
			if (strpos($data['route'], 'product_id') === 0) {
				$product_id = str_replace('product_id=', '', $data['route']);
			}
			
			if (strpos($data['route'], 'manufacturer_id') === 0) {
				$manufacturer_id = str_replace('manufacturer_id=', '', $data['route']);
			}
			
			if (strpos($data['route'], 'information_id') === 0) {
				$information_id = str_replace('information_id=', '', $data['route']);
			}
			
			if (preg_match('/[A-Za-z0-9%]+\/[A-Za-z0-9%]+/i', $data['route']) && !in_array($data['route'], $custom_page_exception_routes)) {
				$route = $data['route'];
			}
		} else {
			$category_id = '%';
			$product_id = '%';
			$manufacturer_id = '%';
			$information_id = '%';
			$route = '%/%';
		}
		
		if (isset($category_id)) {
			$add = '';
			
			if ($category_id != '%') {
				$add = " WHERE c.category_id = '" . (int)$category_id . "'";
			}
			
			if (($category_id != '%') && !$category_short_url) {
				if (VERSION >= '3.0.0.0') {						
					$query = $this->db->query("SELECT cp.category_id AS category_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c2 ON (c2.category_id = cp.category_id) LEFT JOIN " . DB_PREFIX . "category c ON (c.category_id = cp.path_id) LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('category_id=', cp.category_id))" . $add);
				} else {
					$query = $this->db->query("SELECT cp.category_id AS category_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c2 ON (c2.category_id = cp.category_id) LEFT JOIN " . DB_PREFIX . "category c ON (c.category_id = cp.path_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('category_id=', cp.category_id))" . $add);
				}
			} else {
				if (VERSION >= '3.0.0.0') {
					$query = $this->db->query("SELECT c.category_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('category_id=', c.category_id))" . $add);
				} else {
					$query = $this->db->query("SELECT c.category_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('category_id=', c.category_id))" . $add);
				}
			}
			
			$categories = array();
			$sub_categories = array();
			
			foreach ($query->rows as $result) {
				$categories[$result['category_id']]['category_id'] = $result['category_id'];
							
				if (!isset($categories[$result['category_id']]['url_keyword'])) {
					$categories[$result['category_id']]['url_keyword'] = array();
				}
			
				if ((isset($field_info['sheet']['category']['field']['url_keyword']['multi_store']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store_status'])) {
					$categories[$result['category_id']]['url_keyword'][$result['store_id']][$result['language_id']] = $result['keyword'];
				} elseif ($result['store_id'] == 0) {
					foreach ($stores as $store) {
						$categories[$result['category_id']]['url_keyword'][$store['store_id']][$result['language_id']] = $result['keyword'];
					}
				}
			}
			
			foreach ($categories as $category) {
				foreach ($stores as $store) {
					if (($category_id != '%') && ($category_id != $category['category_id']) && $data['setting'][$store['store_id']]['sheet']['category']['short_url']) continue;
					if (isset($data['store_id']) && ($data['store_id'] != $store['store_id'])) continue;
					
					if (!$data['setting'][$store['store_id']]['sheet']['category']['short_url']) {
						$category_path = $this->getCategoryPath($category['category_id']);
					} else {
						$category_path = $category['category_id'];						
					}
															
					foreach ($languages as $language) {					
						if (isset($data['language_id']) && ($data['language_id'] != $language['language_id'])) continue;
						
						$url_rewrite = '';
					
						if ($category_path != $category['category_id']) {
							if (!$sub_categories) {
								if (VERSION >= '3.0.0.0') {
									$query = $this->db->query("SELECT c.category_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('category_id=', c.category_id))");
								} else {
									$query = $this->db->query("SELECT c.category_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('category_id=', c.category_id))");
								}
																	
								foreach ($query->rows as $result) {
									$sub_categories[$result['category_id']]['category_id'] = $result['category_id'];
							
									if (!isset($sub_categories[$result['category_id']]['url_keyword'])) {
										$sub_categories[$result['category_id']]['url_keyword'] = array();
									}
			
									if ((isset($field_info['sheet']['category']['field']['url_keyword']['multi_store']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store_status'])) {
										$sub_categories[$result['category_id']]['url_keyword'][$result['store_id']][$result['language_id']] = $result['keyword'];
									} elseif ($result['store_id'] == 0) {
										foreach ($stores as $store) {
											$sub_categories[$result['category_id']]['url_keyword'][$store['store_id']][$result['language_id']] = $result['keyword'];
										}
									}
								}
							}
							
							$sub_categories_id = explode('_', $category_path);
							
							foreach ($sub_categories_id as $sub_category_id) {									
								if (isset($sub_categories[$sub_category_id]['url_keyword'][$store['store_id']][$language['language_id']]) && $sub_categories[$sub_category_id]['url_keyword'][$store['store_id']][$language['language_id']]) {
									$url_rewrite .= '/' . $sub_categories[$sub_category_id]['url_keyword'][$store['store_id']][$language['language_id']];
								} else {
									$url_rewrite = '';

									break;
								}
							}
						} else {
							if (isset($categories[$category['category_id']]['url_keyword'][$store['store_id']][$language['language_id']]) && $categories[$category['category_id']]['url_keyword'][$store['store_id']][$language['language_id']]) {
								$url_rewrite .= '/' . $categories[$category['category_id']]['url_keyword'][$store['store_id']][$language['language_id']];
							} else {
								$url_rewrite = '';
							}
						}
						
						if ($url_rewrite && $data['setting'][$store['store_id']]['multi_language_sub_directory']['status'] && isset($data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']]) && $data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']]) {
							$url_rewrite = '/' . $data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']] . $url_rewrite;
						}
												
						$this->d_cache->set($this->codename, 'url_rewrite.product_category.' . $category['category_id'] . '.' . $store['store_id'] . '.' . $language['language_id'], $url_rewrite);								
					}
				}
			}
		}	

		if (isset($product_id) || (isset($category_id) && !$product_short_url)) {			
			if (isset($category_id)) {
				if ($category_id != '%') {
					if (VERSION >= '3.0.0.0') {						
						$query = $this->db->query("SELECT pc.product_id AS product_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "d_product_category pc LEFT JOIN " . DB_PREFIX . "category_path cp ON (cp.category_id = pc.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (c2.category_id = cp.category_id) LEFT JOIN " . DB_PREFIX . "category c ON (c.category_id = cp.path_id) LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('product_id=', pc.product_id)) WHERE c.category_id = '" . (int)$category_id . "'");
					} else {
						$query = $this->db->query("SELECT pc.product_id AS product_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "d_product_category pc LEFT JOIN " . DB_PREFIX . "category_path cp ON (cp.category_id = pc.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (c2.category_id = cp.category_id) LEFT JOIN " . DB_PREFIX . "category c ON (c.category_id = cp.path_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('product_id=', pc.product_id)) WHERE c.category_id = '" . (int)$category_id . "'");
					}
				} else {
					$product_id = '%';
				}
			} 
				
			if (isset($product_id)) {
				$add = '';
				
				if ($product_id != '%') {
					$add .= " WHERE p.product_id = '" . (int)$product_id . "'";
				}
						
				if (VERSION >= '3.0.0.0') {		
					$query = $this->db->query("SELECT p.product_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('product_id=', p.product_id))" . $add);
				} else {
					$query = $this->db->query("SELECT p.product_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('product_id=', p.product_id))" . $add);
				}
			}
			
			$products = array();
			$sub_categories = array();
			
			foreach ($query->rows as $result) {
				$products[$result['product_id']]['product_id'] = $result['product_id'];
			
				if (!isset($products[$result['product_id']]['url_keyword'])) {
					$products[$result['product_id']]['url_keyword'] = array();
				}
			
				if ((isset($field_info['sheet']['product']['field']['url_keyword']['multi_store']) && $field_info['sheet']['product']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['product']['field']['url_keyword']['multi_store_status'])) {
					$products[$result['product_id']]['url_keyword'][$result['store_id']][$result['language_id']] = $result['keyword'];
				} elseif ($result['store_id'] == 0) {
					foreach ($stores as $store) {
						$products[$result['product_id']]['url_keyword'][$store['store_id']][$result['language_id']] = $result['keyword'];
					}
				}
			}
			
			foreach ($products as $product) {
				foreach ($stores as $store) {
					if (isset($data['store_id']) && ($data['store_id'] != $store['store_id'])) continue;
					
					if (!$data['setting'][$store['store_id']]['sheet']['product']['short_url']) {
						$product_path = $this->getProductPath($product['product_id']);
					} else {
						$product_path = '';						
					}
					
					foreach ($languages as $language) {					
						if (isset($data['language_id']) && ($data['language_id'] != $language['language_id'])) continue;
						
						$url_rewrite = '';
					
						if ($product_path) {
							if (!$sub_categories) {
								if (VERSION >= '3.0.0.0') {
									$query = $this->db->query("SELECT c.category_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('category_id=', c.category_id))");
								} else {
									$query = $this->db->query("SELECT c.category_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('category_id=', c.category_id))");
								}
																	
								foreach ($query->rows as $result) {
									$sub_categories[$result['category_id']]['category_id'] = $result['category_id'];
							
									if (!isset($sub_categories[$result['category_id']]['url_keyword'])) {
										$sub_categories[$result['category_id']]['url_keyword'] = array();
									}
			
									if ((isset($field_info['sheet']['category']['field']['url_keyword']['multi_store']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store_status'])) {
										$sub_categories[$result['category_id']]['url_keyword'][$result['store_id']][$result['language_id']] = $result['keyword'];
									} elseif ($result['store_id'] == 0) {
										foreach ($stores as $store) {
											$sub_categories[$result['category_id']]['url_keyword'][$store['store_id']][$result['language_id']] = $result['keyword'];
										}
									}
								}
							}

							$sub_categories_id = explode('_', $product_path);
					
							foreach ($sub_categories_id as $sub_category_id) {									
								if (isset($sub_categories[$sub_category_id]['url_keyword'][$store['store_id']][$language['language_id']]) && $sub_categories[$sub_category_id]['url_keyword'][$store['store_id']][$language['language_id']]) {
									$url_rewrite .= '/' . $sub_categories[$sub_category_id]['url_keyword'][$store['store_id']][$language['language_id']];
								} else {
									$url_rewrite = '';

									break;
								}
							}
						}
						
						if (isset($products[$product['product_id']]['url_keyword'][$store['store_id']][$language['language_id']]) && $products[$product['product_id']]['url_keyword'][$store['store_id']][$language['language_id']]) {
							$url_rewrite .= '/' . $products[$product['product_id']]['url_keyword'][$store['store_id']][$language['language_id']];
						} else {
							$url_rewrite = '';
						}
						
						if ($url_rewrite && $data['setting'][$store['store_id']]['multi_language_sub_directory']['status'] && isset($data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']]) && $data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']]) {
							$url_rewrite = '/' . $data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']] . $url_rewrite;
						}
						
						$this->d_cache->set($this->codename, 'url_rewrite.product_product.' . $product['product_id'] . '.' . $store['store_id'] . '.' . $language['language_id'], $url_rewrite);
					}
				}
			}
		}
		
		if (isset($manufacturer_id)) {
			$add = '';
			
			if ($manufacturer_id != '%') {
				$add .= " WHERE m.manufacturer_id = '" . (int)$manufacturer_id . "'";
			}
			
			if (VERSION >= '3.0.0.0') {
				$query = $this->db->query("SELECT m.manufacturer_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "manufacturer m LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('manufacturer_id=', m.manufacturer_id))" . $add);
			} else {
				$query = $this->db->query("SELECT m.manufacturer_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "manufacturer m LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('manufacturer_id=', m.manufacturer_id))" . $add);
			}
			
			$manufacturers = array();
		
			foreach ($query->rows as $result) {
				$manufacturers[$result['manufacturer_id']]['manufacturer_id'] = $result['manufacturer_id'];
			
				if (!isset($manufacturers[$result['manufacturer_id']]['url_keyword'])) {
					$manufacturers[$result['manufacturer_id']]['url_keyword'] = array();
				}
			
				if ((isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store']) && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status'])) {
					$manufacturers[$result['manufacturer_id']]['url_keyword'][$result['store_id']][$result['language_id']] = $result['keyword'];
				} elseif ($result['store_id'] == 0) {
					foreach ($stores as $store) {
						$manufacturers[$result['manufacturer_id']]['url_keyword'][$store['store_id']][$result['language_id']] = $result['keyword'];
					}
				}
			}
			
			foreach ($manufacturers as $manufacturer) {
				foreach ($stores as $store) {
					if (isset($data['store_id']) && ($data['store_id'] != $store['store_id'])) continue;
					
					foreach ($languages as $language) {					
						if (isset($data['language_id']) && ($data['language_id'] != $language['language_id'])) continue;
						
						$url_rewrite = '';
						
						if (isset($manufacturers[$manufacturer['manufacturer_id']]['url_keyword'][$store['store_id']][$language['language_id']]) && $manufacturers[$manufacturer['manufacturer_id']]['url_keyword'][$store['store_id']][$language['language_id']]) {
							$url_rewrite .= '/' . $manufacturers[$manufacturer['manufacturer_id']]['url_keyword'][$store['store_id']][$language['language_id']];
						}
						
						if ($url_rewrite && $data['setting'][$store['store_id']]['multi_language_sub_directory']['status'] && isset($data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']]) && $data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']]) {
							$url_rewrite = '/' . $data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']] . $url_rewrite;
						}
													
						$this->d_cache->set($this->codename, 'url_rewrite.product_manufacturer_info.' . $manufacturer['manufacturer_id'] . '.' . $store['store_id'] . '.' . $language['language_id'], $url_rewrite);
					}
				}
			}
		}
		
		if (isset($information_id)) {
			$add = '';
			
			if ($information_id != '%') {
				$add .= " WHERE i.information_id = '" . (int)$information_id . "'";
			}
			
			if (VERSION >= '3.0.0.0') {
				$query = $this->db->query("SELECT i.information_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('information_id=', i.information_id))" . $add);
			} else {
				$query = $this->db->query("SELECT i.information_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('information_id=', i.information_id))" . $add);
			}
			
			$informations = array();
		
			foreach ($query->rows as $result) {
				$informations[$result['information_id']]['information_id'] = $result['information_id'];
			
				if (!isset($informations[$result['information_id']]['url_keyword'])) {
					$informations[$result['information_id']]['url_keyword'] = array();
				}
			
				if ((isset($field_info['sheet']['information']['field']['url_keyword']['multi_store']) && $field_info['sheet']['information']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['information']['field']['url_keyword']['multi_store_status'])) {
					$informations[$result['information_id']]['url_keyword'][$result['store_id']][$result['language_id']] = $result['keyword'];
				} elseif ($result['store_id'] == 0) {
					foreach ($stores as $store) {
						$informations[$result['information_id']]['url_keyword'][$store['store_id']][$result['language_id']] = $result['keyword'];
					}
				}
			}
			
			foreach ($informations as $information) {
				foreach ($stores as $store) {
					if (isset($data['store_id']) && ($data['store_id'] != $store['store_id'])) continue;
					
					foreach ($languages as $language) {					
						if (isset($data['language_id']) && ($data['language_id'] != $language['language_id'])) continue;
						
						$url_rewrite = '';
						
						if (isset($informations[$information['information_id']]['url_keyword'][$store['store_id']][$language['language_id']]) && $informations[$information['information_id']]['url_keyword'][$store['store_id']][$language['language_id']]) {
							$url_rewrite .= '/' . $informations[$information['information_id']]['url_keyword'][$store['store_id']][$language['language_id']];
						}
						
						if ($url_rewrite && $data['setting'][$store['store_id']]['multi_language_sub_directory']['status'] && isset($data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']]) && $data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']]) {
							$url_rewrite = '/' . $data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']] . $url_rewrite;
						}
													
						$this->d_cache->set($this->codename, 'url_rewrite.information_information.' . $information['information_id'] . '.' . $store['store_id'] . '.' . $language['language_id'], $url_rewrite);
					}
				}
			}
		}
		
		if (isset($route)) {
			if ($route == '%/%') {
				$add = " LIKE '%/%'";
			} else {
				$add = " = '" . $this->db->escape($route) . "'";
			}
			
			if (VERSION >= '3.0.0.0') {
				$query = $this->db->query("SELECT query as route, store_id, language_id, keyword FROM " . DB_PREFIX . "seo_url WHERE query" . $add);
			} else {
				$query = $this->db->query("SELECT route, store_id, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword WHERE route" . $add);
			}
			
			$custom_pages = array();
		
			foreach ($query->rows as $result) {
				if (!in_array($result['route'], $custom_page_exception_routes)) {
					$custom_pages[$result['route']]['route'] = $result['route'];
				
					if ((isset($field_info['sheet']['custom_page']['field']['url_keyword']['multi_store']) && $field_info['sheet']['custom_page']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['custom_page']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['custom_page']['field']['url_keyword']['multi_store_status'])) {
						$custom_pages[$result['route']]['url_keyword'][$result['store_id']][$result['language_id']] = $result['keyword'];
					} elseif ($result['store_id'] == 0) {
						foreach ($stores as $store) {
							$custom_pages[$result['route']]['url_keyword'][$store['store_id']][$result['language_id']] = $result['keyword'];
						}
					}
				}
			}
			
			foreach ($custom_pages as $custom_page) {
				foreach ($stores as $store) {
					if (isset($data['store_id']) && ($data['store_id'] != $store['store_id'])) continue;
					
					foreach ($languages as $language) {					
						if (isset($data['language_id']) && ($data['language_id'] != $language['language_id'])) continue;
						
						$url_rewrite = '';
						
						if (isset($custom_pages[$custom_page['route']]['url_keyword'][$store['store_id']][$language['language_id']])) {
							$url_keyword = $custom_pages[$custom_page['route']]['url_keyword'][$store['store_id']][$language['language_id']];

							if ($url_keyword) {
								if (substr($url_keyword, 0, 1) == '/') {
									$url_keyword = substr($url_keyword, 1, strlen($url_keyword) - 1);
								}
						
								$url_rewrite .= '/' . $url_keyword;	
							}
						} 
						
						if ($url_rewrite && $data['setting'][$store['store_id']]['multi_language_sub_directory']['status'] && isset($data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']]) && $data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']]) {
							$url_rewrite = '/' . $data['setting'][$store['store_id']]['multi_language_sub_directory']['name'][$language['language_id']] . $url_rewrite;
						}
																			
						$this->d_cache->set($this->codename, 'url_rewrite.' . preg_replace('/[^A-Z0-9\._-]/i', '_', $custom_page['route']) . '.' . $store['store_id'] . '.' . $language['language_id'], $url_rewrite);
					}
				}
			}
		}
	}
	
	/*
	*	Clear URL Cache.
	*/
	public function clearURLCache() {
		// Register Cache
		if (!$this->registry->has('d_cache') && file_exists(DIR_SYSTEM . 'library/d_cache.php')) {
			$this->registry->set('d_cache', new d_cache());
		}
						
		if ($this->registry->has('d_cache')) {
			$this->d_cache->delete($this->codename, 'url_rewrite');
		}
	}
	
	/*
	*	Return Category Path.
	*/		
	public function getCategoryPath($category_id) {		
		$path = false;
		
		$query = $this->db->query("SELECT GROUP_CONCAT(c.category_id ORDER BY level SEPARATOR '_') as category_path FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c ON (cp.path_id = c.category_id) WHERE cp.category_id = '" . (int)$category_id . "' GROUP BY cp.category_id");
		
		if ($query->num_rows) {
			$path = $query->row['category_path'];
		}
						
		return $path;
	}

	/*
	*	Return Product Path.
	*/		
	public function getProductPath($product_id) {		
		$path = false;
		
		$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "d_product_category WHERE product_id = '" . (int)$product_id . "'");
		
		if ($query->num_rows) {
			if ($query->row['category_id']) {
				$path = $this->getCategoryPath($query->row['category_id']);
			}
		}
				
		return $path;
	}
				
	/*
	*	Save SEO extensions.
	*/
	public function saveSEOExtensions($seo_extensions) {
		$this->load->model('setting/setting');
		
		$setting['d_seo_extension_install'] = $seo_extensions;
		
		$this->model_setting_setting->editSetting('d_seo_extension', $setting);
	}
	
	/*
	*	Return list of installed SEO extensions.
	*/
	public function getInstalledSEOExtensions() {
		$this->load->model('setting/setting');
				
		$installed_extensions = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension ORDER BY code");
		
		foreach ($query->rows as $result) {
			$installed_extensions[] = $result['code'];
		}
		
		$installed_seo_extensions = $this->model_setting_setting->getSetting('d_seo_extension');
		$installed_seo_extensions = isset($installed_seo_extensions['d_seo_extension_install']) ? $installed_seo_extensions['d_seo_extension_install'] : array();
		
		$seo_extensions = array();
		
		$files = glob(DIR_APPLICATION . 'controller/extension/d_seo_module/*.php');
		
		if ($files) {
			foreach ($files as $file) {
				$seo_extension = basename($file, '.php');
				
				if (in_array($seo_extension, $installed_extensions) && in_array($seo_extension, $installed_seo_extensions)) {
					$seo_extensions[] = $seo_extension;
				}
			}
		}
		
		return $seo_extensions;
	}
	
	/*
	*	Return list of installed SEO URL extensions.
	*/
	public function getInstalledSEOURLExtensions() {
		$this->load->model('setting/setting');
				
		$installed_extensions = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension ORDER BY code");
		
		foreach ($query->rows as $result) {
			$installed_extensions[] = $result['code'];
		}
		
		$installed_seo_extensions = $this->model_setting_setting->getSetting('d_seo_extension');
		$installed_seo_extensions = isset($installed_seo_extensions['d_seo_extension_install']) ? $installed_seo_extensions['d_seo_extension_install'] : array();
		
		$seo_url_extensions = array();
		
		$files = glob(DIR_APPLICATION . 'controller/extension/' . $this->codename . '/*.php');
		
		if ($files) {
			foreach ($files as $file) {
				$seo_url_extension = basename($file, '.php');
				
				if (in_array($seo_url_extension, $installed_extensions) && in_array($seo_url_extension, $installed_seo_extensions)) {
					$seo_url_extensions[] = $seo_url_extension;
				}
			}
		}
		
		return $seo_url_extensions;
	}
		
	/*
	*	Return list of languages.
	*/
	public function getLanguages() {
		$this->load->model('localisation/language');
		
		$languages = $this->model_localisation_language->getLanguages();
		
		foreach ($languages as $key => $language) {
            if (VERSION >= '2.2.0.0') {
                $languages[$key]['flag'] = 'language/' . $language['code'] . '/' . $language['code'] . '.png';
            } else {
                $languages[$key]['flag'] = 'view/image/flags/' . $language['image'];
            }
        }
		
		return $languages;
	}
	
	/*
	*	Return list of stores.
	*/
	public function getStores() {
		$this->load->model('setting/store');
		
		$result = array();
		
		$result[] = array(
			'store_id' => 0, 
			'name' => $this->config->get('config_name')
		);
		
		$stores = $this->model_setting_store->getStores();
		
		if ($stores) {			
			foreach ($stores as $store) {
				$result[] = array(
					'store_id' => $store['store_id'],
					'name' => $store['name']	
				);
			}	
		}
		
		return $result;
	}
	
	/*
	*	Return store.
	*/
	public function getStore($store_id) {
		$this->load->model('setting/store');
		
		$result = array();
		
		if ($store_id == 0) {
			$result = array(
				'store_id' => 0, 
				'name' => $this->config->get('config_name'),
				'url' => HTTP_CATALOG,
				'ssl' => HTTPS_CATALOG
			);
		} else {
			$store = $this->model_setting_store->getStore($store_id);
			
			$result = array(
				'store_id' => $store['store_id'],
				'name' => $store['name'],
				'url' => $store['url'],
				'ssl' => $store['ssl']
			);
		}
				
		return $result;
	}
		
	/*
	*	Return URL Info.
	*/	
	public function getURLInfo($url) {						
		$url_info = parse_url(str_replace('&amp;', '&', $url));
		
		$url_info['scheme'] = isset($url_info['scheme']) ? $url_info['scheme'] . '://' : '';
		$url_info['user'] = isset($url_info['user']) ? $url_info['user'] : '';
		$url_info['pass'] = isset($url_info['pass']) ? ':' . $url_info['pass']  : '';
		$url_info['pass'] = ($url_info['user'] || $url_info['pass']) ? $url_info['pass'] . '@' : ''; 
		$url_info['host'] = isset($url_info['host']) ? $url_info['host'] : '';
		$url_info['port'] = isset($url_info['port']) ? ':' . $url_info['port'] : '';
		$url_info['path'] = isset($url_info['path']) ? $url_info['path'] : '';		
		
		$url_info['data'] = array();
		
		if (isset($url_info['query'])) {
			parse_str($url_info['query'], $url_info['data']);
		}
		
		$url_info['query'] = isset($url_info['query']) ? '?' . $url_info['query'] : '';
		$url_info['fragment'] = isset($url_info['fragment']) ? '#' . $url_info['fragment'] : '';
						
		return $url_info;
	}
	
	/*
	*	Sort Array By Column.
	*/
	public function sortArrayByColumn($arr, $col, $dir = SORT_ASC) {
		$sort_col = array();
		$sort_key = array();
		
		foreach ($arr as $key => $row) {
			$sort_key[$key] = $key;
			
			if (isset($row[$col])) {
				$sort_col[$key] = $row[$col];
			} else {
				$sort_col[$key] = '';
			}
		}
		
		array_multisort($sort_col, $dir, $sort_key, SORT_ASC, $arr);
		
		return $arr;
	}
		
	/*
	*	Install.
	*/		
	public function installExtension() {
		$languages = $this->getLanguages();
				
		if (VERSION < '3.0.0.0') {		
			$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
					
			$this->db->query("CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "d_url_keyword (route VARCHAR(255) NOT NULL, store_id INT(11) NOT NULL, language_id INT(11) NOT NULL, keyword VARCHAR(255) NOT NULL, PRIMARY KEY (route, store_id, language_id), KEY keyword (keyword)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
			
			$add = '';
		
			if ($custom_page_exception_routes) {
				$add = " AND route NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
			}
			
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE 'category_id=%' OR route LIKE 'product_id=%' OR route LIKE 'manufacturer_id=%' OR route LIKE 'information_id=%' OR (route LIKE '%/%'" . $add . ")");
			
			$add = '';
		
			if ($custom_page_exception_routes) {
				$add = " AND query NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
			}
				
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "url_alias WHERE query LIKE 'category_id=%' OR query LIKE 'product_id=%' OR query LIKE 'manufacturer_id=%' OR query LIKE 'information_id=%' OR (query LIKE '%/%'" . $add . ")");
		
			foreach ($query->rows as $result) {
				foreach ($languages as $language) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = '" . $this->db->escape($result['query']) . "', store_id = '0', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($result['keyword']) . "'");
				}
			}
		}
				
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "d_url_redirect");
		
		$sql = "CREATE TABLE " . DB_PREFIX . "d_url_redirect (url_redirect_id INT(11) NOT NULL AUTO_INCREMENT, url_from VARCHAR(512) NOT NULL, ";
		
		foreach ($languages as $language) {
			$sql .= "url_to_" . (int)$language['language_id'] . " VARCHAR(512) NOT NULL, ";
		}
		
		$sql .= "PRIMARY KEY (url_redirect_id), KEY url_from (url_from)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
		
		$this->db->query($sql);
		
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "d_product_category");
		
		$this->db->query("CREATE TABLE " . DB_PREFIX . "d_product_category (product_id INT(11) NOT NULL, category_id INT(11) NOT NULL, PRIMARY KEY (product_id)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");		
		
		$query = $this->db->query("SELECT DISTINCT pc.product_id, pc.category_id, GROUP_CONCAT(cp.path_id ORDER BY cp.level SEPARATOR '_') AS category_path FROM " . DB_PREFIX . "product_to_category pc LEFT JOIN " . DB_PREFIX . "category_path cp ON (cp.category_id = pc.category_id) GROUP BY pc.product_id, cp.category_id ORDER BY category_path");
				
		$product_category = array();
		
		foreach ($query->rows as $result) {
			$product_category[$result['product_id']] = $result['category_id'];
		}
		
		foreach ($product_category as $product_id => $category_id) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "d_product_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
		}
								
		$this->refreshURLCache();
	}
	
	/*
	*	Uninstall.
	*/			
	public function uninstallExtension() {		
		if (VERSION < '3.0.0.0') {
			$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
			
			$add = '';
		
			if ($custom_page_exception_routes) {
				$add = " AND route NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
			}
			
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE 'category_id=%' OR route LIKE 'product_id=%' OR route LIKE 'manufacturer_id=%' OR route LIKE 'information_id=%' OR (route LIKE '%/%'" . $add . ")");
			
			$add = '';
		
			if ($custom_page_exception_routes) {
				$add = " AND query NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
			}
			
			$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query LIKE '%/%'" . $add);
		}
		
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "d_url_redirect");
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "d_product_category");
		
		// Register Cache
		if (!$this->registry->has('d_cache') && file_exists(DIR_SYSTEM . 'library/d_cache.php')) {
			$this->registry->set('d_cache', new d_cache());
		}
						
		if ($this->registry->has('d_cache')) {
			$this->d_cache->deleteAll($this->codename);
		}
	}
}
?>