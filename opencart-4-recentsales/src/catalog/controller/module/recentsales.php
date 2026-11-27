<?php
namespace Opencart\Catalog\Controller\Extension\Recentsales\Module;
/**
 * Recent Sales Notifications: a small popup with products from real recent orders, and a
 * "Bought 12 times in the last 7 days" line on product pages. Nothing is invented.
 */
class Recentsales extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/recentsales/module/recentsales';

	private function setting(string $key): string {
		return html_entity_decode((string)$this->config->get('module_recentsales_' . $key), ENT_QUOTES, 'UTF-8');
	}

	private function statuses(): string {
		$ids = array_map('intval', (array)$this->config->get('module_recentsales_statuses'));

		return implode(',', $ids ?: [0]);
	}

	private function ago(int $seconds): string {
		$this->load->language($this->route, 'rs');

		if ($seconds < 60) {
			return $this->language->get('rs_text_just_now');
		}

		if ($seconds < 3600) {
			$n = intdiv($seconds, 60);

			return $n == 1 ? $this->language->get('rs_text_minute') : sprintf($this->language->get('rs_text_minutes'), $n);
		}

		if ($seconds < 86400) {
			$n = intdiv($seconds, 3600);

			return $n == 1 ? $this->language->get('rs_text_hour') : sprintf($this->language->get('rs_text_hours'), $n);
		}

		$n = intdiv($seconds, 86400);

		return $n == 1 ? $this->language->get('rs_text_day') : sprintf($this->language->get('rs_text_days'), $n);
	}

	/**
	 * Event: catalog/view/common/footer/after. Adds the popup script (data comes from feed()).
	 */
	public function footer(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_recentsales_status') || !$this->config->get('module_recentsales_popup')) {
			return;
		}

		if (preg_match('#^(checkout/|account/)#', (string)($this->request->get['route'] ?? ''))) {
			return;
		}

		$this->load->language($this->route, 'rs');

		$data['feed'] = html_entity_decode($this->url->link($this->route . '.feed', 'language=' . $this->config->get('config_language')), ENT_QUOTES, 'UTF-8');
		$data['delay'] = (int)$this->config->get('module_recentsales_delay');
		$data['interval'] = max(5, (int)$this->config->get('module_recentsales_interval'));
		$data['max'] = max(1, (int)$this->config->get('module_recentsales_max'));
		$data['text_close'] = $this->language->get('rs_text_close');

		$html = $this->load->view('extension/recentsales/module/recentsales', $data);

		if (($position = strripos($output, '</body>')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		} else {
			$output .= $html;
		}
	}

	/**
	 * JSON: the most recent sold products (one entry per product), newest first.
	 */
	public function feed(): void {
		$json = ['items' => []];

		if ($this->config->get('module_recentsales_status') && $this->config->get('module_recentsales_popup')) {
			$days = max(1, (int)$this->config->get('module_recentsales_days'));
			$city = (bool)$this->config->get('module_recentsales_city');

			$query = $this->db->query("SELECT op.`product_id`, MAX(o.`date_added`) AS `last`, SUBSTRING_INDEX(GROUP_CONCAT(o.`shipping_city` ORDER BY o.`date_added` DESC SEPARATOR '|'), '|', 1) AS `city` FROM `" . DB_PREFIX . "order_product` op LEFT JOIN `" . DB_PREFIX . "order` o ON (o.`order_id` = op.`order_id`) WHERE o.`order_status_id` IN (" . $this->statuses() . ") AND o.`store_id` = '" . (int)$this->config->get('config_store_id') . "' AND o.`date_added` > DATE_SUB(NOW(), INTERVAL " . $days . " DAY) GROUP BY op.`product_id` ORDER BY `last` DESC LIMIT 10");

			$this->load->model('catalog/product');
			$this->load->model('tool/image');

			$now = time();

			foreach ($query->rows as $row) {
				$product = $this->model_catalog_product->getProduct((int)$row['product_id']);

				// Only products that are still for sale.
				if (!$product) {
					continue;
				}

				$name = html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8');
				$time = $this->ago(max(0, $now - strtotime($row['last'])));
				$use_city = $city && trim((string)$row['city']) !== '';

				$text = str_replace(['%product%', '%time%', '%city%'], [$name, $time, trim((string)$row['city'])], $use_city ? $this->setting('city_text') : $this->setting('text'));

				$json['items'][] = [
					'text'  => $text,
					'href'  => html_entity_decode($this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)$product['product_id']), ENT_QUOTES, 'UTF-8'),
					'image' => $this->model_tool_image->resize(html_entity_decode((string)($product['image'] ?: 'placeholder.png'), ENT_QUOTES, 'UTF-8'), 80, 80)
				];
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->addHeader('Cache-Control: max-age=300');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Event: catalog/view/product/product/after. "Bought 12 times in the last 7 days" under the price.
	 */
	public function product(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_recentsales_status') || !$this->config->get('module_recentsales_counter')) {
			return;
		}

		$product_id = (int)($args['product_id'] ?? 0);
		$days = max(1, (int)$this->config->get('module_recentsales_days'));

		$row = $this->db->query("SELECT COALESCE(SUM(op.`quantity`), 0) AS `total` FROM `" . DB_PREFIX . "order_product` op LEFT JOIN `" . DB_PREFIX . "order` o ON (o.`order_id` = op.`order_id`) WHERE op.`product_id` = '" . $product_id . "' AND o.`order_status_id` IN (" . $this->statuses() . ") AND o.`date_added` > DATE_SUB(NOW(), INTERVAL " . $days . " DAY)")->row;

		$count = (int)$row['total'];

		if ($count < max(1, (int)$this->config->get('module_recentsales_min'))) {
			return;
		}

		$text = str_replace(['%count%', '%days%'], [$count, $days], $this->setting('counter_text'));
		$html = '<li class="recentsales-counter text-success"><i class="fa-solid fa-fire"></i> ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</li>';

		// Under the price block.
		if (preg_match('#<li><h2><span class="price-new">.*?</span></h2></li>#s', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1] + strlen($match[0][0]), 0);
		}
	}
}
