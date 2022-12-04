<?php
class ControllerExtensionDashboardDSEOModuleURLRedirect extends Controller {
	private $codename = 'd_seo_module_url_redirect';
	private $main_codename = 'd_seo_module_url';
	private $route = 'extension/dashboard/d_seo_module_url_redirect';
	private $config_file = 'd_seo_module_url_redirect';
	private $extension = array();
	private $error = array(); 
	
	public function __construct($registry) {
		parent::__construct($registry);
		
		$this->d_shopunity = (file_exists(DIR_SYSTEM . 'library/d_shopunity/extension/d_shopunity.json'));
		$this->extension = json_decode(file_get_contents(DIR_SYSTEM . 'library/d_shopunity/extension/' . $this->main_codename . '.json'), true);
	}
	
	public function index() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');

		if ($this->d_shopunity) {
			$this->load->model('extension/d_shopunity/mbooth');
				
			$this->model_extension_d_shopunity_mbooth->validateDependencies($this->main_codename);
		}
		
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .=  'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .=  'user_token=' . $this->session->data['user_token'];
		}
		
		// Styles and Scripts
		$this->document->addStyle('view/stylesheet/d_bootstrap_extra/bootstrap.css');
		$this->document->addScript('view/javascript/d_bootstrap_switch/js/bootstrap-switch.min.js');
        $this->document->addStyle('view/javascript/d_bootstrap_switch/css/bootstrap-switch.css');
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
		$data['stores'] = $this->{'model_extension_dashboard_' . $this->codename}->getStores();
										
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$data['server'] = HTTPS_SERVER;
			$data['catalog'] = HTTPS_CATALOG;
		} else {
			$data['server'] = HTTP_SERVER;
			$data['catalog'] = HTTP_CATALOG;
		}
		
		// Action
		$data['module_link'] = $this->url->link($this->route, $url_token, true);
		$data['action'] = $this->url->link($this->route . '/save', $url_token, true);
		
		if (VERSION >= '3.0.0.0') {
			$data['cancel'] = $this->url->link('marketplace/extension', $url_token . '&type=dashboard', true);
		} else {
			$data['cancel'] = $this->url->link('extension/extension', $url_token . '&type=dashboard', true);
		}
		
		// Button
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');	
		
		// Entry
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_width'] = $this->language->get('entry_width');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_list_limit'] = $this->language->get('entry_list_limit');
		$data['entry_store'] = $this->language->get('entry_store');
				
		// Text		
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		// Notification
		foreach($this->error as $key => $error){
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
				'text' => $this->language->get('text_dashboard'),
				'href' => $this->url->link('marketplace/extension', $url_token . '&type=dashboard', true)
			);
		} elseif (VERSION >= '2.3.0.0') {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_dashboard'),
				'href' => $this->url->link('extension/extension', $url_token . '&type=dashboard', true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_dashboard'),
				'href' => $this->url->link('extension/dashboard', $url_token, true)
			);
		}

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title_main'),
			'href' => $this->url->link($this->route, $url_token, true)
		);
		
		// Setting 	
		$this->config->load($this->config_file);
		$data['setting'] = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
		
		$setting = $this->model_setting_setting->getSetting('dashboard_' . $this->codename);	
		$status = isset($setting['dashboard_' . $this->codename . '_status']) ? $setting['dashboard_' . $this->codename . '_status'] : false;
		$width = isset($setting['dashboard_' . $this->codename . '_width']) ? $setting['dashboard_' . $this->codename . '_width'] : 12;
		$sort_order = isset($setting['dashboard_' . $this->codename . '_sort_order']) ? $setting['dashboard_' . $this->codename . '_sort_order'] : 31;
		$setting = isset($setting['dashboard_' . $this->codename . '_setting']) ? $setting['dashboard_' . $this->codename . '_setting'] : array();
		
		$data['status'] = $status;
		$data['width'] = $width;
		$data['sort_order'] = $sort_order;
								
		if (!empty($setting)) {
			$data['setting'] = array_replace_recursive($data['setting'], $setting);
		}
		
		$data['columns'] = array();
		
		for ($i = 3; $i <= 12; $i++) {
			$data['columns'][] = $i;
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}
	
	public function save() {
		$this->load->language($this->route);
		
		$this->load->model('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('dashboard_' . $this->codename, $this->request->post);
						
			$data['success'] = $this->language->get('success_save');
		}
						
		$data['error'] = $this->error;
				
		$this->response->setOutput(json_encode($data));
	}
	
	public function dashboard() {
		$this->load->language($this->route);
				
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
		
		// Heading
		$data['heading_title'] = $this->language->get('heading_title_main');
		
		// Variable
		$data['codename'] = $this->codename;
		$data['route'] = $this->route;
		$data['url_token'] =  $url_token;
			
		return $this->load->view($this->route . '_info', $data);
	}
	
	public function refresh() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
				
		if (file_exists(DIR_APPLICATION . 'model/extension/module/d_twig_manager.php')) {
			$this->load->model('extension/module/d_twig_manager');
			
			$this->model_extension_module_d_twig_manager->installCompatibility();
		}
		
		// Heading
		$data['heading_title'] = $this->language->get('heading_title_main');
		
		// Variable
		$data['codename'] = $this->codename;
		$data['route'] = $this->route;
		$data['languages'] = $this->{'model_extension_dashboard_' . $this->codename}->getLanguages();
		$data['stores'] = array();
		
		// Column
		$data['column_url_from'] = $this->language->get('column_url_from');
		$data['column_url_to'] = $this->language->get('column_url_to');
		
		// Text
		$data['text_heading_info'] = $this->language->get('text_heading_info');
		$data['text_no_results'] = $this->language->get('text_no_results');
		
		// Setting
		$this->config->load($this->config_file);
		$config_setting = ($this->config->get($this->codename . '_setting')) ? $this->config->get($this->codename . '_setting') : array();
		
		$setting = $this->model_setting_setting->getSetting('dashboard_' . $this->codename);	
		$setting = isset($setting['dashboard_' . $this->codename . '_setting']) ? $setting['dashboard_' . $this->codename . '_setting'] : array();
		
		if (!empty($setting)) {
			$config_setting = array_replace_recursive($config_setting, $setting);
		}
		
		$setting = $config_setting;
		
		$store_redirects = array();
				
		$store_empty_redirects = $this->{'model_extension_dashboard_' . $this->codename}->getStoreEmptyRedirects();				
		if ($store_empty_redirects) $store_redirects = array_replace_recursive($store_redirects, $store_empty_redirects);
		
		foreach ($store_redirects as $store_id => $redirects) {
			$store_redirects[$store_id] = array_slice($redirects, 0, $setting['list_limit']);
		}
		
		$stores = $this->{'model_extension_dashboard_' . $this->codename}->getStores();
					
		foreach ($stores as $store) {			
			if ((in_array($store['store_id'], $setting['stores_id'])) || (VERSION < '2.3.0.0')) {
				$data['stores'][$store['store_id']] = $store;
				$data['stores'][$store['store_id']]['empty_redirects_count'] = 0;
				$data['stores'][$store['store_id']]['redirects'] = array();
					
				if (isset($store_empty_redirects[$store['store_id']])) {
					$data['stores'][$store['store_id']]['empty_redirects_count'] = count($store_empty_redirects[$store['store_id']]);
				}
									
				if (isset($store_redirects[$store['store_id']])) {
					$data['stores'][$store['store_id']]['redirects'] = $store_redirects[$store['store_id']];
				}
			}
		}
		
		$this->response->setOutput($this->load->view($this->route . '_refresh', $data));
	}
		
	public function editRedirect() {
		$this->load->language($this->route);
		
		$this->load->model($this->route);
		
		if (isset($this->request->post['url_redirect_id']) && isset($this->request->post['store_id']) && isset($this->request->post['field_code']) && isset($this->request->post['value']) && $this->validateEditRedirect()) {
			$redirect_data = array(
				'url_redirect_id'	=> $this->request->post['url_redirect_id'],
				'store_id'			=> $this->request->post['store_id'],
				'field_code'		=> $this->request->post['field_code'],
				'value'				=> $this->request->post['value']
			);
		
			$this->{'model_extension_dashboard_' . $this->codename}->editRedirect($redirect_data);
		}
			
		$data['error'] = $this->error;
		
		if (!$data['error']) {
			$data['value'] = $this->request->post['value'];
		} 
		
		$this->response->setOutput(json_encode($data));
	}
	
	/*
	*	Validator Functions.
	*/		
	private function validate($permission = 'modify') {
		if (isset($this->request->post['config'])) {
			return false;
		}
				
		if (!$this->user->hasPermission($permission, $this->route)) {
			$this->error['warning'] = $this->language->get('error_permission');
			
			return false;
		}
		
		return true;
	}
		
	private function validateEditRedirect($permission = 'modify') {
		$this->load->model($this->route);
		
		if (isset($this->request->post['config'])) {
			return false;
		}
				
		if (!$this->user->hasPermission($permission, $this->route)) {
			$this->error['warning'] = $this->language->get('error_permission');
			
			return false;
		}
		
		if ($this->request->post['field_code'] == 'url_from') {
			if (!trim($this->request->post['value'])) {
				$this->error['warning'] = $this->language->get('error_url_from');
				
				return false;
			}
			
			$url_from_info = $this->{'model_extension_dashboard_' . $this->codename}->getURLInfo($this->request->post['value']);
			$url_from = $url_from_info['host'] . $url_from_info['port'] . $url_from_info['path'];
			
			if (isset($url_from_info['data']['route'])) {
				$url_from .= '?route=' . $url_from_info['data']['route'];
			} 
			
			$this->request->post['value'] = $url_from;
			
			$url_redirects = $this->{'model_extension_dashboard_' . $this->codename}->getRedirects(array('filter' => array('url_from' => $url_from)));
			
			foreach ($url_redirects as $url_redirect) {
				if ($url_redirect['url_redirect_id'] != $this->request->post['url_redirect_id']) {
					$this->error['warning'] = sprintf($this->language->get('error_url_from_exists'), $url_from);
					
					return false;
				}
			}
		}
			
		return true;
	}
}
