<?php
class ControllerExtensionModuleDSEOModule extends Controller {
	private $codename = 'd_seo_module';
	private $route = 'extension/module/d_seo_module';
	private $config_file = 'd_seo_module';
	private $extension = array();
	private $error = array();

	public function __construct($registry) {
		parent::__construct($registry);

		$this->d_shopunity = (file_exists(DIR_SYSTEM . 'library/d_shopunity/extension/d_shopunity.json'));
		$this->extension = json_decode(file_get_contents(DIR_SYSTEM . 'library/d_shopunity/extension/' . $this->codename . '.json'), true);
	}
	
	public function index() {		
		$this->dashboard();
	}
	
	public function dashboard() {
		$this->load->language($this->route);

		$this->load->model($this->route);
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		
		if ($this->d_shopunity) {
			$this->load->model('extension/d_shopunity/mbooth');
				
			$this->model_extension_d_shopunity_mbooth->validateDependencies($this->codename);
		}
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$this->update();
		
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
		$data['extension_id'] = $this->extension['extension_id'];
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
		$data['href_dashboard'] = $this->url->link($this->route . '/dashboard', $url_token . '&' . $url_store, true);
		$data['href_setting'] = $this->url->link($this->route . '/setting', $url_token . '&' . $url_store, true);
		$data['href_multi_store_field'] = $this->url->link($this->route . '/multi_store_field', $url_token . '&' . $url_store, true);
		$data['href_target_keyword'] = $this->url->link($this->route . '/target_keyword', $url_token . '&' . $url_store, true);
		$data['href_export_import'] = $this->url->link($this->route . '/export_import', $url_token . '&' . $url_store, true);
		$data['href_instruction'] = $this->url->link($this->route . '/instruction', $url_token . '&' . $url_store, true);
			
		$data['module_link'] = $this->url->link($this->route, $url_token . '&' . $url_store, true);
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
		$data['text_dashboard'] = $this->language->get('text_dashboard');
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_multi_store_fields'] = $this->language->get('text_multi_store_fields');
		$data['text_target_keywords'] = $this->language->get('text_target_keywords');
		$data['text_export_import'] = $this->language->get('text_export_import');
		$data['text_instructions'] = $this->language->get('text_instructions');
		
		// Button
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_setup'] = $this->language->get('button_setup');
		$data['button_install'] = $this->language->get('button_install');
		$data['button_view'] = $this->language->get('button_view');
		$data['button_reset'] = $this->language->get('button_reset');
		
		// Text
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_install'] = $this->language->get('text_install');
		$data['text_setup'] = $this->language->get('text_setup');
		$data['text_full_setup'] = $this->language->get('text_full_setup');
		$data['text_custom_setup'] = $this->language->get('text_custom_setup');
		$data['text_seo_extensions'] = $this->language->get('text_seo_extensions');
		$data['text_quick_setup'] = $this->language->get('text_quick_setup');
		$data['text_implemented'] = $this->language->get('text_implemented');
		$data['text_details'] = $this->language->get('text_details');
		$data['text_no_control_elements'] = $this->language->get('text_no_control_elements');
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
		
		if (!file_exists(DIR_SYSTEM . '/library/d_shopunity/extension/d_seo_module_pack.json')) {
			$data['d_seo_module_pack']['info'] = $this->language->get('help_d_seo_module_pack');
			$data['d_seo_module_pack']['extension_id'] = 130;
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
			'href' => $this->url->link($this->route, $url_token . '&' . $url_store, true)
		);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
				
		if ($data['installed']) {
			$control_extensions = array();
		
			$seo_extensions = $this->{'model_extension_module_' . $this->codename}->getSEOExtensions();
		
			foreach ($seo_extensions as $seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $seo_extension . '/control_extensions');
				if ($info) $control_extensions = array_merge($control_extensions, $info);
			}
		
			$control_extensions = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($control_extensions, 'sort_order');
		
			$data['control_extensions'] = array();
		
			foreach ($control_extensions as $control_extension) {
				if (isset($control_extension['code']) && isset($control_extension['name']) && isset($control_extension['image']) && isset($control_extension['href'])) {				
					$url_extension_code = 'extension_code=' . $control_extension['code'];
								
					$control_extension['install_href'] = $this->url->link('extension/module/' . $this->codename . '/installControlExtension', $url_token . '&' . $url_extension_code, true);
					$control_extension['installed'] = in_array($control_extension['code'], $installed_seo_extensions) ? true : false;
										
					$data['control_extensions'][] = $control_extension;
				}
			}
		
			$control_elements = array();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/control_elements', array('store_id' => $store_id));
				if ($info) $control_elements = array_merge($control_elements, $info);
			}
		
			$data['quick_setup_total'] = 0;
			$data['implemented_total'] = 0;
			$data['control_elements'] = array();
		
			foreach ($control_elements as $control_element) {
				if (isset($control_element['extension_code']) && isset($control_element['element_code']) && isset($control_element['name']) && isset($control_element['description']) && isset($control_element['confirm']) && isset($control_element['implemented'])) {						
					$url_extension_code = 'extension_code=' . $control_element['extension_code'];
					$url_element_code = 'element_code=' . $control_element['element_code'];
								
					$control_element['edit_href'] = $this->url->link('extension/module/' . $this->codename . '/executeControlElement', $url_token . '&' . $url_store . '&' . $url_extension_code . '&' . $url_element_code, true);
				
					if ($control_element['implemented']) {
						$data['implemented_total']++;
					} else {
						$data['quick_setup_total']++;
					}
				
					$data['control_elements'][] = $control_element;
				}
			}
		
			$data['control_elements'] = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($data['control_elements'], 'weight', SORT_DESC);
			
			$this->response->setOutput($this->load->view($this->route . '/dashboard', $data));
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
		
		$this->update();
		
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
		
		$data['catalog_url_info'] = $this->{'model_extension_module_' . $this->codename}->getURLInfo($data['catalog']);
								
		// Action
		$data['href_dashboard'] = $this->url->link($this->route . '/dashboard', $url_token . '&' . $url_store, true);
		$data['href_setting'] = $this->url->link($this->route . '/setting', $url_token . '&' . $url_store, true);
		$data['href_multi_store_field'] = $this->url->link($this->route . '/multi_store_field', $url_token . '&' . $url_store, true);
		$data['href_target_keyword'] = $this->url->link($this->route . '/target_keyword', $url_token . '&' . $url_store, true);
		$data['href_export_import'] = $this->url->link($this->route . '/export_import', $url_token . '&' . $url_store, true);
		$data['href_instruction'] = $this->url->link($this->route . '/instruction', $url_token . '&' . $url_store, true);
			
		$data['module_link'] = $this->url->link($this->route, $url_token . '&' . $url_store, true);
		$data['action'] = $this->url->link($this->route . '/save', $url_token . '&' . $url_store, true);
		$data['setup'] = $this->url->link($this->route . '/setupExtension', $url_token, true);
		$data['install'] = $this->url->link($this->route . '/installExtension', $url_token, true);
		$data['uninstall'] = $this->url->link($this->route . '/uninstallExtension', $url_token, true);
		$data['store_setting'] = $this->url->link('setting/setting', $url_token, true);
			
		if (VERSION >= '3.0.0.0') {
			$data['cancel'] = $this->url->link('marketplace/extension', $url_token . '&type=module', true);
		} elseif (VERSION >= '2.3.0.0') {
			$data['cancel'] = $this->url->link('extension/extension', $url_token . '&type=module', true);
		} else {
			$data['cancel'] = $this->url->link('extension/module', $url_token, true);
		}
		
		// Tab
		$data['text_dashboard'] = $this->language->get('text_dashboard');
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_multi_store_fields'] = $this->language->get('text_multi_store_fields');
		$data['text_target_keywords'] = $this->language->get('text_target_keywords');
		$data['text_export_import'] = $this->language->get('text_export_import');
		$data['text_instructions'] = $this->language->get('text_instructions');
		$data['text_basic_settings'] = $this->language->get('text_basic_settings');
		$data['text_htaccess'] = $this->language->get('text_htaccess');
		$data['text_robots'] = $this->language->get('text_robots');

		// Button
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_setup'] = $this->language->get('button_setup');
		$data['button_uninstall'] = $this->language->get('button_uninstall');
		$data['button_view_store_setting'] = $this->language->get('button_view_store_setting');
		
		// Entry
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_list_limit'] = $this->language->get('entry_list_limit');
		$data['entry_uninstall'] = $this->language->get('entry_uninstall');
		$data['entry_text'] = $this->language->get('entry_text');

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
		$data['help_htaccess_setting'] = $this->language->get('help_htaccess_setting');
		$data['help_htaccess_subfolder'] = $this->language->get('help_htaccess_subfolder');
		$data['help_robots'] = $this->language->get('help_robots');
		
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
						
			$data['htaccess'] = $this->{'model_extension_module_' . $this->codename}->getFileData('htaccess');		
			$data['robots'] = $this->{'model_extension_module_' . $this->codename}->getFileData('robots');
		
			if (!$data['htaccess']['status'] && !trim($data['htaccess']['text'])) {
				$data['htaccess']['text'] = str_replace('[catalog_url_path]', $data['catalog_url_info']['path'], $data['setting']['default_htaccess']);
			}
		
			if (!$data['robots']['status'] && !trim($data['robots']['text'])) {
				$data['robots']['text'] = str_replace('[catalog_url]', $data['catalog'], $data['setting']['default_robots']);
				$data['robots']['text'] = str_replace('[catalog_url_host]', $data['catalog_url_info']['host'], $data['robots']['text']);
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
	
	public function multi_store_field() {
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
		
		$this->update();
		
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
		$data['href_dashboard'] = $this->url->link($this->route . '/dashboard', $url_token . '&' . $url_store, true);
		$data['href_setting'] = $this->url->link($this->route . '/setting', $url_token . '&' . $url_store, true);
		$data['href_multi_store_field'] = $this->url->link($this->route . '/multi_store_field', $url_token . '&' . $url_store, true);
		$data['href_target_keyword'] = $this->url->link($this->route . '/target_keyword', $url_token . '&' . $url_store, true);
		$data['href_export_import'] = $this->url->link($this->route . '/export_import', $url_token . '&' . $url_store, true);
		$data['href_instruction'] = $this->url->link($this->route . '/instruction', $url_token . '&' . $url_store, true);
			
		$data['action'] = $this->url->link($this->route . '/save', $url_token, true);
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
		$data['text_dashboard'] = $this->language->get('text_dashboard');
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_multi_store_fields'] = $this->language->get('text_multi_store_fields');
		$data['text_target_keywords'] = $this->language->get('text_target_keywords');
		$data['text_export_import'] = $this->language->get('text_export_import');
		$data['text_instructions'] = $this->language->get('text_instructions');
		
		// Button
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_setup'] = $this->language->get('button_setup');
						
		// Column
		$data['column_field'] = $this->language->get('column_field');
		$data['column_multi_store_status'] = $this->language->get('column_multi_store_status');
				
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
			'href' => $this->url->link($this->route, $url_token . '&' . $url_store, true)
		);
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		if ($data['installed']) {
			$data['field_setting'] = $this->getFieldInfo();
			
			$this->response->setOutput($this->load->view($this->route . '/multi_store_field', $data));
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
	
	public function target_keyword() {
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
		
		$this->update();
		
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
		$data['href_dashboard'] = $this->url->link($this->route . '/dashboard', $url_token . '&' . $url_store, true);
		$data['href_setting'] = $this->url->link($this->route . '/setting', $url_token . '&' . $url_store, true);
		$data['href_multi_store_field'] = $this->url->link($this->route . '/multi_store_field', $url_token . '&' . $url_store, true);
		$data['href_target_keyword'] = $this->url->link($this->route . '/target_keyword', $url_token . '&' . $url_store, true);
		$data['href_export_import'] = $this->url->link($this->route . '/export_import', $url_token . '&' . $url_store, true);
		$data['href_instruction'] = $this->url->link($this->route . '/instruction', $url_token . '&' . $url_store, true);
		
		$data['module_link'] = $this->url->link($this->route, $url_token . '&' . $url_store, true);
		$data['store_url'] = $this->url->link($this->route . '/target_keyword', $url_token . '&' . $url_sheet, true);
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
		$data['text_dashboard'] = $this->language->get('text_dashboard');
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_multi_store_fields'] = $this->language->get('text_multi_store_fields');
		$data['text_target_keywords'] = $this->language->get('text_target_keywords');
		$data['text_export_import'] = $this->language->get('text_export_import');
		$data['text_instructions'] = $this->language->get('text_instructions');
								
		// Button
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');	
		$data['button_setup'] = $this->language->get('button_setup');
		$data['button_filter'] = $this->language->get('button_filter');
		$data['button_clear_filter'] = $this->language->get('button_clear_filter');
		$data['button_create_default_target_keywords'] = $this->language->get('button_create_default_target_keywords');
		$data['button_add_target_keyword'] = $this->language->get('button_add_target_keyword');
		$data['button_delete_target_keywords'] = $this->language->get('button_delete_target_keywords');	
						
		// Column
		$data['column_route'] = $this->language->get('column_route');
		$data['column_target_keyword'] = $this->language->get('column_target_keyword');
		
		// Entry
		$data['entry_route'] = $this->language->get('entry_route');
		$data['entry_target_keyword'] = $this->language->get('entry_target_keyword');
				
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
		$data['text_add_target_keyword'] = $this->language->get('text_add_target_keyword');
		$data['text_delete_target_keywords_confirm'] = $this->language->get('text_delete_target_keywords_confirm');
		$data['text_create_default_target_keywords_confirm'] = $this->language->get('text_create_default_target_keywords_confirm');
		
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

			$target_setting = array();
		
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/target_config');
				if ($info) $target_setting = array_replace_recursive($target_setting, $info);
			}
		
			$sheets = array();
		
			foreach ($target_setting['sheet'] as $sheet) {
				if (isset($sheet['code']) && isset($sheet['icon']) && isset($sheet['name']) && isset($sheet['sort_order'])) {								
					$sheets[] = array(
						'code'			=> $sheet['code'],
						'icon'			=> $sheet['icon'],
						'name'			=> $sheet['name'],
						'sort_order'	=> $sheet['sort_order'],
						'url'			=> $this->url->link($this->route . '/target_keyword', $url_token . '&' . $url_store . '&sheet_code=' . $sheet['code'], true)
					);
				}
			}
						
			$sheets = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($sheets, 'sort_order');
			
			$filter_data = array(
				'store_id'			=> $store_id,
				'sheet_code'		=> $sheet_code,
				'filter'	  	  	=> $filter
			);
			
			$target_elements = array();
						
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/target_elements', $filter_data);
				if ($info) $target_elements = array_replace_recursive($target_elements, $info);
			}
				
			$data['target_elements'] = array();
		
			$i = 0;
			
			foreach ($target_elements as $target_element) {
				if (isset($target_element['target_keyword'])) {
					foreach ($target_element['target_keyword'] as $language_id => $target_keyword) {
						foreach ($target_keyword as $sort_order => $keyword) {
							$field_data = array(
								'field_code' => 'target_keyword',
								'filter' => array(
									'store_id' => $store_id,
									'keyword' => $keyword
								)
							);
			
							$target_keywords = $this->getFieldElements($field_data);
							$store_target_keywords = reset($target_keywords);
							
							if ((count($target_keywords) > 1) || (count(reset($store_target_keywords)) > 1)) {
								$target_element['target_keyword_duplicate'][$language_id][$sort_order] = 1;
							}
						}
					}
				}
				
				if (($i >= (($page - 1) * $data['setting']['list_limit'])) && ($i < ((($page - 1) * $data['setting']['list_limit']) + $data['setting']['list_limit']))) {
					$data['target_elements'][] = $target_element;
				}
			
				$i++;
			
				if ($i == ((($page - 1) * $data['setting']['list_limit']) + $data['setting']['list_limit'])) break;
			}
							
			$pagination = new Pagination();
			$pagination->total = count($target_elements);
			$pagination->page = $page;
			$pagination->limit = $data['setting']['list_limit'];
			$pagination->url = $this->url->link($this->route . '/target_keyword', $url_token . '&' . $url_store . '&' . $url_sheet . '&page={page}', true);

			$data['pagination'] = $pagination->render();

			$data['results'] = sprintf($this->language->get('text_pagination'), (count($target_elements)) ? (($page - 1) * $data['setting']['list_limit']) + 1 : 0, ((($page - 1) * $data['setting']['list_limit']) > (count($target_elements) - $data['setting']['list_limit'])) ? count($target_elements) : ((($page - 1) * $data['setting']['list_limit']) + $data['setting']['list_limit']), count($target_elements), ceil(count($target_elements) / $data['setting']['list_limit']));
			
			$data['sheets'] = $sheets;
			$data['filter'] = $filter;

			$this->response->setOutput($this->load->view($this->route . '/target_keyword', $data));
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
		
		$this->update();

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
		$data['href_dashboard'] = $this->url->link($this->route . '/dashboard', $url_token . '&' . $url_store, true);
		$data['href_setting'] = $this->url->link($this->route . '/setting', $url_token . '&' . $url_store, true);
		$data['href_multi_store_field'] = $this->url->link($this->route . '/multi_store_field', $url_token . '&' . $url_store, true);
		$data['href_target_keyword'] = $this->url->link($this->route . '/target_keyword', $url_token . '&' . $url_store, true);
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
		$data['text_dashboard'] = $this->language->get('text_dashboard');
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_multi_store_fields'] = $this->language->get('text_multi_store_fields');
		$data['text_target_keywords'] = $this->language->get('text_target_keywords');
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
		
		$this->update();
		
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
						
		// Action
		$data['href_dashboard'] = $this->url->link($this->route . '/dashboard', $url_token . '&' . $url_store, true);
		$data['href_setting'] = $this->url->link($this->route . '/setting', $url_token . '&' . $url_store, true);
		$data['href_multi_store_field'] = $this->url->link($this->route . '/multi_store_field', $url_token . '&' . $url_store, true);
		$data['href_target_keyword'] = $this->url->link($this->route . '/target_keyword', $url_token . '&' . $url_store, true);
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
		$data['text_dashboard'] = $this->language->get('text_dashboard');
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_multi_store_fields'] = $this->language->get('text_multi_store_fields');
		$data['text_target_keywords'] = $this->language->get('text_target_keywords');
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

			if (isset($this->request->post['htaccess'])) {
				$this->{'model_extension_module_' . $this->codename}->saveFileData('htaccess', $this->request->post['htaccess']);
				
				if (isset($this->request->post['htaccess']['status']) && $this->request->post['htaccess']['status']) {
					$new_setting['module_' . $this->codename . '_setting']['control_element']['enable_htaccess']['implemented'] = 1;
				}
			}
			
			if (isset($this->request->post['robots'])) {
				$this->{'model_extension_module_' . $this->codename}->saveFileData('robots', $this->request->post['robots']);
				
				if (isset($this->request->post['robots']['status']) && $this->request->post['robots']['status']) {
					$new_setting['module_' . $this->codename . '_setting']['control_element']['enable_robots']['implemented'] = 1;
				}
			}

			$this->model_setting_setting->editSetting('module_' . $this->codename, $new_setting, $store_id);
			
			$save_data = array(
				'old_setting'		=> $old_setting,
				'new_setting'		=> $new_setting,
				'store_id'			=> $store_id
			);			

			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/save', $save_data);
			}			
			
			$data['success'] = $this->language->get('success_save');
		}

		$data['error'] = $this->error;

		$this->response->setOutput(json_encode($data));
	}
	
	public function installControlExtension() {
		$this->load->language($this->route);
		
		$data = array();
						
		if (isset($this->request->get['extension_code']) && $this->validate()) {
			$extension_code	= $this->request->get['extension_code'];
			
			if ($extension_code) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $extension_code . '/control_install_extension');
				if ($info) $data = $info;
			}
		}
						
		$this->response->setOutput(json_encode($data));
	}
	
	public function executeControlElement() {
		$this->load->language($this->route);

		$this->load->model($this->route);
		$this->load->model('setting/setting');
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
				
		if (isset($this->request->get['extension_code']) && isset($this->request->get['element_code']) && $this->validate()) {
			$extension_code	= $this->request->get['extension_code'];
			$element_code = $this->request->get['element_code'];
			
			if ($extension_code && $element_code) {
				$control_element_data = array(
					'store_id'			=> $store_id,
					'element_code'		=> $element_code
				);
			
				$info = $this->load->controller('extension/' . $this->codename . '/' . $extension_code . '/control_execute_element', $control_element_data);
				
				if (isset($info['error'])) {
					$this->error = array_replace_recursive($this->error, $info['error']);
				}
			}
		}
		
		if (!$this->error) {					
			$data['success'] = $this->language->get('success_execute');
		}
		
		$data['error'] = $this->error;
					
		$this->response->setOutput(json_encode($data));
	}
	
	public function createDefaultTargetElements() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		$this->config->load($this->config_file);
		$config_setting = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
								
		if ($this->validate()) {
			$this->{'model_extension_module_' . $this->codename}->createDefaultTargetElements($config_setting['default_target_keywords'], $store_id);
						
			$data['success'] = $this->language->get('success_create_default_target_keywords');
		}
		
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function addTargetElement() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
				
		if (isset($this->request->post['target_element']) && $this->validateAddTargetElement()) {			
			$target_element_data = $this->request->post['target_element'];
			$target_element_data['store_id'] = $store_id;
						
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/add_target_element', $target_element_data);
			}
									
			$data['success'] = $this->language->get('success_add_target_keyword');
		}
		
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function editTargetElement() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
		
		if (isset($this->request->post['route']) && isset($this->request->post['language_id']) && isset($this->request->post['target_keyword']) && $this->validateEditTargetElement()) {
			$target_element_data = array(
				'route'				=> $this->request->post['route'],
				'store_id'			=> $store_id,
				'language_id'		=> $this->request->post['language_id'],
				'target_keyword'	=> $this->request->post['target_keyword']
			);
		
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/edit_target_element', $target_element_data);
			}
		}
			
		$data['error'] = $this->error;
		
		$this->response->setOutput(json_encode($data));
	}
		
	public function deleteTargetElements() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		
		if (isset($this->request->get['store_id'])) { 
			$store_id = $this->request->get['store_id']; 
		} else {  
			$store_id = 0;
		}
				
		if (isset($this->request->post['selected']) && $this->validate()) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($this->request->post['selected'] as $route) {			
				$target_element_data = array(
					'route'				=> $route,
					'store_id'			=> $store_id
				);
			
				foreach ($installed_seo_extensions as $installed_seo_extension) {
					$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/delete_target_element', $target_element_data);
				}
			}
			
			$data['success'] = $this->language->get('success_delete_target_keywords');
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
		$target_setting = array();
		
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/target_config');
			if ($info) $target_setting = array_replace_recursive($target_setting, $info);
		}
								
		$sheets = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($target_setting['sheet'], 'sort_order');
		
		$store = $this->{'model_extension_module_' . $this->codename}->getStore($store_id);
		$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
		
		if (file_exists(DIR_SYSTEM . 'library/d_excel_reader_writer.php')) {
			$d_excel_reader_writer = new d_excel_reader_writer();
					
			foreach ($sheet_codes as $sheet_code) {			
				if ($sheet_code == 'target_keyword') {
					// Set the column widths				
					$column_widths = array(max(strlen('route') + 4, 30) + 1);
					
					foreach ($languages as $language) {
						$column_widths[] = max(strlen('target_keyword') + 4, 30) + 1;
					}
					
					$d_excel_reader_writer->setColumnWidths($column_widths);
		
					// The heading row and column styles									
					$header = array('route' => 'string');
					
					foreach ($languages as $language) {
						$header['target_keyword' . '(' . $language['code'] . ')'] = 'string';
					}
					
					$d_excel_reader_writer->writeSheetHeader($sheet_code, $header);
											
					// The actual custom pages data				
					foreach ($sheets as $sheet) {
						$export_data = array(
							'store_id'			=> $store_id,
							'sheet_code'		=> $sheet['code']
						);
			
						$target_elements = array();
						
						foreach ($installed_seo_extensions as $installed_seo_extension) {
							$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/export_target_elements', $export_data);
							if ($info) $target_elements = array_replace_recursive($target_elements, $info);
						}
						
						foreach ($target_elements as $route => $target_element) {
							$data = array(html_entity_decode($target_element['route'], ENT_QUOTES,'UTF-8'));
							
							foreach ($languages as $language) {
								if (isset($target_element['target_keyword'][$language['language_id']])) {
									$data[] = html_entity_decode($target_element['target_keyword'][$language['language_id']], ENT_QUOTES, 'UTF-8');
								} else {
									$data[] = '';
								}
							}
							
							$d_excel_reader_writer->writeSheetRow($sheet_code, $data);
						}
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
		$target_setting = array();
		
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/target_config');
			if ($info) $target_setting = array_replace_recursive($target_setting, $info);
		}
								
		$sheets = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($target_setting['sheet'], 'sort_order');
		
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
				
				// get worksheet if there				
				foreach ($sheet_codes as $sheet_index => $sheet_code) {
					if ($sheet_code == 'target_keyword') {				
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
								
						$target_elements = array();
					
						foreach ($elements as $element) {
							$target_element = array();
						
							if (isset($element['route']) && $element['route']) {
								$target_element['route'] = $element['route'];
							} else {
								continue;
							}
						
							$target_element['target_keyword'] = array();
						
							foreach ($languages as $language) {
								if (isset($element['target_keyword' . '(' . $language['code'] . ')'])) {
									$target_element['target_keyword'][$language['language_id']] = $element['target_keyword' . '(' . $language['code'] . ')'];
								}
							}
						
							$target_elements[] = $target_element;
						}
						
						$import_data = array(
							'store_id'			=> $store_id,
							'target_elements'	=> $target_elements
						);
										
						foreach ($installed_seo_extensions as $installed_seo_extension) {
							$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/import_target_elements', $import_data);
							
							if (isset($info['error'])) {
								$this->error = array_replace_recursive($this->error, $info['error']);
							}
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
						
		$seo_extensions = $this->{'model_extension_module_' . $this->codename}->getSEOExtensions();
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
		// Install SEO Modules
		foreach ($seo_extensions as $seo_extension) {
			if (!in_array($seo_extension, $installed_seo_extensions)) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $seo_extension . '/control_install_extension');
				
				if (isset($info['error'])) {
					$this->error = array_replace_recursive($this->error, $info['error']);
				}
			}
		}
				
		if (!$this->error) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
				
			foreach ($stores as $store) {
				$control_elements = array();
		
				foreach ($installed_seo_extensions as $installed_seo_extension) {
					$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/control_elements', array('store_id' => $store['store_id']));
					if ($info) $control_elements = array_merge($control_elements, $info);
				}
				
				$control_elements = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($control_elements, 'weight', SORT_DESC);
				
				foreach ($control_elements as $control_element) {
					if (isset($control_element['extension_code']) && isset($control_element['element_code']) && isset($control_element['name']) && isset($control_element['description']) && isset($control_element['confirm']) && isset($control_element['implemented'])) {								
						if ($control_element['extension_code'] && $control_element['element_code']) {
							$control_element_data = array(
								'store_id'			=> $store['store_id'],
								'element_code'		=> $control_element['element_code']
							);
			
							$info = $this->load->controller('extension/' . $this->codename . '/' . $control_element['extension_code'] . '/control_execute_element', $control_element_data);
				
							if (isset($info['error'])) {
								$this->error = array_replace_recursive($this->error, $info['error']);
							}
						}
					}
				}
			}
		}
		
		$this->load->language($this->route);
		
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
		
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$server = HTTPS_SERVER;
			$catalog = HTTPS_CATALOG;
		} else {
			$server = HTTP_SERVER;
			$catalog = HTTP_CATALOG;
		}
		
		$catalog_url_info = $this->{'model_extension_module_' . $this->codename}->getURLInfo($catalog);
				
		if ($this->validateInstall()) {
			$this->{'model_extension_module_' . $this->codename}->installExtension();
						
			if (file_exists(DIR_APPLICATION . 'model/extension/module/d_event_manager.php')) {
				$this->load->model('extension/module/d_event_manager');
				
				$this->model_extension_module_d_event_manager->installCompatibility();				
				$this->model_extension_module_d_event_manager->deleteEvent($this->codename);
							
				if (VERSION >= '2.3.0.0') {
					$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/view/common/column_left/before', 'extension/module/d_seo_module/column_left_before');
				} else {
					$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/view/common/menu/after', 'extension/module/d_seo_module/menu_after');
					$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/view/common/dashboard/after', 'extension/module/d_seo_module/dashboard_after');
				}
				
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/view/common/header/after', 'extension/module/d_seo_module/header_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/localisation/language/addLanguage/after', 'extension/module/d_seo_module/language_add_language_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/localisation/language/editLanguage/after', 'extension/module/d_seo_module/language_edit_language_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/localisation/language/deleteLanguage/after', 'extension/module/d_seo_module/language_delete_language_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/view/setting/setting/after', 'extension/module/d_seo_module/setting_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/view/setting/store_form/after', 'extension/module/d_seo_module/store_form_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/setting/store/addStore/after', 'extension/module/d_seo_module/store_add_store_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/setting/store/editStore/after', 'extension/module/d_seo_module/store_edit_store_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/setting/store/deleteStore/after', 'extension/module/d_seo_module/store_delete_store_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/view/catalog/category_form/after', 'extension/module/d_seo_module/category_form_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/catalog/category/addCategory/after', 'extension/module/d_seo_module/category_add_category_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/catalog/category/editCategory/after', 'extension/module/d_seo_module/category_edit_category_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/catalog/category/deleteCategory/after', 'extension/module/d_seo_module/category_delete_category_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/view/catalog/product_form/after', 'extension/module/d_seo_module/product_form_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/catalog/product/addProduct/after', 'extension/module/d_seo_module/product_add_product_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/catalog/product/editProduct/after', 'extension/module/d_seo_module/product_edit_product_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/catalog/product/deleteProduct/after', 'extension/module/d_seo_module/product_delete_product_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/view/catalog/manufacturer_form/after', 'extension/module/d_seo_module/manufacturer_form_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/catalog/manufacturer/addManufacturer/after', 'extension/module/d_seo_module/manufacturer_add_manufacturer_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/catalog/manufacturer/editManufacturer/after', 'extension/module/d_seo_module/manufacturer_edit_manufacturer_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/catalog/manufacturer/deleteManufacturer/after', 'extension/module/d_seo_module/manufacturer_delete_manufacturer_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/view/catalog/information_form/after', 'extension/module/d_seo_module/information_form_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/catalog/information/addInformation/after', 'extension/module/d_seo_module/information_add_information_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/catalog/information/editInformation/after', 'extension/module/d_seo_module/information_edit_information_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'admin/model/catalog/information/deleteInformation/after', 'extension/module/d_seo_module/information_delete_information_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/common/header/before', 'extension/module/d_seo_module/header_before');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/common/header/after', 'extension/module/d_seo_module/header_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/common/footer/before', 'extension/module/d_seo_module/footer_before');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/common/footer/after', 'extension/module/d_seo_module/footer_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/common/home/before', 'extension/module/d_seo_module/home_before');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/common/home/after', 'extension/module/d_seo_module/home_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/category/before', 'extension/module/d_seo_module/category_before');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/category/after', 'extension/module/d_seo_module/category_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/model/catalog/category/getCategory/after', 'extension/module/d_seo_module/category_get_category_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/model/catalog/category/getCategories/after', 'extension/module/d_seo_module/category_get_categories_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/product/before', 'extension/module/d_seo_module/product_before');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/product/after', 'extension/module/d_seo_module/product_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/model/catalog/product/getProduct/after', 'extension/module/d_seo_module/product_get_product_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/model/catalog/product/getProducts/after', 'extension/module/d_seo_module/product_get_products_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/manufacturer_list/before', 'extension/module/d_seo_module/manufacturer_list_before');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/manufacturer_list/after', 'extension/module/d_seo_module/manufacturer_list_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/manufacturer_info/before', 'extension/module/d_seo_module/manufacturer_info_before');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/manufacturer_info/after', 'extension/module/d_seo_module/manufacturer_info_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/model/catalog/manufacturer/getManufacturer/after', 'extension/module/d_seo_module/manufacturer_get_manufacturer_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/model/catalog/manufacturer/getManufacturers/after', 'extension/module/d_seo_module/manufacturer_get_manufacturers_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/information/information/before', 'extension/module/d_seo_module/information_before');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/information/information/after', 'extension/module/d_seo_module/information_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/model/catalog/information/getInformation/after', 'extension/module/d_seo_module/information_get_information_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/model/catalog/information/getInformations/after', 'extension/module/d_seo_module/information_get_informations_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/search/before', 'extension/module/d_seo_module/search_before');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/search/after', 'extension/module/d_seo_module/search_after');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/special/before', 'extension/module/d_seo_module/special_before');
				$this->model_extension_module_d_event_manager->addEvent($this->codename, 'catalog/view/product/special/after', 'extension/module/d_seo_module/special_after');
			}
			
			if (file_exists(DIR_APPLICATION . 'model/extension/d_opencart_patch/modification.php')) {
				$this->load->model('extension/d_opencart_patch/modification');
		
				$this->model_extension_d_opencart_patch_modification->setModification($this->codename . '.xml', 1);
				$this->model_extension_d_opencart_patch_modification->refreshCache();
			}
			
			if (file_exists(DIR_APPLICATION . 'model/extension/d_opencart_patch/extension.php')) {
				$this->load->model('extension/d_opencart_patch/extension');
				$this->load->model('extension/d_opencart_patch/user');
				
				$user_group_id = $this->model_extension_d_opencart_patch_user->getGroupId();
				
				// Install SEO Module
				if (!$this->model_extension_d_opencart_patch_extension->isInstalled('d_seo_module')) {
					$this->model_extension_d_opencart_patch_extension->install('module', 'd_seo_module');
				
					$this->model_user_user_group->addPermission($user_group_id, 'access', 'extension/module/d_seo_module');
					$this->model_user_user_group->addPermission($user_group_id, 'modify', 'extension/module/d_seo_module');
				}
				
				$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
				
				foreach ($stores as $store) {
					$setting = $this->model_setting_setting->getSetting('module_' . $this->codename, $store['store_id']);
										
					$setting['module_' . $this->codename . '_status'] = 1;
					$setting['module_' . $this->codename . '_setting']['control_element']['enable_status']['implemented'] = 1;
					$setting['module_' . $this->codename . '_setting']['control_element']['enable_seo_url']['implemented'] = 1;
					$setting['module_' . $this->codename . '_setting']['control_element']['enable_htaccess']['implemented'] = 1;
					$setting['module_' . $this->codename . '_setting']['control_element']['enable_robots']['implemented'] = 1;
								
					$this->model_setting_setting->editSetting('module_' . $this->codename, $setting, $store['store_id']);
				}
				
				$store_setting = $this->model_setting_setting->getSetting('config');
				$store_setting['config_seo_url'] = 1;
			
				$this->model_setting_setting->editSetting('config', $store_setting);
				
				$this->config->load($this->config_file);
				$config_setting = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
				
				$htaccess = $this->{'model_extension_module_' . $this->codename}->getFileData('htaccess');		
			
				if (!$htaccess['status'] && !trim($htaccess['text'])) {
					$htaccess['text'] = str_replace('[catalog_url_path]', $catalog_url_info['path'], $config_setting['default_htaccess']);
				}
			
				$htaccess['status'] = 1;
		
				$this->{'model_extension_module_' . $this->codename}->saveFileData('htaccess', $htaccess);
				
				$robots = $this->{'model_extension_module_' . $this->codename}->getFileData('robots');		
			
				if (!$robots['status'] && !trim($robots['text'])) {
					$robots['text'] = str_replace('[catalog_url]', $catalog, $config_setting['default_robots']);
					$robots['text'] = str_replace('[catalog_url_host]', $catalog_url_info['host'], $robots['text']);
				}
			
				$robots['status'] = 1;
			
				$this->{'model_extension_module_' . $this->codename}->saveFileData('robots', $robots);
												
				// Install SEO Module Target Keyword
				if (!$this->model_extension_d_opencart_patch_extension->isInstalled('d_seo_module_target_keyword')) {
					$this->model_extension_d_opencart_patch_extension->install('dashboard', 'd_seo_module_target_keyword');
			
					$setting = array(
						'dashboard_d_seo_module_target_keyword_status' => true,
						'dashboard_d_seo_module_target_keyword_width' => 12,
						'dashboard_d_seo_module_target_keyword_sort_order' => 20
					);
				
					$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
				
					foreach ($stores as $store) {
						$setting['dashboard_d_seo_module_target_keyword_setting']['stores_id'][] = $store['store_id'];
					}
			
					$this->model_setting_setting->editSetting('dashboard_d_seo_module_target_keyword', $setting);
					
					$this->model_user_user_group->addPermission($user_group_id, 'access', 'extension/dashboard');
					$this->model_user_user_group->addPermission($user_group_id, 'modify', 'extension/dashboard');					
					$this->model_user_user_group->addPermission($user_group_id, 'access', 'extension/dashboard/d_seo_module_target_keyword');
					$this->model_user_user_group->addPermission($user_group_id, 'modify', 'extension/dashboard/d_seo_module_target_keyword');
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
			
			if (file_exists(DIR_APPLICATION . 'model/extension/module/d_event_manager.php')) {
				$this->load->model('extension/module/d_event_manager');
				
				$this->model_extension_module_d_event_manager->deleteEvent($this->codename);
			}
			
			if (file_exists(DIR_APPLICATION . 'model/extension/d_opencart_patch/modification.php')) {
				$this->load->model('extension/d_opencart_patch/modification');
		
				$this->model_extension_d_opencart_patch_modification->setModification($this->codename . '.xml', 0);
				$this->model_extension_d_opencart_patch_modification->refreshCache();
			}
			
			if (file_exists(DIR_APPLICATION . 'model/extension/d_opencart_patch/extension.php')) {
				$this->load->model('extension/d_opencart_patch/extension');
				$this->load->model('extension/d_opencart_patch/user');
				
				$user_group_id = $this->model_extension_d_opencart_patch_user->getGroupId();
				
				// Uninstall SEO Module Target Keyword
				if ($this->model_extension_d_opencart_patch_extension->isInstalled('d_seo_module_target_keyword')) {
					$this->model_extension_d_opencart_patch_extension->uninstall('dashboard', 'd_seo_module_target_keyword');
					$this->model_setting_setting->deleteSetting('dashboard_d_seo_module_target_keyword');
						
					$this->model_user_user_group->removePermission($user_group_id, 'access', 'extension/dashboard/d_seo_module_target_keyword');
					$this->model_user_user_group->removePermission($user_group_id, 'modify', 'extension/dashboard/d_seo_module_target_keyword');
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
		
		$this->update();
	}
	
	public function update() {
		$result = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "url_target'");
		
		if ($result->rows) {
			$this->db->query("DROP TABLE IF EXISTS " . DB_PREFIX . "url_target");
			
			$this->uninstallExtension();
			$this->installExtension();
		}
		
		if (file_exists(DIR_APPLICATION . 'model/extension/d_opencart_patch/modification.php')) {
			$this->load->model('extension/d_opencart_patch/modification');
			
			$result = $this->model_extension_d_opencart_patch_modification->getModificationByCode('d_seo_module');
			
			if ($result && (strpos($result['xml'], '$url_info = parse_url(str_replace') !== false)) {
				$this->model_extension_d_opencart_patch_modification->setModification($this->codename . '.xml', 0);
				$this->model_extension_d_opencart_patch_modification->setModification($this->codename . '.xml', 1);
				$this->model_extension_d_opencart_patch_modification->refreshCache();
			}
		}
	}
	
	public function header_after($route, $data, &$output) {
		$this->load->language($this->route);
		
		$this->load->model($this->route);

		if (file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			$menu_items = array();

			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
					
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/header_menu');
				if ($info) $menu_items = array_merge($menu_items, $info);
			}
		
			$menu_items = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($menu_items, 'sort_order');
				
			$html_menu = '<li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-search fa-lg" style="margin-right: 5px"></i> <span class="hidden-xs hidden-sm hidden-md">' . $this->language->get('text_seo') . '</span> <i class="fa fa-caret-down fa-fw"></i></a><ul class="dropdown-menu dropdown-menu-right" style="min-width: 260px">';
		
			foreach ($menu_items as $menu_item) {
				$html_menu .= $menu_item['html'];
				
				if ($menu_item != end($menu_items)) {
					$html_menu .= '<li class="divider"></li>';
				}
			}
		
			$html_menu .= '</ul></li>';
		
			if ($menu_items) {
				$html_dom = new d_simple_html_dom();
				$html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
		
				if ($html_dom->find('.navbar .nav')) {
					$html_dom->find('.navbar .nav li', 0)->outertext .= $html_menu;
				
					$output = (string)$html_dom;
				}
			}
		}
	}
		
	public function column_left_before($route, &$data) {
		$this->load->language($this->route);
		
		$this->load->model($this->route);

		$menu_items = array();

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/menu');
			if ($info) $menu_items = array_merge($menu_items, $info);
		}
		
		$menu_items = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($menu_items, 'sort_order');

		if ($menu_items) {
			$data['menus'][] = array(
				'id'       => 'menu-seo',
				'icon'	   => 'fa-search',
				'name'	   => $this->language->get('text_seo'),
				'href'     => '',
				'children' => $menu_items
			);
		}
	}
	
	public function menu_after($route, $data, &$output) {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		
		if (file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			$menu_items = array();

			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
					
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/menu');
				if ($info) $menu_items = array_merge($menu_items, $info);
			}
		
			$menu_items = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($menu_items, 'sort_order');
				
			$html_menu = '<li id="menu-seo"><a class="parent"><i class="fa fa-search fa-fw"></i> <span>' . $this->language->get('text_seo') . '</span></a><ul>';
		
			foreach ($menu_items as $menu_item) {
				$html_menu .= '<li>';
			
				if ($menu_item['href']) {
					$html_menu .= '<a href="' . $menu_item['href'] . '">' . $menu_item['name'] . '</a>';
				} else {
					$html_menu .= '<a class="parent">' . $menu_item['name'] . '</a>';
				}
			
				if (isset($menu_item['children']) && $menu_item['children']) {
					$html_menu .= '<ul>';
				
					foreach ($menu_item['children'] as $children_1) {
						$html_menu .= '<li>';
					
						if ($children_1['href']) {
							$html_menu .= '<a href="' . $children_1['href'] . '">' . $children_1['name'] . '</a>';
						} else {
							$html_menu .= '<a class="parent">' . $children_1['name'] . '</a>';
						}
					
						if ($children_1['children']) {
							$html_menu .= '<ul>';
						
							foreach ($children_1['children'] as $children_2) {
								$html_menu .= '<li><a href="' . $children_2['href'] . '">' . $children_2['name'] . '</a></li>';
							}
						
							$html_menu .= '</ul>';
						}
					
						$html_menu .= '</li>';
					}
				
					$html_menu .= '</ul>';
				}
			
				$html_menu .= '</li>';
			}
		
			$html_menu .= '</ul></li>';
		
			if ($menu_items) {
				$html_dom = new d_simple_html_dom();
				$html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
		
				$html_dom->find('#reports', 0)->outertext .= $html_menu;
				
				$output = (string)$html_dom;
			}
		}
	}
	
	public function dashboard_after($route, $data, &$output) {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		
		if (file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			$dashboards = array();

			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/dashboard');
				if ($info) $dashboards = array_merge($dashboards, $info);
			}
		
			$dashboards = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($dashboards, 'sort_order');
				
			// Split the array so the columns width is not more than 12 on each row.
			$width = 0;
			$column = array();
			$rows = array();
		
			foreach ($dashboards as $dashboard) {
				$column[] = $dashboard;
			
				$width = ($width + $dashboard['width']);
			
				if ($width >= 12) {
					$rows[] = $column;
				
					$width = 0;
					$column = array();
				}
			}
		
			$html_dashboard = '';
		
			foreach ($rows as $row) {
				$html_dashboard .= '<div class="row">';
			
				foreach ($row as $dashboard_1) {
					$class = 'col-lg-' . $dashboard_1['width'] . ' col-md-3 col-sm-6';
				
					foreach ($row as $dashboard_2) {
						if ($dashboard_2['width'] > 3) {
							$class = 'col-lg-' . $dashboard_1['width'] . ' col-md-12 col-sm-12';
						}
					}
				
					$html_dashboard .= '<div class="' . $class . '">' . $dashboard_1['html'] . '</div>';
				}
			
				$html_dashboard .= '</div>';
			}
						
			if ($html_dashboard) {
				$html_dom = new d_simple_html_dom();
				$html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
		
				$html_dom->find('#content .container-fluid', 1)->innertext .= $html_dashboard;
				
				$output = (string)$html_dom;
			}
		}
	}

	public function language_add_language_after($route, $data, $output) {
		$this->load->model($this->route);

		$data = $data[0];
		$data['language_id'] = $output;

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/language_add_language', $data);
		}
	}
	
	public function language_edit_language_after($route, $data, $output) {
		$this->load->model($this->route);

		$language_id = $data[0];
		$data = $data[1];
		$data['language_id'] = $language_id;

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/language_edit_language', $data);
		}
	}

	public function language_delete_language_after($route, $data, $output) {
		$this->load->model($this->route);

		$data['language_id'] = $data[0];

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/language_delete_language', $data);
		}
	}
	
	public function setting_after($route, $data, &$output) {
		$this->load->language($this->route);

		$this->load->model($this->route);
		
		if (file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			$html_tab_general = '';
			$html_tab_general_language = array();
			$html_tab_store = '';
			$html_tab_local = '';
			$html_tab_option = '';
			$html_style = '';
			$html_script = '';

			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();

			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$html_tab_general .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/setting_tab_general');
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/setting_tab_general_language');
				
				foreach ($languages as $language) {
					if (!isset($html_tab_general_language[$language['language_id']])) $html_tab_general_language[$language['language_id']] = '';
					
					if (isset($info[$language['language_id']])) {
						$html_tab_general_language[$language['language_id']] .= $info[$language['language_id']];
					}
				}
				
				$html_tab_store .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/setting_tab_store');
				$html_tab_local .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/setting_tab_local');
				$html_tab_option .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/setting_tab_option');
				$html_style .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/setting_style');
				$html_script .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/setting_script');
			}
			
			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);

			if ($html_tab_general) {
				$html_dom->find('#tab-general', 0)->innertext .= $html_tab_general;
			}
			
			if (reset($html_tab_general_language)) {
				$html_languages = '<ul class="nav nav-tabs" id="language">';
				
				foreach ($languages as $language) {
					$html_languages .= '<li' . (($language == reset($languages)) ? ' class="active"' : '') . '><a href="#language' . $language['language_id'] . '" data-toggle="tab"><img src="' . $language['flag'] . '" title="' . $language['name'] . '" /> ' . $language['name'] . '</a></li>';
				}
				
				$html_languages .= '</ul>';
				$html_languages .= '<div class="tab-language tab-content">';
				
				foreach ($languages as $language) {
					$html_languages .= '<div class="tab-pane' . (($language == reset($languages)) ? ' active' : '') . '" id="language' . $language['language_id'] . '"></div>';
				}
				
				$html_languages .= '</div>';

				$html_dom->find('#tab-general', 0)->innertext = $html_languages . $html_dom->find('#tab-general', 0)->innertext;
				$html_dom->load((string)$html_dom, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);

				foreach ($languages as $language) {
					$html_dom->find('#tab-general #language' . $language['language_id'], 0)->innertext .= $html_tab_general_language[$language['language_id']];
				}
			}
			
			if ($html_tab_store) {
				$html_dom->find('#tab-store', 0)->innertext .= $html_tab_store;
			}
			
			if ($html_tab_local) {
				$html_dom->find('#tab-local', 0)->innertext .= $html_tab_local;
			}
			
			if ($html_tab_option) {
				$html_dom->find('#tab-option', 0)->innertext .= $html_tab_option;
			}
						
			if ($html_style) {
				$html_dom->find('#content', 0)->innertext .= $html_style;
			}
			
			if ($html_script) {
				$html_dom->find('#content', 0)->innertext .= $html_script;
			}

			$output = (string)$html_dom;
		}
	}
		
	public function setting_validate($error) {
		$this->load->model($this->route);
				
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/setting_validate', $error);
			if ($info != '') $error = $info;
		}
		
		return $error;
	}
	
	public function setting_edit_setting() {
		$this->load->model($this->route);

		$data = $this->request->post;
		$data['store_id'] = 0;
		
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/setting_edit_setting', $data);
		}
	}
	
	public function store_form_after($route, $data, &$output) {
		$this->load->language($this->route);

		$this->load->model($this->route);
		
		if (file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			$html_tab_general = '';
			$html_tab_general_language = array();
			$html_tab_store = '';
			$html_tab_local = '';
			$html_tab_option = '';
			$html_style = '';
			$html_script = '';

			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();

			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$html_tab_general .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/store_form_tab_general');
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/store_form_tab_general_language');
				
				foreach ($languages as $language) {
					if (!isset($html_tab_general_language[$language['language_id']])) $html_tab_general_language[$language['language_id']] = '';
					
					if (isset($info[$language['language_id']])) {
						$html_tab_general_language[$language['language_id']] .= $info[$language['language_id']];
					}
				}
				
				$html_tab_store .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/store_form_tab_store');
				$html_tab_local .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/store_form_tab_local');
				$html_tab_option .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/store_form_tab_option');
				$html_style .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/store_form_style');
				$html_script .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/store_form_script');
			}
			
			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);

			if ($html_tab_general) {
				$html_dom->find('#tab-general', 0)->innertext .= $html_tab_general;
			}
			
			if (reset($html_tab_general_language)) {
				$html_languages = '<ul class="nav nav-tabs" id="language">';
				
				foreach ($languages as $language) {
					$html_languages .= '<li' . (($language == reset($languages)) ? ' class="active"' : '') . '><a href="#language' . $language['language_id'] . '" data-toggle="tab"><img src="' . $language['flag'] . '" title="' . $language['name'] . '" /> ' . $language['name'] . '</a></li>';
				}
				
				$html_languages .= '</ul>';
				$html_languages .= '<div class="tab-language tab-content">';
				
				foreach ($languages as $language) {
					$html_languages .= '<div class="tab-pane' . (($language == reset($languages)) ? ' active' : '') . '" id="language' . $language['language_id'] . '"></div>';
				}
				
				$html_languages .= '</div>';

				$html_dom->find('#tab-general', 0)->innertext = $html_languages . $html_dom->find('#tab-general', 0)->innertext;
				$html_dom->load((string)$html_dom, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);

				foreach ($languages as $language) {
					$html_dom->find('#tab-general #language' . $language['language_id'], 0)->innertext .= $html_tab_general_language[$language['language_id']];
				}
			}
			
			if ($html_tab_store) {
				$html_dom->find('#tab-store', 0)->innertext .= $html_tab_store;
			}
			
			if ($html_tab_local) {
				$html_dom->find('#tab-local', 0)->innertext .= $html_tab_local;
			}
			
			if ($html_tab_option) {
				$html_dom->find('#tab-option', 0)->innertext .= $html_tab_option;
			}
						
			if ($html_style) {
				$html_dom->find('#content', 0)->innertext .= $html_style;
			}
			
			if ($html_script) {
				$html_dom->find('#content', 0)->innertext .= $html_script;
			}

			$output = (string)$html_dom;
		}
	}
	
	public function store_validate_form($error) {
		$this->load->model($this->route);
				
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/store_validate_form', $error);
			if ($info != '') $error = $info;
		}
		
		return $error;
	}
	
	public function store_add_store_after($route, $data, $output) {
		$this->load->model($this->route);

		$data = $data[0];
		$data['store_id'] = $output;

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/store_add_store', $data);
		}
	}

	public function store_edit_store_after($route, $data, $output) {
		$this->load->model($this->route);

		$store_id = $data[0];
		$data = $data[1];
		$data['store_id'] = $store_id;

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/store_edit_store', $data);
		}
	}
	
	public function store_delete_store_after($route, $data, $output) {
		$this->load->model($this->route);

		$data['store_id'] = $data[0];

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/store_delete_store', $data);
		}
	}
		
	public function category_form_after($route, $data, &$output) {
		$this->load->language($this->route);

		$this->load->model($this->route);
		
		if (file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			$html_tab_general = '';
			$html_tab_general_language = array();
			$html_tab_general_store = array();
			$html_tab_general_store_language = array();
			$html_tab_data = '';
			$html_style = '';
			$html_script = '';

			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
			$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
			unset($stores[0]);
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$html_tab_general .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_form_tab_general');
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_form_tab_general_language');
				
				foreach ($languages as $language) {
					if (!isset($html_tab_general_language[$language['language_id']])) $html_tab_general_language[$language['language_id']] = '';
					
					if (isset($info[$language['language_id']])) {
						$html_tab_general_language[$language['language_id']] .= $info[$language['language_id']];
					}
				}
				
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_form_tab_general_store');
				
				foreach ($stores as $store) {
					if (!isset($html_tab_general_store[$store['store_id']])) $html_tab_general_store[$store['store_id']] = '';
					
					if (isset($info[$store['store_id']])) {
						$html_tab_general_store[$store['store_id']] .= $info[$store['store_id']];
					}
				}
				
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_form_tab_general_store_language');
				
				foreach ($stores as $store) {					
					foreach ($languages as $language) {
						if (!isset($html_tab_general_store_language[$store['store_id']][$language['language_id']])) $html_tab_general_store_language[$store['store_id']][$language['language_id']] = '';
						
						if (isset($info[$store['store_id']][$language['language_id']])) {
							$html_tab_general_store_language[$store['store_id']][$language['language_id']] .= $info[$store['store_id']][$language['language_id']];
						}
					}
				}
				
				$html_tab_data .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_form_tab_data');
				$html_style .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_form_style');
				$html_script .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_form_script');
			}
			
			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);

			if ($html_tab_general) {
				$html_dom->find('#tab-general', 0)->innertext .= $html_tab_general;
			}
			
			if (reset($html_tab_general_language)) {
				foreach ($languages as $language) {
					$html_dom->find('#tab-general #language' . $language['language_id'], 0)->innertext .= $html_tab_general_language[$language['language_id']];
				}
			}
			
			$html_tab_general_language = reset($html_tab_general_store_language);
			
			if ((count($stores)) && (reset($html_tab_general_store) || reset($html_tab_general_language))) {
				$html_stores = '<ul class="nav nav-tabs" id="store">';
								
				foreach ($stores as $store) {
					$html_stores .= '<li' . (($store == reset($stores)) ? ' class="active"' : '') . '><a href="#store_' . $store['store_id'] . '" data-toggle="tab">' . $store['name'] . '</a></li>';
				}
				
				$html_stores .= '</ul>';
				$html_stores .= '<div class="tab-store tab-content">';
				
				foreach ($stores as $store) {
					$html_store_languages = '';
						
					if (reset($html_tab_general_store_language[$store['store_id']])) {
						$html_store_languages = '<ul class="nav nav-tabs" id="store_' . $store['store_id'] . '_language">';
				
						foreach ($languages as $language) {
							$html_store_languages .= '<li' . (($language == reset($languages)) ? ' class="active"' : '') . '><a href="#store_' . $store['store_id'] . '_language_' . $language['language_id'] . '" data-toggle="tab"><img src="' . $language['flag'] . '" title="' . $language['name'] . '" /> ' . $language['name'] . '</a></li>';
						}
				
						$html_store_languages .= '</ul>';
						$html_store_languages .= '<div class="tab-language tab-content">';
				
						foreach ($languages as $language) {
							$html_store_languages .= '<div class="tab-pane' . (($language == reset($languages)) ? ' active' : '') . '" id="store_' . $store['store_id'] . '_language_' . $language['language_id'] . '">' . $html_tab_general_store_language[$store['store_id']][$language['language_id']] . '</div>';
						}
						
						$html_store_languages .= '</div>';
					}
									
					$html_stores .= '<div class="tab-pane' . (($store == reset($stores)) ? ' active' : '') . '" id="store_' . $store['store_id'] . '">' . $html_tab_general_store[$store['store_id']] . $html_store_languages . '</div>';
				}
				
				$html_stores .= '</div>';
				
				$html_dom->find('#tab-general', 0)->innertext .= $html_stores;
				$html_dom->load((string)$html_dom, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			}
			
			if ($html_tab_data) {
				$html_dom->find('#tab-data', 0)->innertext .= $html_tab_data;
			}
						
			if ($html_style) {
				$html_dom->find('#content', 0)->innertext .= $html_style;
			}
			
			if ($html_script) {
				$html_dom->find('#content', 0)->innertext .= $html_script;
			}

			$output = (string)$html_dom;
		}
	}
	
	public function category_validate_form($error) {
		$this->load->model($this->route);
				
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_validate_form', $error);
			if ($info != '') $error = $info;
		}
		
		return $error;
	}

	public function category_add_category_after($route, $data, $output) {
		$this->load->model($this->route);

		$data = $data[0];
		$data['category_id'] = $output;

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_add_category', $data);
		}
	}

	public function category_edit_category_after($route, $data, $output) {
		$this->load->model($this->route);

		$category_id = $data[0];
		$data = $data[1];
		$data['category_id'] = $category_id;

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_edit_category', $data);
		}
	}
	
	public function category_delete_category_after($route, $data, $output) {
		$this->load->model($this->route);

		$data['category_id'] = $data[0];

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_delete_category', $data);
		}
	}

	public function product_form_after($route, $data, &$output) {
		$this->load->language($this->route);

		$this->load->model($this->route);
		
		if (file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			$html_tab_general = '';
			$html_tab_general_language = array();
			$html_tab_general_store = array();
			$html_tab_general_store_language = array();
			$html_tab_data = '';
			$html_tab_links = '';
			$html_style = '';
			$html_script = '';

			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
			$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
			unset($stores[0]);
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$html_tab_general .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_form_tab_general');
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_form_tab_general_language');
				
				foreach ($languages as $language) {
					if (!isset($html_tab_general_language[$language['language_id']])) $html_tab_general_language[$language['language_id']] = '';
					
					if (isset($info[$language['language_id']])) {
						$html_tab_general_language[$language['language_id']] .= $info[$language['language_id']];
					}
				}
				
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_form_tab_general_store');
				
				foreach ($stores as $store) {
					if (!isset($html_tab_general_store[$store['store_id']])) $html_tab_general_store[$store['store_id']] = '';
					
					if (isset($info[$store['store_id']])) {
						$html_tab_general_store[$store['store_id']] .= $info[$store['store_id']];
					}
				}
				
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_form_tab_general_store_language');
				
				foreach ($stores as $store) {					
					foreach ($languages as $language) {
						if (!isset($html_tab_general_store_language[$store['store_id']][$language['language_id']])) $html_tab_general_store_language[$store['store_id']][$language['language_id']] = '';
						
						if (isset($info[$store['store_id']][$language['language_id']])) {
							$html_tab_general_store_language[$store['store_id']][$language['language_id']] .= $info[$store['store_id']][$language['language_id']];
						}
					}
				}
				
				$html_tab_data .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_form_tab_data');
				$html_tab_links .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_form_tab_links');
				$html_style .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_form_style');
				$html_script .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_form_script');
			}
			
			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			
			if ($html_tab_general) {
				$html_dom->find('#tab-general', 0)->innertext .= $html_tab_general;
			}
			
			if (reset($html_tab_general_language)) {
				foreach ($languages as $language) {
					$html_dom->find('#tab-general #language' . $language['language_id'], 0)->innertext .= $html_tab_general_language[$language['language_id']];
				}
			}
			
			$html_tab_general_language = reset($html_tab_general_store_language);
			
			if ((count($stores)) && (reset($html_tab_general_store) || reset($html_tab_general_language))) {
				$html_stores = '<ul class="nav nav-tabs" id="store">';
								
				foreach ($stores as $store) {
					$html_stores .= '<li' . (($store == reset($stores)) ? ' class="active"' : '') . '><a href="#store_' . $store['store_id'] . '" data-toggle="tab">' . $store['name'] . '</a></li>';
				}
				
				$html_stores .= '</ul>';
				$html_stores .= '<div class="tab-store tab-content">';
				
				foreach ($stores as $store) {
					$html_store_languages = '';
						
					if (reset($html_tab_general_store_language[$store['store_id']])) {
						$html_store_languages = '<ul class="nav nav-tabs" id="store_' . $store['store_id'] . '_language">';
				
						foreach ($languages as $language) {
							$html_store_languages .= '<li' . (($language == reset($languages)) ? ' class="active"' : '') . '><a href="#store_' . $store['store_id'] . '_language_' . $language['language_id'] . '" data-toggle="tab"><img src="' . $language['flag'] . '" title="' . $language['name'] . '" /> ' . $language['name'] . '</a></li>';
						}
				
						$html_store_languages .= '</ul>';
						$html_store_languages .= '<div class="tab-language tab-content">';
				
						foreach ($languages as $language) {
							$html_store_languages .= '<div class="tab-pane' . (($language == reset($languages)) ? ' active' : '') . '" id="store_' . $store['store_id'] . '_language_' . $language['language_id'] . '">' . $html_tab_general_store_language[$store['store_id']][$language['language_id']] . '</div>';
						}
						
						$html_store_languages .= '</div>';
					}
									
					$html_stores .= '<div class="tab-pane' . (($store == reset($stores)) ? ' active' : '') . '" id="store_' . $store['store_id'] . '">' . $html_tab_general_store[$store['store_id']] . $html_store_languages . '</div>';
				}
				
				$html_stores .= '</div>';
				
				$html_dom->find('#tab-general', 0)->innertext .= $html_stores;
				$html_dom->load((string)$html_dom, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			}
			
			if ($html_tab_data) {
				$html_dom->find('#tab-data', 0)->innertext .= $html_tab_data;
			}
			
			if ($html_tab_links) {
				$html_dom->find('#tab-links', 0)->innertext .= $html_tab_links;
			}
						
			if ($html_style) {
				$html_dom->find('#content', 0)->innertext .= $html_style;
			}
			
			if ($html_script) {
				$html_dom->find('#content', 0)->innertext .= $html_script;
			}

			$output = (string)$html_dom;
		}
	}
	
	public function product_validate_form($error) {
		$this->load->model($this->route);
				
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_validate_form', $error);
			if ($info != '') $error = $info;
		}
		
		return $error;
	}

	public function product_add_product_after($route, $data, $output) {
		$this->load->model($this->route);

		$data = $data[0];
		$data['product_id'] = $output;

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_add_product', $data);
		}
	}

	public function product_edit_product_after($route, $data, $output) {
		$this->load->model($this->route);

		$product_id = $data[0];
		$data = $data[1];
		$data['product_id'] = $product_id;

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_edit_product', $data);
		}
	}
	
	public function product_delete_product_after($route, $data, $output) {
		$this->load->model($this->route);

		$data['product_id'] = $data[0];

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_delete_product', $data);
		}
	}

	public function manufacturer_form_after($route, $data, &$output) {
		$this->load->language($this->route);

		$this->load->model($this->route);
		
		if (file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			$html_tab_general = '';
			$html_tab_general_language = array();
			$html_tab_general_store = array();
			$html_tab_general_store_language = array();
			$html_tab_data = '';
			$html_style = '';
			$html_script = '';

			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
			$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
			unset($stores[0]);
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$html_tab_general .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_form_tab_general');
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_form_tab_general_language');
				
				foreach ($languages as $language) {
					if (!isset($html_tab_general_language[$language['language_id']])) $html_tab_general_language[$language['language_id']] = '';
					
					if (isset($info[$language['language_id']])) {
						$html_tab_general_language[$language['language_id']] .= $info[$language['language_id']];
					}
				}
				
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_form_tab_general_store');
				
				foreach ($stores as $store) {
					if (!isset($html_tab_general_store[$store['store_id']])) $html_tab_general_store[$store['store_id']] = '';
					
					if (isset($info[$store['store_id']])) {
						$html_tab_general_store[$store['store_id']] .= $info[$store['store_id']];
					}
				}
				
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_form_tab_general_store_language');
				
				foreach ($stores as $store) {					
					foreach ($languages as $language) {
						if (!isset($html_tab_general_store_language[$store['store_id']][$language['language_id']])) $html_tab_general_store_language[$store['store_id']][$language['language_id']] = '';
						
						if (isset($info[$store['store_id']][$language['language_id']])) {
							$html_tab_general_store_language[$store['store_id']][$language['language_id']] .= $info[$store['store_id']][$language['language_id']];
						}
					}
				}
				
				$html_tab_data .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_form_tab_data');
				$html_style .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_form_style');
				$html_script .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_form_script');
			}

			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			
			if (VERSION >= '3.0.0.0') {
				$html_manufacturer_name = $html_dom->find('#form-manufacturer #tab-general .form-group', 0)->outertext;
				$html_dom->find('#form-manufacturer #tab-general .form-group', 0)->outertext = '';
				$html_manufacturer_data = $html_dom->find('#form-manufacturer #tab-general', 0)->innertext;
				$html_dom->find('#form-manufacturer #tab-general', 0)->innertext = $html_manufacturer_name;
				$html_dom->find('#form-manufacturer .nav-tabs a[href=#tab-general]', 0)->outertext .= '<li><a href="#tab-data" data-toggle="tab">' . $this->language->get('text_data') . '</a></li>';
				$html_dom->find('#form-manufacturer .tab-content #tab-general', 0)->outertext .= '<div class="tab-pane" id="tab-data">' . $html_manufacturer_data . '</div>';
			} else {
				$html_manufacturer_name = $html_dom->find('#form-manufacturer .form-group', 0)->outertext;
				$html_dom->find('#form-manufacturer .form-group', 0)->outertext = '';
				$html_manufacturer_data = $html_dom->find('#form-manufacturer', 0)->innertext;
				$html_dom->find('#form-manufacturer', 0)->innertext = '<ul class="nav nav-tabs"><li class="active"><a href="#tab-general" data-toggle="tab">' . $this->language->get('text_general') . '</a></li><li><a href="#tab-data" data-toggle="tab">' . $this->language->get('text_data') . '</a></li></ul><div class="tab-main tab-content"><div class="tab-pane active" id="tab-general">' . $html_manufacturer_name . '</div><div class="tab-pane" id="tab-data">' . $html_manufacturer_data . '</div></div>';
			}
			
			$html_dom->load((string)$html_dom, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);

			if ($html_tab_general) {
				$html_dom->find('#tab-general', 0)->innertext .= $html_tab_general;
			}
			
			if (reset($html_tab_general_language)) {
				$html_languages = '<ul class="nav nav-tabs" id="language">';
				
				foreach ($languages as $language) {
					$html_languages .= '<li' . (($language == reset($languages)) ? ' class="active"' : '') . '><a href="#language' . $language['language_id'] . '" data-toggle="tab"><img src="' . $language['flag'] . '" title="' . $language['name'] . '" /> ' . $language['name'] . '</a></li>';
				}
				
				$html_languages .= '</ul>';
				$html_languages .= '<div class="tab-language tab-content">';
				
				foreach ($languages as $language) {
					$html_languages .= '<div class="tab-pane' . (($language == reset($languages)) ? ' active' : '') . '" id="language' . $language['language_id'] . '"></div>';
				}
				
				$html_languages .= '</div>';

				$html_dom->find('#tab-general', 0)->innertext .= $html_languages;
				$html_dom->load((string)$html_dom, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);

				foreach ($languages as $language) {
					$html_dom->find('#tab-general #language' . $language['language_id'], 0)->innertext .= $html_tab_general_language[$language['language_id']];
				}
			}
			
			$html_tab_general_language = reset($html_tab_general_store_language);
			
			if ((count($stores)) && (reset($html_tab_general_store) || reset($html_tab_general_language))) {
				$html_stores = '<ul class="nav nav-tabs" id="store">';
								
				foreach ($stores as $store) {
					$html_stores .= '<li' . (($store == reset($stores)) ? ' class="active"' : '') . '><a href="#store_' . $store['store_id'] . '" data-toggle="tab">' . $store['name'] . '</a></li>';
				}
				
				$html_stores .= '</ul>';
				$html_stores .= '<div class="tab-store tab-content">';
				
				foreach ($stores as $store) {
					$html_store_languages = '';
						
					if (reset($html_tab_general_store_language[$store['store_id']])) {
						$html_store_languages = '<ul class="nav nav-tabs" id="store_' . $store['store_id'] . '_language">';
				
						foreach ($languages as $language) {
							$html_store_languages .= '<li' . (($language == reset($languages)) ? ' class="active"' : '') . '><a href="#store_' . $store['store_id'] . '_language_' . $language['language_id'] . '" data-toggle="tab"><img src="' . $language['flag'] . '" title="' . $language['name'] . '" /> ' . $language['name'] . '</a></li>';
						}
				
						$html_store_languages .= '</ul>';
						$html_store_languages .= '<div class="tab-language tab-content">';
				
						foreach ($languages as $language) {
							$html_store_languages .= '<div class="tab-pane' . (($language == reset($languages)) ? ' active' : '') . '" id="store_' . $store['store_id'] . '_language_' . $language['language_id'] . '">' . $html_tab_general_store_language[$store['store_id']][$language['language_id']] . '</div>';
						}
						
						$html_store_languages .= '</div>';
					}
									
					$html_stores .= '<div class="tab-pane' . (($store == reset($stores)) ? ' active' : '') . '" id="store_' . $store['store_id'] . '">' . $html_tab_general_store[$store['store_id']] . $html_store_languages . '</div>';
				}
				
				$html_stores .= '</div>';
				
				$html_dom->find('#tab-general', 0)->innertext .= $html_stores;
				$html_dom->load((string)$html_dom, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			}
			
			if ($html_tab_data) {
				$html_dom->find('#tab-data', 0)->innertext .= $html_tab_data;
			}
						
			if ($html_style) {
				$html_dom->find('#content', 0)->innertext .= $html_style;
			}
			
			if ($html_script) {
				$html_dom->find('#content', 0)->innertext .= $html_script;
			}

			$output = (string)$html_dom;
		}
	}
	
	public function manufacturer_validate_form($error) {
		$this->load->model($this->route);
				
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_validate_form', $error);
			if ($info != '') $error = $info;
		}
		
		return $error;
	}

	public function manufacturer_add_manufacturer_after($route, $data, $output) {
		$this->load->model($this->route);

		$data = $data[0];
		$data['manufacturer_id'] = $output;

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_add_manufacturer', $data);
		}
	}

	public function manufacturer_edit_manufacturer_after($route, $data, $output) {
		$this->load->model($this->route);

		$manufacturer_id = $data[0];
		$data = $data[1];
		$data['manufacturer_id'] = $manufacturer_id;

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_edit_manufacturer', $data);
		}
	}
	
	public function manufacturer_delete_manufacturer_after($route, $data, $output) {
		$this->load->model($this->route);

		$data['manufacturer_id'] = $data[0];

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_delete_manufacturer', $data);
		}
	}

	public function information_form_after($route, $data, &$output) {
		$this->load->language($this->route);

		$this->load->model($this->route);
		
		if (file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			$html_tab_general = '';
			$html_tab_general_language = array();
			$html_tab_general_store = array();
			$html_tab_general_store_language = array();
			$html_tab_data = '';
			$html_style = '';
			$html_script = '';

			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			$languages = $this->{'model_extension_module_' . $this->codename}->getLanguages();
			$stores = $this->{'model_extension_module_' . $this->codename}->getStores();
			unset($stores[0]);
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$html_tab_general .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_form_tab_general');
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_form_tab_general_language');
				
				foreach ($languages as $language) {
					if (!isset($html_tab_general_language[$language['language_id']])) $html_tab_general_language[$language['language_id']] = '';
					
					if (isset($info[$language['language_id']])) {
						$html_tab_general_language[$language['language_id']] .= $info[$language['language_id']];
					}
				}
				
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_form_tab_general_store');
				
				foreach ($stores as $store) {
					if (!isset($html_tab_general_store[$store['store_id']])) $html_tab_general_store[$store['store_id']] = '';
					
					if (isset($info[$store['store_id']])) {
						$html_tab_general_store[$store['store_id']] .= $info[$store['store_id']];
					}
				}
				
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_form_tab_general_store_language');
				
				foreach ($stores as $store) {					
					foreach ($languages as $language) {
						if (!isset($html_tab_general_store_language[$store['store_id']][$language['language_id']])) $html_tab_general_store_language[$store['store_id']][$language['language_id']] = '';
						
						if (isset($info[$store['store_id']][$language['language_id']])) {
							$html_tab_general_store_language[$store['store_id']][$language['language_id']] .= $info[$store['store_id']][$language['language_id']];
						}
					}
				}
				
				$html_tab_data .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_form_tab_data');
				$html_style .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_form_style');
				$html_script .= $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_form_script');
			}
			
			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$output, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);

			if ($html_tab_general) {
				$html_dom->find('#tab-general', 0)->innertext .= $html_tab_general;
			}
			
			if (reset($html_tab_general_language)) {
				foreach ($languages as $language) {
					$html_dom->find('#tab-general #language' . $language['language_id'], 0)->innertext .= $html_tab_general_language[$language['language_id']];
				}
			}
			
			$html_tab_general_language = reset($html_tab_general_store_language);
			
			if ((count($stores)) && (reset($html_tab_general_store) || reset($html_tab_general_language))) {
				$html_stores = '<ul class="nav nav-tabs" id="store">';
								
				foreach ($stores as $store) {
					$html_stores .= '<li' . (($store == reset($stores)) ? ' class="active"' : '') . '><a href="#store_' . $store['store_id'] . '" data-toggle="tab">' . $store['name'] . '</a></li>';
				}
				
				$html_stores .= '</ul>';
				$html_stores .= '<div class="tab-store tab-content">';
				
				foreach ($stores as $store) {
					$html_store_languages = '';
						
					if (reset($html_tab_general_store_language[$store['store_id']])) {
						$html_store_languages = '<ul class="nav nav-tabs" id="store_' . $store['store_id'] . '_language">';
				
						foreach ($languages as $language) {
							$html_store_languages .= '<li' . (($language == reset($languages)) ? ' class="active"' : '') . '><a href="#store_' . $store['store_id'] . '_language_' . $language['language_id'] . '" data-toggle="tab"><img src="' . $language['flag'] . '" title="' . $language['name'] . '" /> ' . $language['name'] . '</a></li>';
						}
				
						$html_store_languages .= '</ul>';
						$html_store_languages .= '<div class="tab-language tab-content">';
				
						foreach ($languages as $language) {
							$html_store_languages .= '<div class="tab-pane' . (($language == reset($languages)) ? ' active' : '') . '" id="store_' . $store['store_id'] . '_language_' . $language['language_id'] . '">' . $html_tab_general_store_language[$store['store_id']][$language['language_id']] . '</div>';
						}
						
						$html_store_languages .= '</div>';
					}
									
					$html_stores .= '<div class="tab-pane' . (($store == reset($stores)) ? ' active' : '') . '" id="store_' . $store['store_id'] . '">' . $html_tab_general_store[$store['store_id']] . $html_store_languages . '</div>';
				}
				
				$html_stores .= '</div>';
				
				$html_dom->find('#tab-general', 0)->innertext .= $html_stores;
				$html_dom->load((string)$html_dom, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
			}
			
			if ($html_tab_data) {
				$html_dom->find('#tab-data', 0)->innertext .= $html_tab_data;
			}
						
			if ($html_style) {
				$html_dom->find('#content', 0)->innertext .= $html_style;
			}
			
			if ($html_script) {
				$html_dom->find('#content', 0)->innertext .= $html_script;
			}

			$output = (string)$html_dom;
		}
	}
	
	public function information_validate_form($error) {
		$this->load->model($this->route);
				
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_validate_form', $error);
			if ($info != '') $error = $info;
		}
		
		return $error;
	}

	public function information_add_information_after($route, $data, $output) {
		$this->load->model($this->route);

		$data = $data[0];
		$data['information_id'] = $output;

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_add_information', $data);
		}
	}

	public function information_edit_information_after($route, $data, $output) {
		$this->load->model($this->route);

		$information_id = $data[0];
		$data = $data[1];
		$data['information_id'] = $information_id;

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_edit_information', $data);
		}
	}
	
	public function information_delete_information_after($route, $data, $output) {
		$this->load->model($this->route);

		$data['information_id'] = $data[0];

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();

		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_delete_information', $data);
		}
	}
		
	/*
	*	Return Field Info.
	*/	
	public function getFieldInfo() {	
		$_language = new Language();
		$_language->load($this->route);
				
		$this->load->model($this->route);

		if ($this->config->get($this->codename . '_field_info')) {
			return $this->config->get($this->codename . '_field_info');
		}
				
		// Setting		
		$config_field_setting = array();
		
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/field_config');
			if ($info) $config_field_setting = array_replace_recursive($config_field_setting, $info);
		}
		
		$setting = $this->model_setting_setting->getSetting('module_' . $this->codename);
		$field_setting = isset($setting['module_' . $this->codename . '_field_setting']) ? $setting['module_' . $this->codename . '_field_setting'] : array();
					
		if (!empty($field_setting)) {
			$config_field_setting = array_replace_recursive($config_field_setting, $field_setting);
		}
		
		$field_setting = $config_field_setting;
		
		$sheets = array();
		
		foreach ($field_setting['sheet'] as $sheet) {
			if (isset($sheet['code']) && isset($sheet['name'])) {				
				$fields = array();
				
				if (isset($sheet['field'])) {
					foreach ($sheet['field'] as $field) {
						if (isset($field['code']) && isset($field['name']) && isset($field['description']) && isset($field['type']) && isset($field['multi_language']) && isset($field['multi_store']) && isset($field['required'])) {						
							$fields[$field['code']] = $field;
						}
					}
					
					$fields = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($fields, 'sort_order');
				}
				
				$sheet['field'] = array();
				
				foreach ($fields as $field) {
					$sheet['field'][$field['code']] = $field;
				}
				
				$sheets[$sheet['code']] = $sheet;
			}
		}
				
		$field_setting['sheet'] = $this->{'model_extension_module_' . $this->codename}->sortArrayByColumn($sheets, 'sort_order');
				
		$this->config->set($this->codename . '_field_info', $field_setting);
			
		return $field_setting;
	}
	
	/*
	*	Return Field Elements.
	*/	
	public function getFieldElements($data) {
		$this->load->model($this->route);
		
		$field_elements = array();
		
		if (isset($data['field_code']) && isset($data['filter'])) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
				
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/field_elements', $data);
				if ($info != '') $field_elements = array_replace_recursive($field_elements, $info);	
			}
		}
						
		return $field_elements;
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
							
		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
									
		foreach ($installed_seo_extensions as $installed_seo_extension) {
			$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/custom_page_exception_routes');
			if ($info) $custom_page_exception_routes = array_merge($custom_page_exception_routes, $info);
		}
		
		$this->config->set($this->codename . '_custom_page_exception_routes', $custom_page_exception_routes);
		
		return $custom_page_exception_routes;
	}
	
	/*
	*	Return Target Keywords.
	*/	
	public function getTargetKeywords() {
		$filter = array();
		
		if (isset($this->request->post['route'])) {
			$filter['route'] = $this->request->post['route'];
		}
		
		if (isset($this->request->post['store_id'])) {
			$filter['store_id'] = $this->request->post['store_id'];
		}
		
		if (isset($this->request->post['language_id'])) {
			$filter['language_id'] = $this->request->post['language_id'];
		}
		
		if (isset($this->request->post['sort_order'])) {
			$filter['sort_order'] = $this->request->post['sort_order'];
		}
		
		if (isset($this->request->post['keyword'])) {
			$filter['keyword'] = $this->request->post['keyword'];
		}
		
		$field_data = array(
			'field_code' => 'target_keyword',
			'filter' => $filter
		);
			
		$data['target_keywords'] = $this->getFieldElements($field_data);
						
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
	
	private function validateAddTargetElement($permission = 'modify') {						
		if (!$this->user->hasPermission($permission, $this->route)) {
			$this->error['warning'] = $this->language->get('error_permission');
			
			return false;
		}
		
		if (!preg_match('/[A-Za-z0-9]+\/[A-Za-z0-9]+/i', $this->request->post['target_element']['route']) && !preg_match('/[A-Za-z0-9]+\=[0-9]+/i', $this->request->post['target_element']['route'])) {
			$this->error['warning'] = $this->language->get('error_route');
			
			return false;
		}
		
		$field_data = array(
			'field_code' => 'target_keyword',
			'filter' => array(
				'route' => $this->request->post['target_element']['route'],
				'store_id' => $this->request->get['store_id']
			)
		);
			
		$target_keywords = $this->getFieldElements($field_data);
								
		if ($target_keywords) {
			$this->error['warning'] = sprintf($this->language->get('error_route_exists'), $this->request->post['target_element']['route']);
			
			return false;
		}
		
		foreach ($this->request->post['target_element']['target_keyword'] as $language_id => $target_keyword) {
			preg_match_all('/\[[^]]+\]/', $target_keyword, $keywords);
				
			if (!$keywords[0]) {
				$this->error['warning'] = sprintf($this->language->get('error_target_keyword'), $target_keyword);
				
				return false;
			}	
		}	
				
		return true;
	}
	
	private function validateEditTargetElement($permission = 'modify') {				
		if (!$this->user->hasPermission($permission, $this->route)) {
			$this->error['warning'] = $this->language->get('error_permission');
			
			return false;
		}
		
		preg_match_all('/\[[^]]+\]/', $this->request->post['target_keyword'], $keywords);
				
		if (!$keywords[0]) {
			$this->error['warning'] = sprintf($this->language->get('error_target_keyword'), $this->request->post['target_keyword']);
			
			return false;
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
		
		$installed_seo_extensions[] = $this->codename;
		
		$this->{'model_extension_module_' . $this->codename}->saveSEOExtensions($installed_seo_extensions);

		return true;
	}

	private function validateUninstall($permission = 'modify') {
		$this->load->model($this->route);

		$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
		if (count($installed_seo_extensions) > 1) {
			$this->error['warning'] = $this->language->get('error_dependencies');
			
			return false;
		}
		
		$key = array_search($this->codename, $installed_seo_extensions);
		
		if ($key !== false) unset($installed_seo_extensions[$key]);
				
		$this->{'model_extension_module_' . $this->codename}->saveSEOExtensions($installed_seo_extensions);

		return true;
	}
}
