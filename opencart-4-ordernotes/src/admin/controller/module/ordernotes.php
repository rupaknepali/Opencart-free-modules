<?php
namespace Opencart\Admin\Controller\Extension\Ordernotes\Module;
/**
 * Staff Order Notes: settings, install and uninstall. The order page code is in sale/note.php.
 */
class Ordernotes extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/ordernotes/module/ordernotes';

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

		$this->load->model($this->route);

		foreach ($this->model_extension_ordernotes_module_ordernotes->getSettings() as $key => $value) {
			$data['module_ordernotes_' . $key] = $value;
		}

		$totals = $this->model_extension_ordernotes_module_ordernotes->getTotals();

		$data['text_total'] = sprintf($this->language->get('text_total'), (int)$totals['notes'], (int)$totals['orders']);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_settings');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_ordernotes', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model($this->route);
		$this->model_extension_ordernotes_module_ordernotes->install();

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'ordernotes_order_info',
			'description' => 'Staff Order Notes: notes card on the order page',
			'trigger'     => 'admin/view/sale/order_info/after',
			'action'      => 'extension/ordernotes/sale/note.orderInfo',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'ordernotes_order_list',
			'description' => 'Staff Order Notes: note badges in the order list',
			'trigger'     => 'admin/view/sale/order_list/after',
			'action'      => 'extension/ordernotes/sale/note.orderList',
			'status'      => 1,
			'sort_order'  => 0
		]);

		// Every user group that can modify orders may use the notes (OpenCart checks access per route).
		$this->load->model('user/user_group');

		foreach ($this->model_user_user_group->getUserGroups() as $user_group) {
			$permission = $user_group['permission'] ? json_decode($user_group['permission'], true) : [];

			if (in_array('sale/order', $permission['modify'] ?? [])) {
				$this->model_user_user_group->addPermission((int)$user_group['user_group_id'], 'access', 'extension/ordernotes/sale/note');
				$this->model_user_user_group->addPermission((int)$user_group['user_group_id'], 'modify', 'extension/ordernotes/sale/note');
			}
		}
	}

	public function uninstall(): void {
		// The notes table is kept on purpose, so reinstalling doesn't lose your team's notes.
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('ordernotes_order_info');
		$this->model_setting_event->deleteEventByCode('ordernotes_order_list');

		$this->load->model('user/user_group');

		foreach ($this->model_user_user_group->getUserGroups() as $user_group) {
			$this->model_user_user_group->removePermission((int)$user_group['user_group_id'], 'access', 'extension/ordernotes/sale/note');
			$this->model_user_user_group->removePermission((int)$user_group['user_group_id'], 'modify', 'extension/ordernotes/sale/note');
		}
	}
}
