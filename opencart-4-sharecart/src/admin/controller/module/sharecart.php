<?php
namespace Opencart\Admin\Controller\Extension\Sharecart\Module;
/**
 * Share Cart: settings and a list of recently shared carts.
 */
class Sharecart extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/sharecart/module/sharecart';

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

		$defaults = [
			'status' => 0,
			'button' => $this->language->get('text_default_button'),
			'intro'  => $this->language->get('text_default_intro'),
			'loaded' => $this->language->get('text_default_loaded'),
			'mode'   => 'merge',
			'days'   => 30
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_sharecart');

		foreach ($defaults as $key => $value) {
			$data['module_sharecart_' . $key] = array_key_exists('module_sharecart_' . $key, $saved) ? $saved['module_sharecart_' . $key] : $value;
		}

		$data['recent'] = [];

		$days = (int)$data['module_sharecart_days'];

		$query = $this->db->query("SELECT s.*, c.`firstname`, c.`lastname` FROM `" . DB_PREFIX . "sharecart` s LEFT JOIN `" . DB_PREFIX . "customer` c ON (c.`customer_id` = s.`customer_id`) ORDER BY s.`date_added` DESC LIMIT 25");

		foreach ($query->rows as $row) {
			$items = json_decode($row['items'], true) ?: [];

			$data['recent'][] = [
				'code'     => $row['code'],
				'url'      => HTTP_CATALOG . 'index.php?route=extension/sharecart/module/sharecart.open&code=' . $row['code'],
				'items'    => array_sum(array_column($items, 'quantity')),
				'customer' => $row['customer_id'] ? trim($row['firstname'] . ' ' . $row['lastname']) : $this->language->get('column_guest'),
				'opened'   => (int)$row['opened'],
				'date'     => date($this->language->get('datetime_format'), strtotime($row['date_added'])),
				'expired'  => $days && strtotime($row['date_added']) < time() - $days * 86400
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

		if (!$json && substr_count((string)($post['module_sharecart_loaded'] ?? ''), '%s') != 1) {
			$json['error']['loaded'] = $this->language->get('error_loaded');
		}

		$post['module_sharecart_days'] = max(0, (int)($post['module_sharecart_days'] ?? 0));
		$post['module_sharecart_mode'] = ($post['module_sharecart_mode'] ?? '') == 'replace' ? 'replace' : 'merge';

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_sharecart', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "sharecart` (
			`sharecart_id` INT(11) NOT NULL AUTO_INCREMENT,
			`code` VARCHAR(16) NOT NULL,
			`store_id` INT(11) NOT NULL DEFAULT '0',
			`customer_id` INT(11) NOT NULL DEFAULT '0',
			`session_id` VARCHAR(32) NOT NULL DEFAULT '',
			`items` MEDIUMTEXT NOT NULL,
			`opened` INT(11) NOT NULL DEFAULT '0',
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`sharecart_id`),
			UNIQUE KEY `code` (`code`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'sharecart_cart_list',
			'description' => 'Share Cart: share box under the cart',
			'trigger'     => 'catalog/view/checkout/cart_list/after',
			'action'      => 'extension/sharecart/module/sharecart.cartList',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('sharecart_cart_list');

		// The table is kept so links that were already sent keep working after a reinstall.
	}
}
