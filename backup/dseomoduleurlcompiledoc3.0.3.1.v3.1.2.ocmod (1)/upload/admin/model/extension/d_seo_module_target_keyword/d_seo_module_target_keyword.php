<?php
class ModelExtensionDSEOModuleTargetKeywordDSEOModuleTargetKeyword extends Model {
	private $codename = 'd_seo_module';
	
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
	*	Return Target Elements.
	*/	
	public function getTargetElements() {
		$this->load->model('extension/module/' . $this->codename);
		
		$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
		
		$target_elements = array();
		
		$query = $this->db->query("SELECT c.category_id, tk.store_id, tk.language_id, tk.sort_order, tk.keyword FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('category_id=', c.category_id))");
														
		foreach ($query->rows as $result) {
			$route = 'category_id=' . $result['category_id'];			
			$target_elements[$route]['route'] = $route;
			
			if (!isset($target_elements[$route]['target_keyword'])) {
				$target_elements[$route]['target_keyword'] = array();
			}
			
			if ((isset($field_info['sheet']['category']['field']['target_keyword']['multi_store']) && $field_info['sheet']['category']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['category']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['category']['field']['target_keyword']['multi_store_status'])) {
				$target_elements[$route]['target_keyword'][$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
			} elseif ($result['store_id'] == 0) {
				foreach ($stores as $store) {
					$target_elements[$route]['target_keyword'][$store['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
				}
			}
		}
				
		$query = $this->db->query("SELECT p.product_id, tk.store_id, tk.language_id, tk.sort_order, tk.keyword FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('product_id=', p.product_id))");
		
		foreach ($query->rows as $result) {
			$route = 'product_id=' . $result['product_id'];
			$target_elements[$route]['route'] = $route;
			
			if (!isset($target_elements[$route]['target_keyword'])) {
				$target_elements[$route]['target_keyword'] = array();
			}
			
			if ((isset($field_info['sheet']['product']['field']['target_keyword']['multi_store']) && $field_info['sheet']['product']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['product']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['product']['field']['target_keyword']['multi_store_status'])) {
				$target_elements[$route]['target_keyword'][$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
			} elseif ($result['store_id'] == 0) {
				foreach ($stores as $store) {
					$target_elements[$route]['target_keyword'][$store['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
				}
			}
		}
		
		$query = $this->db->query("SELECT m.manufacturer_id, tk.store_id, tk.language_id, tk.sort_order, tk.keyword FROM " . DB_PREFIX . "manufacturer m LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('manufacturer_id=', m.manufacturer_id))");
		
		foreach ($query->rows as $result) {
			$route = 'manufacturer_id=' . $result['manufacturer_id'];
			$target_elements[$route]['route'] = $route;
			
			if (!isset($target_elements[$route]['target_keyword'])) {
				$target_elements[$route]['target_keyword'] = array();
			}
			
			if ((isset($field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store']) && $field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['manufacturer']['field']['target_keyword']['multi_store_status'])) {
				$target_elements[$route]['target_keyword'][$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
			} elseif ($result['store_id'] == 0) {
				foreach ($stores as $store) {
					$target_elements[$route]['target_keyword'][$store['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
				}
			}
		}
		
		$query = $this->db->query("SELECT i.information_id, tk.store_id, tk.language_id, tk.sort_order, tk.keyword FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "d_target_keyword tk ON (tk.route = CONCAT('information_id=', i.information_id))");
		
		foreach ($query->rows as $result) {
			$route = 'information_id=' . $result['information_id'];			
			$target_elements[$route]['route'] = $route;
			
			if (!isset($target_elements[$route]['target_keyword'])) {
				$target_elements[$route]['target_keyword'] = array();
			}
			
			if ((isset($field_info['sheet']['information']['field']['target_keyword']['multi_store']) && $field_info['sheet']['information']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['information']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['information']['field']['target_keyword']['multi_store_status'])) {
				$target_elements[$route]['target_keyword'][$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
			} elseif ($result['store_id'] == 0) {
				foreach ($stores as $store) {
					$target_elements[$route]['target_keyword'][$store['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
				}
			}
		}
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE '%/%'");
		
		foreach ($query->rows as $result) {
			if (!in_array($result['route'], $custom_page_exception_routes)) {
				$target_elements[$result['route']]['route'] = $result['route'];
				
				if (!isset($target_elements[$result['route']]['target_keyword'])) {
					$target_elements[$result['route']]['target_keyword'] = array();
				}
				
				if ((isset($field_info['sheet']['custom_page']['field']['target_keyword']['multi_store']) && $field_info['sheet']['custom_page']['field']['target_keyword']['multi_store'] && isset($field_info['sheet']['custom_page']['field']['target_keyword']['multi_store_status']) && $field_info['sheet']['custom_page']['field']['target_keyword']['multi_store_status'])) {
					$target_elements[$result['route']]['target_keyword'][$result['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
				} elseif ($result['store_id'] == 0) {
					foreach ($stores as $store) {
						$target_elements[$result['route']]['target_keyword'][$store['store_id']][$result['language_id']][$result['sort_order']] = $result['keyword'];
					}
				}
			}
		}
						
		return $target_elements;
	}
}
?>