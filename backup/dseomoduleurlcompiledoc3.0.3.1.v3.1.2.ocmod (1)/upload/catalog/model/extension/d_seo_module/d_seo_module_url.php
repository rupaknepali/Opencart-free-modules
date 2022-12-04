<?php
class ModelExtensionDSEOModuleDSEOModuleURL extends Model {
	private $codename = 'd_seo_module_url';
	
	/*
	*	Return URL for Language.
	*/	
	public function getURLForLanguage($url, $language_code) {	
		$url_info = $this->getURLInfo($url);
		
		$store_id = (int)$this->config->get('config_store_id');
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "language WHERE code = '" . $language_code . "'");

		$language_id = (int)$query->row['language_id'];
		
		// Setting
		$setting = ($this->config->get('module_' . $this->codename . '_setting')) ? $this->config->get('module_' . $this->codename . '_setting') : array();
		
		if (!isset($url_info['data']['route']) && !isset($url_info['data']['_route_']) && ($url == $this->url->link('common/home', '', true))) {
			$url_info['data']['route'] = 'common/home';
		}
	
		if (isset($url_info['data']['_route_'])) {
			$parts = explode('/', $url_info['data']['_route_']);

			// remove any empty arrays from trailing
			if (utf8_strlen(end($parts)) == 0) {
				array_pop($parts);
			}
			
			if ($setting['multi_language_sub_directory']['status']) {
				foreach ($setting['multi_language_sub_directory']['name'] as $subdirectory_language_id => $subdirectory_name) {
					if ($subdirectory_name == reset($parts)) {
						$multi_language_sub_directory_language_id = $subdirectory_language_id;
						
						array_shift($parts);
						
						break;
					}
				}
			}
			
			if (empty($parts)) {
				$url_info['data']['route'] = 'common/home';
			}

			foreach ($parts as $part) {	
				$field_data = array(
					'field_code' => 'url_keyword',
					'filter' => array(
						'store_id' => $store_id,
						'keyword' => $part
					)
				);
			
				$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
				
				if ($url_keywords) {				
					foreach ($url_keywords as $url_route => $store_url_keywords) {
						foreach ($store_url_keywords[$store_id] as $url_language_id => $keyword) {
							$route = $url_route;
						}
							
						foreach ($store_url_keywords[$store_id] as $url_language_id => $keyword) {
							if ($url_language_id == (int)$this->config->get('config_language_id')) {
								$route = $url_route;
							}
						}
					}
				}
													
				if (isset($route)) {
					$route = explode('=', $route);

					if ($route[0] == 'product_id') {
						$url_info['data']['product_id'] = $route[1];
					}

					if ($route[0] == 'category_id') {
						if (!isset($url_info['data']['path'])) {
							$url_info['data']['path'] = $route[1];
						} else {
							$url_info['data']['path'] .= '_' . $route[1];
						}
					}

					if ($route[0] == 'manufacturer_id') {
						$url_info['data']['manufacturer_id'] = $route[1];
					}

					if ($route[0] == 'information_id') {
						$url_info['data']['information_id'] = $route[1];
					}

					if (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $route[0])) {
						$url_info['data']['route'] = $route[0];
					}
				} else {
					break;
				}
			}
		}
		
		$params = array();
			
		if (isset($url_info['data']['product_id'])) {
			$url_info['data']['route'] = 'product/product';
			if (isset($url_info['data']['path'])) $params[] = 'path=' . $url_info['data']['path'];
			$params[] = 'product_id=' . $url_info['data']['product_id'];
		} elseif (isset($url_info['data']['path'])) {
			$url_info['data']['route'] = 'product/category';
			$params[] = 'path=' . $url_info['data']['path'];
		} elseif (isset($url_info['data']['manufacturer_id'])) {
			$url_info['data']['route'] = 'product/manufacturer/info';
			$params[] = 'manufacturer_id=' . $url_info['data']['manufacturer_id'];
		} elseif (isset($url_info['data']['information_id'])) {
			$url_info['data']['route'] = 'information/information';
			$params[] = 'information_id=' . $url_info['data']['information_id'];
		}
		
		if (isset($url_info['data']['route'])) {
			foreach($url_info['data'] as $param => $value) {
				if ($param != '_route_' && $param != 'route' && $param != 'path' && $param != 'product_id' && $param != 'manufacturer_id' && $param != 'information_id') {
					$params[] = $param . '=' . $value;
				}
			}
		
			$config_language_id = $this->config->get('config_language_id');
			$this->config->set('config_language_id', $language_id);
			$url = $this->url->link($url_info['data']['route'], implode('&', $params), true);
			$this->config->set('config_language_id', $config_language_id);	
		}

		return $url;
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
	*	Return Current URL.
	*/	
	public function getCurrentURL() {
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$url = "https://";
		} else {
			$url = 'http://';
		}
		
		$url .= $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
			
		$url = str_replace('&', '&amp;', str_replace('&amp;', '&', $url));
		
		return $url;
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
	*	Add URL Redirect From.
	*/	
	public function addURLRedirectFrom($url_from) {		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_url_redirect WHERE url_from = '" . $this->db->escape($url_from) . "'");
		
		if (!$query->num_rows) {	
			$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_redirect SET url_from = '" . $this->db->escape($url_from) . "'");
		}
	}
	
	/*
	*	Get URL Redirect To.
	*/	
	public function getURLRedirectTo($url_from) {		
		$config_language_id = $this->config->get('config_language_id');
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_url_redirect WHERE url_from = '" . $this->db->escape($url_from) . "'");
		
		if ($query->row) {
			return $query->row['url_to_' . $config_language_id];
		} else {
			return false;
		}
	}
	
	/*
	*	Validate Route.
	*/		
	public function validateRoute($route) {
		if ($route == 'error/not_found') {
			return false;
		}
		
		$parts = explode('/', preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route));

		// Break apart the route
		while ($parts) {
			$file = DIR_APPLICATION . 'controller/' . implode('/', $parts) . '.php';

			if (is_file($file)) {
				$route = implode('/', $parts);		
				
				break;
			} else {
				$method = array_pop($parts);
			}
		}
		
		$file = DIR_APPLICATION . 'controller/' . $route . '.php';		
		
		if (!is_file($file)) {
			return false;
		}
		
		return true;
	}
	
	/*
	*	Return Field Elements.
	*/
	public function getFieldElements($data) {				
		if ($data['field_code'] == 'url_keyword') {
			$this->load->model('extension/module/' . $this->codename);
		
			$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
		
			$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
			$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
			
			$field_elements = array();
			
			if (VERSION >= '3.0.0.0') {
				$sql = "SELECT query as route, store_id, language_id, keyword FROM " . DB_PREFIX . "seo_url";
			} else {
				$sql = "SELECT route, store_id, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword";
			}
			
			$implode = array();
				
			foreach ($data['filter'] as $filter_code => $filter) {
				if (!empty($filter)) {
					if ($filter_code == 'route') {
						if (strpos($filter, '%') !== false) {
							if (VERSION >= '3.0.0.0') {
								$implode[] = "query LIKE '" . $this->db->escape($filter) . "'";
							}else {
								$implode[] = "route LIKE '" . $this->db->escape($filter) . "'";
							}
						} else {
							if (VERSION >= '3.0.0.0') {
								$implode[] = "query = '" . $this->db->escape($filter) . "'";
							}else {
								$implode[] = "route = '" . $this->db->escape($filter) . "'";
							}
						}
					}
														
					if ($filter_code == 'language_id' ) {
						$implode[] = "language_id = '" . (int)$filter . "'";
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
				if (strpos($result['route'], 'category_id') === 0) {
					if (isset($field_info['sheet']['category']['field']['url_keyword']['multi_store']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) {
						if ((isset($data['filter']['store_id']) && ($result['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
							$field_elements[$result['route']][$result['store_id']][$result['language_id']] = $result['keyword'];
						}
					} elseif ($result['store_id'] == 0) {
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$result['route']][$store['store_id']][$result['language_id']] = $result['keyword'];
							}
						}
					}
				}
					
				if (strpos($result['route'], 'product_id') === 0) {
					if (isset($field_info['sheet']['product']['field']['url_keyword']['multi_store']) && $field_info['sheet']['product']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['product']['field']['url_keyword']['multi_store_status']) {
						if ((isset($data['filter']['store_id']) && ($result['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
							$field_elements[$result['route']][$result['store_id']][$result['language_id']] = $result['keyword'];
						}
					} elseif ($result['store_id'] == 0) {
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$result['route']][$store['store_id']][$result['language_id']] = $result['keyword'];
							}
						}
					}
				}
					
				if (strpos($result['route'], 'manufacturer_id') === 0) {
					if (isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store']) && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) {
						if ((isset($data['filter']['store_id']) && ($result['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
							$field_elements[$result['route']][$result['store_id']][$result['language_id']] = $result['keyword'];
						}
					} elseif ($result['store_id'] == 0) {
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$result['route']][$store['store_id']][$result['language_id']] = $result['keyword'];
							}
						}
					}
				}
					
				if (strpos($result['route'], 'information_id') === 0) {
					if (isset($field_info['sheet']['information']['field']['url_keyword']['multi_store']) && $field_info['sheet']['information']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['information']['field']['url_keyword']['multi_store_status']) {
						if ((isset($data['filter']['store_id']) && ($result['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
							$field_elements[$result['route']][$result['store_id']][$result['language_id']] = $result['keyword'];
						}
					} elseif ($result['store_id'] == 0) {
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$result['route']][$store['store_id']][$result['language_id']] = $result['keyword'];
							}
						}
					}
				}
					
				if (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $result['route']) && !($custom_page_exception_routes && in_array($result['route'], $custom_page_exception_routes))) {
					if (isset($field_info['sheet']['custom_page']['field']['url_keyword']['multi_store']) && $field_info['sheet']['custom_page']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['custom_page']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['custom_page']['field']['url_keyword']['multi_store_status']) {
						if ((isset($data['filter']['store_id']) && ($result['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
							$field_elements[$result['route']][$result['store_id']][$result['language_id']] = $result['keyword'];
						}
					} elseif ($result['store_id'] == 0) {
						foreach ($stores as $store) {
							if ((isset($data['filter']['store_id']) && ($store['store_id'] == $data['filter']['store_id'])) || !isset($data['filter']['store_id'])) {
								$field_elements[$result['route']][$store['store_id']][$result['language_id']] = $result['keyword'];
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