<?php
namespace Opencart\Admin\Controller\Extension\Ordercancel\Module;
/**
 * Customer Order Cancellation: settings page, recent cancellations and a card on the order page.
 */
class Ordercancel extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/ordercancel/module/ordercancel';

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

		// Pending and Processing by name lookup would break in other languages, so use the store's own default order status.
		$defaults = [
			'status'          => 0,
			'statuses'        => array_values(array_unique([(string)$this->config->get('config_order_status_id'), '2'])),
			'hours'           => 24,
			'cancel_status'   => 7,
			'reasons'         => $this->language->get('text_default_reasons'),
			'reason_required' => 1,
			'notify'          => 1
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_ordercancel');

		foreach ($defaults as $key => $value) {
			$data['module_ordercancel_' . $key] = array_key_exists('module_ordercancel_' . $key, $saved) ? $saved['module_ordercancel_' . $key] : $value;
		}

		$data['module_ordercancel_statuses'] = array_map('strval', (array)$data['module_ordercancel_statuses']);

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['recent'] = [];

		$query = $this->db->query("SELECT oc.*, o.`firstname`, o.`lastname` FROM `" . DB_PREFIX . "ordercancel` oc LEFT JOIN `" . DB_PREFIX . "order` o ON (o.`order_id` = oc.`order_id`) ORDER BY oc.`date_added` DESC LIMIT 20");

		foreach ($query->rows as $row) {
			$data['recent'][] = [
				'order_id' => $row['order_id'],
				'customer' => trim($row['firstname'] . ' ' . $row['lastname']),
				'reason'   => $row['reason'],
				'comment'  => $row['comment'],
				'date'     => date($this->language->get('datetime_format'), strtotime($row['date_added'])),
				'href'     => $this->url->link('sale/order.info', $token . '&order_id=' . $row['order_id'])
			];
		}

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
		$post['module_ordercancel_statuses'] = array_values(array_map('strval', array_map('intval', (array)($post['module_ordercancel_statuses'] ?? []))));
		$post['module_ordercancel_hours'] = max(0, (int)($post['module_ordercancel_hours'] ?? 0));

		if (!$json && !$post['module_ordercancel_statuses']) {
			$json['error']['statuses'] = $this->language->get('error_statuses');
		}

		if (!$json && in_array((string)(int)($post['module_ordercancel_cancel_status'] ?? 0), $post['module_ordercancel_statuses'], true)) {
			$json['error']['cancel_status'] = $this->language->get('error_cancel_status');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_ordercancel', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Event: admin/view/sale/order_info/after. Shows the customer's reason above the order history.
	 */
	public function orderInfo(string &$route, array &$args, string &$output): void {
		$order_id = (int)($this->request->get['order_id'] ?? 0);

		if (!$order_id) {
			return;
		}

		$row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ordercancel` WHERE `order_id` = '" . $order_id . "'")->row;

		if (!$row) {
			return;
		}

		$text = $this->load->language($this->route, 'ordercancel');

		$html = '<div class="card mb-3 border-danger"><div class="card-header text-danger"><i class="fa-solid fa-ban"></i> ' . $text['ordercancel_text_card_title'] . '</div><div class="card-body">';
		$html .= '<p class="mb-1"><strong>' . $text['ordercancel_column_date'] . ':</strong> ' . date($this->language->get('datetime_format'), strtotime($row['date_added'])) . '</p>';

		if ($row['reason'] !== '') {
			$html .= '<p class="mb-1"><strong>' . $text['ordercancel_column_reason'] . ':</strong> ' . htmlspecialchars($row['reason'], ENT_QUOTES, 'UTF-8') . '</p>';
		}

		if ($row['comment'] !== '') {
			$html .= '<p class="mb-1"><strong>' . $text['ordercancel_column_comment'] . ':</strong> ' . nl2br(htmlspecialchars($row['comment'], ENT_QUOTES, 'UTF-8')) . '</p>';
		}

		$html .= '<p class="mb-0 text-muted small">' . $text['ordercancel_text_card_help'] . '</p></div></div>';

		// Above the order history card (the one with the comment icon).
		if (preg_match('#<div class="card mb-3">\s*<div class="card-header"><i class="fa-solid fa-comment"></i>#', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1], 0);
		}
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ordercancel` (
			`order_id` INT(11) NOT NULL,
			`customer_id` INT(11) NOT NULL,
			`previous_status_id` INT(11) NOT NULL,
			`reason` VARCHAR(255) NOT NULL DEFAULT '',
			`comment` TEXT NOT NULL,
			`ip` VARCHAR(40) NOT NULL DEFAULT '',
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		foreach ($this->events() as [$code, $trigger, $action, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Customer Order Cancellation: ' . $description,
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

		// The table is kept so the cancellation history survives a reinstall.
	}

	private function events(): array {
		return [
			['ordercancel_admin_order', 'admin/view/sale/order_info/after', 'extension/ordercancel/module/ordercancel.orderInfo', 'reason card on the admin order page'],
			['ordercancel_account_order', 'catalog/view/account/order_info/after', 'extension/ordercancel/module/ordercancel.orderInfo', 'Cancel order button on the customer order page']
		];
	}
}
