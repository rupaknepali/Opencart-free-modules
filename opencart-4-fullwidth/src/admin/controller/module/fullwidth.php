<?php
namespace Opencart\Admin\Controller\Extension\Fullwidth\Module;
/**
 * Full-Width Layout Position: adds a Content Full Width position to layouts.
 */
class Fullwidth extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/fullwidth/module/fullwidth';

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

		$defaults = [
			'status' => 0
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_fullwidth');

		foreach ($defaults as $key => $value) {
			$data['module_fullwidth_' . $key] = array_key_exists('module_fullwidth_' . $key, $saved) ? $saved['module_fullwidth_' . $key] : $value;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];
		$post = $this->request->post;

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_fullwidth', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'fullwidth_layout',
			'description' => 'Full-Width Layout Position: position in the layout form',
			'trigger'     => 'admin/view/design/layout_form/after',
			'action'      => 'extension/fullwidth/event/fullwidth.layout',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'fullwidth_header',
			'description' => 'Full-Width Layout Position: full-width modules under the menu',
			'trigger'     => 'catalog/view/common/header/after',
			'action'      => 'extension/fullwidth/event/fullwidth.header',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('fullwidth_layout');
		$this->model_setting_event->deleteEventByCode('fullwidth_header');
	}
}
