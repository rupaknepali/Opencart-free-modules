<?php
class ModelExtensionDSEOModuleDSEOModule extends Model {
	private $codename = 'd_seo_module';
	
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
					$sql = "SELECT query as route, store_id, language_id, keyword FROM " . DB_PREFIX . "seo_url";
				} else {
					$sql = "SELECT query as route, keyword FROM " . DB_PREFIX . "url_alias";
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