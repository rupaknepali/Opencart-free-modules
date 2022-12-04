<?php
class ControllerExtensionModuleDSEOModuleURL extends Controller {
	private $codename = 'd_seo_module_url';
	private $route = 'extension/module/d_seo_module_url';
		
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
}