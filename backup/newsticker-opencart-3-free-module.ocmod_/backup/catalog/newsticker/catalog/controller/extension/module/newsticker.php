<?php
class ControllerExtensionModulenewsticker extends Controller {
	public function index($setting) {
		static $module = 0;
        $this->load->language('extension/module/newsticker');
		$this->load->model('design/newsticker');
		$this->load->model('tool/image');

		$this->document->addStyle('catalog/view/theme/default/stylesheet/newsticker.css');
		$this->document->addScript('catalog/view/javascript/newsticker.js');

		$data['newstickers'] = array();

        $data['color'] =$setting['color'];
        $data['limitedtime'] =$setting['limitedtime'];
		$results = $this->model_design_newsticker->getnewsticker($setting['newsticker_id']);
        $data['limited_time_offer']=$this->language->get('limited_time_offer');
		foreach ($results as $result) {
				$data['newstickers'][] = array(
					'message' => $result['message'],
					'name'  => $result['name']
				);
		}

		$data['module'] = $module++;

		return $this->load->view('extension/module/newsticker', $data);
	}
}