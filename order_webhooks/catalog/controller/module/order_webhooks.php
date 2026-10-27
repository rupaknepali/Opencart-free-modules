<?php
namespace Opencart\Catalog\Controller\Extension\OrderWebhooks\Module;
/**
 * Class OrderWebhooks
 *
 * Order Webhooks for OpenCart 4.1.0.x
 *
 * Events:
 *   catalog/model/checkout/order.addHistory/before  -> orderBefore (remembers the old status)
 *   catalog/model/checkout/order.addHistory/after   -> orderAfter (order.created / order.status_changed)
 *   catalog/model/account/customer.addCustomer/after -> customer (customer.created)
 * Cron:
 *   order_webhooks (every run of cron.php)           -> cron (retries)
 *
 * Admin status changes also arrive here, because OpenCart 4 admin changes order history through the catalog API.
 *
 * @package Opencart\Catalog\Controller\Extension\OrderWebhooks\Module
 */
class OrderWebhooks extends \Opencart\System\Engine\Controller {
	/**
	 * Order status before addHistory(). order_id => order_status_id
	 *
	 * @var array<int, int>
	 */
	private static array $previous = [];

	/**
	 * Delivery IDs queued in this request, sent after the response.
	 *
	 * @var array<int, int>
	 */
	private static array $queued = [];

	/**
	 * Order Before
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args [order_id, order_status_id, ...]
	 *
	 * @return void
	 */
	public function orderBefore(string &$route, array &$args): void {
		if (!$this->config->get('module_order_webhooks_status') || empty($args[0])) {
			return;
		}

		$query = $this->db->query("SELECT `order_status_id` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$args[0] . "'");

		self::$previous[(int)$args[0]] = $query->num_rows ? (int)$query->row['order_status_id'] : 0;
	}

	/**
	 * Order After
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args
	 * @param mixed             $output
	 *
	 * @return void
	 */
	public function orderAfter(string &$route, array &$args, &$output): void {
		if (!$this->config->get('module_order_webhooks_status') || empty($args[0]) || !isset(self::$previous[(int)$args[0]])) {
			return;
		}

		$order_id = (int)$args[0];
		$old_status_id = self::$previous[$order_id];
		$new_status_id = (int)$args[1];

		unset(self::$previous[$order_id]);

		if (!$old_status_id && $new_status_id) {
			$event = 'order.created';
		} elseif ($old_status_id != $new_status_id) {
			$event = 'order.status_changed';
		} else {
			return;
		}

		try {
			$data = $this->getOrderData($order_id);

			if ($data) {
				$data['previous_status_id'] = $old_status_id;

				$this->queue($event, $data);
			}
		} catch (\Throwable $e) {
			$this->log->write('Order Webhooks: ' . $e->getMessage());
		}
	}

	/**
	 * Customer
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args [data]
	 * @param mixed             $output customer_id
	 *
	 * @return void
	 */
	public function customer(string &$route, array &$args, &$output): void {
		if (!$this->config->get('module_order_webhooks_status') || !$output) {
			return;
		}

		try {
			$query = $this->db->query("SELECT `customer_id`, `customer_group_id`, `store_id`, `language_id`, `firstname`, `lastname`, `email`, `telephone`, `newsletter`, `status`, `date_added` FROM `" . DB_PREFIX . "customer` WHERE `customer_id` = '" . (int)$output . "'");

			if ($query->num_rows) {
				$customer = $query->row;

				$customer['customer_id'] = (int)$customer['customer_id'];
				$customer['customer_group_id'] = (int)$customer['customer_group_id'];
				$customer['store_id'] = (int)$customer['store_id'];
				$customer['language_id'] = (int)$customer['language_id'];
				$customer['newsletter'] = (bool)$customer['newsletter'];
				$customer['status'] = (bool)$customer['status'];

				$this->queue('customer.created', $customer);
			}
		} catch (\Throwable $e) {
			$this->log->write('Order Webhooks: ' . $e->getMessage());
		}
	}

	/**
	 * Cron
	 *
	 * Retries deliveries that failed or were not sent yet. Called by OpenCart's cron.php.
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
		$this->load->model('extension/order_webhooks/module/order_webhooks');

		$this->model_extension_order_webhooks_module_order_webhooks->clean(max(7, (int)$this->config->get('module_order_webhooks_retention')));

		if (!$this->config->get('module_order_webhooks_status')) {
			return;
		}

		foreach ($this->model_extension_order_webhooks_module_order_webhooks->getDueDeliveries(50) as $delivery) {
			$this->deliver($delivery, 10);
		}
	}

	/**
	 * Flush
	 *
	 * Sends the deliveries queued in this request. Runs after PHP has sent the page to the customer when PHP-FPM is used.
	 *
	 * @return void
	 */
	public function flush(): void {
		if (!self::$queued) {
			return;
		}

		if (function_exists('fastcgi_finish_request')) {
			// The registry response has already been sent at this point.
			fastcgi_finish_request();
		}

		$this->load->model('extension/order_webhooks/module/order_webhooks');

		foreach (self::$queued as $webhook_delivery_id) {
			$delivery = $this->model_extension_order_webhooks_module_order_webhooks->getDelivery($webhook_delivery_id);

			// The cron may have picked it up already.
			if ($delivery && !$delivery['status'] && !$delivery['attempts']) {
				$this->deliver($delivery, function_exists('fastcgi_finish_request') ? 10 : 3);
			}
		}

		self::$queued = [];
	}

	/**
	 * Queue
	 *
	 * @param string               $event
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	private function queue(string $event, array $data): void {
		$endpoints = (array)$this->config->get('module_order_webhooks_endpoint');

		$payload = json_encode([
			'type'      => $event,
			'timestamp' => date('c'),
			'data'      => $data
		], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);

		$this->load->model('extension/order_webhooks/module/order_webhooks');

		foreach ($endpoints as $endpoint) {
			if (!empty($endpoint['status']) && !empty($endpoint['url']) && in_array($event, (array)($endpoint['event'] ?? []))) {
				$queued = !self::$queued;

				self::$queued[] = $this->model_extension_order_webhooks_module_order_webhooks->addDelivery(html_entity_decode($endpoint['url'], ENT_QUOTES, 'UTF-8'), $event, (string)$payload);

				if ($queued) {
					register_shutdown_function([$this, 'flush']);
				}
			}
		}
	}

	/**
	 * Deliver
	 *
	 * @param array<string, mixed> $delivery
	 * @param int                  $timeout
	 *
	 * @return void
	 */
	private function deliver(array $delivery, int $timeout): void {
		$secret = '';

		foreach ((array)$this->config->get('module_order_webhooks_endpoint') as $endpoint) {
			if (html_entity_decode($endpoint['url'] ?? '', ENT_QUOTES, 'UTF-8') == $delivery['url']) {
				$secret = (string)$endpoint['secret'];

				break;
			}
		}

		// Endpoint was removed after the delivery was queued.
		if ($secret === '') {
			$result = ['code' => 0, 'body' => '', 'error' => 'Endpoint no longer configured'];
		} else {
			$result = \Opencart\System\Library\Extension\OrderWebhooks\Sender::send($delivery['url'], $secret, $delivery['message_id'], $delivery['payload'], $timeout);
		}

		$this->model_extension_order_webhooks_module_order_webhooks->editResult($delivery, $result);
	}

	/**
	 * Get Order Data
	 *
	 * @param int $order_id
	 *
	 * @return array<string, mixed>
	 */
	private function getOrderData(int $order_id): array {
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if (!$order_info) {
			return [];
		}

		$status = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "order_status` WHERE `order_status_id` = '" . (int)$order_info['order_status_id'] . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");

		$products = [];

		foreach ($this->model_checkout_order->getProducts($order_id) as $product) {
			$options = [];

			foreach ($this->model_checkout_order->getOptions($order_id, (int)$product['order_product_id']) as $option) {
				$options[] = [
					'name'  => $option['name'],
					'value' => $option['value']
				];
			}

			$products[] = [
				'product_id' => (int)$product['product_id'],
				'master_id'  => (int)$product['master_id'],
				'name'       => html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8'),
				'model'      => $product['model'],
				'quantity'   => (int)$product['quantity'],
				'price'      => (float)$product['price'],
				'tax'        => (float)$product['tax'],
				'total'      => (float)$product['total'],
				'options'    => $options
			];
		}

		$totals = [];

		foreach ($this->model_checkout_order->getTotals($order_id) as $total) {
			$totals[] = [
				'code'  => $total['code'],
				'title' => html_entity_decode($total['title'], ENT_QUOTES, 'UTF-8'),
				'value' => (float)$total['value']
			];
		}

		$address = function (string $type) use ($order_info): array {
			return [
				'firstname'  => $order_info[$type . '_firstname'],
				'lastname'   => $order_info[$type . '_lastname'],
				'company'    => $order_info[$type . '_company'],
				'address_1'  => $order_info[$type . '_address_1'],
				'address_2'  => $order_info[$type . '_address_2'],
				'city'       => $order_info[$type . '_city'],
				'postcode'   => $order_info[$type . '_postcode'],
				'zone'       => $order_info[$type . '_zone'],
				'country'    => $order_info[$type . '_country'],
				'iso_code_2' => $order_info[$type . '_iso_code_2'] ?? ''
			];
		};

		$payment_method = $order_info['payment_method'] ?? [];
		$shipping_method = $order_info['shipping_method'] ?? [];

		if (is_string($payment_method)) {
			$payment_method = json_decode($payment_method, true);
		}

		if (is_string($shipping_method)) {
			$shipping_method = json_decode($shipping_method, true);
		}

		return [
			'order_id'          => (int)$order_info['order_id'],
			'store_id'          => (int)$order_info['store_id'],
			'store_name'        => html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'),
			'order_status_id'   => (int)$order_info['order_status_id'],
			'order_status'      => $status->num_rows ? $status->row['name'] : '',
			'currency_code'     => $order_info['currency_code'],
			'currency_value'    => (float)$order_info['currency_value'],
			'total'             => (float)$order_info['total'],
			'customer'          => [
				'customer_id'       => (int)$order_info['customer_id'],
				'customer_group_id' => (int)$order_info['customer_group_id'],
				'firstname'         => $order_info['firstname'],
				'lastname'          => $order_info['lastname'],
				'email'             => $order_info['email'],
				'telephone'         => $order_info['telephone']
			],
			'payment_address'   => $address('payment'),
			'shipping_address'  => $shipping_method ? $address('shipping') : null,
			'payment_method'    => is_array($payment_method) ? ($payment_method['name'] ?? '') : '',
			'shipping_method'   => is_array($shipping_method) ? ($shipping_method['name'] ?? '') : '',
			'comment'           => $order_info['comment'],
			'products'          => $products,
			'totals'            => $totals,
			'date_added'        => $order_info['date_added']
		];
	}
}
