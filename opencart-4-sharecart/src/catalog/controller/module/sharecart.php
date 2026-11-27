<?php
namespace Opencart\Catalog\Controller\Extension\Sharecart\Module;
/**
 * Share Cart: turns the current cart into a short link, and adds a shared cart's products to the
 * cart of whoever opens the link.
 */
class Sharecart extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/sharecart/module/sharecart';

	private function setting(string $key): string {
		return html_entity_decode((string)$this->config->get('module_sharecart_' . $key), ENT_QUOTES, 'UTF-8');
	}

	/**
	 * The raw cart rows of the current visitor, the same way the core Cart library selects them.
	 */
	private function cartRows(): array {
		return $this->db->query("SELECT `product_id`, `subscription_plan_id`, `option`, `quantity` FROM `" . DB_PREFIX . "cart` WHERE `store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `customer_id` = '" . (int)$this->customer->getId() . "' AND `session_id` = '" . $this->db->escape($this->session->getId()) . "'")->rows;
	}

	/**
	 * Event: catalog/view/checkout/cart_list/after. Adds the share box under a non-empty cart.
	 */
	public function cartList(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_sharecart_status') || empty($args['products'])) {
			return;
		}

		$this->load->language($this->route, 'sharecart');

		$data['button'] = $this->setting('button');
		$data['intro'] = $this->setting('intro');
		$data['text_copy'] = $this->language->get('sharecart_text_copy');
		$data['text_copied'] = $this->language->get('sharecart_text_copied');
		$data['text_cart_link'] = $this->language->get('sharecart_text_cart_link');
		$data['create'] = $this->url->link($this->route . '.create', 'language=' . $this->config->get('config_language'));

		$output .= $this->load->view($this->route, $data);
	}

	/**
	 * POST from the share button. Saves the cart and returns its link as JSON.
	 */
	public function create(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->config->get('module_sharecart_status') || $this->request->server['REQUEST_METHOD'] != 'POST') {
			$json['error'] = $this->language->get('error_empty');
		}

		$items = [];

		if (!$json) {
			foreach ($this->cartRows() as $row) {
				// Only what identifies the product. Prices and other extensions' overrides are never shared.
				$items[] = [
					'product_id'           => (int)$row['product_id'],
					'quantity'             => max(1, (int)$row['quantity']),
					'option'               => json_decode($row['option'], true) ?: [],
					'subscription_plan_id' => (int)$row['subscription_plan_id']
				];
			}

			if (!$items) {
				$json['error'] = $this->language->get('error_empty');
			}
		}

		if (!$json) {
			$recent = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "sharecart` WHERE `session_id` = '" . $this->db->escape($this->session->getId()) . "' AND `date_added` > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->row;

			if ((int)$recent['total'] >= 20) {
				$json['error'] = $this->language->get('error_limit');
			}
		}

		if (!$json) {
			$encoded = json_encode($items);

			// The same cart shared twice gets the same link.
			$existing = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "sharecart` WHERE `session_id` = '" . $this->db->escape($this->session->getId()) . "' AND `items` = '" . $this->db->escape($encoded) . "' AND `date_added` > DATE_SUB(NOW(), INTERVAL 1 DAY) LIMIT 1")->row;

			if ($existing) {
				$code = $existing['code'];
			} else {
				$alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';

				do {
					$code = '';

					for ($i = 0; $i < 10; $i++) {
						$code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
					}
				} while ($this->db->query("SELECT 1 FROM `" . DB_PREFIX . "sharecart` WHERE `code` = '" . $code . "'")->num_rows);

				$this->db->query("INSERT INTO `" . DB_PREFIX . "sharecart` SET `code` = '" . $code . "', `store_id` = '" . (int)$this->config->get('config_store_id') . "', `customer_id` = '" . (int)$this->customer->getId() . "', `session_id` = '" . $this->db->escape($this->session->getId()) . "', `items` = '" . $this->db->escape($encoded) . "', `date_added` = NOW()");
			}

			$json['url'] = html_entity_decode($this->url->link($this->route . '.open', 'language=' . $this->config->get('config_language') . '&code=' . $code), ENT_QUOTES, 'UTF-8');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * The shared link. Adds the products that are still available, then shows the cart.
	 */
	public function open(): void {
		$this->load->language($this->route);

		$code = (string)($this->request->get['code'] ?? '');
		$cart = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true);

		$row = [];

		if ($this->config->get('module_sharecart_status') && preg_match('/^[a-z0-9]{10}$/', $code)) {
			$days = (int)$this->config->get('module_sharecart_days');

			$row = $this->db->query("SELECT * FROM `" . DB_PREFIX . "sharecart` WHERE `code` = '" . $this->db->escape($code) . "' AND `store_id` = '" . (int)$this->config->get('config_store_id') . "'" . ($days ? " AND `date_added` > DATE_SUB(NOW(), INTERVAL " . $days . " DAY)" : ""))->row;
		}

		if (!$row) {
			$this->session->data['error'] = $this->language->get('text_expired');

			$this->response->redirect($cart);

			return;
		}

		$this->load->model('catalog/product');

		if ($this->config->get('module_sharecart_mode') == 'replace') {
			$this->cart->clear();
		}

		$added = 0;

		foreach (json_decode($row['items'], true) ?: [] as $item) {
			$product = $this->model_catalog_product->getProduct((int)$item['product_id']);

			// Skip products that were disabled or deleted since the cart was shared.
			if (!$product) {
				continue;
			}

			$this->cart->add((int)$item['product_id'], max(1, (int)$item['quantity']), (array)$item['option'], (int)$item['subscription_plan_id']);

			$added++;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "sharecart` SET `opened` = `opened` + 1 WHERE `sharecart_id` = '" . (int)$row['sharecart_id'] . "'");

		if ($added) {
			$this->session->data['success'] = sprintf($this->setting('loaded'), $added);
		} else {
			$this->session->data['error'] = $this->language->get('text_none');
		}

		// Totals and shipping methods depend on the cart; let checkout recalculate them.
		unset($this->session->data['shipping_method'], $this->session->data['shipping_methods'], $this->session->data['payment_method'], $this->session->data['payment_methods']);

		$this->response->redirect($cart);
	}
}
