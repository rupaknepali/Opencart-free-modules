<?php
class ModelExtensionModuleDSEOModule extends Model {
	private $codename = 'd_seo_module';
	
	/*
	*	Save File Data.
	*/
	public function saveFileData($file, $data) {
		$dir = str_replace('system/', '', DIR_SYSTEM);
		
		if ($file == 'htaccess') {
			$file_on = $dir . '.htaccess';
			$file_off = $dir . '._htaccess'; 
		}
		
		if ($file == 'robots') {
			$file_on = $dir . 'robots.txt';
			$file_off = $dir . '_robots.txt'; 
		}
		
		if ($data['status']) {
			if (file_exists($file_off)) unlink($file_off);
			$fh = fopen($file_on, 'w');
			fwrite($fh, html_entity_decode($data['text']));
			fclose($fh);
		} else {
			if (file_exists($file_on)) unlink($file_on);
			$fh = fopen($file_off, 'w');
			fwrite($fh, html_entity_decode($data['text']));
			fclose($fh);
		}
	}
	
	/*
	*	Return File Data.
	*/
	public function getFileData($file) {
		$dir = str_replace('system/', '', DIR_SYSTEM);
		
		if ($file == 'htaccess') {
			$file_on = $dir . '.htaccess';
			$file_off = $dir . '._htaccess'; 
		}
		
		if ($file == 'robots') {
			$file_on = $dir . 'robots.txt';
			$file_off = $dir . '_robots.txt'; 
		}
		
		$data = array();
		
		if (file_exists($file_on)) { 
			$data['status'] = true;
			$fh = fopen($file_on, 'r');
			$data['text'] = fread($fh, filesize($file_on) + 1);
			fclose($fh);
		} else {
			if (file_exists($file_off)) {
				$data['status'] = false;
				$fh = fopen($file_off, 'r');
				$data['text'] = fread($fh, filesize($file_off) + 1);
				fclose($fh);
			} else {
				$data['status'] = false;
				$data['text'] = '';
			}
		}
				
		return $data;
	}
	
	/*
	*	Create Default Target Elements.
	*/
	public function createDefaultTargetElements($default_target_keywords, $store_id = 0) {
		$languages = $this->getLanguages();
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "d_target_keyword WHERE route LIKE '%/%' AND store_id = '" . (int)$store_id . "'");
		
		foreach ($languages as $language) {
			$implode = array();
						
			foreach ($default_target_keywords as $route => $target_keyword) {
				$sort_order = 1;
				
				foreach ($target_keyword as $keyword) {
					$implode[] = "('" . $route . "', '" . (int)$store_id . "', '" . (int)$language['language_id'] . "', '" . $sort_order . "', '" . $keyword . "')";
					
					$sort_order++;
				}
			}
			
			if ($implode) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "d_target_keyword (route, store_id, language_id, sort_order, keyword) VALUES " . implode(', ', $implode));
			}
		}
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
	*	Return list of SEO extensions.
	*/
	public function getSEOExtensions() {
		$this->load->model('setting/setting');
				
		$seo_extensions = array();
		
		$files = glob(DIR_APPLICATION . 'controller/extension/' . $this->codename . '/*.php');
		
		if ($files) {
			foreach ($files as $file) {
				$seo_extensions[] = basename($file, '.php');
			}
		}
		
		return $seo_extensions;
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
		
		$files = glob(DIR_APPLICATION . 'controller/extension/' . $this->codename . '/*.php');
		
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
		if (VERSION < '3.0.0.0') {
			$this->db->query("ALTER TABLE " . DB_PREFIX . "setting MODIFY code VARCHAR(128) NOT NULL");
		}
		
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "d_target_keyword");
		
		$this->db->query("CREATE TABLE " . DB_PREFIX . "d_target_keyword (route VARCHAR(255) NOT NULL, store_id INT(11) NOT NULL, language_id INT(11) NOT NULL, sort_order INT(3) NOT NULL, keyword VARCHAR(255) NOT NULL, PRIMARY KEY (route, store_id, language_id, sort_order), KEY keyword (keyword)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
	}
	
	/*
	*	Uninstall.
	*/		
	public function uninstallExtension() {
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "d_target_keyword");
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "d_meta_data");
		$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "d_url_keyword");
	}
}
?>