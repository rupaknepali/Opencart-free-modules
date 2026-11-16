<?php
namespace Opencart\Admin\Controller\Extension\Productbadges\Module;
/**
 * Product Badges: settings page, and the events that draw the badges.
 */
class Productbadges extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/productbadges/module/productbadges';

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')];
		$data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token)];

		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$defaults = [
			'sale'             => 1,
			'sale_format'      => 'percent',
			'new'              => 1,
			'new_days'         => 30,
			'bestseller'       => 1,
			'bestseller_limit' => 10,
			'bestseller_days'  => 90,
			'soldout'          => 1,
			'position'         => 'left',
			'product_page'     => 1,
			'status'           => 0
		];

		foreach ($defaults as $key => $value) {
			$setting = $this->config->get('module_productbadges_' . $key);

			$data['module_productbadges_' . $key] = ($setting === null || $setting === '') ? $value : $setting;
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

		if ((int)($post['module_productbadges_new_days'] ?? 0) < 1) {
			$json['error']['new-days'] = $this->language->get('error_days');
		}

		if ((int)($post['module_productbadges_bestseller_limit'] ?? 0) < 1 || (int)($post['module_productbadges_bestseller_days'] ?? 0) < 1) {
			$json['error']['bestseller'] = $this->language->get('error_bestseller');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_productbadges', $post);

			// New bestseller settings should apply at once, not after the cache expires.
			$this->cache->delete('productbadges');

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$events = [
			'productbadges_thumb'   => ['catalog/view/product/thumb/after', 'thumb', 'Product Badges: badges on product cards'],
			'productbadges_product' => ['catalog/view/product/product/after', 'product', 'Product Badges: badges on the product page image'],
			'productbadges_header'  => ['catalog/view/common/header/after', 'header', 'Product Badges: badge styles']
		];

		foreach ($events as $code => [$trigger, $method, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => $description,
				'trigger'     => $trigger,
				'action'      => 'extension/productbadges/event/productbadges.' . $method,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (['productbadges_thumb', 'productbadges_product', 'productbadges_header'] as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}
	}
}
