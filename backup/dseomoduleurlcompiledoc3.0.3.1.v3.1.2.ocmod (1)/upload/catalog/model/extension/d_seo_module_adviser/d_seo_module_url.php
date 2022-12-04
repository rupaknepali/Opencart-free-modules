<?php
class ModelExtensionDSEOModuleAdviserDSEOModuleURL extends Model {
	private $codename = 'd_seo_module_url';
	private $route = 'extension/d_seo_module_adviser/d_seo_module_url';
	
	/*
	*	Return Elements for Adviser.
	*/
	public function getAdviserElements($route) {
		$_language = new Language();
		$_language->load($this->route);
		
		$this->load->model('extension/module/' . $this->codename);
		
		$store_id = $this->config->get('config_store_id');
		$language_id = $this->config->get('config_language_id');
		
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$server = HTTPS_SERVER;
		} else {
			$server = HTTP_SERVER;
		}
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
				
		$adviser_elements = array();
		
		if ((strpos($route, 'category_id') === 0) || (strpos($route, 'product_id') === 0) || (strpos($route, 'manufacturer_id') === 0) || (strpos($route, 'information_id') === 0) || (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $route) && !($custom_page_exception_routes && in_array($route, $custom_page_exception_routes)))) {			
			$field_data = array(
				'field_code' => 'target_keyword',
				'filter' => array(
					'route' => $route,
					'store_id' => $store_id,
					'language_id' => $language_id
				)
			);
			
			$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
			if ($target_keywords) $target_keyword = $target_keywords[$route][$store_id][$language_id];
			
			$field_data = array(
				'field_code' => 'url_keyword',
				'filter' => array(
					'route' => $route,
					'store_id' => $store_id,
					'language_id' => $language_id
				)
			);
			
			$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
			if ($url_keywords) $url_keyword = $url_keywords[$route][$store_id][$language_id];
		
			$url_keyword_duplicate = 0;
			
			if (isset($url_keyword) && $url_keyword) {
				$field_data = array(
					'field_code' => 'url_keyword',
					'filter' => array(
						'store_id' => $store_id,
						'keyword' => $url_keyword
					)
				);
			
				$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
				
				if ($url_keywords) {				
					foreach ($url_keywords as $url_route => $store_url_keywords) {
						foreach ($store_url_keywords[$store_id] as $url_language_id => $keyword) {
							if (($url_route != $route) || ($url_language_id != $language_id)) {
								$url_keyword_duplicate++;
							}
						}
					}
				}
			}
			
			$url_keyword_target_keyword_rating = 0;
			
			if (isset($url_keyword) && isset($target_keyword)) {
				$url_keyword_target_keyword_count = 0;
				
				foreach ($target_keyword as $keyword) {
					if (strpos(mb_strtolower($url_keyword, 'UTF-8'), mb_strtolower($keyword, 'UTF-8')) !== false) $url_keyword_target_keyword_count++;
				}
			
				$url_keyword_target_keyword_rating = $url_keyword_target_keyword_count / count($target_keyword);
			}
											
			$adviser_elements[] = array(
				'extension_code'	=> $this->codename,
				'element_code'		=> 'url_keyword_empty',
				'name'				=> $_language->get('text_url_keyword_empty'),
				'description'		=> $_language->get('help_url_keyword_empty'),
				'rating'			=> (isset($url_keyword) && $url_keyword) ? 1 : 0,
				'weight'			=> 1
			);
			
			$adviser_elements[] = array(
				'extension_code'	=> $this->codename,
				'element_code'		=> 'url_keyword_consistency',
				'name'				=> $_language->get('text_url_keyword_consistency'),
				'description'		=> $_language->get('help_url_keyword_consistency'),
				'rating'			=> (isset($url_keyword) && $url_keyword && filter_var($server . $url_keyword, FILTER_VALIDATE_URL) === false) ? 0 : 1,
				'weight'			=> 1
			);
			
			$adviser_elements[] = array(
				'extension_code'	=> $this->codename,
				'element_code'		=> 'url_keyword_duplicate',
				'name'				=> $_language->get('text_url_keyword_duplicate'),
				'description'		=> $_language->get('help_url_keyword_duplicate'),
				'rating'			=> $url_keyword_duplicate ? (1 / ($url_keyword_duplicate + 1)) : 1,
				'weight'			=> 1
			);
									
			$adviser_elements[] = array(
				'extension_code'	=> $this->codename,
				'element_code'		=> 'url_keyword_target_keyword',
				'name'				=> $_language->get('text_url_keyword_target_keyword'),
				'description'		=> $_language->get('help_url_keyword_target_keyword'),
				'rating'			=> $url_keyword_target_keyword_rating,
				'weight'			=> 0.8
			);
			
			$adviser_elements[] = array(
				'extension_code'	=> $this->codename,
				'element_code'		=> 'url_keyword_length',
				'name'				=> $_language->get('text_url_keyword_length'),
				'description'		=> $_language->get('help_url_keyword_length'),
				'rating'			=> (isset($url_keyword) && (mb_strlen($url_keyword, 'UTF-8') > 100)) ? 0 : 1,
				'weight'			=> 0.2
			);
		}
		
		return $adviser_elements;
	}
}
?>