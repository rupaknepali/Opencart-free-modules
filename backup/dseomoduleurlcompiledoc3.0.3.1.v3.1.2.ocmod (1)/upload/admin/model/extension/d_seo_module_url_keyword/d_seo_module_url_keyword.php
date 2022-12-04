<?php
class ModelExtensionDSEOModuleURLKeywordDSEOModuleURLKeyword extends Model {
	private $codename = 'd_seo_module_url';
	
	/*
	*	Edit URL Element.
	*/
	public function editURLElement($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
		
		if (isset($data['route']) && isset($data['store_id']) && isset($data['language_id']) && isset($data['url_keyword']) && trim($data['url_keyword'])) {
			if ((strpos($data['route'], 'category_id') === 0) || (strpos($data['route'], 'product_id') === 0) || (strpos($data['route'], 'manufacturer_id') === 0) || (strpos($data['route'], 'information_id') === 0) || (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $data['route']) && !in_array($data['route'], $custom_page_exception_routes))) {					
				$url_keyword_store_id = 0;
				
				if (strpos($data['route'], 'category_id') === 0) {
					if (isset($field_info['sheet']['category']['field']['url_keyword']['multi_store']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) {
						$url_keyword_store_id = $data['store_id'];
					} else {
						$url_keyword_store_id = 0;
					}
				}
				
				if (strpos($data['route'], 'product_id') === 0) {
					if (isset($field_info['sheet']['product']['field']['url_keyword']['multi_store']) && $field_info['sheet']['product']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['product']['field']['url_keyword']['multi_store_status']) {
						$url_keyword_store_id = $data['store_id'];
					} else {
						$url_keyword_store_id = 0;
					}
				}
				
				if (strpos($data['route'], 'manufacturer_id') === 0) {
					if (isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store']) && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) {
						$url_keyword_store_id = $data['store_id'];
					} else {
						$url_keyword_store_id = 0;
					}
				}
				
				if (strpos($data['route'], 'information_id') === 0) {
					if (isset($field_info['sheet']['information']['field']['url_keyword']['multi_store']) && $field_info['sheet']['information']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['information']['field']['url_keyword']['multi_store_status']) {
						$url_keyword_store_id = $data['store_id'];
					} else {
						$url_keyword_store_id = 0;
					}
				}
				
				if (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $data['route'])) {
					if (isset($field_info['sheet']['custom_page']['field']['url_keyword']['multi_store']) && $field_info['sheet']['custom_page']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['custom_page']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['custom_page']['field']['url_keyword']['multi_store_status']) {
						$url_keyword_store_id = $data['store_id'];
					} else {
						$url_keyword_store_id = 0;
					}
				}
								
				if (VERSION >= '3.0.0.0') {
					$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = '" . $this->db->escape($data['route']) . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$data['language_id'] . "'");
					$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = '" . $this->db->escape($data['route']) . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$data['language_id'] . "', keyword = '" . $this->db->escape($data['url_keyword']) . "'");
				} else {
					$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = '" . $this->db->escape($data['route']) . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$data['language_id'] . "'");
					$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = '" . $this->db->escape($data['route']) . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$data['language_id'] . "', keyword = '" . $this->db->escape($data['url_keyword']) . "'");
		
					if (($url_keyword_store_id == 0) && ($data['language_id'] == (int)$this->config->get('config_language_id'))) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = '" . $this->db->escape($data['route']) . "'");
						$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = '" . $this->db->escape($data['route']) . "', keyword = '" . $this->db->escape($data['url_keyword']) . "'");
					}
				}
				
				$cache_data = array(
					'route' => $data['route'],
					'store_id' => $data['store_id'],
					'language_id' => $data['language_id']
				);
				
				$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
			}
		}
	}
	
	/*
	*	Return URL Elements.
	*/
	public function getURLElements() {
		$this->load->model('extension/module/' . $this->codename);
		
		$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
		
		$url_elements = array();
				
		if (VERSION >= '3.0.0.0') {
			$query = $this->db->query("SELECT c.category_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('category_id=', c.category_id))");
		} else {
			$query = $this->db->query("SELECT c.category_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('category_id=', c.category_id))");
		}
														
		foreach ($query->rows as $result) {
			$route = 'category_id=' . $result['category_id'];		
			$url_elements[$route]['route'] = $route;
			
			if (!isset($url_elements[$route]['url_keyword'])) {
				$url_elements[$route]['url_keyword'] = array();
			}
			
			if ((isset($field_info['sheet']['category']['field']['url_keyword']['multi_store']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['category']['field']['url_keyword']['multi_store_status'])) {
				$url_elements[$route]['url_keyword'][$result['store_id']][$result['language_id']] = $result['keyword'];
			} elseif ($result['store_id'] == 0) {
				foreach ($stores as $store) {
					$url_elements[$route]['url_keyword'][$store['store_id']][$result['language_id']] = $result['keyword'];
				}
			}
		}
		
		if (VERSION >= '3.0.0.0') {		
			$query = $this->db->query("SELECT p.product_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('product_id=', p.product_id))");
		} else {
			$query = $this->db->query("SELECT p.product_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('product_id=', p.product_id))");
		}
		
		foreach ($query->rows as $result) {
			$route = 'product_id=' . $result['product_id'];			
			$url_elements[$route]['route'] = $route;
			
			if (!isset($url_elements[$route]['url_keyword'])) {
				$url_elements[$route]['url_keyword'] = array();
			}
			
			if ((isset($field_info['sheet']['product']['field']['url_keyword']['multi_store']) && $field_info['sheet']['product']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['product']['field']['url_keyword']['multi_store_status'])) {
				$url_elements[$route]['url_keyword'][$result['store_id']][$result['language_id']] = $result['keyword'];
			} elseif ($result['store_id'] == 0) {
				foreach ($stores as $store) {
					$url_elements[$route]['url_keyword'][$store['store_id']][$result['language_id']] = $result['keyword'];
				}
			}
		}
		
		if (VERSION >= '3.0.0.0') {
			$query = $this->db->query("SELECT m.manufacturer_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "manufacturer m LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('manufacturer_id=', m.manufacturer_id))");
		} else {
			$query = $this->db->query("SELECT m.manufacturer_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "manufacturer m LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('manufacturer_id=', m.manufacturer_id))");
		}
		
		foreach ($query->rows as $result) {
			$route = 'manufacturer_id=' . $result['manufacturer_id'];			
			$url_elements[$route]['route'] = $route;
			
			if (!isset($url_elements[$route]['url_keyword'])) {
				$url_elements[$route]['url_keyword'] = array();
			}
			
			if ((isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store']) && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status'])) {
				$url_elements[$route]['url_keyword'][$result['store_id']][$result['language_id']] = $result['keyword'];
			} elseif ($result['store_id'] == 0) {
				foreach ($stores as $store) {
					$url_elements[$route]['url_keyword'][$store['store_id']][$result['language_id']] = $result['keyword'];
				}
			}
		}
		
		if (VERSION >= '3.0.0.0') {
			$query = $this->db->query("SELECT i.information_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('information_id=', i.information_id))");
		} else {
			$query = $this->db->query("SELECT i.information_id, uk.store_id, uk.language_id, uk.keyword FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('information_id=', i.information_id))");
		}
		
		foreach ($query->rows as $result) {
			$route = 'information_id=' . $result['information_id'];			
			$url_elements[$route]['route'] = $route;
			
			if (!isset($url_elements[$route]['url_keyword'])) {
				$url_elements[$route]['url_keyword'] = array();
			}
			
			if ((isset($field_info['sheet']['information']['field']['url_keyword']['multi_store']) && $field_info['sheet']['information']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['information']['field']['url_keyword']['multi_store_status'])) {
				$url_elements[$route]['url_keyword'][$result['store_id']][$result['language_id']] = $result['keyword'];
			} elseif ($result['store_id'] == 0) {
				foreach ($stores as $store) {
					$url_elements[$route]['url_keyword'][$store['store_id']][$result['language_id']] = $result['keyword'];
				}
			}
		}
		
		if (VERSION >= '3.0.0.0') {
			$query = $this->db->query("SELECT query as route, store_id, language_id, keyword FROM " . DB_PREFIX . "seo_url WHERE query LIKE '%/%'");
		} else {
			$query = $this->db->query("SELECT route, store_id, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE '%/%'");
		}
		
		foreach ($query->rows as $result) {
			if (!in_array($result['route'], $custom_page_exception_routes)) {
				$url_elements[$result['route']]['route'] = $result['route'];
				
				if (!isset($url_elements[$result['route']]['url_keyword'])) {
					$url_elements[$result['route']]['url_keyword'] = array();
				}
				
				if ((isset($field_info['sheet']['custom_page']['field']['url_keyword']['multi_store']) && $field_info['sheet']['custom_page']['field']['url_keyword']['multi_store'] && isset($field_info['sheet']['custom_page']['field']['url_keyword']['multi_store_status']) && $field_info['sheet']['custom_page']['field']['url_keyword']['multi_store_status'])) {
					$url_elements[$result['route']]['url_keyword'][$result['store_id']][$result['language_id']] = $result['keyword'];
				} elseif ($result['store_id'] == 0) {
					foreach ($stores as $store) {
						$url_elements[$result['route']]['url_keyword'][$store['store_id']][$result['language_id']] = $result['keyword'];
					}
				}
			}
		}
		
		return $url_elements;
	}
}
?>