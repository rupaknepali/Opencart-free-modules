<?php
namespace Opencart\Admin\Controller\Extension\Withdrawal\Module;
/**
 * EU Withdrawal Button: settings and the list of withdrawal requests.
 */
class Withdrawal extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/withdrawal/module/withdrawal';

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
		$data['process'] = $this->url->link($this->route . '.process', $token);

		$defaults = [
			'status'       => 0,
			'link'         => $this->language->get('text_default_link'),
			'confirm'      => $this->language->get('text_default_confirm'),
			'intro'        => $this->language->get('text_default_intro'),
			'footer'       => 1,
			'account'      => 1,
			'mail'         => 1,
			'order_status' => 0,
			'alert'        => 1
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_withdrawal');

		foreach ($defaults as $key => $value) {
			$data['module_withdrawal_' . $key] = array_key_exists('module_withdrawal_' . $key, $saved) ? $saved['module_withdrawal_' . $key] : $value;
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['requests'] = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "withdrawal` ORDER BY `date_added` DESC LIMIT 50");

		foreach ($query->rows as $row) {
			$data['requests'][] = [
				'id'        => $row['withdrawal_id'],
				'date'      => date($this->language->get('datetime_format'), strtotime($row['date_added'])),
				'name'      => $row['name'],
				'email'     => $row['email'],
				'order'     => $row['order_reference'],
				'order_url' => $row['order_id'] ? $this->url->link('sale/order.info', $token . '&order_id=' . $row['order_id']) : '',
				'matched'   => (bool)$row['order_id'],
				'items'     => $row['items'],
				'processed' => (bool)$row['processed']
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

		if (!$json && (trim((string)($post['module_withdrawal_link'] ?? '')) === '' || trim((string)($post['module_withdrawal_confirm'] ?? '')) === '')) {
			$json['error']['warning'] = $this->language->get('error_text');
		}

		$post['module_withdrawal_order_status'] = (int)($post['module_withdrawal_order_status'] ?? 0);

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_withdrawal', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function process(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->db->query("UPDATE `" . DB_PREFIX . "withdrawal` SET `processed` = '1' WHERE `withdrawal_id` = '" . (int)($this->request->post['id'] ?? 0) . "'");

			$json['success'] = $this->language->get('text_processed');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "withdrawal` (
			`withdrawal_id` INT(11) NOT NULL AUTO_INCREMENT,
			`store_id` INT(11) NOT NULL DEFAULT '0',
			`customer_id` INT(11) NOT NULL DEFAULT '0',
			`order_id` INT(11) NOT NULL DEFAULT '0',
			`order_reference` VARCHAR(64) NOT NULL DEFAULT '',
			`name` VARCHAR(128) NOT NULL,
			`email` VARCHAR(96) NOT NULL,
			`items` TEXT NOT NULL,
			`ip` VARCHAR(40) NOT NULL DEFAULT '',
			`processed` TINYINT(1) NOT NULL DEFAULT '0',
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`withdrawal_id`),
			KEY `order_id` (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		foreach ($this->events() as [$code, $trigger, $action, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'EU Withdrawal Button: ' . $description,
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

		// The table is kept: withdrawal records are evidence you may need later.
	}

	private function events(): array {
		return [
			['withdrawal_footer', 'catalog/view/common/footer/after', 'extension/withdrawal/module/withdrawal.footer', 'link in the footer'],
			['withdrawal_account', 'catalog/view/account/order_info/after', 'extension/withdrawal/module/withdrawal.orderInfo', 'link on customer order pages'],
			['withdrawal_mail', 'catalog/view/mail/order_add/after', 'extension/withdrawal/module/withdrawal.mail', 'note in order confirmation emails']
		];
	}
}
