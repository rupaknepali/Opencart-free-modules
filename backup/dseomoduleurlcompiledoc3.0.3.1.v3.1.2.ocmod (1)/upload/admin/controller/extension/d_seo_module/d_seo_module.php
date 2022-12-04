<?php
class ControllerExtensionDSEOModuleDSEOModule extends Controller {
	private $codename = 'd_seo_module';
	private $route = 'extension/d_seo_module/d_seo_module';
	private $config_file = 'd_seo_module';
	private $error = array();
			
	/*
	*	Functions for SEO Module.
	*/	
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
				'sort_order' 	=> 1,
				'children' 		=> array()
			);
		}

		return $menu;
	}
	
	public function dashboard() {
		$dashboards = array();
		
		if ($this->user->hasPermission('access', 'extension/dashboard/d_seo_module_target_keyword')) {
			$dashboards[] = array(
				'html' 			=> $this->load->controller('extension/dashboard/d_seo_module_target_keyword/dashboard'),
				'width' 		=> 12,
				'sort_order' 	=> 20
			);
		}

		return $dashboards;
	}
	
	public function language_add_language($data) {
		$this->load->model($this->route);
		
		$this->load->controller('extension/module/d_seo_module/update');
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->addLanguage($data);
	}
		
	public function language_delete_language($data) {
		$this->load->model($this->route);
		
		$this->load->controller('extension/module/d_seo_module/update');

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
		
		$this->load->controller('extension/module/d_seo_module/update');
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
				
		if (isset($field_info['sheet']['custom_page']['field'])) {
			$data['fields'] = $field_info['sheet']['custom_page']['field'];
		} else {
			$data['fields'] = array();
		}
						
		if (isset($this->request->post['target_keyword'])) {
			$data['target_keyword'] = $this->request->post['target_keyword'];
		} else {
			$data['target_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getHomeTargetKeyword();
		}
			
		$html_tab_general_language = array();
						
		foreach ($languages as $language) {
			$data['language_id'] = $language['language_id'];
			
			if (isset($data['target_keyword'][$data['language_id']])) {
				foreach ($data['target_keyword'][$data['language_id']] as $sort_order => $keyword) {
					$field_data = array(
						'field_code' => 'target_keyword',
						'filter' => array(
							'store_id' => 0,
							'keyword' => $keyword
						)
					);
			
					$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
					
					if ($target_keywords) {
						$store_target_keywords = reset($target_keywords);
							
						if ((count($target_keywords) > 1) || (count(reset($store_target_keywords)) > 1)) {
							$data['target_keyword_duplicate'][$data['language_id']][$sort_order] = 1;
						}
					}
				}				
			}
		
			$html_tab_general_language[$data['language_id']] = $this->load->view($this->route . '/setting_tab_general_language', $data);
		}
		
		return $html_tab_general_language;
	}
	
	public function setting_style() {		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		return $this->load->view($this->route . '/setting_style');
	}
	
	public function setting_script() {			
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
		
		$data['route'] = $this->route;
		$data['url_token'] = $url_token;
		$data['store_id'] = 0;
				
		return $this->load->view($this->route . '/setting_script', $data);
	}
	
	public function setting_edit_setting($data) {
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveHomeTargetKeyword($data);
		
		if ($data['config_seo_url']) {
			$setting = $this->model_setting_setting->getSetting('module_' . $this->codename);
			$setting['module_' . $this->codename . '_setting']['control_element']['enable_seo_url']['implemented'] = 1;
			
			$this->model_setting_setting->editSetting('module_' . $this->codename, $setting);
		}
	}
	
	public function store_form_tab_general_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$this->load->controller('extension/module/d_seo_module/update');
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
				
		if (isset($field_info['sheet']['custom_page']['field'])) {
			$data['fields'] = $field_info['sheet']['custom_page']['field'];
		} else {
			$data['fields'] = array();
		}
									
		if (isset($this->request->post['target_keyword'])) {
			$data['target_keyword'] = $this->request->post['target_keyword'];
		} elseif (isset($this->request->get['store_id'])) {
			$data['target_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getHomeTargetKeyword($this->request->get['store_id']);
		} else {
			$data['target_keyword'] = array();
		}
							
		$html_tab_general_language = array();
								
		foreach ($languages as $language) {
			$data['language_id'] = $language['language_id'];
			
			if (isset($data['target_keyword'][$data['language_id']]) && isset($this->request->get['store_id'])) {
				foreach ($data['target_keyword'][$data['language_id']] as $sort_order => $keyword) {
					$field_data = array(
						'field_code' => 'target_keyword',
						'filter' => array(
							'store_id' => $this->request->get['store_id'],
							'keyword' => $keyword
						)
					);
			
					$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
					
					if ($target_keywords) {
						$store_target_keywords = reset($target_keywords);
							
						if ((count($target_keywords) > 1) || (count(reset($store_target_keywords)) > 1)) {
							$data['target_keyword_duplicate'][$data['language_id']][$sort_order] = 1;
						}
					}
				}				
			}
		
			$html_tab_general_language[$data['language_id']] = $this->load->view($this->route . '/store_form_tab_general_language', $data);
		}
		
		return $html_tab_general_language;
	}
	
	public function store_form_style() {		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		return $this->load->view($this->route . '/store_form_style');
	}
	
	public function store_form_script() {	
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
		
		$data['route'] = $this->route;
		$data['url_token'] = $url_token;
		
		if (isset($this->request->get['store_id'])) {
			$data['store_id'] = $this->request->get['store_id'];
		} else {
			$data['store_id'] = -1;
		}
				
		return $this->load->view($this->route . '/store_form_script', $data);
	}
	
	public function store_add_store($data) {
		$this->load->model($this->route);
		
		$this->load->controller('extension/module/d_seo_module/update');
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveHomeTargetKeyword($data);
	}
	
	public function store_edit_store($data) {
		$this->load->model($this->route);
		
		$this->load->controller('extension/module/d_seo_module/update');
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveHomeTargetKeyword($data);
	}
	
	public function store_delete_store($data) {
		$this->load->model($this->route);
		
		$this->load->controller('extension/module/d_seo_module/update');
		
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
		
		$this->load->controller('extension/module/d_seo_module/update');
		
		$field_info = $this->load->controller('extension/module/d_seo_module/getFieldInfo');
								
		if (isset($field_info['sheet']['category']['field'])) {
			$data['fields'] = $field_info['sheet']['category']['field'];
		} else {
			$data['fields'] = array();
		}
						
		if (isset($this->request->post['target_keyword'])) {
			$data['target_keyword'] = $this->request->post['target_keyword'];
		} elseif (isset($this->request->get['category_id'])) {	
			$data['target_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getCategoryTargetKeyword($this->request->get['category_id']);
		} else {
			$data['target_keyword'] = array();
		}
		
		$data['store_id'] = 0;
				
		$html_tab_general_language = array();
				
		foreach ($languages as $language) {
			$data['language_id'] = $language['language_id'];
			
			if (isset($data['target_keyword'][$data['store_id']][$data['language_id']])) {
				foreach ($data['target_keyword'][$data['store_id']][$data['language_id']] as $sort_order => $keyword) {
					$field_data = array(
						'field_code' => 'target_keyword',
						'filter' => array(
							'store_id' => $data['store_id'],
							'keyword' => $keyword
						)
					);
			
					$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
					
					if ($target_keywords) {
						$store_target_keywords = reset($target_keywords);
							
						if ((count($target_keywords) > 1) || (count(reset($store_target_keywords)) > 1)) {
							$data['target_keyword_duplicate'][$data['store_id']][$data['language_id']][$sort_order] = 1;
						}
					}
				}				
			}
			
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
		
		$this->load->controller('extension/module/d_seo_module/update');
				
		$field_info = $this->load->controller('extension/module/' . $this->codename . '/getFieldInfo');
				
		if (isset($field_info['sheet']['category']['field'])) {
			$data['fields'] = $field_info['sheet']['category']['field'];
		} else {
			$data['fields'] = array();
		}
		
		if (isset($this->request->post['target_keyword'])) {
			$data['target_keyword'] = $this->request->post['target_keyword'];
		} elseif (isset($this->request->get['category_id'])) {	
			$data['target_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getCategoryTargetKeyword($this->request->get['category_id']);
		} else {
			$data['target_keyword'] = array();
		}
		
		$html_tab_general_store_language = array();
		
		foreach ($stores as $store) {
			$data['store_id'] = $store['store_id'];
			
			foreach ($languages as $language) {
				$data['language_id'] = $language['language_id'];
			
				if (isset($data['target_keyword'][$data['store_id']][$data['language_id']])) {
					foreach ($data['target_keyword'][$data['store_id']][$data['language_id']] as $sort_order => $keyword) {
						$field_data = array(
							'field_code' => 'target_keyword',
							'filter' => array(
								'store_id' => $data['store_id'],
								'keyword' => $keyword
							)
						);
			
						$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
						
						if ($target_keywords) {
							$store_target_keywords = reset($target_keywords);
							
							if ((count($target_keywords) > 1) || (count(reset($store_target_keywords)) > 1)) {
								$data['target_keyword_duplicate'][$data['store_id']][$data['language_id']][$sort_order] = 1;
							}
						}
					}				
				}
				
				$html_tab_general_store_language[$data['store_id']][$data['language_id']] = $this->load->view($this->route . '/category_form_tab_general_store_language', $data);
			}
		}
				
		return $html_tab_general_store_language;
	}
	
	public function category_form_style() {		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		return $this->load->view($this->route . '/category_form_style');
	}
	
	public function category_form_script() {			
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
		
		$data['route'] = $this->route;
		$data['url_token'] = $url_token;
				
		return $this->load->view($this->route . '/category_form_script', $data);
	}
	
	public function category_add_category($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveCategoryTargetKeyword($data);
	}
	
	public function category_edit_category($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveCategoryTargetKeyword($data);
	}
	
	public function category_delete_category($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->deleteCategoryTargetKeyword($data);
	}
	
	public function product_form_tab_general_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$this->load->controller('extension/module/d_seo_module/update');
		
		$field_info = $this->load->controller('extension/module/' . $this->codename . '/getFieldInfo');
				
		if (isset($field_info['sheet']['product']['field'])) {
			$data['fields'] = $field_info['sheet']['product']['field'];
		} else {
			$data['fields'] = array();
		}
		
		if (isset($this->request->post['target_keyword'])) {
			$data['target_keyword'] = $this->request->post['target_keyword'];
		} elseif (isset($this->request->get['product_id'])) {	
			$data['target_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getProductTargetKeyword($this->request->get['product_id']);
		} else {
			$data['target_keyword'] = array();
		}
		
		$data['store_id'] = 0;
		
		$html_tab_general_language = array();
				
		foreach ($languages as $language) {
			$data['language_id'] = $language['language_id'];
			
			if (isset($data['target_keyword'][$data['store_id']][$data['language_id']])) {
				foreach ($data['target_keyword'][$data['store_id']][$data['language_id']] as $sort_order => $keyword) {
					$field_data = array(
						'field_code' => 'target_keyword',
						'filter' => array(
							'store_id' => $data['store_id'],
							'keyword' => $keyword
						)
					);
			
					$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
					
					if ($target_keywords) {
						$store_target_keywords = reset($target_keywords);
							
						if ((count($target_keywords) > 1) || (count(reset($store_target_keywords)) > 1)) {
							$data['target_keyword_duplicate'][$data['store_id']][$data['language_id']][$sort_order] = 1;
						}
					}
				}				
			}
					
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
		
		$this->load->controller('extension/module/d_seo_module/update');
				
		$field_info = $this->load->controller('extension/module/' . $this->codename . '/getFieldInfo');
				
		if (isset($field_info['sheet']['product']['field'])) {
			$data['fields'] = $field_info['sheet']['product']['field'];
		} else {
			$data['fields'] = array();
		}
		
		if (isset($this->request->post['target_keyword'])) {
			$data['target_keyword'] = $this->request->post['target_keyword'];
		} elseif (isset($this->request->get['product_id'])) {	
			$data['target_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getProductTargetKeyword($this->request->get['product_id']);
		} else {
			$data['target_keyword'] = array();
		}
		
		$html_tab_general_store_language = array();
		
		foreach ($stores as $store) {
			$data['store_id'] = $store['store_id'];
			
			foreach ($languages as $language) {
				$data['language_id'] = $language['language_id'];
			
				if (isset($data['target_keyword'][$data['store_id']][$data['language_id']])) {
					foreach ($data['target_keyword'][$data['store_id']][$data['language_id']] as $sort_order => $keyword) {
						$field_data = array(
							'field_code' => 'target_keyword',
							'filter' => array(
								'store_id' => $data['store_id'],
								'keyword' => $keyword
							)
						);
			
						$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
						
						if ($target_keywords) {
							$store_target_keywords = reset($target_keywords);
							
							if ((count($target_keywords) > 1) || (count(reset($store_target_keywords)) > 1)) {
								$data['target_keyword_duplicate'][$data['store_id']][$data['language_id']][$sort_order] = 1;
							}
						}
					}				
				}
				
				$html_tab_general_store_language[$data['store_id']][$data['language_id']] = $this->load->view($this->route . '/product_form_tab_general_store_language', $data);
			}
		}
				
		return $html_tab_general_store_language;
	}
	
	public function product_form_style() {		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		return $this->load->view($this->route . '/product_form_style');
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
		
		$data['route'] = $this->route;
		$data['url_token'] = $url_token;
		$data['text_none'] = $_language->get('text_none');
				
		return $this->load->view($this->route . '/product_form_script', $data);
	}
	
	public function product_add_product($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveProductTargetKeyword($data);
	}
	
	public function product_edit_product($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveProductTargetKeyword($data);
	}
	
	public function product_delete_product($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->deleteProductTargetKeyword($data);
	}
	
	public function manufacturer_form_tab_general_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$this->load->controller('extension/module/d_seo_module/update');
		
		$field_info = $this->load->controller('extension/module/' . $this->codename . '/getFieldInfo');
				
		if (isset($field_info['sheet']['manufacturer']['field'])) {
			$data['fields'] = $field_info['sheet']['manufacturer']['field'];
		} else {
			$data['fields'] = array();
		}
		
		if (isset($this->request->post['target_keyword'])) {
			$data['target_keyword'] = $this->request->post['target_keyword'];
		} elseif (isset($this->request->get['manufacturer_id'])) {
			$data['target_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getManufacturerTargetKeyword($this->request->get['manufacturer_id']);
		} else {
			$data['target_keyword'] = array();
		}
		
		$data['store_id'] = 0;
				
		$html_tab_general_language = array();
				
		foreach ($languages as $language) {
			$data['language_id'] = $language['language_id'];
			
			if (isset($data['target_keyword'][$data['store_id']][$data['language_id']])) {
				foreach ($data['target_keyword'][$data['store_id']][$data['language_id']] as $sort_order => $keyword) {
					$field_data = array(
						'field_code' => 'target_keyword',
						'filter' => array(
							'store_id' => $data['store_id'],
							'keyword' => $keyword
						)
					);
			
					$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
					
					if ($target_keywords) {
						$store_target_keywords = reset($target_keywords);
							
						if ((count($target_keywords) > 1) || (count(reset($store_target_keywords)) > 1)) {
							$data['target_keyword_duplicate'][$data['store_id']][$data['language_id']][$sort_order] = 1;
						}
					}
				}				
			}
			
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
		
		$this->load->controller('extension/module/d_seo_module/update');
				
		$field_info = $this->load->controller('extension/module/' . $this->codename . '/getFieldInfo');
				
		if (isset($field_info['sheet']['manufacturer']['field'])) {
			$data['fields'] = $field_info['sheet']['manufacturer']['field'];
		} else {
			$data['fields'] = array();
		}
		
		if (isset($this->request->post['target_keyword'])) {
			$data['target_keyword'] = $this->request->post['target_keyword'];
		} elseif (isset($this->request->get['manufacturer_id'])) {	
			$data['target_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getManufacturerTargetKeyword($this->request->get['manufacturer_id']);
		} else {
			$data['target_keyword'] = array();
		}
		
		$html_tab_general_store_language = array();
		
		foreach ($stores as $store) {
			$data['store_id'] = $store['store_id'];
			
			foreach ($languages as $language) {
				$data['language_id'] = $language['language_id'];
			
				if (isset($data['target_keyword'][$data['store_id']][$data['language_id']])) {
					foreach ($data['target_keyword'][$data['store_id']][$data['language_id']] as $sort_order => $keyword) {
						$field_data = array(
							'field_code' => 'target_keyword',
							'filter' => array(
								'store_id' => $data['store_id'],
								'keyword' => $keyword
							)
						);
			
						$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
						
						if ($target_keywords) {
							$store_target_keywords = reset($target_keywords);
							
							if ((count($target_keywords) > 1) || (count(reset($store_target_keywords)) > 1)) {
								$data['target_keyword_duplicate'][$data['store_id']][$data['language_id']][$sort_order] = 1;
							}
						}
					}				
				}
				
				$html_tab_general_store_language[$data['store_id']][$data['language_id']] = $this->load->view($this->route . '/manufacturer_form_tab_general_store_language', $data);
			}
		}
				
		return $html_tab_general_store_language;
	}
	
	public function manufacturer_form_style() {		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		return $this->load->view($this->route . '/manufacturer_form_style');
	}
	
	public function manufacturer_form_script() {	
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
		
		$data['route'] = $this->route;
		$data['url_token'] = $url_token;
		
		return $this->load->view($this->route . '/manufacturer_form_script', $data);
	}
	
	public function manufacturer_add_manufacturer($data) {
		$this->load->model($this->route);

		$this->{'model_extension_d_seo_module_' . $this->codename}->saveManufacturerTargetKeyword($data);
	}
	
	public function manufacturer_edit_manufacturer($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveManufacturerTargetKeyword($data);
	}
	
	public function manufacturer_delete_manufacturer($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->deleteManufacturerTargetKeyword($data);
	}
	
	public function information_form_tab_general_language() {		
		$this->load->model($this->route);
		$this->load->model('extension/module/' . $this->codename);
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$this->load->controller('extension/module/d_seo_module/update');
		
		$field_info = $this->load->controller('extension/module/' . $this->codename . '/getFieldInfo');
				
		if (isset($field_info['sheet']['information']['field'])) {
			$data['fields'] = $field_info['sheet']['information']['field'];
		} else {
			$data['fields'] = array();
		}
		
		if (isset($this->request->post['target_keyword'])) {
			$data['target_keyword'] = $this->request->post['target_keyword'];
		} elseif (isset($this->request->get['information_id'])) {
			$data['target_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getInformationTargetKeyword($this->request->get['information_id']);
		} else {
			$data['target_keyword'] = array();
		}
		
		$data['store_id'] = 0;
			
		$html_tab_general_language = array();
				
		foreach ($languages as $language) {
			$data['language_id'] = $language['language_id'];
			
			if (isset($data['target_keyword'][$data['store_id']][$data['language_id']])) {
				foreach ($data['target_keyword'][$data['store_id']][$data['language_id']] as $sort_order => $keyword) {
					$field_data = array(
						'field_code' => 'target_keyword',
						'filter' => array(
							'store_id' => $data['store_id'],
							'keyword' => $keyword
						)
					);
			
					$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
					
					if ($target_keywords) {
						$store_target_keywords = reset($target_keywords);
							
						if ((count($target_keywords) > 1) || (count(reset($store_target_keywords)) > 1)) {
							$data['target_keyword_duplicate'][$data['store_id']][$data['language_id']][$sort_order] = 1;
						}
					}
				}				
			}
			
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
		
		$this->load->controller('extension/module/d_seo_module/update');
				
		$field_info = $this->load->controller('extension/module/' . $this->codename . '/getFieldInfo');
		
		if (isset($field_info['sheet']['information']['field'])) {
			$data['fields'] = $field_info['sheet']['information']['field'];
		} else {
			$data['fields'] = array();
		}
		
		if (isset($this->request->post['target_keyword'])) {
			$data['target_keyword'] = $this->request->post['target_keyword'];
		} elseif (isset($this->request->get['information_id'])) {	
			$data['target_keyword'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getInformationTargetKeyword($this->request->get['information_id']);
		} else {
			$data['target_keyword'] = array();
		}
		
		$html_tab_general_store_language = array();
		
		foreach ($stores as $store) {
			$data['store_id'] = $store['store_id'];
			
			foreach ($languages as $language) {
				$data['language_id'] = $language['language_id'];
			
				if (isset($data['target_keyword'][$data['store_id']][$data['language_id']])) {
					foreach ($data['target_keyword'][$data['store_id']][$data['language_id']] as $sort_order => $keyword) {
						$field_data = array(
							'field_code' => 'target_keyword',
							'filter' => array(
								'store_id' => $data['store_id'],
								'keyword' => $keyword
							)
						);
			
						$target_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
						
						if ($target_keywords) {
							$store_target_keywords = reset($target_keywords);
							
							if ((count($target_keywords) > 1) || (count(reset($store_target_keywords)) > 1)) {
								$data['target_keyword_duplicate'][$data['store_id']][$data['language_id']][$sort_order] = 1;
							}
						}
					}				
				}
				
				$html_tab_general_store_language[$data['store_id']][$data['language_id']] = $this->load->view($this->route . '/information_form_tab_general_store_language', $data);
			}
		}
				
		return $html_tab_general_store_language;
	}
	
	public function information_form_style() {		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		return $this->load->view($this->route . '/information_form_style');
	}
	
	public function information_form_script() {	
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
		
		$data['route'] = $this->route;
		$data['url_token'] = $url_token;
				
		return $this->load->view($this->route . '/information_form_script', $data);
	}
	
	public function information_add_information($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveInformationTargetKeyword($data);
	}
	
	public function information_edit_information($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->saveInformationTargetKeyword($data);
	}
	
	public function information_delete_information($data) {
		$this->load->model($this->route);
		
		$this->{'model_extension_d_seo_module_' . $this->codename}->deleteInformationTargetKeyword($data);
	}
	
	public function control_setup_extension() {
		$this->load->controller('extension/module/' . $this->codename . '/setupExtension');
			
		$json = $this->response->getOutput();
			
		if ($json) {
			$data = json_decode($json, true);
			
			return $data;
		}
		
		return false;
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
		$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $data['store_id']);
		$status = isset($setting['module_' . $this->codename . '_status']) ? $setting['module_' . $this->codename . '_status'] : false;
		$setting = isset($setting['module_' . $this->codename . '_setting']) ? $setting['module_' . $this->codename . '_setting'] : array();
				
		$htaccess = $this->{'model_extension_module_' . $this->codename}->getFileData('htaccess');		
		$robots = $this->{'model_extension_module_' . $this->codename}->getFileData('robots');
						
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
		
		if (!$this->config->get('config_seo_url')) {
			$control_elements[] = array(
				'extension_code' 		=> $this->codename,
				'extension_name' 		=> $_language->get('heading_title_main'),
				'element_code'			=> 'enable_seo_url',
				'name'					=> $_language->get('text_enable_seo_url'),
				'description'			=> $_language->get('help_enable_seo_url'),
				'confirm'				=> false,
				'href'					=> $this->url->link('setting/setting', $url_token, true),
				'implemented'			=> isset($setting['control_element']['enable_seo_url']['implemented']) ? 1 : 0,
				'weight'				=> 0.95
			);
		}
		
		if (!$htaccess['status']) {
			$control_elements[] = array(
				'extension_code' 		=> $this->codename,
				'extension_name' 		=> $_language->get('heading_title_main'),
				'element_code'			=> 'enable_htaccess',
				'name'					=> $_language->get('text_enable_htaccess'),
				'description'			=> $_language->get('help_enable_htaccess'),
				'confirm'				=> false,
				'href'					=> $this->url->link('extension/module/' . $this->codename . '/setting', $url_token, true),
				'implemented'			=> isset($setting['control_element']['enable_htaccess']['implemented']) ? 1 : 0,
				'weight'				=> 0.95
			);
		}
		
		if (!$robots['status']) {
			$control_elements[] = array(
				'extension_code' 		=> $this->codename,
				'extension_name' 		=> $_language->get('heading_title_main'),
				'element_code'			=> 'enable_robots',
				'name'					=> $_language->get('text_enable_robots'),
				'description'			=> $_language->get('help_enable_robots'),
				'confirm'				=> false,
				'href'					=> $this->url->link('extension/module/' . $this->codename . '/setting', $url_token, true),
				'implemented'			=> isset($setting['control_element']['enable_robots']['implemented']) ? 1 : 0,
				'weight'				=> 0.6
			);
		}
						
		return $control_elements;
	}
	
	public function control_execute_element($data) {
		$this->load->model('extension/module/' . $this->codename);
		$this->load->model('setting/setting');
		
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$server = HTTPS_SERVER;
			$catalog = HTTPS_CATALOG;
		} else {
			$server = HTTP_SERVER;
			$catalog = HTTP_CATALOG;
		}
		
		$catalog_url_info = $this->{'model_extension_module_' . $this->codename}->getURLInfo($catalog);
		
		// Setting
		$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $data['store_id']);
										
		if ($data['element_code'] == 'enable_status') {
			$setting['module_' . $this->codename . '_status'] = 1;
			$setting['module_' . $this->codename . '_setting']['control_element']['enable_status']['implemented'] = 1;
			
			$this->model_setting_setting->editSetting('module_' . $this->codename, $setting, $data['store_id']);
		}
		
		if ($data['element_code'] == 'enable_seo_url') {
			$store_setting = $this->model_setting_setting->getSetting('config');
			$store_setting['config_seo_url'] = 1;
			
			$this->model_setting_setting->editSetting('config', $store_setting);
			
			$setting['module_' . $this->codename . '_setting']['control_element']['enable_seo_url']['implemented'] = 1;
			
			$this->model_setting_setting->editSetting('module_' . $this->codename, $setting);
		}
		
		if ($data['element_code'] == 'enable_htaccess') {
			$this->config->load($this->config_file);
			$config_setting = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
				
			$htaccess = $this->{'model_extension_module_' . $this->codename}->getFileData('htaccess');		
			
			if (!$htaccess['status'] && !trim($htaccess['text'])) {
				$htaccess['text'] = str_replace('[catalog_url_path]', $catalog_url_info['path'], $config_setting['default_htaccess']);
			}
			
			$htaccess['status'] = 1;
			
			$this->{'model_extension_module_' . $this->codename}->saveFileData('htaccess', $htaccess);
			
			$setting['module_' . $this->codename . '_setting']['control_element']['enable_htaccess']['implemented'] = 1;
			
			$this->model_setting_setting->editSetting('module_' . $this->codename, $setting, $data['store_id']);
		}
		
		if ($data['element_code'] == 'enable_robots') {
			$this->config->load($this->config_file);
			$config_setting = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
				
			$robots = $this->{'model_extension_module_' . $this->codename}->getFileData('robots');		
			
			if (!$robots['status'] && !trim($robots['text'])) {
				$robots['text'] = str_replace('[catalog_url]', $catalog, $config_setting['default_robots']);
				$robots['text'] = str_replace('[catalog_url_host]', $catalog_url_info['host'], $robots['text']);
			}
			
			$robots['status'] = 1;
			
			$this->{'model_extension_module_' . $this->codename}->saveFileData('robots', $robots);
			
			$setting['module_' . $this->codename . '_setting']['control_element']['enable_robots']['implemented'] = 1;
			
			$this->model_setting_setting->editSetting('module_' . $this->codename, $setting, $data['store_id']);
		}
				
		$result['error'] = $this->error;
		
		return $result;
	}
	
	public function target_config() {
		$_language = new Language();
		$_language->load($this->route);
		
		$_config = new Config();
		$_config->load($this->config_file);
		$target_setting = ($_config->get($this->codename . '_target_setting')) ? $_config->get($this->codename . '_target_setting') : array();
		
		foreach ($target_setting['sheet'] as $sheet) {
			if (substr($sheet['name'], 0, strlen('text_')) == 'text_') {
				$target_setting['sheet'][$sheet['code']]['name'] = $_language->get($sheet['name']);
			}
		}
					
		return $target_setting;
	}
		
	public function target_elements($filter_data) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_' . $this->codename}->getTargetElements($filter_data);
	}
		
	public function add_target_element($target_element_data) {
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_' . $this->codename}->addTargetElement($target_element_data);
	}
	
	public function edit_target_element($target_element_data) {
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_' . $this->codename}->editTargetElement($target_element_data);
	}
	
	public function delete_target_element($target_element_data) {
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_' . $this->codename}->deleteTargetElement($target_element_data);
	}
		
	public function export_target_elements($export_data) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_' . $this->codename}->getExportTargetElements($export_data);
	}
	
	public function import_target_elements($import_data) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_' . $this->codename}->saveImportTargetElements($import_data);
	}
	
	public function field_config() {
		$_language = new Language();
		$_language->load($this->route);
		
		$_config = new Config();
		$_config->load($this->config_file);
		$field_setting = ($_config->get($this->codename . '_field_setting')) ? $_config->get($this->codename . '_field_setting') : array();

		foreach ($field_setting['sheet'] as $sheet) {				
			if (substr($sheet['name'], 0, strlen('text_')) == 'text_') {
				$field_setting['sheet'][$sheet['code']]['name'] = $_language->get($sheet['name']);
			}
			
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
