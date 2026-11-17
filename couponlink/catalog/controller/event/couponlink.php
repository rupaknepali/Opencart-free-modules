<?php
namespace Opencart\Catalog\Controller\Extension\Couponlink\Event;
/**
 * Coupon Links: ?coupon=CODE on any store link saves the coupon for checkout.
 */
class Couponlink extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/common/header/after
	 */
	public function header(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_couponlink_status')) {
			return;
		}

		$param = $this->config->get('module_couponlink_param') ?: 'coupon';

		if (isset($this->request->get[$param]) && is_string($this->request->get[$param])) {
			$this->apply(oc_substr(trim(html_entity_decode($this->request->get[$param], ENT_QUOTES, 'UTF-8')), 0, 64));
		}

		if (empty($this->session->data['couponlink_notice'])) {
			return;
		}

		// Show the result once, then forget it.
		$notice = $this->session->data['couponlink_notice'];
		unset($this->session->data['couponlink_notice']);

		$html = $this->load->view('extension/couponlink/module/couponlink', $notice);

		if (preg_match('/<body[^>]*>/i', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1] + strlen($match[0][0]), 0);
		}
	}

	/**
	 * Saves the coupon if it exists, is enabled and is within its dates.
	 * Cart-dependent rules (minimum total, products, uses) are left to OpenCart's coupon total at checkout,
	 * because the cart is usually empty when someone clicks a link.
	 */
	private function apply(string $code): void {
		$this->load->language('extension/couponlink/module/couponlink');

		$valid = false;

		if ($code !== '' && $this->config->get('total_coupon_status')) {
			$this->load->model('marketing/coupon');

			// getCouponByCode() only returns enabled coupons.
			$coupon = $this->model_marketing_coupon->getCouponByCode($code);

			$now = time();

			$valid = $coupon
				&& ($coupon['date_start'] == '0000-00-00' || strtotime($coupon['date_start']) < $now)
				&& ($coupon['date_end'] == '0000-00-00' || strtotime($coupon['date_end']) > $now);
		}

		$safe = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

		if ($valid) {
			// The same session key OpenCart's own coupon box uses, so checkout applies it as normal.
			$this->session->data['coupon'] = $coupon['code'];

			// Payment methods can depend on the total; let checkout recalculate them, as OpenCart does.
			unset($this->session->data['payment_method'], $this->session->data['payment_methods']);

			// Settings are stored HTML-encoded by OpenCart: decode, then escape once.
			$text = html_entity_decode((string)($this->config->get('module_couponlink_message') ?: $this->language->get('cl_success')), ENT_QUOTES, 'UTF-8');

			$message = sprintf(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), '<strong>' . $safe . '</strong>');

			if ((float)$coupon['total'] > 0) {
				$message .= ' ' . sprintf($this->language->get('cl_minimum'), $this->currency->format((float)$coupon['total'], $this->session->data['currency']));
			}

			$this->session->data['couponlink_notice'] = ['type' => 'success', 'message' => $message];
		} else {
			$this->session->data['couponlink_notice'] = ['type' => 'warning', 'message' => sprintf($this->language->get('cl_invalid'), '<strong>' . $safe . '</strong>')];
		}
	}
}
