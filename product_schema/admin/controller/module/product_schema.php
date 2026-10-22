<?php
namespace Opencart\Admin\Controller\Extension\ProductSchema\Module;
/**
 * Class ProductSchema
 *
 * Product Structured Data (JSON-LD) for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\ProductSchema\Module
 */
class ProductSchema extends \Opencart\System\Engine\Controller {
	/**
	 * Catalog events registered on install. code => [trigger, method]
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $events = [
		'product_schema_product'  => ['catalog/view/product/product/after', 'product'],
		'product_schema_category' => ['catalog/view/product/category/after', 'category'],
		'product_schema_home'     => ['catalog/view/common/home/after', 'home']
	];

	/**
	 * @var array<int, string>
	 */
	private array $availability = ['InStock', 'OutOfStock', 'PreOrder', 'BackOrder', 'LimitedAvailability', 'SoldOut', 'Discontinued', 'InStoreOnly', 'OnlineOnly', 'PreSale'];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/product_schema/module/product_schema');

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
			'href' => $this->url->link('extension/product_schema/module/product_schema', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/product_schema/module/product_schema.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$fields = [
			'status'          => 0,
			'product'         => 1,
			'breadcrumb'      => 1,
			'organization'    => 1,
			'website'         => 1,
			'reviews'         => 5,
			'condition'       => 'NewCondition',
			'gtin'            => 'EAN,UPC,JAN,ISBN',
			'return_status'   => 0,
			'return_country'  => '',
			'return_days'     => 30,
			'return_fees'     => 'FreeReturn',
			'shipping_status' => 0,
			'shipping_country' => '',
			'shipping_rate'   => 0,
			'handling_min'    => 0,
			'handling_max'    => 1,
			'transit_min'     => 1,
			'transit_max'     => 5,
			'same_as'         => ''
		];

		foreach ($fields as $field => $default) {
			$value = $this->config->get('module_product_schema_' . $field);

			$data['module_product_schema_' . $field] = ($value !== null) ? $value : $default;
		}

		// Availability mapping per stock status
		$this->load->model('localisation/stock_status');

		$map = (array)$this->config->get('module_product_schema_availability');

		$data['stock_statuses'] = [];

		foreach ($this->model_localisation_stock_status->getStockStatuses() as $stock_status) {
			$data['stock_statuses'][] = [
				'stock_status_id' => $stock_status['stock_status_id'],
				'name'            => $stock_status['name'],
				'value'           => $map[$stock_status['stock_status_id']] ?? $this->guessAvailability($stock_status['name'])
			];
		}

		$data['availabilities'] = $this->availability;

		// Identifier codes for the GTIN help text
		$this->load->model('catalog/identifier');

		$codes = [];

		foreach ($this->model_catalog_identifier->getIdentifiers() as $identifier) {
			$codes[] = $identifier['code'];
		}

		$data['help_gtin'] = sprintf($this->language->get('help_gtin'), implode(', ', $codes));

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/product_schema/module/product_schema', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/product_schema/module/product_schema');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/product_schema/module/product_schema')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (!empty($post['module_product_schema_return_status']) && !preg_match('/^[A-Za-z]{2}$/', (string)($post['module_product_schema_return_country'] ?? ''))) {
			$json['error']['return_country'] = $this->language->get('error_country');
		}

		if (!empty($post['module_product_schema_shipping_status']) && !preg_match('/^[A-Za-z]{2}$/', (string)($post['module_product_schema_shipping_country'] ?? ''))) {
			$json['error']['shipping_country'] = $this->language->get('error_country');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['module_product_schema_reviews'] = min(10, max(0, (int)($post['module_product_schema_reviews'] ?? 5)));
			$post['module_product_schema_return_country'] = strtoupper((string)($post['module_product_schema_return_country'] ?? ''));
			$post['module_product_schema_shipping_country'] = strtoupper((string)($post['module_product_schema_shipping_country'] ?? ''));
			$post['module_product_schema_gtin'] = strtoupper(preg_replace('/\s+/', '', (string)($post['module_product_schema_gtin'] ?? '')));

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_product_schema', $post);

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
		if (!$this->user->hasPermission('modify', 'extension/module')) {
			return;
		}

		$this->load->model('setting/event');

		foreach ($this->events as $code => $event) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Product Structured Data (JSON-LD)',
				'trigger'     => $event[0],
				'action'      => 'extension/product_schema/module/product_schema.' . $event[1],
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		// Default availability mapping from stock status names
		$this->load->model('localisation/stock_status');

		$map = [];

		foreach ($this->model_localisation_stock_status->getStockStatuses() as $stock_status) {
			$map[$stock_status['stock_status_id']] = $this->guessAvailability($stock_status['name']);
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_product_schema', [
			'module_product_schema_status'       => 0,
			'module_product_schema_product'      => 1,
			'module_product_schema_breadcrumb'   => 1,
			'module_product_schema_organization' => 1,
			'module_product_schema_website'      => 1,
			'module_product_schema_reviews'      => 5,
			'module_product_schema_condition'    => 'NewCondition',
			'module_product_schema_gtin'         => 'EAN,UPC,JAN,ISBN',
			'module_product_schema_availability' => $map,
			'module_product_schema_return_days'  => 30,
			'module_product_schema_return_fees'  => 'FreeReturn',
			'module_product_schema_handling_min' => 0,
			'module_product_schema_handling_max' => 1,
			'module_product_schema_transit_min'  => 1,
			'module_product_schema_transit_max'  => 5
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
	}

	/**
	 * Guess Availability
	 *
	 * @param string $name stock status name
	 *
	 * @return string
	 */
	private function guessAvailability(string $name): string {
		$name = oc_strtolower($name);

		if (str_contains($name, 'pre-order') || str_contains($name, 'preorder') || str_contains($name, 'pre order')) {
			return 'PreOrder';
		}

		if (str_contains($name, 'out of stock') || str_contains($name, 'sold out')) {
			return 'OutOfStock';
		}

		if (str_contains($name, 'in stock')) {
			return 'InStock';
		}

		if (preg_match('/\d/', $name) || str_contains($name, 'day') || str_contains($name, 'week')) {
			return 'BackOrder';
		}

		return 'OutOfStock';
	}
}
