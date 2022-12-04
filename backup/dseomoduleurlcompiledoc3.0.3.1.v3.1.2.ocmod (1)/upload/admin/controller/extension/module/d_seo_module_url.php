<?php
class ControllerExtensionModuleDSEOModuleURL extends Controller {
	private $codename = 'd_seo_module_url';
	private $route = 'extension/module/d_seo_module_url';
	private $config_file = 'd_seo_module_url';
	private $extension = array();
	private $error = array(); 
	
	public function __construct($registry) {
		parent::__construct($registry);

		$this->d_shopunity = (file_exists(DIR_SYSTEM . 'library/d_shopunity/extension/d_shopunity.json'));
		$this->extension = json_decode(file_get_contents(DIR_SYSTEM . 'library/d_shopunity/extension/' . $this->codename . '.json'), true);
	}
	
	public function index() {		
		$this->setting();
	}
	
	public function setting() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		
		if ($this->d_shopunity) {		
			$this->load->model('extension/d_shopunity/mbooth');
				
			$this->model_extension_d_shopunity_mbooth->validateDependencies($this->codename);
		}
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .=  'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .=  'user_token=' . $this->session->data['user_token'];
		}
		
		$url_store =  'store_id=' . $store_id;
		
		// Styles and Scripts
		$this->document->addStyle('view/stylesheet/d_bootstrap_extra/bootstrap.css');
		$this->document->addScript('view/javascript/d_bootstrap_switch/js/bootstrap-switch.min.js');
        $this->document->addStyle('view/javascript/d_bootstrap_switch/css/bootstrap-switch.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/core/normalize/normalize.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/themes/light/light.css');
		$this->document->addStyle('view/stylesheet/d_seo_module.css');
				
		// Heading
		$this->document->setTitle($this->language->get('heading_title_main'));
		$data['heading_title'] = $this->language->get('heading_title_main');
		
		// Variable
		$data['codename'] = $this->codename;
		$data['route'] = $this->route;
		$data['version'] = $this->extension['version'];
		$data['extension_id'] = $this->extension['extension_id'];
		$data['config'] = $this->config_file;
		$data['d_shopunity'] = $this->d_shopunity;
		$data['url_token'] = $url_token;
		$data['store_id'] = $store_id;
		$data['stores'] = $this->{'model_extension_module_' . $this->codename}->getStores();
		$data['languages'] = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		$data['installed'] = in_array($this->codename, $installed_seo_extensions) ? true : false;
						
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$data['server'] = HTTPS_SERVER;
			$data['catalog'] = HTTPS_CATALOG;
		} else {
			$data['server'] = HTTP_SERVER;
			$data['catalog'] = HTTP_CATALOG;
		}
						
		// Action
		$data['href_setting'] = $this->url->link($this->route . '/setting', $url_token . '&' . $url_store, true);
		$data['href_generator'] = $this->url->link($this->route . '/generator', $url_token . '&' . $url_store, true);
		$data['href_url_keyword'] = $this->url->link($this->route . '/url_keyword', $url_token . '&' . $url_store, true);
		$data['href_redirect'] = $this->url->link($this->route . '/redirect', $url_token . '&' . $url_store, true);
		$data['href_export_import'] = $this->url->link($this->route . '/export_import', $url_token . '&' . $url_store, true);
		$data['href_instruction'] = $this->url->link($this->route . '/instruction', $url_token . '&' . $url_store, true);
		
		$data['module_link'] = $this->url->link($this->route, $url_token . '&' . $url_store, true);
		$data['action'] = $this->url->link($this->route . '/save', $url_token . '&' . $url_store, true);
		$data['setup'] = $this->url->link($this->route . '/setupExtension', $url_token, true);
		$data['install'] = $this->url->link($this->route . '/installExtension', $url_token, true);
		$data['uninstall'] = $this->url->link($this->route . '/uninstallExtension', $url_token, true);
		
		if (VERSION >= '3.0.0.0') {
			$data['cancel'] = $this->url->link('marketplace/extension', $url_token . '&type=module', true);
		} elseif (VERSION >= '2.3.0.0') {
			$data['cancel'] = $this->url->link('extension/extension', $url_token . '&type=module', true);
		} else {
			$data['cancel'] = $this->url->link('extension/module', $url_token, true);
		}
				
		// Tab
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_generator'] = $this->language->get('text_generator');
		$data['text_url_keywords'] = $this->language->get('text_url_keywords');
		$data['text_redirects'] = $this->language->get('text_redirects');
		$data['text_export_import'] = $this->language->get('text_export_import');
		$data['text_instructions'] = $this->language->get('text_instructions');
		
		$data['text_basic_settings'] = $this->language->get('text_basic_settings');
		$data['text_multi_language_sub_directories'] = $this->language->get('text_multi_language_sub_directories');
		$data['text_category'] = $this->language->get('text_category');
		$data['text_product'] = $this->language->get('text_product');
		$data['text_manufacturer'] = $this->language->get('text_manufacturer');
		$data['text_information'] = $this->language->get('text_information');
		$data['text_search'] = $this->language->get('text_search');
		$data['text_special'] = $this->language->get('text_special');
		$data['text_custom_page'] = $this->language->get('text_custom_page');
		$data['text_url_keyword'] = $this->language->get('text_url_keyword');
		$data['text_list'] = $this->language->get('text_list');
				
		// Button
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');	
		$data['button_setup'] = $this->language->get('button_setup');
		$data['button_uninstall'] = $this->language->get('button_uninstall');
						
		// Entry
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_list_limit'] = $this->language->get('entry_list_limit');
		$data['entry_uninstall'] = $this->language->get('entry_uninstall');
		$data['entry_multi_language_sub_directory_name'] = $this->language->get('entry_multi_language_sub_directory_name');
		$data['entry_unique_url'] = $this->language->get('entry_unique_url');
		$data['entry_exception_data'] = $this->language->get('entry_exception_data');
		$data['entry_short_url'] = $this->language->get('entry_short_url');
		$data['entry_canonical_link_search'] = $this->language->get('entry_canonical_link_search');
		$data['entry_canonical_link_tag'] = $this->language->get('entry_canonical_link_tag');
		$data['entry_canonical_link_page'] = $this->language->get('entry_canonical_link_page');
						
		// Text
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_install'] = $this->language->get('text_install');
		$data['text_setup'] = $this->language->get('text_setup');
		$data['text_full_setup'] = $this->language->get('text_full_setup');
		$data['text_custom_setup'] = $this->language->get('text_custom_setup');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_powered_by'] = $this->language->get('text_powered_by');
		$data['text_uninstall_confirm'] = $this->language->get('text_uninstall_confirm');
				
		// Help
		$data['help_setup'] = $this->language->get('help_setup');
		$data['help_full_setup'] = $this->language->get('help_full_setup');
		$data['help_custom_setup'] = $this->language->get('help_custom_setup');
		$data['help_multi_language_sub_directory_status'] = $this->language->get('help_multi_language_sub_directory_status');
		$data['help_multi_language_sub_directory_name'] = $this->language->get('help_multi_language_sub_directory_name');
		$data['help_redirect_status'] = $this->language->get('help_redirect_status');
		$data['help_redirect_exception_data'] = $this->language->get('help_redirect_exception_data');
		$data['help_unique_url'] = $this->language->get('help_unique_url');
		$data['help_exception_data'] = $this->language->get('help_exception_data');
		$data['help_short_url'] = $this->language->get('help_short_url');
		$data['help_canonical_link_search'] = $this->language->get('help_canonical_link_search');
		$data['help_canonical_link_tag'] = $this->language->get('help_canonical_link_tag');
		$data['help_canonical_link_page'] = $this->language->get('help_canonical_link_page');
		
		// Notification
		foreach ($this->error as $key => $error) {
			$data['error'][$key] = $error;
		}
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		
		// Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $url_token, true)
		);
				
		if (VERSION >= '3.0.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('marketplace/extension', $url_token . '&type=module', true)
			);
		} elseif (VERSION >= '2.3.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('extension/extension', $url_token . '&type=module', true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('extension/module', $url_token, true)
			);
		}
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_seo_module'),
			'href' => $this->url->link('extension/module/d_seo_module', $url_token . '&' . $url_store, true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_url'),
			'href' => $this->url->link($this->route, $url_token . '&' . $url_store, true)
		);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		if ($data['installed']) {		
			// Setting 		
			$this->config->load($this->config_file);
			$data['setting'] = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
				
			$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $store_id);
			$status = isset($setting['module_' . $this->codename . '_status']) ? $setting['module_' . $this->codename . '_status'] : false;
			$setting = isset($setting['module_' . $this->codename . '_setting']) ? $setting['module_' . $this->codename . '_setting'] : array();
		
			$data['status'] = $status;
								
			if (!empty($setting)) {
				$data['setting'] = array_replace_recursive($data['setting'], $setting);
			}
			
			$this->response->setOutput($this->load->view($this->route . '/setting', $data));
		} else {
			// Setting
			$this->config->load($this->config_file);
			$config_feature_setting = ($this->config->get($this->codename . '_feature_setting')) ? $this->config->get($this->codename . '_feature_setting') : array();
		
			$data['features'] = array();
		
			foreach ($config_feature_setting as $feature) {
				if (substr($feature['name'], 0, strlen('text_')) == 'text_') {
					$feature['name'] = $this->language->get($feature['name']);
				}
						
				$data['features'][] = $feature;
			}
			
			$this->response->setOutput($this->load->view($this->route . '/install', $data));
		}
	}
	
	public function generator() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		
		if ($this->d_shopunity) {		
			$this->load->model('extension/d_shopunity/mbooth');
				
			$this->model_extension_d_shopunity_mbooth->validateDependencies($this->codename);
		}
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
				
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .= 'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .= 'user_token=' . $this->session->data['user_token'];
		}
		
		$url_store = 'store_id=' . $store_id;
		
		// Styles and Scripts
		$this->document->addStyle('view/stylesheet/d_bootstrap_extra/bootstrap.css');
		$this->document->addScript('view/javascript/d_bootstrap_switch/js/bootstrap-switch.min.js');
        $this->document->addStyle('view/javascript/d_bootstrap_switch/css/bootstrap-switch.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/core/normalize/normalize.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/themes/light/light.css');
		$this->document->addStyle('view/stylesheet/d_seo_module.css');
				
		// Heading
		$this->document->setTitle($this->language->get('heading_title_main'));
		$data['heading_title'] = $this->language->get('heading_title_main');
		
		// Variable
		$data['codename'] = $this->codename;
		$data['route'] = $this->route;
		$data['version'] = $this->extension['version'];
		$data['config'] = $this->config_file;
		$data['d_shopunity'] = $this->d_shopunity;
		$data['store_id'] = $store_id;
		$data['url_token'] = $url_token;
		$data['stores'] = $this->{'model_extension_module_' . $this->codename}->getStores();
		$data['languages'] = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		$data['installed'] = in_array($this->codename, $installed_seo_extensions) ? true : false;
						
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$data['server'] = HTTPS_SERVER;
			$data['catalog'] = HTTPS_CATALOG;
		} else {
			$data['server'] = HTTP_SERVER;
			$data['catalog'] = HTTP_CATALOG;
		}
						
		// Action
		$data['href_setting'] = $this->url->link($this->route . '/setting', $url_token . '&' . $url_store, true);
		$data['href_generator'] = $this->url->link($this->route . '/generator', $url_token . '&' . $url_store, true);
		$data['href_url_keyword'] = $this->url->link($this->route . '/url_keyword', $url_token . '&' . $url_store, true);
		$data['href_redirect'] = $this->url->link($this->route . '/redirect', $url_token . '&' . $url_store, true);
		$data['href_export_import'] = $this->url->link($this->route . '/export_import', $url_token . '&' . $url_store, true);
		$data['href_instruction'] = $this->url->link($this->route . '/instruction', $url_token . '&' . $url_store, true);
		
		$data['module_link'] = $this->url->link($this->route, $url_token . '&' . $url_store, true);
		$data['action'] = $this->url->link($this->route . '/save', $url_token . '&' . $url_store, true);
		$data['setup'] = $this->url->link($this->route . '/setupExtension', $url_token, true);
		$data['install'] = $this->url->link($this->route . '/installExtension', $url_token, true);
		
		if (VERSION >= '3.0.0.0') {
			$data['cancel'] = $this->url->link('marketplace/extension', $url_token . '&type=module', true);
		} elseif (VERSION >= '2.3.0.0') {
			$data['cancel'] = $this->url->link('extension/extension', $url_token . '&type=module', true);
		} else {
			$data['cancel'] = $this->url->link('extension/module', $url_token, true);
		}
		
		// Tab
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_generator'] = $this->language->get('text_generator');
		$data['text_url_keywords'] = $this->language->get('text_url_keywords');
		$data['text_redirects'] = $this->language->get('text_redirects');
		$data['text_export_import'] = $this->language->get('text_export_import');
		$data['text_instructions'] = $this->language->get('text_instructions');
		
		$data['text_category'] = $this->language->get('text_category');
		$data['text_product'] = $this->language->get('text_product');
		$data['text_manufacturer'] = $this->language->get('text_manufacturer');
		$data['text_search'] = $this->language->get('text_search');
		$data['text_special'] = $this->language->get('text_special');
		$data['text_information'] = $this->language->get('text_information');
				
		// Button
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');	
		$data['button_setup'] = $this->language->get('button_setup');
		$data['button_submit'] = $this->language->get('button_submit');
		$data['button_generate'] = $this->language->get('button_generate');
		$data['button_clear'] = $this->language->get('button_clear');
				
		// Entry
		$data['entry_template'] = $this->language->get('entry_template');
		$data['entry_transform_language_symbol'] = $this->language->get('entry_transform_language_symbol');
		$data['entry_translit_language_symbol'] = $this->language->get('entry_translit_language_symbol');
		$data['entry_trim_symbol'] = $this->language->get('entry_trim_symbol');
		$data['entry_overwrite'] = $this->language->get('entry_overwrite');
		$data['entry_keyword_number'] = $this->language->get('entry_keyword_number');
		$data['entry_generation'] = $this->language->get('entry_generation');
					
		// Text
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_install'] = $this->language->get('text_install');
		$data['text_setup'] = $this->language->get('text_setup');
		$data['text_full_setup'] = $this->language->get('text_full_setup');
		$data['text_custom_setup'] = $this->language->get('text_custom_setup');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_powered_by'] = $this->language->get('text_powered_by');
				
		$data['text_transform_none'] = $this->language->get('text_transform_none');
		$data['text_transform_lower_to_upper'] = $this->language->get('text_transform_lower_to_upper');
		$data['text_transform_upper_to_lower'] = $this->language->get('text_transform_upper_to_lower');
		$data['text_insert_tag_settings'] = $this->language->get('text_insert_tag_settings');
		$data['text_generate_confirm'] = $this->language->get('text_generate_confirm');
		$data['text_clear_confirm'] = $this->language->get('text_clear_confirm');
		
		// Help
		$data['help_setup'] = $this->language->get('help_setup');
		$data['help_full_setup'] = $this->language->get('help_full_setup');
		$data['help_custom_setup'] = $this->language->get('help_custom_setup');
		$data['help_template'] = $this->language->get('help_template');
		$data['help_translit_language_symbol'] = $this->language->get('help_translit_language_symbol');
		$data['help_transform_language_symbol'] = $this->language->get('help_transform_language_symbol');
		$data['help_trim_symbol'] = $this->language->get('help_trim_symbol');
		$data['help_overwrite'] = $this->language->get('help_overwrite');
			
		// Notification
		foreach ($this->error as $key => $error) {
			$data['error'][$key] = $error;
		}
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		
		// Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $url_token, true)
		);

		if (VERSION >= '3.0.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('marketplace/extension', $url_token . '&type=module', true)
			);
		} elseif (VERSION >= '2.3.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('extension/extension', $url_token . '&type=module', true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('extension/module', $url_token, true)
			);
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_seo_module'),
			'href' => $this->url->link('extension/module/d_seo_module', $url_token . '&' . $url_store, true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_url'),
			'href' => $this->url->link($this->route, $url_token . '&' . $url_store, true)
		);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		if ($data['installed']) {
			// Setting		
			$data['generator_setting'] = array();
		
			$installed_seo_url_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOURLExtensions();
		
			foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/url_generator_config');
				if ($info) $data['generator_setting'] = array_replace_recursive($data['generator_setting'], $info);
			}
		
			$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $store_id);
			$generator_setting = isset($setting['module_' . $this->codename . '_generator_setting']) ? $setting['module_' . $this->codename . '_generator_setting'] : array();
						
			if (!empty($generator_setting)) {
				$data['generator_setting'] = array_replace_recursive($data['generator_setting'], $generator_setting);
			}
		
			$sheets = array();
		
			foreach ($data['generator_setting']['sheet'] as $sheet) {
				if (isset($sheet['code']) && isset($sheet['name'])) {				
					$fields = array();
				
					if (isset($sheet['field'])) {
						foreach ($sheet['field'] as $field) {
							if (isset($field['code']) && isset($field['name'])) {
								$fields[] = $field;
							}
						}
					
						$fields = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($fields, 'sort_order');
					}
				
					$sheet['field'] = array();
				
					foreach ($fields as $field) {
						$sheet['field'][$field['code']] = $field;
					}
				
					$sheets[] = $sheet;
				}
			}
		
			$data['generator_setting']['sheet'] = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($sheets, 'sort_order');		
			$this->response->setOutput($this->load->view($this->route . '/generator', $data));
		} else {
			// Setting
			$this->config->load($this->config_file);
			$config_feature_setting = ($this->config->get($this->codename . '_feature_setting')) ? $this->config->get($this->codename . '_feature_setting') : array();
		
			$data['features'] = array();
		
			foreach ($config_feature_setting as $feature) {
				if (substr($feature['name'], 0, strlen('text_')) == 'text_') {
					$feature['name'] = $this->language->get($feature['name']);
				}
						
				$data['features'][] = $feature;
			}
			
			$this->response->setOutput($this->load->view($this->route . '/install', $data));
		}
	}
	
	public function url_keyword() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		
		if ($this->d_shopunity) {		
			$this->load->model('extension/d_shopunity/mbooth');
				
			$this->model_extension_d_shopunity_mbooth->validateDependencies($this->codename);
		}
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
				
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		if (isset($this->request->get['sheet_code'])) { 
			$sheet_code = $this->request->get['sheet_code']; 
		} else {  
			$sheet_code = 'category';
		}
		
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .= 'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .= 'user_token=' . $this->session->data['user_token'];
		}
		
		$url_store = 'store_id=' . $store_id;
		$url_sheet = 'sheet_code=' . $sheet_code;
		$url_page = 'page=' . $page;
		
		// Styles and Scripts
		$this->document->addStyle('view/stylesheet/d_bootstrap_extra/bootstrap.css');
		$this->document->addScript('view/javascript/d_bootstrap_switch/js/bootstrap-switch.min.js');
        $this->document->addStyle('view/javascript/d_bootstrap_switch/css/bootstrap-switch.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/core/normalize/normalize.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/themes/light/light.css');
		$this->document->addStyle('view/stylesheet/d_seo_module.css');
					
		// Heading
		$this->document->setTitle($this->language->get('heading_title_main'));
		$data['heading_title'] = $this->language->get('heading_title_main');
		
		// Variable
		$data['codename'] = $this->codename;
		$data['route'] = $this->route;
		$data['version'] = $this->extension['version'];
		$data['config'] = $this->config_file;
		$data['d_shopunity'] = $this->d_shopunity;
		$data['store_id'] = $store_id;
		$data['sheet_code'] = $sheet_code;
		$data['page'] = $page;
		$data['url_token'] = $url_token;
		$data['stores'] = $this->{'model_extension_module_' . $this->codename}->getStores();
		$data['languages'] = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		$data['installed'] = in_array($this->codename, $installed_seo_extensions) ? true : false;
						
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$data['server'] = HTTPS_SERVER;
			$data['catalog'] = HTTPS_CATALOG;
		} else {
			$data['server'] = HTTP_SERVER;
			$data['catalog'] = HTTP_CATALOG;
		}
						
		// Action
		$data['href_setting'] = $this->url->link($this->route . '/setting', $url_token . '&' . $url_store, true);
		$data['href_generator'] = $this->url->link($this->route . '/generator', $url_token . '&' . $url_store, true);
		$data['href_url_keyword'] = $this->url->link($this->route . '/url_keyword', $url_token . '&' . $url_store, true);
		$data['href_redirect'] = $this->url->link($this->route . '/redirect', $url_token . '&' . $url_store, true);
		$data['href_export_import'] = $this->url->link($this->route . '/export_import', $url_token . '&' . $url_store, true);
		$data['href_instruction'] = $this->url->link($this->route . '/instruction', $url_token . '&' . $url_store, true);
		
		$data['module_link'] = $this->url->link($this->route, $url_token . '&' . $url_store, true);
		$data['store_url'] = $this->url->link($this->route . '/url_keyword', $url_token . '&' . $url_sheet, true);
		$data['setup'] = $this->url->link($this->route . '/setupExtension', $url_token, true);
		$data['install'] = $this->url->link($this->route . '/installExtension', $url_token, true);
		
		if (VERSION >= '3.0.0.0') {
			$data['cancel'] = $this->url->link('marketplace/extension', $url_token . '&type=module', true);
		} elseif (VERSION >= '2.3.0.0') {
			$data['cancel'] = $this->url->link('extension/extension', $url_token . '&type=module', true);
		} else {
			$data['cancel'] = $this->url->link('extension/module', $url_token, true);
		}
		
		// Tab
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_generator'] = $this->language->get('text_generator');
		$data['text_url_keywords'] = $this->language->get('text_url_keywords');
		$data['text_redirects'] = $this->language->get('text_redirects');
		$data['text_export_import'] = $this->language->get('text_export_import');
		$data['text_instructions'] = $this->language->get('text_instructions');
		$data['text_url_keywords'] = $this->language->get('text_url_keywords');
		
		// Button
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');	
		$data['button_setup'] = $this->language->get('button_setup');
		$data['button_filter'] = $this->language->get('button_filter');
		$data['button_clear_filter'] = $this->language->get('button_clear_filter');
		$data['button_create_default_url_keywords'] = $this->language->get('button_create_default_url_keywords');
		$data['button_add_url_keyword'] = $this->language->get('button_add_url_keyword');
		$data['button_delete_url_keywords'] = $this->language->get('button_delete_url_keywords');		
						
		// Column
		$data['column_route'] = $this->language->get('column_route');
		$data['column_url_keyword'] = $this->language->get('column_url_keyword');
		
		// Entry
		$data['entry_route'] = $this->language->get('entry_route');
		$data['entry_url_keyword'] = $this->language->get('entry_url_keyword');
				
		// Text
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_install'] = $this->language->get('text_install');
		$data['text_setup'] = $this->language->get('text_setup');
		$data['text_full_setup'] = $this->language->get('text_full_setup');
		$data['text_custom_setup'] = $this->language->get('text_custom_setup');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_powered_by'] = $this->language->get('text_powered_by');
		
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_add_url_keyword'] = $this->language->get('text_add_url_keyword');
		$data['text_delete_url_keywords_confirm'] = $this->language->get('text_delete_url_keywords_confirm');
		$data['text_create_default_url_keywords_confirm'] = $this->language->get('text_create_default_url_keywords_confirm');
		
		// Help
		$data['help_setup'] = $this->language->get('help_setup');
		$data['help_full_setup'] = $this->language->get('help_full_setup');
		$data['help_custom_setup'] = $this->language->get('help_custom_setup');
		
		// Notification
		foreach ($this->error as $key => $error) {
			$data['error'][$key] = $error;
		}
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		
		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}
		
		if (isset($this->request->post['clear_filter'])) {
			if ($this->request->post['clear_filter']) {	
				unset($this->request->post['filter']);
				unset($this->session->data[$this->codename . '_filter_' . $sheet_code]);
			}
		}
		
		if (isset($this->request->post['filter'])) {
			$filter = $this->request->post['filter'];
			$i = 0;
			
			foreach($filter as $value) {
				if ($value) $i++;
			}
			
			if ($i > 0) {
				$this->session->data[$this->codename . '_filter_' . $sheet_code] = $filter;
			} else {
				$filter = array();
				unset($this->session->data[$this->codename . '_filter_' . $sheet_code]);
			}
		} elseif (isset($this->session->data[$this->codename . '_filter_' . $sheet_code])) {
			$filter = $this->session->data[$this->codename . '_filter_' . $sheet_code];
		} else {
			$filter = array();
			unset($this->session->data[$this->codename . '_filter_' . $sheet_code]);
		}
		
		// Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $url_token, true)
		);

		if (VERSION >= '3.0.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('marketplace/extension', $url_token . '&type=module', true)
			);
		} elseif (VERSION >= '2.3.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('extension/extension', $url_token . '&type=module', true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('extension/module', $url_token, true)
			);
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_seo_module'),
			'href' => $this->url->link('extension/module/d_seo_module', $url_token . '&' . $url_store, true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_url'),
			'href' => $this->url->link($this->route, $url_token . '&' . $url_store, true)
		);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		if ($data['installed']) {		
			// Setting 	
			$this->config->load($this->config_file);
			$data['setting'] = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
		
			$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $store_id);
			$setting = isset($setting['module_' . $this->codename . '_setting']) ? $setting['module_' . $this->codename . '_setting'] : array();
										
			if (!empty($setting)) {
				$data['setting'] = array_replace_recursive($data['setting'], $setting);
			}
			
			$url_setting = array();
		
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/url_config');
				if ($info) $url_setting = array_replace_recursive($url_setting, $info);
			}
		
			$sheets = array();
		
			foreach ($url_setting['sheet'] as $sheet) {
				if (isset($sheet['code']) && isset($sheet['icon']) && isset($sheet['name']) && isset($sheet['sort_order'])) {								
					$sheets[] = array(
						'code'			=> $sheet['code'],
						'icon'			=> $sheet['icon'],
						'name'			=> $sheet['name'],
						'sort_order'	=> $sheet['sort_order'],
						'url'			=> $this->url->link($this->route . '/url_keyword', $url_token . '&' . $url_store . '&sheet_code=' . $sheet['code'], true)
					);
				}
			}
						
			$sheets = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($sheets, 'sort_order');
			
			$filter_data = array(
				'store_id'			=> $store_id,
				'sheet_code'		=> $sheet_code,
				'filter'	  	  	=> $filter
			);
			
			$url_elements = array();
						
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/url_elements', $filter_data);
				if ($info) $url_elements = array_replace_recursive($url_elements, $info);
			}
						
			$data['url_elements'] = array();
		
			$i = 0;
			
			foreach ($url_elements as $url_element) {
				if (isset($url_element['url_keyword'])) {
					foreach ($url_element['url_keyword'] as $language_id => $url_keyword) {
						$field_data = array(
							'field_code' => 'url_keyword',
							'filter' => array(
								'store_id' => $store_id,
								'keyword' => $url_keyword
							)
						);
			
						$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data); 
						$store_url_keywords = reset($url_keywords);
							
						if ((count($url_keywords) > 1) || (count(reset($store_url_keywords)) > 1)) {
							$url_element['url_keyword_duplicate'][$language_id] = 1;
						}
					}
				}
				
				if (($i >= (($page - 1) * $data['setting']['list_limit'])) && ($i < ((($page - 1) * $data['setting']['list_limit']) + $data['setting']['list_limit']))) {
					$data['url_elements'][] = $url_element;
				}
			
				$i++;
			
				if ($i == ((($page - 1) * $data['setting']['list_limit']) + $data['setting']['list_limit'])) break;
			}
					
			$pagination = new Pagination();
			$pagination->total = count($url_elements);
			$pagination->page = $page;
			$pagination->limit = $data['setting']['list_limit'];
			$pagination->url = $this->url->link($this->route . '/url_keyword', $url_token . '&' . $url_store . '&' . $url_sheet . '&page={page}', true);

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), (count($url_elements)) ? (($page - 1) * $data['setting']['list_limit']) + 1 : 0, ((($page - 1) * $data['setting']['list_limit']) > (count($url_elements) - $data['setting']['list_limit'])) ? count($url_elements) : ((($page - 1) * $data['setting']['list_limit']) + $data['setting']['list_limit']), count($url_elements), ceil(count($url_elements) / $data['setting']['list_limit']));
								
			$data['sheets'] = $sheets;
			$data['filter'] = $filter;

			$this->response->setOutput($this->load->view($this->route . '/url_keyword', $data));
		} else {
			// Setting
			$this->config->load($this->config_file);
			$config_feature_setting = ($this->config->get($this->codename . '_feature_setting')) ? $this->config->get($this->codename . '_feature_setting') : array();
		
			$data['features'] = array();
		
			foreach ($config_feature_setting as $feature) {
				if (substr($feature['name'], 0, strlen('text_')) == 'text_') {
					$feature['name'] = $this->language->get($feature['name']);
				}
						
				$data['features'][] = $feature;
			}
			
			$this->response->setOutput($this->load->view($this->route . '/install', $data));
		}
	}
	
	public function redirect() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		
		if ($this->d_shopunity) {		
			$this->load->model('extension/d_shopunity/mbooth');
				
			$this->model_extension_d_shopunity_mbooth->validateDependencies($this->codename);
		}
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'url_from';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}
		
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .= 'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .= 'user_token=' . $this->session->data['user_token'];
		}
		
		$url_store = 'store_id=' . $store_id;		
		$url_sort = 'sort=' . $sort;
		$url_order = 'order=' . $order;		
		$url_page = 'page=' . $page;
		
		// Styles and Scripts
		$this->document->addStyle('view/stylesheet/d_bootstrap_extra/bootstrap.css');
		$this->document->addScript('view/javascript/d_bootstrap_switch/js/bootstrap-switch.min.js');
        $this->document->addStyle('view/javascript/d_bootstrap_switch/css/bootstrap-switch.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/core/normalize/normalize.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/themes/light/light.css');
		$this->document->addStyle('view/stylesheet/d_seo_module.css');
						
		// Heading
		$this->document->setTitle($this->language->get('heading_title_main'));
		$data['heading_title'] = $this->language->get('heading_title_main');
		
		// Variable
		$data['codename'] = $this->codename;
		$data['route'] = $this->route;
		$data['version'] = $this->extension['version'];
		$data['config'] = $this->config_file;
		$data['d_shopunity'] = $this->d_shopunity;
		$data['store_id'] = $store_id;
		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['page'] = $page;
		$data['url_token'] = $url_token;
		$data['stores'] = $this->{'model_extension_module_' . $this->codename}->getStores();
				
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		$data['installed'] = in_array($this->codename, $installed_seo_extensions) ? true : false;
						
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$data['server'] = HTTPS_SERVER;
			$data['catalog'] = HTTPS_CATALOG;
		} else {
			$data['server'] = HTTP_SERVER;
			$data['catalog'] = HTTP_CATALOG;
		}
						
		// Action
		$data['href_setting'] = $this->url->link($this->route . '/setting', $url_token . '&' . $url_store, true);
		$data['href_generator'] = $this->url->link($this->route . '/generator', $url_token . '&' . $url_store, true);
		$data['href_url_keyword'] = $this->url->link($this->route . '/url_keyword', $url_token . '&' . $url_store, true);
		$data['href_redirect'] = $this->url->link($this->route . '/redirect', $url_token . '&' . $url_store, true);
		$data['href_export_import'] = $this->url->link($this->route . '/export_import', $url_token . '&' . $url_store, true);
		$data['href_instruction'] = $this->url->link($this->route . '/instruction', $url_token . '&' . $url_store, true);
		
		$data['module_link'] = $this->url->link($this->route, $url_token . '&' . $url_store, true);
		$data['action'] = $this->url->link($this->route . '/redirect', $url_token . '&' . $url_store, true);
		$data['setup'] = $this->url->link($this->route . '/setupExtension', $url_token, true);
		$data['install'] = $this->url->link($this->route . '/installExtension', $url_token, true);
		
		if (VERSION >= '3.0.0.0') {
			$data['cancel'] = $this->url->link('marketplace/extension', $url_token . '&type=module', true);
		} elseif (VERSION >= '2.3.0.0') {
			$data['cancel'] = $this->url->link('extension/extension', $url_token . '&type=module', true);
		} else {
			$data['cancel'] = $this->url->link('extension/module', $url_token, true);
		}
				
		// Tab
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_generator'] = $this->language->get('text_generator');
		$data['text_url_keywords'] = $this->language->get('text_url_keywords');
		$data['text_redirects'] = $this->language->get('text_redirects');
		$data['text_export_import'] = $this->language->get('text_export_import');
		$data['text_instructions'] = $this->language->get('text_instructions');
					
		// Button
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');	
		$data['button_setup'] = $this->language->get('button_setup');
		$data['button_add_redirect'] = $this->language->get('button_add_redirect');
		$data['button_delete_redirect'] = $this->language->get('button_delete_redirect');	
		$data['button_filter'] = $this->language->get('button_filter');
		$data['button_clear_filter'] = $this->language->get('button_clear_filter');
						
		// Column
		$data['column_url_from'] = $this->language->get('column_url_from');
		$data['column_url_to'] = $this->language->get('column_url_to');
		
		// Entry
		$data['entry_url_from'] = $this->language->get('entry_url_from');
		$data['entry_url_to'] = $this->language->get('entry_url_to');
				
		// Text
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_install'] = $this->language->get('text_install');
		$data['text_setup'] = $this->language->get('text_setup');
		$data['text_full_setup'] = $this->language->get('text_full_setup');
		$data['text_custom_setup'] = $this->language->get('text_custom_setup');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_powered_by'] = $this->language->get('text_powered_by');
		
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_add_redirect'] = $this->language->get('text_add_redirect');
		$data['text_delete_redirects_confirm'] = $this->language->get('text_delete_redirects_confirm');
		
		// Help
		$data['help_setup'] = $this->language->get('help_setup');
		$data['help_full_setup'] = $this->language->get('help_full_setup');
		$data['help_custom_setup'] = $this->language->get('help_custom_setup');
		
		// Notification
		foreach ($this->error as $key => $error) {
			$data['error'][$key] = $error;
		}
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		
		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}
		
		if (isset($this->request->post['clear_filter'])) {
			if ($this->request->post['clear_filter']) {	
				unset($this->request->post['filter']);
				unset($this->session->data[$this->codename . '_filter']);
			}
		}
		
		if (isset($this->request->post['filter'])) {
			$filter = $this->request->post['filter'];
			$i = 0;
			
			foreach($filter as $value) {
				if ($value) $i++;
			}
			if ($i > 0) {
				$this->session->data[$this->codename . '_filter'] = $filter;
			} else {
				$filter = array();
				unset($this->session->data[$this->codename . '_filter']);
			}
		} elseif (isset($this->session->data[$this->codename . '_filter'])) {
			$filter = $this->session->data[$this->codename . '_filter'];
		} else {
			$filter = array();
			unset($this->session->data[$this->codename . '_filter']);
		}
				
		// Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $url_token, true)
		);

		if (VERSION >= '3.0.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('marketplace/extension', $url_token . '&type=module', true)
			);
		} elseif (VERSION >= '2.3.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('extension/extension', $url_token . '&type=module', true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('extension/module', $url_token, true)
			);
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_seo_module'),
			'href' => $this->url->link('extension/module/d_seo_module', $url_token . '&' . $url_store, true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_url'),
			'href' => $this->url->link($this->route, $url_token . '&' . $url_store, true)
		);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		if ($data['installed']) {
			// Setting 	
			$this->config->load($this->config_file);
			$data['setting'] = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
		
			$setting = $this->model_setting_setting->getSetting('module_' . $this->codename);
			$setting = isset($setting['module_' . $this->codename . '_setting']) ? $setting['module_' . $this->codename . '_setting'] : array();
										
			if (!empty($setting)) {
				$data['setting'] = array_replace_recursive($data['setting'], $setting);
			}
		
			$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
												
			$filter_data = array(
				'filter'			=> $filter,
				'sort'  			=> $sort,
				'order' 			=> $order,
				'start' 			=> ($page - 1) * $data['setting']['list_limit'],
				'limit' 			=> $data['setting']['list_limit']
			);
		
			$redirect_total = $this->{'model_extension_module_' . $this->codename}->getTotalRedirects($filter_data);
		
			$data['redirects'] = $this->{'model_extension_module_' . $this->codename}->getRedirects($filter_data);
				
			if ($order == 'ASC') {
				$data['sort_url_from'] = $this->url->link($this->route . '/redirect', $url_token . '&sort=url_from' . '&order=DESC&' . $url_page, true);
		
				foreach ($languages as $language) {
					$data['sort_url_to'][$language['language_id']] = $this->url->link($this->route . '/redirect', $url_token . '&sort=url_to_' . $language['language_id'] . '&order=DESC&' . $url_page, true);
				}
			} else {
				$data['sort_url_from'] = $this->url->link($this->route . '/redirect', $url_token . '&sort=url_from' . '&order=ASC&' . $url_page, true);
		
				foreach ($languages as $language) {
					$data['sort_url_to'][$language['language_id']] = $this->url->link($this->route . '/redirect', $url_token . '&sort=url_to_' . $language['language_id'] . '&order=ASC&' . $url_page, true);
				}
			}
				
			$pagination = new Pagination();
			$pagination->total = $redirect_total;
			$pagination->page = $page;
			$pagination->limit = $data['setting']['list_limit'];
			$pagination->url = $this->url->link($this->route . '/redirect', $url_token . '&' . $url_sort . '&' . $url_order . '&page={page}', true);

			$data['pagination'] = $pagination->render();
		
			$data['results'] = sprintf($this->language->get('text_pagination'), ($redirect_total) ? (($page - 1) * $data['setting']['list_limit']) + 1 : 0, ((($page - 1) * $data['setting']['list_limit']) > ($redirect_total - $data['setting']['list_limit'])) ? $redirect_total : ((($page - 1) * $data['setting']['list_limit']) + $data['setting']['list_limit']), $redirect_total, ceil($redirect_total / $data['setting']['list_limit']));
		
			$data['languages'] = $languages;
			$data['filter'] = $filter;
			
			$this->response->setOutput($this->load->view($this->route . '/redirect', $data));
		} else {
			// Setting
			$this->config->load($this->config_file);
			$config_feature_setting = ($this->config->get($this->codename . '_feature_setting')) ? $this->config->get($this->codename . '_feature_setting') : array();
		
			$data['features'] = array();
		
			foreach ($config_feature_setting as $feature) {
				if (substr($feature['name'], 0, strlen('text_')) == 'text_') {
					$feature['name'] = $this->language->get($feature['name']);
				}
						
				$data['features'][] = $feature;
			}
			
			$this->response->setOutput($this->load->view($this->route . '/install', $data));
		}
	}
	
	public function export_import() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		
		if ($this->d_shopunity) {		
			$this->load->model('extension/d_shopunity/mbooth');
				
			$this->model_extension_d_shopunity_mbooth->validateDependencies($this->codename);
		}
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
				
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .= 'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .= 'user_token=' . $this->session->data['user_token'];
		}
		
		$url_store = 'store_id=' . $store_id;
		
		// Styles and Scripts
		$this->document->addStyle('view/stylesheet/d_bootstrap_extra/bootstrap.css');
		$this->document->addScript('view/javascript/d_bootstrap_switch/js/bootstrap-switch.min.js');
        $this->document->addStyle('view/javascript/d_bootstrap_switch/css/bootstrap-switch.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/core/normalize/normalize.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/themes/light/light.css');
		$this->document->addStyle('view/stylesheet/d_seo_module.css');
				
		// Heading
		$this->document->setTitle($this->language->get('heading_title_main'));
		$data['heading_title'] = $this->language->get('heading_title_main');
		
		// Variable
		$data['codename'] = $this->codename;
		$data['route'] = $this->route;
		$data['version'] = $this->extension['version'];
		$data['config'] = $this->config_file;
		$data['d_shopunity'] = $this->d_shopunity;
		$data['store_id'] = $store_id;
		$data['stores'] = $this->{'model_extension_module_' . $this->codename}->getStores();
				
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		$data['installed'] = in_array($this->codename, $installed_seo_extensions) ? true : false;
						
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$data['server'] = HTTPS_SERVER;
			$data['catalog'] = HTTPS_CATALOG;
		} else {
			$data['server'] = HTTP_SERVER;
			$data['catalog'] = HTTP_CATALOG;
		}
						
		// Action
		$data['href_setting'] = $this->url->link($this->route . '/setting', $url_token . '&' . $url_store, true);
		$data['href_generator'] = $this->url->link($this->route . '/generator', $url_token . '&' . $url_store, true);
		$data['href_url_keyword'] = $this->url->link($this->route . '/url_keyword', $url_token . '&' . $url_store, true);
		$data['href_redirect'] = $this->url->link($this->route . '/redirect', $url_token . '&' . $url_store, true);
		$data['href_export_import'] = $this->url->link($this->route . '/export_import', $url_token . '&' . $url_store, true);
		$data['href_instruction'] = $this->url->link($this->route . '/instruction', $url_token . '&' . $url_store, true);
		
		$data['module_link'] = $this->url->link($this->route, $url_token . '&' . $url_store, true);
		$data['setup'] = $this->url->link($this->route . '/setupExtension', $url_token, true);
		$data['install'] = $this->url->link($this->route . '/installExtension', $url_token, true);
		$data['export'] = $this->url->link($this->route . '/export', $url_token, true);
		$data['import'] = $this->url->link($this->route . '/import', $url_token, true);
		
		if (VERSION >= '3.0.0.0') {
			$data['cancel'] = $this->url->link('marketplace/extension', $url_token . '&type=module', true);
		} elseif (VERSION >= '2.3.0.0') {
			$data['cancel'] = $this->url->link('extension/extension', $url_token . '&type=module', true);
		} else {
			$data['cancel'] = $this->url->link('extension/module', $url_token, true);
		}
		
		// Tab
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_generator'] = $this->language->get('text_generator');
		$data['text_url_keywords'] = $this->language->get('text_url_keywords');
		$data['text_redirects'] = $this->language->get('text_redirects');
		$data['text_export_import'] = $this->language->get('text_export_import');
		$data['text_instructions'] = $this->language->get('text_instructions');
		
		$data['text_export'] = $this->language->get('text_export');
		$data['text_import'] = $this->language->get('text_import');
				
		// Button
		$data['button_cancel'] = $this->language->get('button_cancel');	
		$data['button_setup'] = $this->language->get('button_setup');
		$data['button_export'] = $this->language->get('button_export');
		$data['button_import'] = $this->language->get('button_import');
				
		// Entry
		$data['entry_store'] = $this->language->get('entry_store');
		$data['entry_sheet'] = $this->language->get('entry_sheet');
		$data['entry_export'] = $this->language->get('entry_export');
		$data['entry_upload'] = $this->language->get('entry_upload');
		$data['entry_import'] = $this->language->get('entry_import');
				
		// Text
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_install'] = $this->language->get('text_install');
		$data['text_setup'] = $this->language->get('text_setup');
		$data['text_full_setup'] = $this->language->get('text_full_setup');
		$data['text_custom_setup'] = $this->language->get('text_custom_setup');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_powered_by'] = $this->language->get('text_powered_by');
						
		// Help
		$data['help_setup'] = $this->language->get('help_setup');
		$data['help_full_setup'] = $this->language->get('help_full_setup');
		$data['help_custom_setup'] = $this->language->get('help_custom_setup');
		
		// Notification
		foreach ($this->error as $key => $error) {
			$data['error'][$key] = $error;
		}
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		
		// Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $url_token, true)
		);

		if (VERSION >= '3.0.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('marketplace/extension', $url_token . '&type=module', true)
			);
		} elseif (VERSION >= '2.3.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('extension/extension', $url_token . '&type=module', true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('extension/module', $url_token, true)
			);
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_seo_module'),
			'href' => $this->url->link('extension/module/d_seo_module', $url_token . '&' . $url_store, true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_url'),
			'href' => $this->url->link($this->route, $url_token . '&' . $url_store, true)
		);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		if ($data['installed']) {		
			// Setting 		
			$this->config->load($this->config_file);
			$data['setting'] = ($this->config->get($this->codename)) ? $this->config->get($this->codename) : array();
		
			$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $store_id);
			$setting = isset($setting['module_' . $this->codename . '_setting']) ? $setting['module_' . $this->codename . '_setting'] : array();
		
			if (!empty($setting)) {
				$data['setting'] = array_replace_recursive($data['setting'], $setting);
			}
			
			$this->response->setOutput($this->load->view($this->route . '/export_import', $data));
		} else {
			// Setting
			$this->config->load($this->config_file);
			$config_feature_setting = ($this->config->get($this->codename . '_feature_setting')) ? $this->config->get($this->codename . '_feature_setting') : array();
		
			$data['features'] = array();
		
			foreach ($config_feature_setting as $feature) {
				if (substr($feature['name'], 0, strlen('text_')) == 'text_') {
					$feature['name'] = $this->language->get($feature['name']);
				}
						
				$data['features'][] = $feature;
			}
			
			$this->response->setOutput($this->load->view($this->route . '/install', $data));
		}
	}
	
	public function instruction() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		
		if ($this->d_shopunity) {		
			$this->load->model('extension/d_shopunity/mbooth');
				
			$this->model_extension_d_shopunity_mbooth->validateDependencies($this->codename);
		}
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
				
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .= 'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .= 'user_token=' . $this->session->data['user_token'];
		}
		
		$url_store = 'store_id=' . $store_id;
		
		// Styles and Scripts
		$this->document->addStyle('view/stylesheet/d_bootstrap_extra/bootstrap.css');
		$this->document->addScript('view/javascript/d_bootstrap_switch/js/bootstrap-switch.min.js');
        $this->document->addStyle('view/javascript/d_bootstrap_switch/css/bootstrap-switch.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/core/normalize/normalize.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/themes/light/light.css');
		$this->document->addStyle('view/stylesheet/d_seo_module.css');
				
		// Heading
		$this->document->setTitle($this->language->get('heading_title_main'));
		$data['heading_title'] = $this->language->get('heading_title_main');
		
		// Variable
		$data['codename'] = $this->codename;
		$data['route'] = $this->route;
		$data['version'] = $this->extension['version'];
		$data['config'] = $this->config_file;
		$data['d_shopunity'] = $this->d_shopunity;
		$data['store_id'] = $store_id;
						
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		$data['installed'] = in_array($this->codename, $installed_seo_extensions) ? true : false;
						
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$data['server'] = HTTPS_SERVER;
			$data['catalog'] = HTTPS_CATALOG;
		} else {
			$data['server'] = HTTP_SERVER;
			$data['catalog'] = HTTP_CATALOG;
		}
		
		$url = '';
		
		if (isset($this->request->get['store_id'])) {
			$url .=  '&store_id=' . $store_id;
		}
				
		// Action
		$data['href_setting'] = $this->url->link($this->route . '/setting', $url_token . '&' . $url_store, true);
		$data['href_generator'] = $this->url->link($this->route . '/generator', $url_token . '&' . $url_store, true);
		$data['href_url_keyword'] = $this->url->link($this->route . '/url_keyword', $url_token . '&' . $url_store, true);
		$data['href_redirect'] = $this->url->link($this->route . '/redirect', $url_token . '&' . $url_store, true);
		$data['href_export_import'] = $this->url->link($this->route . '/export_import', $url_token . '&' . $url_store, true);
		$data['href_instruction'] = $this->url->link($this->route . '/instruction', $url_token . '&' . $url_store, true);
		
		$data['setup'] = $this->url->link($this->route . '/setupExtension', $url_token, true);
		$data['install'] = $this->url->link($this->route . '/installExtension', $url_token, true);
		
		if (VERSION >= '3.0.0.0') {
			$data['cancel'] = $this->url->link('marketplace/extension', $url_token . '&type=module', true);
		} elseif (VERSION >= '2.3.0.0') {
			$data['cancel'] = $this->url->link('extension/extension', $url_token . '&type=module', true);
		} else {
			$data['cancel'] = $this->url->link('extension/module', $url_token, true);
		}
		
		// Tab
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_generator'] = $this->language->get('text_generator');
		$data['text_url_keywords'] = $this->language->get('text_url_keywords');
		$data['text_redirects'] = $this->language->get('text_redirects');
		$data['text_export_import'] = $this->language->get('text_export_import');
		$data['text_instructions'] = $this->language->get('text_instructions');
						
		// Button
		$data['button_cancel'] = $this->language->get('button_cancel');	
		$data['button_setup'] = $this->language->get('button_setup');
										
		// Text
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_install'] = $this->language->get('text_install');
		$data['text_setup'] = $this->language->get('text_setup');
		$data['text_full_setup'] = $this->language->get('text_full_setup');
		$data['text_custom_setup'] = $this->language->get('text_custom_setup');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_powered_by'] = $this->language->get('text_powered_by');
		$data['text_instructions_full'] = $this->language->get('text_instructions_full');
				
		// Help
		$data['help_setup'] = $this->language->get('help_setup');
		$data['help_full_setup'] = $this->language->get('help_full_setup');
		$data['help_custom_setup'] = $this->language->get('help_custom_setup');
		
		// Notification
		foreach ($this->error as $key => $error) {
			$data['error'][$key] = $error;
		}
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		
		// Breadcrumbs
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $url_token, true)
		);

		if (VERSION >= '3.0.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('marketplace/extension', $url_token . '&type=module', true)
			);
		} elseif (VERSION >= '2.3.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('extension/extension', $url_token . '&type=module', true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_modules'),
				'href' => $this->url->link('extension/module', $url_token, true)
			);
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_seo_module'),
			'href' => $this->url->link('extension/module/d_seo_module', $url_token . '&' . $url_store, true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_url'),
			'href' => $this->url->link($this->route, $url_token . '&' . $url_store, true)
		);
								
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		if ($data['installed']) {
			$this->response->setOutput($this->load->view($this->route . '/instruction', $data));
		} else {
			// Setting
			$this->config->load($this->config_file);
			$config_feature_setting = ($this->config->get($this->codename . '_feature_setting')) ? $this->config->get($this->codename . '_feature_setting') : array();
		
			$data['features'] = array();
		
			foreach ($config_feature_setting as $feature) {
				if (substr($feature['name'], 0, strlen('text_')) == 'text_') {
					$feature['name'] = $this->language->get($feature['name']);
				}
						
				$data['features'][] = $feature;
			}
			
			$this->response->setOutput($this->load->view($this->route . '/install', $data));
		}
	}
		
	public function save() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$old_setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $store_id);
			
			$new_setting = array_replace_recursive($old_setting, $this->request->post);
						
			if (isset($this->request->post['module_' . $this->codename . '_status']) && $this->request->post['module_' . $this->codename . '_status']) {
				$new_setting['module_' . $this->codename . '_setting']['control_element']['enable_status']['implemented'] = 1;
			}
						
			$this->model_setting_setting->editSetting('module_' . $this->codename, $new_setting, $store_id);
			
			$save_data = array(
				'old_setting'		=> $old_setting,
				'new_setting'		=> $new_setting,
				'store_id'			=> $store_id
			);			

			$installed_seo_url_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOURLExtensions();
			
			foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
				$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/save', $save_data);
			}
						
			$data['success'] = $this->language->get('success_save');
		}
						
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function generateFields() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		if (isset($this->request->post['module_' . $this->codename . '_generator_setting']['sheet']) && $this->validate()) {
			$generator_data = array(
				'store_id'		=> $store_id,
				'sheet'			=> $this->request->post['module_' . $this->codename . '_generator_setting']['sheet']
			);
			
			$installed_seo_url_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOURLExtensions();
		
			foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/url_generator_generate_fields', $generator_data);
				
				if (isset($info['error'])) {
					$this->error = array_replace_recursive($this->error, $info['error']);
				}
			}
			
			if (!$this->error) {
				$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $store_id);
			
				$sheet = reset($generator_data['sheet']);
				$sheet_code = key($generator_data['sheet']);
				$field = reset($sheet['field']);
				$field_code = key($sheet['field']);
			
				$setting['module_' . $this->codename . '_setting']['control_element']['generate_' . $field_code . '_' . $sheet_code]['implemented'] = 1;
				
				$this->model_setting_setting->editSetting('module_' . $this->codename, $setting, $store_id);
				
				$data['success'] = $this->language->get('success_generate');
			}
		}
		
		$data['error'] = $this->error;
						
		$this->response->setOutput(json_encode($data));
	}
	
	public function clearFields() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		if (isset($this->request->post['module_' . $this->codename . '_generator_setting']['sheet']) && $this->validate()) {
			$generator_data = array(
				'store_id'		=> $store_id,
				'sheet'			=> $this->request->post['module_' . $this->codename . '_generator_setting']['sheet']
			);
			
			$installed_seo_url_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOURLExtensions();
		
			foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/url_generator_clear_fields', $generator_data);
				
				if (isset($info['error'])) {
					$this->error = array_replace_recursive($this->error, $info['error']);
				}
			}
			
			if (!$this->error) {
				$data['success'] = $this->language->get('success_clear');
			}
		}
		
		$data['error'] = $this->error;
						
		$this->response->setOutput(json_encode($data));
	}
		
	public function createDefaultURLElements() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		$this->config->load($this->config_file);
		$config_setting = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
								
		if ($this->validate()) {
			$this->{'model_extension_module_' . $this->codename}->createDefaultURLElements($config_setting['default_url_keywords'], $store_id);
						
			$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $store_id);
			
			$setting['module_' . $this->codename . '_setting']['control_element']['create_default_url_elements']['implemented'] = 1;
						
			$this->model_setting_setting->editSetting('module_' . $this->codename, $setting, $store_id);
			
			$data['success'] = $this->language->get('success_create_default_url_keywords');
		}
				
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function addURLElement() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
				
		if (isset($this->request->post['url_element']) && $this->validateAddURLElement()) {			
			$url_element_data = $this->request->post['url_element'];
			$url_element_data['store_id'] = $store_id;
						
			$installed_seo_url_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOURLExtensions();
			
			foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
				$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/add_url_element', $url_element_data);
			}
									
			$data['success'] = $this->language->get('success_add_url_keyword');
		}
		
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function editURLElement() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		if (isset($this->request->post['route']) && isset($this->request->post['language_id']) && isset($this->request->post['url_keyword']) && $this->validateEditURLElement()) {
			$url_element_data = array(
				'route'				=> $this->request->post['route'],
				'store_id'			=> $store_id,
				'language_id'		=> $this->request->post['language_id'],
				'url_keyword'		=> $this->request->post['url_keyword']
			);
		
			$installed_seo_url_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOURLExtensions();
			
			foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
				$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/edit_url_element', $url_element_data);
			}
		}
			
		$data['error'] = $this->error;
		
		$this->response->setOutput(json_encode($data));
	}
		
	public function deleteURLElements() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
				
		if (isset($this->request->post['selected']) && $this->validate()) {
			$installed_seo_url_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOURLExtensions();
			
			foreach ($this->request->post['selected'] as $route) {			
				$url_element_data = array(
					'route'				=> $route,
					'store_id'			=> $store_id
				);
			
				foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
					$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/delete_url_element', $url_element_data);
				}
			}
			
			$data['success'] = $this->language->get('success_delete_url_keywords');
		}
		
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function addRedirect() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
				
		if (isset($this->request->post['redirect']) && $this->validateAddRedirect()) {					
			$this->{'model_extension_module_' . $this->codename}->addRedirect($this->request->post['redirect']);
						
			$data['success'] = $this->language->get('success_add_redirect');
		}
		
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function editRedirect() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		
		if (isset($this->request->post['url_redirect_id']) && isset($this->request->post['field_code']) && isset($this->request->post['value']) && $this->validateEditRedirect()) {
			$redirect_data = array(
				'url_redirect_id'	=> $this->request->post['url_redirect_id'],
				'field_code'		=> $this->request->post['field_code'],
				'value'				=> $this->request->post['value']
			);
		
			$this->{'model_extension_module_' . $this->codename}->editRedirect($redirect_data);
			
			$data['value'] = $this->request->post['value'];
		}
			
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function deleteRedirect() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
				
		if (isset($this->request->post['selected']) && $this->validate()) {
			foreach ($this->request->post['selected'] as $url_from) {
				$this->{'model_extension_module_' . $this->codename}->deleteRedirect($url_from);
			}
			
			$data['success'] = $this->language->get('success_delete_redirects');
		}
		
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function export() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		
		if (isset($this->request->post['store_id'])) { 
			$store_id = $this->request->post['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		if (isset($this->request->post['sheet_codes'])) { 
			$sheet_codes = $this->request->post['sheet_codes']; 
		} else {  
			$sheet_codes = array();
		}
		
		// Setting
		$url_setting = array();
		
		$installed_seo_url_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOURLExtensions();
		
		foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/url_config');
			if ($info) $url_setting = array_replace_recursive($url_setting, $info);
		}
								
		$sheets = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($url_setting['sheet'], 'sort_order');
		
		$store = $this->{'model_extension_module_' . $this->codename}->getStore($store_id);
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		if (file_exists(DIR_SYSTEM . 'library/d_excel_reader_writer.php')) {						
			$d_excel_reader_writer = new d_excel_reader_writer();

			foreach ($sheet_codes as $sheet_code) {			
				if ($sheet_code == 'url_keyword') {
					// Set the column widths				
					$column_widths = array(max(strlen('route') + 4, 30) + 1);
					
					foreach ($languages as $language) {
						$column_widths[] = max(strlen('url_keyword') + 4, 30) + 1;
					}
		
					// The heading row and column styles									
					$header = array('route' => 'string');
					
					foreach ($languages as $language) {
						$header['url_keyword' . '(' . $language['code'] . ')'] = 'string';
					}
					
					$d_excel_reader_writer->setColumnWidths($column_widths);
					$d_excel_reader_writer->writeSheetHeader($sheet_code, $header);	

					// The actual custom pages data
					foreach ($sheets as $sheet) {
						$export_data = array(
							'store_id'			=> $store_id,
							'sheet_code'		=> $sheet['code']
						);
			
						$url_elements = array();
						
						foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
							$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/export_url_elements', $export_data);
							if ($info) $url_elements = array_replace_recursive($url_elements, $info);
						}
						
						foreach ($url_elements as $route => $url_element) {
							$data = array(html_entity_decode($url_element['route'], ENT_QUOTES,'UTF-8'));
							
							foreach ($languages as $language) {
								if (isset($url_element['url_keyword'][$language['language_id']])) {
									$data[] = html_entity_decode($url_element['url_keyword'][$language['language_id']], ENT_QUOTES, 'UTF-8');
								} else {
									$data[] = '';
								}
							}
							
							$d_excel_reader_writer->writeSheetRow($sheet_code, $data);
						}
					}
				}

				if ($sheet_code == 'redirect') {
					// Set the column widths				
					$column_widths = array(max(strlen('url_from') + 4, 50) + 1);
					
					foreach ($languages as $language) {
						$column_widths[] = max(strlen('url_to') + 4, 50) + 1;
					}
					
					$d_excel_reader_writer->setColumnWidths($column_widths);
		
					// The heading row and column styles									
					$header = array('url_from' => 'string');
					
					foreach ($languages as $language) {
						$header['url_to' . '(' . $language['code'] . ')'] = 'string';
					}
					
					$d_excel_reader_writer->writeSheetHeader($sheet_code, $header);	
										
					$store_url_info = $this->{'model_extension_module_' . $this->codename}->getURLInfo($store['url']);
					$store_url = $store_url_info['host'] . $store_url_info['port'] . $store_url_info['path'];
					
					// The actual redirects data
					$redirects = $this->{'model_extension_module_' . $this->codename}->getRedirects(array('filter_url_from' => $store_url));
				
					foreach ($redirects as $redirect) {
						$data = array(html_entity_decode($redirect['url_from'], ENT_QUOTES,'UTF-8'));
					
						foreach ($languages as $language) {
							if (isset($redirect['url_to_' . $language['language_id']])) {
								$data[] = html_entity_decode($redirect['url_to_' . $language['language_id']], ENT_QUOTES,'UTF-8');
							} else {
								$data[] = '';
							}
						}	
					
						$d_excel_reader_writer->writeSheetRow($sheet_code, $data);
					}
				}
			}
			
			$filename = $this->codename . ' ' . $store['name'] . ' ' . date('Y-m-d') . '.xlsx';
					
			$this->response->addHeader('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			$this->response->addHeader('Content-Disposition: attachment;filename="' . $filename . '"');
			$this->response->addHeader('Cache-Control: max-age=0');
			
			$this->response->setOutput($d_excel_reader_writer->writeToString());
		}
	}
	
	public function import() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		
		if (isset($this->request->post['store_id'])) { 
			$store_id = $this->request->post['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		// Setting
		$url_setting = array();
		
		$installed_seo_url_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOURLExtensions();
		
		foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/url_config');
			if ($info) $url_setting = array_replace_recursive($url_setting, $info);
		}
								
		$sheets = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($url_setting['sheet'], 'sort_order');
				
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validateImport())) {
			if ((isset($this->request->files['upload'])) && (is_uploaded_file($this->request->files['upload']['tmp_name'])) && file_exists(DIR_SYSTEM . 'library/d_excel_reader_writer.php')) {
				$filepath = $this->request->files['upload']['tmp_name'];
				$filename = $this->request->files['upload']['name'];
				$mimetype = $this->request->files['upload']['type'];
				
				$d_excel_reader_writer = new d_excel_reader_writer();
				
				// parse uploaded spreadsheet file
				$reader = $d_excel_reader_writer->readFromFile($filepath, $filename, $mimetype);
								
				// get worksheet if there
				$sheet_codes = $reader->Sheets();
				
				foreach ($sheet_codes as $sheet_index => $sheet_code) {
					if ($sheet_code == 'url_keyword') {				
						$reader->ChangeSheet($sheet_index);
						
						$elements = array();
						$header = array();
						
						foreach ($reader as $row => $row_data) {
							if (!$header) {
								$header = $row_data;
																							
								continue;
							}
							
							foreach ($header as $col => $col_data) {
								$cell = isset($row_data[$col]) ? $row_data[$col] : '';
								$elements[$row][$header[$col]] = htmlspecialchars($cell);
							}
						}
						
						$url_elements = array();
					
						foreach ($elements as $element) {
							$url_element = array();
						
							if (isset($element['route']) && $element['route']) {
								$url_element['route'] = $element['route'];
							} else {
								continue;
							}
						
							$url_element['url_keyword'] = array();
						
							foreach ($languages as $language) {
								if (isset($element['url_keyword' . '(' . $language['code'] . ')'])) {
									$url_element['url_keyword'][$language['language_id']] = $element['url_keyword' . '(' . $language['code'] . ')'];
								}
							}
						
							$url_elements[] = $url_element;
						}
						
						$import_data = array(
							'store_id'			=> $store_id,
							'url_elements'		=> $url_elements
						);
										
						foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
							$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/import_url_elements', $import_data);
							
							if (isset($info['error'])) {
								$this->error = array_replace_recursive($this->error, $info['error']);
							}
						}
					}
					
					if ($sheet_code == 'redirect') {				
						$reader->ChangeSheet($sheet_index);
						
						$elements = array();
						$header = array();
						
						foreach ($reader as $row => $row_data) {
							if (!$header) {
								$header = $row_data;
																							
								continue;
							}
							
							foreach ($header as $col => $col_data) {
								$cell = isset($row_data[$col]) ? $row_data[$col] : '';
								$elements[$row][$header[$col]] = htmlspecialchars($cell);
							}
						}
						
						$redirects = array();
					
						foreach ($elements as $element) {
							$redirect = array();
						
							if (isset($element['url_from']) && $element['url_from']) {						
								$url_from_info = $this->{'model_extension_module_' . $this->codename}->getURLInfo($element['url_from']);
								$redirect['url_from'] = $url_from_info['host'] . $url_from_info['port'] . $url_from_info['path'];
							
								if (isset($url_from_info['data']['route'])) {
									$redirect['url_from'] .= '?route=' . $url_from_info['data']['route'];
								} 
							} else {
								continue;
							}
						
							foreach ($languages as $language) {
								if (isset($element['url_to' . '(' . $language['code'] . ')'])) {
									$redirect['url_to_' . $language['language_id']] = $element['url_to' . '(' . $language['code'] . ')'];
								}
							}
						
							$redirects[] = $redirect;
						
							$this->{'model_extension_module_' . $this->codename}->saveRedirects($redirects, $store_id);
						}
					}
				}
			}
		}
						
		$data['error'] = $this->error;
		
		if (!$this->error) {
			$data['success'] = $this->language->get('success_import');
		}
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function setupExtension() {
		$this->load->model($this->route);
		
		$info = $this->load->controller('extension/d_seo_module/d_seo_module/control_setup_extension');
		
		$this->load->language($this->route);
		
		if (isset($info['error'])) {
			$this->error = array_replace_recursive($this->error, $info['error']);
		}
		
		if (!$this->error) {
			$data['success'] = $this->language->get('success_install');
		}
		
		$data['error'] = $this->error;

		$this->response->setOutput(json_encode($data));
	}
	
	public function installExtension() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		$this->load->model('user/user_group');
						
		if ($this->validateInstall()) {
			$this->{'model_extension_module_' . $this->codename}->installExtension();
			
			if (file_exists(DIR_APPLICATION . 'model/extension/d_opencart_patch/extension.php') && file_exists(DIR_APPLICATION . 'model/extension/d_opencart_patch/user.php')) {
				$this->load->model('extension/d_opencart_patch/extension');			
				$this->load->model('extension/d_opencart_patch/user');

				$user_group_id = $this->model_extension_d_opencart_patch_user->getGroupId();				
				
				// Install SEO Module URL
				if (!$this->model_extension_d_opencart_patch_extension->isInstalled('d_seo_module_url')) {
					$this->model_extension_d_opencart_patch_extension->install('module', 'd_seo_module_url');
				
					$this->model_user_user_group->addPermission($user_group_id, 'access', 'extension/module/d_seo_module_url');
					$this->model_user_user_group->addPermission($user_group_id, 'modify', 'extension/module/d_seo_module_url');
				}
				
				$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
				
				foreach ($stores as $store) {
					$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $store['store_id']);
										
					$setting['module_' . $this->codename . '_status'] = 1;
					$setting['module_' . $this->codename . '_setting']['control_element']['enable_status']['implemented'] = 1;
			
					$this->model_setting_setting->editSetting('module_' . $this->codename, $setting, $store['store_id']);
				}
				
				// Install SEO Module URL Keyword
				if (!$this->model_extension_d_opencart_patch_extension->isInstalled('d_seo_module_url_keyword')) {
					$this->model_extension_d_opencart_patch_extension->install('dashboard', 'd_seo_module_url_keyword');
			
					$setting = array(
						'dashboard_d_seo_module_url_keyword_status' => true,
						'dashboard_d_seo_module_url_keyword_width' => 12,
						'dashboard_d_seo_module_url_keyword_sort_order' => 40
					);
				
					$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
				
					foreach ($stores as $store) {
						$setting['dashboard_d_seo_module_url_keyword_setting']['stores_id'][] = $store['store_id'];
					}
			
					$this->model_setting_setting->editSetting('dashboard_d_seo_module_url_keyword', $setting);
					
					$this->model_user_user_group->addPermission($user_group_id, 'access', 'extension/dashboard');
					$this->model_user_user_group->addPermission($user_group_id, 'modify', 'extension/dashboard');					
					$this->model_user_user_group->addPermission($user_group_id, 'access', 'extension/dashboard/d_seo_module_url_keyword');
					$this->model_user_user_group->addPermission($user_group_id, 'modify', 'extension/dashboard/d_seo_module_url_keyword');
				}
				
				// Install SEO Module URL Redirect
				if (!$this->model_extension_d_opencart_patch_extension->isInstalled('d_seo_module_url_redirect')) {
					$this->model_extension_d_opencart_patch_extension->install('dashboard', 'd_seo_module_url_redirect');
			
					$setting = array(
						'dashboard_d_seo_module_url_redirect_status' => true,
						'dashboard_d_seo_module_url_redirect_width' => 12,
						'dashboard_d_seo_module_url_redirect_sort_order' => 41
					);
				
					$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
				
					foreach ($stores as $store) {
						$setting['dashboard_d_seo_module_url_redirect_setting']['stores_id'][] = $store['store_id'];
					}
			
					$this->model_setting_setting->editSetting('dashboard_d_seo_module_url_redirect', $setting);
					
					$this->model_user_user_group->addPermission($user_group_id, 'access', 'extension/dashboard');
					$this->model_user_user_group->addPermission($user_group_id, 'modify', 'extension/dashboard');
					$this->model_user_user_group->addPermission($user_group_id, 'access', 'extension/dashboard/d_seo_module_url_redirect');
					$this->model_user_user_group->addPermission($user_group_id, 'modify', 'extension/dashboard/d_seo_module_url_redirect');		
				}
			}
			
			$data['success'] = $this->language->get('success_install');
		}
		
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function uninstallExtension() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		$this->load->model('user/user_group');
				
		if ($this->validateUninstall()) {
			$this->{'model_extension_module_' . $this->codename}->uninstallExtension();
			
			if (file_exists(DIR_APPLICATION . 'model/extension/d_opencart_patch/extension.php') && file_exists(DIR_APPLICATION . 'model/extension/d_opencart_patch/user.php')) {
				$this->load->model('extension/d_opencart_patch/extension');			
				$this->load->model('extension/d_opencart_patch/user');
				
				$user_group_id = $this->model_extension_d_opencart_patch_user->getGroupId();
				
				// Uninstall SEO Module URL Keyword
				if ($this->model_extension_d_opencart_patch_extension->isInstalled('d_seo_module_url_keyword')) {
					$this->model_extension_d_opencart_patch_extension->uninstall('dashboard', 'd_seo_module_url_keyword');
					$this->model_setting_setting->deleteSetting('dashboard_d_seo_module_url_keyword');
						
					$this->model_user_user_group->removePermission($user_group_id, 'access', 'extension/dashboard/d_seo_module_url_keyword');
					$this->model_user_user_group->removePermission($user_group_id, 'modify', 'extension/dashboard/d_seo_module_url_keyword');	
				}
				
				// Uninstall SEO Module URL Redirect
				if ($this->model_extension_d_opencart_patch_extension->isInstalled('d_seo_module_url_redirect')) {
					$this->model_extension_d_opencart_patch_extension->uninstall('dashboard', 'd_seo_module_url_redirect');
					$this->model_setting_setting->deleteSetting('dashboard_d_seo_module_url_redirect');
						
					$this->model_user_user_group->removePermission($user_group_id, 'access', 'extension/dashboard/d_seo_module_url_redirect');
					$this->model_user_user_group->removePermission($user_group_id, 'modify', 'extension/dashboard/d_seo_module_url_redirect');		
				}
			}
			
			$data['success'] = $this->language->get('success_uninstall');
		}
						
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function install() {
		if ($this->d_shopunity) {
			$this->load->model('extension/d_shopunity/mbooth');
			
			$this->model_extension_d_shopunity_mbooth->installDependencies($this->codename);  
		}
	}
	
	/*
	*	Return Custom Page Exception Routes.
	*/	
	public function getCustomPageExceptionRoutes() {
		$this->load->model($this->route);
		
		if ($this->config->get($this->codename . '_custom_page_exception_routes')) {
			return $this->config->get($this->codename . '_custom_page_exception_routes');
		}
		
		$custom_page_exception_routes = array();
							
		$installed_seo_url_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOURLExtensions();
									
		foreach ($installed_seo_url_extensions as $installed_seo_url_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_url_extension . '/custom_page_exception_routes');
			if ($info) $custom_page_exception_routes = array_merge($custom_page_exception_routes, $info);
		}
		
		$this->config->set($this->codename . '_custom_page_exception_routes', $custom_page_exception_routes);
		
		return $custom_page_exception_routes;
	}
	
	/*
	*	Refresh URL Cache.
	*/
	public function refreshURLCache() {
		$this->load->model($this->route);
		
		$this->{'model_extension_module_' . $this->codename}->refreshURLCache();
									
		if (!$this->error) {
			$data['success'] = $this->language->get('success_refresh_url_cache');
		}
		
		$data['error'] = $this->error;
		
		$this->response->setOutput(json_encode($data));
	}

	/*
	*	Clear URL Cache.
	*/
	public function clearURLCache() {
		$this->load->model($this->route);
		
		$this->{'model_extension_module_' . $this->codename}->clearURLCache();
									
		if (!$this->error) {
			$data['success'] = $this->language->get('success_clear_url_cache');
		}
		
		$data['error'] = $this->error;
		
		$this->response->setOutput(json_encode($data));
	}	
				
	/*
	*	Validator Functions.
	*/		
	private function validate($permission = 'modify') {				
		if (!$this->user->hasPermission($permission, $this->route)) {
			$this->error['warning'] = $this->language->get('error_permission');
			
			return false;
		}
		
		return true;
	}
	
	private function validateAddURLElement($permission = 'modify') {				
		if (!$this->user->hasPermission($permission, $this->route)) {
			$this->error['warning'] = $this->language->get('error_permission');
			
			return false;
		}
		
		if (!preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $this->request->post['url_element']['route']) && !preg_match('/[A-Za-z0-9]+\=[0-9]+/i', $this->request->post['url_element']['route'])) {
			$this->error['warning'] = $this->language->get('error_route');
			
			return false;
		}
		
		$field_data = array(
			'field_code' => 'url_keyword',
			'filter' => array(
				'route' => $this->request->post['url_element']['route'],
				'store_id' => $this->request->get['store_id']
			)
		);
			
		$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
			
		if ($url_keywords) {
			$this->error['warning'] = sprintf($this->language->get('error_route_exists'), $this->request->post['url_element']['route']);
			
			return false;
		}
		
		foreach ($this->request->post['url_element']['url_keyword'] as $language_id => $url_keyword) {
			if (!trim($url_keyword)) {
				$this->error['warning'] = sprintf($this->language->get('error_url_keyword'), $url_keyword);
				
				return false;
			}
			
			$field_data = array(
				'field_code' => 'url_keyword',
				'filter' => array(
					'store_id' => $this->request->get['store_id'],
					'keyword' => $url_keyword
				)
			);
			
			$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
				
			if ($url_keywords) {
				$this->error['warning'] = sprintf($this->language->get('error_url_keyword_exists'), $url_keyword);
				
				return false;
			}
		}	
		
		return true;
	}
	
	private function validateEditURLElement($permission = 'modify') {				
		if (!$this->user->hasPermission($permission, $this->route)) {
			$this->error['warning'] = $this->language->get('error_permission');
			
			return false;
		}
		
		if (!trim($this->request->post['url_keyword'])) {
			$this->error['warning'] = sprintf($this->language->get('error_url_keyword'), $this->request->post['url_keyword']);
			
			return false;
		}
		
		$field_data = array(
			'field_code' => 'url_keyword',
			'filter' => array(
				'store_id' => $this->request->get['store_id'],
				'keyword' => $this->request->post['url_keyword']
			)
		);
			
		$url_keywords = $this->load->controller('extension/module/d_seo_module/getFieldElements', $field_data);
		
		if ($url_keywords) {
			foreach ($url_keywords as $route => $store_url_keywords) {
				if ($route != $this->request->post['route']) {
					$this->error['warning'] = sprintf($this->language->get('error_url_keyword_exists'), $this->request->post['url_keyword']);
				
					return false;
				}
			}
		}	
		
		return true;
	}
	
	private function validateAddRedirect($permission = 'modify') {
		$this->load->model($this->route);
						
		if (!$this->user->hasPermission($permission, $this->route)) {
			$this->error['warning'] = $this->language->get('error_permission');
			
			return false;
		}
		
		if (!trim($this->request->post['redirect']['url_from'])) {
			$this->error['warning'] = $this->language->get('error_url_from');
			
			return false;
		}
		
		$url_from_info = $this->{'model_extension_module_' . $this->codename}->getURLInfo($this->request->post['redirect']['url_from']);
		$url_from = $url_from_info['host'] . $url_from_info['port'] . $url_from_info['path'];
		
		if (isset($url_from_info['data']['route'])) {
			$url_from .= '?route=' . $url_from_info['data']['route'];
		} 
		
		$this->request->post['redirect']['url_from'] = $url_from;
		
		if ($this->{'model_extension_module_' . $this->codename}->getRedirects(array('filter' => array('url_from' => $url_from)))) {
			$this->error['warning'] = sprintf($this->language->get('error_url_from_exists'), $url_from);
			
			return false;
		}
				
		return true;
	}
	
	private function validateEditRedirect($permission = 'modify') {
		$this->load->model($this->route);
						
		if (!$this->user->hasPermission($permission, $this->route)) {
			$this->error['warning'] = $this->language->get('error_permission');
			
			return false;
		}
		
		if ($this->request->post['field_code'] == 'url_from') {
			if (!trim($this->request->post['value'])) {
				$this->error['warning'] = $this->language->get('error_url_from');
				
				return false;
			}
			
			$url_from_info = $this->{'model_extension_module_' . $this->codename}->getURLInfo($this->request->post['value']);
			$url_from = $url_from_info['host'] . $url_from_info['port'] . $url_from_info['path'];
			
			if (isset($url_from_info['data']['route'])) {
				$url_from .= '?route=' . $url_from_info['data']['route'];
			}
			
			$this->request->post['value'] = $url_from;
			
			$redirects = $this->{'model_extension_module_' . $this->codename}->getRedirects(array('filter' => array('url_from' => $url_from)));
			
			foreach ($redirects as $redirect) {
				if ($redirect['url_redirect_id'] != $this->request->post['url_redirect_id']) {
					$this->error['warning'] = sprintf($this->language->get('error_url_from_exists'), $url_from);
					
					return false;
				}
			}
		}
			
		return true;
	}
	
	private function validateImport($permission = 'modify') {				
		if (!$this->user->hasPermission($permission, $this->route)) {
			$this->error['warning'] = $this->language->get('error_permission');
			
			return false;
		}
		
		if (!isset($this->request->files['upload']['name']) || !$this->request->files['upload']['name']) {
			$this->error['warning'] = $this->language->get('error_upload_name');
			
			return false;
		}
		
		$ext = strtolower(pathinfo($this->request->files['upload']['name'], PATHINFO_EXTENSION));
		
		if (($ext != 'xls') && ($ext != 'xlsx') && ($ext != 'ods')) {
			$this->error['warning'] = $this->language->get('error_upload_ext');
			
			return false;
		}

		return true;
	}
	
	private function validateInstall($permission = 'modify') {
		$this->load->model($this->route);
				
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
		if (in_array($this->codename, $installed_seo_extensions)) {
			$this->error['warning'] = $this->language->get('error_installed');
			
			return false;
		}
		
		if (!in_array('d_seo_module', $installed_seo_extensions)) {
			$info = $this->load->controller('extension/d_seo_module/d_seo_module/control_install_extension');
			
			$this->load->language($this->route);
			
			if ($info) {		
				if ($info['error']) {
					$this->error = $info['error'];
				
					return false;
				} else {
					$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
				} 
			} else {
				$this->error['warning'] = $this->language->get('error_dependence_d_seo_module');
				
				return false;
			}
		}
		
		$installed_seo_extensions[] = $this->codename;
		
		$this->{'model_extension_module_' . $this->codename}->saveSEOExtensions($installed_seo_extensions);
										
		return true;
	}
	
	private function validateUninstall($permission = 'modify') {
		$this->load->model($this->route);
				
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
		$key = array_search($this->codename, $installed_seo_extensions);
		if ($key !== false) unset($installed_seo_extensions[$key]);
		
		$this->{'model_extension_module_' . $this->codename}->saveSEOExtensions($installed_seo_extensions);

		return true;
	}
}