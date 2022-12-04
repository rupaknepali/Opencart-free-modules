<?php
class ModelExtensionDashboardDSEOModuleURLRedirect extends Model {
	
	/*
	*	Edit Redirect.
	*/
	public function editRedirect($data) {		
		$query = $this->db->query("UPDATE " . DB_PREFIX . "d_url_redirect SET " . $this->db->escape($data['field_code']) . " = '" . $this->db->escape($data['value']) . "' WHERE url_redirect_id = '" . (int)$data['url_redirect_id'] . "'");
	}
		
	/*
	*	Return Store Empty Redirects.
	*/
	public function getStoreEmptyRedirects() {	
		$redirects = array();
		
		$stores = $this->getStores();
		$languages = $this->getLanguages();
		
		$sql = "SELECT * FROM " . DB_PREFIX . "d_url_redirect";
		
		$implode = array();
		
		foreach($languages as $language) {
			$implode[] = $this->db->escape('url_to_' . $language['language_id']) . " = ''";
		}
		
		if ($implode) {
			$sql .= " WHERE " .  implode(' OR ', $implode);
		}
				
		$query = $this->db->query($sql);
								
		$redirects = $query->rows;
		
		$store_empty_redirects = array();
		
		foreach ($redirects as $redirect) {
			$url_from_info = $this->getURLInfo($redirect['url_from']);
			$url_from = $url_from_info['host'] . $url_from_info['port'] . $url_from_info['path'];
			
			if (isset($url_from_info['data']['route'])) {
				$url_from .= '?route=' . $url_from_info['data']['route'];
			}
			
			foreach ($stores as $store) {
				if (strpos($url_from, $store['url']) === 0) {
					if (!isset($store_empty_redirects[$store['store_id']][$redirect['url_redirect_id']])) {
						$store_empty_redirects[$store['store_id']][$redirect['url_redirect_id']] = $redirect;
					}
				}
			}
		}
		
		return $store_empty_redirects;
	}
	
	/*
	*	Return Redirects.
	*/
	public function getRedirects($data = array()) {
		$url_redirects = array();
		
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
		
		$url_info = $this->getURLInfo(HTTP_CATALOG);
		$url = $url_info['host'] . $url_info['port'] . $url_info['path'];
			
		if (isset($url_info['data']['route'])) {
			$url .= '?route=' . $url_info['data']['route'];
		}
			
		$ssl_info = $this->getURLInfo(HTTPS_CATALOG);
		$ssl = $ssl_info['host'] . $ssl_info['port'] . $ssl_info['path'];
			
		if (isset($ssl_info['data']['route'])) {
			$ssl .= '?route=' . $ssl_info['data']['route'];
		}
			
		$result[] = array(
			'store_id' => 0, 
			'name' => $this->config->get('config_name'),
			'url' => $url,
			'ssl' => $ssl
		);
		
		$stores = $this->model_setting_store->getStores();
		
		if ($stores) {			
			foreach ($stores as $store) {
				$url_info = $this->getURLInfo($store['url']);
				$url = $url_info['host'] . $url_info['port'] . $url_info['path'];
			
				if (isset($url_info['data']['route'])) {
					$url .= '?route=' . $url_info['data']['route'];
				}
			
				$ssl_info = $this->getURLInfo($store['ssl']);
				$ssl = $ssl_info['host'] . $ssl_info['port'] . $ssl_info['path'];
			
				if (isset($ssl_info['data']['route'])) {
					$ssl .= '?route=' . $ssl_info['data']['route'];
				}
			
				$result[] = array(
					'store_id' => $store['store_id'],
					'name' => $store['name'],
					'url' => $url,
					'ssl' => $ssl
				);
			}	
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
}
?>