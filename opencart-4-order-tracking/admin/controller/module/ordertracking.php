<?php
namespace Opencart\Admin\Controller\Extension\Ordertracking\Module;
/**
 * Order Tracking: add carrier tracking numbers to orders, notify the customer through
 * OpenCart's own order history, and show "Track package" links in the customer account.
 */
class Ordertracking extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/ordertracking/module/ordertracking';

	/**
	 * Carriers offered on a fresh install. {tracking} is replaced with the tracking number.
	 */
	private function defaultCarriers(): array {
		return [
			['name' => 'USPS', 'url' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={tracking}'],
			['name' => 'UPS', 'url' => 'https://www.ups.com/track?tracknum={tracking}'],
			['name' => 'FedEx', 'url' => 'https://www.fedex.com/fedextrack/?trknbr={tracking}'],
			['name' => 'DHL Express', 'url' => 'https://www.dhl.com/global-en/home/tracking/tracking-express.html?submit=1&tracking-id={tracking}'],
			['name' => 'Royal Mail', 'url' => 'https://www.royalmail.com/track-your-item#/tracking-results/{tracking}'],
			['name' => 'Canada Post', 'url' => 'https://www.canadapost-postescanada.ca/track-reperez/en#/search?searchFor={tracking}'],
			['name' => 'Australia Post', 'url' => 'https://auspost.com.au/mypost/track/details/{tracking}'],
			['name' => 'Other (17TRACK)', 'url' => 'https://t.17track.net/en#nums={tracking}']
		];
	}

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token)]
		];

		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$shipped = 0;

		foreach ($data['order_statuses'] as $order_status) {
			if (oc_strtolower($order_status['name']) == 'shipped') {
				$shipped = (int)$order_status['order_status_id'];
			}
		}

		$defaults = [
			'status'          => 0,
			'carriers'        => $this->defaultCarriers(),
			'order_status_id' => $shipped,
			'notify'          => 1,
			'comment'         => $this->language->get('text_default_comment')
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_ordertracking');

		foreach ($defaults as $key => $value) {
			$data['module_ordertracking_' . $key] = array_key_exists('module_ordertracking_' . $key, $saved) ? $saved['module_ordertracking_' . $key] : $value;
		}

		if (!is_array($data['module_ordertracking_carriers'])) {
			$data['module_ordertracking_carriers'] = [];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];
		$post = $this->request->post;

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$carriers = [];

		foreach ((array)($post['module_ordertracking_carriers'] ?? []) as $row => $carrier) {
			$name = trim((string)($carrier['name'] ?? ''));
			$url = trim((string)($carrier['url'] ?? ''));

			if ($name === '' && $url === '') {
				continue;
			}

			// Only http(s) links, and they must contain the placeholder or every package gets the same link.
			if ($name === '' || !preg_match('#^https?://#i', html_entity_decode($url, ENT_QUOTES, 'UTF-8')) || !str_contains($url, '{tracking}')) {
				$json['error']['carrier-' . $row] = $this->language->get('error_carrier');
			}

			$carriers[] = ['name' => $name, 'url' => $url];
		}

		if (!$carriers) {
			$json['error']['warning'] = $this->language->get('error_carriers');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['module_ordertracking_carriers'] = $carriers;

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_ordertracking', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Event: admin/view/sale/order_info/after. Adds the Shipment tracking card above Order History.
	 */
	public function orderInfo(string &$route, array &$args, string &$output): void {
		if (!$this->config->get('module_ordertracking_status') || empty($this->request->get['order_id'])) {
			return;
		}

		$this->load->language($this->route);
		$this->load->model($this->route);

		$order_id = (int)$this->request->get['order_id'];

		$data['order_id'] = $order_id;
		$data['user_token'] = $this->session->data['user_token'];
		$data['carriers'] = array_values((array)$this->config->get('module_ordertracking_carriers'));
		$data['order_status_id'] = (int)$this->config->get('module_ordertracking_order_status_id');
		$data['notify'] = (int)$this->config->get('module_ordertracking_notify');
		$data['list'] = $this->getList($order_id);
		$data['can_modify'] = $this->user->hasPermission('modify', 'sale/order');

		$html = $this->load->view('extension/ordertracking/module/ordertracking_order', $data);

		// Insert before the card that holds the order history (the one with the comment icon).
		if (preg_match('#<div class="card mb-3">\s*<div class="card-header"><i class="fa-solid fa-comment"></i>#', $output, $match, PREG_OFFSET_CAPTURE)) {
			$output = substr_replace($output, $html, $match[0][1], 0);
		} elseif (($position = strpos($output, '<form id="form-history"')) !== false) {
			$output = substr_replace($output, $html, $position, 0);
		}
	}

	private function getList(int $order_id): string {
		$this->load->language($this->route);
		$this->load->model($this->route);

		$data['trackings'] = [];

		foreach ($this->model_extension_ordertracking_module_ordertracking->getTrackings($order_id) as $tracking) {
			$data['trackings'][] = [
				'order_tracking_id' => $tracking['order_tracking_id'],
				'carrier'           => $tracking['carrier'],
				'tracking_number'   => $tracking['tracking_number'],
				'url'               => $tracking['url'],
				'date_added'        => date($this->language->get('datetime_format'), strtotime($tracking['date_added']))
			];
		}

		$data['can_modify'] = $this->user->hasPermission('modify', 'sale/order');

		return $this->load->view('extension/ordertracking/module/ordertracking_list', $data);
	}

	/**
	 * AJAX: save a tracking number. Returns the history comment so the page can add it
	 * through OpenCart's own order history form (which sends the customer email).
	 */
	public function add(): void {
		$this->load->language($this->route);

		$json = [];

		$order_id = (int)($this->request->post['order_id'] ?? 0);
		$carrier_key = (int)($this->request->post['carrier'] ?? -1);
		$tracking_number = trim((string)($this->request->post['tracking_number'] ?? ''));

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$this->load->model('sale/order');

		if (!$json && !$this->model_sale_order->getOrder($order_id)) {
			$json['error'] = $this->language->get('error_order');
		}

		$carriers = array_values((array)$this->config->get('module_ordertracking_carriers'));

		if (!$json && !isset($carriers[$carrier_key])) {
			$json['error'] = $this->language->get('error_carrier_select');
		}

		// Tracking numbers are letters, digits, spaces and dashes. Spaces and dashes are removed for the link.
		if (!$json && !preg_match('/^[A-Za-z0-9 \-]{4,40}$/', $tracking_number)) {
			$json['error'] = $this->language->get('error_tracking');
		}

		if (!$json) {
			$carrier = $carriers[$carrier_key];
			$clean = strtoupper(str_replace([' ', '-'], '', $tracking_number));
			$url = str_replace('{tracking}', rawurlencode($clean), html_entity_decode($carrier['url'], ENT_QUOTES, 'UTF-8'));
			$name = html_entity_decode($carrier['name'], ENT_QUOTES, 'UTF-8');

			$this->load->model($this->route);

			$this->model_extension_ordertracking_module_ordertracking->addTracking($order_id, $name, $clean, $url);

			$comment = html_entity_decode((string)$this->config->get('module_ordertracking_comment'), ENT_QUOTES, 'UTF-8');

			$json['comment'] = str_replace(['{carrier}', '{tracking}', '{url}'], [$name, $clean, $url], $comment);
			$json['list'] = $this->getList($order_id);
			$json['success'] = $this->language->get('text_added');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function remove(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', 'sale/order')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model($this->route);

			$order_id = (int)($this->request->post['order_id'] ?? 0);

			$this->model_extension_ordertracking_module_ordertracking->deleteTracking($order_id, (int)($this->request->post['order_tracking_id'] ?? 0));

			$json['list'] = $this->getList($order_id);
			$json['success'] = $this->language->get('text_removed');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model($this->route);

		$this->model_extension_ordertracking_module_ordertracking->install();

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'ordertracking_admin',
			'description' => 'Order Tracking: tracking card on the admin order page',
			'trigger'     => 'admin/view/sale/order_info/after',
			'action'      => 'extension/ordertracking/module/ordertracking.orderInfo',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'ordertracking_account',
			'description' => 'Order Tracking: tracking links on the customer order page',
			'trigger'     => 'catalog/view/account/order_info/after',
			'action'      => 'extension/ordertracking/event/ordertracking',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'ordertracking_mail',
			'description' => 'Order Tracking: line breaks and a clickable link in the shipping email',
			'trigger'     => 'catalog/view/mail/order_history/after',
			'action'      => 'extension/ordertracking/event/ordertracking.mail',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('ordertracking_admin');
		$this->model_setting_event->deleteEventByCode('ordertracking_account');
		$this->model_setting_event->deleteEventByCode('ordertracking_mail');

		// The tracking table is kept, so reinstalling doesn't lose tracking numbers already sent to customers.
	}
}
