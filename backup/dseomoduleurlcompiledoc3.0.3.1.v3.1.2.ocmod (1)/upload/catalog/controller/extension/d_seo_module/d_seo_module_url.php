<?php
class ControllerExtensionDSEOModuleDSEOModuleURL extends Controller {
	private $codename = 'd_seo_module_url';
	private $route = 'extension/d_seo_module/d_seo_module_url';
	private $config_file = 'd_seo_module_url';
	
	/*
	*	Functions for SEO Module.
	*/	
	public function seo_url_add_rewrite() {
		$this->load->model('setting/setting');

		// Setting
		$_config = new Config();
		$_config->load($this->config_file);
		$config_setting = ($_config->get($this->codename . '_setting')) ? $_config->get($this->codename . '_setting') : array();
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		$setting = ($this->config->get('module_' . $this->codename . '_setting')) ? $this->config->get('module_' . $this->codename . '_setting') : array();
		
		if (!empty($setting)) {
			$config_setting = array_replace_recursive($config_setting, $setting);
		}
		
		$setting = $config_setting;
		
		$setting['custom_page_exception_routes'] = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
		
		$this->config->set('module_' . $this->codename . '_setting', $setting);
				
		if ($status) {			
			// Register Cache
			if (!$this->registry->has('d_cache') && file_exists(DIR_SYSTEM . 'library/d_cache.php')) {
				$this->registry->set('d_cache', new d_cache());
			}
		
			// Add rewrite to url class
			if ($this->config->get('config_seo_url')) {
				$this->url->addRewrite($this);
			}
		}
	}
	
	public function seo_url_analyse() {
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		$store_id = (int)$this->config->get('config_store_id');
		
		// Setting
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		$setting = ($this->config->get('module_' . $this->codename . '_setting')) ? $this->config->get('module_' . $this->codename . '_setting') : array();
				
		if ($status) {							
			if (!isset($this->request->get['route']) && !isset($this->request->get['_route_'])) {
				$this->request->get['route'] = 'common/home';
				
				$field_data = array(
					'field_code' => 'url_keyword',
					'filter' => array(
						'store_id' => $store_id,
						'keyword' => '/'
					)
				);
			
				$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
					
				if ($url_keywords) {				
					foreach ($url_keywords as $url_route => $store_url_keywords) {
						foreach ($store_url_keywords[$store_id] as $url_language_id => $keyword) {
							$language_id = $url_language_id;
						}
							
						foreach ($store_url_keywords[$store_id] as $url_language_id => $keyword) {
							if ($url_language_id == (int)$this->config->get('config_language_id')) {
								$language_id = $url_language_id;
							}
						}
					}
				}

				if (isset($language_id)) {
					$this->load->model($this->route);
					$this->load->model('localisation/language');
										
					$language_info = $this->model_localisation_language->getLanguage($language_id);
							
					if ($this->session->data['language'] != $language_info['code']) {
						$this->session->data['language'] = $language_info['code'];
						setcookie('language', $language_info['code'], time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
												
						if (VERSION >= '2.2.0.0') {
							$language = new Language($language_info['code']);
							$language->load($language_info['code']);
		
							$this->registry->set('language', $language);
		
							$this->config->set('config_language_id', $language_id);	
						} else {
							$language = new Language($language_info['directory']);
							$language->load($language_info['directory']);
							
							$this->registry->set('language', $language);
							
							$this->config->set('config_language_id', $language_id);
							$this->config->set('config_language', $language_info['code']);
						}
					}	
				}
			}
			
			if (!isset($this->request->get['route']) && isset($this->request->get['_route_'])) {
				$parts = explode('/', $this->request->get['_route_']);
								
				// remove any empty arrays from trailing
				if (utf8_strlen(end($parts)) == 0) {
					array_pop($parts);
				}
				
				if ($setting['multi_language_sub_directory']['status']) {
					foreach ($setting['multi_language_sub_directory']['name'] as $subdirectory_language_id => $subdirectory_name) {
						if ($subdirectory_name == reset($parts)) {
							$multi_language_sub_directory_language_id = $subdirectory_language_id;
						
							array_shift($parts);
						
							break;
						}
					}
				}
				
				if (empty($parts)) {
					$this->request->get['route'] = 'common/home';
				
					$field_data = array(
						'field_code' => 'url_keyword',
						'filter' => array(
							'store_id' => $store_id,
							'keyword' => '/'
						)
					);
			
					$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
					
					if ($url_keywords) {				
						foreach ($url_keywords as $url_route => $store_url_keywords) {
							foreach ($store_url_keywords[$store_id] as $url_language_id => $keyword) {
								$language_id = $url_language_id;
							}
							
							foreach ($store_url_keywords[$store_id] as $url_language_id => $keyword) {
								if ($url_language_id == (int)$this->config->get('config_language_id')) {
									$language_id = $url_language_id;
								}
							}
						}
					}
				}
				
				foreach ($parts as $part) {									
					unset($route);
					unset($language_id);
										
					$field_data = array(
						'field_code' => 'url_keyword',
						'filter' => array(
							'store_id' => $store_id,
							'keyword' => $part
						)
					);
			
					$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
					
					if ($url_keywords) {				
						foreach ($url_keywords as $url_route => $store_url_keywords) {
							foreach ($store_url_keywords[$store_id] as $url_language_id => $keyword) {
								$route = $url_route;
								$language_id = $url_language_id;
							}
							
							foreach ($store_url_keywords[$store_id] as $url_language_id => $keyword) {
								if ($url_language_id == (int)$this->config->get('config_language_id')) {
									$route = $url_route;
									$language_id = $url_language_id;
								}
							}
						}
					}
																			
					if (isset($route)) {			
						$route = explode('=', $route);

						if ($route[0] == 'product_id') {
							$this->request->get['product_id'] = $route[1];
						}

						if ($route[0] == 'category_id') {
							if (!isset($this->request->get['path'])) {
								$this->request->get['path'] = $route[1];
							} else {
								$this->request->get['path'] .= '_' . $route[1];
							}
						}

						if ($route[0] == 'manufacturer_id') {
							$this->request->get['manufacturer_id'] = $route[1];
						}

						if ($route[0] == 'information_id') {
							$this->request->get['information_id'] = $route[1];
						}
					
						if (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $route[0])) {
							$this->request->get['route'] = $route[0];
						}
					} else {										
						break;
					}
				}
				
				if (isset($multi_language_sub_directory_language_id)) {
					$language_id = $multi_language_sub_directory_language_id;
				}
				
				if (isset($language_id)) {
					$this->load->model($this->route);
					$this->load->model('localisation/language');
										
					$language_info = $this->model_localisation_language->getLanguage($language_id);
							
					if ($this->session->data['language'] != $language_info['code']) {
						$this->session->data['language'] = $language_info['code'];
						setcookie('language', $language_info['code'], time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
												
						if (VERSION >= '2.2.0.0') {
							$language = new Language($language_info['code']);
							$language->load($language_info['code']);
		
							$this->registry->set('language', $language);
		
							$this->config->set('config_language_id', $language_id);	
						} else {
							$language = new Language($language_info['directory']);
							$language->load($language_info['directory']);
							
							$this->registry->set('language', $language);
							
							$this->config->set('config_language_id', $language_id);
							$this->config->set('config_language', $language_info['code']);
						}
					}	
				}

				if (!isset($this->request->get['route'])) {
					if (isset($this->request->get['product_id'])) {
						$this->request->get['route'] = 'product/product';
					} elseif (isset($this->request->get['path'])) {
						$this->request->get['route'] = 'product/category';
					} elseif (isset($this->request->get['manufacturer_id'])) {
						$this->request->get['route'] = 'product/manufacturer/info';
					} elseif (isset($this->request->get['information_id'])) {
						$this->request->get['route'] = 'information/information';
					}
				}
			}
			
			if (isset($this->request->get['route'])) {
				if ($this->request->get['route'] == 'product/category') {
					if (isset($this->request->get['path'])) {
						$parts = explode('_', (string)$this->request->get['path']);
						$category_id = (int)array_pop($parts);
					} else {
						$category_id = 0;
					}
				
					if ($category_id) {		
						$category_path = $this->{'model_extension_d_seo_module_' . $this->codename}->getCategoryPath($category_id);
							
						if ($setting['sheet']['category']['unique_url'] && $category_path && (!isset($this->request->get['curRoute']))) {			
							$url_data = array();
							
							$url_data['path'] = $category_path;
																						
							$exception_data = explode(',', $setting['sheet']['category']['exception_data']);
						
							foreach ($exception_data as $exception) {
								$exception = trim($exception);
								
								if (isset($this->request->get[$exception])) {
									$url_data[$exception] = $this->request->get[$exception];
								}
							}
							
							$url_from = $this->{'model_extension_d_seo_module_' . $this->codename}->getCurrentURL();
							$url_to = $this->url->link($this->request->get['route'], http_build_query($url_data), true);
							
							if (($url_to != $url_from) && ($url_to != urldecode($url_from))) {
								$this->response->redirect($url_to, '301');
							}
						}
						
						// Breadcrumbs
						unset($this->request->get['path']);
						
						if ($category_path) {
							$this->request->get['path'] = $category_path;
						}
					}
				} elseif ($this->request->get['route'] == 'product/product') {
					if (isset($this->request->get['product_id'])) {
						$product_id = (int)$this->request->get['product_id'];
					} else {
						$product_id = 0;
					}
					
					if ($product_id) {
						$product_path = $this->{'model_extension_d_seo_module_' . $this->codename}->getProductPath($product_id);
						
						if ($setting['sheet']['product']['unique_url']) {							
							$url_data = array();
																					
							$url_data['product_id'] = $product_id;
						
							$exception_data = explode(',', $setting['sheet']['product']['exception_data']);
						
							foreach ($exception_data as $exception) {
								$exception = trim($exception);
								
								if (isset($this->request->get[$exception])) {
									$url_data[$exception] = $this->request->get[$exception];
								}
							}
							
							$url_from = $this->{'model_extension_d_seo_module_' . $this->codename}->getCurrentURL();
							$url_to = $this->url->link($this->request->get['route'], http_build_query($url_data), true);
							
							if (($url_to != $url_from) && ($url_to != urldecode($url_from))) {
								$this->response->redirect($url_to, '301');
							}
						}
						
						// Breadcrumbs
						unset($this->request->get['path']);
						
						if ($product_path) {
							$this->request->get['path'] = $product_path;
						}
					}	
				} elseif ($this->request->get['route'] == 'product/manufacturer/info') {
					if (isset($this->request->get['manufacturer_id'])) {
						$manufacturer_id = (int)$this->request->get['manufacturer_id'];
					} else {
						$manufacturer_id = 0;
					}
								
					if ($manufacturer_id) {
						if ($setting['sheet']['manufacturer']['unique_url']) {
							$url_data = array();
							
							$url_data['manufacturer_id'] = $manufacturer_id;
						
							$exception_data = explode(',', $setting['sheet']['manufacturer']['exception_data']);
							
							foreach ($exception_data as $exception) {
								$exception = trim($exception);
								
								if (isset($this->request->get[$exception])) {
									$url_data[$exception] = $this->request->get[$exception];
								}
							}
						
							$url_from = $this->{'model_extension_d_seo_module_' . $this->codename}->getCurrentURL();
							$url_to = $this->url->link($this->request->get['route'], http_build_query($url_data), true);
							
							if (($url_to != $url_from) && ($url_to != urldecode($url_from))) {
								$this->response->redirect($url_to, '301');
							}
						}
					}
				} elseif ($this->request->get['route'] == 'information/information') {
					if (isset($this->request->get['information_id'])) {
						$information_id = (int)$this->request->get['information_id'];
					} else {
						$information_id = 0;
					}	
					
					if ($information_id) {
						if ($setting['sheet']['information']['unique_url']) {
							$url_data = array();
							
							$url_data['information_id'] = $information_id;
						
							$exception_data = explode(',', $setting['sheet']['information']['exception_data']);
							
							foreach ($exception_data as $exception) {
								$exception = trim($exception);
								
								if (isset($this->request->get[$exception])) {
									$url_data[$exception] = $this->request->get[$exception];
								}
							}
						
							$url_from = $this->{'model_extension_d_seo_module_' . $this->codename}->getCurrentURL();
							$url_to = $this->url->link($this->request->get['route'], http_build_query($url_data), true);
							
							if (($url_to != $url_from) && ($url_to != urldecode($url_from))) {
								$this->response->redirect($url_to, '301');
							}
						}
					}
				} elseif ($this->request->get['route'] == 'product/manufacturer') {
					if ($setting['sheet']['manufacturer']['unique_url']) {
						$url_data = array();
						
						$exception_data = explode(',', $setting['sheet']['manufacturer']['exception_data']);
							
						foreach ($exception_data as $exception) {
							$exception = trim($exception);
							
							if (isset($this->request->get[$exception])) {
								$url_data[$exception] = $this->request->get[$exception];
							}
						}
						
						$url_from = $this->{'model_extension_d_seo_module_' . $this->codename}->getCurrentURL();
						$url_to = $this->url->link($this->request->get['route'], http_build_query($url_data), true);
							
						if (($url_to != $url_from) && ($url_to != urldecode($url_from))) {
							$this->response->redirect($url_to, '301');
						}
					}
				} elseif ($this->request->get['route'] == 'product/search') {
					if ($setting['sheet']['search']['unique_url']) {
						$url_data = array();
						
						$exception_data = explode(',', $setting['sheet']['search']['exception_data']);
							
						foreach ($exception_data as $exception) {
							$exception = trim($exception);
							
							if (isset($this->request->get[$exception])) {
								$url_data[$exception] = $this->request->get[$exception];
							}
						}
						
						$url_from = $this->{'model_extension_d_seo_module_' . $this->codename}->getCurrentURL();
						$url_to = $this->url->link($this->request->get['route'], http_build_query($url_data), true);
							
						if (($url_to != $url_from) && ($url_to != urldecode($url_from))) {
							$this->response->redirect($url_to, '301');
						}
					}
				} elseif ($this->request->get['route'] == 'product/special') {
					if ($setting['sheet']['special']['unique_url']) {
						$url_data = array();
						
						$exception_data = explode(',', $setting['sheet']['special']['exception_data']);
							
						foreach ($exception_data as $exception) {
							$exception = trim($exception);
							
							if (isset($this->request->get[$exception])) {
								$url_data[$exception] = $this->request->get[$exception];
							}
						}
						
						$url_from = $this->{'model_extension_d_seo_module_' . $this->codename}->getCurrentURL();
						$url_to = $this->url->link($this->request->get['route'], http_build_query($url_data), true);
							
						if (($url_to != $url_from) && ($url_to != urldecode($url_from))) {
							$this->response->redirect($url_to, '301');
						}
					}
				} elseif (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $this->request->get['route'])) {
					$custom_page_exception_routes = array_merge($setting['custom_page_exception_routes'], array('error/not_found'));
					
					if (!in_array($this->request->get['route'], $custom_page_exception_routes)) {
						$language_id = (int)$this->config->get('config_language_id');
					
						$field_data = array(
							'field_code' => 'url_keyword',
							'filter' => array(
								'route' => $this->request->get['route'],
								'store_id' => $store_id,
								'language_id' => $language_id
							)
						);
			
						$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
								
						if ($url_keywords) {
							if ($setting['sheet']['custom_page']['unique_url']) {
								$url_data = array();
						
								$exception_data = explode(',', $setting['sheet']['custom_page']['exception_data']);
							
								foreach ($exception_data as $exception) {
									$exception = trim($exception);
								
									if (isset($this->request->get[$exception])) {
										$url_data[$exception] = $this->request->get[$exception];
									}
								}
						
								$url_from = $this->{'model_extension_d_seo_module_' . $this->codename}->getCurrentURL();
								$url_to = $this->url->link($this->request->get['route'], http_build_query($url_data), true);
								
								if (($url_to != $url_from) && ($url_to != urldecode($url_from))) {
									$this->response->redirect($url_to, '301');
								}
							}
						}
					}
				}
			}
		}
	}
	
	public function seo_url_validate() {
		$this->load->model($this->route);
		
		// Setting
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		$setting = ($this->config->get('module_' . $this->codename . '_setting')) ? $this->config->get('module_' . $this->codename . '_setting') : array();
				
		if ($status) {
			if ((isset($this->request->get['route']) && !$this->{'model_extension_d_seo_module_' . $this->codename}->validateRoute($this->request->get['route'])) || !isset($this->request->get['route'])) {
				$url_from = $this->{'model_extension_d_seo_module_' . $this->codename}->getCurrentURL();
				$url_from_info = $this->{'model_extension_d_seo_module_' . $this->codename}->getURLInfo($url_from);
				$url_from = $url_from_info['host'] . $url_from_info['port'] . $url_from_info['path'];
					
				if (isset($url_from_info['data']['route'])) {
					$url_from .= '?route=' . $url_from_info['data']['route'];
				} 
					
				$this->{'model_extension_d_seo_module_' . $this->codename}->addURLRedirectFrom($url_from);
			}
				
			if ($setting['redirect']['status']) {					
				$url_from = $this->{'model_extension_d_seo_module_' . $this->codename}->getCurrentURL();
				$url_from_info = $this->{'model_extension_d_seo_module_' . $this->codename}->getURLInfo($url_from);
				$url_from = $url_from_info['host'] . $url_from_info['port'] . $url_from_info['path'];
					
				if (isset($url_from_info['data']['route'])) {
					$url_from .= '?route=' . $url_from_info['data']['route'];
				} 
					
				$url_to = $this->{'model_extension_d_seo_module_' . $this->codename}->getURLRedirectTo($url_from);
					
				if ($url_to) {
					$url_to_info = $this->{'model_extension_d_seo_module_' . $this->codename}->getURLInfo($url_to);
						
					if (($url_to_info['host'] . $url_to_info['port'] . $url_to_info['path'] != $url_from_info['host'] . $url_from_info['port'] . $url_from_info['path']) && ($url_to_info['host'] . $url_to_info['port'] . $url_to_info['path'] != urldecode($url_from_info['host'] . $url_from_info['port'] . $url_from_info['path']))) {
						$exception_data = explode(',', $setting['redirect']['exception_data']);
							
						foreach ($exception_data as $exception) {
							$exception = trim($exception);
								
							if (isset($this->request->get[$exception]) && !isset($url_to_info['data'][$exception])) {
								$url_to_info['data'][$exception] = $this->request->get[$exception];
							}
						}
							
						$url_to = '';
							
						if ($url_to_info['scheme']) {
							$url_to .= $url_to_info['scheme'];
						} else {
							$url_to .= $url_from_info['scheme'];
						}
							
						$url_to .= $url_to_info['host'] . $url_to_info['port'] . $url_to_info['path'];
							
						if ($url_to_info['data']) {
							$url_to .= '?' . http_build_query($url_to_info['data']);
						}
														
						$this->response->redirect($url_to, '301');
					}
				}
			}
		}
	}
	
	public function rewrite($url) {
		$this->load->model($this->route);
				
		// Setting
		$setting = ($this->config->get('module_' . $this->codename . '_setting')) ? $this->config->get('module_' . $this->codename . '_setting') : array();
		
		$url_info = $this->{'model_extension_d_seo_module_' . $this->codename}->getURLInfo($url);
			
		if (isset($url_info['data']['route']) && $this->registry->has('d_cache')) {
			$store_id = (int)$this->config->get('config_store_id');
			$language_id = (int)$this->config->get('config_language_id');
			
			$url_rewrite = false;
							
			if ($url_info['data']['route'] == 'product/category') {
				if (isset($url_info['data']['path'])) {
					$parts = explode('_', (string)$url_info['data']['path']);
					$category_id = (int)array_pop($parts);
				} else {
					$category_id = 0;
				}
					
				if ($category_id) {
					$url_rewrite = $this->d_cache->get($this->codename, 'url_rewrite.' . preg_replace('/[^A-Z0-9\._-]/i', '_', $url_info['data']['route']) . '.' . $category_id . '.' . $store_id . '.' . $language_id);
					
					if ($url_rewrite) {
						unset($url_info['data']['route']);
						unset($url_info['data']['path']);
					} elseif ($url_rewrite === false) {
						$url_rewrite = '';
							
						if (!$setting['sheet']['category']['short_url']) {
							$category_path = $this->{'model_extension_d_seo_module_' . $this->codename}->getCategoryPath($category_id);
						} else {
							$category_path = $category_id;						
						}
					
						unset($url_info['data']['path']);
					
						$url_info['data'] = array_slice($url_info['data'], 0, 1, true) + array('path' => $category_path) + array_slice($url_info['data'], 1, count($url_info['data']) - 1, true);
																
						$sub_categories_id = explode('_', $category_path);
								
						foreach ($sub_categories_id as $sub_category_id) {
							$route = 'category_id=' . $sub_category_id;
								
							$field_data = array(
								'field_code' => 'url_keyword',
								'filter' => array(
									'route' => $route,
									'store_id' => $store_id,
									'language_id' => $language_id
								)
							);
			
							$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
									
							if ($url_keywords) {
								$url_keyword = $url_keywords[$route][$store_id][$language_id];
								
								if ($url_keyword) {
									$url_rewrite .= '/' . $url_keyword;
								} else {
									$url_rewrite = '';

									break;
								}
							}
						}
						
						if ($url_rewrite && $setting['multi_language_sub_directory']['status'] && isset($setting['multi_language_sub_directory']['name'][$language_id]) && $setting['multi_language_sub_directory']['name'][$language_id]) {
							$url_rewrite = '/' . $setting['multi_language_sub_directory']['name'][$language_id] . $url_rewrite;
						}
							
						$this->d_cache->set($this->codename, 'url_rewrite.' . preg_replace('/[^A-Z0-9\._-]/i', '_', $url_info['data']['route']) . '.' . $category_id . '.' . $store_id . '.' . $language_id, $url_rewrite);
							
						if ($url_rewrite) {
							unset($url_info['data']['route']);
							unset($url_info['data']['path']);
						}
					}						
				}
			} elseif ($url_info['data']['route'] == 'product/product') {
				if (isset($url_info['data']['product_id'])) {
					$product_id = (int)$url_info['data']['product_id'];
				} else {
					$product_id = 0;
				}
				
				if ($product_id) {
					$url_rewrite = $this->d_cache->get($this->codename, 'url_rewrite.' . preg_replace('/[^A-Z0-9\._-]/i', '_', $url_info['data']['route']) . '.' . $product_id . '.' . $store_id . '.' . $language_id);
						
					if ($url_rewrite) {
						unset($url_info['data']['route']);
						unset($url_info['data']['path']);
						unset($url_info['data']['manufacturer_id']);
						unset($url_info['data']['product_id']);
					} elseif ($url_rewrite === false) {
						$url_rewrite = '';
							
						if (!$setting['sheet']['product']['short_url']) {
							$product_path = $this->{'model_extension_d_seo_module_' . $this->codename}->getProductPath($product_id);
						} else {
							$product_path = '';
						}
							
						unset($url_info['data']['path']);
												
						if ($product_path) {
							$url_info['data'] = array_slice($url_info['data'], 0, 1, true) + array('path' => $product_path) + array_slice($url_info['data'], 1, count($url_info['data']) - 1, true);
								
							$sub_categories_id = explode('_', $product_path);
								
							foreach ($sub_categories_id as $sub_category_id) {
								$route = 'category_id=' . $sub_category_id;
								
								$field_data = array(
									'field_code' => 'url_keyword',
									'filter' => array(
										'route' => $route,
										'store_id' => $store_id,
										'language_id' => $language_id
									)
								);
			
								$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
									
								if ($url_keywords) {
									$url_keyword = $url_keywords[$route][$store_id][$language_id];
								
									if ($url_keyword) {
										$url_rewrite .= '/' . $url_keyword;
									} else {
										$url_rewrite = '';

										break;
									}
								}
							}
						}
								
						$route = 'product_id=' . $product_id; 
								
						$field_data = array(
							'field_code' => 'url_keyword',
							'filter' => array(
								'route' => $route,
								'store_id' => $store_id,
								'language_id' => $language_id
							)
						);
			
						$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
								
						if ($url_keywords) {
							$url_keyword = $url_keywords[$route][$store_id][$language_id];
								
							if ($url_keyword) {
								$url_rewrite .= '/' . $url_keyword;
							} else {
								$url_rewrite = '';
							}
						}
						
						if ($url_rewrite && $setting['multi_language_sub_directory']['status'] && isset($setting['multi_language_sub_directory']['name'][$language_id]) && $setting['multi_language_sub_directory']['name'][$language_id]) {
							$url_rewrite = '/' . $setting['multi_language_sub_directory']['name'][$language_id] . $url_rewrite;
						}
							
						$this->d_cache->set($this->codename, 'url_rewrite.' . preg_replace('/[^A-Z0-9\._-]/i', '_', $url_info['data']['route']) . '.' . $product_id . '.' . $store_id . '.' . $language_id, $url_rewrite);
								
						if ($url_rewrite) {
							unset($url_info['data']['route']);
							unset($url_info['data']['path']);
							unset($url_info['data']['manufacturer_id']);
							unset($url_info['data']['product_id']);
						}
					}
				}
			} elseif ($url_info['data']['route'] == 'product/manufacturer/info') {
				if (isset($url_info['data']['manufacturer_id'])) {
					$manufacturer_id = (int)$url_info['data']['manufacturer_id'];
				} else {
					$manufacturer_id = 0;
				}
				
				if ($manufacturer_id) {
					$url_rewrite = $this->d_cache->get($this->codename, 'url_rewrite.' . preg_replace('/[^A-Z0-9\._-]/i', '_', $url_info['data']['route']) . '.' . $manufacturer_id . '.' . $store_id . '.' . $language_id);
						
					if ($url_rewrite) {
						unset($url_info['data']['route']);
						unset($url_info['data']['manufacturer_id']);
					} elseif ($url_rewrite === false) {
						$url_rewrite = '';
							
						$route = 'manufacturer_id=' . $manufacturer_id; 
								
						$field_data = array(
							'field_code' => 'url_keyword',
							'filter' => array(
								'route' => $route,
								'store_id' => $store_id,
								'language_id' => $language_id
							)
						);
			
						$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
								
						if ($url_keywords) {
							$url_keyword = $url_keywords[$route][$store_id][$language_id];
								
							if ($url_keyword) {
								$url_rewrite .= '/' . $url_keyword;
							}
						}
						
						if ($url_rewrite && $setting['multi_language_sub_directory']['status'] && isset($setting['multi_language_sub_directory']['name'][$language_id]) && $setting['multi_language_sub_directory']['name'][$language_id]) {
							$url_rewrite = '/' . $setting['multi_language_sub_directory']['name'][$language_id] . $url_rewrite;
						}
							
						$this->d_cache->set($this->codename, 'url_rewrite.' . preg_replace('/[^A-Z0-9\._-]/i', '_', $url_info['data']['route']) . '.' . $manufacturer_id . '.' . $store_id . '.' . $language_id, $url_rewrite);
															
						if ($url_rewrite) {
							unset($url_info['data']['route']);
							unset($url_info['data']['manufacturer_id']);
						}
					}
				}
			} elseif ($url_info['data']['route'] == 'information/information') {
				if (isset($url_info['data']['information_id'])) {
					$information_id = (int)$url_info['data']['information_id'];
				} else {
					$information_id = 0;
				}
				
				if ($information_id) {
					$url_rewrite = $this->d_cache->get($this->codename, 'url_rewrite.' . preg_replace('/[^A-Z0-9\._-]/i', '_', $url_info['data']['route']) . '.' . $information_id . '.' . $store_id . '.' . $language_id);
						
					if ($url_rewrite) {
						unset($url_info['data']['route']);
						unset($url_info['data']['information_id']);
					} elseif ($url_rewrite === false) {
						$url_rewrite = '';
							
						$route = 'information_id=' . $information_id; 
							
						$field_data = array(
							'field_code' => 'url_keyword',
							'filter' => array(
								'route' => $route,
								'store_id' => $store_id,
								'language_id' => $language_id
							)
						);
			
						$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
								
						if ($url_keywords) {
							$url_keyword = $url_keywords[$route][$store_id][$language_id];
								
							if ($url_keyword) {
								$url_rewrite .= '/' . $url_keyword;
							}
						}
						
						if ($url_rewrite && $setting['multi_language_sub_directory']['status'] && isset($setting['multi_language_sub_directory']['name'][$language_id]) && $setting['multi_language_sub_directory']['name'][$language_id]) {
							$url_rewrite = '/' . $setting['multi_language_sub_directory']['name'][$language_id] . $url_rewrite;
						}
							
						$this->d_cache->set($this->codename, 'url_rewrite.' . preg_replace('/[^A-Z0-9\._-]/i', '_', $url_info['data']['route']) . '.' . $information_id . '.' . $store_id . '.' . $language_id, $url_rewrite);
							
						if ($url_rewrite) {
							unset($url_info['data']['route']);
							unset($url_info['data']['information_id']);
						}
					}	
				}
			} else {
				if (!in_array($url_info['data']['route'], $setting['custom_page_exception_routes'])) {
					$url_rewrite = $this->d_cache->get($this->codename, 'url_rewrite.' . preg_replace('/[^A-Z0-9\._-]/i', '_', $url_info['data']['route']) . '.' . $store_id . '.' . $language_id);
					
					if ($url_rewrite) {
						unset($url_info['data']['route']);
					} elseif ($url_rewrite === false) {
						$url_rewrite = '';
							
						$route = $url_info['data']['route'];
						
						$field_data = array(
							'field_code' => 'url_keyword',
							'filter' => array(
								'route' => $route,
								'store_id' => $store_id,
								'language_id' => $language_id
							)
						);
			
						$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
								
						if ($url_keywords) {
							$url_keyword = $url_keywords[$route][$store_id][$language_id];
								
							if ($url_keyword) {
								if (substr($url_keyword, 0, 1) == '/') {
									$url_keyword = substr($url_keyword, 1, strlen($url_keyword) - 1);
								}
						
								$url_rewrite .= '/' . $url_keyword;	
							}
						}
						
						if ($url_rewrite && $setting['multi_language_sub_directory']['status'] && isset($setting['multi_language_sub_directory']['name'][$language_id]) && $setting['multi_language_sub_directory']['name'][$language_id]) {
							$url_rewrite = '/' . $setting['multi_language_sub_directory']['name'][$language_id] . $url_rewrite;
						}
							
						$this->d_cache->set($this->codename, 'url_rewrite.' . preg_replace('/[^A-Z0-9\._-]/i', '_', $url_info['data']['route']) . '.' . $store_id . '.' . $language_id, $url_rewrite);
							
						if ($url_rewrite) {
							unset($url_info['data']['route']);
						}
					}
				}
			}

			if ($url_rewrite) {			
				$url_info['path'] = str_replace('/index.php', '', $url_info['path']) . $url_rewrite;
				
				$url = $url_info['scheme'] . $url_info['host'] . $url_info['port'] . $url_info['path'];
				
				if ($url_info['data']) {
					$url .= '?' . http_build_query($url_info['data'], '', '&amp;');
				}
			}			
		}
		
		return $url;
	}
		
	public function language_language() {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status && isset($this->request->post['redirect'])) {
			$this->request->post['redirect'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getURLForLanguage($this->request->post['redirect'], $this->session->data['language']);
		}
	}
	
	public function header_after($html) {
		$this->load->model('extension/module/' . $this->codename);
				
		// Setting
		$_config = new Config();
		$_config->load($this->config_file);
		$config_setting = ($_config->get($this->codename . '_setting')) ? $_config->get($this->codename . '_setting') : array();
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		$setting = ($this->config->get('module_' . $this->codename . '_setting')) ? $this->config->get('module_' . $this->codename . '_setting') : array();
		
		if (!empty($setting)) {
			$config_setting = array_replace_recursive($config_setting, $setting);
		}
		
		$setting = $config_setting;
						
		if ($status && file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			if (isset($this->request->get['route'])) {
				if ($this->request->get['route'] == 'product/category') {
					if (isset($this->request->get['path'])) {
						$parts = explode('_', (string)$this->request->get['path']);
						$category_id = (int)array_pop($parts);
					} else {
						$category_id = 0;
					}
				
					if ($category_id) {
						$route = $this->request->get['route'];
						$args = 'path=' . $category_id;
						
						foreach ($this->request->get as $key => $value) {
							if (($key != 'route') && ($key != '_route_') && ($key != 'path')) {
								$args .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
							}
						}
					}
				} elseif ($this->request->get['route'] == 'product/product') {
					if (isset($this->request->get['product_id'])) {
						$product_id = (int)$this->request->get['product_id'];
					} else {
						$product_id = 0;
					}
				
					if ($product_id) {
						$route = $this->request->get['route'];
						$args = 'product_id=' . $product_id;
						
						foreach ($this->request->get as $key => $value) {
							if (($key != 'route') && ($key != '_route_') && ($key != 'product_id')) {
								$args .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
							}
						}
					}
				} elseif ($this->request->get['route'] == 'product/manufacturer/info') {
					if (isset($this->request->get['manufacturer_id'])) {
						$manufacturer_id = (int)$this->request->get['manufacturer_id'];
					} else {
						$manufacturer_id = 0;
					}
								
					if ($manufacturer_id) {
						$route = $this->request->get['route'];
						$args = 'manufacturer_id=' . $manufacturer_id;
						
						foreach ($this->request->get as $key => $value) {
							if (($key != 'route') && ($key != '_route_') && ($key != 'manufacturer_id')) {
								$args .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
							}
						}
					}
				} elseif ($this->request->get['route'] == 'information/information') {
					if (isset($this->request->get['information_id'])) {
						$information_id = (int)$this->request->get['information_id'];
					} else {
						$information_id = 0;
					}	
					
					if ($information_id) {
						$route = $this->request->get['route'];
						$args = 'information_id=' . $information_id;
						
						foreach ($this->request->get as $key => $value) {
							if (($key != 'route') && ($key != '_route_') && ($key != 'information_id')) {
								$args .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
							}
						}
					}
				} elseif ($this->request->get['route'] == 'product/manufacturer') {
					$route = $this->request->get['route'];
					$args = '';
				} elseif ($this->request->get['route'] == 'product/search') {
					$route = $this->request->get['route'];
					$args = '';
						
					foreach ($this->request->get as $key => $value) {
						if (($key != 'route') && ($key != '_route_')) {
							$args .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
						}
					}
				} elseif ($this->request->get['route'] == 'product/special') {
					$route = $this->request->get['route'];
					$args = '';
									
					foreach ($this->request->get as $key => $value) {
						if (($key != 'route') && ($key != '_route_')) {
							$args .= '&' . $key . '=' . urlencode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
						}
					}
				} elseif (preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $this->request->get['route'])) {
					$custom_page_exception_routes = $this->load->controller('extension/module/d_seo_module_url/getCustomPageExceptionRoutes');
					$custom_page_exception_routes = array_merge($custom_page_exception_routes, array('error/not_found'));
					
					if (!in_array($this->request->get['route'], $custom_page_exception_routes)) {
						$route = $this->request->get['route'];
						$args = '';
					}
				}
				
				$html_links = '';
				$alternate_links = array();
								
				$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
							
				foreach ($languages as $language) {
					if (isset($route) && isset($args)) {
						$config_language_id = $this->config->get('config_language_id');
						$this->config->set('config_language_id', $language['language_id']);	
						$alternate_link = $this->url->link($route, $args, true);
						
						if (!in_array($alternate_link, $alternate_links)) {
							$alternate_links[] = $alternate_link;
							$html_links .= '<link rel="alternate" hreflang="' . preg_replace('/-(.+?)+/', '', $language['code']) . '" href="' . $alternate_link . '" />' . "\n";
						}
						
						$this->config->set('config_language_id', $config_language_id);	
					}
				}	
				
				if (count($alternate_links) > 1) {
					$html_dom = new d_simple_html_dom();
					$html_dom->load((string)$html, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			
					foreach ($html_dom->find('head') as $element) {
						$element->innertext .= $html_links;
					}
				
					return (string)$html_dom;
				}
			}
		}
		
		return $html;
	}
	
	public function home_after($html) {	
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
				
		if ($status && file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {		
			$canonical_link = $this->url->link('common/home', '', true);
			
			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$html, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			
			if ($html_dom->find('link[rel="canonical"]')) {
				foreach ($html_dom->find('link[rel="canonical"]') as $element) {
					$element->setAttribute('href', $canonical_link);
				}
			} else {
				foreach ($html_dom->find('head') as $element) {
					$element->innertext .= '<link href="' . $canonical_link . '" rel="canonical" />' . "\n";
				}
			}
			
			return (string)$html_dom;
		}
		
		return $html;
	}
	
	public function category_after($html) {	
		// Setting
		$_config = new Config();
		$_config->load($this->config_file);
		$config_setting = ($_config->get($this->codename . '_setting')) ? $_config->get($this->codename . '_setting') : array();
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		$setting = ($this->config->get('module_' . $this->codename . '_setting')) ? $this->config->get('module_' . $this->codename . '_setting') : array();
		
		if (!empty($setting)) {
			$config_setting = array_replace_recursive($config_setting, $setting);
		}
		
		$setting = $config_setting;
				
		if ($status && file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			if (isset($this->request->get['path'])) {
				$parts = explode('_', (string)$this->request->get['path']);
				$category_id = (int)array_pop($parts);
			} else {
				$category_id = 0;
			}
			
			$url = '';
			
			if ($setting['sheet']['category']['canonical_link_page'] && isset($this->request->get['page']) && ($this->request->get['page'] > 1)) {
				$url .= '&page=' . urlencode(html_entity_decode($this->request->get['page'], ENT_QUOTES, 'UTF-8'));
			}
									
			$canonical_link = $this->url->link('product/category', 'path=' . $category_id . $url, true);
			
			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$html, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			
			if ($html_dom->find('link[rel="canonical"]')) {
				foreach ($html_dom->find('link[rel="canonical"]') as $element) {
					$element->setAttribute('href', $canonical_link);
				}
			} else {
				foreach ($html_dom->find('head') as $element) {
					$element->innertext .= '<link href="' . $canonical_link . '" rel="canonical" />' . "\n";
				}
			}
				
			return (string)$html_dom;
		}
		
		return $html;
	}
	
	public function manufacturer_list_after($html) {
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
				
		if ($status && file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {		
			$canonical_link = $this->url->link('product/manufacturer', '', true);
		
			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$html, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			
			if ($html_dom->find('link[rel="canonical"]')) {
				foreach ($html_dom->find('link[rel="canonical"]') as $element) {
					$element->setAttribute('href', $canonical_link);
				}
			} else {
				foreach ($html_dom->find('head') as $element) {
					$element->innertext .= '<link href="' . $canonical_link . '" rel="canonical" />' . "\n";
				}
			}
			
			return (string)$html_dom;
		}
		
		return $html;
	}
	
	public function manufacturer_info_after($html) {
		// Setting
		$_config = new Config();
		$_config->load($this->config_file);
		$config_setting = ($_config->get($this->codename . '_setting')) ? $_config->get($this->codename . '_setting') : array();
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		$setting = ($this->config->get('module_' . $this->codename . '_setting')) ? $this->config->get('module_' . $this->codename . '_setting') : array();
		
		if (!empty($setting)) {
			$config_setting = array_replace_recursive($config_setting, $setting);
		}
		
		$setting = $config_setting;
		
		if ($status && file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			if (isset($this->request->get['manufacturer_id'])) {
				$manufacturer_id = (int)$this->request->get['manufacturer_id'];
			} else {
				$manufacturer_id = 0;
			}
			
			$url = '';
			
			if ($setting['sheet']['manufacturer']['canonical_link_page'] && isset($this->request->get['page']) && ($this->request->get['page'] > 1)) {
				$url .= '&page=' . urlencode(html_entity_decode($this->request->get['page'], ENT_QUOTES, 'UTF-8'));
			}
					
			$canonical_link = $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer_id . $url, true);
			
			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$html, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			
			if ($html_dom->find('link[rel="canonical"]')) {
				foreach ($html_dom->find('link[rel="canonical"]') as $element) {
					$element->setAttribute('href', $canonical_link);
				}
			} else {
				foreach ($html_dom->find('head') as $element) {
					$element->innertext .= '<link href="' . $canonical_link . '" rel="canonical" />' . "\n";
				}
			}
				
			return (string)$html_dom;
		}
		
		return $html;
	}
	
	public function search_after($html) {
		// Setting
		$_config = new Config();
		$_config->load($this->config_file);
		$config_setting = ($_config->get($this->codename . '_setting')) ? $_config->get($this->codename . '_setting') : array();
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		$setting = ($this->config->get('module_' . $this->codename . '_setting')) ? $this->config->get('module_' . $this->codename . '_setting') : array();
		
		if (!empty($setting)) {
			$config_setting = array_replace_recursive($config_setting, $setting);
		}
		
		$setting = $config_setting;
				
		if ($status && file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			$url = '';

			if ($setting['sheet']['search']['canonical_link_search'] && isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if ($setting['sheet']['search']['canonical_link_tag'] && isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}
			
			if ($setting['sheet']['search']['canonical_link_page'] && isset($this->request->get['page']) && ($this->request->get['page'] > 1)) {
				$url .= '&page=' . urlencode(html_entity_decode($this->request->get['page'], ENT_QUOTES, 'UTF-8'));
			}
					
			$canonical_link = $this->url->link('product/search', $url, true);
			
			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$html, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			
			if ($html_dom->find('link[rel="canonical"]')) {
				foreach ($html_dom->find('link[rel="canonical"]') as $element) {
					$element->setAttribute('href', $canonical_link);
				}
			} else {
				foreach ($html_dom->find('head') as $element) {
					$element->innertext .= '<link href="' . $canonical_link . '" rel="canonical" />' . "\n";
				}
			}
				
			return (string)$html_dom;
		}
		
		return $html;
	}
	
	public function special_after($html) {
		// Setting
		$_config = new Config();
		$_config->load($this->config_file);
		$config_setting = ($_config->get($this->codename . '_setting')) ? $_config->get($this->codename . '_setting') : array();
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		$setting = ($this->config->get('module_' . $this->codename . '_setting')) ? $this->config->get('module_' . $this->codename . '_setting') : array();
		
		if (!empty($setting)) {
			$config_setting = array_replace_recursive($config_setting, $setting);
		}
		
		$setting = $config_setting;
				
		if ($status && file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			$url = '';
			
			if ($setting['sheet']['special']['canonical_link_page'] && isset($this->request->get['page']) && ($this->request->get['page'] > 1)) {
				$url .= '&page=' . urlencode(html_entity_decode($this->request->get['page'], ENT_QUOTES, 'UTF-8'));
			}
					
			$canonical_link = $this->url->link('product/special', $url, true);
				
			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$html, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			
			if ($html_dom->find('link[rel="canonical"]')) {
				foreach ($html_dom->find('link[rel="canonical"]') as $element) {
					$element->setAttribute('href', $canonical_link);
				}
			} else {
				foreach ($html_dom->find('head') as $element) {
					$element->innertext .= '<link href="' . $canonical_link . '" rel="canonical" />' . "\n";
				}
			}
				
			return (string)$html_dom;
		}
		
		return $html;
	}
	
	public function field_config() {
		$_language = new Language();
		$_language->load($this->route);
		
		$_config = new Config();
		$_config->load($this->config_file);
		$field_setting = ($_config->get($this->codename . '_field_setting')) ? $_config->get($this->codename . '_field_setting') : array();

		foreach ($field_setting['sheet'] as $sheet) {			
			foreach ($sheet['field'] as $field) {
				if (substr($field['name'], 0, strlen('text_')) == 'text_') {
					$field_setting['sheet'][$sheet['code']]['field'][$field['code']]['name'] = $_language->get($field['name']);
				}
				
				if (substr($field['description'], 0, strlen('help_')) == 'help_') {
					$field_setting['sheet'][$sheet['code']]['field'][$field['code']]['description'] = $_language->get($field['description']);
				}
			}
		}
					
		return $field_setting;
	}
	
	public function field_elements($filter_data) {
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_' . $this->codename}->getFieldElements($filter_data);
	}
}