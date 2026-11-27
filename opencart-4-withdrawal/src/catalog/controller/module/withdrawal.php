<?php
namespace Opencart\Catalog\Controller\Extension\Withdrawal\Module;
/**
 * EU Withdrawal Button: the "withdraw from contract here" function required for online sales to EU
 * consumers since 19 June 2026 (Article 11a of Directive 2011/83/EU).
 *
 * Step 1 (index): form with name, contract (order) details and email. No login needed.
 * Step 2 (review): the statement and a "confirm withdrawal" button.
 * Step 3 (confirm): store it, acknowledge it by email with its content and date and time.
 */
class Withdrawal extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/withdrawal/module/withdrawal';

	private function setting(string $key): string {
		return html_entity_decode((string)$this->config->get('module_withdrawal_' . $key), ENT_QUOTES, 'UTF-8');
	}

	private function link(array $extra = []): string {
		$args = 'language=' . $this->config->get('config_language');

		foreach ($extra as $key => $value) {
			$args .= '&' . $key . '=' . urlencode((string)$value);
		}

		return $this->url->link($this->route, $args);
	}

	private function page(string $content): void {
		$this->document->setTitle($this->language->get('heading_title'));
		$this->response->addHeader('X-Robots-Tag: noindex');

		$data['content'] = $content;
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/withdrawal/module/withdrawal_layout', $data));
	}

	/**
	 * Step 1: the withdrawal form.
	 */
	public function index(): void {
		if (!$this->config->get('module_withdrawal_status')) {
			$this->response->redirect($this->url->link('error/not_found', 'language=' . $this->config->get('config_language')));

			return;
		}

		$this->load->language($this->route);

		$draft = $this->session->data['withdrawal_draft'] ?? [];

		$data['heading_title'] = $this->setting('link');
		$data['intro'] = $this->setting('intro');
		$data['action'] = $this->url->link($this->route . '.review', 'language=' . $this->config->get('config_language'));
		$data['errors'] = $this->session->data['withdrawal_errors'] ?? [];
		unset($this->session->data['withdrawal_errors']);

		$data['name'] = $draft['name'] ?? '';
		$data['email'] = $draft['email'] ?? '';
		$data['order'] = $draft['order'] ?? (string)($this->request->get['order_id'] ?? '');
		$data['items'] = $draft['items'] ?? '';
		$data['orders'] = [];

		// Logged-in customers get their details filled in and can pick an order.
		if ($this->customer->isLogged()) {
			if ($data['name'] === '') {
				$data['name'] = trim($this->customer->getFirstName() . ' ' . $this->customer->getLastName());
			}

			if ($data['email'] === '') {
				$data['email'] = $this->customer->getEmail();
			}

			$query = $this->db->query("SELECT `order_id`, `date_added`, `total`, `currency_code`, `currency_value` FROM `" . DB_PREFIX . "order` WHERE `customer_id` = '" . (int)$this->customer->getId() . "' AND `order_status_id` > '0' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "' ORDER BY `date_added` DESC LIMIT 20");

			foreach ($query->rows as $row) {
				$data['orders'][] = [
					'order_id' => $row['order_id'],
					'label'    => '#' . $row['order_id'] . ' · ' . date($this->language->get('date_format_short'), strtotime($row['date_added'])) . ' · ' . $this->currency->format($row['total'], $row['currency_code'], $row['currency_value'])
				];
			}
		}

		$this->page($this->load->view('extension/withdrawal/module/withdrawal_form', $data + $this->language->all()));
	}

	/**
	 * Step 2: validate and show the statement with the confirmation button.
	 */
	public function review(): void {
		$this->load->language($this->route);

		if (!$this->config->get('module_withdrawal_status') || $this->request->server['REQUEST_METHOD'] != 'POST') {
			$this->response->redirect($this->link());

			return;
		}

		$clean = fn($key, $max) => oc_substr(trim(html_entity_decode((string)($this->request->post[$key] ?? ''), ENT_QUOTES, 'UTF-8')), 0, $max);

		$draft = [
			'name'  => $clean('name', 128),
			'email' => $clean('email', 96),
			'order' => $clean('order', 64),
			'items' => $clean('items', 2000)
		];

		$errors = [];

		if ($draft['name'] === '') {
			$errors['name'] = $this->language->get('error_name');
		}

		if (!filter_var($draft['email'], FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = $this->language->get('error_email');
		}

		if ($draft['order'] === '') {
			$errors['order'] = $this->language->get('error_order');
		}

		$this->session->data['withdrawal_draft'] = $draft;

		if ($errors || !empty($this->request->post['website'])) {
			$this->session->data['withdrawal_errors'] = $errors;

			$this->response->redirect($this->link());

			return;
		}

		$data = $draft + $this->language->all();
		$data['heading_title'] = $this->language->get('text_review');
		$data['confirm_text'] = $this->setting('confirm');
		$data['action'] = $this->url->link($this->route . '.confirm', 'language=' . $this->config->get('config_language'));
		$data['back'] = $this->link();

		// A one-time key ties the confirmation to this reviewed statement.
		$this->session->data['withdrawal_key'] = bin2hex(random_bytes(16));
		$data['key'] = $this->session->data['withdrawal_key'];

		$this->page($this->load->view('extension/withdrawal/module/withdrawal_review', $data));
	}

	/**
	 * Step 3: the "confirm withdrawal" button was pressed.
	 */
	public function confirm(): void {
		$this->load->language($this->route);

		$draft = $this->session->data['withdrawal_draft'] ?? [];
		$key = (string)($this->request->post['key'] ?? '');

		if (!$this->config->get('module_withdrawal_status') || $this->request->server['REQUEST_METHOD'] != 'POST' || !$draft || empty($this->session->data['withdrawal_key']) || !hash_equals($this->session->data['withdrawal_key'], $key)) {
			$this->session->data['withdrawal_errors'] = ['warning' => $this->language->get('error_session')];

			$this->response->redirect($this->link());

			return;
		}

		$ip = (string)($this->request->server['REMOTE_ADDR'] ?? '');

		$recent = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "withdrawal` WHERE `ip` = '" . $this->db->escape($ip) . "' AND `date_added` > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->row;

		if ((int)$recent['total'] >= 10) {
			$this->session->data['withdrawal_errors'] = ['warning' => $this->language->get('error_limit')];

			$this->response->redirect($this->link());

			return;
		}

		unset($this->session->data['withdrawal_key'], $this->session->data['withdrawal_draft']);

		// Match the order when the number and the email (or the logged-in customer) belong together.
		$order_id = 0;
		$number = (int)preg_replace('/\D/', '', $draft['order']);

		if ($number) {
			$order = $this->db->query("SELECT `order_id`, `email`, `customer_id` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . $number . "' AND `order_status_id` > '0'")->row;

			if ($order && (oc_strtolower($order['email']) == oc_strtolower($draft['email']) || ($this->customer->isLogged() && (int)$order['customer_id'] == (int)$this->customer->getId()))) {
				$order_id = (int)$order['order_id'];
			}
		}

		$this->db->query("INSERT INTO `" . DB_PREFIX . "withdrawal` SET `store_id` = '" . (int)$this->config->get('config_store_id') . "', `customer_id` = '" . (int)$this->customer->getId() . "', `order_id` = '" . $order_id . "', `order_reference` = '" . $this->db->escape($draft['order']) . "', `name` = '" . $this->db->escape($draft['name']) . "', `email` = '" . $this->db->escape($draft['email']) . "', `items` = '" . $this->db->escape($draft['items']) . "', `ip` = '" . $this->db->escape($ip) . "', `date_added` = NOW()");

		$submitted = date('j M Y, H:i T');

		if ($order_id) {
			$history = sprintf($this->language->get('text_order_history'), $submitted, $draft['items'] !== '' ? $draft['items'] : '-');
			$status = (int)$this->config->get('module_withdrawal_order_status');

			if ($status) {
				$this->load->model('checkout/order');

				$this->model_checkout_order->addHistory($order_id, $status, $history, false);
			} else {
				// Keep the current status; only record the withdrawal in the order history.
				$this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` SET `order_id` = '" . $order_id . "', `order_status_id` = (SELECT `order_status_id` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . $order_id . "'), `notify` = '0', `comment` = '" . $this->db->escape($history) . "', `date_added` = NOW()");
			}
		}

		$this->sendMails($draft, $submitted);

		// Redirect after the POST, so reloading the page can't submit the withdrawal twice.
		$this->session->data['withdrawal_done'] = sprintf($this->language->get('text_done'), $submitted, $draft['email']);

		$this->response->redirect($this->url->link($this->route . '.done', 'language=' . $this->config->get('config_language')));
	}

	/**
	 * The page after a confirmed withdrawal.
	 */
	public function done(): void {
		$this->load->language($this->route);

		if (empty($this->session->data['withdrawal_done'])) {
			$this->response->redirect($this->link());

			return;
		}

		$data = $this->language->all();
		$data['heading_title'] = $this->language->get('text_done_title');
		$data['message'] = $this->session->data['withdrawal_done'];
		$data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

		unset($this->session->data['withdrawal_done']);

		$this->page($this->load->view('extension/withdrawal/module/withdrawal_done', $data));
	}

	private function sendMails(array $draft, string $submitted): void {
		if (!$this->config->get('config_mail_engine')) {
			return;
		}

		$store = html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

		$data = $this->language->all();
		$data['intro'] = sprintf($this->language->get('text_mail_intro'), $draft['name'], $submitted);
		$data['submitted'] = $submitted;
		$data['store'] = $store;
		$data += $draft;

		$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'), [
			'parameter'     => $this->config->get('config_mail_parameter'),
			'smtp_hostname' => $this->config->get('config_mail_smtp_hostname'),
			'smtp_username' => $this->config->get('config_mail_smtp_username'),
			'smtp_password' => html_entity_decode((string)$this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
			'smtp_port'     => $this->config->get('config_mail_smtp_port'),
			'smtp_timeout'  => $this->config->get('config_mail_smtp_timeout')
		]);

		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender($store);

		try {
			// The acknowledgement on a durable medium, with the content and the date and time of submission.
			$mail->setTo($draft['email']);
			$mail->setSubject(sprintf($this->language->get('text_mail_subject'), $draft['order']));
			$mail->setHtml($this->load->view('extension/withdrawal/module/withdrawal_mail', $data));
			$mail->send();

			if ($this->config->get('module_withdrawal_alert')) {
				$mail->setTo($this->config->get('config_email'));
				$mail->setReplyTo($draft['email']);
				$mail->setSubject(sprintf($this->language->get('text_alert_subject'), $draft['order']));
				$mail->send();
			}
		} catch (\Exception $e) {
			$this->log->write('EU Withdrawal Button: ' . $e->getMessage());
		}
	}

	/**
	 * Event: catalog/view/common/footer/after. The link on every page.
	 */
	public function footer(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_withdrawal_status') || !$this->config->get('module_withdrawal_footer')) {
			return;
		}

		$html = '<div class="withdrawal-link text-center py-3"><a href="' . $this->link() . '" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-rotate-left"></i> ' . htmlspecialchars($this->setting('link'), ENT_QUOTES, 'UTF-8') . '</a></div>';

		if (($position = strripos($output, '</footer>')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		} else {
			$output = $html . $output;
		}
	}

	/**
	 * Event: catalog/view/account/order_info/after. The link on the customer's order page, with the order filled in.
	 */
	public function orderInfo(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_withdrawal_status') || !$this->config->get('module_withdrawal_account') || empty($args['order_id'])) {
			return;
		}

		$this->load->language($this->route, 'wd');

		$html = '<div class="card mb-3"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2"><span class="text-body-secondary">' . $this->language->get('wd_text_account_note') . '</span><a href="' . $this->link(['order_id' => (int)$args['order_id']]) . '" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i> ' . htmlspecialchars($this->setting('link'), ENT_QUOTES, 'UTF-8') . '</a></div></div>';

		if (($history = strpos($output, '<div id="history">')) !== false && ($heading = strrpos(substr($output, 0, $history), '<h2>')) !== false) {
			$output = substr_replace($output, $html, $heading, 0);
		}
	}

	/**
	 * Event: catalog/view/mail/order_add/after. Tells customers where the withdrawal function is.
	 */
	public function mail(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_withdrawal_status') || !$this->config->get('module_withdrawal_mail')) {
			return;
		}

		$this->load->language($this->route, 'wd');

		$url = html_entity_decode($this->link(), ENT_QUOTES, 'UTF-8');
		$html = '<p style="margin-top:20px;font-size:12px;">' . sprintf(htmlspecialchars($this->language->get('wd_text_mail_note'), ENT_QUOTES, 'UTF-8'), '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($this->setting('link'), ENT_QUOTES, 'UTF-8') . '</a>') . '</p>';

		if (($position = strripos($output, '</body>')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		} else {
			$output .= $html;
		}
	}
}
