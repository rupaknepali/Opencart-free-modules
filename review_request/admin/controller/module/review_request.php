<?php
namespace Opencart\Admin\Controller\Extension\ReviewRequest\Module;
/**
 * Class ReviewRequest
 *
 * Review Request Emails for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\ReviewRequest\Module
 */
class ReviewRequest extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/review_request/module/review_request');

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
			'href' => $this->url->link('extension/review_request/module/review_request', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/review_request/module/review_request.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
		$data['cron'] = $this->url->link('marketplace/cron', 'user_token=' . $this->session->data['user_token']);
		$data['reviews'] = $this->url->link('catalog/review', 'user_token=' . $this->session->data['user_token']);

		$data['module_review_request_status'] = $this->config->get('module_review_request_status');
		$data['module_review_request_order_status'] = (array)$this->config->get('module_review_request_order_status');
		$data['module_review_request_delay'] = (int)$this->config->get('module_review_request_delay') ?: 7;
		$data['module_review_request_reminder'] = $this->config->get('module_review_request_reminder');
		$data['module_review_request_reminder_delay'] = (int)$this->config->get('module_review_request_reminder_delay') ?: 7;
		$data['module_review_request_approve'] = $this->config->get('module_review_request_approve');
		$data['module_review_request_start'] = $this->config->get('module_review_request_start') ?: date('Y-m-d');
		$data['module_review_request_retention'] = (int)$this->config->get('module_review_request_retention') ?: 365;
		$data['module_review_request_bcc'] = $this->config->get('module_review_request_bcc');
		$data['module_review_request_subject'] = $this->config->get('module_review_request_subject');
		$data['module_review_request_message'] = $this->config->get('module_review_request_message');
		$data['module_review_request_reminder_subject'] = $this->config->get('module_review_request_reminder_subject');
		$data['module_review_request_reminder_message'] = $this->config->get('module_review_request_reminder_message');

		$data['review_status'] = $this->config->get('config_review_status');

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		// Report
		$this->load->model('extension/review_request/module/review_request');

		$statistics = $this->model_extension_review_request_module_review_request->getStatistics();

		$data['stat_requested'] = (int)$statistics['requested'];
		$data['stat_clicked'] = (int)$statistics['requested'] ? round(((int)$statistics['clicked'] / (int)$statistics['requested']) * 100, 1) . '%' : '0%';
		$data['stat_reviews'] = (int)$statistics['reviews'];
		$data['stat_rate'] = (int)$statistics['requested'] ? round(((int)$statistics['responded'] / (int)$statistics['requested']) * 100, 1) . '%' : '0%';
		$data['stat_rating'] = (int)$statistics['reviews'] ? number_format((int)$statistics['rating_total'] / (int)$statistics['reviews'], 1) : '-';

		$data['list'] = $this->getList();

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/review_request/module/review_request', $data));
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('extension/review_request/module/review_request');

		$this->response->setOutput($this->getList());
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	public function getList(): string {
		$filter_email = (string)($this->request->get['filter_email'] ?? '');
		$filter_order_id = (string)($this->request->get['filter_order_id'] ?? '');
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

		if ($filter_order_id !== '') {
			$url .= '&filter_order_id=' . (int)$filter_order_id;
		}

		if ($filter_status !== '') {
			$url .= '&filter_status=' . (int)$filter_status;
		}

		$filter_data = [
			'filter_email'    => $filter_email,
			'filter_order_id' => $filter_order_id,
			'filter_status'   => $filter_status,
			'start'           => ($page - 1) * $limit,
			'limit'           => $limit
		];

		$this->load->model('extension/review_request/module/review_request');

		$data['requests'] = [];

		$results = $this->model_extension_review_request_module_review_request->getRequests($filter_data);

		foreach ($results as $result) {
			$names = [];

			foreach ((array)json_decode($result['products'], true) as $product) {
				$names[] = $product['name'] . (!empty($product['review_id']) ? ' (' . (int)$product['rating'] . '★)' : '');
			}

			$data['requests'][] = [
				'review_request_id' => $result['review_request_id'],
				'email'             => $result['email'],
				'firstname'         => $result['firstname'],
				'order_id'          => $result['order_id'],
				'order'             => $this->url->link('sale/order.info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id']),
				'products'          => implode(', ', $names),
				'emails'            => (int)$result['emails'],
				'clicks'            => (int)$result['clicks'],
				'reviews'           => (int)$result['reviews'],
				'status'            => $this->language->get('text_status_' . (int)$result['status']),
				'status_id'         => (int)$result['status'],
				'date_sent'         => $result['date_sent'] ? date($this->language->get('date_format_short'), strtotime($result['date_sent'])) : '',
				'delete'            => $this->url->link('extension/review_request/module/review_request.delete', 'user_token=' . $this->session->data['user_token'] . '&review_request_id=' . $result['review_request_id'])
			];
		}

		$request_total = $this->model_extension_review_request_module_review_request->getTotalRequests($filter_data);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $request_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('extension/review_request/module/review_request.list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($request_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($request_total - $limit)) ? $request_total : ((($page - 1) * $limit) + $limit), $request_total, ceil($request_total / $limit));

		return $this->load->view('extension/review_request/module/review_request_list', $data);
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/review_request/module/review_request');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/review_request/module/review_request')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (empty($post['module_review_request_order_status'])) {
			$json['error']['order_status'] = $this->language->get('error_order_status');
		}

		$delay = (int)($post['module_review_request_delay'] ?? 0);

		if ($delay < 1 || $delay > 60) {
			$json['error']['delay'] = $this->language->get('error_delay');
		}

		if (!empty($post['module_review_request_reminder'])) {
			$reminder_delay = (int)($post['module_review_request_reminder_delay'] ?? 0);

			if ($reminder_delay < 1 || $reminder_delay > 30) {
				$json['error']['reminder_delay'] = $this->language->get('error_reminder_delay');
			}

			if (oc_strlen(trim($post['module_review_request_reminder_subject'] ?? '')) < 3) {
				$json['error']['reminder_subject'] = $this->language->get('error_subject');
			}

			if (oc_strlen(trim(strip_tags(html_entity_decode($post['module_review_request_reminder_message'] ?? '', ENT_QUOTES, 'UTF-8')))) < 3) {
				$json['error']['reminder_message'] = $this->language->get('error_message');
			}
		}

		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($post['module_review_request_start'] ?? ''))) {
			$json['error']['start'] = $this->language->get('error_start');
		}

		if (!empty($post['module_review_request_bcc']) && !oc_validate_email($post['module_review_request_bcc'])) {
			$json['error']['bcc'] = $this->language->get('error_bcc');
		}

		if (oc_strlen(trim($post['module_review_request_subject'] ?? '')) < 3) {
			$json['error']['subject'] = $this->language->get('error_subject');
		}

		if (oc_strlen(trim(strip_tags(html_entity_decode($post['module_review_request_message'] ?? '', ENT_QUOTES, 'UTF-8')))) < 3) {
			$json['error']['message'] = $this->language->get('error_message');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['module_review_request_order_status'] = array_map('intval', (array)$post['module_review_request_order_status']);
			$post['module_review_request_reminder_delay'] = max(1, (int)($post['module_review_request_reminder_delay'] ?? 7));
			$post['module_review_request_retention'] = max(90, (int)($post['module_review_request_retention'] ?? 365));

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_review_request', $post);

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
		$this->load->language('extension/review_request/module/review_request');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/review_request/module/review_request')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('extension/review_request/module/review_request');

			$this->model_extension_review_request_module_review_request->deleteRequest((int)($this->request->get['review_request_id'] ?? 0));

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

		$this->load->model('extension/review_request/module/review_request');

		$this->model_extension_review_request_module_review_request->install();

		// Cron
		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('review_request');
		$this->model_setting_cron->addCron('review_request', 'Emails customers asking them to review the products they bought.', 'hour', 'extension/review_request/module/review_request.cron', true);

		// Default settings, stored escaped the same way OpenCart's request object stores posted values.
		$button = '<p style="text-align:center;margin:24px 0;"><a href="{review_link}" style="background:#1f6feb;color:#ffffff;padding:12px 24px;border-radius:4px;text-decoration:none;font-weight:bold;">%s</a></p>';

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_review_request', [
			'module_review_request_status'           => 0,
			'module_review_request_order_status'     => array_map('intval', (array)$this->config->get('config_complete_status')),
			'module_review_request_delay'            => 7,
			'module_review_request_reminder'         => 1,
			'module_review_request_reminder_delay'   => 7,
			'module_review_request_approve'          => 0,
			'module_review_request_start'            => date('Y-m-d'),
			'module_review_request_retention'        => 365,
			'module_review_request_bcc'              => '',
			'module_review_request_subject'          => htmlspecialchars('How was your order from {store_name}?', ENT_COMPAT, 'UTF-8'),
			'module_review_request_message'          => htmlspecialchars('<p>Hi {firstname},</p><p>Thanks again for your order #{order_id}. We hope everything arrived safely.</p><p>Could you take a minute to tell other shoppers what you think? Click a star to start your review:</p>{products}' . sprintf($button, 'Write a review') . '<p>Every review is read by our team, good or bad. If something went wrong with your order, just reply to this email and we\'ll fix it.</p>', ENT_COMPAT, 'UTF-8'),
			'module_review_request_reminder_subject' => htmlspecialchars('A quick review for {store_name}?', ENT_COMPAT, 'UTF-8'),
			'module_review_request_reminder_message' => htmlspecialchars('<p>Hi {firstname},</p><p>Just a reminder: your review of order #{order_id} would really help other shoppers. It only takes a minute.</p>{products}' . sprintf($button, 'Write a review') . '<p>This is the last time we\'ll ask about this order.</p>', ENT_COMPAT, 'UTF-8')
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

		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('review_request');

		$this->load->model('extension/review_request/module/review_request');

		$this->model_extension_review_request_module_review_request->uninstall();
	}
}
