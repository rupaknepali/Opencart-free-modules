<?php
namespace Opencart\Admin\Controller\Extension\Stickycart\Module;
/**
 * Sticky Add to Cart: a bar with price and Add to Cart that stays on screen on product pages.
 */
class Stickycart extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/stickycart/module/stickycart';

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
			'label' => 'Add to Cart',
			'desktop' => 0,
			'status' => 0
		];

		foreach ($defaults as $key => $value) {
			$setting = $this->config->get('module_stickycart_' . $key);

			$data['module_stickycart_' . $key] = ($setting === null) ? $value : $setting;
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

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_stickycart', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'stickycart_product',
			'description' => 'Sticky Add to Cart: sticky bar on product pages',
			'trigger'     => 'catalog/view/product/product/after',
			'action'      => 'extension/stickycart/event/stickycart.product',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('stickycart_product');
	}
}
