<?php
namespace Opencart\Admin\Controller\Extension\Minorder\Module;
/**
 * Minimum Order Amount: per customer group rules, and the events that enforce them.
 */
class Minorder extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/minorder/module/minorder';

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

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['module_minorder_rule'] = (array)$this->config->get('module_minorder_rule');
		$data['module_minorder_basis'] = $this->config->get('module_minorder_basis') ?: 'subtotal';
		$data['module_minorder_status'] = $this->config->get('module_minorder_status');

		$data['currency'] = $this->config->get('config_currency');

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

		foreach ((array)($this->request->post['module_minorder_rule'] ?? []) as $customer_group_id => $rule) {
			if ((float)($rule['amount'] ?? 0) < 0 || (int)($rule['quantity'] ?? 0) < 0) {
				$json['error']['warning'] = $this->language->get('error_negative');
			}
		}

		if (!in_array($this->request->post['module_minorder_basis'] ?? '', ['subtotal', 'total'])) {
			$json['error']['warning'] = $this->language->get('error_basis');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_minorder', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'minorder_cart',
			'description' => 'Minimum Order Amount: notice on the cart page',
			'trigger'     => 'catalog/view/checkout/cart_list/after',
			'action'      => 'extension/minorder/event/minorder.cart',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'minorder_checkout',
			'description' => 'Minimum Order Amount: send carts below the minimum back from checkout',
			'trigger'     => 'catalog/controller/checkout/checkout/before',
			'action'      => 'extension/minorder/event/minorder.checkout',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('minorder_cart');
		$this->model_setting_event->deleteEventByCode('minorder_checkout');
	}
}
