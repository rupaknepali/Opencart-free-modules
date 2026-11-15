<?php
namespace Opencart\Admin\Controller\Extension\Quickview\Module;
/**
 * Product Quick View: settings page, and the two events it needs.
 */
class Quickview extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/quickview/module/quickview');

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
			'href' => $this->url->link('extension/quickview/module/quickview', $token)
		];

		$data['save'] = $this->url->link('extension/quickview/module/quickview.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$data['module_quickview_label'] = $this->config->get('module_quickview_label') ?: $this->language->get('text_default_label');
		$data['module_quickview_status'] = $this->config->get('module_quickview_status');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/quickview/module/quickview', $data));
	}

	public function save(): void {
		$this->load->language('extension/quickview/module/quickview');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/quickview/module/quickview')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_quickview', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'quickview_thumb',
			'description' => 'Product Quick View: add the Quick view button to product cards',
			'trigger'     => 'catalog/view/product/thumb/after',
			'action'      => 'extension/quickview/event/quickview.thumb',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'quickview_footer',
			'description' => 'Product Quick View: add the popup to every page',
			'trigger'     => 'catalog/view/common/footer/after',
			'action'      => 'extension/quickview/event/quickview.footer',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('quickview_thumb');
		$this->model_setting_event->deleteEventByCode('quickview_footer');
	}
}
