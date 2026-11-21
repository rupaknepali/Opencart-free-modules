<?php
namespace Opencart\Admin\Controller\Extension\Sellinpacks\Module;
/**
 * Sell in Packs: products sold in multiples (packs of 6, 12…) and a maximum quantity per order.
 */
class Sellinpacks extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/sellinpacks/module/sellinpacks';

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

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_sellinpacks');

		$data['module_sellinpacks_status'] = $saved['module_sellinpacks_status'] ?? 0;

		$this->load->model('catalog/product');

		$data['rules'] = [];

		foreach ((array)($saved['module_sellinpacks_rule'] ?? []) as $rule) {
			$product_info = $this->model_catalog_product->getProduct((int)$rule['product_id']);

			if ($product_info) {
				$data['rules'][] = [
					'product_id' => (int)$rule['product_id'],
					'name'       => $product_info['name'],
					'model'      => $product_info['model'],
					'step'       => (int)$rule['step'],
					'max'        => (int)$rule['max']
				];
			}
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

		$rules = [];

		foreach ((array)($post['module_sellinpacks_rule'] ?? []) as $rule) {
			$product_id = (int)($rule['product_id'] ?? 0);
			$step = max(1, (int)($rule['step'] ?? 1));
			$max = max(0, (int)($rule['max'] ?? 0));

			if (!$product_id) {
				continue;
			}

			// A maximum that isn't a whole number of packs can never be reached exactly.
			if ($max && $max % $step) {
				$json['error']['warning'] = sprintf($this->language->get('error_max'), $max, $step);
			}

			$rules[$product_id] = ['product_id' => $product_id, 'step' => $step, 'max' => $max];
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_sellinpacks', [
				'module_sellinpacks_status' => (int)!empty($post['module_sellinpacks_status']),
				'module_sellinpacks_rule'   => $rules
			]);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$events = [
			['sellinpacks_product', 'catalog/view/product/product/after', 'product', 'pack size on the product page'],
			['sellinpacks_thumb', 'catalog/view/product/thumb/after', 'thumb', 'pack size on product cards'],
			['sellinpacks_cart', 'catalog/view/checkout/cart_list/after', 'cart', 'warning in the cart'],
			['sellinpacks_checkout', 'catalog/controller/checkout/checkout/before', 'checkout', 'keep checkout closed until quantities are fixed']
		];

		foreach ($events as [$code, $trigger, $method, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Sell in Packs: ' . $description,
				'trigger'     => $trigger,
				'action'      => 'extension/sellinpacks/event/sellinpacks.' . $method,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (['sellinpacks_product', 'sellinpacks_thumb', 'sellinpacks_cart', 'sellinpacks_checkout'] as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}
	}
}
