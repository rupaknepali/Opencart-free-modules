<?php
class ControllerExtensionModuleWebocreationyoutube extends Controller
{
	public function index($setting)
	{
		static $module = 0;
		$data['webocreationyoutubes'] = array();
		$data['youtube_id'] = $setting['webocreationyoutube_id'];
		$data['module'] = $module++;
		return $this->load->view('extension/module/webocreationyoutube', $data);
	}
}
