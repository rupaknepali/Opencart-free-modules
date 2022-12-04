<?php
class ModelExtensionDashboardDSEOModuleURLKeyword extends Model {
	private $codename = 'd_seo_module_url_keyword';
	
	/*
	*	Return Store Duplicate URL Elements.
	*/
	public function getStoreDuplicateURLElements($url_elements) {
		$routes = array();
		
		foreach ($url_elements as $url_element) {
			foreach($url_element['url_keyword'] as $store_id => $language_url_keyword) {
				foreach($language_url_keyword as $url_keyword) {
					if ($url_keyword) {
						$routes[$store_id][$url_keyword][] = $url_element['route'];
					}
				}
			}
		}
		
		$store_duplicate_url_elements = array();
		
		foreach ($url_elements as $url_element) {
			foreach($url_element['url_keyword'] as $store_id => $language_url_keyword) {
				foreach($language_url_keyword as $language_id => $url_keyword) {
					if (isset($routes[$store_id][$url_keyword]) && (count($routes[$store_id][$url_keyword]) > 1) && (reset($routes[$store_id][$url_keyword]) != end($routes[$store_id][$url_keyword]))) {
						if (!isset($store_duplicate_url_elements[$store_id][$url_element['route']])) {
							$store_duplicate_url_elements[$store_id][$url_element['route']] = $url_element;						
						}
						
						$store_duplicate_url_elements[$store_id][$url_element['route']]['url_keyword_duplicate'][$store_id][$language_id] = 1;
					}
				}
			}
		}
		
		foreach ($url_elements as $url_element) {
			foreach($url_element['url_keyword'] as $store_id => $language_url_keyword) {
				foreach($language_url_keyword as $language_id => $url_keyword) {
					if (isset($routes[$store_id][$url_keyword]) && (count($routes[$store_id][$url_keyword]) > 1) && (reset($routes[$store_id][$url_keyword]) == end($routes[$store_id][$url_keyword]))) {
						if (!isset($store_duplicate_url_elements[$store_id][$url_element['route']])) {
							$store_duplicate_url_elements[$store_id][$url_element['route']] = $url_element;						
						}
						
						$store_duplicate_url_elements[$store_id][$url_element['route']]['url_keyword_duplicate'][$store_id][$language_id] = 1;
					}
				}
			}
		}
			
		return $store_duplicate_url_elements;
	}
	
	/*
	*	Return Store Empty URL Elements.
	*/
	public function getStoreEmptyURLElements($url_elements) {
		$stores = $this->getStores();
		$languages = $this->getLanguages();
		
		$store_empty_url_elements = array();
		
		foreach ($url_elements as $url_element) {
			foreach ($stores as $store) {
				foreach ($languages as $language) {
					if (!isset($url_element['url_keyword'][$store['store_id']][$language['language_id']]) || (isset($url_element['url_keyword'][$store['store_id']][$language['language_id']]) && !$url_element['url_keyword'][$store['store_id']][$language['language_id']])) {
						if (!isset($store_empty_url_elements[$store['store_id']][$url_element['route']])) {
							$store_empty_url_elements[$store['store_id']][$url_element['route']] = $url_element;
						}
					}
				}
			}
		}
		
		return $store_empty_url_elements;
	}
			
	/*
	*	Return list of installed SEO URL Keyword extensions.
	*/
	public function getInstalledSEOURLKeywordExtensions() {
		$this->load->model('setting/setting');
				
		$installed_extensions = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension ORDER BY code");
		
		foreach ($query->rows as $result) {
			$installed_extensions[] = $result['code'];
		}
				
		$installed_seo_url_keyword_extensions = array();
		
		$files = glob(DIR_APPLICATION . 'controller/extension/' . $this->codename . '/*.php');
		
		if ($files) {
			foreach ($files as $file) {
				$installed_seo_url_keyword_extension = basename($file, '.php');
				
				if (in_array($installed_seo_url_keyword_extension, $installed_extensions)) {
					$installed_seo_url_keyword_extensions[] = $installed_seo_url_keyword_extension;
				}
			}
		}
		
		return $installed_seo_url_keyword_extensions;
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
}
?>