<?php
namespace Opencart\Admin\Controller\Extension\Tiktok\Module;
/**
 * TikTok Pixel and Events API: ViewContent, AddToCart, InitiateCheckout and Purchase in the browser,
 * Purchase from the server too, deduplicated, and only with the visitor's consent.
 */
class Tiktok extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/tiktok/module/tiktok';

	private array $defaults = [
		'status'       => 0,
		'pixel_id'     => '',
		'access_token' => '',
		'test_code'    => '',
		'consent'      => 'opencart',
		'matching'     => 1,
		'content_id'   => 'product_id'
	];

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

		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_tiktok');

		foreach ($this->defaults as $key => $value) {
			$data['module_tiktok_' . $key] = array_key_exists('module_tiktok_' . $key, $saved) ? $saved['module_tiktok_' . $key] : $value;
		}

		// The OpenCart cookie bar only appears when a cookie policy page is chosen.
		$data['cookie_bar'] = (bool)$this->config->get('config_cookie_id');
		$data['setting_link'] = $this->url->link('setting/setting', $token);

		$data['log'] = [];

		foreach ($this->db->query("SELECT * FROM `" . DB_PREFIX . "tiktok_order` WHERE `sent` > 0 ORDER BY `date_sent` DESC LIMIT 10")->rows as $row) {
			$data['log'][] = [
				'order_id' => $row['order_id'],
				'date'     => date($this->language->get('datetime_format'), strtotime($row['date_sent'])),
				'ok'       => $row['sent'] == 1,
				'message'  => $row['message']
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
		$post = $this->request->post;

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$pixel = strtoupper(trim((string)($post['module_tiktok_pixel_id'] ?? '')));

		if (!empty($post['module_tiktok_status']) && !preg_match('/^[A-Z0-9]{10,30}$/', $pixel)) {
			$json['error']['pixel-id'] = $this->language->get('error_pixel_id');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_tiktok', [
				'module_tiktok_status'       => (int)!empty($post['module_tiktok_status']),
				'module_tiktok_pixel_id'     => $pixel,
				'module_tiktok_access_token' => trim((string)($post['module_tiktok_access_token'] ?? '')),
				'module_tiktok_test_code'    => trim((string)($post['module_tiktok_test_code'] ?? '')),
				'module_tiktok_consent'      => in_array($post['module_tiktok_consent'] ?? '', ['opencart', 'custom', 'none']) ? $post['module_tiktok_consent'] : 'opencart',
				'module_tiktok_matching'     => (int)!empty($post['module_tiktok_matching']),
				'module_tiktok_content_id'   => ($post['module_tiktok_content_id'] ?? '') == 'model' ? 'model' : 'product_id'
			]);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		// What the Events API needs at purchase time, saved when the order is created: payment gateways often
		// confirm orders from their own server, where the shopper's session and cookies aren't available.
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tiktok_order` (
			`order_id` INT(11) NOT NULL,
			`consent` TINYINT(1) NOT NULL DEFAULT '0',
			`ttclid` VARCHAR(255) NOT NULL DEFAULT '',
			`ttp` VARCHAR(255) NOT NULL DEFAULT '',
			`sent` TINYINT(1) NOT NULL DEFAULT '0',
			`message` VARCHAR(255) NOT NULL DEFAULT '',
			`date_added` DATETIME NOT NULL,
			`date_sent` DATETIME NULL,
			PRIMARY KEY (`order_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		$events = [
			['tiktok_header', 'catalog/view/common/header/after', 'header', 'pixel base code and page view'],
			['tiktok_product', 'catalog/view/product/product/after', 'product', 'ViewContent'],
			['tiktok_cart', 'catalog/controller/checkout/cart.add/after', 'cart', 'AddToCart'],
			['tiktok_checkout', 'catalog/view/checkout/checkout/after', 'checkout', 'InitiateCheckout'],
			['tiktok_success_before', 'catalog/controller/checkout/success/before', 'successBefore', 'remember the order on the success page'],
			['tiktok_success', 'catalog/view/common/success/after', 'success', 'Purchase in the browser'],
			['tiktok_order_add', 'catalog/model/checkout/order.addOrder/after', 'orderAdd', 'save click ID and consent with the order'],
			['tiktok_order_history', 'catalog/model/checkout/order.addHistory/after', 'history', 'Purchase from the server']
		];

		foreach ($events as [$code, $trigger, $method, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'TikTok: ' . $description,
				'trigger'     => $trigger,
				'action'      => 'extension/tiktok/event/tiktok.' . $method,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (['tiktok_header', 'tiktok_product', 'tiktok_cart', 'tiktok_checkout', 'tiktok_success_before', 'tiktok_success', 'tiktok_order_add', 'tiktok_order_history'] as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}

		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tiktok_order`");
	}
}
