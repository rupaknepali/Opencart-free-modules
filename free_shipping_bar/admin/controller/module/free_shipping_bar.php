<?php
namespace Opencart\Admin\Controller\Extension\FreeShippingBar\Module;
/**
 * Class FreeShippingBar
 *
 * Free Shipping Progress Bar for OpenCart 4.1.0.x
 *
 * @package Opencart\Admin\Controller\Extension\FreeShippingBar\Module
 */
class FreeShippingBar extends \Opencart\System\Engine\Controller {
	/**
	 * Catalog events registered on install. code => [trigger, method]
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $events = [
		'free_shipping_bar_cart_list' => ['catalog/view/checkout/cart_list/after', 'cartList'],
		'free_shipping_bar_cart'      => ['catalog/view/checkout/cart/after', 'cart'],
		'free_shipping_bar_mini_cart' => ['catalog/view/common/cart/after', 'miniCart']
	];

	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/free_shipping_bar/module/free_shipping_bar');

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
			'href' => $this->url->link('extension/free_shipping_bar/module/free_shipping_bar', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/free_shipping_bar/module/free_shipping_bar.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$data['module_free_shipping_bar_status'] = $this->config->get('module_free_shipping_bar_status');
		$data['module_free_shipping_bar_source'] = $this->config->get('module_free_shipping_bar_source') ?: 'free';
		$data['module_free_shipping_bar_total'] = $this->config->get('module_free_shipping_bar_total');
		$data['module_free_shipping_bar_cart'] = $this->config->get('module_free_shipping_bar_cart');
		$data['module_free_shipping_bar_mini_cart'] = $this->config->get('module_free_shipping_bar_mini_cart');
		$data['module_free_shipping_bar_suggest'] = (int)$this->config->get('module_free_shipping_bar_suggest');
		$data['module_free_shipping_bar_category'] = $this->config->get('module_free_shipping_bar_category');

		// Show the store owner what the Free Shipping method is set to, so the two can't silently disagree.
		if ($this->config->get('shipping_free_status')) {
			$data['text_free_method'] = sprintf($this->language->get('text_free_enabled'), $this->currency->format((float)$this->config->get('shipping_free_total'), $this->config->get('config_currency')));
		} else {
			$data['text_free_method'] = $this->language->get('text_free_disabled');
		}

		$data['free_geo_zone'] = (bool)$this->config->get('shipping_free_geo_zone_id');

		$data['currency'] = $this->config->get('config_currency');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/free_shipping_bar/module/free_shipping_bar', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/free_shipping_bar/module/free_shipping_bar');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/free_shipping_bar/module/free_shipping_bar')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (!in_array($post['module_free_shipping_bar_source'] ?? '', ['free', 'custom'])) {
			$post['module_free_shipping_bar_source'] = 'free';
		}

		$post['module_free_shipping_bar_total'] = max(0, (float)($post['module_free_shipping_bar_total'] ?? 0));
		$post['module_free_shipping_bar_suggest'] = min(6, max(0, (int)($post['module_free_shipping_bar_suggest'] ?? 0)));

		if ($post['module_free_shipping_bar_source'] == 'custom' && $post['module_free_shipping_bar_total'] <= 0) {
			$json['error']['total'] = $this->language->get('error_total');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_free_shipping_bar', $post);

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
				'description' => 'Free Shipping Progress Bar',
				'trigger'     => $event[0],
				'action'      => 'extension/free_shipping_bar/module/free_shipping_bar.' . $event[1],
				'status'      => 1,
				'sort_order'  => 0
			]);
		}

		$this->load->model('setting/setting');

		$this->model_setting_setting->editSetting('module_free_shipping_bar', [
			'module_free_shipping_bar_status'    => 0,
			'module_free_shipping_bar_source'    => 'free',
			'module_free_shipping_bar_total'     => 0,
			'module_free_shipping_bar_cart'      => 1,
			'module_free_shipping_bar_mini_cart' => 1,
			'module_free_shipping_bar_suggest'   => 3,
			'module_free_shipping_bar_category'  => 1
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
}
