<?php
class ControllerExtensionDSEOModuleURLKeywordDSEOModuleURLKeyword extends Controller {
	private $codename = 'd_seo_module_url_keyword';
	private $route = 'extension/d_seo_module_url_keyword/d_seo_module_url_keyword';
	private $config_file = 'd_seo_module_url_keyword';
	private $error = array();
	
	/*
	*	Functions for SEO Module URL Keyword.
	*/
	public function edit_url_element($url_element_data) {
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_url_keyword_' . $this->codename}->editURLElement($url_element_data);
	}
	
	public function url_elements() {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_url_keyword_' . $this->codename}->getURLElements();
	}
		
	public function store_url_elements_links($store_url_elements) {	
		$url_token = '';
		
		if (isset($this->session->data['token'])) {
			$url_token .= 'token=' . $this->session->data['token'];
		}
		
		if (isset($this->session->data['user_token'])) {
			$url_token .= 'user_token=' . $this->session->data['user_token'];
		}
		
		foreach ($store_url_elements as $store_id => $url_elements) {
			foreach ($url_elements as $url_element_key => $url_element) {
				if (strpos($url_element['route'], 'category_id') === 0) {
					$route_arr = explode("category_id=", $url_element['route']);
				
					if (isset($route_arr[1])) {
						$category_id = $route_arr[1];
						$store_url_elements[$store_id][$url_element_key]['link'] = $this->url->link('catalog/category/edit', $url_token . '&category_id=' . $category_id, true);
					}
				} elseif (strpos($url_element['route'], 'product_id') === 0) {
					$route_arr = explode("product_id=", $url_element['route']);
				
					if (isset($route_arr[1])) {
						$product_id = $route_arr[1];
						$store_url_elements[$store_id][$url_element_key]['link'] = $this->url->link('catalog/product/edit', $url_token . '&product_id=' . $product_id, true);
					}
				} elseif (strpos($url_element['route'], 'manufacturer_id') === 0) {
					$route_arr = explode("manufacturer_id=", $url_element['route']);
				
					if (isset($route_arr[1])) {
						$manufacturer_id = $route_arr[1];
						$store_url_elements[$store_id][$url_element_key]['link'] = $this->url->link('catalog/manufacturer/edit', $url_token . '&manufacturer_id=' . $manufacturer_id, true);
					}
				} elseif (strpos($url_element['route'], 'information_id') === 0) {
					$route_arr = explode("information_id=", $url_element['route']);
				
					if (isset($route_arr[1])) {
						$information_id = $route_arr[1];
						$store_url_elements[$store_id][$url_element_key]['link'] = $this->url->link('catalog/information/edit', $url_token . '&information_id=' . $information_id, true);
					}
				}
			}
		}
		
		return $store_url_elements;
	}
}
