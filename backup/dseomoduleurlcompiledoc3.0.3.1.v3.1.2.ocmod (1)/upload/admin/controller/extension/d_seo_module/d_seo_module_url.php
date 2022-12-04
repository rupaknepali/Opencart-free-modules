<?php
class ControllerExtensionDSEOModuleDSEOModuleURL extends Controller {
	private $codename = 'd_seo_module_url';
	private $route = 'extension/d_seo_module/d_seo_module_url';
	private $config_file = 'd_seo_module_url';
	private $error = array();
		
	/*
	*	Functions for SEO Module.
	*/	
	public function header_menu() {
		$_language = new Language();
		$_language->load($this->route);
		
		$this->load->model('setting/setting');
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$data['url_token'] = '';
		
		if (isset($this->session->data['token'])) {
			$data['url_token'] .= 'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$data['url_token'] .= 'user_token=' . $this->session->data['user_token'];
		}
		
		// Setting 						
		$this->config->load($this->config_file);
		$data['setting'] = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
				
		$setting = $this->model_setting_setting->getSetting('module_' . $this->codename);
		$status = isset($setting['module_' . $this->codename . '_status']) ? $setting['module_' . $this->codename . '_status'] : false;
		$setting = isset($setting['module_' . $this->codename . '_setting']) ? $setting['module_' . $this->codename . '_setting'] : array();
				
		if (!empty($setting)) {
			$data['setting'] = array_replace_recursive($data['setting'], $setting);
		}
										
		// Button
		$data['button_refresh_url_cache'] = $_language->get('button_refresh_url_cache');
		$data['button_clear_url_cache'] = $_language->get('button_clear_url_cache');
		
		$menu = array();

		if ($status && $this->user->hasPermission('access', 'extension/module/' . $this->codename)) {
			$menu[] = array(
				'html'	   		=> $this->load->view($this->route . '/header_menu', $data),
				'sort_order' 	=> 3
			);
		}

		return $menu;
	}
	
	public function menu() {
		$_language = new Language();
		$_language->load($this->route);
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .= 'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .= 'user_token=' . $this->session->data['user_token'];
		}
		
		$menu = array();

		if ($this->user->hasPermission('access', 'extension/module/' . $this->codename)) {
			$menu[] = array(
				'name'	   		=> $_language->get('heading_title_main'),
				'href'     		=> $this->url->link('extension/module/' . $this->codename, $url_token, true),
				'sort_order' 	=> 3,
				'children' 		=> array()
			);
		}

		return $menu;
	}
	
	public function dashboard() {		
		$dashboards = array();
		
		if ($this->user->hasPermission('access', 'extension/dashboard/d_seo_module_url_keyword')) {
			$dashboards[] = array(
				'html' 			=> $this->load->controller('extension/dashboard/d_seo_module_url_keyword/dashboard'),
				'width' 		=> 12,
				'sort_order' 	=> 40
			);
		}

		if ($this->user->hasPermission('access', 'extension/dashboard/d_seo_module_url_redirect')) {		
			$dashboards[] = array(
				'html' 			=> $this->load->controller('extension/dashboard/d_seo_module_url_redirect/dashboard'),
				'width' 		=> 12,
				'sort_order' 	=> 41
			);
		}

		return $dashboards;
	}
		
	public function language_add_language($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->addLanguage($data);
	}
	
	public function language_delete_language($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->deleteLanguage($data);
	}
	
	public function setting_tab_general_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		
		if (isset($field_info['sheet']['custom_page']['field'])) {
			$data['fields'] = $field_info['sheet']['custom_page']['field'];
		} else {
			$data['fields'] = array();
		}
		
		$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();
					
		if (isset($this->request->post['url_keyword'])) {
			$data['url_keyword'] = $this->request->post['url_keyword'];
		} else {
			$data['url_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getHomeURLKeyword();
		}
		
		$html_tab_general_language = array();
				
		foreach ($languages as $language) {
			$data['language_id'] = $language['language_id'];
		
			$html_tab_general_language[$data['language_id']] = $this->load->view($this->route . '/setting_tab_general_language', $data);
		}
		
		return $html_tab_general_language;
	}
	
	public function setting_validate($error) {
		if (isset($this->request->post['url_keyword'])) {		
			$_language = new Language();
			$_language->load($this->route);
				
			foreach ($this->request->post['url_keyword'] as $language_id => $url_keyword) {
				if (trim($url_keyword)) {								
					$field_data = array(
						'field_code' => 'url_keyword',
						'filter' => array(
							'store_id' => '0',
							'keyword' => $url_keyword
						)
					);
			
					$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
									
					if ($url_keywords) {
						foreach ($url_keywords as $route => $store_url_keywords) {
							if ($route != 'common/home') {
								$error['url_keyword'][$language_id] = sprintf($_language->get('error_url_keyword_exists'), $url_keyword);
							}
						}
					}
				}
			}
			
			$this->config->set($this->codename . '_error', $error);
		}
				
		return $error;
	}

	public function setting_edit_setting($data) {
		$this->load->model($this->route);
				
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveHomeURLKeyword($data);
	}
	
	public function store_form_tab_general_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		
		if (isset($field_info['sheet']['custom_page']['field'])) {
			$data['fields'] = $field_info['sheet']['custom_page']['field'];
		} else {
			$data['fields'] = array();
		}
		
		$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();
							
		if (isset($this->request->post['url_keyword'])) {
			$data['url_keyword'] = $this->request->post['url_keyword'];
		} elseif (isset($this->request->get['store_id'])) {
			$data['url_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getHomeURLKeyword($this->request->get['store_id']);
		} else {
			$data['url_keyword'] = array();
		}
					
		$html_tab_general_language = array();
						
		foreach ($languages as $language) {
			$data['language_id'] = $language['language_id'];
		
			$html_tab_general_language[$data['language_id']] = $this->load->view($this->route . '/store_form_tab_general_language', $data);
		}
		
		return $html_tab_general_language;
	}
		
	public function store_validate_form($error) {		
		if (isset($this->request->post['url_keyword']) && isset($this->request->get['store_id'])) {		
			$_language = new Language();
			$_language->load($this->route);
			
			foreach ($this->request->post['url_keyword'] as $language_id => $url_keyword) {
				if (trim($url_keyword)) {								
					$field_data = array(
						'field_code' => 'url_keyword',
						'filter' => array(
							'store_id' => $this->request->get['store_id'],
							'keyword' => $url_keyword
						)
					);
			
					$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
									
					if ($url_keywords) {
						foreach ($url_keywords as $route => $store_url_keywords) {
							if ($route != 'common/home') {
								$error['url_keyword'][$language_id] = sprintf($_language->get('error_url_keyword_exists'), $url_keyword);
							}
						}
					}
				}
			}
						
			$this->config->set($this->codename . '_error', $error);
		}
				
		return $error;
	}
	
	public function store_add_store($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveHomeURLKeyword($data);
	}
	
	public function store_edit_store($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveHomeURLKeyword($data);
	}
	
	public function store_delete_store($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->deleteStore($data);
	}
		
	public function category_form_tab_general_language() {
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		
		if (isset($field_info['sheet']['category']['field'])) {
			$data['fields'] = $field_info['sheet']['category']['field'];
		} else {
			$data['fields'] = array();
		}
		
		$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();
		
		if (isset($this->request->post['url_keyword'])) {
			$data['url_keyword'] = $this->request->post['url_keyword'];
		} elseif (isset($this->request->get['category_id'])) {
			$data['url_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getCategoryURLKeyword($this->request->get['category_id']);
		} else {
			$data['url_keyword'] = array();
		}
		
		$data['store_id'] = 0;
		
		$html_tab_general_language = array();
					
		foreach ($languages as $language) {
			$data['language_id'] = $language['language_id'];
		
			$html_tab_general_language[$data['language_id']] = $this->load->view($this->route . '/category_form_tab_general_language', $data);
		}
				
		return $html_tab_general_language;
	}
	
	public function category_form_tab_general_store_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		
		if (isset($field_info['sheet']['category']['field'])) {
			$data['fields'] = $field_info['sheet']['category']['field'];
		} else {
			$data['fields'] = array();
		}
		
		$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();
		
		if (isset($this->request->post['url_keyword'])) {
			$data['url_keyword'] = $this->request->post['url_keyword'];
		} elseif (isset($this->request->get['category_id'])) {
			$data['url_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getCategoryURLKeyword($this->request->get['category_id']);
		} else {
			$data['url_keyword'] = array();
		}
		
		$html_tab_general_store_language = array();
		
		foreach ($stores as $store) {
			$data['store_id'] = $store['store_id'];		
		
			foreach ($languages as $language) {
				$data['language_id'] = $language['language_id'];
		
				$html_tab_general_store_language[$data['store_id']][$data['language_id']] = $this->load->view($this->route . '/category_form_tab_general_store_language', $data);
			}
		}
			
		return $html_tab_general_store_language;
	}
	
	public function category_form_script() {		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		return $this->load->view($this->route . '/category_form_script');
	}
	
	public function category_validate_form($error) {
		unset($error['keyword']);
		
		if (isset($this->request->post['url_keyword'])) {		
			$_language = new Language();
			$_language->load($this->route);
				
			foreach ($this->request->post['url_keyword'] as $store_id => $language_url_keyword) {
				foreach ($language_url_keyword as $language_id => $url_keyword) {
					if (trim($url_keyword)) {								
						$field_data = array(
							'field_code' => 'url_keyword',
							'filter' => array(
								'store_id' => $store_id,
								'keyword' => $url_keyword
							)
						);
			
						$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
									
						if ($url_keywords) {
							if (isset($this->request->get['category_id'])) {
								foreach ($url_keywords as $route => $store_url_keywords) {
									if ($route != 'category_id=' . $this->request->get['category_id']) {
										$error['url_keyword'][$store_id][$language_id] = sprintf($_language->get('error_url_keyword_exists'), $url_keyword);
									}
								}
							} else {
								$error['url_keyword'][$store_id][$language_id] = sprintf($_language->get('error_url_keyword_exists'), $url_keyword);
							}
						}
					}
				}
			}
		}
		
		$this->config->set($this->codename . '_error', $error);
				
		return $error;
	}
	
	public function category_add_category($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveCategoryURLKeyword($data);
	}
	
	public function category_edit_category($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveCategoryURLKeyword($data);
	}
	
	public function category_delete_category($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->deleteCategoryURLKeyword($data);
	}
					
	public function product_form_tab_general_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		
		if (isset($field_info['sheet']['product']['field'])) {
			$data['fields'] = $field_info['sheet']['product']['field'];
		} else {
			$data['fields'] = array();
		}
		
		$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();
		
		if (isset($this->request->post['url_keyword'])) {
			$data['url_keyword'] = $this->request->post['url_keyword'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['url_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getProductURLKeyword($this->request->get['product_id']);
		} else {
			$data['url_keyword'] = array();
		}
		
		$data['store_id'] = 0;
		
		$html_tab_general_language = array();
				
		foreach ($languages as $language) {
			$data['language_id'] = $language['language_id'];
		
			$html_tab_general_language[$data['language_id']] = $this->load->view($this->route . '/product_form_tab_general_language', $data);
		}
				
		return $html_tab_general_language;
	}
	
	public function product_form_tab_general_store_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		
		if (isset($field_info['sheet']['product']['field'])) {
			$data['fields'] = $field_info['sheet']['product']['field'];
		} else {
			$data['fields'] = array();
		}
		
		$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();
		
		if (isset($this->request->post['url_keyword'])) {
			$data['url_keyword'] = $this->request->post['url_keyword'];
		} elseif (isset($this->request->get['product_id'])) {
			$data['url_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getProductURLKeyword($this->request->get['product_id']);
		} else {
			$data['url_keyword'] = array();
		}
		
		$html_tab_general_store_language = array();
		
		foreach ($stores as $store) {
			$data['store_id'] = $store['store_id'];		
		
			foreach ($languages as $language) {
				$data['language_id'] = $language['language_id'];
		
				$html_tab_general_store_language[$data['store_id']][$data['language_id']] = $this->load->view($this->route . '/category_form_tab_general_store_language', $data);
			}
		}
				
		return $html_tab_general_store_language;
	}
	
	public function product_form_tab_links() {		
		$this->load->model($this->route);
				
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		
		if (isset($field_info['sheet']['product']['field'])) {
			$data['fields'] = $field_info['sheet']['product']['field'];
		} else {
			$data['fields'] = array();
		}
		
		$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();
		
		if (isset($this->request->get['product_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$category_info = $this->{'model_extension_d_seo_module_' . $this->codename}->getProductCategory($this->request->get['product_id']);
		}
		
		if (isset($this->request->post['category_id'])) {
			$data['category_id'] = $this->request->post['category_id'];
		} elseif (!empty($category_info)) {
			$data['category_id'] = $category_info['category_id'];
		} else {
			$data['category_id'] = 0;
		}
		
		if (isset($this->request->post['category_path'])) {
			$data['category_path'] = $this->request->post['category_path'];
		} elseif (!empty($category_info)) {
			$data['category_path'] = $category_info['category_path'];
		} else {
			$data['category_path'] = '';
		}
		
		return $this->load->view($this->route . '/product_form_tab_links', $data);
	}
	
	public function product_form_script() {	
		$_language = new Language();
		$_language->load($this->route);
			
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .= 'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .= 'user_token=' . $this->session->data['user_token'];
		}
		
		$data['url_token'] = $url_token;
		$data['text_none'] = $_language->get('text_none');
					
		return $this->load->view($this->route . '/product_form_script', $data);
	}
	
	public function product_validate_form($error) {
		unset($error['keyword']);
		
		if (isset($this->request->post['url_keyword'])) {		
			$_language = new Language();
			$_language->load($this->route);
				
			foreach ($this->request->post['url_keyword'] as $store_id => $language_url_keyword) {
				foreach ($language_url_keyword as $language_id => $url_keyword) {
					if (trim($url_keyword)) {								
						$field_data = array(
							'field_code' => 'url_keyword',
							'filter' => array(
								'store_id' => $store_id,
								'keyword' => $url_keyword
							)
						);
			
						$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
									
						if ($url_keywords) {
							if (isset($this->request->get['product_id'])) {
								foreach ($url_keywords as $route => $store_url_keywords) {
									if ($route != 'product_id=' . $this->request->get['product_id']) {
										$error['url_keyword'][$store_id][$language_id] = sprintf($_language->get('error_url_keyword_exists'), $url_keyword);
									}
								}
							} else {
								$error['url_keyword'][$store_id][$language_id] = sprintf($_language->get('error_url_keyword_exists'), $url_keyword);
							}
						}
					}
				}
			}
		}
		
		$this->config->set($this->codename . '_error', $error);
				
		return $error;
	}
	
	public function product_add_product($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveProductURLKeyword($data);
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveProductCategory($data);
	}
	
	public function product_edit_product($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveProductURLKeyword($data);
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveProductCategory($data);
	}
	
	public function product_delete_product($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->deleteProductURLKeyword($data);
		$this->{'model_extension_d_seo_module_' . $this->codename}->deleteProductCategory($data);
	}
	
	public function manufacturer_form_tab_general_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		
		if (isset($field_info['sheet']['manufacturer']['field'])) {
			$data['fields'] = $field_info['sheet']['manufacturer']['field'];
		} else {
			$data['fields'] = array();
		}
		
		$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();
		
		if (isset($this->request->post['url_keyword'])) {
			$data['url_keyword'] = $this->request->post['url_keyword'];
		} elseif (isset($this->request->get['manufacturer_id'])) {
			$data['url_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getManufacturerURLKeyword($this->request->get['manufacturer_id']);
		} else {
			$data['url_keyword'] = array();
		}
		
		$data['store_id'] = 0;
		
		$html_tab_general_language = array();
				
		foreach ($languages as $language) {
			$data['language_id'] = $language['language_id'];
		
			$html_tab_general_language[$data['language_id']] = $this->load->view($this->route . '/manufacturer_form_tab_general_language', $data);
		}
				
		return $html_tab_general_language;
	}
	
	public function manufacturer_form_tab_general_store_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		
		if (isset($field_info['sheet']['manufacturer']['field'])) {
			$data['fields'] = $field_info['sheet']['manufacturer']['field'];
		} else {
			$data['fields'] = array();
		}
		
		$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();
		
		if (isset($this->request->post['url_keyword'])) {
			$data['url_keyword'] = $this->request->post['url_keyword'];
		} elseif (isset($this->request->get['manufacturer_id'])) {
			$data['url_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getManufacturerURLKeyword($this->request->get['manufacturer_id']);
		} else {
			$data['url_keyword'] = array();
		}
		
		$html_tab_general_store_language = array();
		
		foreach ($stores as $store) {
			$data['store_id'] = $store['store_id'];		
		
			foreach ($languages as $language) {
				$data['language_id'] = $language['language_id'];
		
				$html_tab_general_store_language[$data['store_id']][$data['language_id']] = $this->load->view($this->route . '/category_form_tab_general_store_language', $data);
			}
		}
				
		return $html_tab_general_store_language;
	}
	
	public function manufacturer_form_script() {		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		return $this->load->view($this->route . '/manufacturer_form_script');
	}
	
	public function manufacturer_validate_form($error) {
		unset($error['keyword']);
		
		if (isset($this->request->post['url_keyword'])) {		
			$_language = new Language();
			$_language->load($this->route);
				
			foreach ($this->request->post['url_keyword'] as $store_id => $language_url_keyword) {
				foreach ($language_url_keyword as $language_id => $url_keyword) {
					if (trim($url_keyword)) {								
						$field_data = array(
							'field_code' => 'url_keyword',
							'filter' => array(
								'store_id' => $store_id,
								'keyword' => $url_keyword
							)
						);
			
						$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
									
						if ($url_keywords) {
							if (isset($this->request->get['manufacturer_id'])) {
								foreach ($url_keywords as $route => $store_url_keywords) {
									if ($route != 'manufacturer_id=' . $this->request->get['manufacturer_id']) {
										$error['url_keyword'][$store_id][$language_id] = sprintf($_language->get('error_url_keyword_exists'), $url_keyword);
										$error['warning'] = $_language->get('error_warning');
									}
								}
							} else {
								$error['url_keyword'][$store_id][$language_id] = sprintf($_language->get('error_url_keyword_exists'), $url_keyword);
								$error['warning'] = $_language->get('error_warning');
							}
						}
					}
				}
			}
		}
		
		$this->config->set($this->codename . '_error', $error);
				
		return $error;
	}
	
	public function manufacturer_add_manufacturer($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveManufacturerURLKeyword($data);
	}
	
	public function manufacturer_edit_manufacturer($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveManufacturerURLKeyword($data);
	}
	
	public function manufacturer_delete_manufacturer($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->deleteManufacturerURLKeyword($data);
	}
	
	public function information_form_tab_general_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		
		if (isset($field_info['sheet']['information']['field'])) {
			$data['fields'] = $field_info['sheet']['information']['field'];
		} else {
			$data['fields'] = array();
		}
		
		$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();
		
		if (isset($this->request->post['url_keyword'])) {
			$data['url_keyword'] = $this->request->post['url_keyword'];
		} elseif (isset($this->request->get['information_id'])) {
			$data['url_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getInformationURLKeyword($this->request->get['information_id']);
		} else {
			$data['url_keyword'] = array();
		}
		
		$data['store_id'] = 0;
		
		$html_tab_general_language = array();
				
		foreach ($languages as $language) {
			$data['language_id'] = $language['language_id'];
		
			$html_tab_general_language[$data['language_id']] = $this->load->view($this->route . '/information_form_tab_general_language', $data);
		}
				
		return $html_tab_general_language;
	}
	
	public function information_form_tab_general_store_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
		
		if (isset($field_info['sheet']['information']['field'])) {
			$data['fields'] = $field_info['sheet']['information']['field'];
		} else {
			$data['fields'] = array();
		}
		
		$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();
		
		if (isset($this->request->post['url_keyword'])) {
			$data['url_keyword'] = $this->request->post['url_keyword'];
		} elseif (isset($this->request->get['information_id'])) {
			$data['url_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getInformationURLKeyword($this->request->get['information_id']);
		} else {
			$data['url_keyword'] = array();
		}
		
		$html_tab_general_store_language = array();
		
		foreach ($stores as $store) {
			$data['store_id'] = $store['store_id'];		
		
			foreach ($languages as $language) {
				$data['language_id'] = $language['language_id'];
		
				$html_tab_general_store_language[$data['store_id']][$data['language_id']] = $this->load->view($this->route . '/category_form_tab_general_store_language', $data);
			}
		}
				
		return $html_tab_general_store_language;
	}

	public function information_form_script() {		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		return $this->load->view($this->route . '/information_form_script');
	}
	
	public function information_validate_form($error) {
		unset($error['keyword']);
		
		if (isset($this->request->post['url_keyword'])) {		
			$_language = new Language();
			$_language->load($this->route);
				
			foreach ($this->request->post['url_keyword'] as $store_id => $language_url_keyword) {
				foreach ($language_url_keyword as $language_id => $url_keyword) {
					if (trim($url_keyword)) {								
						$field_data = array(
							'field_code' => 'url_keyword',
							'filter' => array(
								'store_id' => $store_id,
								'keyword' => $url_keyword
							)
						);
			
						$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
									
						if ($url_keywords) {
							if (isset($this->request->get['information_id'])) {
								foreach ($url_keywords as $route => $store_url_keywords) {
									if ($route != 'information_id=' . $this->request->get['information_id']) {
										$error['url_keyword'][$store_id][$language_id] = sprintf($_language->get('error_url_keyword_exists'), $url_keyword);
									}
								}
							} else {
								$error['url_keyword'][$store_id][$language_id] = sprintf($_language->get('error_url_keyword_exists'), $url_keyword);
							}
						}
					}
				}
			}
		}
		
		$this->config->set($this->codename . '_error', $error);
				
		return $error;
	}
		
	public function information_add_information($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveInformationURLKeyword($data);
	}
	
	public function information_edit_information($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveInformationURLKeyword($data);
	}
	
	public function information_delete_information($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->deleteInformationURLKeyword($data);
	}
	
	public function save($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		if (isset($data['new_setting']['module_d_seo_module_field_setting']['sheet']['category']['field']['url_keyword']['multi_store_status']) && isset($data['old_setting']['module_d_seo_module_field_setting']['sheet']['category']['field']['url_keyword']['multi_store_status']) && ($data['new_setting']['module_d_seo_module_field_setting']['sheet']['category']['field']['url_keyword']['multi_store_status'] != $data['old_setting']['module_d_seo_module_field_setting']['sheet']['category']['field']['url_keyword']['multi_store_status'])) {
			$cache_data = array(
				'route' => 'category_id=%',
				'store_id' => $data['store_id']
			);
			
			$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
		}
		
		if (isset($data['new_setting']['module_d_seo_module_field_setting']['sheet']['product']['field']['url_keyword']['multi_store_status']) && isset($data['old_setting']['module_d_seo_module_field_setting']['sheet']['product']['field']['url_keyword']['multi_store_status']) && ($data['new_setting']['module_d_seo_module_field_setting']['sheet']['product']['field']['url_keyword']['multi_store_status'] != $data['old_setting']['module_d_seo_module_field_setting']['sheet']['product']['field']['url_keyword']['multi_store_status'])) {
			$cache_data = array(
				'route' => 'product_id=%',
				'store_id' => $data['store_id']
			);
			
			$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
		}
		
		if (isset($data['new_setting']['module_d_seo_module_field_setting']['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) && isset($data['old_setting']['module_d_seo_module_field_setting']['sheet']['manufacturer']['field']['url_keyword']['multi_store_status']) && ($data['new_setting']['module_d_seo_module_field_setting']['sheet']['manufacturer']['field']['url_keyword']['multi_store_status'] != $data['old_setting']['module_d_seo_module_field_setting']['sheet']['manufacturer']['field']['url_keyword']['multi_store_status'])) {
			$cache_data = array(
				'route' => 'manufacturer_id=%',
				'store_id' => $data['store_id']
			);
			
			$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
		}
		
		if (isset($data['new_setting']['module_d_seo_module_field_setting']['sheet']['information']['field']['url_keyword']['multi_store_status']) && isset($data['old_setting']['module_d_seo_module_field_setting']['sheet']['information']['field']['url_keyword']['multi_store_status']) && ($data['new_setting']['module_d_seo_module_field_setting']['sheet']['information']['field']['url_keyword']['multi_store_status'] != $data['old_setting']['module_d_seo_module_field_setting']['sheet']['information']['field']['url_keyword']['multi_store_status'])) {
			$cache_data = array(
				'route' => 'information_id=%',
				'store_id' => $data['store_id']
			);
			
			$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
		}
		
		if (isset($data['new_setting']['module_d_seo_module_field_setting']['sheet']['custom_page']['field']['url_keyword']['multi_store_status']) && isset($data['old_setting']['module_d_seo_module_field_setting']['sheet']['custom_page']['field']['url_keyword']['multi_store_status']) && ($data['new_setting']['module_d_seo_module_field_setting']['sheet']['custom_page']['field']['url_keyword']['multi_store_status'] != $data['old_setting']['module_d_seo_module_field_setting']['sheet']['custom_page']['field']['url_keyword']['multi_store_status'])) {
			$cache_data = array(
				'route' => '%/%',
				'store_id' => $data['store_id']
			);
			
			$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
		}
	}
	
	public function control_extensions() {
		$_language = new Language();
		$_language->load($this->route);
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .= 'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .= 'user_token=' . $this->session->data['user_token'];
		}
		
		$control_extensions = array();

		$control_extensions[] = array(
			'code'				=> $this->codename,
			'name'	   			=> $_language->get('heading_title_main'),
			'image'				=> $this->codename . '/logo.svg',
			'href'     			=> $this->url->link('extension/module/' . $this->codename, $url_token, true),
			'sort_order' 		=> 3
		);
				
		return $control_extensions;
	}
	
	public function control_install_extension() {
		$this->load->controller('extension/module/' . $this->codename . '/installExtension');
			
		$json = $this->response->getOutput();
			
		if ($json) {
			$data = json_decode($json, true);
			
			return $data;
		}
		
		return false;
	}
	
	public function control_elements($data) {
		$_language = new Language();
		$_language->load($this->route);
		
		$this->load->model('extension/module/' . $this->codename);
		$this->load->model('setting/setting');
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .= 'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .= 'user_token=' . $this->session->data['user_token'];
		}
		
		// Setting 				
		$config_generator_setting = array();
										
		$installed_seo_url_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOURLExtensions();
		
		foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/url_generator_config');
			if ($info) $config_generator_setting = array_replace_recursive($config_generator_setting, $info);
		}
		
		$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $data['store_id']);
		$status = isset($setting['module_' . $this->codename . '_status']) ? $setting['module_' . $this->codename . '_status'] : false;
		$generator_setting = isset($setting['module_' . $this->codename . '_generator_setting']) ? $setting['module_' . $this->codename . '_generator_setting'] : array();
		$setting = isset($setting['module_' . $this->codename . '_setting']) ? $setting['module_' . $this->codename . '_setting'] : array();
		
		if (!empty($generator_setting)) {
			$config_generator_setting = array_replace_recursive($config_generator_setting, $generator_setting);
		}
		
		$generator_setting = $config_generator_setting;
		
		$control_elements = array();
		
		if (!$status) {
			$control_elements[] = array(
				'extension_code' 		=> $this->codename,
				'extension_name' 		=> $_language->get('heading_title_main'),
				'element_code'			=> 'enable_status',
				'name'					=> $_language->get('text_enable_status'),
				'description'			=> $_language->get('help_enable_status'),
				'confirm'				=> false,
				'href'					=> $this->url->link('extension/module/' . $this->codename . '/setting', $url_token, true),
				'implemented'			=> isset($setting['control_element']['enable_status']['implemented']) ? 1 : 0,
				'weight'				=> 1
			);
		}
		
		$generator_setting['sheet'] = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($generator_setting['sheet'], 'sort_order');
						
		foreach ($generator_setting['sheet'] as $sheet) {
			if (isset($sheet['code']) && isset($sheet['name']) && (isset($sheet['field']))) {				
				$sheet['field'] = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($sheet['field'], 'sort_order');
				
				foreach ($sheet['field'] as $field) {
					if (isset($field['code']) && isset($field['name'])) {
						$control_elements[] = array(
							'extension_code' 		=> $this->codename,
							'extension_name' 		=> $_language->get('heading_title_main'),
							'element_code'			=> 'generate_' . $field['code'] . '_' . $sheet['code'],
							'name'					=> sprintf($_language->get('text_generate'), $sheet['name'], $field['name']),
							'description'			=> sprintf($_language->get('help_generate'), $field['name'], $sheet['name']),
							'confirm'				=> $_language->get('text_generate_confirm'),
							'href'					=> $this->url->link('extension/module/' . $this->codename . '/generator', $url_token, true),
							'implemented'			=> isset($setting['control_element']['generate_' . $field['code'] . '_' . $sheet['code']]['implemented']) ? 1 : 0,
							'weight'				=> 0.9
						);
					}
				}
			}
		}
		
		$control_elements[] = array(
			'extension_code' 		=> $this->codename,
			'extension_name' 		=> $_language->get('heading_title_main'),
			'element_code'			=> 'create_default_url_elements',
			'name'					=> $_language->get('text_create_default_url_keywords'),
			'description'			=> $_language->get('help_create_default_url_keywords'),
			'confirm'				=> $_language->get('text_create_default_url_keywords_confirm'),
			'href'					=> $this->url->link('extension/module/' . $this->codename . '/url_keyword', $url_token . '&sheet_code=custom_page', true),
			'implemented'			=> isset($setting['control_element']['create_default_url_elements']['implemented']) ? 1 : 0,
			'weight'				=> 0.5
		);
								
		return $control_elements;
	}
	
	public function control_execute_element($data) {				
		$this->load->model('extension/module/' . $this->codename);
		$this->load->model('setting/setting');
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		// Setting 				
		$config_generator_setting = array();
										
		$installed_seo_url_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOURLExtensions();
		
		foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/url_generator_config');
			if ($info) $config_generator_setting = array_replace_recursive($config_generator_setting, $info);
		}
		
		$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $data['store_id']);
		$status = isset($setting['module_' . $this->codename . '_status']) ? $setting['module_' . $this->codename . '_status'] : false;
		$generator_setting = isset($setting['module_' . $this->codename . '_generator_setting']) ? $setting['module_' . $this->codename . '_generator_setting'] : array();
						
		if (!empty($generator_setting)) {
			$config_generator_setting = array_replace_recursive($config_generator_setting, $generator_setting);
		}
		
		$generator_setting = $config_generator_setting;
				
		if ($data['element_code'] == 'enable_status') {
			$setting['module_' . $this->codename . '_status'] = 1;
			$setting['module_' . $this->codename . '_setting']['control_element']['enable_status']['implemented'] = 1;
			
			$this->model_setting_setting->editSetting('module_' . $this->codename, $setting, $data['store_id']);
		}
		
		if (strpos($data['element_code'], 'generate_url_keyword') === 0) {
			$sheet_code = str_replace('generate_url_keyword_', '', $data['element_code']);
			$field_code = 'url_keyword';
		}
			
		if (isset($sheet_code) && isset($field_code) && isset($generator_setting['sheet'][$sheet_code]['field'][$field_code])) {
			$field_setting = $generator_setting['sheet'][$sheet_code]['field'][$field_code];
			$field_data = array();
						
			if (isset($field_setting['multi_language']) && $field_setting['multi_language']) {
				foreach ($languages as $language) {
					if (isset($field_setting['template'][$language['language_id']])) {
						$field_data['template'][$language['language_id']] = $field_setting['template'][$language['language_id']];
					} elseif (isset($field_setting['template_default'])) {
						$field_data['template'][$language['language_id']] = $field_setting['template_default'];
					}
				}
			} else {
				if (isset($field_setting['template'])) {
					$field_data['template'] = $field_setting['template'];
				} elseif (isset($field_settingd['template_default'])) {
					$field_data['template'] = $field_setting['template_default'];
				}
			}
				
			if (isset($field_setting['translit_symbol_status'])) {
				$field_data['translit_symbol_status'] = $field_setting['translit_symbol_status'];
			}
				
			if (isset($field_setting['translit_language_symbol_status'])) {
				$field_data['translit_language_symbol_status'] = $field_setting['translit_language_symbol_status'];
			}
				
			if (isset($field_setting['transform_language_symbol_id'])) {
				$field_data['transform_language_symbol_id'] = $field_setting['transform_language_symbol_id'];
			}
				
			if (isset($field_setting['overwrite'])) {
				$field_data['overwrite'] = $field_setting['overwrite'];
			}				
						
			$generator_data['store_id'] = $data['store_id'];
			$generator_data['sheet'][$sheet_code]['field'][$field_code] = $field_data;	
		
			foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/url_generator_generate_fields', $generator_data);
				
				if (isset($info['error'])) {
					$this->error = array_replace_recursive($this->error, $info['error']);
				}			
			}
			
			if (!$this->error) {
				$setting['module_' . $this->codename . '_setting']['control_element']['generate_' . $field_code . '_' . $sheet_code]['implemented'] = 1;
			
				$this->model_setting_setting->editSetting('module_' . $this->codename, $setting, $data['store_id']);
			}
		}
		
		if ($data['element_code'] == 'create_default_url_elements') {
			$this->config->load($this->config_file);
			$config_setting = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
								
			$this->{'model_extension_module_' . $this->codename}->createDefaultURLElements($config_setting['default_url_keywords'], $data['store_id']);
			
			$setting['module_' . $this->codename . '_setting']['control_element']['create_default_url_elements']['implemented'] = 1;
						
			$this->model_setting_setting->editSetting('module_' . $this->codename, $setting, $data['store_id']);
		}
				
		$result['error'] = $this->error;
		
		return $result;
	}
	
	public function clear_cache() {
		$this->load->model('extension/module/' . $this->codename);
										
		return $this->{'model_extension_module_' . $this->codename}->clearURLCache();
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