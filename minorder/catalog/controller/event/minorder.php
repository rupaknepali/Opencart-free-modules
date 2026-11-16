<?php
namespace Opencart\Catalog\Controller\Extension\Minorder\Event;
/**
 * Minimum Order Amount: shows what's missing in the cart and keeps checkout closed until the minimum is met.
 */
class Minorder extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/view/checkout/cart_list/after
	 * The cart list is also reloaded by AJAX after quantity changes, so the notice stays current.
	 */
	public function cart(string &$route, array &$args, string &$output): void {
		$shortfall = $this->shortfall();

		if (!$shortfall) {
			return;
		}

		$this->load->language('extension/minorder/module/minorder');

		$html = $this->load->view('extension/minorder/module/minorder', $shortfall);

		// The notice goes first; the Checkout button becomes a disabled one.
		$output = $html . $output;
		$output = preg_replace('/<a href="[^"]*checkout\/checkout[^"]*" class="btn btn-primary">([^<]*)<\/a>/', '<button type="button" class="btn btn-primary" disabled>$1</button>', $output);
	}

	/**
	 * Trigger: catalog/controller/checkout/checkout/before
	 * Someone going straight to the checkout URL is sent back to the cart, where the notice explains why.
	 */
	public function checkout(string &$route, array &$args): void {
		if ($this->shortfall()) {
			$this->response->redirect($this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true));
		}
	}

	/**
	 * What's missing for the current cart, or [] if it meets the minimum (or there's no rule).
	 */
	private function shortfall(): array {
		if (!$this->config->get('module_minorder_status') || !$this->cart->hasProducts()) {
			return [];
		}

		$rules = (array)$this->config->get('module_minorder_rule');
		$rule = $rules[(int)$this->config->get('config_customer_group_id')] ?? [];

		$min_amount = (float)($rule['amount'] ?? 0);
		$min_quantity = (int)($rule['quantity'] ?? 0);

		if ($min_amount <= 0 && $min_quantity <= 0) {
			return [];
		}

		// Amounts are in the default currency, like OpenCart's prices.
		$amount = $this->config->get('module_minorder_basis') == 'total' ? $this->cart->getTotal() : $this->cart->getSubTotal();
		$quantity = $this->cart->countProducts();

		$this->load->language('extension/minorder/module/minorder');

		$currency = $this->session->data['currency'];
		$messages = [];

		if ($min_amount > 0 && $amount < $min_amount) {
			$messages[] = sprintf($this->language->get('mo_amount'), $this->currency->format($min_amount - $amount, $currency), $this->currency->format($min_amount, $currency));
		}

		if ($min_quantity > 0 && $quantity < $min_quantity) {
			$messages[] = sprintf($this->language->get('mo_quantity'), $min_quantity - $quantity, $min_quantity);
		}

		if (!$messages) {
			return [];
		}

		return [
			'messages' => $messages,
			'percent'  => $min_amount > 0 ? min(100, (int)floor($amount / $min_amount * 100)) : min(100, (int)floor($quantity / max(1, $min_quantity) * 100))
		];
	}
}
