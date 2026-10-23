<?php
namespace Opencart\Catalog\Controller\Extension\BackInStock\Module;
/**
 * Class BackInStock
 *
 * Back-in-Stock Notifications for OpenCart 4.1.0.x
 *
 * Events:
 *   catalog/view/product/product/after            -> product
 *   catalog/model/checkout/order.addHistory/after -> order
 * Cron:
 *   back_in_stock (hour)                          -> cron
 * Public routes:
 *   extension/back_in_stock/module/back_in_stock.subscribe (POST)
 *   extension/back_in_stock/module/back_in_stock.click&token=
 *
 * @package Opencart\Catalog\Controller\Extension\BackInStock\Module
 */
class BackInStock extends \Opencart\System\Engine\Controller {
	/**
	 * Product
	 *
	 * Adds the "Notify me" form to out-of-stock product pages.
	 *
	 * @param string               $route
	 * @param array<string, mixed> $data
	 * @param string               $output
	 *
	 * @return void
	 */
	public function product(string &$route, array &$data, string &$output): void {
		if (!$this->config->get('module_back_in_stock_status') || empty($data['product_id'])) {
			return;
		}

		try {
			$this->load->model('catalog/product');

			$product_info = $this->model_catalog_product->getProduct((int)$data['product_id']);

			if (!$product_info || (int)$product_info['quantity'] > 0) {
				return;
			}

			$this->load->language('extension/back_in_stock/module/back_in_stock');

			$form = [
				'product_id'  => (int)$data['product_id'],
				'email'       => $this->customer->isLogged() ? $this->customer->getEmail() : '',
				'action'      => $this->url->link('extension/back_in_stock/module/back_in_stock.subscribe', 'language=' . $this->config->get('config_language')),
				'hide_cart'   => $this->config->get('module_back_in_stock_hide_cart') && !$this->config->get('config_stock_checkout'),
				'text_title'  => $this->language->get('text_title'),
				'text_intro'  => $this->language->get('text_intro'),
				'text_privacy' => $this->language->get('text_privacy'),
				'entry_email' => $this->language->get('entry_email'),
				'button_notify' => $this->language->get('button_notify')
			];

			$html = $this->load->view('extension/back_in_stock/module/back_in_stock_form', $form);

			// After the add to cart form (our form can't be nested inside it), or before the tabs.
			$position = false;

			$start = strpos($output, 'id="form-product"');

			if ($start !== false) {
				$end = strpos($output, '</form>', $start);

				if ($end !== false) {
					$position = $end + strlen('</form>');
				}
			}

			if ($position === false) {
				$position = strpos($output, '<ul class="nav nav-tabs">');
			}

			if ($position === false) {
				$this->log->write('Back-in-Stock Notifications: could not find where to add the form on the product page. Is your theme missing id="form-product"?');

				return;
			}

			$output = substr_replace($output, $html, $position, 0);
		} catch (\Throwable $e) {
			$this->log->write('Back-in-Stock Notifications: ' . $e->getMessage());
		}
	}

	/**
	 * Subscribe
	 *
	 * @return void
	 */
	public function subscribe(): void {
		$this->load->language('extension/back_in_stock/module/back_in_stock');

		$json = [];

		$product_id = (int)($this->request->post['product_id'] ?? 0);
		$email = trim((string)($this->request->post['email'] ?? ''));

		if (!$this->config->get('module_back_in_stock_status') || $this->request->server['REQUEST_METHOD'] != 'POST') {
			$json['error'] = $this->language->get('error_product');
		}

		// Bots fill in every field, people can't see this one.
		if (!empty($this->request->post['website'])) {
			$json['error'] = $this->language->get('error_product');
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if (!$product_info) {
			$json['error'] = $this->language->get('error_product');
		} elseif ((int)$product_info['quantity'] > 0) {
			$json['error'] = $this->language->get('error_in_stock');
		}

		if (!$json && (oc_strlen($email) > 96 || !oc_validate_email($email))) {
			$json['error'] = $this->language->get('error_email');
		}

		$this->load->model('extension/back_in_stock/module/back_in_stock');

		$ip = oc_get_ip();

		if (!$json && $this->model_extension_back_in_stock_module_back_in_stock->getTotalRecentByIp($ip) >= 10) {
			$json['error'] = $this->language->get('error_limit');
		}

		if (!$json) {
			$store_id = (int)$this->config->get('config_store_id');

			// Same message whether or not they were already subscribed, so the form can't be used to look up emails.
			if (!$this->model_extension_back_in_stock_module_back_in_stock->getWaiting($store_id, $product_id, $email)) {
				$this->model_extension_back_in_stock_module_back_in_stock->addSubscription([
					'store_id'      => $store_id,
					'product_id'    => $product_id,
					'customer_id'   => (int)$this->customer->getId(),
					'email'         => $email,
					'firstname'     => $this->customer->isLogged() ? $this->customer->getFirstName() : '',
					'product_name'  => html_entity_decode($product_info['name'], ENT_QUOTES, 'UTF-8'),
					'language_code' => (string)$this->config->get('config_language'),
					'currency_code' => $this->session->data['currency'] ?? $this->config->get('config_currency'),
					'ip'            => $ip
				]);
			}

			$json['success'] = sprintf($this->language->get('text_success'), htmlspecialchars($email, ENT_QUOTES, 'UTF-8'));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Order
	 *
	 * Marks notified subscriptions as purchased when the customer orders the product.
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args [order_id, order_status_id, ...]
	 * @param mixed             $output
	 *
	 * @return void
	 */
	public function order(string &$route, array &$args, &$output): void {
		if (empty($args[0]) || empty($args[1])) {
			return;
		}

		try {
			$this->load->model('checkout/order');

			$order_info = $this->model_checkout_order->getOrder((int)$args[0]);

			if (!$order_info || !$order_info['email']) {
				return;
			}

			$product_ids = [];

			foreach ($this->model_checkout_order->getProducts((int)$order_info['order_id']) as $product) {
				$product_ids[] = (int)$product['product_id'];

				// A variant order also counts for its master product.
				if (!empty($product['master_id'])) {
					$product_ids[] = (int)$product['master_id'];
				}
			}

			$this->load->model('extension/back_in_stock/module/back_in_stock');

			$this->model_extension_back_in_stock_module_back_in_stock->editPurchased((int)$order_info['store_id'], $order_info['email'], $product_ids, (int)$order_info['order_id'], (float)$order_info['total']);
		} catch (\Throwable $e) {
			$this->log->write('Back-in-Stock Notifications: ' . $e->getMessage());
		}
	}

	/**
	 * Cron
	 *
	 * Called hourly by OpenCart's cron.php (Extensions > Cron Jobs).
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
		if (!$this->config->get('module_back_in_stock_status')) {
			return;
		}

		$this->load->model('extension/back_in_stock/module/back_in_stock');

		$this->model_extension_back_in_stock_module_back_in_stock->clean((int)$this->config->get('module_back_in_stock_retention'));

		if (!$this->config->get('config_mail_engine')) {
			return;
		}

		$subscriptions = $this->model_extension_back_in_stock_module_back_in_stock->getDueSubscriptions((int)$this->config->get('module_back_in_stock_max_emails') ?: 100);

		foreach ($subscriptions as $subscription) {
			try {
				if ($this->send($subscription)) {
					$this->model_extension_back_in_stock_module_back_in_stock->editNotified((int)$subscription['back_in_stock_id']);
				}
			} catch (\Throwable $e) {
				$this->log->write('Back-in-Stock Notifications: could not email subscription ' . $subscription['back_in_stock_id'] . ': ' . $e->getMessage());
			}
		}
	}

	/**
	 * Click
	 *
	 * The link in the notification email. Counts the click and redirects to the product page.
	 *
	 * @return void
	 */
	public function click(): void {
		$token = (string)($this->request->get['token'] ?? '');

		$this->load->model('extension/back_in_stock/module/back_in_stock');

		$subscription_info = $token ? $this->model_extension_back_in_stock_module_back_in_stock->getSubscriptionByToken($token) : [];

		if (!$subscription_info) {
			$this->response->redirect($this->url->link('common/home', 'language=' . $this->config->get('config_language'), true));

			return;
		}

		$this->model_extension_back_in_stock_module_back_in_stock->addClick((int)$subscription_info['back_in_stock_id']);

		if ($subscription_info['currency_code'] && $this->currency->has($subscription_info['currency_code'])) {
			$this->session->data['currency'] = $subscription_info['currency_code'];
		}

		$this->response->redirect($this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)$subscription_info['product_id'] . '&utm_source=back_in_stock&utm_medium=email&utm_campaign=back_in_stock', true));
	}

	/**
	 * Send
	 *
	 * @param array<string, mixed> $subscription
	 *
	 * @throws \Exception
	 *
	 * @return bool false if the product can't be shown in that store right now
	 */
	private function send(array $subscription): bool {
		$this->load->language('extension/back_in_stock/module/back_in_stock');

		// Store
		$store_name = (string)$this->config->get('config_name');
		$store_url = (string)$this->config->get('config_url');

		if ($subscription['store_id']) {
			$this->load->model('setting/store');

			$store_info = $this->model_setting_store->getStore((int)$subscription['store_id']);

			if ($store_info) {
				$store_name = $store_info['name'];
				$store_url = $store_info['url'];
			}
		}

		$store_name = html_entity_decode($store_name, ENT_QUOTES, 'UTF-8');

		// Product, read as that store so prices and store assignment are right.
		$this->load->model('catalog/product');

		$store_id = $this->config->get('config_store_id');

		$this->config->set('config_store_id', (int)$subscription['store_id']);

		$product_info = $this->model_catalog_product->getProduct((int)$subscription['product_id']);

		$this->config->set('config_store_id', $store_id);

		if (!$product_info) {
			return false;
		}

		$currency = ($subscription['currency_code'] && $this->currency->has($subscription['currency_code'])) ? $subscription['currency_code'] : $this->config->get('config_currency');

		$price = (float)($product_info['special'] ?: $product_info['price']);

		$product_price = $this->currency->format($this->tax->calculate($price, (int)$product_info['tax_class_id'], $this->config->get('config_tax')), $currency);

		$this->load->model('tool/image');

		$product_image = $product_info['image'] ? $this->model_tool_image->resize($product_info['image'], 200, 200) : '';

		$product_name = $subscription['product_name'] ?: html_entity_decode($product_info['name'], ENT_QUOTES, 'UTF-8');

		$language = $subscription['language_code'] ?: $this->config->get('config_language');

		$product_link = $store_url . 'index.php?route=extension/back_in_stock/module/back_in_stock.click&language=' . urlencode($language) . '&token=' . $subscription['token'];

		$product_html = $this->load->view('extension/back_in_stock/module/back_in_stock_product', [
			'image' => $product_image,
			'name'  => $product_name,
			'price' => $product_price,
			'href'  => $product_link
		]);

		$firstname = $subscription['firstname'] ?: $this->language->get('text_there');

		$replace = [
			'{firstname}'          => htmlspecialchars($firstname, ENT_QUOTES, 'UTF-8'),
			'{store_name}'         => htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'),
			'{product_name}'       => htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'),
			'{product}'            => $product_html,
			'{product_image}'      => htmlspecialchars($product_image, ENT_QUOTES, 'UTF-8'),
			'{product_price}'      => $product_price,
			'{product_link}'       => htmlspecialchars($product_link, ENT_QUOTES, 'UTF-8'),
			'%7Bproduct_link%7D'   => htmlspecialchars($product_link, ENT_QUOTES, 'UTF-8')
		];

		$subject = strtr(html_entity_decode((string)$this->config->get('module_back_in_stock_subject'), ENT_QUOTES, 'UTF-8'), ['{firstname}' => $firstname, '{store_name}' => $store_name, '{product_name}' => $product_name]);
		$message = strtr(html_entity_decode((string)$this->config->get('module_back_in_stock_message'), ENT_QUOTES, 'UTF-8'), $replace);

		$logo = '';

		if ($this->config->get('config_logo') && is_file(DIR_IMAGE . html_entity_decode($this->config->get('config_logo'), ENT_QUOTES, 'UTF-8'))) {
			$logo = $store_url . 'image/' . $this->config->get('config_logo');
		}

		$html = $this->load->view('extension/back_in_stock/module/back_in_stock_mail', [
			'title'       => htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
			'store_name'  => htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'),
			'store_url'   => $store_url,
			'logo'        => $logo,
			'message'     => $message,
			'text_footer' => sprintf($this->language->get('text_footer'), htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'), htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'))
		]);

		$mail_option = [
			'parameter'     => $this->config->get('config_mail_parameter'),
			'smtp_hostname' => $this->config->get('config_mail_smtp_hostname'),
			'smtp_username' => $this->config->get('config_mail_smtp_username'),
			'smtp_password' => html_entity_decode((string)$this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
			'smtp_port'     => $this->config->get('config_mail_smtp_port'),
			'smtp_timeout'  => $this->config->get('config_mail_smtp_timeout')
		];

		$recipients = [$subscription['email']];

		if ($this->config->get('module_back_in_stock_bcc')) {
			$recipients[] = $this->config->get('module_back_in_stock_bcc');
		}

		foreach ($recipients as $recipient) {
			$mail = new \Opencart\System\Library\Mail($this->config->get('config_mail_engine'), $mail_option);
			$mail->setTo($recipient);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender($store_name);
			$mail->setSubject($subject);
			$mail->setHtml($html);
			$mail->send();
		}

		return true;
	}
}
