<?php
class ControllerExtensionModuleDTranslit extends Controller {
	private $codename = 'd_translit';
	private $route = 'extension/module/d_translit';
	private $config_file = 'd_translit';
	private $extension = array();
	private $error = array(); 
			
	public function __construct($registry) {
		parent::__construct($registry);
		
		$this->d_shopunity = (file_exists(DIR_SYSTEM . 'library/d_shopunity/extension/d_shopunity.json'));
		$this->extension = json_decode(file_get_contents(DIR_SYSTEM . 'library/d_shopunity/extension/' . $this->codename . '.json'), true);
	}
	
	public function index() {
		$this->load->language($this->route);
		
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
		$this->document->addStyle('view/stylesheet/d_admin_style/core/normalize/normalize.css');
		$this->document->addStyle('view/stylesheet/d_admin_style/themes/light/light.css');
				
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
										
		if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$data['server'] = HTTPS_SERVER;
			$data['catalog'] = HTTPS_CATALOG;
		} else {
			$data['server'] = HTTP_SERVER;
			$data['catalog'] = HTTP_CATALOG;
		}
		
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .=  'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .=  'user_token=' . $this->session->data['user_token'];
		}
				
		// Action
		$data['module_link'] = $this->url->link($this->route, $url_token, true);
		$data['action'] = $this->url->link($this->route . '/save', $url_token, true);
			
		if (VERSION >= '3.0.0.0') {
			$data['cancel'] = $this->url->link('marketplace/extension', $url_token . '&type=module', true);
		} elseif (VERSION >= '2.3.0.0') {
			$data['cancel'] = $this->url->link('extension/extension', $url_token . '&type=module', true);
		} else {
			$data['cancel'] = $this->url->link('extension/module', $url_token, true);
		}
						
		// Tab
		$data['text_settings'] = $this->language->get('text_settings');
		$data['text_translit_symbol'] = $this->language->get('text_translit_symbol');
		$data['text_translit_language_symbol'] = $this->language->get('text_translit_language_symbol');
		$data['text_trim_symbol'] = $this->language->get('text_trim_symbol');
		$data['text_instructions'] = $this->language->get('text_instructions');
				
		// Button
		$data['button_save'] = $this->language->get('button_save');
		$data['button_save_and_stay'] = $this->language->get('button_save_and_stay');
		$data['button_cancel'] = $this->language->get('button_cancel');	
		$data['button_add_translit_symbol'] = $this->language->get('button_add_translit_symbol');
		$data['button_delete_translit_symbol'] = $this->language->get('button_delete_translit_symbol');
		$data['button_add_translit_language_symbol'] = $this->language->get('button_add_translit_language_symbol');
		$data['button_delete_translit_language_symbol'] = $this->language->get('button_delete_translit_language_symbol');
		$data['button_add_trim_symbol'] = $this->language->get('button_add_trim_symbol');
		$data['button_delete_trim_symbol'] = $this->language->get('button_delete_trim_symbol');
				
		// Entry
		$data['entry_translit_symbol'] = $this->language->get('entry_translit_symbol');
		$data['entry_translit_language_symbol'] = $this->language->get('entry_translit_language_symbol');
		$data['entry_transform_language_symbol'] = $this->language->get('entry_transform_language_symbol');
		$data['entry_trim_symbol'] = $this->language->get('entry_trim_symbol');
		$data['entry_input_symbol'] = $this->language->get('entry_input_symbol');
		$data['entry_ouput_symbol'] = $this->language->get('entry_ouput_symbol');
		$data['entry_symbol'] = $this->language->get('entry_symbol');
						
		// Text
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_module'] = $this->language->get('text_module');
		$data['text_powered_by'] = $this->language->get('text_powered_by');
				
		$data['text_transform_none'] = $this->language->get('text_transform_none');
		$data['text_transform_lower_to_upper'] = $this->language->get('text_transform_lower_to_upper');
		$data['text_transform_upper_to_lower'] = $this->language->get('text_transform_upper_to_lower');
		$data['text_delete_translit_symbol'] = $this->language->get('text_delete_translit_symbol');
		$data['text_delete_translit_language_symbol'] = $this->language->get('text_delete_translit_language_symbol');
		$data['text_delete_trim_symbol'] = $this->language->get('text_delete_trim_symbol');
		
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
			'text' => $this->language->get('heading_title_main'),
			'href' => $this->url->link($this->route, $url_token, true)
		);
						
		// Setting 		
		$this->config->load($this->config_file);
		$data['setting'] = ($this->config->get($this->codename)) ? $this->config->get($this->codename) : array();
		
		$setting = $this->model_setting_setting->getSetting('module_' . $this->codename);
		$setting = isset($setting['module_' . $this->codename . '_setting']) ? $setting['module_' . $this->codename . '_setting'] : array();
						
		if (!empty($setting)) {
			$data['setting'] = array_replace_recursive($data['setting'], $setting);
		} else {
			$data['setting']['translit_symbol'] = $data['setting']['translit_symbol_default'];
			$data['setting']['translit_language_symbol'] = $data['setting']['translit_language_symbol_default'];
			$data['setting']['trim_symbol'] = $data['setting']['trim_symbol_default'];
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
			if (isset($this->request->post['module_' . $this->codename . '_setting']['translit_symbol'])) {
				$result = array();
				
				foreach ($this->request->post['module_' . $this->codename . '_setting']['translit_symbol'] as $translit_symbol) {
					$result[$translit_symbol['input']] = $translit_symbol['output'];
				}
				
				$this->request->post['module_' . $this->codename . '_setting']['translit_symbol'] = $result;
			}
			
			if (isset($this->request->post['module_' . $this->codename . '_setting']['translit_language_symbol'])) {
				$result = array();
				
				foreach ($this->request->post['module_' . $this->codename . '_setting']['translit_language_symbol'] as $translit_language_symbol) {
					$result[$translit_language_symbol['input']] = $translit_language_symbol['output'];
				}
				
				$this->request->post['module_' . $this->codename . '_setting']['translit_language_symbol'] = $result;
			}
			
			if (isset($this->request->post['module_' . $this->codename . '_setting']['trim_symbol'])) {
				$result = array();
				
				foreach ($this->request->post['module_' . $this->codename . '_setting']['trim_symbol'] as $trim_symbol) {
					$result[] = $trim_symbol;
				}
				
				$this->request->post['module_' . $this->codename . '_setting']['trim_symbol'] = $result;
			}
					
			$this->model_setting_setting->editSetting('module_' . $this->codename, $this->request->post);

			$data['success'] = $this->language->get('success_save');
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
							
	private function validate($permission = 'modify') {				
		if (!$this->user->hasPermission($permission, $this->route)) {
			$this->error['warning'] = $this->language->get('error_permission');
			return false;
		}
		
		return true;
	}	
}