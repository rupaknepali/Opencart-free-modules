<?php
class ControllerExtensionModuleDSEOModule extends Controller {
	private $codename = 'd_seo_module';
	private $route = 'extension/module/d_seo_module';
	private $config_file = 'd_seo_module';
	
	public function seo_url() {
		$this->load->model($this->route);
		
		// Setting
		$_config = new Config();
		$_config->load($this->config_file);
		$config_setting = ($_config->get($this->codename . '_setting')) ? $_config->get($this->codename . '_setting') : array();
				
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		$setting = ($this->config->get('module_' . $this->codename . '_setting')) ? $this->config->get('module_' . $this->codename . '_setting') : array();
		
		if (!empty($setting)) {
			$config_setting = array_replace_recursive($config_setting, $setting);
		}
		
		$setting = $config_setting;
		
		$this->config->set('module_' . $this->codename . '_setting', $setting);
		
		if ($status) {			
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/seo_url_add_rewrite');
			}
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/seo_url_analyse');
			}
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/seo_url_validate');
			}
		}
	}
		
	public function language_language() {
		$this->load->model($this->route);
				
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/language_language');
			}
		}
	}
	
	public function header_before($route, &$data) {
		$this->load->model($this->route);
				
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/header_before', $data);
				if ($info) $data = $info;
			}
		}			
	}
			
	public function header_after($route, $data, &$output) {
		$this->load->model($this->route);
				
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/header_after', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function footer_before($route, &$data) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/footer_before', $data);
				if ($info) $data = $info;
			}
		}			
	}
			
	public function footer_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/footer_after', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function home_before($route, &$data) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/home_before', $data);
				if ($info) $data = $info;
			}
		}			
	}
			
	public function home_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/home_after', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function category_before($route, &$data) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_before', $data);
				if ($info) $data = $info;
			}
		}
	}
			
	public function category_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_after', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function category_get_category_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_get_category', $output);
				if ($info) $output = $info;
			}
		}
	}
		
	public function category_get_categories_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/category_get_categories', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function product_before($route, &$data) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_before', $data);
				if ($info) $data = $info;
			}
		}			
	}
			
	public function product_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_after', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function product_get_product_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_get_product', $output);
				if ($info) $output = $info;
			}
		}
	}
		
	public function product_get_products_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/product_get_products', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function manufacturer_list_before($route, &$data) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_list_before', $data);
				if ($info) $data = $info;
			}	
		}
	}
			
	public function manufacturer_list_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_list_after', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function manufacturer_info_before($route, &$data) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_info_before', $data);
				if ($info) $data = $info;
			}	
		}
	}
			
	public function manufacturer_info_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_info_after', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function manufacturer_get_manufacturer_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_get_manufacturer', $output);
				if ($info) $output = $info;
			}
		}
	}
		
	public function manufacturer_get_manufacturers_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/manufacturer_get_manufacturers', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function information_before($route, &$data) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_before', $data);
				if ($info) $data = $info;
			}
		}
	}
			
	public function information_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_after', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function information_get_information_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_get_information', $output);
				if ($info) $output = $info;
			}
		}
	}
		
	public function information_get_informations_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
			
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/information_get_informations', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function search_before($route, &$data) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/search_before', $data);
				if ($info) $data = $info;
			}
		}
	}
			
	public function search_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/search_after', $output);
				if ($info) $output = $info;
			}
		}
	}
	
	public function special_before($route, &$data) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/special_before', $data);
				if ($info) $data = $info;
			}
		}
	}
			
	public function special_after($route, $data, &$output) {
		$this->load->model($this->route);
		
		$status = ($this->config->get('module_' . $this->codename . '_status')) ? $this->config->get('module_' . $this->codename . '_status') : false;
		
		if ($status) {
			$installed_seo_extensions = $this->{'model_extension_module_' . $this->codename}->getInstalledSEOExtensions();
		
			foreach ($installed_seo_extensions as $installed_seo_extension) {
				$info = $this->load->controller('extension/' . $this->codename . '/' . $installed_seo_extension . '/special_after', $output);
				if ($info) $output = $info;
			}
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
		
		$field_setting = ($this->config->get('module_' . $this->codename . '_field_setting')) ? $this->config->get('module_' . $this->codename . '_field_setting') : array();
			
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
}