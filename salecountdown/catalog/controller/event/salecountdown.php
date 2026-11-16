<?php
namespace Opencart\Catalog\Controller\Extension\Salecountdown\Event;
/**
 * Sale Countdown Timer: "Sale ends in 2d 04:12:33" for specials with an end date.
 */
class Salecountdown extends \Opencart\System\Engine\Controller {
	private bool $used = false;

	/**
	 * Trigger: catalog/view/product/product/after
	 * Under the price on the product page.
	 */
	public function product(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_salecountdown_status') || !$this->config->get('module_salecountdown_product') || empty($args['product_id']) || empty($args['special'])) {
			return;
		}

		$end = $this->deadline((int)$args['product_id']);

		if (!$end) {
			return;
		}

		$this->load->language('extension/salecountdown/module/salecountdown');

		$html = '<p class="oc-countdown oc-countdown-lg" data-end="' . $end . '"><i class="fa-regular fa-clock" aria-hidden="true"></i> '
			. $this->language->get('sc_ends') . ' <strong class="oc-countdown-time">' . $this->remaining($end, false) . '</strong></p>';

		// Right after the price list: the first </ul> after the special price.
		$price = strpos($output, 'price-new');

		if ($price !== false && ($close = strpos($output, '</ul>', $price)) !== false) {
			$output = substr_replace($output, $html, $close + 5, 0);
			$this->used = true;
		}
	}

	/**
	 * Trigger: catalog/view/product/thumb/after
	 * A short version under the price on product cards.
	 */
	public function thumb(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_salecountdown_status') || !$this->config->get('module_salecountdown_card') || empty($args['product_id']) || empty($args['special'])) {
			return;
		}

		$end = $this->deadline((int)$args['product_id']);

		if (!$end) {
			return;
		}

		$this->load->language('extension/salecountdown/module/salecountdown');

		$html = '<div class="oc-countdown oc-countdown-sm" data-end="' . $end . '" data-short="1"><i class="fa-regular fa-clock" aria-hidden="true"></i> '
			. $this->language->get('sc_ends_short') . ' <span class="oc-countdown-time">' . $this->remaining($end, true) . '</span></div>';

		// After the card's price block.
		if (($price = strpos($output, '<div class="price">')) !== false && ($close = strpos($output, '</div>', $price)) !== false) {
			$output = substr_replace($output, $html, $close + 6, 0);
			$this->used = true;
		}
	}

	/**
	 * Trigger: catalog/view/common/footer/after
	 * Styles and the script that ticks every countdown on the page, only if one was shown.
	 */
	public function footer(string &$route, array &$args, string &$output): void {
		if (!$this->used) {
			return;
		}

		$this->load->language('extension/salecountdown/module/salecountdown');

		$html = $this->load->view('extension/salecountdown/module/salecountdown', ['now' => time()]);

		$position = strripos($output, '</body>');

		$output = $position !== false ? substr_replace($output, $html, $position, 0) : $output . $html;
	}

	/**
	 * Unix time the special ends, or 0 if there's no end date or it's too far away.
	 * OpenCart compares date_end with NOW(), so a special ending "2026-11-20" stops at 00:00 that day.
	 */
	private function deadline(int $product_id): int {
		$this->load->model('extension/salecountdown/module/salecountdown');

		$date = $this->model_extension_salecountdown_module_salecountdown->getSpecialEnd($product_id);

		if (!$date) {
			return 0;
		}

		$end = strtotime($date . ' 00:00:00');
		$days = (int)$this->config->get('module_salecountdown_days');

		if ($end <= time() || ($days > 0 && $end - time() > $days * 86400)) {
			return 0;
		}

		return $end;
	}

	/**
	 * The text shown before JavaScript runs (and for visitors without it).
	 */
	private function remaining(int $end, bool $short): string {
		$left = max(0, $end - time());

		$d = intdiv($left, 86400);
		$h = intdiv($left % 86400, 3600);
		$m = intdiv($left % 3600, 60);
		$s = $left % 60;

		if ($short) {
			return $d ? $d . 'd ' . $h . 'h' : $h . 'h ' . $m . 'm';
		}

		return ($d ? $d . 'd ' : '') . sprintf('%02d:%02d:%02d', $h, $m, $s);
	}
}
