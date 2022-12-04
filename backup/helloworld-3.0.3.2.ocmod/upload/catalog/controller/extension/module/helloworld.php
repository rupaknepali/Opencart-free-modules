<?php
class ControllerExtensionModuleHelloworld extends Controller
{
	public function index($setting)
	{
		if (isset($setting['name'][$this->config->get('config_language_id')])) {
			$data['html'] = html_entity_decode($setting['module_description'][$this->config->get('config_language_id')]['title'], ENT_QUOTES, 'UTF-8');
			return $this->load->view('extension/module/helloworld', $data);
		}
	}
}
