<?php
class ControllerExtensionDSEOModuleAdviserDSEOModule extends Controller {
	private $codename = 'd_seo_module';
	private $route = 'extension/d_seo_module_adviser/d_seo_module';
	private $config_file = 'd_seo_module';
	private $error = array();
	
	/*
	*	Functions for SEO Module Adviser.
	*/
	public function adviser_elements($route) {	
		$this->load->model($this->route);
		
		return $this->{'model_extension_d_seo_module_adviser_' . $this->codename}->getAdviserElements($route);
	}
}
