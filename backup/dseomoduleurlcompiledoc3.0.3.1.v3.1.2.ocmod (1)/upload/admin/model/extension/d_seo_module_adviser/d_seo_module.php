<?php
class ModelExtensionDSEOModuleAdviserDSEOModule extends Model {
	private $codename = 'd_seo_module';
	private $route = 'extension/d_seo_module_adviser/d_seo_module';		
	
	/*
	*	Return Elements for Adviser.
	*/
	public function getAdviserElements($route) {
		$_language = new Language();
		$_language->load($this->route);
		
		$this->load->model('extension/module/' . $this->codename);
		
		$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module/getCustomPageExceptionRoutes');
						
		$adviser_elements = array();
				
		if ((strpos($route, 'category_id') === 0) || (strpos($route, 'product_id') === 0) || (strpos($route, 'manufacturer_id') === 0) || (strpos($route, 'information_id') === 0) || (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $route) && !($custom_page_exception_routes && in_array($route, $custom_page_exception_routes)))) {						
			$file_robots = str_replace('system/', '', DIR_SYSTEM) . 'robots.txt';
		
			if (file_exists($file_robots) && file_exists(DIR_SYSTEM . 'library/d_robots_txt_parser.php')) { 
				$robots_txt_parser = new d_robots_txt_parser(file_get_contents($file_robots));
			}
				
			$field_data = array(
				'field_code' => 'target_keyword',
				'filter' => array('route' => $route)
			);
			
			$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
			if ($target_keywords) $target_keyword = reset($target_keywords);
			
			$field_data = array(
				'field_code' => 'url_keyword',
				'filter' => array('route' => $route)
			);
			
			$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);			
			if ($url_keywords) $url_keyword = reset($url_keywords);
																		
			foreach ($stores as $store) {							
				foreach ($languages as $language) {
					$target_keyword_duplicate = 0;
			
					if (isset($target_keyword[$store['store_id']][$language['language_id']])) {
						foreach ($target_keyword[$store['store_id']][$language['language_id']] as $keyword) {						
							$field_data = array(
								'field_code' => 'target_keyword',
								'filter' => array(
									'store_id' => $store['store_id'],
									'keyword' => $keyword
								)
							);
			
							$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
							
							if ($target_keywords) {
								foreach ($target_keywords as $target_route => $store_target_keywords) {
									foreach ($store_target_keywords[$store['store_id']] as $target_language_id => $keywords) {
										if (($target_route != $route) || ($target_language_id != $language['language_id'])) {
											$target_keyword_duplicate++;
										}
									}
								}
							}
						}
					}
							
					$robots_empty_rating = 0;
			
					if (isset($robots_txt_parser) && $robots_txt_parser->getRules()) {
						$robots_empty_rating = 1;
					}
			
					$robots_no_index_rating = 1;
			
					if (isset($url_keyword[$store['store_id']][$language['language_id']]) && $url_keyword[$store['store_id']][$language['language_id']]) {
						if (isset($robots_txt_parser) && $robots_txt_parser->isUrlDisallow('/' . $url_keyword[$store['store_id']][$language['language_id']])) {
							$robots_no_index_rating = 0;
						}
					}
														
					$adviser_elements[$store['store_id']][$language['language_id']][] = array(
						'extension_code'	=> $this->codename,
						'element_code'		=> 'target_keyword_empty',
						'name'				=> $_language->get('text_target_keyword_empty'),
						'description'		=> $_language->get('help_target_keyword_empty'),
						'rating'			=> isset($target_keyword[$store['store_id']][$language['language_id']]) ? 1 : 0,
						'weight'			=> 1
					);
			
					$adviser_elements[$store['store_id']][$language['language_id']][] = array(
						'extension_code'	=> $this->codename,
						'element_code'		=> 'target_keyword_duplicate',
						'name'				=> $_language->get('text_target_keyword_duplicate'),
						'description'		=> $_language->get('help_target_keyword_duplicate'),
						'rating'			=> $target_keyword_duplicate ? (1 / ($target_keyword_duplicate + 1)) : 1,
						'weight'			=> 0.8
					);
			
					$adviser_elements[$store['store_id']][$language['language_id']][] = array(
						'extension_code'	=> $this->codename,
						'element_code'		=> 'robots_empty',
						'name'				=> $_language->get('text_robots_empty'),
						'description'		=> $_language->get('help_robots_empty'),
						'rating'			=> $robots_empty_rating,
						'weight'			=> 1
					);
			
					$adviser_elements[$store['store_id']][$language['language_id']][] = array(
						'extension_code'	=> $this->codename,
						'element_code'		=> 'robots_no_index',
						'name'				=> $_language->get('text_robots_no_index'),
						'description'		=> $_language->get('help_robots_no_index'),
						'rating'			=> $robots_no_index_rating,
						'weight'			=> 1
					);
					
					$adviser_elements[$store['store_id']][$language['language_id']][] = array(
						'extension_code'	=> $this->codename,
						'element_code'		=> 'seo_url_disabled',
						'name'				=> $_language->get('text_seo_url_disabled'),
						'description'		=> $_language->get('help_seo_url_disabled'),
						'rating'			=> ($this->config->get('config_seo_url')) ? 1 : 0,
						'weight'			=> 1
					);	
				}
			}
		}
			
		return $adviser_elements;
	}
}
?>