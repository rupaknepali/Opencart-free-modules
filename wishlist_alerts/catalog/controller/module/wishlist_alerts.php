<?php
namespace Opencart\Catalog\Controller\Extension\WishlistAlerts\Module;
/**
 * Class WishlistAlerts
 *
 * Wishlist Alerts for OpenCart 4.1.0.x
 *
 * Cron:
 *   wishlist_alerts (day) -> cron
 * Events:
 *   catalog/model/checkout/order.addHistory/after -> reward (optional automatic reward points)
 *
 * For each wishlist item we keep the price and quantity we saw last time. A price drop is measured against
 * that baseline. Small drops don't move the baseline down, so a price that falls 3% a day still triggers
 * an alert once the total drop passes the minimum.
 *
 * @package Opencart\Catalog\Controller\Extension\WishlistAlerts\Module
 */
class WishlistAlerts extends \Opencart\System\Engine\Controller {
	/**
	 * Cron
	 *
	 * @param int    $cron_id
	 * @param string $code
	 * @param string $cycle
	 * @param string $date_added
	 * @param string $date_modified
	 *
	 * @return void
	 */
	public function cron(int $cron_id, string $code, string $cycle, string $date_added, string $date_modified): void {
		if (!$this->config->get('module_wishlist_alerts_status')) {
			return;
		}

		$this->load->model('extension/wishlist_alerts/module/wishlist_alerts');

		$this->model_extension_wishlist_alerts_module_wishlist_alerts->clean();

		$max_emails = (int)$this->config->get('module_wishlist_alerts_max_emails') ?: 100;
		$sent = 0;
		$last_id = 0;

		// Settings that we switch per customer, restored at the end.
		$original = [
			'config_customer_group_id' => $this->config->get('config_customer_group_id'),
			'config_store_id'          => $this->config->get('config_store_id'),
			'config_language_id'       => $this->config->get('config_language_id')
		];

		do {
			$customers = $this->model_extension_wishlist_alerts_module_wishlist_alerts->getCustomers((bool)$this->config->get('module_wishlist_alerts_newsletter'), (int)$this->config->get('module_wishlist_alerts_cooldown') ?: 7, $last_id, 200);

			foreach ($customers as $customer) {
				if ($sent >= $max_emails) {
					break 2;
				}

				$last_id = (int)$customer['customer_id'];

				try {
					if ($this->check($customer)) {
						$sent++;
					}
				} catch (\Throwable $e) {
					$this->log->write('Wishlist Alerts: customer ' . $customer['customer_id'] . ': ' . $e->getMessage());
				}
			}
		} while ($customers);

		foreach ($original as $key => $value) {
			$this->config->set($key, $value);
		}
	}

	/**
	 * Reward
	 *
	 * OpenCart 4 only adds the reward points a customer earned when an admin clicks the reward button on the
	 * order. This adds them when the order reaches a Complete order status, and takes them away again if the
	 * order later moves to a status that is neither processing nor complete (cancelled, refunded and so on).
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args [order_id, order_status_id, ...]
	 * @param mixed             $output
	 *
	 * @return void
	 */
	public function reward(string &$route, array &$args, &$output): void {
		if (!$this->config->get('module_wishlist_alerts_reward') || empty($args[0]) || !isset($args[1])) {
			return;
		}

		try {
			$order_id = (int)$args[0];
			$order_status_id = (int)$args[1];

			$this->load->model('checkout/order');

			$order_info = $this->model_checkout_order->getOrder($order_id);

			if (!$order_info || !$order_info['customer_id']) {
				return;
			}

			// Points earned, as shown on the order. Redeemed points are stored as negative rows and are left alone.
			$points = (int)$this->db->query("SELECT COALESCE(SUM(`reward`), 0) AS `total` FROM `" . DB_PREFIX . "order_product` WHERE `order_id` = '" . $order_id . "'")->row['total'];

			$earned = (int)$this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "customer_reward` WHERE `order_id` = '" . $order_id . "' AND `points` > '0'")->row['total'];

			$complete = in_array($order_status_id, (array)$this->config->get('config_complete_status'));
			$active = $complete || in_array($order_status_id, (array)$this->config->get('config_processing_status'));

			if ($complete && $points > 0 && !$earned) {
				$this->load->language('extension/wishlist_alerts/module/wishlist_alerts');

				$this->load->model('account/reward');

				$this->model_account_reward->addReward((int)$order_info['customer_id'], $order_id, sprintf($this->language->get('text_reward'), $order_id), $points);
			} elseif (!$active && $earned) {
				$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_reward` WHERE `order_id` = '" . $order_id . "' AND `points` > '0'");
			}
		} catch (\Throwable $e) {
			$this->log->write('Wishlist Alerts & Reward Points: ' . $e->getMessage());
		}
	}

	/**
	 * Check
	 *
	 * @param array<string, mixed> $customer
	 *
	 * @return bool true if an email was sent
	 */
	private function check(array $customer): bool {
		$this->config->set('config_customer_group_id', (int)$customer['customer_group_id']);
		$this->config->set('config_store_id', (int)$customer['store_id']);

		if ($customer['language_id']) {
			$this->config->set('config_language_id', (int)$customer['language_id']);
		}

		$this->load->model('catalog/product');

		$min_drop = (int)$this->config->get('module_wishlist_alerts_min_drop') ?: 10;
		$stock_checkout = (bool)$this->config->get('config_stock_checkout');

		$alerts = [];
		$snapshots = [];

		foreach ($this->model_extension_wishlist_alerts_module_wishlist_alerts->getItems((int)$customer['customer_id']) as $item) {
			$product_info = $this->model_catalog_product->getProduct((int)$item['product_id']);

			// Disabled or deleted products are left on the wishlist but never mailed.
			if (!$product_info) {
				continue;
			}

			$price = (float)($product_info['special'] ?: $product_info['price']);
			$quantity = (int)$product_info['quantity'];

			$snapshot = [
				'product_id' => (int)$item['product_id'],
				'price'      => $price,
				'quantity'   => $quantity
			];

			// First time we see this item: just remember it.
			if ($item['last_price'] === null) {
				$this->model_extension_wishlist_alerts_module_wishlist_alerts->editSnapshot((int)$customer['customer_id'], (int)$item['product_id'], $price, $quantity);

				continue;
			}

			$last_price = (float)$item['last_price'];
			$last_quantity = (int)$item['last_quantity'];

			$available = $quantity > 0 || $stock_checkout;

			$reason = '';

			if ($this->config->get('module_wishlist_alerts_restock') && $last_quantity <= 0 && $quantity > 0) {
				$reason = 'restock';
			} elseif ($this->config->get('module_wishlist_alerts_price_drop') && $available && $last_price > 0 && $price <= $last_price * (1 - $min_drop / 100)) {
				$reason = 'price';
			}

			if ($reason) {
				$alerts[] = [
					'product' => $product_info,
					'reason'  => $reason,
					'old'     => $last_price,
					'new'     => $price
				];
			} elseif ($price < $last_price) {
				// Keep the higher baseline so small drops add up.
				$snapshot['price'] = $last_price;
			}

			$snapshots[] = $snapshot;
		}

		$sent = false;

		if ($alerts) {
			$sent = $this->send($customer, $alerts);
		}

		// If the email failed, keep the old values so we try again tomorrow.
		if ($sent || !$alerts) {
			foreach ($snapshots as $snapshot) {
				$this->model_extension_wishlist_alerts_module_wishlist_alerts->editSnapshot((int)$customer['customer_id'], $snapshot['product_id'], $snapshot['price'], $snapshot['quantity']);
			}
		}

		return $sent;
	}

	/**
	 * Send
	 *
	 * @param array<string, mixed>             $customer
	 * @param array<int, array<string, mixed>> $alerts
	 *
	 * @return bool
	 */
	private function send(array $customer, array $alerts): bool {
		if (!$this->config->get('config_mail_engine')) {
			return false;
		}

		$this->load->language('extension/wishlist_alerts/module/wishlist_alerts');

		$store_name = (string)$this->config->get('config_name');
		$store_url = (string)$this->config->get('config_url');

		if ($customer['store_id']) {
			$this->load->model('setting/store');

			$store_info = $this->model_setting_store->getStore((int)$customer['store_id']);

			if ($store_info) {
				$store_name = $store_info['name'];
				$store_url = $store_info['url'];
			}
		}

		$store_name = html_entity_decode($store_name, ENT_QUOTES, 'UTF-8');

		$language_code = (string)$this->config->get('config_language');

		$language_query = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "language` WHERE `language_id` = '" . (int)$customer['language_id'] . "' AND `status` = '1'");

		if ($language_query->num_rows) {
			$language_code = $language_query->row['code'];
		}

		$this->load->model('tool/image');

		$currency = (string)$this->config->get('config_currency');

		$products = [];
		$names = [];

		foreach ($alerts as $alert) {
			$product = $alert['product'];

			$names[] = html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8');

			// Twig doesn't escape in OpenCart. Names are stored HTML-encoded, so they are safe to print as they are.
			$products[] = [
				'name'      => $product['name'],
				'image'     => $product['image'] ? $this->model_tool_image->resize($product['image'], 120, 120) : '',
				'reason'    => $alert['reason'],
				'old_price' => $this->currency->format($this->tax->calculate($alert['old'], (int)$product['tax_class_id'], $this->config->get('config_tax')), $currency),
				'price'     => $this->currency->format($this->tax->calculate($alert['new'], (int)$product['tax_class_id'], $this->config->get('config_tax')), $currency),
				'href'      => $store_url . 'index.php?route=product/product&language=' . urlencode($language_code) . '&product_id=' . (int)$product['product_id'] . '&utm_source=wishlist_alert&utm_medium=email&utm_campaign=wishlist_' . $alert['reason']
			];
		}

		$replace = [
			'{firstname}'  => $customer['firstname'] ?: $this->language->get('text_there'),
			'{store_name}' => $store_name
		];

		$subject = strtr(html_entity_decode((string)$this->config->get('module_wishlist_alerts_subject'), ENT_QUOTES, 'UTF-8'), $replace);

		// The message is plain text, so it is escaped as a whole and line breaks kept.
		$message = nl2br(htmlspecialchars(strtr(html_entity_decode((string)$this->config->get('module_wishlist_alerts_message'), ENT_QUOTES, 'UTF-8'), $replace), ENT_QUOTES, 'UTF-8'));

		$html = $this->load->view('extension/wishlist_alerts/module/wishlist_alerts_mail', [
			'title'            => htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
			'message'          => $message,
			'products'         => $products,
			'store_name'       => htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'),
			'store_url'        => $store_url,
			'wishlist'         => $store_url . 'index.php?route=account/wishlist&language=' . urlencode($language_code),
			'text_price_drop'  => $this->language->get('text_price_drop'),
			'text_restock'     => $this->language->get('text_restock'),
			'text_view'        => $this->language->get('text_view'),
			'text_footer'      => sprintf($this->language->get('text_footer'), htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'))
		]);

		$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'), [
			'parameter'     => $this->config->get('config_mail_parameter'),
			'smtp_hostname' => $this->config->get('config_mail_smtp_hostname'),
			'smtp_username' => $this->config->get('config_mail_smtp_username'),
			'smtp_password' => html_entity_decode((string)$this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
			'smtp_port'     => $this->config->get('config_mail_smtp_port'),
			'smtp_timeout'  => $this->config->get('config_mail_smtp_timeout')
		]);

		$mail->setTo($customer['email']);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender($store_name);
		$mail->setSubject($subject);
		$mail->setHtml($html);
		$mail->send();

		$this->model_extension_wishlist_alerts_module_wishlist_alerts->addLog((int)$customer['customer_id'], $customer['email'], $names);

		return true;
	}
}
