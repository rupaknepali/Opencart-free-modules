<?php
namespace Opencart\Admin\Controller\Extension\Lowstock\Module;

class Lowstock extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/lowstock/module/lowstock');

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $token)
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', $token . '&type=module')
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/lowstock/module/lowstock', $token)
		];

		$data['save'] = $this->url->link('extension/lowstock/module/lowstock.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$data['module_lowstock_status'] = $this->config->get('module_lowstock_status');
		$data['module_lowstock_threshold'] = $this->config->get('module_lowstock_threshold') ?: 5;
		$data['module_lowstock_text'] = $this->config->get('module_lowstock_text') ?: $this->language->get('text_default_message');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/lowstock/module/lowstock', $data));
	}

	public function save(): void {
		$this->load->language('extension/lowstock/module/lowstock');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/lowstock/module/lowstock')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if ((int)($this->request->post['module_lowstock_threshold'] ?? 0) < 1) {
			$json['error']['threshold'] = $this->language->get('error_threshold');
		}

		if (!str_contains($this->request->post['module_lowstock_text'] ?? '', '%s')) {
			$json['error']['text'] = $this->language->get('error_text');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_lowstock', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'lowstock',
			'description' => 'Show "Only X left" on product pages when stock is low',
			'trigger'     => 'catalog/view/product/product/before',
			'action'      => 'extension/lowstock/event/lowstock',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('lowstock');
	}
}
