<?php
namespace Opencart\Catalog\Controller\Extension\Ordercancel\Module;
/**
 * Customer Order Cancellation: a "Cancel order" button on the customer's order page while the order
 * is in an allowed status and inside the time limit.
 */
class Ordercancel extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/ordercancel/module/ordercancel';

	private function reasons(): array {
		$lines = preg_split('/\r\n|\r|\n/', html_entity_decode((string)$this->config->get('module_ordercancel_reasons'), ENT_QUOTES, 'UTF-8'));

		return array_values(array_filter(array_map('trim', $lines), 'strlen'));
	}

	/**
	 * Event: catalog/view/account/order_info/after
	 */
	public function orderInfo(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_ordercancel_status') || !$this->customer->isLogged()) {
			return;
		}

		$order_id = (int)($args['order_id'] ?? 0);

		$this->load->model('account/order');
		$this->load->model($this->route);

		$order_info = $this->model_account_order->getOrder($order_id);

		if (!$order_info) {
			return;
		}

		$this->load->language($this->route);

		$data['order_id'] = $order_id;
		$cancellation = $this->model_extension_ordercancel_module_ordercancel->getCancellation($order_id);

		if ($cancellation) {
			$data['cancelled'] = sprintf($this->language->get('text_cancelled'), date($this->language->get('date_format_short'), strtotime($cancellation['date_added'])));
		} else {
			$deadline = $this->model_extension_ordercancel_module_ordercancel->getDeadline($order_info);

			if ($deadline < 0) {
				return;
			}

			$data['cancelled'] = '';
			$data['text_can_cancel'] = $deadline ? sprintf($this->language->get('text_can_cancel'), date($this->language->get('datetime_format'), $deadline)) : $this->language->get('text_can_cancel_no_limit');
			$data['text_confirm'] = sprintf($this->language->get('text_confirm'), $order_id);
			$data['reasons'] = $this->reasons();
			$data['reason_required'] = (bool)$this->config->get('module_ordercancel_reason_required');
			$data['action'] = $this->url->link($this->route . '.cancel', 'language=' . $this->config->get('config_language') . '&customer_token=' . ($this->session->data['customer_token'] ?? '') . '&order_id=' . $order_id);
		}

		$html = $this->load->view($this->route, $data);

		// Above the order history heading.
		if (($history = strpos($output, '<div id="history">')) !== false && ($heading = strrpos(substr($output, 0, $history), '<h2>')) !== false) {
			$output = substr_replace($output, $html, $heading, 0);
		}
	}

	/**
	 * POST from the cancel form. Returns JSON.
	 */
	public function cancel(): void {
		$this->load->language($this->route);

		$json = [];

		$order_id = (int)($this->request->get['order_id'] ?? 0);

		// The customer token in the URL guards against cross-site requests, like the core account pages.
		if (!$this->customer->isLogged() || empty($this->session->data['customer_token']) || !isset($this->request->get['customer_token']) || !hash_equals($this->session->data['customer_token'], (string)$this->request->get['customer_token'])) {
			$json['error']['warning'] = $this->language->get('error_login');
		}

		if (!$json && $this->request->server['REQUEST_METHOD'] != 'POST') {
			$json['error']['warning'] = $this->language->get('error_order');
		}

		$order_info = [];

		if (!$json) {
			$this->load->model('account/order');
			$this->load->model($this->route);

			// getOrder() only returns orders of the logged-in customer.
			$order_info = $this->model_account_order->getOrder($order_id);

			if (!$order_info || $this->model_extension_ordercancel_module_ordercancel->getCancellation($order_id) || $this->model_extension_ordercancel_module_ordercancel->getDeadline($order_info) < 0) {
				$json['error']['warning'] = $this->language->get('error_order');
			}
		}

		$reason = '';
		$reasons = $this->reasons();

		if (!$json && $reasons) {
			$index = (string)($this->request->post['reason'] ?? '');

			if ($index !== '' && ctype_digit($index) && isset($reasons[(int)$index])) {
				$reason = $reasons[(int)$index];
			} elseif ($this->config->get('module_ordercancel_reason_required')) {
				$json['error']['reason'] = $this->language->get('error_reason');
			}
		}

		if (!$json) {
			// Request data arrives HTML-encoded; store plain text and escape on output.
			$comment = oc_substr(trim(html_entity_decode((string)($this->request->post['comment'] ?? ''), ENT_QUOTES, 'UTF-8')), 0, 1000);

			$this->model_extension_ordercancel_module_ordercancel->addCancellation($order_id, (int)$this->customer->getId(), (int)$order_info['order_status_id'], $reason, $comment);

			$history = sprintf($this->language->get('text_history'), $reason !== '' ? $reason : '-');

			if ($comment !== '') {
				$history .= "\n" . $comment;
			}

			// The core history call sends the customer the order status email and restocks when the order leaves a processing status.
			$this->load->model('checkout/order');

			$this->model_checkout_order->addHistory($order_id, (int)$this->config->get('module_ordercancel_cancel_status'), $history, true);

			if ($this->config->get('module_ordercancel_notify')) {
				$this->alert($order_info, $reason, $comment);
			}

			$json['success'] = sprintf($this->language->get('text_success'), $order_id);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function alert(array $order_info, string $reason, string $comment): void {
		if (!$this->config->get('config_mail_engine')) {
			return;
		}

		$name = trim($order_info['firstname'] . ' ' . $order_info['lastname']);

		$data['subject'] = sprintf($this->language->get('text_mail_subject'), $order_info['order_id']);
		$data['intro'] = sprintf($this->language->get('text_mail_intro'), $name, $order_info['order_id'], $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']));
		$data['reason'] = $reason;
		$data['comment'] = $comment;
		$data['entry_reason'] = $this->language->get('entry_reason');
		$data['entry_comment'] = $this->language->get('text_mail_comment');
		$data['text_mail_refund'] = $this->language->get('text_mail_refund');
		$data['store'] = html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

		$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'), [
			'parameter'     => $this->config->get('config_mail_parameter'),
			'smtp_hostname' => $this->config->get('config_mail_smtp_hostname'),
			'smtp_username' => $this->config->get('config_mail_smtp_username'),
			'smtp_password' => html_entity_decode((string)$this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
			'smtp_port'     => $this->config->get('config_mail_smtp_port'),
			'smtp_timeout'  => $this->config->get('config_mail_smtp_timeout')
		]);

		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender($data['store']);
		$mail->setSubject($data['subject']);
		$mail->setHtml($this->load->view('extension/ordercancel/module/ordercancel_mail', $data));

		$emails = array_merge([(string)$this->config->get('config_email')], explode(',', (string)$this->config->get('config_mail_alert_email')));

		foreach (array_unique(array_filter(array_map('trim', $emails))) as $email) {
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$mail->setTo($email);

				try {
					$mail->send();
				} catch (\Exception $e) {
					// A mail failure must not undo the cancellation the customer already sees.
					$this->log->write('Customer Order Cancellation: ' . $e->getMessage());
				}
			}
		}
	}
}
