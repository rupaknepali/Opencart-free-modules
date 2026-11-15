<?php
namespace Opencart\Admin\Controller\Extension\Loadmore\Module;
/**
 * Load More Products: settings page, and the event that adds the script to product listings.
 */
class Loadmore extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/loadmore/module/loadmore');

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')];
		$data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/loadmore/module/loadmore', $token)];

		$data['save'] = $this->url->link('extension/loadmore/module/loadmore.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$data['module_loadmore_mode'] = $this->config->get('module_loadmore_mode') ?: 'button';
		$data['module_loadmore_auto'] = $this->config->get('module_loadmore_auto') ?? 3;
		$data['module_loadmore_label'] = $this->config->get('module_loadmore_label') ?: $this->language->get('text_default_label');
		$data['module_loadmore_status'] = $this->config->get('module_loadmore_status');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/loadmore/module/loadmore', $data));
	}

	public function save(): void {
		$this->load->language('extension/loadmore/module/loadmore');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/loadmore/module/loadmore')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if (!in_array($this->request->post['module_loadmore_mode'] ?? '', ['button', 'scroll'])) {
			$json['error']['warning'] = $this->language->get('error_mode');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_loadmore', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'loadmore_footer',
			'description' => 'Load More Products: add the script to product listing pages',
			'trigger'     => 'catalog/view/common/footer/after',
			'action'      => 'extension/loadmore/event/loadmore',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('loadmore_footer');
	}
}
