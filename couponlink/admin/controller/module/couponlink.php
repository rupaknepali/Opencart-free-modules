<?php
namespace Opencart\Admin\Controller\Extension\Couponlink\Module;
/**
 * Coupon Links: apply a coupon from a link like ?coupon=SAVE10.
 */
class Couponlink extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/couponlink/module/couponlink';

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
			'param' => 'coupon',
			'message' => 'Coupon %s is saved and will be applied at checkout.',
			'status' => 0
		];

		foreach ($defaults as $key => $value) {
			$setting = $this->config->get('module_couponlink_' . $key);

			$data['module_couponlink_' . $key] = ($setting === null) ? $value : $setting;
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

		if (!preg_match('/^[a-zA-Z0-9_]{1,32}$/', (string)($post['module_couponlink_param'] ?? ''))) {
			$json['error']['param'] = $this->language->get('error_param');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_couponlink', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'couponlink_header',
			'description' => 'Coupon Links: apply coupons from links',
			'trigger'     => 'catalog/view/common/header/after',
			'action'      => 'extension/couponlink/event/couponlink.header',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('couponlink_header');
	}
}
