<?php
namespace Opencart\Catalog\Controller\Extension\Storepickup\Event;
/**
 * Store Pickup: opening hours and ready time at checkout, in the order email and on the order page.
 */
class Storepickup extends \Opencart\System\Engine\Controller {
	/**
	 * Trigger: catalog/controller/checkout/shipping_method.quote/after
	 * Adds the hours, ready time and a map link under each location in the checkout's shipping list.
	 * Only the list shown to the shopper changes; the plain name saved with the order stays as it is.
	 */
	public function quote(string &$route, array &$args, mixed &$output): void {
		$json = json_decode((string)$this->response->getOutput(), true);

		if (empty($json['shipping_methods']['storepickup']['quote'])) {
			return;
		}

		// The default theme shows each label as an inline block, so a long one drops below its radio button.
		// Line the radio and text up side by side, for pickup options only.
		$style = '<style>#modal-shipping .form-check:has(.storepickup-details){display:flex;align-items:baseline;gap:.5rem}</style>';

		// It goes in the method heading: checkout copies the chosen option's label text into a text box.
		$json['shipping_methods']['storepickup']['name'] = htmlspecialchars((string)$json['shipping_methods']['storepickup']['name'], ENT_QUOTES, 'UTF-8') . $style;

		foreach ($json['shipping_methods']['storepickup']['quote'] as &$quote) {
			if (!empty($quote['storepickup'])) {
				$quote['name'] = htmlspecialchars($quote['name'], ENT_QUOTES, 'UTF-8') . $this->details($quote['storepickup'], 'checkout');
			}
		}

		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Trigger: catalog/view/mail/order_add/after
	 */
	public function mail(string &$route, array &$args, string &$output): void {
		$pickup = $this->pickup((int)($args['order_id'] ?? 0));

		if (!$pickup || ($position = strpos($output, $args['shipping_method'] ?? "\0")) === false) {
			return;
		}

		$output = substr_replace($output, $this->details($pickup, 'mail'), $position + strlen($args['shipping_method']), 0);
	}

	/**
	 * Trigger: catalog/view/account/order_info/after
	 */
	public function order(string &$route, array &$args, string &$output): void {
		if (!$this->customer->isLogged()) {
			return;
		}

		$pickup = $this->pickup((int)($args['order_id'] ?? 0), (int)$this->customer->getId());

		if (!$pickup || ($position = strpos($output, $args['shipping_method'] ?? "\0")) === false) {
			return;
		}

		$output = substr_replace($output, $this->details($pickup, 'order'), $position + strlen($args['shipping_method']), 0);
	}

	/**
	 * The pickup details stored with the order's shipping method, if it's a pickup order.
	 */
	private function pickup(int $order_id, int $customer_id = 0): array {
		if (!$order_id) {
			return [];
		}

		$query = $this->db->query("SELECT `shipping_method` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "'" . ($customer_id ? " AND `customer_id` = '" . (int)$customer_id . "'" : ''));

		$method = $query->num_rows ? json_decode((string)$query->row['shipping_method'], true) : [];

		return (is_array($method) && str_starts_with((string)($method['code'] ?? ''), 'storepickup.') && !empty($method['storepickup'])) ? $method['storepickup'] : [];
	}

	private function details(array $pickup, string $where): string {
		$this->load->language('extension/storepickup/shipping/storepickup');

		$e = fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

		$parts = [];

		if ($pickup['hours'] !== '') {
			$parts[] = $e($pickup['hours']);
		}

		if ($pickup['ready'] !== '') {
			$parts[] = $e($pickup['ready']);
		}

		$map = '';

		if ($this->config->get('shipping_storepickup_map') && $pickup['address'] !== '') {
			$map = '<a href="https://www.google.com/maps/search/?api=1&amp;query=' . rawurlencode($pickup['location'] . ', ' . $pickup['address']) . '" target="_blank" rel="noopener">' . $this->language->get('sp_map') . '</a>';
		}

		$instructions = html_entity_decode((string)$this->config->get('shipping_storepickup_instructions'), ENT_QUOTES, 'UTF-8');

		if ($where == 'checkout') {
			// The hidden dash keeps the address and the hours apart in the text box checkout fills in after choosing.
			return '<span class="visually-hidden"> – </span><br/><small class="text-muted storepickup-details">' . implode(' · ', array_merge($parts, $map ? [$map] : [])) . '</small><br/>';
		}

		$html = '<br/>' . ($where == 'mail' ? '<b>' . $this->language->get('sp_when') . '</b> ' : '<strong>' . $this->language->get('sp_when') . '</strong> ') . implode(' · ', $parts);

		if ($map) {
			$html .= ' · ' . $map;
		}

		if ($instructions !== '') {
			$html .= '<br/>' . nl2br($e($instructions));
		}

		return $html;
	}
}
