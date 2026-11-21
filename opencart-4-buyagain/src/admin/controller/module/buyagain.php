<?php
namespace Opencart\Admin\Controller\Extension\Buyagain\Module;
/**
 * Buy It Again: reorder a whole past order in one click, and a block of previously purchased products.
 */
class Buyagain extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/buyagain/module/buyagain';

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
			'status' => 0,
			'reorder' => 1,
			'account' => 1,
			'heading' => 'Buy it again',
			'limit' => 8,
			'image' => 200
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_buyagain');

		foreach ($defaults as $key => $value) {
			$data['module_buyagain_' . $key] = array_key_exists('module_buyagain_' . $key, $saved) ? $saved['module_buyagain_' . $key] : $value;
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

		$post['module_buyagain_limit'] = max(1, min(24, (int)($post['module_buyagain_limit'] ?? 8)));
		$post['module_buyagain_image'] = max(40, min(600, (int)($post['module_buyagain_image'] ?? 200)));

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_buyagain', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'buyagain_order',
			'description' => 'Buy It Again: Add all to cart on the order page',
			'trigger'     => 'catalog/view/account/order_info/after',
			'action'      => 'extension/buyagain/event/buyagain.order',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'buyagain_account',
			'description' => 'Buy It Again: block on My Account',
			'trigger'     => 'catalog/view/account/account/after',
			'action'      => 'extension/buyagain/event/buyagain.account',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('buyagain_order');
		$this->model_setting_event->deleteEventByCode('buyagain_account');
	}
}
