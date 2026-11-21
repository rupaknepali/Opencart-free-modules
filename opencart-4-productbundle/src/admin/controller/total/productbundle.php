<?php
namespace Opencart\Admin\Controller\Extension\Productbundle\Total;
/**
 * Product Bundles: "buy these together and save 10%", applied as a real discount in the cart and at checkout.
 */
class Productbundle extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/productbundle/total/productbundle';

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
		$data['user_token'] = $this->session->data['user_token'];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('total_productbundle');

		$defaults = [
			'status'     => 0,
			'sort_order' => 2,
			'coupon'     => 1,
			'product'    => 1,
			'cart'       => 1
		];

		foreach ($defaults as $key => $value) {
			$data['total_productbundle_' . $key] = array_key_exists('total_productbundle_' . $key, $saved) ? $saved['total_productbundle_' . $key] : $value;
		}

		$this->load->model('catalog/product');

		$data['bundles'] = [];

		foreach ((array)($saved['total_productbundle_bundles'] ?? []) as $bundle) {
			$products = [];

			foreach ((array)$bundle['products'] as $product_id) {
				$product_info = $this->model_catalog_product->getProduct((int)$product_id);

				if ($product_info) {
					$products[] = ['product_id' => (int)$product_id, 'name' => $product_info['name']];
				}
			}

			$data['bundles'][] = [
				'name'     => $bundle['name'],
				'discount' => $bundle['discount'],
				'status'   => (int)$bundle['status'],
				'products' => $products
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

		$bundles = [];

		foreach ((array)($post['total_productbundle_bundles'] ?? []) as $row => $bundle) {
			$name = trim((string)($bundle['name'] ?? ''));
			$discount = (float)($bundle['discount'] ?? 0);
			$products = array_values(array_unique(array_filter(array_map('intval', (array)($bundle['products'] ?? [])))));

			if ($name === '' && !$products) {
				continue;
			}

			if ($name === '') {
				$json['error']['bundle-' . $row] = $this->language->get('error_name');
			} elseif (count($products) < 2) {
				$json['error']['bundle-' . $row] = $this->language->get('error_products');
			} elseif ($discount <= 0 || $discount >= 100) {
				$json['error']['bundle-' . $row] = $this->language->get('error_discount');
			}

			$bundles[] = [
				'name'     => oc_substr($name, 0, 64),
				'discount' => round($discount, 2),
				'products' => array_slice($products, 0, 6),
				'status'   => (int)!empty($bundle['status'])
			];
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['total_productbundle_bundles'] = $bundles;
			$post['total_productbundle_sort_order'] = (int)($post['total_productbundle_sort_order'] ?? 2);

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('total_productbundle', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$events = [
			['productbundle_product', 'catalog/view/product/product/after', 'product', 'bundle offer on the product page'],
			['productbundle_cart', 'catalog/view/checkout/cart_list/after', 'cart', 'complete-the-bundle hint in the cart']
		];

		foreach ($events as [$code, $trigger, $method, $description]) {
			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => 'Product Bundles: ' . $description,
				'trigger'     => $trigger,
				'action'      => 'extension/productbundle/event/productbundle.' . $method,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (['productbundle_product', 'productbundle_cart'] as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}
	}
}
