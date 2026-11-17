<?php
namespace Opencart\Catalog\Controller\Extension\Requestquote\Module;
/**
 * Request a Quote: receives the form.
 */
class Requestquote extends \Opencart\System\Engine\Controller {
	public function send(): void {
		$this->load->language('extension/requestquote/module/requestquote');

		$json = [];
		$post = $this->request->post;

		if (!$this->config->get('module_requestquote_status') || $this->request->server['REQUEST_METHOD'] != 'POST') {
			$json['error']['warning'] = $this->language->get('rq_error_closed');
		}

		// Bots fill in the hidden field; people never see it.
		if (!empty($post['website'])) {
			$json['error']['warning'] = $this->language->get('rq_error_closed');
		}

		// One request a minute per visitor.
		if (!empty($this->session->data['requestquote_time']) && time() - $this->session->data['requestquote_time'] < 60) {
			$json['error']['warning'] = $this->language->get('rq_error_wait');
		}

		$this->load->model('catalog/product');

		$product = $this->model_catalog_product->getProduct((int)($post['product_id'] ?? 0));

		if (!$product) {
			$json['error']['warning'] = $this->language->get('rq_error_closed');
		}

		$name = trim((string)($post['name'] ?? ''));
		$email = trim((string)($post['email'] ?? ''));
		$telephone = trim((string)($post['telephone'] ?? ''));
		$company = trim((string)($post['company'] ?? ''));
		$quantity = (int)($post['quantity'] ?? 0);
		$message = trim((string)($post['message'] ?? ''));

		if (oc_strlen($name) < 2 || oc_strlen($name) > 64) {
			$json['error']['name'] = $this->language->get('rq_error_name');
		}

		if (!filter_var(html_entity_decode($email, ENT_QUOTES, 'UTF-8'), FILTER_VALIDATE_EMAIL) || oc_strlen($email) > 96) {
			$json['error']['email'] = $this->language->get('rq_error_email');
		}

		if (oc_strlen($telephone) > 32 || oc_strlen($company) > 64) {
			$json['error']['warning'] = $this->language->get('rq_error_long');
		}

		if ($quantity < 1 || $quantity > 1000000) {
			$json['error']['quantity'] = $this->language->get('rq_error_quantity');
		}

		if (oc_strlen($message) > 2000) {
			$json['error']['message'] = $this->language->get('rq_error_message');
		}

		if (!$json) {
			$this->load->model('extension/requestquote/module/requestquote');

			$id = $this->model_extension_requestquote_module_requestquote->addRequest([
				'product_id' => (int)$product['product_id'],
				'name'       => $name,
				'email'      => $email,
				'telephone'  => $telephone,
				'company'    => $company,
				'quantity'   => $quantity,
				'message'    => $message
			]);

			$this->session->data['requestquote_time'] = time();

			if ($this->config->get('module_requestquote_email')) {
				$this->notify($id, $product, compact('name', 'email', 'telephone', 'company', 'quantity', 'message'));
			}

			$json['success'] = $this->language->get('rq_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Emails the request to the store, with Reply-To set to the customer.
	 * A mail failure must never lose the request: it's already saved, so errors are only logged.
	 */
	private function notify(int $id, array $product, array $request): void {
		if (!$this->config->get('config_mail_engine')) {
			return;
		}

		try {
			$mail_option = [
				'parameter'     => $this->config->get('config_mail_parameter'),
				'smtp_hostname' => $this->config->get('config_mail_smtp_hostname'),
				'smtp_username' => $this->config->get('config_mail_smtp_username'),
				'smtp_password' => html_entity_decode((string)$this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
				'smtp_port'     => $this->config->get('config_mail_smtp_port'),
				'smtp_timeout'  => $this->config->get('config_mail_smtp_timeout')
			];

			$store = html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
			$product_name = html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8');

			$text = sprintf($this->language->get('rq_mail_intro'), $product_name) . "\n\n";

			foreach (['name', 'email', 'telephone', 'company', 'quantity', 'message'] as $key) {
				$text .= $this->language->get('rq_entry_' . $key) . ': ' . html_entity_decode((string)$request[$key], ENT_QUOTES, 'UTF-8') . "\n";
			}

			$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'), $mail_option);
			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender($store);
			$mail->setReplyTo(html_entity_decode($request['email'], ENT_QUOTES, 'UTF-8'));
			$mail->setSubject(sprintf($this->language->get('rq_mail_subject'), $id, $product_name));
			$mail->setText($text);
			$mail->send();
		} catch (\Throwable $e) {
			$this->log->write('Request a Quote: email for request #' . $id . ' failed: ' . $e->getMessage());
		}
	}
}
