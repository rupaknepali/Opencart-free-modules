<?php
namespace Opencart\Catalog\Controller\Extension\Deliveryslot\Module;
/**
 * Delivery Date and Time Slots: a day and slot picker in checkout, saved with the order.
 */
class Deliveryslot extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/deliveryslot/module/deliveryslot';

	private function model(): object {
		$this->load->model($this->route);

		return $this->model_extension_deliveryslot_module_deliveryslot;
	}

	/**
	 * "2026-12-03" to "Thu 3 Dec 2026" using the language file, so day and month names can be translated.
	 */
	private function formatDate(string $date): string {
		$this->load->language($this->route, 'ds');

		$time = strtotime($date);
		$weekdays = explode(',', $this->language->get('ds_text_weekdays'));
		$months = explode(',', $this->language->get('ds_text_months'));

		return sprintf($this->language->get('ds_text_date_format'), $weekdays[(int)date('w', $time)] ?? '', date('j', $time), $months[(int)date('n', $time) - 1] ?? '', date('Y', $time));
	}

	/**
	 * The customer's choice from the session, if it is still available.
	 */
	private function selected(): array {
		$selected = $this->session->data['deliveryslot'] ?? [];

		if (!$selected || !$this->model()->isAvailable((string)$selected['date'], (string)$selected['slot'])) {
			unset($this->session->data['deliveryslot']);

			return [];
		}

		return $selected;
	}

	/**
	 * Event: catalog/view/checkout/shipping_method/after. Adds the picker under the shipping method.
	 */
	public function shippingMethod(string &$route, array &$args, string &$output): void {
		$settings = $this->model()->getSettings();

		if (!$settings['status'] || !$this->cart->hasShipping()) {
			return;
		}

		$text = $this->load->language($this->route, 'ds');

		$dates = [];

		foreach ($this->model()->getAvailability() as $date => $slots) {
			$dates[] = [
				'date'  => $date,
				'label' => $this->formatDate($date),
				'slots' => array_map(fn($slot) => [
					'label' => $slot['label'],
					'left'  => $slot['left'] !== null && $slot['left'] <= 3 ? sprintf($text['ds_text_left'], $slot['left']) : ''
				], $slots)
			];
		}

		$selected = $this->selected();

		$data = [
			'dates'         => $dates,
			'dates_json'    => json_encode($dates),
			'selected_date' => $selected['date'] ?? '',
			'selected_slot' => $selected['slot'] ?? '',
			'required'      => (int)$settings['required'],
			'save'          => html_entity_decode($this->url->link($this->route . '.save', 'language=' . $this->config->get('config_language')), ENT_QUOTES, 'UTF-8'),
			'confirm'       => html_entity_decode($this->url->link('checkout/confirm.confirm', 'language=' . $this->config->get('config_language')), ENT_QUOTES, 'UTF-8'),
			'text_title'    => $text['ds_text_title'],
			'text_choose'   => $text['ds_text_choose'],
			'text_date'     => $text['ds_text_date'],
			'text_slot'     => $text['ds_text_slot'],
			'text_none'     => $text['ds_text_none'],
			'text_select'   => $this->language->get('text_select')
		];

		$output .= $this->load->view('extension/deliveryslot/module/deliveryslot_picker', $data);
	}

	/**
	 * The order checkout has already created for this session and not yet confirmed (status 0).
	 */
	private function openOrder(): array {
		if (empty($this->session->data['order_id'])) {
			return [];
		}

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder((int)$this->session->data['order_id']);

		return $order_info && !$order_info['order_status_id'] ? $order_info : [];
	}

	/**
	 * POST date, slot. Saves the choice in the session (and on the order if checkout already created it).
	 */
	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		$date = (string)($this->request->post['date'] ?? '');
		$slot = (string)($this->request->post['slot'] ?? '');

		// An empty slot clears the choice (the customer picked another day).
		if ($slot === '' && $this->model()->getSettings()['status']) {
			unset($this->session->data['deliveryslot']);

			$order_info = $this->openOrder();

			if ($order_info) {
				$this->model()->deleteForOrder((int)$order_info['order_id']);
			}

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode(['cleared' => true]));

			return;
		}

		if (!$this->model()->getSettings()['status'] || !$this->cart->hasShipping() || !$this->model()->isAvailable($date, $slot)) {
			$json['error'] = $this->language->get('error_slot');
		}

		if (!$json) {
			$this->session->data['deliveryslot'] = ['date' => $date, 'slot' => $slot];

			// Checkout creates the order (status 0) as soon as the confirm step loads; keep it in step.
			$order_info = $this->openOrder();

			if ($order_info) {
				$this->model()->saveForOrder((int)$order_info['order_id'], $date, $slot);
			}

			$json['success'] = sprintf($this->language->get('text_delivery'), $this->formatDate($date), $slot);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Event: catalog/view/checkout/confirm/before. Shows the chosen slot above the payment button,
	 * or replaces the payment button with a reminder while a required slot is missing.
	 */
	public function confirm(string &$route, array &$args, mixed &$code): void {
		$settings = $this->model()->getSettings();

		if (!$settings['status'] || !$this->cart->hasShipping()) {
			return;
		}

		$this->load->language($this->route, 'ds');

		$selected = $this->selected();

		if ($selected) {
			$html = '<div class="alert alert-info"><i class="fa-solid fa-calendar-check"></i> ' . htmlspecialchars(sprintf($this->language->get('ds_text_delivery'), $this->formatDate($selected['date']), $selected['slot']), ENT_QUOTES, 'UTF-8') . '</div>';

			$args['payment'] = $html . ($args['payment'] ?? '');
		} elseif ($settings['required'] && $this->model()->getAvailability()) {
			// No slot chosen: no payment button, so the order can't be confirmed without one.
			$args['payment'] = '<div class="alert alert-warning" id="deliveryslot-required"><i class="fa-solid fa-calendar-xmark"></i> ' . htmlspecialchars($this->language->get('ds_text_required'), ENT_QUOTES, 'UTF-8') . '</div>';
		}
	}

	/**
	 * Event: catalog/model/checkout/order.addOrder/after. $output is the new order ID.
	 */
	public function addOrder(string &$route, array &$args, mixed &$output): void {
		$selected = $this->session->data['deliveryslot'] ?? [];

		if ($selected && $output && $this->model()->getSettings()['status']) {
			$this->model()->saveForOrder((int)$output, (string)$selected['date'], (string)$selected['slot']);
		}
	}

	/**
	 * Event: catalog/model/checkout/order.editOrder/after. Checkout re-saves the order when the customer
	 * changes something on the confirm step. Orders edited without a choice in the session keep their slot.
	 */
	public function editOrder(string &$route, array &$args, mixed &$output): void {
		$selected = $this->session->data['deliveryslot'] ?? [];

		if ($selected && !empty($args[0]) && $this->model()->getSettings()['status']) {
			$this->model()->saveForOrder((int)$args[0], (string)$selected['date'], (string)$selected['slot']);
		}
	}

	/**
	 * Event: catalog/controller/checkout/success/before. The next order starts with no slot.
	 */
	public function success(string &$route, array &$args): void {
		unset($this->session->data['deliveryslot']);
	}

	private function line(int $order_id): string {
		$row = $order_id ? $this->model()->getForOrder($order_id) : [];

		if (!$row) {
			return '';
		}

		$this->load->language($this->route, 'ds');

		return htmlspecialchars(sprintf($this->language->get('ds_text_delivery'), $this->formatDate($row['delivery_date']), $row['slot']), ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Event: catalog/view/account/order_info/after.
	 */
	public function orderInfo(string &$route, array &$args, string &$output): void {
		$line = $this->line((int)($args['order_id'] ?? 0));

		if ($line) {
			$html = '<div class="alert alert-info"><i class="fa-solid fa-calendar-check"></i> ' . $line . '</div>';
			$position = strpos($output, '<div class="table-responsive">');
			$output = $position !== false ? substr_replace($output, $html, $position, 0) : $output . $html;
		}
	}

	/**
	 * Event: catalog/view/mail/order_add/after and mail/order_alert/after. The slot goes above the order details.
	 */
	public function mail(string &$route, array &$args, string &$output): void {
		$line = $this->line((int)($args['order_id'] ?? 0));

		if ($line) {
			$html = '<p style="margin-top: 0px; margin-bottom: 20px; font-size: 14px;"><strong>' . $line . '</strong></p>';

			if (($position = strpos($output, '<table')) !== false) {
				$output = substr_replace($output, $html, $position, 0);
			} elseif (($position = strripos($output, '</body>')) !== false) {
				$output = substr_replace($output, $html, $position, 0);
			} else {
				$output .= $html;
			}
		}
	}
}
