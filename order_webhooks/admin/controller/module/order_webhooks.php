<?php
namespace Opencart\Admin\Controller\Extension\OrderWebhooks\Module;
/**
 * Class OrderWebhooks
 *
 * Order Webhooks for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\OrderWebhooks\Module
 */
class OrderWebhooks extends \Opencart\System\Engine\Controller {
	/**
	 * Catalog events registered on install. code => [trigger, method]
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $events = [
		'order_webhooks_order_before' => ['catalog/model/checkout/order.addHistory/before', 'orderBefore'],
		'order_webhooks_order_after'  => ['catalog/model/checkout/order.addHistory/after', 'orderAfter'],
		'order_webhooks_customer'     => ['catalog/model/account/customer.addCustomer/after', 'customer']
	];

	/**
	 * Events an endpoint can subscribe to.
	 *
	 * @var array<int, string>
	 */
	private array $types = ['order.created', 'order.status_changed', 'customer.created'];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/order_webhooks/module/order_webhooks');

		$this->document->setTitle($this->language->get('heading_title'));

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
			'href' => $this->url->link('extension/order_webhooks/module/order_webhooks', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/order_webhooks/module/order_webhooks.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
		$data['test'] = $this->url->link('extension/order_webhooks/module/order_webhooks.test', 'user_token=' . $this->session->data['user_token']);
		$data['cron'] = $this->url->link('marketplace/cron', 'user_token=' . $this->session->data['user_token']);

		$data['module_order_webhooks_status'] = $this->config->get('module_order_webhooks_status');
		$data['module_order_webhooks_retention'] = (int)$this->config->get('module_order_webhooks_retention') ?: 30;

		$data['endpoints'] = [];

		foreach ((array)$this->config->get('module_order_webhooks_endpoint') as $endpoint) {
			$data['endpoints'][] = [
				'url'    => $endpoint['url'] ?? '',
				'secret' => $endpoint['secret'] ?? '',
				'event'  => (array)($endpoint['event'] ?? []),
				'status' => !empty($endpoint['status'])
			];
		}

		$data['types'] = $this->types;

		$data['list'] = $this->getList();

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/order_webhooks/module/order_webhooks', $data));
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('extension/order_webhooks/module/order_webhooks');

		$this->response->setOutput($this->getList());
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	public function getList(): string {
		$filter_status = (string)($this->request->get['filter_status'] ?? '');

		$page = max(1, (int)($this->request->get['page'] ?? 1));

		$limit = (int)$this->config->get('config_pagination_admin') ?: 10;

		$filter_data = [
			'filter_status' => $filter_status,
			'start'         => ($page - 1) * $limit,
			'limit'         => $limit
		];

		$this->load->model('extension/order_webhooks/module/order_webhooks');

		$data['deliveries'] = [];

		foreach ($this->model_extension_order_webhooks_module_order_webhooks->getDeliveries($filter_data) as $result) {
			$data['deliveries'][] = [
				'webhook_delivery_id' => $result['webhook_delivery_id'],
				'message_id'          => $result['message_id'],
				'url'                 => $result['url'],
				'event'               => $result['event'],
				'status'              => $this->language->get('text_status_' . (int)$result['status']),
				'status_id'           => (int)$result['status'],
				'attempts'            => (int)$result['attempts'],
				'response_code'       => (int)$result['response_code'],
				'response_body'       => $result['response_body'],
				'next_attempt'        => $result['next_attempt'] ? date($this->language->get('datetime_format'), strtotime($result['next_attempt'])) : '',
				'date_added'          => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
				'resend'              => $this->url->link('extension/order_webhooks/module/order_webhooks.resend', 'user_token=' . $this->session->data['user_token'] . '&webhook_delivery_id=' . $result['webhook_delivery_id'])
			];
		}

		$total = $this->model_extension_order_webhooks_module_order_webhooks->getTotalDeliveries($filter_data);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('extension/order_webhooks/module/order_webhooks.list', 'user_token=' . $this->session->data['user_token'] . ($filter_status !== '' ? '&filter_status=' . (int)$filter_status : '') . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($total - $limit)) ? $total : ((($page - 1) * $limit) + $limit), $total, ceil($total / $limit));

		$data['user_token'] = $this->session->data['user_token'];

		return $this->load->view('extension/order_webhooks/module/order_webhooks_list', $data);
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/order_webhooks/module/order_webhooks');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/order_webhooks/module/order_webhooks')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$endpoints = [];

		foreach (array_values((array)($this->request->post['module_order_webhooks_endpoint'] ?? [])) as $row => $endpoint) {
			$url = trim(html_entity_decode((string)($endpoint['url'] ?? ''), ENT_QUOTES, 'UTF-8'));

			if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https://#i', $url)) {
				$json['error']['url_' . $row] = $this->language->get('error_url');
			}

			$events = array_values(array_intersect((array)($endpoint['event'] ?? []), $this->types));

			if (!$events) {
				$json['error']['event_' . $row] = $this->language->get('error_event');
			}

			$secret = trim((string)($endpoint['secret'] ?? ''));

			if (!preg_match('#^whsec_[A-Za-z0-9+/=]{40,}$#', $secret)) {
				$secret = \Opencart\System\Library\Extension\OrderWebhooks\Sender::generateSecret();
			}

			$endpoints[] = [
				'url'    => $url,
				'secret' => $secret,
				'event'  => $events,
				'status' => !empty($endpoint['status']) ? 1 : 0
			];
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_order_webhooks', [
				'module_order_webhooks_status'    => !empty($this->request->post['module_order_webhooks_status']) ? 1 : 0,
				'module_order_webhooks_retention' => max(7, (int)($this->request->post['module_order_webhooks_retention'] ?? 30)),
				'module_order_webhooks_endpoint'  => $endpoints
			]);

			$json['success'] = $this->language->get('text_success');
			$json['secrets'] = array_column($endpoints, 'secret');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Test
	 *
	 * Sends a webhook.test event to a saved endpoint.
	 *
	 * @return void
	 */
	public function test(): void {
		$this->load->language('extension/order_webhooks/module/order_webhooks');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/order_webhooks/module/order_webhooks')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$endpoints = array_values((array)$this->config->get('module_order_webhooks_endpoint'));

		$row = (int)($this->request->get['endpoint'] ?? -1);

		if (!$json && empty($endpoints[$row]['url'])) {
			$json['error'] = $this->language->get('error_endpoint');
		}

		if (!$json) {
			$endpoint = $endpoints[$row];

			$payload = (string)json_encode([
				'type'      => 'webhook.test',
				'timestamp' => date('c'),
				'data'      => [
					'store_name' => html_entity_decode((string)$this->config->get('config_name'), ENT_QUOTES, 'UTF-8'),
					'message'    => 'This is a test webhook from OpenCart Order Webhooks.'
				]
			], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

			$json['success'] = sprintf($this->language->get('text_test_sent'), $this->send($endpoint['url'], $endpoint['secret'], 'webhook.test', $payload));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Resend
	 *
	 * @return void
	 */
	public function resend(): void {
		$this->load->language('extension/order_webhooks/module/order_webhooks');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/order_webhooks/module/order_webhooks')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$this->load->model('extension/order_webhooks/module/order_webhooks');

		$delivery_info = $this->model_extension_order_webhooks_module_order_webhooks->getDelivery((int)($this->request->get['webhook_delivery_id'] ?? 0));

		$secret = '';

		if ($delivery_info) {
			foreach ((array)$this->config->get('module_order_webhooks_endpoint') as $endpoint) {
				if (($endpoint['url'] ?? '') == $delivery_info['url']) {
					$secret = (string)$endpoint['secret'];
				}
			}
		}

		if (!$json && (!$delivery_info || $secret === '')) {
			$json['error'] = $this->language->get('error_delivery');
		}

		if (!$json) {
			// A resend is a new message with a new webhook-id, so receivers that de-duplicate on the ID will process it.
			$code = $this->send($delivery_info['url'], $secret, $delivery_info['event'], $delivery_info['payload']);

			$json['success'] = sprintf($this->language->get('text_resent'), $code);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Send
	 *
	 * @param string $url
	 * @param string $secret
	 * @param string $event
	 * @param string $payload
	 *
	 * @return string HTTP code, or the cURL error
	 */
	private function send(string $url, string $secret, string $event, string $payload): string {
		$this->load->model('extension/order_webhooks/module/order_webhooks');

		$webhook_delivery_id = $this->model_extension_order_webhooks_module_order_webhooks->addDelivery($url, $event, $payload);

		$delivery_info = $this->model_extension_order_webhooks_module_order_webhooks->getDelivery($webhook_delivery_id);

		$result = \Opencart\System\Library\Extension\OrderWebhooks\Sender::send($url, $secret, $delivery_info['message_id'], $payload, 10);

		$this->model_extension_order_webhooks_module_order_webhooks->editResult($webhook_delivery_id, $result);

		return $result['error'] ? $result['code'] . ' (' . $result['error'] . ')' : (string)$result['code'];
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

		$this->load->model('extension/order_webhooks/module/order_webhooks');

		$this->model_extension_order_webhooks_module_order_webhooks->install();

		$this->load->model('setting/event');

		foreach ($this->events as $code => $event) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Order Webhooks',
				'trigger'     => $event[0],
				'action'      => 'extension/order_webhooks/module/order_webhooks.' . $event[1],
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		// The job runs every time cron.php runs ("+1 minute" cycle), so retries go out as often as your server cron allows.
		$this->load->model('setting/cron');

		$this->model_setting_cron->deleteCronByCode('order_webhooks');
		$this->model_setting_cron->addCron('order_webhooks', 'Retries failed order webhooks.', 'minute', 'extension/order_webhooks/module/order_webhooks.cron', true);

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_order_webhooks', [
			'module_order_webhooks_status'    => 0,
			'module_order_webhooks_retention' => 30,
			'module_order_webhooks_endpoint'  => []
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

		$this->model_setting_cron->deleteCronByCode('order_webhooks');

		$this->load->model('extension/order_webhooks/module/order_webhooks');

		$this->model_extension_order_webhooks_module_order_webhooks->uninstall();
	}
}
