<?php
namespace Opencart\Catalog\Controller\Extension\ReviewRequest\Module;
/**
 * Class ReviewRequest
 *
 * Review Request Emails for OpenCart 4.1.0.x
 *
 * Cron:
 *   review_request (hour) -> cron
 * Public routes:
 *   extension/review_request/module/review_request&token=[&product_id=&rating=]
 *   extension/review_request/module/review_request.save (POST)
 *   extension/review_request/module/review_request.unsubscribe&token=
 *
 * @package Opencart\Catalog\Controller\Extension\ReviewRequest\Module
 */
class ReviewRequest extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * The review page the email links to. The token proves the visitor bought these products.
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/review_request/module/review_request');

		// Personal page, keep it out of search engines.
		$this->response->addHeader('X-Robots-Tag: noindex, nofollow');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => ''
		];

		$token = (string)($this->request->get['token'] ?? '');

		$this->load->model('extension/review_request/module/review_request');

		$request_info = $token ? $this->model_extension_review_request_module_review_request->getRequestByToken($token) : [];

		$data['products'] = [];

		if ($request_info && $this->config->get('config_review_status')) {
			$this->model_extension_review_request_module_review_request->addClick((int)$request_info['review_request_id']);

			$selected_id = (int)($this->request->get['product_id'] ?? 0);
			$selected_rating = (int)($this->request->get['rating'] ?? 0);

			$this->load->model('catalog/product');
			$this->load->model('tool/image');

			foreach ((array)json_decode($request_info['products'], true) as $product) {
				$product_info = $this->model_catalog_product->getProduct((int)$product['product_id']);

				if (!$product_info) {
					continue;
				}

				if ($product_info['image'] && is_file(DIR_IMAGE . html_entity_decode($product_info['image'], ENT_QUOTES, 'UTF-8'))) {
					$thumb = $this->model_tool_image->resize($product_info['image'], 100, 100);
				} else {
					$thumb = $this->model_tool_image->resize('placeholder.png', 100, 100);
				}

				$data['products'][] = [
					'product_id' => (int)$product['product_id'],
					'name'       => $product_info['name'],
					'thumb'      => $thumb,
					'href'       => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . (int)$product['product_id']),
					'reviewed'   => !empty($product['review_id']),
					'rating'     => !empty($product['review_id']) ? (int)$product['rating'] : ((int)$product['product_id'] == $selected_id && $selected_rating >= 1 && $selected_rating <= 5 ? $selected_rating : 0),
					'selected'   => (int)$product['product_id'] == $selected_id
				];
			}

			$data['text_intro'] = sprintf($this->language->get('text_intro'), (int)$request_info['order_id']);
		} elseif ($request_info) {
			$data['text_intro'] = $this->language->get('text_disabled');
		} else {
			$data['text_intro'] = $this->language->get('text_invalid');
		}

		$data['token'] = $token;
		$data['author'] = $request_info['author'] ?? '';
		$data['save'] = $this->url->link('extension/review_request/module/review_request.save', 'language=' . $this->config->get('config_language'));
		$data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/review_request/module/review_request', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/review_request/module/review_request');

		$json = [];

		$post = $this->request->post + ['token' => '', 'product_id' => 0, 'author' => '', 'text' => '', 'rating' => 0];

		$this->load->model('extension/review_request/module/review_request');

		$request_info = $this->model_extension_review_request_module_review_request->getRequestByToken((string)$post['token']);

		$products = $request_info ? (array)json_decode($request_info['products'], true) : [];

		$key = null;

		foreach ($products as $index => $product) {
			if ((int)$product['product_id'] == (int)$post['product_id']) {
				$key = $index;
			}
		}

		if (!$this->config->get('config_review_status')) {
			$json['error']['warning'] = $this->language->get('text_disabled');
		} elseif (!$request_info || $key === null) {
			$json['error']['warning'] = $this->language->get('text_invalid');
		} elseif (!empty($products[$key]['review_id'])) {
			$json['error']['warning'] = $this->language->get('error_reviewed');
		}

		// Same rules as OpenCart's own review form.
		if (!oc_validate_length((string)$post['author'], 3, 25)) {
			$json['error']['author'] = $this->language->get('error_author');
		}

		if (!oc_validate_length((string)$post['text'], 25, 1000)) {
			$json['error']['text'] = $this->language->get('error_text');
		}

		$rating = (int)$post['rating'];

		if ($rating < 1 || $rating > 5) {
			$json['error']['rating'] = $this->language->get('error_rating');
		}

		if (!$json) {
			// Through the core model, so the store owner's "new review" alert and statistics still work.
			$this->load->model('catalog/review');

			$review_id = $this->model_catalog_review->addReview((int)$post['product_id'], [
				'author' => (string)$post['author'],
				'text'   => (string)$post['text'],
				'rating' => $rating
			]);

			$approve = (int)(bool)$this->config->get('module_review_request_approve');

			$this->model_extension_review_request_module_review_request->editReview($review_id, (int)$request_info['customer_id'], $approve);

			if ($approve) {
				$this->model_extension_review_request_module_review_request->editProductRating((int)$post['product_id']);
			}

			$products[$key]['review_id'] = $review_id;
			$products[$key]['rating'] = $rating;

			$this->model_extension_review_request_module_review_request->addReviewed((int)$request_info['review_request_id'], $products, $rating);

			$json['success'] = $this->language->get($approve ? 'text_success_published' : 'text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Unsubscribe
	 *
	 * @return void
	 */
	public function unsubscribe(): void {
		$this->load->language('extension/review_request/module/review_request');

		$token = (string)($this->request->get['token'] ?? '');

		$this->load->model('extension/review_request/module/review_request');

		$request_info = $token ? $this->model_extension_review_request_module_review_request->getRequestByToken($token) : [];

		if ($request_info) {
			$this->model_extension_review_request_module_review_request->unsubscribe($request_info['email']);

			$data['text_message'] = sprintf($this->language->get('text_unsubscribed'), htmlspecialchars($request_info['email'], ENT_QUOTES, 'UTF-8'));
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

		$this->response->setOutput($this->load->view('extension/review_request/module/review_request_unsubscribe', $data));
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
		if (!$this->config->get('module_review_request_status') || !$this->config->get('config_review_status')) {
			return;
		}

		$this->load->model('extension/review_request/module/review_request');

		$this->model_extension_review_request_module_review_request->clean((int)$this->config->get('module_review_request_retention'));

		if (!$this->config->get('config_mail_engine')) {
			return;
		}

		$this->load->model('catalog/product');

		// First emails
		$orders = $this->model_extension_review_request_module_review_request->getDueOrders((array)$this->config->get('module_review_request_order_status'), (int)$this->config->get('module_review_request_delay') ?: 7, (string)$this->config->get('module_review_request_start') ?: date('Y-m-d'));

		foreach ($orders as $order) {
			$lastname = html_entity_decode($order['lastname'], ENT_QUOTES, 'UTF-8');

			$request_data = [
				'order_id'      => (int)$order['order_id'],
				'store_id'      => (int)$order['store_id'],
				'customer_id'   => (int)$order['customer_id'],
				'email'         => $order['email'],
				'firstname'     => $order['firstname'],
				'author'        => htmlspecialchars(trim(html_entity_decode($order['firstname'], ENT_QUOTES, 'UTF-8') . ' ' . ($lastname !== '' ? oc_substr($lastname, 0, 1) . '.' : '')), ENT_QUOTES, 'UTF-8'),
				'language_code' => (string)$order['language_code'],
				'products'      => []
			];

			if ($this->model_extension_review_request_module_review_request->isUnsubscribed($order['email'])) {
				$this->model_extension_review_request_module_review_request->addRequest($request_data, 3);

				continue;
			}

			// Read products as the order's store so disabled or unassigned products are skipped.
			$store_id = $this->config->get('config_store_id');

			$this->config->set('config_store_id', (int)$order['store_id']);

			foreach ($this->model_extension_review_request_module_review_request->getOrderProducts((int)$order['order_id']) as $order_product) {
				$product_id = (int)$order_product['product_id'];

				if (isset($request_data['products'][$product_id]) || $this->model_extension_review_request_module_review_request->hasReviewed((int)$order['customer_id'], $product_id)) {
					continue;
				}

				$product_info = $this->model_catalog_product->getProduct($product_id);

				if ($product_info) {
					$request_data['products'][$product_id] = [
						'product_id' => $product_id,
						'name'       => html_entity_decode($order_product['name'], ENT_QUOTES, 'UTF-8'),
						'image'      => $product_info['image']
					];
				}
			}

			$this->config->set('config_store_id', $store_id);

			$request_data['products'] = array_values($request_data['products']);

			if (!$request_data['products']) {
				$this->model_extension_review_request_module_review_request->addRequest($request_data, 4);

				continue;
			}

			$token = $this->model_extension_review_request_module_review_request->addRequest($request_data, 1);

			$request_info = $this->model_extension_review_request_module_review_request->getRequestByToken($token);

			try {
				$this->send($request_info, false);

				$this->model_extension_review_request_module_review_request->editSent((int)$request_info['review_request_id']);
			} catch (\Throwable $e) {
				// Try again next hour.
				$this->model_extension_review_request_module_review_request->deleteRequest($token);

				$this->log->write('Review Request Emails: could not email order ' . $order['order_id'] . ': ' . $e->getMessage());
			}
		}

		// Reminders
		if ($this->config->get('module_review_request_reminder')) {
			$requests = $this->model_extension_review_request_module_review_request->getDueReminders((int)$this->config->get('module_review_request_reminder_delay') ?: 7);

			foreach ($requests as $request_info) {
				try {
					$this->send($request_info, true);
				} catch (\Throwable $e) {
					$this->log->write('Review Request Emails: could not send reminder for order ' . $request_info['order_id'] . ': ' . $e->getMessage());
				}

				// Counted even on failure, so a bad address is never retried.
				$this->model_extension_review_request_module_review_request->editSent((int)$request_info['review_request_id']);
			}
		}
	}

	/**
	 * Send
	 *
	 * @param array<string, mixed> $request_info
	 * @param bool                 $reminder
	 *
	 * @throws \Exception
	 *
	 * @return void
	 */
	private function send(array $request_info, bool $reminder): void {
		$this->load->language('extension/review_request/module/review_request');

		// Store
		$store_name = (string)$this->config->get('config_name');
		$store_url = (string)$this->config->get('config_url');

		if ($request_info['store_id']) {
			$this->load->model('setting/store');

			$store_info = $this->model_setting_store->getStore((int)$request_info['store_id']);

			if ($store_info) {
				$store_name = $store_info['name'];
				$store_url = $store_info['url'];
			}
		}

		$store_name = html_entity_decode($store_name, ENT_QUOTES, 'UTF-8');

		// Links
		$language = $request_info['language_code'] ?: $this->config->get('config_language');

		$utm = '&utm_source=review_request&utm_medium=email&utm_campaign=review_request&utm_content=' . ($reminder ? 'reminder' : 'email_1');

		$review_link = $store_url . 'index.php?route=extension/review_request/module/review_request&language=' . urlencode($language) . '&token=' . $request_info['token'];
		$unsubscribe_link = $store_url . 'index.php?route=extension/review_request/module/review_request.unsubscribe&language=' . urlencode($language) . '&token=' . $request_info['token'];

		// Products with one link per star
		$this->load->model('tool/image');

		$products = [];

		foreach ((array)json_decode($request_info['products'], true) as $product) {
			$stars = [];

			for ($i = 1; $i <= 5; $i++) {
				$stars[$i] = $review_link . '&product_id=' . (int)$product['product_id'] . '&rating=' . $i . $utm;
			}

			$products[] = [
				'thumb' => $product['image'] ? $this->model_tool_image->resize($product['image'], 80, 80) : '',
				'name'  => $product['name'],
				'stars' => $stars
			];
		}

		$products_html = $this->load->view('extension/review_request/module/review_request_items', [
			'products'  => $products,
			'text_rate' => $this->language->get('text_rate')
		]);

		$firstname = html_entity_decode($request_info['firstname'], ENT_QUOTES, 'UTF-8') ?: $this->language->get('text_there');

		$replace = [
			'{firstname}'            => htmlspecialchars($firstname, ENT_QUOTES, 'UTF-8'),
			'{store_name}'           => htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'),
			'{order_id}'             => (int)$request_info['order_id'],
			'{products}'             => $products_html,
			'{review_link}'          => htmlspecialchars($review_link . $utm, ENT_QUOTES, 'UTF-8'),
			'%7Breview_link%7D'      => htmlspecialchars($review_link . $utm, ENT_QUOTES, 'UTF-8'),
			'{unsubscribe_link}'     => htmlspecialchars($unsubscribe_link, ENT_QUOTES, 'UTF-8'),
			'%7Bunsubscribe_link%7D' => htmlspecialchars($unsubscribe_link, ENT_QUOTES, 'UTF-8')
		];

		$prefix = $reminder ? 'module_review_request_reminder_' : 'module_review_request_';

		$subject = strtr(html_entity_decode((string)$this->config->get($prefix . 'subject'), ENT_QUOTES, 'UTF-8'), ['{firstname}' => $firstname, '{store_name}' => $store_name, '{order_id}' => (int)$request_info['order_id']]);
		$message = strtr(html_entity_decode((string)$this->config->get($prefix . 'message'), ENT_QUOTES, 'UTF-8'), $replace);

		$logo = '';

		if ($this->config->get('config_logo') && is_file(DIR_IMAGE . html_entity_decode($this->config->get('config_logo'), ENT_QUOTES, 'UTF-8'))) {
			$logo = $store_url . 'image/' . $this->config->get('config_logo');
		}

		$html = $this->load->view('extension/review_request/module/review_request_mail', [
			'title'            => htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
			'store_name'       => htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8'),
			'store_url'        => $store_url,
			'logo'             => $logo,
			'message'          => $message,
			'text_footer'      => sprintf($this->language->get('text_footer'), (int)$request_info['order_id'], htmlspecialchars($store_name, ENT_QUOTES, 'UTF-8')),
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

		$recipients = [$request_info['email']];

		if ($this->config->get('module_review_request_bcc')) {
			$recipients[] = $this->config->get('module_review_request_bcc');
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
	}
}
