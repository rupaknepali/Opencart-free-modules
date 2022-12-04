<?php
class ControllerExtensionDSEOModuleURLDSEOModuleURL extends Controller {
	private $codename = 'd_seo_module_url';
	private $route = 'extension/d_seo_module_url/d_seo_module_url';
	private $config_file = 'd_seo_module_url';
	private $error = array(); 
		
	/*
	*	Functions for SEO Module URL.
	*/
	public function save($data) {
		$this->load->model('extension/module/' . $this->codename);
		
		if ($data['new_setting']['module_d_seo_module_url_setting']['sheet']['category']['short_url'] != $data['old_setting']['module_d_seo_module_url_setting']['sheet']['category']['short_url']) {
			$cache_data = array(
				'route' => 'category_id=%',
				'store_id' => $data['store_id']
			);
			
			$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
		}
		
		if ($data['new_setting']['module_d_seo_module_url_setting']['sheet']['product']['short_url'] != $data['old_setting']['module_d_seo_module_url_setting']['sheet']['product']['short_url']) {
			$cache_data = array(
				'route' => 'product_id=%',
				'store_id' => $data['store_id']
			);
					
			$this->{'model_extension_module_' . $this->codename}->refreshURLCache($cache_data);
		}
	}
	
	public function url_generator_config() {
		$_language = new Language();
		$_language->load($this->route);
		
		$_config = new Config();
		$_config->load($this->config_file);
		$generator_setting = ($_config->get($this->codename . '_generator_setting')) ? $_config->get($this->codename . '_generator_setting') : array();
				
		foreach ($generator_setting['sheet'] as $sheet) {
			if (substr($sheet['name'], 0, strlen('text_')) == 'text_') {
				$generator_setting['sheet'][$sheet['code']]['name'] = $_language->get($sheet['name']);
			}
							
			foreach ($sheet['field'] as $field) {
				if (substr($field['name'], 0, strlen('text_')) == 'text_') {
					$generator_setting['sheet'][$sheet['code']]['field'][$field['code']]['name'] = $_language->get($field['name']);
				}
				
				if (substr($field['description'], 0, strlen('help_')) == 'help_') {
					$generator_setting['sheet'][$sheet['code']]['field'][$field['code']]['description'] = $_language->get($field['description']);
				}
			}
				
			foreach ($sheet['button_popup'] as $button_popup) {
				if (substr($button_popup['name'], 0, strlen('text_')) == 'text_') {
					$generator_setting['sheet'][$sheet['code']]['button_popup'][$button_popup['code']]['name'] = $_language->get($button_popup['name']);
				}
						
				foreach ($button_popup['field'] as $field) {
					if (substr($field['name'], 0, strlen('text_')) == 'text_') {
						$generator_setting['sheet'][$sheet['code']]['button_popup'][$button_popup['code']]['field'][$field['code']]['name'] = $_language->get($field['name']);
					}
				}
			}
		}
							
		return $generator_setting;
	}
	
	public function url_generator_generate_fields($generator_data) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_url_' . $this->codename}->generateFields($generator_data);
	}
	
	public function url_generator_clear_fields($generator_data) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_url_' . $this->codename}->clearFields($generator_data);
	}
	
	public function url_config() {
		$_language = new Language();
		$_language->load($this->route);
		
		$_config = new Config();
		$_config->load($this->config_file);
		$url_setting = ($_config->get($this->codename . '_url_setting')) ? $_config->get($this->codename . '_url_setting') : array();
		
		foreach ($url_setting['sheet'] as $sheet) {
			if (substr($sheet['name'], 0, strlen('text_')) == 'text_') {
				$url_setting['sheet'][$sheet['code']]['name'] = $_language->get($sheet['name']);
			}
		}
					
		return $url_setting;
	}
		
	public function url_elements($filter_data) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_url_' . $this->codename}->getURLElements($filter_data);
	}
		
	public function add_url_element($url_element_data) {
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_url_' . $this->codename}->addURLElement($url_element_data);
	}
	
	public function edit_url_element($url_element_data) {
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_url_' . $this->codename}->editURLElement($url_element_data);
	}
	
	public function delete_url_element($url_element_data) {
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_url_' . $this->codename}->deleteURLElement($url_element_data);
	}
		
	public function export_url_elements($export_data) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_url_' . $this->codename}->getExportURLElements($export_data);
	}
	
	public function import_url_elements($import_data) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_url_' . $this->codename}->saveImportURLElements($import_data);
	}
}