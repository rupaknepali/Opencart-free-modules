<?php
namespace Opencart\Admin\Controller\Extension\Autorelated\Module;

class Autorelated extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/autorelated/module/autorelated');

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
			'href' => $this->url->link('extension/autorelated/module/autorelated', $token)
		];

		$data['save'] = $this->url->link('extension/autorelated/module/autorelated.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$data['module_autorelated_status'] = $this->config->get('module_autorelated_status');
		$data['module_autorelated_limit'] = $this->config->get('module_autorelated_limit') ?: 4;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/autorelated/module/autorelated', $data));
	}

	public function save(): void {
		$this->load->language('extension/autorelated/module/autorelated');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/autorelated/module/autorelated')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$limit = (int)($this->request->post['module_autorelated_limit'] ?? 0);

		if ($limit < 1 || $limit > 24) {
			$json['error']['limit'] = $this->language->get('error_limit');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_autorelated', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'autorelated',
			'description' => 'Fill empty related products with products from the same category',
			// "?" matches one character: "/" in OpenCart 4.0.2.x, "." in 4.1.x
			'trigger'     => 'catalog/model/catalog/product?getRelated/after',
			'action'      => 'extension/autorelated/event/autorelated',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('autorelated');
	}
}
