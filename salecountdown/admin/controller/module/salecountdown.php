<?php
namespace Opencart\Admin\Controller\Extension\Salecountdown\Module;
/**
 * Sale Countdown Timer: settings page, and the events that show the countdown.
 */
class Salecountdown extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/salecountdown/module/salecountdown';

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token)]
		];

		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$defaults = ['product' => 1, 'card' => 1, 'days' => 7, 'status' => 0];

		foreach ($defaults as $key => $value) {
			$setting = $this->config->get('module_salecountdown_' . $key);

			$data['module_salecountdown_' . $key] = ($setting === null || $setting === '') ? $value : $setting;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if ((int)($this->request->post['module_salecountdown_days'] ?? -1) < 0) {
			$json['error']['days'] = $this->language->get('error_days');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_salecountdown', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$events = [
			'salecountdown_product' => ['catalog/view/product/product/after', 'product', 'Sale Countdown Timer: countdown on product pages'],
			'salecountdown_thumb'   => ['catalog/view/product/thumb/after', 'thumb', 'Sale Countdown Timer: countdown on product cards'],
			'salecountdown_footer'  => ['catalog/view/common/footer/after', 'footer', 'Sale Countdown Timer: styles and ticking script']
		];

		foreach ($events as $code => [$trigger, $method, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => $description,
				'trigger'     => $trigger,
				'action'      => 'extension/salecountdown/event/salecountdown.' . $method,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (['salecountdown_product', 'salecountdown_thumb', 'salecountdown_footer'] as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}
	}
}
