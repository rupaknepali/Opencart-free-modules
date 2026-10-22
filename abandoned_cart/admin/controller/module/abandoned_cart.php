<?php
namespace Opencart\Admin\Controller\Extension\AbandonedCart\Module;
/**
 * Class AbandonedCart
 *
 * Abandoned Cart Recovery for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\AbandonedCart\Module
 */
class AbandonedCart extends \Opencart\System\Engine\Controller {
	/**
	 * Catalog events registered on install. code => [trigger, method]
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $events = [
		'abandoned_cart_register' => ['catalog/controller/checkout/register.save/after', 'capture'],
		'abandoned_cart_add'      => ['catalog/controller/checkout/cart.add/after', 'capture'],
		'abandoned_cart_edit'     => ['catalog/controller/checkout/cart.edit/after', 'capture'],
		'abandoned_cart_remove'   => ['catalog/controller/checkout/cart.remove/after', 'capture'],
		'abandoned_cart_order'    => ['catalog/model/checkout/order.addHistory/after', 'order']
	];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/abandoned_cart/module/abandoned_cart');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
		$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/abandoned_cart/module/abandoned_cart', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/abandoned_cart/module/abandoned_cart.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
		$data['cron'] = $this->url->link('marketplace/cron', 'user_token=' . $this->session->data['user_token']);

		$data['module_abandoned_cart_status'] = $this->config->get('module_abandoned_cart_status');
		$data['module_abandoned_cart_min_total'] = (float)$this->config->get('module_abandoned_cart_min_total');
		$data['module_abandoned_cart_consent'] = $this->config->get('module_abandoned_cart_consent');
		$data['module_abandoned_cart_retention'] = $this->config->get('module_abandoned_cart_retention') ?: 30;
		$data['module_abandoned_cart_bcc'] = $this->config->get('module_abandoned_cart_bcc');

		$stages = (array)$this->config->get('module_abandoned_cart_stage');
		$defaults = $this->getDefaultStages();

		$data['stages'] = [];

		foreach ($defaults as $key => $default) {
			$data['stages'][$key] = (isset($stages[$key]) ? $stages[$key] : []) + $default;
		}

		$data['module_abandoned_cart_coupon_stage'] = (int)$this->config->get('module_abandoned_cart_coupon_stage');
		$data['module_abandoned_cart_coupon_discount'] = (float)$this->config->get('module_abandoned_cart_coupon_discount');
		$data['module_abandoned_cart_coupon_days'] = (int)$this->config->get('module_abandoned_cart_coupon_days') ?: 7;

		$data['default_currency'] = $this->config->get('config_currency');

		// Report
		$this->load->model('extension/abandoned_cart/module/abandoned_cart');

		$statistics = $this->model_extension_abandoned_cart_module_abandoned_cart->getStatistics();

		$data['stat_captured'] = (int)$statistics['captured'];
		$data['stat_emails'] = (int)$statistics['emails'];
		$data['stat_recovered'] = (int)$statistics['recovered'];
		$data['stat_rate'] = (int)$statistics['emailed'] ? round(((int)$statistics['recovered'] / (int)$statistics['emailed']) * 100, 1) . '%' : '0%';
		$data['stat_revenue'] = $this->currency->format((float)$statistics['revenue'], $this->config->get('config_currency'));

		$data['list'] = $this->getList();

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/abandoned_cart/module/abandoned_cart', $data));
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('extension/abandoned_cart/module/abandoned_cart');

		$this->response->setOutput($this->getList());
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	public function getList(): string {
		if (isset($this->request->get['filter_email'])) {
			$filter_email = (string)$this->request->get['filter_email'];
		} else {
			$filter_email = '';
		}

		if (isset($this->request->get['filter_status'])) {
			$filter_status = (string)$this->request->get['filter_status'];
		} else {
			$filter_status = '';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$limit = (int)$this->config->get('config_pagination_admin') ?: 10;

		$url = '';

		if ($filter_email !== '') {
			$url .= '&filter_email=' . urlencode(html_entity_decode($filter_email, ENT_QUOTES, 'UTF-8'));
		}

		if ($filter_status !== '') {
			$url .= '&filter_status=' . (int)$filter_status;
		}

		$filter_data = [
			'filter_email'  => $filter_email,
			'filter_status' => $filter_status,
			'start'         => ($page - 1) * $limit,
			'limit'         => $limit
		];

		$this->load->model('extension/abandoned_cart/module/abandoned_cart');

		$data['carts'] = [];

		$results = $this->model_extension_abandoned_cart_module_abandoned_cart->getCarts($filter_data);

		foreach ($results as $result) {
			$products = json_decode($result['products'], true) ?: [];

			$names = [];

			foreach ($products as $product) {
				$names[] = $product['quantity'] . ' x ' . $product['name'];
			}

			$data['carts'][] = [
				'abandoned_cart_id' => $result['abandoned_cart_id'],
				'email'             => $result['email'],
				'firstname'         => $result['firstname'],
				'products'          => implode(', ', $names),
				'total'             => $result['total_text'] ?: $this->currency->format((float)$result['total'], $this->config->get('config_currency')),
				'emails'            => (int)$result['emails'],
				'clicks'            => (int)$result['clicks'],
				'status'            => $this->language->get('text_status_' . (int)$result['status']),
				'status_id'         => (int)$result['status'],
				'order'             => $result['order_id'] ? $this->url->link('sale/order.info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id']) : '',
				'order_id'          => $result['order_id'],
				'order_total'       => $result['order_id'] ? $this->currency->format((float)$result['order_total'], $this->config->get('config_currency')) : '',
				'date_modified'     => date($this->language->get('datetime_format'), strtotime($result['date_modified'])),
				'delete'            => $this->url->link('extension/abandoned_cart/module/abandoned_cart.delete', 'user_token=' . $this->session->data['user_token'] . '&abandoned_cart_id=' . $result['abandoned_cart_id'])
			];
		}

		$cart_total = $this->model_extension_abandoned_cart_module_abandoned_cart->getTotalCarts($filter_data);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $cart_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('extension/abandoned_cart/module/abandoned_cart.list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($cart_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($cart_total - $limit)) ? $cart_total : ((($page - 1) * $limit) + $limit), $cart_total, ceil($cart_total / $limit));

		return $this->load->view('extension/abandoned_cart/module/abandoned_cart_list', $data);
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/abandoned_cart/module/abandoned_cart');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/abandoned_cart/module/abandoned_cart')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (!empty($post['module_abandoned_cart_bcc']) && !oc_validate_email($post['module_abandoned_cart_bcc'])) {
			$json['error']['bcc'] = $this->language->get('error_bcc');
		}

		if (isset($post['module_abandoned_cart_retention']) && (int)$post['module_abandoned_cart_retention'] < 15) {
			$json['error']['retention'] = $this->language->get('error_retention');
		}

		foreach ((array)($post['module_abandoned_cart_stage'] ?? []) as $key => $stage) {
			if (!empty($stage['status'])) {
				if (oc_strlen(trim($stage['subject'] ?? '')) < 3) {
					$json['error']['stage_' . $key . '_subject'] = $this->language->get('error_subject');
				}

				if (oc_strlen(trim(strip_tags(html_entity_decode($stage['message'] ?? '', ENT_QUOTES, 'UTF-8')))) < 3) {
					$json['error']['stage_' . $key . '_message'] = $this->language->get('error_message');
				}
			}
		}

		if (!empty($post['module_abandoned_cart_coupon_stage'])) {
			$discount = (float)($post['module_abandoned_cart_coupon_discount'] ?? 0);

			if ($discount <= 0 || $discount > 100) {
				$json['error']['coupon_discount'] = $this->language->get('error_coupon_discount');
			}
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['module_abandoned_cart_min_total'] = max(0, (float)($post['module_abandoned_cart_min_total'] ?? 0));
			$post['module_abandoned_cart_retention'] = (int)($post['module_abandoned_cart_retention'] ?? 30);
			$post['module_abandoned_cart_coupon_days'] = max(1, (int)($post['module_abandoned_cart_coupon_days'] ?? 7));

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_abandoned_cart', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Delete
	 *
	 * @return void
	 */
	public function delete(): void {
		$this->load->language('extension/abandoned_cart/module/abandoned_cart');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/abandoned_cart/module/abandoned_cart')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('extension/abandoned_cart/module/abandoned_cart');

			$this->model_extension_abandoned_cart_module_abandoned_cart->deleteCart((int)($this->request->get['abandoned_cart_id'] ?? 0));

			$json['success'] = $this->language->get('text_delete');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install
	 *
	 * Called by Extensions > Extensions > Modules when the module is installed.
	 *
	 * @return void
	 */
	public function install(): void {
		if (!$this->user->hasPermission('modify', 'extension/module')) {
			return;
		}

		$this->load->model('extension/abandoned_cart/module/abandoned_cart');

		$this->model_extension_abandoned_cart_module_abandoned_cart->install();

		// Events
		$this->load->model('setting/event');

		foreach ($this->events as $code => $event) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Abandoned Cart Recovery',
				'trigger'     => $event[0],
				'action'      => 'extension/abandoned_cart/module/abandoned_cart.' . $event[1],
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		// Cron
		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('abandoned_cart');
		$this->model_setting_cron->addCron('abandoned_cart', 'Sends abandoned cart reminder emails and deletes old cart data.', 'hour', 'extension/abandoned_cart/module/abandoned_cart.cron', true);

		// Default settings
		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_abandoned_cart', [
			'module_abandoned_cart_status'          => 0,
			'module_abandoned_cart_min_total'       => 0,
			'module_abandoned_cart_consent'         => 0,
			'module_abandoned_cart_retention'       => 30,
			'module_abandoned_cart_bcc'             => '',
			'module_abandoned_cart_stage'           => $this->getDefaultStages(),
			'module_abandoned_cart_coupon_stage'    => 0,
			'module_abandoned_cart_coupon_discount' => 10,
			'module_abandoned_cart_coupon_days'     => 7
		]);
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		if (!$this->user->hasPermission('modify', 'extension/module')) {
			return;
		}

		$this->load->model('setting/event');

		foreach (array_keys($this->events) as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('abandoned_cart');

		$this->load->model('extension/abandoned_cart/module/abandoned_cart');

		$this->model_extension_abandoned_cart_module_abandoned_cart->uninstall();
	}

	/**
	 * Get Default Stages
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function getDefaultStages(): array {
		$this->load->language('extension/abandoned_cart/module/abandoned_cart');

		$button = '<p style="text-align:center;margin:24px 0;"><a href="{restore_link}" style="background:#1f6feb;color:#ffffff;padding:12px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">%s</a></p>';

		// Stored escaped, the same way OpenCart's request object stores posted values.
		$stages = [
			1 => [
				'status'  => 1,
				'delay'   => 1,
				'subject' => 'You left something in your cart',
				'message' => '<p>Hi {firstname},</p><p>You left these items in your cart at {store_name}. We saved them for you.</p>{cart}' . sprintf($button, 'Return to my cart') . '<p>Questions? Just reply to this email.</p>'
			],
			2 => [
				'status'  => 1,
				'delay'   => 24,
				'subject' => 'Still thinking it over?',
				'message' => '<p>Hi {firstname},</p><p>Your cart at {store_name} is still waiting. A few things our customers like to know:</p><ul><li>Secure checkout</li><li>Fast dispatch and tracked delivery</li><li>Easy returns</li></ul>{cart}' . sprintf($button, 'Complete my order') . '<p>Questions? Just reply to this email.</p>'
			],
			3 => [
				'status'  => 1,
				'delay'   => 72,
				'subject' => 'Your cart is about to expire',
				'message' => '<p>Hi {firstname},</p><p>This is our last reminder about the items in your cart at {store_name}.</p>{cart}<p>Cart total: <strong>{cart_total}</strong></p>{coupon}' . sprintf($button, 'Return to my cart')
			]
		];

		foreach ($stages as $key => $stage) {
			$stages[$key]['subject'] = htmlspecialchars($stage['subject'], ENT_COMPAT, 'UTF-8');
			$stages[$key]['message'] = htmlspecialchars($stage['message'], ENT_COMPAT, 'UTF-8');
		}

		return $stages;
	}
}
