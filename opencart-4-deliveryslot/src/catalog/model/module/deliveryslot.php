<?php
namespace Opencart\Catalog\Model\Extension\Deliveryslot\Module;
/**
 * Delivery Date and Time Slots: available dates, slot capacity and the slot saved with each order.
 */
class Deliveryslot extends \Opencart\System\Engine\Model {
	/**
	 * Settings with defaults for a fresh install (Config::get() returns '' for unsaved keys).
	 */
	public function getSettings(): array {
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

	/**
	 * "09:00-12:00|10" lines to [['label' => '09:00-12:00', 'start' => '09:00', 'capacity' => 10], ...]. Capacity 0 = unlimited.
	 */
	public function parseSlots(string $text): array {
		$slots = [];

		foreach (preg_split('/\R/', $text) as $line) {
			$parts = array_map('trim', explode('|', $line));

			if (preg_match('/^(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})$/', $parts[0], $match)) {
				$slots[] = [
					'label'    => $match[1] . '-' . $match[2],
					'start'    => $match[1],
					'capacity' => isset($parts[1]) ? max(0, (int)$parts[1]) : 0
				];
			}
		}

		return $slots;
	}

	/**
	 * Booked orders per date and slot. Only orders in your processing and complete statuses count,
	 * so canceled orders and abandoned checkouts free their slot.
	 */
	public function getBooked(string $from, string $to): array {
		$statuses = array_unique(array_map('intval', array_merge((array)$this->config->get('config_processing_status'), (array)$this->config->get('config_complete_status'))));

		if (!$statuses) {
			return [];
		}

		$query = $this->db->query("SELECT ds.`delivery_date`, ds.`slot`, COUNT(*) AS `total` FROM `" . DB_PREFIX . "deliveryslot` ds JOIN `" . DB_PREFIX . "order` o ON (o.`order_id` = ds.`order_id`) WHERE ds.`delivery_date` BETWEEN '" . $this->db->escape($from) . "' AND '" . $this->db->escape($to) . "' AND o.`order_status_id` IN (" . implode(',', $statuses) . ") GROUP BY ds.`delivery_date`, ds.`slot`");

		$booked = [];

		foreach ($query->rows as $row) {
			$booked[$row['delivery_date']][$row['slot']] = (int)$row['total'];
		}

		return $booked;
	}

	/**
	 * Dates a customer can choose now, each with its slots that still have room.
	 * Returns ['2026-12-03' => [['label' => '09:00-12:00', 'left' => 7], ...], ...]
	 */
	public function getAvailability(): array {
		$settings = $this->getSettings();
		$slots = $this->parseSlots((string)$settings['slots']);

		if (!$slots) {
			return [];
		}

		$now = time();
		$today = strtotime(date('Y-m-d', $now));
		$lead = max(0, (int)$settings['lead']);

		// Orders placed after the cutoff time are treated as tomorrow's orders.
		if (preg_match('/^\d{2}:\d{2}$/', (string)$settings['cutoff']) && date('H:i', $now) >= $settings['cutoff']) {
			$lead++;
		}

		$days = max(1, min(90, (int)$settings['days']));
		$holidays = array_filter(array_map('trim', preg_split('/\R/', (string)$settings['holidays'])));
		$closed = array_map('strval', $settings['closed']);

		$from = date('Y-m-d', strtotime('+' . $lead . ' day', $today));
		$to = date('Y-m-d', strtotime('+' . ($lead + $days) . ' day', $today));
		$booked = $this->getBooked($from, $to);

		$dates = [];

		for ($i = $lead; $i < $lead + $days; $i++) {
			$time = strtotime('+' . $i . ' day', $today);
			$date = date('Y-m-d', $time);

			if (in_array(date('w', $time), $closed, true) || in_array($date, $holidays, true)) {
				continue;
			}

			$open = [];

			foreach ($slots as $slot) {
				// Same-day delivery (lead time 0): hide slots that have already started.
				if ($date == date('Y-m-d', $now) && $slot['start'] <= date('H:i', $now)) {
					continue;
				}

				$taken = $booked[$date][$slot['label']] ?? 0;

				if (!$slot['capacity'] || $taken < $slot['capacity']) {
					$open[] = ['label' => $slot['label'], 'left' => $slot['capacity'] ? $slot['capacity'] - $taken : null];
				}
			}

			if ($open) {
				$dates[$date] = $open;
			}
		}

		return $dates;
	}

	public function isAvailable(string $date, string $slot): bool {
		foreach ($this->getAvailability()[$date] ?? [] as $open) {
			if ($open['label'] === $slot) {
				return true;
			}
		}

		return false;
	}

	public function saveForOrder(int $order_id, string $date, string $slot): void {
		$this->db->query("REPLACE INTO `" . DB_PREFIX . "deliveryslot` SET `order_id` = '" . (int)$order_id . "', `delivery_date` = '" . $this->db->escape($date) . "', `slot` = '" . $this->db->escape($slot) . "', `date_added` = NOW()");
	}

	public function deleteForOrder(int $order_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "deliveryslot` WHERE `order_id` = '" . (int)$order_id . "'");
	}

	public function getForOrder(int $order_id): array {
		return $this->db->query("SELECT * FROM `" . DB_PREFIX . "deliveryslot` WHERE `order_id` = '" . (int)$order_id . "'")->row;
	}
}
