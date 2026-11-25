<?php
namespace Opencart\Catalog\Controller\Extension\Newsletterpopup\Module;
/**
 * Newsletter Popup: a delayed or exit-intent sign-up popup with a consent box, optional double
 * opt-in and a coupon code shown after sign-up.
 */
class Newsletterpopup extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/newsletterpopup/module/newsletterpopup';

	private function setting(string $key): string {
		return html_entity_decode((string)$this->config->get('module_newsletterpopup_' . $key), ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Event: catalog/view/common/footer/after
	 */
	public function footer(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_newsletterpopup_status')) {
			return;
		}

		if ($this->config->get('module_newsletterpopup_guests') && $this->customer->isLogged()) {
			return;
		}

		// Never interrupt checkout or account pages.
		$page = (string)($this->request->get['route'] ?? 'common/home');

		if (preg_match('#^(checkout/|account/|extension/newsletterpopup/)#', $page)) {
			return;
		}

		$this->load->language($this->route, 'np');

		$data = [
			'title'   => $this->setting('title'),
			'text'    => $this->setting('text'),
			'button'  => $this->setting('button'),
			'consent' => $this->setting('consent'),
			'action'  => $this->url->link($this->route . '.subscribe', 'language=' . $this->config->get('config_language')),
			'delay'   => (int)$this->config->get('module_newsletterpopup_delay'),
			'exit'    => (bool)$this->config->get('module_newsletterpopup_exit'),
			'days'    => (int)$this->config->get('module_newsletterpopup_days'),
			'text_close' => $this->language->get('np_text_close'),
			'text_email' => $this->language->get('np_text_email')
		];

		$html = $this->load->view($this->route, $data);

		if (($position = strripos($output, '</body>')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		} else {
			$output .= $html;
		}
	}

	/**
	 * POST from the popup. Returns JSON.
	 */
	public function subscribe(): void {
		$this->load->language($this->route);

		$json = [];

		$email = trim(html_entity_decode((string)($this->request->post['email'] ?? ''), ENT_QUOTES, 'UTF-8'));
		$ip = (string)($this->request->server['REMOTE_ADDR'] ?? '');
		$store_id = (int)$this->config->get('config_store_id');

		if (!$this->config->get('module_newsletterpopup_status') || $this->request->server['REQUEST_METHOD'] != 'POST') {
			$json['error']['email'] = $this->language->get('error_email');
		}

		// Honeypot: a field people never see. Bots fill it; pretend it worked.
		if (!$json && !empty($this->request->post['website'])) {
			$json['success'] = $this->setting('success');

			$this->respond($json);

			return;
		}

		if (!$json && (oc_strlen($email) > 96 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
			$json['error']['email'] = $this->language->get('error_email');
		}

		if (!$json && $this->setting('consent') !== '' && empty($this->request->post['consent'])) {
			$json['error']['consent'] = $this->language->get('error_consent');
		}

		if (!$json) {
			$recent = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "newsletterpopup` WHERE `ip` = '" . $this->db->escape($ip) . "' AND `date_added` > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->row;

			if ((int)$recent['total'] >= 5) {
				$json['error']['email'] = $this->language->get('error_limit');
			}
		}

		if (!$json) {
			$email = oc_strtolower($email);
			$double = (bool)$this->config->get('module_newsletterpopup_double_optin');

			$existing = $this->db->query("SELECT * FROM `" . DB_PREFIX . "newsletterpopup` WHERE `store_id` = '" . $store_id . "' AND `email` = '" . $this->db->escape($email) . "'")->row;
			$customer = $this->db->query("SELECT `customer_id` FROM `" . DB_PREFIX . "customer` WHERE LCASE(`email`) = '" . $this->db->escape($email) . "'")->row;

			if ($existing && $existing['confirmed']) {
				// Already on the list: same answer as a new sign-up, so the form doesn't reveal who subscribed.
				$json['success'] = $double ? $this->setting('pending') : $this->setting('success');

				if (!$double) {
					$json['coupon'] = $this->setting('coupon');
				}
			} else {
				$token = bin2hex(random_bytes(20));

				if ($existing) {
					$this->db->query("UPDATE `" . DB_PREFIX . "newsletterpopup` SET `token` = '" . $token . "', `consent` = '" . $this->db->escape(oc_substr($this->setting('consent'), 0, 255)) . "', `ip` = '" . $this->db->escape($ip) . "', `date_added` = NOW() WHERE `newsletterpopup_id` = '" . (int)$existing['newsletterpopup_id'] . "'");
				} else {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "newsletterpopup` SET `store_id` = '" . $store_id . "', `email` = '" . $this->db->escape($email) . "', `customer_id` = '" . (int)($customer['customer_id'] ?? 0) . "', `confirmed` = '" . ($double ? 0 : 1) . "', `token` = '" . ($double ? $token : '') . "', `consent` = '" . $this->db->escape(oc_substr($this->setting('consent'), 0, 255)) . "', `ip` = '" . $this->db->escape($ip) . "', `date_added` = NOW(), `date_confirmed` = " . ($double ? "NULL" : "NOW()"));
				}

				if ($double) {
					$this->sendConfirmation($email, $token);

					$json['success'] = $this->setting('pending');
				} else {
					$this->markCustomer((int)($customer['customer_id'] ?? 0));

					$json['success'] = $this->setting('success');
					$json['coupon'] = $this->setting('coupon');
				}
			}
		}

		$this->respond($json);
	}

	/**
	 * The link in the double opt-in email.
	 */
	public function confirm(): void {
		$this->load->language($this->route);

		$token = (string)($this->request->get['token'] ?? '');
		$row = [];

		if (preg_match('/^[a-f0-9]{40}$/', $token)) {
			$row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "newsletterpopup` WHERE `token` = '" . $this->db->escape($token) . "' AND `confirmed` = '0'")->row;
		}

		$data['coupon'] = '';

		if ($row) {
			$this->db->query("UPDATE `" . DB_PREFIX . "newsletterpopup` SET `confirmed` = '1', `token` = '', `date_confirmed` = NOW() WHERE `newsletterpopup_id` = '" . (int)$row['newsletterpopup_id'] . "'");

			$this->markCustomer((int)$row['customer_id']);

			$data['message'] = $this->language->get('text_confirmed') . ' ' . $this->setting('success');
			$data['coupon'] = $this->setting('coupon');
		} else {
			$data['message'] = $this->language->get('text_invalid');
		}

		$this->document->setTitle($this->language->get('text_confirm_title'));
		// OpenCart 4.1's Document class has no robots setter, so send the header instead.
		$this->response->addHeader('X-Robots-Tag: noindex, nofollow');

		$data['heading_title'] = $this->language->get('text_confirm_title');
		$data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/newsletterpopup/module/newsletterpopup_confirm', $data));
	}

	/**
	 * A matching customer account gets the core newsletter flag too, so Account > Newsletter shows Yes.
	 */
	private function markCustomer(int $customer_id): void {
		if ($customer_id) {
			$this->db->query("UPDATE `" . DB_PREFIX . "customer` SET `newsletter` = '1' WHERE `customer_id` = '" . $customer_id . "'");
		}
	}

	private function sendConfirmation(string $email, string $token): void {
		if (!$this->config->get('config_mail_engine')) {
			return;
		}

		$store = html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

		$data = [
			'intro'  => sprintf($this->language->get('text_mail_intro'), $store),
			'button' => $this->language->get('text_mail_button'),
			'ignore' => $this->language->get('text_mail_ignore'),
			'link'   => html_entity_decode($this->url->link($this->route . '.confirm', 'language=' . $this->config->get('config_language') . '&token=' . $token), ENT_QUOTES, 'UTF-8'),
			'store'  => $store
		];

		$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'), [
			'parameter'     => $this->config->get('config_mail_parameter'),
			'smtp_hostname' => $this->config->get('config_mail_smtp_hostname'),
			'smtp_username' => $this->config->get('config_mail_smtp_username'),
			'smtp_password' => html_entity_decode((string)$this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
			'smtp_port'     => $this->config->get('config_mail_smtp_port'),
			'smtp_timeout'  => $this->config->get('config_mail_smtp_timeout')
		]);

		$mail->setTo($email);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender($store);
		$mail->setSubject(sprintf($this->language->get('text_mail_subject'), $store));
		$mail->setHtml($this->load->view('extension/newsletterpopup/module/newsletterpopup_mail', $data));

		try {
			$mail->send();
		} catch (\Exception $e) {
			$this->log->write('Newsletter Popup: ' . $e->getMessage());
		}
	}

	private function respond(array $json): void {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
