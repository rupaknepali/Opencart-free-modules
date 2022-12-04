<?php
class ModelExtensionDashboardDSEOModuleTargetKeyword extends Model {
	private $codename = 'd_seo_module_target_keyword';
		
	/*
	*	Return Store Duplicate Target Elements.
	*/
	public function getStoreDuplicateTargetElements($target_elements) {
		$routes = array();
		
		foreach ($target_elements as $target_element) {
			foreach($target_element['target_keyword'] as $store_id => $language_target_keyword) {
				foreach($language_target_keyword as $target_keyword) {
					foreach($target_keyword as $keyword) {
						if ($keyword) {
							$routes[$store_id][$keyword][] = $target_element['route'];
						}
					}
				}
			}
		}
		
		$store_duplicate_target_elements = array();
		
		foreach ($target_elements as $target_element) {
			foreach($target_element['target_keyword'] as $store_id => $language_target_keyword) {
				foreach($language_target_keyword as $language_id => $target_keyword) {
					foreach($target_keyword as $sort_order => $keyword) {
						if (isset($routes[$store_id][$keyword]) && (count($routes[$store_id][$keyword]) > 1) && (reset($routes[$store_id][$keyword]) != end($routes[$store_id][$keyword]))) {
							if (!isset($store_duplicate_target_elements[$store_id][$target_element['route']])) {
								$store_duplicate_target_elements[$store_id][$target_element['route']] = $target_element;						
							}
						
							$store_duplicate_target_elements[$store_id][$target_element['route']]['target_keyword_duplicate'][$store_id][$language_id][$sort_order] = 1;
						}
					}
				}
			}
		}
		
		foreach ($target_elements as $target_element) {
			foreach($target_element['target_keyword'] as $store_id => $language_target_keyword) {
				foreach($language_target_keyword as $language_id => $target_keyword) {
					foreach($target_keyword as $sort_order => $keyword) {
						if (isset($routes[$store_id][$keyword]) && (count($routes[$store_id][$keyword]) > 1) && (reset($routes[$store_id][$keyword]) == end($routes[$store_id][$keyword]))) {
							if (!isset($store_duplicate_target_elements[$store_id][$target_element['route']])) {
								$store_duplicate_target_elements[$store_id][$target_element['route']] = $target_element;						
							}
						
							$store_duplicate_target_elements[$store_id][$target_element['route']]['target_keyword_duplicate'][$store_id][$language_id][$sort_order] = 1;
						}
					}
				}
			}
		}
			
		return $store_duplicate_target_elements;
	}
	
	/*
	*	Return Store Empty Target Elements.
	*/
	public function getStoreEmptyTargetElements($target_elements) {
		$stores = $this->getStores();
		$languages = $this->getLanguages();
		
		$store_empty_target_elements = array();
		
		foreach ($target_elements as $target_element) {
			foreach ($stores as $store) {
				foreach ($languages as $language) {
					if (!isset($target_element['target_keyword'][$store['store_id']][$language['language_id']]) || (isset($target_element['target_keyword'][$store['store_id']][$language['language_id']]) && !$target_element['target_keyword'][$store['store_id']][$language['language_id']])) {
						if (!isset($store_empty_target_elements[$store['store_id']][$target_element['route']])) {
							$store_empty_target_elements[$store['store_id']][$target_element['route']] = $target_element;
						}
					}
				}
			}
		}
			
		return $store_empty_target_elements;
	}
	
	/*
	*	Return list of installed SEO Target Keyword extensions.
	*/
	public function getInstalledSEOTargetKeywordExtensions() {
		$this->load->model('setting/setting');
				
		$installed_extensions = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "extension ORDER BY code");
		
		foreach ($query->rows as $result) {
			$installed_extensions[] = $result['code'];
		}
		
		$installed_seo_target_keyword_extensions = array();
		
		$files = glob(DIR_APPLICATION . 'controller/extension/' . $this->codename . '/*.php');
		
		if ($files) {
			foreach ($files as $file) {
				$installed_seo_target_keyword_extension = basename($file, '.php');
				
				if (in_array($installed_seo_target_keyword_extension, $installed_extensions)) {
					$installed_seo_target_keyword_extensions[] = $installed_seo_target_keyword_extension;
				}
			}
		}
		
		return $installed_seo_target_keyword_extensions;
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