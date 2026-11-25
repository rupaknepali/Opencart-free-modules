<?php
namespace Opencart\Admin\Controller\Extension\Newsletterpopup\Module;
/**
 * Newsletter Popup: settings, the subscriber list and a CSV export.
 */
class Newsletterpopup extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/newsletterpopup/module/newsletterpopup';

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
		$data['export'] = $this->url->link($this->route . '.export', $token);
		$data['delete'] = $this->url->link($this->route . '.delete', $token);

		$defaults = [
			'status'       => 0,
			'title'        => $this->language->get('text_default_title'),
			'text'         => $this->language->get('text_default_text'),
			'button'       => $this->language->get('text_default_button'),
			'consent'      => $this->language->get('text_default_consent'),
			'coupon'       => '',
			'success'      => $this->language->get('text_default_success'),
			'double_optin' => 1,
			'pending'      => $this->language->get('text_default_pending'),
			'delay'        => 8,
			'exit'         => 1,
			'days'         => 14,
			'guests'       => 1
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_newsletterpopup');

		foreach ($defaults as $key => $value) {
			$data['module_newsletterpopup_' . $key] = array_key_exists('module_newsletterpopup_' . $key, $saved) ? $saved['module_newsletterpopup_' . $key] : $value;
		}

		$data['subscribers'] = [];

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "newsletterpopup` ORDER BY `date_added` DESC LIMIT 50");

		foreach ($query->rows as $row) {
			$data['subscribers'][] = [
				'id'        => $row['newsletterpopup_id'],
				'email'     => $row['email'],
				'confirmed' => (bool)$row['confirmed'],
				'customer'  => $row['customer_id'] ? $this->url->link('customer/customer.form', $token . '&customer_id=' . $row['customer_id']) : '',
				'date'      => date($this->language->get('datetime_format'), strtotime($row['date_added']))
			];
		}

		$totals = $this->db->query("SELECT SUM(`confirmed` = 1) AS `confirmed`, SUM(`confirmed` = 0) AS `pending` FROM `" . DB_PREFIX . "newsletterpopup`")->row;

		$data['text_total'] = sprintf($this->language->get('text_total'), (int)$totals['confirmed'], (int)$totals['pending']);

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

		if (!$json && trim((string)($post['module_newsletterpopup_title'] ?? '')) === '') {
			$json['error']['title'] = $this->language->get('error_title');
		}

		$coupon = trim((string)($post['module_newsletterpopup_coupon'] ?? ''));

		if (!$json && $coupon !== '') {
			$found = $this->db->query("SELECT `coupon_id` FROM `" . DB_PREFIX . "coupon` WHERE `code` = '" . $this->db->escape($coupon) . "' AND `status` = '1'")->row;

			if (!$found) {
				$json['error']['coupon'] = $this->language->get('error_coupon');
			}
		}

		$post['module_newsletterpopup_delay'] = max(0, (int)($post['module_newsletterpopup_delay'] ?? 0));
		$post['module_newsletterpopup_days'] = max(0, (int)($post['module_newsletterpopup_days'] ?? 0));

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_newsletterpopup', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function delete(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "newsletterpopup` WHERE `newsletterpopup_id` = '" . (int)($this->request->post['id'] ?? 0) . "'");

			$json['success'] = $this->language->get('text_deleted');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Confirmed subscribers as CSV, ready to import into Mailchimp, Klaviyo or Brevo.
	 */
	public function export(): void {
		if (!$this->user->hasPermission('access', $this->route)) {
			$this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token']));

			return;
		}

		$out = fopen('php://temp', 'r+');

		fputcsv($out, ['email', 'confirmed', 'signed_up', 'confirmed_at', 'consent_text', 'ip'], ',', '"', '');

		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "newsletterpopup` ORDER BY `date_added`");

		foreach ($query->rows as $row) {
			$email = $row['email'];

			// Stop spreadsheet apps from running a cell as a formula.
			if (preg_match('/^[=+\-@]/', $email)) {
				$email = "'" . $email;
			}

			fputcsv($out, [$email, $row['confirmed'] ? 'yes' : 'no', $row['date_added'], $row['date_confirmed'] ?? '', $row['consent'], $row['ip']], ',', '"', '');
		}

		rewind($out);

		$this->response->addHeader('Content-Type: text/csv; charset=utf-8');
		$this->response->addHeader('Content-Disposition: attachment; filename="newsletter-subscribers-' . date('Y-m-d') . '.csv"');
		$this->response->setOutput(stream_get_contents($out));
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "newsletterpopup` (
			`newsletterpopup_id` INT(11) NOT NULL AUTO_INCREMENT,
			`store_id` INT(11) NOT NULL DEFAULT '0',
			`email` VARCHAR(96) NOT NULL,
			`customer_id` INT(11) NOT NULL DEFAULT '0',
			`confirmed` TINYINT(1) NOT NULL DEFAULT '0',
			`token` VARCHAR(64) NOT NULL DEFAULT '',
			`consent` VARCHAR(255) NOT NULL DEFAULT '',
			`ip` VARCHAR(40) NOT NULL DEFAULT '',
			`date_added` DATETIME NOT NULL,
			`date_confirmed` DATETIME NULL,
			PRIMARY KEY (`newsletterpopup_id`),
			UNIQUE KEY `store_email` (`store_id`, `email`),
			KEY `token` (`token`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'newsletterpopup_footer',
			'description' => 'Newsletter Popup: popup markup on storefront pages',
			'trigger'     => 'catalog/view/common/footer/after',
			'action'      => 'extension/newsletterpopup/module/newsletterpopup.footer',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('newsletterpopup_footer');

		// The subscriber table is kept: deleting a mailing list by accident is worse than a leftover table.
	}
}
