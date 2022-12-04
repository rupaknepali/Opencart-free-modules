<?php
class ControllerExtensionDSEOModuleManagerDSEOModuleURL extends Controller {
	private $codename = 'd_seo_module_url';
	private $route = 'extension/d_seo_module_manager/d_seo_module_url';
	private $config_file = 'd_seo_module_url';
	private $error = array(); 
		
	/*
	*	Functions for SEO Module Manager.
	*/	
	public function manager_config() {
		$_language = new Language();
		$_language->load($this->route);
		
		$_config = new Config();
		$_config->load($this->config_file);
		$manager_setting = ($_config->get($this->codename . '_manager_setting')) ? $_config->get($this->codename . '_manager_setting') : array();
		
		foreach ($manager_setting['sheet'] as $sheet) {
			foreach ($sheet['field'] as $field) {
				if (substr($field['name'], 0, strlen('text_')) == 'text_') {
					$manager_setting['sheet'][$sheet['code']]['field'][$field['code']]['name'] = $_language->get($field['name']);
				}
			}
		}
							
		return $manager_setting;
	}
	
	public function manager_list_elements($filter_data) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_manager_' . $this->codename}->getListElements($filter_data);
	}
	
	public function manager_edit_element_field($element_data) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_manager_' . $this->codename}->editElementField($element_data);
	}
	
	public function manager_export_elements($export_data) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_manager_' . $this->codename}->getExportElements($export_data);
	}
	
	public function manager_import_elements($import_data) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_manager_' . $this->codename}->saveImportElements($import_data);
	}
}