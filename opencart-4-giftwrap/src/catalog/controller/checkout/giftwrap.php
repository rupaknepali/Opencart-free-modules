<?php
namespace Opencart\Catalog\Controller\Extension\Giftwrap\Checkout;
/**
 * Gift wrap option and gift message: on the cart page (with the coupon and voucher boxes) and on the checkout page.
 */
class Giftwrap extends \Opencart\System\Engine\Controller {
	/**
	 * The cart page box (cart pages load each order total's checkout controller).
	 */
	public function index(): string {
		if (!$this->config->get('total_giftwrap_status') || !$this->cart->hasShipping()) {
			return '';
		}

		$data = $this->data();
		$data['list'] = $this->url->link('checkout/cart.list', 'language=' . $this->config->get('config_language'), true);

		return $this->load->view('extension/giftwrap/checkout/giftwrap', $data);
	}

	private function data(): array {
		$this->load->language('extension/giftwrap/checkout/giftwrap');

		$price = (float)$this->config->get('total_giftwrap_price');

		if ($this->config->get('total_giftwrap_tax_class_id')) {
			$price = $this->tax->calculate($price, (int)$this->config->get('total_giftwrap_tax_class_id'), $this->config->get('config_tax'));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['entry_giftwrap'] = sprintf($this->language->get('entry_giftwrap'), $this->currency->format($price, $this->session->data['currency']));
		$data['entry_message'] = $this->language->get('entry_message');
		$data['help_message'] = sprintf($this->language->get('help_message'), $this->maxLength());
		$data['button_save'] = $this->language->get('button_save');
		$data['description'] = nl2br(htmlspecialchars((string)($this->config->get('total_giftwrap_description')[(int)$this->config->get('config_language_id')] ?? '')));
		$data['message_status'] = (bool)$this->config->get('total_giftwrap_message');
		$data['max_length'] = $this->maxLength();
		$data['giftwrap'] = !empty($this->session->data['giftwrap']);
		$data['message'] = (string)($this->session->data['giftwrap_message'] ?? '');
		$data['save'] = $this->url->link('extension/giftwrap/checkout/giftwrap.save', 'language=' . $this->config->get('config_language'), true);
		$data['language'] = $this->config->get('config_language');

		return $data;
	}

	private function maxLength(): int {
		return max(20, min(1000, (int)$this->config->get('total_giftwrap_length') ?: 250));
	}

	public function save(): void {
		$this->load->language('extension/giftwrap/checkout/giftwrap');

		$json = [];

		if (!$this->config->get('total_giftwrap_status')) {
			$json['error'] = $this->language->get('error_status');
		} elseif (!$this->cart->hasProducts()) {
			$json['error'] = $this->language->get('error_cart');
		}

		// The request arrives HTML-encoded; keep the plain text and escape it where it's shown.
		$message = trim(html_entity_decode((string)($this->request->post['giftwrap_message'] ?? ''), ENT_QUOTES, 'UTF-8'));

		if (!$json && oc_strlen($message) > $this->maxLength()) {
			$json['error'] = sprintf($this->language->get('error_message'), $this->maxLength());
		}

		if (!$json) {
			if (!empty($this->request->post['giftwrap'])) {
				$this->session->data['giftwrap'] = 1;
				$this->session->data['giftwrap_message'] = $this->config->get('total_giftwrap_message') ? $message : '';

				$json['success'] = $this->language->get('text_success');
			} else {
				unset($this->session->data['giftwrap'], $this->session->data['giftwrap_message']);

				$json['success'] = $this->language->get('text_removed');
			}

		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * catalog/view/checkout/checkout/after: the same box above the order confirmation on the checkout page.
	 */
	public function checkout(string &$route, array &$args, mixed &$output): void {
		if (!$this->config->get('total_giftwrap_status') || !$this->cart->hasShipping() || ($pos = strpos($output, '<div id="checkout-confirm">')) === false) {
			return;
		}

		$output = substr_replace($output, $this->load->view('extension/giftwrap/checkout/giftwrap_checkout', $this->data()), $pos, 0);
	}
}
