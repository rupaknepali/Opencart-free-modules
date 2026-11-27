<?php
namespace Opencart\Admin\Controller\Extension\Recentsales\Module;
/**
 * Recent Sales Notifications: settings.
 */
class Recentsales extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/recentsales/module/recentsales';

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

		$complete = array_map('strval', (array)$this->config->get('config_complete_status'));
		$processing = array_map('strval', (array)$this->config->get('config_processing_status'));

		$defaults = [
			'status'       => 0,
			'popup'        => 1,
			'statuses'     => array_values(array_unique(array_merge($processing, $complete))),
			'days'         => 7,
			'text'         => $this->language->get('text_default_popup'),
			'city'         => 0,
			'city_text'    => $this->language->get('text_default_city'),
			'delay'        => 10,
			'interval'     => 25,
			'max'          => 3,
			'counter'      => 1,
			'counter_text' => $this->language->get('text_default_counter'),
			'min'          => 3
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_recentsales');

		foreach ($defaults as $key => $value) {
			$data['module_recentsales_' . $key] = array_key_exists('module_recentsales_' . $key, $saved) ? $saved['module_recentsales_' . $key] : $value;
		}

		$data['module_recentsales_statuses'] = array_map('strval', (array)$data['module_recentsales_statuses']);

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$statuses = array_map('intval', $data['module_recentsales_statuses']) ?: [0];
		$days = max(1, (int)$data['module_recentsales_days']);

		$count = $this->db->query("SELECT COUNT(DISTINCT op.`product_id`) AS `total` FROM `" . DB_PREFIX . "order_product` op LEFT JOIN `" . DB_PREFIX . "order` o ON (o.`order_id` = op.`order_id`) WHERE o.`order_status_id` IN (" . implode(',', $statuses) . ") AND o.`date_added` > DATE_SUB(NOW(), INTERVAL " . $days . " DAY)")->row;

		$data['text_preview'] = sprintf($this->language->get('text_preview'), (int)$count['total'], $days);

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

		$post = $this->request->post;
		$post['module_recentsales_statuses'] = array_values(array_map('strval', array_map('intval', (array)($post['module_recentsales_statuses'] ?? []))));

		foreach (['days' => 1, 'delay' => 0, 'interval' => 5, 'max' => 1, 'min' => 1] as $key => $min) {
			$post['module_recentsales_' . $key] = max($min, (int)($post['module_recentsales_' . $key] ?? $min));
		}

		if (!$json && !$post['module_recentsales_statuses']) {
			$json['error']['statuses'] = $this->language->get('error_statuses');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_recentsales', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		foreach ([
			['recentsales_footer', 'catalog/view/common/footer/after', 'extension/recentsales/module/recentsales.footer', 'popup script'],
			['recentsales_product', 'catalog/view/product/product/after', 'extension/recentsales/module/recentsales.product', 'counter on product pages']
		] as [$code, $trigger, $action, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Recent Sales Notifications: ' . $description,
				'trigger'     => $trigger,
				'action'      => $action,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('recentsales_footer');
		$this->model_setting_event->deleteEventByCode('recentsales_product');
	}
}
