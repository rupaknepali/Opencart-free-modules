<?php
namespace Opencart\Admin\Controller\Extension\Pdfinvoice\Module;
/**
 * PDF Invoice: settings page, plus "PDF invoice" and "Email PDF" buttons on the admin order page.
 */
class Pdfinvoice extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/pdfinvoice/module/pdfinvoice';

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
		$data['mail_settings'] = $this->url->link('setting/setting', $token);
		$data['mail_ready'] = (bool)$this->config->get('config_mail_engine');

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$defaults = [
			'status'        => 0,
			'send_statuses' => (array)$this->config->get('config_complete_status'),
			'invoice_no'    => 1,
			'account'       => 1,
			'logo'          => 1,
			'paper'         => 'a4',
			'tax_id'        => '',
			'footer'        => $this->language->get('text_default_footer')
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_pdfinvoice');

		foreach ($defaults as $key => $value) {
			$data['module_pdfinvoice_' . $key] = array_key_exists('module_pdfinvoice_' . $key, $saved) ? $saved['module_pdfinvoice_' . $key] : $value;
		}

		$data['module_pdfinvoice_send_statuses'] = array_map('intval', (array)$data['module_pdfinvoice_send_statuses']);

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

		if (!$json) {
			$post = $this->request->post;
			$post['module_pdfinvoice_send_statuses'] = array_map('intval', (array)($post['module_pdfinvoice_send_statuses'] ?? []));
			$post['module_pdfinvoice_paper'] = ($post['module_pdfinvoice_paper'] ?? '') == 'letter' ? 'letter' : 'a4';

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_pdfinvoice', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function invoice(): \Opencart\System\Library\Extension\Pdfinvoice\Invoice {
		$this->load->language($this->route);

		$text = array_filter($this->language->all(), fn($key) => str_starts_with($key, 'pdf_'), ARRAY_FILTER_USE_KEY);

		return new \Opencart\System\Library\Extension\Pdfinvoice\Invoice($this->registry, $text);
	}

	/**
	 * Event: admin/view/sale/order_info/after. Adds the two buttons next to "Print invoice".
	 */
	public function orderInfo(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_pdfinvoice_status') || empty($this->request->get['order_id'])) {
			return;
		}

		$this->load->language($this->route);

		$order_id = (int)$this->request->get['order_id'];
		$token = 'user_token=' . $this->session->data['user_token'];

		$last = $this->invoice()->lastSent($order_id);

		$data['download'] = $this->url->link($this->route . '.download', $token . '&order_id=' . $order_id);
		// Used inside JavaScript, so without the &amp; that url->link() adds for HTML attributes.
		$data['send'] = html_entity_decode($this->url->link($this->route . '.send', $token . '&order_id=' . $order_id), ENT_QUOTES, 'UTF-8');
		$data['last_sent'] = $last ? sprintf($this->language->get('text_last_sent'), $last['email'], date($this->language->get('datetime_format'), strtotime($last['date_added']))) : $this->language->get('text_never_sent');

		$html = $this->load->view('extension/pdfinvoice/module/pdfinvoice_order', $data);

		// Right after the core "Print invoice" button.
		if (preg_match('#<a href="[^"]*sale/order\.invoice[^"]*"[^>]*>\s*<i class="fa-solid fa-print"></i>\s*</a>#', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, ' ' . $html, $match[0][1] + strlen($match[0][0]), 0);
		}
	}

	public function download(): void {
		$this->load->language($this->route);

		$order_id = (int)($this->request->get['order_id'] ?? 0);

		if (!$this->user->hasPermission('access', 'sale/order')) {
			$this->response->setOutput($this->language->get('error_permission'));

			return;
		}

		$invoice = $this->invoice();

		if ($this->config->get('module_pdfinvoice_invoice_no') && $this->user->hasPermission('modify', 'sale/order')) {
			$invoice->ensureInvoiceNo($order_id);
		}

		$order = $invoice->getOrder($order_id);

		if (!$order) {
			$this->response->setOutput($this->language->get('error_order'));

			return;
		}

		$this->response->addHeader('Content-Type: application/pdf');
		$this->response->addHeader('Content-Disposition: inline; filename="' . $invoice->filename($order) . '"');
		$this->response->setOutput($invoice->build($order_id));
	}

	public function send(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			try {
				$error = $this->invoice()->send((int)($this->request->get['order_id'] ?? 0), 'admin');
			} catch (\Throwable $e) {
				$error = 'error_send';
				$this->log->write('PDF Invoice: ' . $e->getMessage());
			}

			if ($error) {
				$json['error'] = $this->language->get($error);
			} else {
				$json['success'] = $this->language->get('text_sent');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "pdfinvoice_log` (
			`log_id` INT(11) NOT NULL AUTO_INCREMENT,
			`order_id` INT(11) NOT NULL,
			`email` VARCHAR(96) NOT NULL,
			`source` VARCHAR(16) NOT NULL,
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`log_id`),
			KEY `order_id` (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'pdfinvoice_admin',
			'description' => 'PDF Invoice: buttons on the admin order page',
			'trigger'     => 'admin/view/sale/order_info/after',
			'action'      => 'extension/pdfinvoice/module/pdfinvoice.orderInfo',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'pdfinvoice_history',
			'description' => 'PDF Invoice: email the invoice when an order reaches a chosen status',
			'trigger'     => 'catalog/model/checkout/order.addHistory/after',
			'action'      => 'extension/pdfinvoice/module/pdfinvoice.history',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'pdfinvoice_account',
			'description' => 'PDF Invoice: download button on the customer order page',
			'trigger'     => 'catalog/view/account/order_info/after',
			'action'      => 'extension/pdfinvoice/module/pdfinvoice.account',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('pdfinvoice_admin');
		$this->model_setting_event->deleteEventByCode('pdfinvoice_history');
		$this->model_setting_event->deleteEventByCode('pdfinvoice_account');

		// The send log is kept so a reinstall doesn't email old orders a second time.
	}
}
