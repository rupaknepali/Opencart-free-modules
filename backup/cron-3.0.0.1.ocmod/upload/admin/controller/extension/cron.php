<?php
class ControllerExtensionCron extends Controller {
	public function index() {
		$time = time();

		$this->load->model('extension/module/cron');

		$results = $this->model_extension_module_cron->getCrons();

		foreach ($results as $result) {
			if ($result['status'] && (strtotime('+1 ' . $result['cycle'], strtotime($result['date_modified'])) < ($time + 10))) {
				$this->load->controller($result['action'], $result['cron_id'], $result['code'], $result['cycle'], $result['date_added'], $result['date_modified']);

				$this->model_extension_module_cron->editCron($result['cron_id']);
			}
		}
	}
}