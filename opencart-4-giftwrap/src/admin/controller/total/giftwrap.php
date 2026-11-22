<?php
namespace Opencart\Admin\Controller\Extension\Giftwrap\Total;
/**
 * Gift wrap: a paid gift wrap option with a gift message, offered in the cart and at checkout. The message shows
 * on the admin order page, the invoice, the packing slip and the customer's order email.
 */
class Giftwrap extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/giftwrap/total/giftwrap';

	private array $defaults = [
		'total_giftwrap_status'       => 0,
		'total_giftwrap_price'        => 5,
		'total_giftwrap_tax_class_id' => 0,
		'total_giftwrap_message'      => 1,
		'total_giftwrap_length'       => 250,
		'total_giftwrap_description'  => [],
		'total_giftwrap_sort_order'   => 4
	];

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=total')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token)]
		];

		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=total');

		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('total_giftwrap');

		foreach ($this->defaults as $key => $value) {
			$data[$key] = array_key_exists($key, $saved) ? $saved[$key] : $value;
		}

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		// Recent gift orders, so you can see what to wrap.
		$data['orders'] = [];

		$query = $this->db->query("SELECT g.`order_id`, g.`message`, o.`firstname`, o.`lastname`, o.`date_added`, os.`name` AS status FROM `" . DB_PREFIX . "giftwrap_order` g INNER JOIN `" . DB_PREFIX . "order` o ON (o.`order_id` = g.`order_id`) LEFT JOIN `" . DB_PREFIX . "order_status` os ON (os.`order_status_id` = o.`order_status_id` AND os.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE o.`order_status_id` > '0' ORDER BY g.`order_id` DESC LIMIT 10");

		foreach ($query->rows as $row) {
			$data['orders'][] = [
				'order_id' => (int)$row['order_id'],
				'customer' => $row['firstname'] . ' ' . $row['lastname'],
				'message'  => $row['message'],
				'status'   => $row['status'],
				'date'     => date($this->language->get('date_format_short'), strtotime($row['date_added'])),
				'href'     => $this->url->link('sale/order.info', $token . '&order_id=' . (int)$row['order_id'])
			];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (!is_numeric($post['total_giftwrap_price'] ?? '') || (float)$post['total_giftwrap_price'] < 0) {
			$json['error']['price'] = $this->language->get('error_price');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$descriptions = [];

			foreach ((array)($post['total_giftwrap_description'] ?? []) as $language_id => $text) {
				$descriptions[(int)$language_id] = oc_substr(trim(html_entity_decode((string)$text, ENT_QUOTES, 'UTF-8')), 0, 255);
			}

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('total_giftwrap', [
				'total_giftwrap_status'       => (int)!empty($post['total_giftwrap_status']),
				'total_giftwrap_price'        => round((float)$post['total_giftwrap_price'], 4),
				'total_giftwrap_tax_class_id' => (int)($post['total_giftwrap_tax_class_id'] ?? 0),
				'total_giftwrap_message'      => (int)!empty($post['total_giftwrap_message']),
				'total_giftwrap_length'       => max(20, min(1000, (int)($post['total_giftwrap_length'] ?? 250))),
				'total_giftwrap_description'  => $descriptions,
				'total_giftwrap_sort_order'   => (int)($post['total_giftwrap_sort_order'] ?? 4)
			]);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// ---------------------------------------------------------------- admin order screens

	private function message(int $order_id): ?string {
		$row = $order_id ? $this->db->query("SELECT `message` FROM `" . DB_PREFIX . "giftwrap_order` WHERE `order_id` = '" . $order_id . "'")->row : [];

		return $row ? (string)$row['message'] : null;
	}

	/**
	 * admin/view/sale/order_info/after: a gift notice at the top of the order page.
	 */
	public function orderInfo(string &$route, array &$args, mixed &$output): void {
		$message = $this->message((int)($this->request->get['order_id'] ?? 0));

		if ($message === null) {
			return;
		}

		$this->load->language($this->route, 'giftwrap');

		$html = '<div class="alert alert-warning"><i class="fa-solid fa-gift"></i> <strong>' . $this->language->get('giftwrap_text_order') . '</strong>' . ($message !== '' ? '<div class="mt-2 p-2 bg-body rounded fst-italic">' . nl2br(htmlspecialchars($message)) . '</div>' : ' ' . $this->language->get('giftwrap_text_no_message')) . '</div>';

		// After the page header: the page header has its own container-fluid, so take the next one.
		$header = strpos($output, '<div class="page-header">');
		$first = $header !== false ? strpos($output, '<div class="container-fluid">', $header) : false;
		$second = $first !== false ? strpos($output, '<div class="container-fluid">', $first + 1) : false;

		if ($second !== false) {
			$output = substr_replace($output, $html, $second + strlen('<div class="container-fluid">'), 0);
		}
	}

	/**
	 * admin/view/sale/order_invoice/before and order_shipping/before: the message with the order's comment.
	 */
	public function printout(string &$route, array &$args): void {
		$this->load->language($this->route, 'giftwrap');

		foreach ((array)($args['orders'] ?? []) as $i => $order) {
			$message = $this->message((int)($order['order_id'] ?? 0));

			if ($message !== null) {
				$note = '<strong>' . $this->language->get('giftwrap_text_order') . '</strong>' . ($message !== '' ? '<br/>' . nl2br(htmlspecialchars($message)) : '');

				$args['orders'][$i]['comment'] = (!empty($order['comment']) ? $order['comment'] . '<br/><br/>' : '') . $note;
			}
		}
	}

	// ---------------------------------------------------------------- install

	private function events(): array {
		return [
			'giftwrap_add_order'  => ['catalog/model/checkout/order.addOrder/after', 'extension/giftwrap/event/giftwrap.addOrder'],
			'giftwrap_edit_order' => ['catalog/model/checkout/order.editOrder/after', 'extension/giftwrap/event/giftwrap.editOrder'],
			'giftwrap_success'    => ['catalog/controller/checkout/success/before', 'extension/giftwrap/event/giftwrap.success'],
			'giftwrap_mail'       => ['catalog/view/mail/order_add/before', 'extension/giftwrap/event/giftwrap.mail'],
			'giftwrap_checkout'   => ['catalog/view/checkout/checkout/after', 'extension/giftwrap/checkout/giftwrap.checkout'],
			'giftwrap_order_info' => ['admin/view/sale/order_info/after', $this->route . '.orderInfo'],
			'giftwrap_invoice'    => ['admin/view/sale/order_invoice/before', $this->route . '.printout'],
			'giftwrap_shipping'   => ['admin/view/sale/order_shipping/before', $this->route . '.printout']
		];
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "giftwrap_order` (
			`order_id` INT(11) NOT NULL,
			`message` TEXT NOT NULL,
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		foreach ($this->events() as $code => [$trigger, $action]) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent(['code' => $code, 'description' => 'Gift wrap', 'trigger' => $trigger, 'action' => $action, 'status' => 1, 'sort_order' => 0]);
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('total_giftwrap', $this->defaults);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (array_keys($this->events()) as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		// Gift messages of past orders stay: you may still need to wrap them.
	}
}
