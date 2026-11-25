<?php
namespace Opencart\Admin\Controller\Extension\Whatsappchat\Module;
/**
 * WhatsApp Chat Button: settings page.
 */
class Whatsappchat extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/whatsappchat/module/whatsappchat';

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
			'status'          => 0,
			'phone'           => '',
			'message'         => $this->language->get('text_default_message'),
			'label'           => $this->language->get('text_default_label'),
			'greeting'        => $this->language->get('text_default_greeting'),
			'position'        => 'right',
			'devices'         => 'all',
			'color'           => '#25d366',
			'product_button'  => 1,
			'ask'             => $this->language->get('text_default_ask'),
			'product_message' => $this->language->get('text_default_product'),
			'hours_status'    => 0,
			'days'            => [1, 2, 3, 4, 5],
			'open'            => '09:00',
			'close'           => '17:00',
			'offline'         => 'note',
			'offline_text'    => $this->language->get('text_default_offline')
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_whatsappchat');

		foreach ($defaults as $key => $value) {
			$data['module_whatsappchat_' . $key] = array_key_exists('module_whatsappchat_' . $key, $saved) ? $saved['module_whatsappchat_' . $key] : $value;
		}

		if (!is_array($data['module_whatsappchat_days'])) {
			$data['module_whatsappchat_days'] = [];
		}

		$data['days'] = [];

		for ($i = 0; $i < 7; $i++) {
			$data['days'][$i] = $this->language->get('text_day_' . $i);
		}
		$data['text_timezone'] = sprintf($this->language->get('text_timezone'), date_default_timezone_get(), date('D H:i'));

		$data['test_link'] = $data['module_whatsappchat_phone'] ? 'https://wa.me/' . $data['module_whatsappchat_phone'] . '?text=' . rawurlencode(html_entity_decode($data['module_whatsappchat_message'], ENT_QUOTES, 'UTF-8')) : '';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		// wa.me wants the full international number as digits only. Strip what people usually type.
		$phone = preg_replace('/[\s\-\.\(\)\+]/', '', (string)($post['module_whatsappchat_phone'] ?? ''));

		if (str_starts_with($phone, '00')) {
			$phone = substr($phone, 2);
		}

		$post['module_whatsappchat_phone'] = $phone;

		if (!$json && !empty($post['module_whatsappchat_status']) && !preg_match('/^[1-9]\d{7,14}$/', $phone)) {
			$json['error']['phone'] = $this->language->get('error_phone');
		}

		$open = (string)($post['module_whatsappchat_open'] ?? '');
		$close = (string)($post['module_whatsappchat_close'] ?? '');

		if (!$json && !empty($post['module_whatsappchat_hours_status']) && (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $open) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $close) || $close <= $open)) {
			$json['error']['time'] = $this->language->get('error_time');
		}

		if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)($post['module_whatsappchat_color'] ?? ''))) {
			$post['module_whatsappchat_color'] = '#25d366';
		}

		$post['module_whatsappchat_days'] = array_values(array_intersect(array_map('intval', (array)($post['module_whatsappchat_days'] ?? [])), range(0, 6)));

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_whatsappchat', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		foreach ($this->events() as [$code, $trigger, $action, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'WhatsApp Chat Button: ' . $description,
				'trigger'     => $trigger,
				'action'      => $action,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach ($this->events() as $event) {
			$this->model_setting_event->deleteEventByCode($event[0]);
		}
	}

	private function events(): array {
		return [
			['whatsappchat_footer', 'catalog/view/common/footer/after', 'extension/whatsappchat/module/whatsappchat.footer', 'floating chat button'],
			['whatsappchat_product', 'catalog/view/product/product/after', 'extension/whatsappchat/module/whatsappchat.product', '"Ask on WhatsApp" button on product pages']
		];
	}
}
