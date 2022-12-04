<?php
class ModelExtensionDSEOModuleURLDSEOModuleURL extends Model {
	private $codename = 'd_seo_module_url';
	
	/*
	*	Generate Fields.
	*/
	public function generateFields($data) {				
		$this->load->model('extension/module/' . $this->codename);
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
				
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_translit.php')) {
			$this->load->model('extension/module/d_translit');
			
			$translit_data = true;
		} else {
			$translit_data = false;
		}
						
		if (isset($data['sheet']['category']['field'])) {											
			$field = array();
			$implode = array();
						
			if (isset($data['sheet']['category']['field']['url_keyword']) && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store_status'])) {
				$field = $data['sheet']['category']['field']['url_keyword'];
				$implode[] = ($data['store_id'] && $field_info['sheet']['category']['field']['url_keyword']['multi_store'] && $field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) ? "uk2.keyword as url_keyword" : "uk.keyword as url_keyword";
			}
			
			if ($data['store_id'] && isset($field_info['sheet']['category']['field']['name']['multi_store']) && $field_info['sheet']['category']['field']['name']['multi_store'] && isset($field_info['sheet']['category']['field']['name']['multi_store_status']) && $field_info['sheet']['category']['field']['name']['multi_store_status']) {
				$implode[] = "md.name";
				$add = "LEFT JOIN " . DB_PREFIX . "d_meta_data md ON (md.route = CONCAT('category_id=', c.category_id) AND md.store_id = '" . (int)$data['store_id'] . "' AND md.language_id = cd.language_id)";
			} else {
				$implode[] = "cd.name";
				$add = "";
			}
			
			$categories = array();
			
			if ($field) {
				$field_template = isset($field['template']) ? $field['template'] : '';
				$field_overwrite = isset($field['overwrite']) ? $field['overwrite'] : 1;
			
				if ($translit_data) {
					$translit_data = array(
						'translit_symbol_status' => isset($field['translit_symbol_status']) ? $field['translit_symbol_status'] : 1,
						'translit_language_symbol_status' => isset($field['translit_language_symbol_status']) ? $field['translit_language_symbol_status'] : 1,
						'transform_language_symbol_id' => isset($field['transform_language_symbol_id']) ? $field['transform_language_symbol_id'] : 0
					);
				}
							
				$field_data = array(
					'field_code' => 'target_keyword',
					'filter' => array('store_id' => $data['store_id'])
				);
			
				$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
								
				if (VERSION >= '3.0.0.0') {
					$query = $this->db->query("SELECT cd.category_id, cd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (cd.category_id = c.category_id) " . $add . " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('category_id=', c.category_id) AND uk.store_id = '0' AND uk.language_id = cd.language_id) LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = CONCAT('category_id=', c.category_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = cd.language_id) GROUP BY c.category_id, cd.language_id");
				} else {
					$query = $this->db->query("SELECT cd.category_id, cd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (cd.category_id = c.category_id) " . $add . " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('category_id=', c.category_id) AND uk.store_id = '0' AND uk.language_id = cd.language_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = CONCAT('category_id=', c.category_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = cd.language_id) GROUP BY c.category_id, cd.language_id");
				}
										
				foreach ($query->rows as $result) {
					$categories[$result['category_id']]['category_id'] = $result['category_id'];
				
					foreach ($result as $field => $value) {
						if (($field != 'category_id') && ($field != 'language_id')) {
							$categories[$result['category_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}		
			
			foreach ($categories as $category) {
				foreach ($languages as $language) {
					if (isset($target_keywords['category_id=' . $category['category_id']][$data['store_id']][$language['language_id']])) {
						$target_keyword = $target_keywords['category_id=' . $category['category_id']][$data['store_id']][$language['language_id']];
					} else {
						$target_keyword = array();
					}
					
					if (is_array($field_template)) {
						$field_new = $field_template[$language['language_id']]; 
					} else {
						$field_new = $field_template; 
					}
					
					$field_new = strtr($field_new, array('[name]' => $category['name'][$language['language_id']]));
					$field_new = $this->replaceTargetKeyword($field_new, $target_keyword);
					
					if ($translit_data) {
						$field_new = $this->model_extension_module_d_translit->translit($field_new, $translit_data);
					}
					
					if (isset($data['sheet']['category']['field']['url_keyword']) && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store_status'])) {
						if ((isset($category['url_keyword'][$language['language_id']]) && ($field_new != $category['url_keyword'][$language['language_id']]) && $field_overwrite) || !isset($category['url_keyword'][$language['language_id']])) {
							if ($data['store_id'] && $field_info['sheet']['category']['field']['url_keyword']['multi_store'] && $field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) {
								$url_keyword_store_id = $data['store_id'];
							} else {
								$url_keyword_store_id = 0;
							}
							
							if (VERSION >= '3.0.0.0') {
								$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$category['category_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
								
								if (trim($field_new)) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'category_id=" . (int)$category['category_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($field_new) . "'");
								}
							} else {
								$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'category_id=" . (int)$category['category_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
									
								if (trim($field_new)) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'category_id=" . (int)$category['category_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($field_new) . "'");
								}	
							
								if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
									$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'category_id=" . (int)$category['category_id'] . "'");
									
									if (trim($field_new)) {
										$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$category['category_id'] . "', keyword = '" . $this->db->escape($field_new) . "'");
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
		
		if (isset($data['sheet']['product']['field'])) {
			$field = array();
			$implode = array();
						
			if (isset($data['sheet']['product']['field']['url_keyword']) && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store_status'])) {
				$field = $data['sheet']['product']['field']['url_keyword'];
				$implode[] = ($data['store_id'] && $field_info['sheet']['product']['field']['url_keyword']['multi_store'] && $field_info['sheet']['product']['field']['url_keyword']['multi_store_status']) ? "uk2.keyword as url_keyword" : "uk.keyword as url_keyword";
			}
			
			if ($data['store_id'] && isset($field_info['sheet']['product']['field']['name']['multi_store']) && $field_info['sheet']['product']['field']['name']['multi_store'] && isset($field_info['sheet']['product']['field']['name']['multi_store_status']) && $field_info['sheet']['product']['field']['name']['multi_store_status']) {
				$implode[] = "md.name";
				$add = "LEFT JOIN " . DB_PREFIX . "d_meta_data md ON (md.route = CONCAT('product_id=', p.product_id) AND md.store_id = '" . (int)$data['store_id'] . "' AND md.language_id = pd.language_id)";
			} else {
				$implode[] = "pd.name";
				$add = "";
			}
			
			$implode[] = "p.model";
			$implode[] = "p.sku";
			$implode[] = "p.upc";
			
			$products = array();
			
			if ($field) {
				$field_template = isset($field['template']) ? $field['template'] : '';
				$field_overwrite = isset($field['overwrite']) ? $field['overwrite'] : 1;
			
				if ($translit_data) {
					$translit_data = array(
						'translit_symbol_status' => isset($field['translit_symbol_status']) ? $field['translit_symbol_status'] : 1,
						'translit_language_symbol_status' => isset($field['translit_language_symbol_status']) ? $field['translit_language_symbol_status'] : 1,
						'transform_language_symbol_id' => isset($field['transform_language_symbol_id']) ? $field['transform_language_symbol_id'] : 0
					);
				}
				
				$field_data = array(
					'field_code' => 'target_keyword',
					'filter' => array('store_id' => $data['store_id'])
				);
			
				$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
								
				if (VERSION >= '3.0.0.0') {
					$query = $this->db->query("SELECT pd.product_id, pd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = p.product_id) " . $add . " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('product_id=', p.product_id) AND uk.store_id = '0' AND uk.language_id = pd.language_id) LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = CONCAT('product_id=', p.product_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = pd.language_id) GROUP BY p.product_id, pd.language_id");
				} else {
					$query = $this->db->query("SELECT pd.product_id, pd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = p.product_id) " . $add . " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('product_id=', p.product_id) AND uk.store_id = '0' AND uk.language_id = pd.language_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = CONCAT('product_id=', p.product_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = pd.language_id) GROUP BY p.product_id, pd.language_id");
				}
						
				foreach ($query->rows as $result) {
					$products[$result['product_id']]['product_id'] = $result['product_id'];
				
					foreach ($result as $field => $value) {
						if (($field != 'product_id') && ($field != 'language_id')) {
							$products[$result['product_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}
						
			foreach ($products as $product) {
				foreach ($languages as $language) {					
					if (isset($target_keywords['product_id=' . $product['product_id']][$data['store_id']][$language['language_id']])) {
						$target_keyword = $target_keywords['product_id=' . $product['product_id']][$data['store_id']][$language['language_id']];
					} else {
						$target_keyword = array();
					}
					
					if (is_array($field_template)) {
						$field_new = $field_template[$language['language_id']]; 
					} else {
						$field_new = $field_template; 
					}
										
					$field_new = strtr($field_new, array(
						'[name]' => $product['name'][$language['language_id']], 
						'[model]' => $product['model'][$language['language_id']],
						'[sku]' => $product['sku'][$language['language_id']],
						'[upc]' => $product['upc'][$language['language_id']]
					));
					$field_new = $this->replaceTargetKeyword($field_new, $target_keyword);
					
					if ($translit_data) {
						$field_new = $this->model_extension_module_d_translit->translit($field_new, $translit_data);
					}
										
					if (isset($data['sheet']['product']['field']['url_keyword']) && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store_status'])) {
						if ((isset($product['url_keyword'][$language['language_id']]) && ($field_new != $product['url_keyword'][$language['language_id']]) && $field_overwrite) || !isset($product['url_keyword'][$language['language_id']])) {
							if ($data['store_id'] && $field_info['sheet']['product']['field']['url_keyword']['multi_store'] && $field_info['sheet']['product']['field']['url_keyword']['multi_store_status']) {
								$url_keyword_store_id = $data['store_id'];
							} else {
								$url_keyword_store_id = 0;
							}
							
							if (VERSION >= '3.0.0.0') {
								$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product['product_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
								
								if (trim($field_new)) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'product_id=" . (int)$product['product_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($field_new) . "'");
								}
							} else {
								$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'product_id=" . (int)$product['product_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
									
								if (trim($field_new)) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'product_id=" . (int)$product['product_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($field_new) . "'");
								}	
							
								if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
									$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$product['product_id'] . "'");
									
									if (trim($field_new)) {
										$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int)$product['product_id'] . "', keyword = '" . $this->db->escape($field_new) . "'");
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
		
		if (isset($data['sheet']['manufacturer']['field'])) {
			$field = array();
			$implode = array();
						
			if (isset($data['sheet']['manufacturer']['field']['url_keyword']) && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status'])) {
				$field = $data['sheet']['manufacturer']['field']['url_keyword'];
				$implode[] = ($data['store_id'] && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store'] && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) ? "uk2.keyword as url_keyword" : "uk.keyword as url_keyword";;
			}
			
			if ($data['store_id'] && isset($field_info['sheet']['manufacturer']['field']['name']['multi_language']) && isset($field_info['sheet']['manufacturer']['field']['name']['multi_store']) && $field_info['sheet']['manufacturer']['field']['name']['multi_store'] && isset($field_info['sheet']['manufacturer']['field']['name']['multi_store_status']) && $field_info['sheet']['manufacturer']['field']['name']['multi_store_status']) {
				$implode[] = "md.name";
				$add = "LEFT JOIN " . DB_PREFIX . "d_meta_data md ON (md.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND md.store_id = '" . (int)$data['store_id'] . "' AND md.language_id = l.language_id)";
			} else {
				$implode[] = "m.name";
				$add = "";
			}
									
			$manufacturers = array();

			if ($field) {
				$field_template = isset($field['template']) ? $field['template'] : '';
				$field_overwrite = isset($field['overwrite']) ? $field['overwrite'] : 1;
			
				if ($translit_data) {
					$translit_data = array(
						'translit_symbol_status' => isset($field['translit_symbol_status']) ? $field['translit_symbol_status'] : 1,
						'translit_language_symbol_status' => isset($field['translit_language_symbol_status']) ? $field['translit_language_symbol_status'] : 1,
						'transform_language_symbol_id' => isset($field['transform_language_symbol_id']) ? $field['transform_language_symbol_id'] : 0
					);
				}
				
				$field_data = array(
					'field_code' => 'target_keyword',
					'filter' => array('store_id' => $data['store_id'])
				);
			
				$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
												
				if (VERSION >= '3.0.0.0') {
					$query = $this->db->query("SELECT m.manufacturer_id, l.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "manufacturer m CROSS JOIN " . DB_PREFIX . "language l " . $add . " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk.store_id = '0' AND uk.language_id = l.language_id) LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = l.language_id) GROUP BY m.manufacturer_id, l.language_id");
				} else {
					$query = $this->db->query("SELECT m.manufacturer_id, l.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "manufacturer m CROSS JOIN " . DB_PREFIX . "language l " . $add . " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk.store_id = '0' AND uk.language_id = l.language_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = l.language_id) GROUP BY m.manufacturer_id, l.language_id");
				}
						
				foreach ($query->rows as $result) {
					$manufacturers[$result['manufacturer_id']]['manufacturer_id'] = $result['manufacturer_id'];
	
					foreach ($result as $field => $value) {
						if (($field != 'manufacturer_id') && ($field != 'language_id')) {
							foreach ($languages as $language) {
								$manufacturers[$result['manufacturer_id']][$field][$language['language_id']] = $value;
							}
						}
					}
				}
			}

			foreach ($manufacturers as $manufacturer) {
				foreach ($languages as $language) {
					if (isset($target_keywords['manufacturer_id=' . $manufacturer['manufacturer_id']][$data['store_id']][$language['language_id']])) {
						$target_keyword = $target_keywords['manufacturer_id=' . $manufacturer['manufacturer_id']][$data['store_id']][$language['language_id']];
					} else {
						$target_keyword = array();
					}
					
					if (is_array($field_template)) {
						$field_new = $field_template[$language['language_id']]; 
					} else {
						$field_new = $field_template; 
					}
										
					$field_new = strtr($field_new, array('[name]' => $manufacturer['name'][$language['language_id']]));
					$field_new = $this->replaceTargetKeyword($field_new, $target_keyword);
					
					if ($translit_data) {
						$field_new = $this->model_extension_module_d_translit->translit($field_new, $translit_data);
					}
					
					if (isset($data['sheet']['manufacturer']['field']['url_keyword']) && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status'])) {
						if ((isset($manufacturer['url_keyword'][$language['language_id']]) && ($field_new != $manufacturer['url_keyword'][$language['language_id']]) && $field_overwrite) || !isset($manufacturer['url_keyword'][$language['language_id']])) {
							if ($data['store_id'] && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store'] && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) {
								$url_keyword_store_id = $data['store_id'];
							} else {
								$url_keyword_store_id = 0;
							}
							
							if (VERSION >= '3.0.0.0') {
								$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
								
								if (trim($field_new)) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($field_new) . "'");
								}
							} else {
								$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
									
								if (trim($field_new)) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($field_new) . "'");
								}	
							
								if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
									$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "'");
									
									if (trim($field_new)) {
										$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "', keyword = '" . $this->db->escape($field_new) . "'");
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
		
		if (isset($data['sheet']['information']['field'])) {
			$field = array();
			$implode = array();
						
			if (isset($data['sheet']['information']['field']['url_keyword']) && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store_status'])) {
				$field = $data['sheet']['information']['field']['url_keyword'];
				$implode[] = ($data['store_id'] && $field_info['sheet']['information']['field']['url_keyword']['multi_store'] && $field_info['sheet']['information']['field']['url_keyword']['multi_store_status']) ? "uk2.keyword as url_keyword" : "uk.keyword as url_keyword";
			}
			
			if ($data['store_id'] && isset($field_info['sheet']['information']['field']['title']['multi_store']) && $field_info['sheet']['information']['field']['title']['multi_store'] && isset($field_info['sheet']['information']['field']['title']['multi_store_status']) && $field_info['sheet']['information']['field']['title']['multi_store_status']) {
				$implode[] = "md.title";
				$add = "LEFT JOIN " . DB_PREFIX . "d_meta_data md ON (md.route = CONCAT('information_id=', i.information_id) AND md.store_id = '" . (int)$data['store_id'] . "' AND md.language_id = id.language_id)";
			} else {
				$implode[] = "id.title";
				$add = "";
			}
			
			$informations = array();
			
			if ($field) {
				$field_template = isset($field['template']) ? $field['template'] : '';
				$field_overwrite = isset($field['overwrite']) ? $field['overwrite'] : 1;
			
				if ($translit_data) {
					$translit_data = array(
						'translit_symbol_status' => isset($field['translit_symbol_status']) ? $field['translit_symbol_status'] : 1,
						'translit_language_symbol_status' => isset($field['translit_language_symbol_status']) ? $field['translit_language_symbol_status'] : 1,
						'transform_language_symbol_id' => isset($field['transform_language_symbol_id']) ? $field['transform_language_symbol_id'] : 0
					);
				}
				
				$field_data = array(
					'field_code' => 'target_keyword',
					'filter' => array('store_id' => $data['store_id'])
				);
			
				$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
								
				if (VERSION >= '3.0.0.0') {
					$query = $this->db->query("SELECT id.information_id, id.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (id.information_id = i.information_id) " . $add . " LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('information_id=', i.information_id) AND uk.store_id = '0' AND uk.language_id = id.language_id) LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = CONCAT('information_id=', i.information_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = id.language_id) GROUP BY i.information_id, id.language_id");
				} else {
					$query = $this->db->query("SELECT id.information_id, id.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (id.information_id = i.information_id) " . $add . " LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('information_id=', i.information_id) AND uk.store_id = '0' AND uk.language_id = id.language_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = CONCAT('information_id=', i.information_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = id.language_id) GROUP BY i.information_id, id.language_id");
				}
						
				foreach ($query->rows as $result) {
					$informations[$result['information_id']]['information_id'] = $result['information_id'];
				
					foreach ($result as $field => $value) {
						if (($field != 'information_id') && ($field != 'language_id')) {
							$informations[$result['information_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}
			
			foreach ($informations as $information) {
				foreach ($languages as $language) {
					if (isset($target_keywords['information_id=' . $information['information_id']][$data['store_id']][$language['language_id']])) {
						$target_keyword = $target_keywords['information_id=' . $information['information_id']][$data['store_id']][$language['language_id']];
					} else {
						$target_keyword = array();
					}
					
					if (is_array($field_template)) {
						$field_new = $field_template[$language['language_id']]; 
					} else {
						$field_new = $field_template; 
					}
										
					$field_new = strtr($field_new, array('[title]' => $information['title'][$language['language_id']]));
					$field_new = $this->replaceTargetKeyword($field_new, $target_keyword);
					
					if ($translit_data) {
						$field_new = $this->model_extension_module_d_translit->translit($field_new, $translit_data);
					}
					
					if (isset($data['sheet']['information']['field']['url_keyword']) && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store_status'])) {
						if ((isset($information['url_keyword'][$language['language_id']]) && ($field_new != $information['url_keyword'][$language['language_id']]) && $field_overwrite) || !isset($information['url_keyword'][$language['language_id']])) {
							if ($data['store_id'] && $field_info['sheet']['information']['field']['url_keyword']['multi_store'] && $field_info['sheet']['information']['field']['url_keyword']['multi_store_status']) {
								$url_keyword_store_id = $data['store_id'];
							} else {
								$url_keyword_store_id = 0;
							}
							
							if (VERSION >= '3.0.0.0') {
								$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'information_id=" . (int)$information['information_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
								
								if (trim($field_new)) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'information_id=" . (int)$information['information_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($field_new) . "'");
								}
							} else {
								$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'information_id=" . (int)$information['information_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
									
								if (trim($field_new)) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'information_id=" . (int)$information['information_id'] . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($field_new) . "'");
								}	
							
								if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
									$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'information_id=" . (int)$information['information_id'] . "'");
									
									if (trim($field_new)) {
										$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'information_id=" . (int)$information['information_id'] . "', keyword = '" . $this->db->escape($field_new) . "'");
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
	
	/*
	*	Clear Fields.
	*/
	public function clearFields($data) {				
		$this->load->model('extension/module/' . $this->codename);
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
										
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
								
		if (isset($data['sheet']['category'])) {
			$field = array();
			$implode = array();
						
			if (isset($data['sheet']['category']['field']['url_keyword']) && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store_status'])) {
				$field = $data['sheet']['category']['field']['url_keyword'];
				$implode[] = ($data['store_id'] && $field_info['sheet']['category']['field']['url_keyword']['multi_store'] && $field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) ? "uk2.keyword as url_keyword" : "uk.keyword as url_keyword";
			}
						
			$categories = array();
			
			if ($field) {				
				if (VERSION >= '3.0.0.0') {
					$query = $this->db->query("SELECT cd.category_id, cd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (cd.category_id = c.category_id) LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('category_id=', c.category_id) AND uk.store_id = '0' AND uk.language_id = cd.language_id) LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = CONCAT('category_id=', c.category_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = cd.language_id) GROUP BY c.category_id, cd.language_id");
				} else {
					$query = $this->db->query("SELECT cd.category_id, cd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "category c LEFT JOIN " . DB_PREFIX . "category_description cd ON (cd.category_id = c.category_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('category_id=', c.category_id) AND uk.store_id = '0' AND uk.language_id = cd.language_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = CONCAT('category_id=', c.category_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = cd.language_id) GROUP BY c.category_id, cd.language_id");
				}
										
				foreach ($query->rows as $result) {
					$categories[$result['category_id']]['category_id'] = $result['category_id'];
				
					foreach ($result as $field => $value) {
						if (($field != 'category_id') && ($field != 'language_id')) {
							$categories[$result['category_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}		
			
			foreach ($categories as $category) {
				foreach ($languages as $language) {					
					if (isset($data['sheet']['category']['field']['url_keyword']) && isset($category['url_keyword'][$language['language_id']]) && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['category']['field']['url_keyword']['multi_store_status'])) {
						if ($data['store_id'] && $field_info['sheet']['category']['field']['url_keyword']['multi_store'] && $field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
							
						if (VERSION >= '3.0.0.0') {
							$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$category['category_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
						} else {
							$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'category_id=" . (int)$category['category_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
		
							if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
								$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'category_id=" . (int)$category['category_id'] . "'");	
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
		
		if (isset($data['sheet']['product'])) {
			$field = array();
			$implode = array();
						
			if (isset($data['sheet']['product']['field']['url_keyword']) && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store_status'])) {
				$field = $data['sheet']['product']['field']['url_keyword'];
				$implode[] = ($data['store_id'] && $field_info['sheet']['product']['field']['url_keyword']['multi_store'] && $field_info['sheet']['product']['field']['url_keyword']['multi_store_status']) ? "uk2.keyword as url_keyword" : "uk.keyword as url_keyword";
			}
									
			$products = array();
			
			if ($field) {				
				if (VERSION >= '3.0.0.0') {
					$query = $this->db->query("SELECT pd.product_id, pd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('product_id=', p.product_id) AND uk.store_id = '0' AND uk.language_id = pd.language_id) LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = CONCAT('product_id=', p.product_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = pd.language_id) GROUP BY p.product_id, pd.language_id");
				} else {
					$query = $this->db->query("SELECT pd.product_id, pd.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (pd.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('product_id=', p.product_id) AND uk.store_id = '0' AND uk.language_id = pd.language_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = CONCAT('product_id=', p.product_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = pd.language_id) GROUP BY p.product_id, pd.language_id");
				}
						
				foreach ($query->rows as $result) {
					$products[$result['product_id']]['product_id'] = $result['product_id'];
				
					foreach ($result as $field => $value) {
						if (($field != 'product_id') && ($field != 'language_id')) {
							$products[$result['product_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}	
			
			foreach ($products as $product) {
				foreach ($languages as $language) {
					if (isset($data['sheet']['product']['field']['url_keyword']) && isset($product['url_keyword'][$language['language_id']]) && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['product']['field']['url_keyword']['multi_store_status'])) {
						if ($data['store_id'] && $field_info['sheet']['product']['field']['url_keyword']['multi_store'] && $field_info['sheet']['product']['field']['url_keyword']['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
							
						if (VERSION >= '3.0.0.0') {
							$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product['product_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
						} else {
							$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'product_id=" . (int)$product['product_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
		
							if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
								$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$product['product_id'] . "'");	
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
		
		if (isset($data['sheet']['manufacturer'])) {
			$field = array();
			$implode = array();
						
			if (isset($data['sheet']['manufacturer']['field']['url_keyword']) && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status'])) {
				$field = $data['sheet']['manufacturer']['field']['url_keyword'];
				$implode[] = ($data['store_id'] && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store'] && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) ? "uk2.keyword as url_keyword" : "uk.keyword as url_keyword";;
			}
												
			$manufacturers = array();

			if ($field) {				
				if (VERSION >= '3.0.0.0') {
					$query = $this->db->query("SELECT m.manufacturer_id, l.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "manufacturer m CROSS JOIN " . DB_PREFIX . "language l LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk.store_id = '0' AND uk.language_id = l.language_id) LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = l.language_id) GROUP BY m.manufacturer_id, l.language_id");
				} else {
					$query = $this->db->query("SELECT m.manufacturer_id, l.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "manufacturer m CROSS JOIN " . DB_PREFIX . "language l LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk.store_id = '0' AND uk.language_id = l.language_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = CONCAT('manufacturer_id=', m.manufacturer_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = l.language_id) GROUP BY m.manufacturer_id, l.language_id");
				}
						
				foreach ($query->rows as $result) {
					$manufacturers[$result['manufacturer_id']]['manufacturer_id'] = $result['manufacturer_id'];
	
					foreach ($result as $field => $value) {
						if (($field != 'manufacturer_id') && ($field != 'language_id')) {
							foreach ($languages as $language) {
								$manufacturers[$result['manufacturer_id']][$field][$language['language_id']] = $value;
							}
						}
					}
				}
			}
			
			foreach ($manufacturers as $manufacturer) {
				foreach ($languages as $language) {
					if (isset($data['sheet']['manufacturer']['field']['url_keyword']) && isset($manufacturer['url_keyword'][$language['language_id']]) && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status'])) {
						if ($data['store_id'] && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store'] && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
							
						if (VERSION >= '3.0.0.0') {
							$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
						} else {
							$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
		
							if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
								$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'manufacturer_id=" . (int)$manufacturer['manufacturer_id'] . "'");	
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
		
		if (isset($data['sheet']['information'])) {
			if (isset($data['sheet']['information']['field']['url_keyword']) && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store_status'])) {
				$field = $data['sheet']['information']['field']['url_keyword'];
				$implode[] = ($data['store_id'] && $field_info['sheet']['information']['field']['url_keyword']['multi_store'] && $field_info['sheet']['information']['field']['url_keyword']['multi_store_status']) ? "uk2.keyword as url_keyword" : "uk.keyword as url_keyword";
			}
						
			$informations = array();
			
			if ($field) {				
				if (VERSION >= '3.0.0.0') {
					$query = $this->db->query("SELECT id.information_id, id.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (id.information_id = i.information_id) LEFT JOIN " . DB_PREFIX . "seo_url uk ON (uk.query = CONCAT('information_id=', i.information_id) AND uk.store_id = '0' AND uk.language_id = id.language_id) LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = CONCAT('information_id=', i.information_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = id.language_id) GROUP BY i.information_id, id.language_id");
				} else {
					$query = $this->db->query("SELECT id.information_id, id.language_id, " . implode(', ', $implode) . " FROM " . DB_PREFIX . "information i LEFT JOIN " . DB_PREFIX . "information_description id ON (id.information_id = i.information_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk ON (uk.route = CONCAT('information_id=', i.information_id) AND uk.store_id = '0' AND uk.language_id = id.language_id) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = CONCAT('information_id=', i.information_id) AND uk2.store_id = '" . (int)$data['store_id'] . "' AND uk2.language_id = id.language_id) GROUP BY i.information_id, id.language_id");
				}
						
				foreach ($query->rows as $result) {
					$informations[$result['information_id']]['information_id'] = $result['information_id'];
				
					foreach ($result as $field => $value) {
						if (($field != 'information_id') && ($field != 'language_id')) {
							$informations[$result['information_id']][$field][$result['language_id']] = $value;
						}
					}
				}
			}
			
			foreach ($informations as $information) {
				foreach ($languages as $language) {
					if (isset($data['sheet']['information']['field']['url_keyword']) && isset($information['url_keyword'][$language['language_id']]) && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store']) && isset($field_info['sheet']['information']['field']['url_keyword']['multi_store_status'])) {
						if ($data['store_id'] && $field_info['sheet']['information']['field']['url_keyword']['multi_store'] && $field_info['sheet']['information']['field']['url_keyword']['multi_store_status']) {
							$url_keyword_store_id = $data['store_id'];
						} else {
							$url_keyword_store_id = 0;
						}
							
						if (VERSION >= '3.0.0.0') {
							$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'information_id=" . (int)$information['information_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
						} else {
							$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'information_id=" . (int)$information['information_id'] . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
		
							if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
								$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'information_id=" . (int)$information['information_id'] . "'");	
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
	
	/*
	*	Return URL Elements.
	*/	
	public function getURLElements($data) {
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
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
		
		$url_elements = array();	
						
		if ($data['sheet_code'] == 'category') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
				$url_keyword_store_id = $data['store_id'];
			} else {
				$url_keyword_store_id = 0;
			}
			
			if (VERSION >= '3.0.0.0') {
				$sql = "SELECT uk.query as route, uk.language_id, uk.keyword, c.category_id FROM " . DB_PREFIX . "seo_url uk LEFT JOIN " . DB_PREFIX . "category c ON (CONCAT('category_id=', c.category_id) = uk.query) LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = uk.query AND uk2.store_id = '" . (int)$url_keyword_store_id . "') WHERE uk.query LIKE 'category_id=%' AND uk.store_id = '" . (int)$url_keyword_store_id . "'";
			} else {
				$sql = "SELECT uk.route, uk.language_id, uk.keyword, c.category_id FROM " . DB_PREFIX . "d_url_keyword uk LEFT JOIN " . DB_PREFIX . "category c ON (CONCAT('category_id=', c.category_id) = uk.route) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = uk.route AND uk2.store_id = '" . (int)$url_keyword_store_id . "') WHERE uk.route LIKE 'category_id=%' AND uk.store_id = '" . (int)$url_keyword_store_id . "'";
			}

			$implode = array();
			$implode_language = array();
						
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'route') {
						if (VERSION >= '3.0.0.0') {
							$implode[] = "uk2.query = '" . $this->db->escape($filter) . "'";
						} else {
							$implode[] = "uk2.route = '" . $this->db->escape($filter) . "'";
						}
					}
										
					if ($field_code == 'url_keyword') {
						foreach ($filter as $language_id => $value) {
							if (!empty($value)) {
								$implode_language[] = "(uk2.language_id = '" . (int)$language_id . "' AND uk2.keyword LIKE '%" . $this->db->escape($value) . "%')";
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

			if (VERSION >= '3.0.0.0') {
				$sql .= " GROUP BY uk.query, uk.language_id";
			} else {
				$sql .= " GROUP BY uk.route, uk.language_id";
			}
			
			$query = $this->db->query($sql);
						
			foreach ($query->rows as $result) {
				$url_elements[$result['route']]['route'] = $result['route'];
				$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
					
				if ($result['category_id']) {
					$url_elements[$result['route']]['link'] = $this->url->link('catalog/category/edit', $url_token . '&category_id=' . $result['category_id'], true);
				}
			}
					
			return $url_elements;
		}
				
		if ($data['sheet_code'] == 'product') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
				$url_keyword_store_id = $data['store_id'];
			} else {
				$url_keyword_store_id = 0;
			}
						
			if (VERSION >= '3.0.0.0') {
				$sql = "SELECT uk.query as route, uk.language_id, uk.keyword, p.product_id FROM " . DB_PREFIX . "seo_url uk LEFT JOIN " . DB_PREFIX . "product p ON (CONCAT('product_id=', p.product_id) = uk.query) LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = uk.query AND uk2.store_id = '" . (int)$url_keyword_store_id . "') WHERE uk.query LIKE 'product_id=%' AND uk.store_id = '" . (int)$url_keyword_store_id . "'";
			} else {
				$sql = "SELECT uk.route, uk.language_id, uk.keyword, p.product_id FROM " . DB_PREFIX . "d_url_keyword uk LEFT JOIN " . DB_PREFIX . "product p ON (CONCAT('product_id=', p.product_id) = uk.route) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = uk.route AND uk2.store_id = '" . (int)$url_keyword_store_id . "') WHERE uk.route LIKE 'product_id=%' AND uk.store_id = '" . (int)$url_keyword_store_id . "'";
			}
			
			$implode = array();
			$implode_language = array();
						
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'route') {
						if (VERSION >= '3.0.0.0') {
							$implode[] = "uk2.query = '" . $this->db->escape($filter) . "'";
						} else {
							$implode[] = "uk2.route = '" . $this->db->escape($filter) . "'";
						}
					}
										
					if ($field_code == 'url_keyword') {
						foreach ($filter as $language_id => $value) {
							if (!empty($value)) {
								$implode_language[] = "(uk2.language_id = '" . (int)$language_id . "' AND uk2.keyword LIKE '%" . $this->db->escape($value) . "%')";
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

			if (VERSION >= '3.0.0.0') {
				$sql .= " GROUP BY uk.query, uk.language_id";
			} else {
				$sql .= " GROUP BY uk.route, uk.language_id";
			}
			
			$query = $this->db->query($sql);
					
			foreach ($query->rows as $result) {
				$url_elements[$result['route']]['route'] = $result['route'];
				$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
					
				if ($result['product_id']) {
					$url_elements[$result['route']]['link'] = $this->url->link('catalog/product/edit', $url_token . '&product_id=' . $result['product_id'], true);
				}
			}
					
			return $url_elements;	
		}
		
		if ($data['sheet_code'] == 'manufacturer') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
				$url_keyword_store_id = $data['store_id'];
			} else {
				$url_keyword_store_id = 0;
			}
						
			if (VERSION >= '3.0.0.0') {
				$sql = "SELECT uk.query as route, uk.language_id, uk.keyword, m.manufacturer_id FROM " . DB_PREFIX . "seo_url uk LEFT JOIN " . DB_PREFIX . "manufacturer m ON (CONCAT('manufacturer_id=', m.manufacturer_id) = uk.query) LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = uk.query AND uk2.store_id = '" . (int)$url_keyword_store_id . "') WHERE uk.query LIKE 'manufacturer_id=%' AND uk.store_id = '" . (int)$url_keyword_store_id . "'";
			} else {
				$sql = "SELECT uk.route, uk.language_id, uk.keyword, m.manufacturer_id FROM " . DB_PREFIX . "d_url_keyword uk LEFT JOIN " . DB_PREFIX . "manufacturer m ON (CONCAT('manufacturer_id=', m.manufacturer_id) = uk.route) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = uk.route AND uk2.store_id = '" . (int)$url_keyword_store_id . "') WHERE uk.route LIKE 'manufacturer_id=%' AND uk.store_id = '" . (int)$url_keyword_store_id . "'";
			}
			
			$implode = array();
			$implode_language = array();
						
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'route') {
						if (VERSION >= '3.0.0.0') {
							$implode[] = "uk2.query = '" . $this->db->escape($filter) . "'";
						} else {
							$implode[] = "uk2.route = '" . $this->db->escape($filter) . "'";
						}
					}
										
					if ($field_code == 'url_keyword') {
						foreach ($filter as $language_id => $value) {
							if (!empty($value)) {
								$implode_language[] = "(uk2.language_id = '" . (int)$language_id . "' AND uk2.keyword LIKE '%" . $this->db->escape($value) . "%')";
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

			if (VERSION >= '3.0.0.0') {
				$sql .= " GROUP BY uk.query, uk.language_id";
			} else {
				$sql .= " GROUP BY uk.route, uk.language_id";
			}
			
			$query = $this->db->query($sql);
						
			foreach ($query->rows as $result) {
				$url_elements[$result['route']]['route'] = $result['route'];
				$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
				
				if ($result['manufacturer_id']) {
					$url_elements[$result['route']]['link'] = $this->url->link('catalog/manufacturer/edit', $url_token . '&manufacturer_id=' . $result['manufacturer_id'], true);
				}
			}
					
			return $url_elements;	
		}
		
		if ($data['sheet_code'] == 'information') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
				$url_keyword_store_id = $data['store_id'];
			} else {
				$url_keyword_store_id = 0;
			}
						
			if (VERSION >= '3.0.0.0') {
				$sql = "SELECT uk.query as route, uk.language_id, uk.keyword, i.information_id FROM " . DB_PREFIX . "seo_url uk LEFT JOIN " . DB_PREFIX . "information i ON (CONCAT('information_id=', i.information_id) = uk.query) LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = uk.query AND uk2.store_id = '" . (int)$url_keyword_store_id . "') WHERE uk.query LIKE 'information_id=%' AND uk.store_id = '" . (int)$url_keyword_store_id . "'";
			} else {
				$sql = "SELECT uk.route, uk.language_id, uk.keyword, i.information_id FROM " . DB_PREFIX . "d_url_keyword uk LEFT JOIN " . DB_PREFIX . "information i ON (CONCAT('information_id=', i.information_id) = uk.route) LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = uk.route AND uk2.store_id = '" . (int)$url_keyword_store_id . "') WHERE uk.route LIKE 'information_id=%' AND uk.store_id = '" . (int)$url_keyword_store_id . "'";
			}
			
			$implode = array();
			$implode_language = array();
						
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'route') {
						if (VERSION >= '3.0.0.0') {
							$implode[] = "uk2.query = '" . $this->db->escape($filter) . "'";
						} else {
							$implode[] = "uk2.route = '" . $this->db->escape($filter) . "'";
						}
					}
										
					if ($field_code == 'url_keyword') {
						foreach ($filter as $language_id => $value) {
							if (!empty($value)) {
								$implode_language[] = "(uk2.language_id = '" . (int)$language_id . "' AND uk2.keyword LIKE '%" . $this->db->escape($value) . "%')";
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

			if (VERSION >= '3.0.0.0') {
				$sql .= " GROUP BY uk.query, uk.language_id";
			} else {
				$sql .= " GROUP BY uk.route, uk.language_id";
			}
			
			$query = $this->db->query($sql);
						
			foreach ($query->rows as $result) {
				$url_elements[$result['route']]['route'] = $result['route'];
				$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
				
				if ($result['information_id']) {
					$url_elements[$result['route']]['link'] = $this->url->link('catalog/information/edit', $url_token . '&information_id=' . $result['information_id'], true);
				}
			}
					
			return $url_elements;	
		}
		
		if ($data['sheet_code'] == 'custom_page') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
				$url_keyword_store_id = $data['store_id'];
			} else {
				$url_keyword_store_id = 0;
			}
			
			if (VERSION >= '3.0.0.0') {
				$sql = "SELECT uk.query as route, uk.language_id, uk.keyword FROM " . DB_PREFIX . "seo_url uk LEFT JOIN " . DB_PREFIX . "seo_url uk2 ON (uk2.query = uk.query AND uk2.store_id = '" . (int)$url_keyword_store_id . "') WHERE uk.query LIKE '%/%' AND uk.store_id = '" . (int)$url_keyword_store_id . "'";
			} else {
				$sql = "SELECT uk.route, uk.language_id, uk.keyword FROM " . DB_PREFIX . "d_url_keyword uk LEFT JOIN " . DB_PREFIX . "d_url_keyword uk2 ON (uk2.route = uk.route AND uk2.store_id = '" . (int)$url_keyword_store_id . "') WHERE uk.route LIKE '%/%' AND uk.store_id = '" . (int)$url_keyword_store_id . "'";
			}
			
			$implode = array();
			$implode_language = array();
						
			foreach ($data['filter'] as $field_code => $filter) {
				if (!empty($filter)) {
					if ($field_code == 'route') {
						if (VERSION >= '3.0.0.0') {
							$implode[] = "uk2.query = '" . $this->db->escape($filter) . "'";
						} else {
							$implode[] = "uk2.route = '" . $this->db->escape($filter) . "'";
						}
					}
										
					if ($field_code == 'url_keyword') {
						foreach ($filter as $language_id => $value) {
							if (!empty($value)) {
								$implode_language[] = "(uk2.language_id = '" . (int)$language_id . "' AND uk2.keyword LIKE '%" . $this->db->escape($value) . "%')";
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

			if (VERSION >= '3.0.0.0') {
				$sql .= " GROUP BY uk.query, uk.language_id";
			} else {
				$sql .= " GROUP BY uk.route, uk.language_id";
			}
			
			$query = $this->db->query($sql);
						
			foreach ($query->rows as $result) {
				if (!in_array($result['route'], $custom_page_exception_routes)) {
					$url_elements[$result['route']]['route'] = $result['route'];
					$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
				}
			}
									
			return $url_elements;
		}
	}
					
	/*
	*	Add URL Element.
	*/
	public function addURLElement($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
		
		if (isset($data['route']) && isset($data['store_id']) && isset($data['url_keyword'])) {
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
				
				foreach ($data['url_keyword'] as $language_id => $url_keyword) {
					if (trim($url_keyword)) {
						if (VERSION >= '3.0.0.0') {
							$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = '" . $this->db->escape($data['route']) . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language_id . "'");
							$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = '" . $this->db->escape($data['route']) . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language_id . "', keyword = '" . $this->db->escape($url_keyword) . "'");
						} else {
							$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = '" . $this->db->escape($data['route']) . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language_id . "'");
							$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = '" . $this->db->escape($data['route']) . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language_id . "', keyword = '" . $this->db->escape($url_keyword) . "'");
					
							if (($url_keyword_store_id == 0) && ($language_id == (int)$this->config->get('config_language_id'))) {
								$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = '" . $this->db->escape($data['route']) . "'");
								$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = '" . $this->db->escape($data['route']) . "', keyword = '" . $this->db->escape($url_keyword) . "'");
							}
						}
					}
				}
				
				$cache_data = array(
					'route' => $data['route'],
					'store_id' => $data['store_id']
				);
						
				$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
			}
		}
	}
	
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
						
				if (trim($data['url_keyword'])) {
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
	*	Delete URL Element.
	*/
	public function deleteURLElement($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
		
		if (isset($data['route']) && isset($data['store_id'])) {
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
					$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = '" . $this->db->escape($data['route']) . "' AND store_id = '" . (int)$url_keyword_store_id . "'");
				} else {
					$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = '" . $this->db->escape($data['route']) . "' AND store_id = '" . (int)$url_keyword_store_id . "'");
			
					if ($url_keyword_store_id == 0) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = '" . $this->db->escape($data['route']) . "'");
					}
				}
				
				$cache_data = array(
					'route' => $data['route'],
					'store_id' => $data['store_id']
				);
				
				$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
			}
		}
	}
	
	/*
	*	Return Export URL Elements.
	*/
	public function getExportURLElements($data) {	
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
		
		$url_elements = array();	
		
		if ($data['sheet_code'] == 'category') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
				$url_keyword_store_id = $data['store_id'];
			} else {
				$url_keyword_store_id = 0;
			}
			
			if (VERSION >= '3.0.0.0') {
				$query = $this->db->query("SELECT query as route, language_id, keyword FROM " . DB_PREFIX . "seo_url WHERE query LIKE 'category_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY query, language_id");
			} else {
				$query = $this->db->query("SELECT route, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE 'category_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY route, language_id");
			}
							
			foreach ($query->rows as $result) {
				$url_elements[$result['route']]['route'] = $result['route'];
				$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
			}
					
			return $url_elements;
		}
		
		if ($data['sheet_code'] == 'product') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
				$url_keyword_store_id = $data['store_id'];
			} else {
				$url_keyword_store_id = 0;
			}
						
			if (VERSION >= '3.0.0.0') {
				$query = $this->db->query("SELECT query as route, language_id, keyword FROM " . DB_PREFIX . "seo_url WHERE query LIKE 'product_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY query, language_id");
			} else {
				$query = $this->db->query("SELECT route, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE 'product_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY route, language_id");
			}
							
			foreach ($query->rows as $result) {
				$url_elements[$result['route']]['route'] = $result['route'];
				$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
			}
					
			return $url_elements;	
		}
		
		if ($data['sheet_code'] == 'manufacturer') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
				$url_keyword_store_id = $data['store_id'];
			} else {
				$url_keyword_store_id = 0;
			}
						
			if (VERSION >= '3.0.0.0') {
				$query = $this->db->query("SELECT query as route, language_id, keyword FROM " . DB_PREFIX . "seo_url WHERE query LIKE 'manufacturer_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY query, language_id");
			} else {
				$query = $this->db->query("SELECT route, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE 'manufacturer_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY route, language_id");
			}	
							
			foreach ($query->rows as $result) {
				$url_elements[$result['route']]['route'] = $result['route'];
				$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
			}
					
			return $url_elements;	
		}
		
		if ($data['sheet_code'] == 'information') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
				$url_keyword_store_id = $data['store_id'];
			} else {
				$url_keyword_store_id = 0;
			}
						
			if (VERSION >= '3.0.0.0') {
				$query = $this->db->query("SELECT query as route, language_id, keyword FROM " . DB_PREFIX . "seo_url WHERE query LIKE 'information_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY query, language_id");
			} else {
				$query = $this->db->query("SELECT route, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE 'information_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY route, language_id");
			}
							
			foreach ($query->rows as $result) {
				$url_elements[$result['route']]['route'] = $result['route'];
				$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
			}
					
			return $url_elements;	
		}
		
		if ($data['sheet_code'] == 'custom_page') {
			if ($data['store_id'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$data['sheet_code']]['field']['url_keyword']['multi_store_status']) {
				$url_keyword_store_id = $data['store_id'];
			} else {
				$url_keyword_store_id = 0;
			}
			
			if (VERSION >= '3.0.0.0') {
				$query = $this->db->query("SELECT query as route, language_id, keyword FROM " . DB_PREFIX . "seo_url WHERE query LIKE '%/%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY query, language_id");
			} else {
				$query = $this->db->query("SELECT route, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE '%/%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY route, language_id");
			}
									
			foreach ($query->rows as $result) {
				if (!in_array($result['route'], $custom_page_exception_routes)) {
					$url_elements[$result['route']]['route'] = $result['route'];
					$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
				}
			}
									
			return $url_elements;
		}
	}
	
	/*
	*	Save Import URL Elements.
	*/
	public function saveImportURLElements($data) {		
		$this->load->model('extension/module/' . $this->codename);
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
		
		$url_elements = array();	
		
		if ($data['store_id'] && $field_info['sheet']['category']['field']['url_keyword']['multi_store'] && $field_info['sheet']['category']['field']['url_keyword']['multi_store_status']) {
			$url_keyword_store_id = $data['store_id'];
		} else {
			$url_keyword_store_id = 0;
		}
		
		if (VERSION >= '3.0.0.0') {
			$query = $this->db->query("SELECT query as route, language_id, keyword FROM " . DB_PREFIX . "seo_url WHERE query LIKE 'category_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY query, language_id");
		} else {
			$query = $this->db->query("SELECT route, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE 'category_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY route, language_id");	
		}
			
		foreach ($query->rows as $result) {
			$url_elements[$result['route']]['route'] = $result['route'];
			$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
		}
				
		if ($data['store_id'] && $field_info['sheet']['product']['field']['url_keyword']['multi_store'] && $field_info['sheet']['product']['field']['url_keyword']['multi_store_status']) {
			$url_keyword_store_id = $data['store_id'];
		} else {
			$url_keyword_store_id = 0;
		}
						
		if (VERSION >= '3.0.0.0') {
			$query = $this->db->query("SELECT query as route, language_id, keyword FROM " . DB_PREFIX . "seo_url WHERE query LIKE 'product_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY query, language_id");
		} else {
			$query = $this->db->query("SELECT route, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE 'product_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY route, language_id");	
		}
							
		foreach ($query->rows as $result) {
			$url_elements[$result['route']]['route'] = $result['route'];
			$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
		}
		
		if ($data['store_id'] && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store'] && $field_info['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) {
			$url_keyword_store_id = $data['store_id'];
		} else {
			$url_keyword_store_id = 0;
		}
						
		if (VERSION >= '3.0.0.0') {
			$query = $this->db->query("SELECT query as route, language_id, keyword FROM " . DB_PREFIX . "seo_url WHERE query LIKE 'manufacturer_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY query, language_id");
		} else {
			$query = $this->db->query("SELECT route, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE 'manufacturer_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY route, language_id");	
		}	
							
		foreach ($query->rows as $result) {
			$url_elements[$result['route']]['route'] = $result['route'];
			$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
		}
					
		if ($data['store_id'] && $field_info['sheet']['information']['field']['url_keyword']['multi_store'] && $field_info['sheet']['information']['field']['url_keyword']['multi_store_status']) {
			$url_keyword_store_id = $data['store_id'];
		} else {
			$url_keyword_store_id = 0;
		}
						
		if (VERSION >= '3.0.0.0') {
			$query = $this->db->query("SELECT query as route, language_id, keyword FROM " . DB_PREFIX . "seo_url WHERE query LIKE 'information_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY query, language_id");
		} else {
			$query = $this->db->query("SELECT route, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE 'information_id=%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY route, language_id");	
		}
							
		foreach ($query->rows as $result) {
			$url_elements[$result['route']]['route'] = $result['route'];
			$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
		}
		
		if ($data['store_id'] && $field_info['sheet']['custom_page']['field']['url_keyword']['multi_store'] && $field_info['sheet']['custom_page']['field']['url_keyword']['multi_store_status']) {
			$url_keyword_store_id = $data['store_id'];
		} else {
			$url_keyword_store_id = 0;
		}
					
		if (VERSION >= '3.0.0.0') {
			$query = $this->db->query("SELECT query as route, language_id, keyword FROM " . DB_PREFIX . "seo_url WHERE query LIKE '%/%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY query, language_id");
		} else {
			$query = $this->db->query("SELECT route, language_id, keyword FROM " . DB_PREFIX . "d_url_keyword WHERE route LIKE '%/%' AND store_id = '" . (int)$url_keyword_store_id . "' GROUP BY route, language_id");	
		}
									
		foreach ($query->rows as $result) {
			if (!in_array($result['route'], $custom_page_exception_routes)) {
				$url_elements[$result['route']]['route'] = $result['route'];
				$url_elements[$result['route']]['url_keyword'][$result['language_id']] = $result['keyword'];
			}
		}
		
		foreach ($data['url_elements'] as $url_element) {
			$sheet_code = '';
			
			if (strpos($url_element['route'], 'category_id') === 0) $sheet_code = 'category';			
			if (strpos($url_element['route'], 'product_id') === 0) $sheet_code = 'product';
			if (strpos($url_element['route'], 'manufacturer_id') === 0) $sheet_code = 'manufacturer';
			if (strpos($url_element['route'], 'information_id') === 0) $sheet_code = 'information';
			if (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $url_element['route']) && !($custom_page_exception_routes && in_array($url_element['route'], $custom_page_exception_routes))) $sheet_code = 'custom_page';
			
			if ($sheet_code) {
				foreach ($languages as $language) {
					if (isset($url_element['url_keyword'][$language['language_id']])) {
						if ((isset($url_elements[$url_element['route']]['url_keyword'][$language['language_id']]) && ($url_element['url_keyword'][$language['language_id']] != $url_elements[$url_element['route']]['url_keyword'][$language['language_id']])) || !isset($url_elements[$url_element['route']]['url_keyword'][$language['language_id']])) {
							if ($data['store_id'] && $field_info['sheet'][$sheet_code]['field']['url_keyword']['multi_store'] && $field_info['sheet'][$sheet_code]['field']['url_keyword']['multi_store_status']) {
								$url_keyword_store_id = $data['store_id'];
							} else {
								$url_keyword_store_id = 0;
							}
						
							if (VERSION >= '3.0.0.0') {
								$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = '" . $this->db->escape($url_element['route']) . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
										
								if (trim($url_element['url_keyword'][$language['language_id']])) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = '" . $this->db->escape($url_element['route']) . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($url_element['url_keyword'][$language['language_id']]) . "'");
								}
							} else {
								$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = '" . $this->db->escape($url_element['route']) . "' AND store_id = '" . (int)$url_keyword_store_id . "' AND language_id = '" . (int)$language['language_id'] . "'");
									
								if (trim($url_element['url_keyword'][$language['language_id']])) {
									$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = '" . $this->db->escape($url_element['route']) . "', store_id='" . (int)$url_keyword_store_id . "', language_id='" . (int)$language['language_id'] . "', keyword = '" . $this->db->escape($url_element['url_keyword'][$language['language_id']]) . "'");
								}	
							
								if (($url_keyword_store_id == 0) && ($language['language_id'] == (int)$this->config->get('config_language_id'))) {
									$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = '" . $this->db->escape($url_element['route']) . "'");
											
									if (trim($url_element['url_keyword'][$language['language_id']])) {	
										$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = '" . $this->db->escape($url_element['route']) . "', keyword = '" . $this->db->escape($url_element['url_keyword'][$language['language_id']]) . "'");
									}
								}
							}
						}
					}
				}	
			}
		}
		
		$cache_data = array(
			'store_id' => $data['store_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
		
	/*
	*	Replace Target Keyword.
	*/		
	private function replaceTargetKeyword($field_template, $target_keyword) {
		$field_template = preg_replace_callback('/\[target_keyword#number=([0-9]+)\]/', function($matches) use ($target_keyword) {
			$replacement_target_keyword = '';
			
			$number = $matches[1]; 
				
			if (isset($target_keyword[$number])) {
				$replacement_target_keyword = $target_keyword[$number];
			}
						
			return $replacement_target_keyword;
			
		}, $field_template);
		
		return $field_template;
	}
}
?>