<?php
namespace Opencart\Admin\Controller\Extension\Boughttogether\Module;
/**
 * Frequently bought together: settings page.
 */
class Boughttogether extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/boughttogether/module/boughttogether');

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
			'href' => $this->url->link('extension/boughttogether/module/boughttogether', $token)
		];

		$data['save'] = $this->url->link('extension/boughttogether/module/boughttogether.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$data['module_boughttogether_heading'] = $this->config->get('module_boughttogether_heading') ?? $this->language->get('text_default_heading');
		$data['module_boughttogether_limit'] = $this->config->get('module_boughttogether_limit') ?: 3;
		$data['module_boughttogether_min_orders'] = $this->config->get('module_boughttogether_min_orders') ?: 2;
		$data['module_boughttogether_days'] = $this->config->get('module_boughttogether_days') ?: 365;
		$data['module_boughttogether_status'] = $this->config->get('module_boughttogether_status');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/boughttogether/module/boughttogether', $data));
	}

	public function save(): void {
		$this->load->language('extension/boughttogether/module/boughttogether');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/boughttogether/module/boughttogether')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		$limit = (int)($post['module_boughttogether_limit'] ?? 0);
		$min = (int)($post['module_boughttogether_min_orders'] ?? 0);
		$days = (int)($post['module_boughttogether_days'] ?? 0);

		if ($limit < 1 || $limit > 6) {
			$json['error']['limit'] = $this->language->get('error_limit');
		}

		if ($min < 1) {
			$json['error']['min-orders'] = $this->language->get('error_min_orders');
		}

		if ($days < 1) {
			$json['error']['days'] = $this->language->get('error_days');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_boughttogether', $post);

			// Clear cached results so new settings apply at once.
			$this->cache->delete('boughttogether');

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
