<?php
namespace Opencart\Admin\Controller\Extension\BackInStock\Module;
/**
 * Class BackInStock
 *
 * Back-in-Stock Notifications for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\BackInStock\Module
 */
class BackInStock extends \Opencart\System\Engine\Controller {
	/**
	 * Catalog events registered on install. code => [trigger, method]
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $events = [
		'back_in_stock_product' => ['catalog/view/product/product/after', 'product'],
		'back_in_stock_order'   => ['catalog/model/checkout/order.addHistory/after', 'order']
	];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/back_in_stock/module/back_in_stock');

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
			'href' => $this->url->link('extension/back_in_stock/module/back_in_stock', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/back_in_stock/module/back_in_stock.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
		$data['cron'] = $this->url->link('marketplace/cron', 'user_token=' . $this->session->data['user_token']);

		$data['module_back_in_stock_status'] = $this->config->get('module_back_in_stock_status');
		$data['module_back_in_stock_hide_cart'] = $this->config->get('module_back_in_stock_hide_cart');
		$data['module_back_in_stock_max_emails'] = (int)$this->config->get('module_back_in_stock_max_emails') ?: 100;
		$data['module_back_in_stock_retention'] = (int)$this->config->get('module_back_in_stock_retention') ?: 180;
		$data['module_back_in_stock_bcc'] = $this->config->get('module_back_in_stock_bcc');
		$data['module_back_in_stock_subject'] = $this->config->get('module_back_in_stock_subject');
		$data['module_back_in_stock_message'] = $this->config->get('module_back_in_stock_message');

		$data['stock_checkout'] = $this->config->get('config_stock_checkout');

		// Report
		$this->load->model('extension/back_in_stock/module/back_in_stock');

		$statistics = $this->model_extension_back_in_stock_module_back_in_stock->getStatistics();

		$data['stat_waiting'] = (int)$statistics['waiting'];
		$data['stat_notified'] = (int)$statistics['notified'];
		$data['stat_purchased'] = (int)$statistics['purchased'];
		$data['stat_rate'] = (int)$statistics['notified'] ? round(((int)$statistics['purchased'] / (int)$statistics['notified']) * 100, 1) . '%' : '0%';
		$data['stat_revenue'] = $this->currency->format((float)$statistics['revenue'], $this->config->get('config_currency'));

		$data['wanted'] = [];

		foreach ($this->model_extension_back_in_stock_module_back_in_stock->getMostWanted() as $result) {
			$data['wanted'][] = [
				'product_name' => $result['product_name'],
				'model'        => $result['model'],
				'quantity'     => (int)$result['quantity'],
				'waiting'      => (int)$result['waiting'],
				'oldest'       => date($this->language->get('date_format_short'), strtotime($result['oldest'])),
				'edit'         => $this->url->link('catalog/product.form', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'])
			];
		}

		$data['list'] = $this->getList();

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/back_in_stock/module/back_in_stock', $data));
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('extension/back_in_stock/module/back_in_stock');

		$this->response->setOutput($this->getList());
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	public function getList(): string {
		$filter_email = (string)($this->request->get['filter_email'] ?? '');
		$filter_product = (string)($this->request->get['filter_product'] ?? '');
		$filter_status = (string)($this->request->get['filter_status'] ?? '');

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

		if ($filter_product !== '') {
			$url .= '&filter_product=' . urlencode(html_entity_decode($filter_product, ENT_QUOTES, 'UTF-8'));
		}

		if ($filter_status !== '') {
			$url .= '&filter_status=' . (int)$filter_status;
		}

		$filter_data = [
			'filter_email'   => $filter_email,
			'filter_product' => $filter_product,
			'filter_status'  => $filter_status,
			'start'          => ($page - 1) * $limit,
			'limit'          => $limit
		];

		$this->load->model('extension/back_in_stock/module/back_in_stock');

		$data['subscriptions'] = [];

		$results = $this->model_extension_back_in_stock_module_back_in_stock->getSubscriptions($filter_data);

		foreach ($results as $result) {
			$data['subscriptions'][] = [
				'back_in_stock_id' => $result['back_in_stock_id'],
				'email'            => $result['email'],
				'firstname'        => $result['firstname'],
				'product_name'     => $result['product_name'],
				'product'          => $this->url->link('catalog/product.form', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id']),
				'quantity'         => $result['quantity'] !== null ? (int)$result['quantity'] : '',
				'clicks'           => (int)$result['clicks'],
				'status'           => $this->language->get('text_status_' . (int)$result['status']),
				'status_id'        => (int)$result['status'],
				'order'            => $result['order_id'] ? $this->url->link('sale/order.info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id']) : '',
				'order_id'         => $result['order_id'],
				'order_total'      => $result['order_id'] ? $this->currency->format((float)$result['order_total'], $this->config->get('config_currency')) : '',
				'date_added'       => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'date_notified'    => $result['date_notified'] ? date($this->language->get('date_format_short'), strtotime($result['date_notified'])) : '',
				'delete'           => $this->url->link('extension/back_in_stock/module/back_in_stock.delete', 'user_token=' . $this->session->data['user_token'] . '&back_in_stock_id=' . $result['back_in_stock_id'])
			];
		}

		$subscription_total = $this->model_extension_back_in_stock_module_back_in_stock->getTotalSubscriptions($filter_data);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $subscription_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('extension/back_in_stock/module/back_in_stock.list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($subscription_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($subscription_total - $limit)) ? $subscription_total : ((($page - 1) * $limit) + $limit), $subscription_total, ceil($subscription_total / $limit));

		return $this->load->view('extension/back_in_stock/module/back_in_stock_list', $data);
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/back_in_stock/module/back_in_stock');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/back_in_stock/module/back_in_stock')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (!empty($post['module_back_in_stock_bcc']) && !oc_validate_email($post['module_back_in_stock_bcc'])) {
			$json['error']['bcc'] = $this->language->get('error_bcc');
		}

		if (oc_strlen(trim($post['module_back_in_stock_subject'] ?? '')) < 3) {
			$json['error']['subject'] = $this->language->get('error_subject');
		}

		if (oc_strlen(trim(strip_tags(html_entity_decode($post['module_back_in_stock_message'] ?? '', ENT_QUOTES, 'UTF-8')))) < 3) {
			$json['error']['message'] = $this->language->get('error_message');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['module_back_in_stock_max_emails'] = max(1, (int)($post['module_back_in_stock_max_emails'] ?? 100));
			$post['module_back_in_stock_retention'] = max(30, (int)($post['module_back_in_stock_retention'] ?? 180));

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_back_in_stock', $post);

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
		$this->load->language('extension/back_in_stock/module/back_in_stock');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/back_in_stock/module/back_in_stock')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('extension/back_in_stock/module/back_in_stock');

			$this->model_extension_back_in_stock_module_back_in_stock->deleteSubscription((int)($this->request->get['back_in_stock_id'] ?? 0));

			$json['success'] = $this->language->get('text_delete');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		if (!$this->user->hasPermission('modify', 'extension/module')) {
			return;
		}

		$this->load->model('extension/back_in_stock/module/back_in_stock');

		$this->model_extension_back_in_stock_module_back_in_stock->install();

		// Events
		$this->load->model('setting/event');

		foreach ($this->events as $code => $event) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Back-in-Stock Notifications',
				'trigger'     => $event[0],
				'action'      => 'extension/back_in_stock/module/back_in_stock.' . $event[1],
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		// Cron
		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('back_in_stock');
		$this->model_setting_cron->addCron('back_in_stock', 'Emails customers when a product they asked about is back in stock.', 'hour', 'extension/back_in_stock/module/back_in_stock.cron', true);

		// Default settings, stored escaped the same way OpenCart's request object stores posted values.
		$button = '<p style="text-align:center;margin:24px 0;"><a href="{product_link}" style="background:#1f6feb;color:#ffffff;padding:12px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">Shop now</a></p>';

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_back_in_stock', [
			'module_back_in_stock_status'     => 0,
			'module_back_in_stock_hide_cart'  => 0,
			'module_back_in_stock_max_emails' => 100,
			'module_back_in_stock_retention'  => 180,
			'module_back_in_stock_bcc'        => '',
			'module_back_in_stock_subject'    => htmlspecialchars('{product_name} is back in stock', ENT_COMPAT, 'UTF-8'),
			'module_back_in_stock_message'    => htmlspecialchars('<p>Hi {firstname},</p><p>Good news: <strong>{product_name}</strong> is back in stock at {store_name}. You asked us to let you know.</p>{product}' . $button . '<p>Stock can run out again quickly, so we can\'t hold it for you.</p>', ENT_COMPAT, 'UTF-8')
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

		$this->model_setting_cron->deleteCronByCode('back_in_stock');

		$this->load->model('extension/back_in_stock/module/back_in_stock');

		$this->model_extension_back_in_stock_module_back_in_stock->uninstall();
	}
}
