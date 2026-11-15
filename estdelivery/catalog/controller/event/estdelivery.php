<?php
namespace Opencart\Catalog\Controller\Extension\Estdelivery\Event;
/**
 * Estimated Delivery Date: adds "Order within ... and get it ..." to product pages.
 */
class Estdelivery extends \Opencart\System\Engine\Controller {
	private array $days = [];
	private array $holidays = [];

	/**
	 * Trigger: catalog/view/product/product/after
	 */
	public function index(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_estdelivery_status') || empty($args['product_id'])) {
			return;
		}

		$this->load->model('catalog/product');

		$product = $this->model_catalog_product->getProduct((int)$args['product_id']);

		// Downloads and services don't ship.
		if (!$product || !$product['shipping']) {
			return;
		}

		// Out of stock (and stock is tracked): hide unless the store says backorders ship normally.
		if ($product['quantity'] <= 0 && $product['subtract'] && !$this->config->get('module_estdelivery_stock')) {
			return;
		}

		$this->days = array_map('intval', (array)($this->config->get('module_estdelivery_days') ?: [1, 2, 3, 4, 5]));
		$this->holidays = array_filter(array_map('trim', preg_split('/\R/', (string)$this->config->get('module_estdelivery_holidays'))));

		$now = new \DateTimeImmutable('now');

		[$cutoff_hour, $cutoff_minute] = array_map('intval', explode(':', $this->config->get('module_estdelivery_cutoff') ?: '14:00'));

		$cutoff = $now->setTime($cutoff_hour, $cutoff_minute);

		$this->load->language('extension/estdelivery/module/estdelivery');

		$data['before'] = '';
		$data['deadline'] = 0;

		// Before today's cut-off on a working day: the order ships from today, and we can count down.
		if ($this->isWorkingDay($now) && $now < $cutoff) {
			$data['before'] = $this->sentence($now->setTime(0, 0), $now, $cutoff);
			$data['deadline'] = $cutoff->getTimestamp();
		}

		// After the cut-off (or on a day off): it ships from the next working day.
		// Rendered either way, so the page can switch to it when the countdown ends.
		$data['after'] = $this->sentence($this->nextWorkingDay($now->setTime(0, 0)), $now, null);

		$data['now'] = $now->getTimestamp();

		$html = $this->load->view('extension/estdelivery/module/estdelivery', $data);

		// Above the Add to Cart form, under the price.
		$position = strpos($output, '<div id="product">');

		if ($position !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}
	}

	/**
	 * Builds the message for an order that is dispatched from $dispatch.
	 */
	private function sentence(\DateTimeImmutable $dispatch, \DateTimeImmutable $now, ?\DateTimeImmutable $deadline): string {
		// Handling time: working days before the parcel leaves.
		for ($i = 0; $i < (int)$this->config->get('module_estdelivery_processing'); $i++) {
			$dispatch = $this->nextWorkingDay($dispatch);
		}

		$from = $dispatch;
		$to = $dispatch;

		for ($i = 0; $i < (int)$this->config->get('module_estdelivery_min'); $i++) {
			$from = $this->nextWorkingDay($from);
		}

		for ($i = 0; $i < (int)($this->config->get('module_estdelivery_max') ?: $this->config->get('module_estdelivery_min')); $i++) {
			$to = $this->nextWorkingDay($to);
		}

		$format = $this->config->get('module_estdelivery_format') ?: 'D j M';

		if ($from == $to) {
			$date = '<strong>' . htmlspecialchars($from->format($format)) . '</strong>';
		} else {
			$date = sprintf($this->language->get('ed_range'), '<strong>' . htmlspecialchars($from->format($format)) . '</strong>', '<strong>' . htmlspecialchars($to->format($format)) . '</strong>');
		}

		if ($deadline) {
			return sprintf($this->language->get('ed_before'), '<strong class="oc-estdelivery-countdown">' . $this->remaining($deadline->getTimestamp() - $now->getTimestamp()) . '</strong>', $date);
		}

		return sprintf($this->language->get('ed_after'), $date);
	}

	private function remaining(int $seconds): string {
		$minutes = max(1, (int)ceil($seconds / 60));

		$hours = intdiv($minutes, 60);
		$minutes = $minutes % 60;

		return ($hours ? $hours . ' ' . $this->language->get('ed_hours') . ' ' : '') . $minutes . ' ' . $this->language->get('ed_minutes');
	}

	private function isWorkingDay(\DateTimeImmutable $day): bool {
		return in_array((int)$day->format('N'), $this->days) && !in_array($day->format('Y-m-d'), $this->holidays);
	}

	private function nextWorkingDay(\DateTimeImmutable $day): \DateTimeImmutable {
		// A year of days off would mean a misconfiguration, not a real schedule; stop there.
		for ($i = 0; $i < 366; $i++) {
			$day = $day->modify('+1 day');

			if ($this->isWorkingDay($day)) {
				break;
			}
		}

		return $day;
	}
}
