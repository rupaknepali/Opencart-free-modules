<?php
namespace Opencart\Admin\Controller\Extension\Deliveryslot\Module;
/**
 * Delivery Date and Time Slots: settings, upcoming deliveries, the slot on the admin order page.
 */
class Deliveryslot extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/deliveryslot/module/deliveryslot';

	private function settings(): array {
		$defaults = [
			'status'   => 0,
			'required' => 1,
			'lead'     => 1,
			'cutoff'   => '14:00',
			'days'     => 14,
			'closed'   => ['0'],
			'holidays' => '',
			'slots'    => "09:00-12:00|10\n12:00-15:00|10\n15:00-18:00|10"
		];

		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_deliveryslot');
		$out = [];

		foreach ($defaults as $key => $value) {
			$out[$key] = array_key_exists('module_deliveryslot_' . $key, $saved) ? $saved['module_deliveryslot_' . $key] : $value;
		}

		if (!is_array($out['closed'])) {
			$out['closed'] = [];
		}

		return $out;
	}

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

		foreach ($this->settings() as $key => $value) {
			$data['module_deliveryslot_' . $key] = $value;
		}

		$data['weekdays'] = [];

		foreach (['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $number => $day) {
			$data['weekdays'][] = ['value' => (string)$number, 'name' => $this->language->get('text_' . $day)];
		}

		// Upcoming deliveries: today and the next 14 days, orders in processing and complete statuses.
		$data['upcoming'] = [];

		$statuses = array_unique(array_map('intval', array_merge((array)$this->config->get('config_processing_status'), (array)$this->config->get('config_complete_status'))));

		if ($statuses) {
			$query = $this->db->query("SELECT ds.`delivery_date`, ds.`slot`, COUNT(*) AS `total`, GROUP_CONCAT(ds.`order_id` ORDER BY ds.`order_id`) AS `orders` FROM `" . DB_PREFIX . "deliveryslot` ds JOIN `" . DB_PREFIX . "order` o ON (o.`order_id` = ds.`order_id`) WHERE ds.`delivery_date` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY) AND o.`order_status_id` IN (" . implode(',', $statuses) . ") GROUP BY ds.`delivery_date`, ds.`slot` ORDER BY ds.`delivery_date`, ds.`slot`");

			$capacity = [];

			foreach (preg_split('/\R/', (string)$data['module_deliveryslot_slots']) as $line) {
				$parts = array_map('trim', explode('|', $line));
				$capacity[str_replace(' ', '', $parts[0])] = (int)($parts[1] ?? 0);
			}

			foreach ($query->rows as $row) {
				$orders = [];

				foreach (explode(',', $row['orders']) as $order_id) {
					$orders[] = ['order_id' => (int)$order_id, 'href' => $this->url->link('sale/order.info', $token . '&order_id=' . (int)$order_id)];
				}

				$data['upcoming'][] = [
					'date'   => date($this->language->get('date_format_short'), strtotime($row['delivery_date'])) . ' (' . $this->language->get('text_' . strtolower(date('l', strtotime($row['delivery_date'])))) . ')',
					'slot'   => $row['slot'],
					'booked' => (int)$row['total'] . ' / ' . (!empty($capacity[$row['slot']]) ? $capacity[$row['slot']] : $this->language->get('text_unlimited')),
					'orders' => $orders
				];
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		$slots = 0;

		foreach (preg_split('/\R/', trim((string)($post['module_deliveryslot_slots'] ?? ''))) as $line) {
			if (trim($line) === '') {
				continue;
			}

			if (!preg_match('/^\s*\d{2}:\d{2}\s*-\s*\d{2}:\d{2}\s*(\|\s*\d+\s*)?$/', $line)) {
				$json['error']['slots'] = sprintf($this->language->get('error_slot_line'), $line);
			} else {
				$slots++;
			}
		}

		if (!$slots && empty($json['error']['slots'])) {
			$json['error']['slots'] = $this->language->get('error_slots');
		}

		$cutoff = trim((string)($post['module_deliveryslot_cutoff'] ?? ''));

		if ($cutoff !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $cutoff)) {
			$json['error']['cutoff'] = $this->language->get('error_cutoff');
		}

		foreach (preg_split('/\R/', trim((string)($post['module_deliveryslot_holidays'] ?? ''))) as $line) {
			$line = trim($line);

			if ($line !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $line) || !strtotime($line))) {
				$json['error']['holidays'] = sprintf($this->language->get('error_holiday'), $line);
			}
		}

		$days = (int)($post['module_deliveryslot_days'] ?? 0);

		if ($days < 1 || $days > 90) {
			$json['error']['days'] = $this->language->get('error_days');
		}

		if (!$json) {
			$post['module_deliveryslot_closed'] = array_values(array_intersect((array)($post['module_deliveryslot_closed'] ?? []), ['0', '1', '2', '3', '4', '5', '6']));
			$post['module_deliveryslot_lead'] = max(0, (int)($post['module_deliveryslot_lead'] ?? 0));
			$post['module_deliveryslot_cutoff'] = $cutoff;

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_deliveryslot', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Event: admin/view/sale/order_info/after. The delivery slot above the order details.
	 */
	public function orderInfo(string &$route, array &$args, string &$output): void {
		$order_id = (int)($args['order_id'] ?? 0);

		if (!$order_id) {
			return;
		}

		$row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "deliveryslot` WHERE `order_id` = '" . $order_id . "'")->row;

		if (!$row) {
			return;
		}

		$this->load->language($this->route, 'ds');

		$time = strtotime($row['delivery_date']);

		$html = '<div class="alert alert-info"><i class="fa-solid fa-calendar-check"></i> <strong>' . htmlspecialchars($this->language->get('ds_text_delivery'), ENT_QUOTES, 'UTF-8') . ':</strong> ' . htmlspecialchars($this->language->get('ds_text_' . strtolower(date('l', $time))) . ', ' . date($this->language->get('date_format_short'), $time) . ', ' . $row['slot'], ENT_QUOTES, 'UTF-8') . '</div>';

		$position = strpos($output, '<div class="card mb-3">');

		if ($position !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "deliveryslot` (
			`order_id` INT(11) NOT NULL,
			`delivery_date` DATE NOT NULL,
			`slot` VARCHAR(32) NOT NULL,
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`order_id`),
			KEY `delivery_date` (`delivery_date`, `slot`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		foreach ($this->events() as [$code, $trigger, $action, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Delivery Date and Time Slots: ' . $description,
				'trigger'     => $trigger,
				'action'      => $action,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach ($this->events() as $event) {
			$this->model_setting_event->deleteEventByCode($event[0]);
		}

		// The table is kept, so the delivery slots of existing orders survive a reinstall.
	}

	private function events(): array {
		$catalog = 'extension/deliveryslot/module/deliveryslot.';

		return [
			['deliveryslot_picker', 'catalog/view/checkout/shipping_method/after', $catalog . 'shippingMethod', 'picker in checkout'],
			['deliveryslot_confirm', 'catalog/view/checkout/confirm/before', $catalog . 'confirm', 'slot or reminder on the confirm step'],
			['deliveryslot_add_order', 'catalog/model/checkout/order.addOrder/after', $catalog . 'addOrder', 'save the slot with new orders'],
			['deliveryslot_edit_order', 'catalog/model/checkout/order.editOrder/after', $catalog . 'editOrder', 'keep the slot when checkout re-saves the order'],
			['deliveryslot_success', 'catalog/controller/checkout/success/before', $catalog . 'success', 'reset after checkout'],
			['deliveryslot_account', 'catalog/view/account/order_info/after', $catalog . 'orderInfo', 'slot on customer order pages'],
			['deliveryslot_mail', 'catalog/view/mail/order_add/after', $catalog . 'mail', 'slot in the order confirmation email'],
			['deliveryslot_mail_alert', 'catalog/view/mail/order_alert/after', $catalog . 'mail', 'slot in the new order alert'],
			['deliveryslot_admin_order', 'admin/view/sale/order_info/after', $this->route . '.orderInfo', 'slot on the admin order page']
		];
	}
}
