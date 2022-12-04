<?php
class ControllerExtensionDSEOModuleDSEOModule extends Controller {
	private $codename = 'd_seo_module';
	private $route = 'extension/d_seo_module/d_seo_module';
	private $config_file = 'd_seo_module';
	
	/*
	*	Functions for SEO Module.
	*/	
	public function product_after($html) {
		$store_id = (int)$this->config->get('config_store_id');
								
		if (file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
			if (isset($this->request->get['product_id'])) {
				$product_id = (int)$this->request->get['product_id'];
			} else {
				$product_id = 0;
			}
					
			$this->load->controller('product/product/review');
		
			$review = $this->response->getOutput();
				
			$html_dom = new d_simple_html_dom();
			$html_dom->load((string)$html, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
		
			foreach ($html_dom->find('#review') as $element) {
				$element->innertext = $review;
			}
				
			return (string)$html_dom;
		}
		
		return $html;
	}
	
	public function search_before($data) {
		$store_id = (int)$this->config->get('config_store_id');
				
		if (isset($this->request->get['tag']) && !$data['search']) {
			$this->load->language('product/search');
			
			$data['heading_title'] = $this->language->get('heading_title') .  ' - ' . $this->language->get('heading_tag') . $this->request->get['tag'];
			$data['search'] = $this->request->get['tag'];
		}
				
		return $data;
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