<?php
class ModelExtensionDSEOModuleDSEOModuleURL extends Model {
	private $codename = 'd_seo_module_url';
	private $route = 'extension/d_seo_module/d_seo_module_url';
	
	/*
	*	Add Language.
	*/
	public function addLanguage($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
		
		$add = '';
		
		if (VERSION >= '3.0.0.0') {
			if ($custom_page_exception_routes) {
				$add = " AND query NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
			}
			
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' AND (query LIKE 'category_id=%' OR query LIKE 'product_id=%' OR query LIKE 'manufacturer_id=%' OR query LIKE 'information_id=%' OR (query LIKE '%/%'" . $add . "))");
			
			foreach ($query->rows as $result) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = '" . $this->db->escape($result['query']) . "', store_id = '" . (int)$result['store_id'] . "', language_id = '" . (int)$data['language_id'] . "', keyword = '" . $this->db->escape($result['keyword']) . "'");
			}
		} else {
			if ($custom_page_exception_routes) {
				$add = " AND route NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
			}
			
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_url_keyword WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' AND (route LIKE 'category_id=%' OR route LIKE 'product_id=%' OR route LIKE 'manufacturer_id=%' OR route LIKE 'information_id=%' OR (route LIKE '%/%'" . $add . "))");
			
			foreach ($query->rows as $result) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = '" . $this->db->escape($result['route']) . "', store_id = '" . (int)$result['store_id'] . "', language_id = '" . (int)$data['language_id'] . "', keyword = '" . $this->db->escape($result['keyword']) . "'");
			}
		}
				
		$this->db->query("ALTER TABLE " . DB_PREFIX . "d_url_redirect ADD (url_to_" . (int)$data['language_id'] . " VARCHAR(512) NOT NULL)");
		
		$this->db->query("UPDATE " . DB_PREFIX . "d_url_redirect SET url_to_" . (int)$data['language_id'] . " = url_to_" . (int)$this->config->get('config_language_id'));
		
		$cache_data = array(
			'language_id' => $data['language_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
	
	/*
	*	Delete Language.
	*/
	public function deleteLanguage($data) {	
		$this->load->model('extension/module/' . $this->codename);
		
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
		
		$add = '';
		
		if (VERSION >= '3.0.0.0') {
			if ($custom_page_exception_routes) {
				$add = " AND query NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
			}
			
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE language_id = '" . (int)$data['language_id'] . "' AND (query LIKE 'category_id=%' OR query LIKE 'product_id=%' OR query LIKE 'manufacturer_id=%' OR query LIKE 'information_id=%' OR (query LIKE '%/%'" . $add . "))");
		} else {
			if ($custom_page_exception_routes) {
				$add = " AND route NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
			}
		
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE language_id = '" . (int)$data['language_id'] . "' AND (route LIKE 'category_id=%' OR route LIKE 'product_id=%' OR route LIKE 'manufacturer_id=%' OR route LIKE 'information_id=%' OR (route LIKE '%/%'" . $add . "))");
		}
				
		$this->db->query("ALTER TABLE " . DB_PREFIX . "d_url_redirect DROP url_to_" . (int)$data['language_id']);
		
		$cache_data = array(
			'language_id' => $data['language_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
	
	/*
	*	Delete Store.
	*/
	public function deleteStore($data) {	
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
		
		$add = '';
		
		if (VERSION >= '3.0.0.0') {
			if ($custom_page_exception_routes) {
				$add = " AND query NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
			}
			
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE store_id = '" . (int)$data['store_id'] . "' AND (query LIKE 'category_id=%' OR query LIKE 'product_id=%' OR query LIKE 'manufacturer_id=%' OR query LIKE 'information_id=%' OR (query LIKE '%/%'" . $add . "))");
		} else {
			if ($custom_page_exception_routes) {
				$add = " AND route NOT IN ('" . implode("', '", $custom_page_exception_routes) . "')";
			}
			
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE store_id = '" . (int)$data['store_id'] . "' AND (route LIKE 'category_id=%' OR route LIKE 'product_id=%' OR route LIKE 'manufacturer_id=%' OR route LIKE 'information_id=%' OR (route LIKE '%/%'" . $add . "))");
		}
	}
	
	/*
	*	Save Home URL Keyword.
	*/
	public function saveHomeURLKeyword($data) {		
		$this->load->model('extension/module/' . $this->codename);
		
		if (VERSION >= '3.0.0.0') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'common/home' AND store_id = '" . (int)$data['store_id'] . "'");
		} else {
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'common/home' AND store_id = '" . (int)$data['store_id'] . "'");
					
			if ($data['store_id'] == 0) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'common/home'");
			}
		}
				
		if (isset($data['url_keyword'])) {	
			foreach ($data['url_keyword'] as $language_id => $url_keyword) {
				if ($url_keyword) {
					if (VERSION >= '3.0.0.0') {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'common/home', store_id='" . (int)$data['store_id'] . "', language_id='" . (int)$language_id . "', keyword = '" . $this->db->escape($url_keyword) . "'");
					} else {
						$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'common/home', store_id='" . (int)$data['store_id'] . "', language_id='" . (int)$language_id . "', keyword = '" . $this->db->escape($url_keyword) . "'");
							
						if (($data['store_id'] == 0) && ($language_id == (int)$this->config->get('config_language_id'))) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'common/home', keyword = '" . $this->db->escape($url_keyword) . "'");
						}
					}
				}
			}
		}
		
		$cache_data = array(
			'route' => 'common/home',
			'store_id' => $data['store_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
			
	/*
	*	Save Category URL Keyword.
	*/
	public function saveCategoryURLKeyword($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		if (VERSION >= '3.0.0.0') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$data['category_id'] . "'");
		} else {
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'category_id=" . (int)$data['category_id'] . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'category_id=" . (int)$data['category_id'] . "'");
		}
		
		if (isset($data['url_keyword'])) {	
			foreach ($data['url_keyword'] as $store_id => $language_url_keyword) {
				foreach ($language_url_keyword as $language_id => $url_keyword) {
					if ($url_keyword) {
						if (VERSION >= '3.0.0.0') {
							$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'category_id=" . (int)$data['category_id'] . "', store_id='" . (int)$store_id . "', language_id='" . (int)$language_id . "', keyword = '" . $this->db->escape($url_keyword) . "'");
						} else {
							$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'category_id=" . (int)$data['category_id'] . "', store_id='" . (int)$store_id . "', language_id='" . (int)$language_id . "', keyword = '" . $this->db->escape($url_keyword) . "'");
							
							if (($store_id == 0) && ($language_id == (int)$this->config->get('config_language_id'))) {
								$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$data['category_id'] . "', keyword = '" . $this->db->escape($url_keyword) . "'");
							}
						}
					}
				}
			}
		}
		
		$cache_data = array(
			'route' => 'category_id=' . $data['category_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
	
	/*
	*	Save Product URL Keyword.
	*/
	public function saveProductURLKeyword($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		if (VERSION >= '3.0.0.0') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$data['product_id'] . "'");
		} else {
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'product_id=" . (int)$data['product_id'] . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$data['product_id'] . "'");
		}
		
		if (isset($data['url_keyword'])) {	
			foreach ($data['url_keyword'] as $store_id => $language_url_keyword) {
				foreach ($language_url_keyword as $language_id => $url_keyword) {
					if ($url_keyword) {
						if (VERSION >= '3.0.0.0') {
							$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'product_id=" . (int)$data['product_id'] . "', store_id='" . (int)$store_id . "', language_id='" . (int)$language_id . "', keyword = '" . $this->db->escape($url_keyword) . "'");
						} else {
							$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'product_id=" . (int)$data['product_id'] . "', store_id='" . (int)$store_id . "', language_id='" . (int)$language_id . "', keyword = '" . $this->db->escape($url_keyword) . "'");
				
							if (($store_id == 0) && ($language_id == (int)$this->config->get('config_language_id'))) {
								$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int)$data['product_id'] . "', keyword = '" . $this->db->escape($url_keyword) . "'");
							}
						}
					}
				}
			}
		}
		
		$cache_data = array(
			'route' => 'product_id=' . $data['product_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
	
	/*
	*	Save Manufacturer URL Keyword.
	*/
	public function saveManufacturerURLKeyword($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		if (VERSION >= '3.0.0.0') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'manufacturer_id=" . (int)$data['manufacturer_id'] . "'");
		} else {
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'manufacturer_id=" . (int)$data['manufacturer_id'] . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'manufacturer_id=" . (int)$data['manufacturer_id'] . "'");
		}
		
		if (isset($data['url_keyword'])) {	
			foreach ($data['url_keyword'] as $store_id => $language_url_keyword) {
				foreach ($language_url_keyword as $language_id => $url_keyword) {
					if ($url_keyword) {
						if (VERSION >= '3.0.0.0') {
							$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'manufacturer_id=" . (int)$data['manufacturer_id'] . "', store_id='" . (int)$store_id . "', language_id='" . (int)$language_id . "', keyword = '" . $this->db->escape($url_keyword) . "'");
						} else {
							$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'manufacturer_id=" . (int)$data['manufacturer_id'] . "', store_id='" . (int)$store_id . "', language_id='" . (int)$language_id . "', keyword = '" . $this->db->escape($url_keyword) . "'");
				
							if (($store_id == 0) && ($language_id == (int)$this->config->get('config_language_id'))) {
								$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'manufacturer_id=" . (int)$data['manufacturer_id'] . "', keyword = '" . $this->db->escape($url_keyword) . "'");
							}
						}
					}
				}
			}
		}
		
		$cache_data = array(
			'route' => 'manufacturer_id=' . $data['manufacturer_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
	
	/*
	*	Save Information URL Keyword.
	*/
	public function saveInformationURLKeyword($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		if (VERSION >= '3.0.0.0') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'information_id=" . (int)$data['information_id'] . "'");
		} else {
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'information_id=" . (int)$data['information_id'] . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'information_id=" . (int)$data['information_id'] . "'");
		}
		
		if (isset($data['url_keyword'])) {	
			foreach ($data['url_keyword'] as $store_id => $language_url_keyword) {
				foreach ($language_url_keyword as $language_id => $url_keyword) {
					if ($url_keyword) {
						if (VERSION >= '3.0.0.0') {
							$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET query = 'information_id=" . (int)$data['information_id'] . "', store_id='" . (int)$store_id . "', language_id='" . (int)$language_id . "', keyword = '" . $this->db->escape($url_keyword) . "'");
						} else {
							$this->db->query("INSERT INTO " . DB_PREFIX . "d_url_keyword SET route = 'information_id=" . (int)$data['information_id'] . "', store_id='" . (int)$store_id . "', language_id='" . (int)$language_id . "', keyword = '" . $this->db->escape($url_keyword) . "'");
				
							if (($store_id == 0) && ($language_id == (int)$this->config->get('config_language_id'))) {
								$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'information_id=" . (int)$data['information_id'] . "', keyword = '" . $this->db->escape($url_keyword) . "'");
							}
						}
					}
				}
			}
		}
		
		$cache_data = array(
			'route' => 'information_id=' . $data['information_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
	
	/*
	*	Save Product Category.
	*/
	public function saveProductCategory($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		if (isset($data['category_id'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_product_category WHERE product_id='" . (int)$data['product_id'] . "'");
			
			$this->db->query("INSERT INTO " . DB_PREFIX . "d_product_category SET product_id = '" . (int)$data['product_id'] . "', category_id = '" . (int)$data['category_id'] . "'");
		}
		
		$cache_data = array(
			'route' => 'product_id=' . $data['product_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
	
	/*
	*	Delete Category URL Keyword.
	*/
	public function deleteCategoryURLKeyword($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		if (VERSION >= '3.0.0.0') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$data['category_id'] . "'");
		} else {
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'category_id=" . (int)$data['category_id'] . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'category_id=" . (int)$data['category_id'] . "'");
		}
		
		$cache_data = array(
			'route' => 'category_id=' . $data['category_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
	
	/*
	*	Delete Product URL Keyword.
	*/
	public function deleteProductURLKeyword($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		if (VERSION >= '3.0.0.0') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$data['product_id'] . "'");
		} else {
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'product_id=" . (int)$data['product_id'] . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$data['product_id'] . "'");
		}
		
		$cache_data = array(
			'route' => 'product_id=' . $data['product_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
	
	/*
	*	Delete Manufacturer URL Keyword.
	*/
	public function deleteManufacturerURLKeyword($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		if (VERSION >= '3.0.0.0') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'manufacturer_id=" . (int)$data['manufacturer_id'] . "'");
		} else {
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'manufacturer_id=" . (int)$data['manufacturer_id'] . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'manufacturer_id=" . (int)$data['manufacturer_id'] . "'");
		}
		
		$cache_data = array(
			'route' => 'manufacturer_id=' . $data['manufacturer_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
	
	/*
	*	Delete Information URL Keyword.
	*/
	public function deleteInformationURLKeyword($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		if (VERSION >= '3.0.0.0') {
			$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'information_id=" . (int)$data['information_id'] . "'");
		} else {
			$this->db->query("DELETE FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'information_id=" . (int)$data['information_id'] . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'information_id=" . (int)$data['information_id'] . "'");
		}
		
		$cache_data = array(
			'route' => 'information_id=' . $data['information_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
	
	/*
	*	Delete Product Category.
	*/
	public function deleteProductCategory($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_product_category WHERE product_id = '" . (int)$data['product_id'] . "'");
		
		$cache_data = array(
			'route' => 'product_id=' . $data['product_id']
		);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
	}
	
	/*
	*	Return Home URL Keyword.
	*/
	public function getHomeURLKeyword($store_id = 0) {
		$url_keyword = array();
		
		if (VERSION >= '3.0.0.0') {	
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'common/home' AND store_id = '" . (int)$store_id . "'");
		} else {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'common/home' AND store_id = '" . (int)$store_id . "'");
		}
		
		foreach ($query->rows as $result) {
			$url_keyword[$result['language_id']] = $result['keyword'];
		}
						
		return $url_keyword;
	}
									
	/*
	*	Return Category URL Keyword.
	*/
	public function getCategoryURLKeyword($category_id) {
		$url_keyword = array();
		
		if (VERSION >= '3.0.0.0') {	
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'category_id=" . (int)$category_id . "'");
		} else {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'category_id=" . (int)$category_id . "'");
		}
		
		foreach ($query->rows as $result) {
			$url_keyword[$result['store_id']][$result['language_id']] = $result['keyword'];
		}
						
		return $url_keyword;
	}
			
	/*
	*	Return Product URL Keyword.
	*/
	public function getProductURLKeyword($product_id) {
		$url_keyword = array();
		
		if (VERSION >= '3.0.0.0') {	
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
		} else {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'product_id=" . (int)$product_id . "'");
		}
		
		foreach ($query->rows as $result) {
			$url_keyword[$result['store_id']][$result['language_id']] = $result['keyword'];
		}
						
		return $url_keyword;
	}
	
	/*
	*	Return Manufacturer URL Keyword.
	*/
	public function getManufacturerURLKeyword($manufacturer_id) {
		$url_keyword = array();
		
		if (VERSION >= '3.0.0.0') {	
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'manufacturer_id=" . (int)$manufacturer_id . "'");
		} else {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'manufacturer_id=" . (int)$manufacturer_id . "'");
		}
		
		foreach ($query->rows as $result) {
			$url_keyword[$result['store_id']][$result['language_id']] = $result['keyword'];
		}
					
		return $url_keyword;
	}
	
	/*
	*	Return Information URL Keyword.
	*/
	public function getInformationURLKeyword($information_id) {
		$url_keyword = array();
		
		if (VERSION >= '3.0.0.0') {	
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'information_id=" . (int)$information_id . "'");
		} else {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "d_url_keyword WHERE route = 'information_id=" . (int)$information_id . "'");
		}		
		
		foreach ($query->rows as $result) {
			$url_keyword[$result['store_id']][$result['language_id']] = $result['keyword'];
		}
						
		return $url_keyword;
	}
	
	/*
	*	Return Product Category.
	*/
	public function getProductCategory($product_id) {
		$query = $this->db->query("SELECT DISTINCT pc.category_id, GROUP_CONCAT(cd.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') as category_path FROM " . DB_PREFIX . "d_product_category pc LEFT JOIN " . DB_PREFIX . "category_path cp ON (cp.category_id = pc.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd ON (cd.category_id = cp.path_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "') WHERE pc.product_id = '" . (int)$product_id . "' GROUP BY cp.category_id");
		
		return $query->row;
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
?>