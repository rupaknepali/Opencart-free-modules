<?php
namespace Opencart\Admin\Controller\Extension\Recentlyviewed\Module;
/**
 * Recently Viewed Products: settings page, and the event that records views.
 */
class Recentlyviewed extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/recentlyviewed/module/recentlyviewed');

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')];
		$data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/recentlyviewed/module/recentlyviewed', $token)];

		$data['save'] = $this->url->link('extension/recentlyviewed/module/recentlyviewed.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$data['module_recentlyviewed_heading'] = $this->config->get('module_recentlyviewed_heading') ?: $this->language->get('text_default_heading');
		$data['module_recentlyviewed_limit'] = $this->config->get('module_recentlyviewed_limit') ?: 4;
		$data['module_recentlyviewed_width'] = $this->config->get('module_recentlyviewed_width') ?: 200;
		$data['module_recentlyviewed_height'] = $this->config->get('module_recentlyviewed_height') ?: 200;
		$data['module_recentlyviewed_clear'] = $this->config->get('module_recentlyviewed_clear') ?? 1;
		$data['module_recentlyviewed_status'] = $this->config->get('module_recentlyviewed_status');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/recentlyviewed/module/recentlyviewed', $data));
	}

	public function save(): void {
		$this->load->language('extension/recentlyviewed/module/recentlyviewed');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/recentlyviewed/module/recentlyviewed')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$limit = (int)($this->request->post['module_recentlyviewed_limit'] ?? 0);

		if ($limit < 1 || $limit > 20) {
			$json['error']['limit'] = $this->language->get('error_limit');
		}

		if ((int)($this->request->post['module_recentlyviewed_width'] ?? 0) < 1 || (int)($this->request->post['module_recentlyviewed_height'] ?? 0) < 1) {
			$json['error']['size'] = $this->language->get('error_size');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_recentlyviewed', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'recentlyviewed_track',
			'description' => 'Recently Viewed Products: remember each product a shopper views',
			'trigger'     => 'catalog/view/product/product/before',
			'action'      => 'extension/recentlyviewed/event/recentlyviewed.track',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('recentlyviewed_track');
	}
}
