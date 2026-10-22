<?php
namespace Opencart\Catalog\Controller\Extension\AbandonedCart\Module;
/**
 * Class AbandonedCart
 *
 * Abandoned Cart Recovery for OpenCart 4.1.0.x
 *
 * Events:
 *   catalog/controller/checkout/register.save/after  -> capture
 *   catalog/controller/checkout/cart.add/after       -> capture
 *   catalog/controller/checkout/cart.edit/after      -> capture
 *   catalog/controller/checkout/cart.remove/after    -> capture
 *   catalog/model/checkout/order.addHistory/after    -> order
 * Cron:
 *   abandoned_cart (hour)                            -> cron
 * Public routes:
 *   extension/abandoned_cart/module/abandoned_cart.restore&token=
 *   extension/abandoned_cart/module/abandoned_cart.unsubscribe&token=
 *
 * @package Opencart\Catalog\Controller\Extension\AbandonedCart\Module
 */
class AbandonedCart extends \Opencart\System\Engine\Controller {
	/**
	 * Capture
	 *
	 * Saves a snapshot of the cart once we know the customer's email address.
	 *
	 * @param string            $route
	 * @param array<int, mixed> $args
	 * @param mixed             $output
	 *
	 * @return void
	 */
	public function capture(string &$route, array &$args, &$output): void {
		if (!$this->config->get('module_abandoned_cart_status')) {
			return;
		}

		// Never let this extension break the checkout.
		try {
			$this->snapshot($route);
		} catch (\Throwable $e) {
			$this->log->write('Abandoned Cart Recovery: ' . $e->getMessage());
		}
	}

	/**
	 * Order
	 *
	 * Closes the open cart when the customer places an order.
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

			if ($order_info && $order_info['email']) {
				$this->load->model('extension/abandoned_cart/module/abandoned_cart');

				$this->model_extension_abandoned_cart_module_abandoned_cart->closeCart((int)$order_info['store_id'], $order_info['email'], (int)$order_info['order_id'], (float)$order_info['total']);
			}
		} catch (\Throwable $e) {
			$this->log->write('Abandoned Cart Recovery: ' . $e->getMessage());
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
		if (!$this->config->get('module_abandoned_cart_status')) {
			return;
		}

		$this->load->model('extension/abandoned_cart/module/abandoned_cart');

		$this->model_extension_abandoned_cart_module_abandoned_cart->expireAndClean((int)$this->config->get('module_abandoned_cart_retention'));

		// Enabled stages, key => delay in hours
		$stages = [];

		foreach ((array)$this->config->get('module_abandoned_cart_stage') as $key => $stage) {
			if (!empty($stage['status'])) {
				$stages[(int)$key] = max(0, (int)$stage['delay']);
			}
		}

		if (!$stages || !$this->config->get('config_mail_engine')) {
			return;
		}

		ksort($stages);

		$carts = $this->model_extension_abandoned_cart_module_abandoned_cart->getDueCarts(min($stages), max(array_keys($stages)), (float)$this->config->get('module_abandoned_cart_min_total'), (bool)$this->config->get('module_abandoned_cart_consent'));

		foreach ($carts as $cart) {
			if ($this->model_extension_abandoned_cart_module_abandoned_cart->isUnsubscribed($cart['email'])) {
				$this->model_extension_abandoned_cart_module_abandoned_cart->editStatus((int)$cart['abandoned_cart_id'], 3);

				continue;
			}

			// Ordered by another route (e.g. admin order, another device) - stop emailing.
			$order_info = $this->model_extension_abandoned_cart_module_abandoned_cart->getOrderSince((int)$cart['store_id'], $cart['email'], $cart['date_added']);

			if ($order_info) {
				$this->model_extension_abandoned_cart_module_abandoned_cart->closeCart((int)$cart['store_id'], $cart['email'], (int)$order_info['order_id'], (float)$order_info['total']);

				continue;
			}

			// Send only the latest stage that is due, so a delayed cron never sends a burst of emails.
			$next = 0;

			foreach ($stages as $key => $delay) {
				if ($key > (int)$cart['stage'] && (int)$cart['idle'] >= $delay * 60) {
					$next = $key;
				}
			}

			if (!$next) {
				continue;
			}

			try {
				$coupon_code = $this->send($cart, $next);

				$this->model_extension_abandoned_cart_module_abandoned_cart->editStage((int)$cart['abandoned_cart_id'], $next, $coupon_code);
			} catch (\Throwable $e) {
				$this->log->write('Abandoned Cart Recovery: could not email cart ' . $cart['abandoned_cart_id'] . ': ' . $e->getMessage());
			}
		}
	}

	/**
	 * Restore
	 *
	 * The link in the reminder email. Refills the cart and redirects to the cart page.
	 *
	 * @return void
	 */
	public function restore(): void {
		$token = (string)($this->request->get['token'] ?? '');

		$language = $this->config->get('config_language');

		$utm = '';

		foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $key) {
			if (!empty($this->request->get[$key])) {
				$utm .= '&' . $key . '=' . urlencode(html_entity_decode((string)$this->request->get[$key], ENT_QUOTES, 'UTF-8'));
			}
		}

		$this->load->model('extension/abandoned_cart/module/abandoned_cart');

		$cart_info = $token ? $this->model_extension_abandoned_cart_module_abandoned_cart->getCartByToken($token) : [];

		// Recovered and ordered carts are not restored again.
		if ($cart_info && !in_array((int)$cart_info['status'], [1, 2])) {
			$this->model_extension_abandoned_cart_module_abandoned_cart->addClick((int)$cart_info['abandoned_cart_id']);

			if ($cart_info['currency_code'] && $this->currency->has($cart_info['currency_code'])) {
				$this->session->data['currency'] = $cart_info['currency_code'];
			}

			// Only refill an empty cart, so clicking twice doesn't double the quantities.
			if (!$this->cart->hasProducts()) {
				$this->load->model('catalog/product');

				foreach ((array)json_decode($cart_info['products'], true) as $product) {
					$product_info = $this->model_catalog_product->getProduct((int)$product['product_id']);

					if ($product_info) {
						$this->cart->add((int)$product['product_id'], max(1, (int)$product['quantity']), (array)($product['option'] ?? []), (int)($product['subscription_plan_id'] ?? 0));
					}
				}
			}

			if ($cart_info['coupon_code']) {
				$this->session->data['coupon'] = $cart_info['coupon_code'];
			}
		}

		$this->response->redirect($this->url->link('checkout/cart', 'language=' . $language . $utm, true));
	}

	/**
	 * Unsubscribe
	 *
	 * @return void
	 */
	public function unsubscribe(): void {
		$this->load->language('extension/abandoned_cart/module/abandoned_cart');

		$token = (string)($this->request->get['token'] ?? '');

		$this->load->model('extension/abandoned_cart/module/abandoned_cart');

		$cart_info = $token ? $this->model_extension_abandoned_cart_module_abandoned_cart->getCartByToken($token) : [];

		if ($cart_info) {
			$this->model_extension_abandoned_cart_module_abandoned_cart->unsubscribe($cart_info['email']);

			$data['text_message'] = sprintf($this->language->get('text_unsubscribed'), htmlspecialchars($cart_info['email'], ENT_QUOTES, 'UTF-8'));
		} else {
			$data['text_message'] = $this->language->get('text_unsubscribe_invalid');
		}

		$this->document->setTitle($this->language->get('heading_unsubscribe'));

		$data['heading_title'] = $this->language->get('heading_unsubscribe');

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/abandoned_cart/module/abandoned_cart_unsubscribe', $data));
	}

	/**
	 * Snapshot
	 *
	 * @param string $route
	 *
	 * @return void
	 */
	private function snapshot(string $route): void {
		// Who is this?
		if ($this->customer->isLogged()) {
			$email = $this->customer->getEmail();
			$firstname = $this->customer->getFirstName();
			$consent = $this->customer->getNewsletter();
		} elseif (!empty($this->session->data['customer']['email'])) {
			$email = $this->session->data['customer']['email'];
			$firstname = $this->session->data['customer']['firstname'] ?? '';
			$consent = !empty($this->session->data['abandoned_cart_consent']);
		} else {
			return;
		}

		// The guest checkout form has a newsletter switch; remember the answer.
		if ($route == 'checkout/register.save' && isset($this->session->data['customer'])) {
			$consent = !empty($this->request->post['newsletter']);

			$this->session->data['abandoned_cart_consent'] = $consent;
		}

		if (!oc_validate_email($email)) {
			return;
		}

		$this->load->model('extension/abandoned_cart/module/abandoned_cart');

		$store_id = (int)$this->config->get('config_store_id');

		$cart_info = $this->model_extension_abandoned_cart_module_abandoned_cart->getOpenCart($store_id, $email);

		if (!$this->cart->hasProducts()) {
			if ($cart_info) {
				$this->model_extension_abandoned_cart_module_abandoned_cart->deleteOpenCart((int)$cart_info['abandoned_cart_id']);
			}

			return;
		}

		// Raw cart rows keep the exact option array needed to re-add products.
		$rows = [];

		foreach ($this->model_extension_abandoned_cart_module_abandoned_cart->getSessionCartRows() as $row) {
			$rows[$row['cart_id']] = $row;
		}

		$this->load->model('checkout/cart');

		$products = [];

		foreach ($this->model_checkout_cart->getProducts() as $product) {
			$options = [];

			foreach ($product['option'] as $option) {
				if ($option['type'] != 'file') {
					$options[] = [
						'name'  => $option['name'],
						'value' => oc_strlen($option['value']) > 30 ? oc_substr($option['value'], 0, 30) . '..' : $option['value']
					];
				}
			}

			$row = $rows[$product['cart_id']] ?? [];

			$products[] = [
				'product_id'           => (int)$product['product_id'],
				'name'                 => $product['name'],
				'model'                => $product['model'],
				'image'                => $product['image'],
				'quantity'             => (int)$product['quantity'],
				'option'               => $row ? (json_decode($row['option'], true) ?: []) : [],
				'option_text'          => $options,
				'subscription_plan_id' => (int)($row['subscription_plan_id'] ?? 0),
				'price_text'           => $product['price_text'],
				'total_text'           => $product['total_text']
			];
		}

		$currency = $this->session->data['currency'] ?? $this->config->get('config_currency');

		$total = $this->cart->getTotal();

		$data = [
			'store_id'      => $store_id,
			'customer_id'   => (int)$this->customer->getId(),
			'session_id'    => $this->session->getId(),
			'email'         => $email,
			'firstname'     => $firstname,
			'language_id'   => (int)$this->config->get('config_language_id'),
			'language_code' => (string)$this->config->get('config_language'),
			'currency_code' => $currency,
			'products'      => $products,
			'total'         => $total,
			'total_text'    => $this->currency->format($total, $currency),
			'consent'       => $consent
		];

		if ($cart_info) {
			$this->model_extension_abandoned_cart_module_abandoned_cart->editCart((int)$cart_info['abandoned_cart_id'], $data);
		} else {
			$this->model_extension_abandoned_cart_module_abandoned_cart->addCart($data);
		}
	}

	/**
	 * Send
	 *
	 * @param array<string, mixed> $cart
	 * @param int                  $stage
	 *
	 * @throws \Exception
	 *
	 * @return string coupon code used in this email, or the existing one
	 */
	private function send(array $cart, int $stage): string {
		$this->load->language('extension/abandoned_cart/module/abandoned_cart');

		$settings = (array)$this->config->get('module_abandoned_cart_stage');
		$setting = $settings[$stage];

		// Store
		$store_name = (string)$this->config->get('config_name');
		$store_url = (string)$this->config->get('config_url');

		if ($cart['store_id']) {
			$this->load->model('setting/store');

			$store_info = $this->model_setting_store->getStore((int)$cart['store_id']);

			if ($store_info) {
				$store_name = $store_info['name'];
				$store_url = $store_info['url'];
			}
		}

		$store_name = html_entity_decode($store_name, ENT_QUOTES, 'UTF-8');

		// Coupon
		$coupon_code = (string)$cart['coupon_code'];
		$coupon = '';
		$coupon_discount = '';
		$coupon_expiry = '';

		$discount = (float)$this->config->get('module_abandoned_cart_coupon_discount');

		if ((int)$this->config->get('module_abandoned_cart_coupon_stage') == $stage && $discount > 0 && !$coupon_code) {
			$coupon_info = $this->model_extension_abandoned_cart_module_abandoned_cart->addCoupon($discount, (int)$this->config->get('module_abandoned_cart_coupon_days'));

			$coupon_code = $coupon_info['code'];
		}

		if ($coupon_code) {
			$coupon_info = $this->model_extension_abandoned_cart_module_abandoned_cart->getCoupon($coupon_code);

			if ($coupon_info) {
				$coupon_discount = rtrim(rtrim(number_format((float)$coupon_info['discount'], 2, '.', ''), '0'), '.') . '%';
				$coupon_expiry = date($this->language->get('date_format_short'), strtotime($coupon_info['date_end']));
				$coupon = sprintf($this->language->get('text_coupon'), $coupon_code, $coupon_discount, $coupon_expiry);
			}
		}

		// Links
		$language = $cart['language_code'] ?: $this->config->get('config_language');

		$restore_link = $store_url . 'index.php?route=extension/abandoned_cart/module/abandoned_cart.restore&language=' . urlencode($language) . '&token=' . $cart['token'] . '&utm_source=abandoned_cart&utm_medium=email&utm_campaign=abandoned_cart&utm_content=email_' . $stage;
		$unsubscribe_link = $store_url . 'index.php?route=extension/abandoned_cart/module/abandoned_cart.unsubscribe&language=' . urlencode($language) . '&token=' . $cart['token'];

		// Products table
		$this->load->model('tool/image');

		$products = [];

		foreach ((array)json_decode($cart['products'], true) as $product) {
			$products[] = [
				'thumb' => $product['image'] ? $this->model_tool_image->resize($product['image'], 80, 80) : '',
				'name'  => $product['name'],
				'option' => $product['option_text'] ?? [],
				'quantity' => $product['quantity'],
				'total' => $product['total_text']
			];
		}

		$cart_html = $this->load->view('extension/abandoned_cart/module/abandoned_cart_items', [
			'products'      => $products,
			'cart_total'    => $cart['total_text'],
			'column_total'  => $this->language->get('column_total'),
			'text_total'    => $this->language->get('text_total')
		]);

		$firstname = $cart['firstname'] ?: $this->language->get('text_there');

		$replace = [
			'{firstname}'          => htmlspecialchars($firstname, ENT_QUOTES, 'UTF-8'),
			'{store_name}'         => htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'),
			'{cart}'               => $cart_html,
			'{cart_total}'         => $cart['total_text'],
			'{restore_link}'       => htmlspecialchars($restore_link, ENT_QUOTES, 'UTF-8'),
			'%7Brestore_link%7D'   => htmlspecialchars($restore_link, ENT_QUOTES, 'UTF-8'),
			'{coupon}'             => $coupon,
			'{coupon_code}'        => $coupon_code,
			'{coupon_discount}'    => $coupon_discount,
			'{coupon_expiry}'      => $coupon_expiry,
			'{unsubscribe_link}'   => htmlspecialchars($unsubscribe_link, ENT_QUOTES, 'UTF-8'),
			'%7Bunsubscribe_link%7D' => htmlspecialchars($unsubscribe_link, ENT_QUOTES, 'UTF-8')
		];

		$subject = strtr(html_entity_decode($setting['subject'], ENT_QUOTES, 'UTF-8'), ['{firstname}' => $firstname, '{store_name}' => $store_name]);
		$message = strtr(html_entity_decode($setting['message'], ENT_QUOTES, 'UTF-8'), $replace);

		$logo = '';

		if ($this->config->get('config_logo') && is_file(DIR_IMAGE . html_entity_decode($this->config->get('config_logo'), ENT_QUOTES, 'UTF-8'))) {
			$logo = $store_url . 'image/' . $this->config->get('config_logo');
		}

		$html = $this->load->view('extension/abandoned_cart/module/abandoned_cart_mail', [
			'title'            => htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
			'store_name'       => htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'),
			'store_url'        => $store_url,
			'logo'             => $logo,
			'message'          => $message,
			'text_footer'      => sprintf($this->language->get('text_footer'), htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8')),
			'text_unsubscribe' => $this->language->get('text_unsubscribe'),
			'unsubscribe'      => htmlspecialchars($unsubscribe_link, ENT_QUOTES, 'UTF-8')
		]);

		$mail_option = [
			'parameter'     => $this->config->get('config_mail_parameter'),
			'smtp_hostname' => $this->config->get('config_mail_smtp_hostname'),
			'smtp_username' => $this->config->get('config_mail_smtp_username'),
			'smtp_password' => html_entity_decode((string)$this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
			'smtp_port'     => $this->config->get('config_mail_smtp_port'),
			'smtp_timeout'  => $this->config->get('config_mail_smtp_timeout')
		];

		$recipients = [$cart['email']];

		if ($this->config->get('module_abandoned_cart_bcc')) {
			$recipients[] = $this->config->get('module_abandoned_cart_bcc');
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

		return $coupon_code;
	}
}
