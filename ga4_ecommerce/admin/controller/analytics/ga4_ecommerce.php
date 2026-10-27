<?php
namespace Opencart\Admin\Controller\Extension\Ga4Ecommerce\Analytics;
/**
 * Class Ga4Ecommerce
 *
 * GA4 Ecommerce Events for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\Ga4Ecommerce\Analytics
 */
class Ga4Ecommerce extends \Opencart\System\Engine\Controller {
	/**
	 * Catalog events registered on install. code => [trigger, method]
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $events = [
		'ga4_ecommerce_list'     => ['catalog/model/catalog/product.getProducts/after', 'productList'],
		'ga4_ecommerce_product'  => ['catalog/view/product/product/after', 'product'],
		'ga4_ecommerce_cart_add' => ['catalog/controller/checkout/cart.add/after', 'cartAdd'],
		'ga4_ecommerce_cart'     => ['catalog/view/checkout/cart/after', 'cart'],
		'ga4_ecommerce_checkout' => ['catalog/view/checkout/checkout/after', 'checkout'],
		'ga4_ecommerce_success'  => ['catalog/controller/checkout/success/before', 'success'],
		'ga4_ecommerce_footer'   => ['catalog/view/common/footer/after', 'footer']
	];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/ga4_ecommerce/analytics/ga4_ecommerce');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=analytics')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/ga4_ecommerce/analytics/ga4_ecommerce', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/ga4_ecommerce/analytics/ga4_ecommerce.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=analytics');

		$data['analytics_ga4_ecommerce_status'] = $this->config->get('analytics_ga4_ecommerce_status');
		$data['analytics_ga4_ecommerce_measurement_id'] = $this->config->get('analytics_ga4_ecommerce_measurement_id');
		$data['analytics_ga4_ecommerce_item_id'] = $this->config->get('analytics_ga4_ecommerce_item_id') ?: 'product_id';
		$data['analytics_ga4_ecommerce_tax'] = $this->config->get('analytics_ga4_ecommerce_tax');
		$data['analytics_ga4_ecommerce_list'] = $this->config->get('analytics_ga4_ecommerce_list');
		$data['analytics_ga4_ecommerce_debug'] = $this->config->get('analytics_ga4_ecommerce_debug');

		$data['cookie_policy'] = (bool)$this->config->get('config_cookie_id');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/ga4_ecommerce/analytics/ga4_ecommerce', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/ga4_ecommerce/analytics/ga4_ecommerce');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/ga4_ecommerce/analytics/ga4_ecommerce')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		$post['analytics_ga4_ecommerce_measurement_id'] = strtoupper(trim((string)($post['analytics_ga4_ecommerce_measurement_id'] ?? '')));

		if (!preg_match('/^G-[A-Z0-9]{4,20}$/', $post['analytics_ga4_ecommerce_measurement_id'])) {
			$json['error']['measurement_id'] = $this->language->get('error_measurement_id');
		}

		if (!in_array($post['analytics_ga4_ecommerce_item_id'] ?? '', ['product_id', 'model', 'sku'])) {
			$post['analytics_ga4_ecommerce_item_id'] = 'product_id';
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('analytics_ga4_ecommerce', $post);

			$json['success'] = $this->language->get('text_success');
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
		if (!$this->user->hasPermission('modify', 'extension/analytics')) {
			return;
		}

		$this->load->model('setting/event');

		foreach ($this->events as $code => $event) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'GA4 Ecommerce Events',
				'trigger'     => $event[0],
				'action'      => 'extension/ga4_ecommerce/analytics/ga4_ecommerce.' . $event[1],
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('analytics_ga4_ecommerce', [
			'analytics_ga4_ecommerce_status'         => 0,
			'analytics_ga4_ecommerce_measurement_id' => '',
			'analytics_ga4_ecommerce_item_id'        => 'product_id',
			'analytics_ga4_ecommerce_tax'            => 0,
			'analytics_ga4_ecommerce_list'           => 1,
			'analytics_ga4_ecommerce_debug'          => 0
		]);
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		if (!$this->user->hasPermission('modify', 'extension/analytics')) {
			return;
		}

		$this->load->model('setting/event');

		foreach (array_keys($this->events) as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}
	}
}
