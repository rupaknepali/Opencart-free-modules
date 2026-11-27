<?php
namespace Opencart\Admin\Controller\Extension\Freegift\Module;
/**
 * Free Gift with Purchase: settings page and the startup action registration.
 */
class Freegift extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/freegift/module/freegift';

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
		$data['user_token'] = $this->session->data['user_token'];

		$defaults = [
			'status'         => 0,
			'product_id'     => 0,
			'threshold'      => '100',
			'customer_group' => [],
			'label'          => $this->language->get('text_default_label'),
			'progress'       => $this->language->get('text_default_progress'),
			'added'          => $this->language->get('text_default_added'),
			'date_start'     => '',
			'date_end'       => ''
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_freegift');

		foreach ($defaults as $key => $value) {
			$data['module_freegift_' . $key] = array_key_exists('module_freegift_' . $key, $saved) ? $saved['module_freegift_' . $key] : $value;
		}

		$data['module_freegift_customer_group'] = array_map('intval', (array)$data['module_freegift_customer_group']);

		$data['product_name'] = '';
		$data['text_stock'] = '';
		$data['text_given'] = '';

		if ($data['module_freegift_product_id']) {
			$this->load->model('catalog/product');

			$product = $this->model_catalog_product->getProduct((int)$data['module_freegift_product_id']);

			if ($product) {
				$data['product_name'] = $product['name'];
				$data['text_stock'] = sprintf($this->language->get('text_stock'), (int)$product['quantity']);

				$given = $this->db->query("SELECT COALESCE(SUM(op.`quantity`), 0) AS `total` FROM `" . DB_PREFIX . "order_product` op LEFT JOIN `" . DB_PREFIX . "order` o ON (o.`order_id` = op.`order_id`) WHERE op.`product_id` = '" . (int)$product['product_id'] . "' AND op.`price` = '0' AND o.`order_status_id` > '0'")->row;

				$data['text_given'] = sprintf($this->language->get('text_given'), (int)$given['total']);
			}
		}

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

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
		$post['module_freegift_product_id'] = (int)($post['module_freegift_product_id'] ?? 0);
		$post['module_freegift_threshold'] = (string)round((float)str_replace(',', '.', (string)($post['module_freegift_threshold'] ?? 0)), 4);
		$post['module_freegift_customer_group'] = array_values(array_map('intval', (array)($post['module_freegift_customer_group'] ?? [])));

		foreach (['date_start', 'date_end'] as $key) {
			if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($post['module_freegift_' . $key] ?? ''))) {
				$post['module_freegift_' . $key] = '';
			}
		}

		if (!$json && !empty($post['module_freegift_status']) && !$post['module_freegift_product_id']) {
			$json['error']['product'] = $this->language->get('error_product');
		}

		if (!$json && (float)$post['module_freegift_threshold'] <= 0) {
			$json['error']['threshold'] = $this->language->get('error_threshold');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_freegift', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/startup');

		$this->model_setting_startup->addStartup([
			'code'        => 'freegift',
			'description' => 'Free Gift with Purchase: add or remove the gift as the cart changes',
			'action'      => 'catalog/extension/freegift/module/freegift',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'freegift_cart_list',
			'description' => 'Free Gift with Purchase: progress message on the cart page',
			'trigger'     => 'catalog/view/checkout/cart_list/after',
			'action'      => 'extension/freegift/module/freegift.cartList',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/startup');

		$this->model_setting_startup->deleteStartupByCode('freegift');

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('freegift_cart_list');

		// Remove gift lines still sitting in carts, so nobody checks out a free product after uninstalling.
		$this->db->query("DELETE FROM `" . DB_PREFIX . "cart` WHERE `option` LIKE '%\"freegift\"%'");
	}
}
