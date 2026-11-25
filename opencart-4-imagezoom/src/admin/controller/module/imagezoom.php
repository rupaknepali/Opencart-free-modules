<?php
namespace Opencart\Admin\Controller\Extension\Imagezoom\Module;
/**
 * Product Image Zoom: settings page.
 */
class Imagezoom extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/imagezoom/module/imagezoom';

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
			'status'   => 0,
			'hover'    => 1,
			'swap'     => 1,
			'lightbox' => 1,
			'size'     => 1600,
			'min'      => '1.3'
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_imagezoom');

		foreach ($defaults as $key => $value) {
			$data['module_imagezoom_' . $key] = array_key_exists('module_imagezoom_' . $key, $saved) ? $saved['module_imagezoom_' . $key] : $value;
		}

		$thumb_w = (int)$this->config->get('config_image_thumb_width');
		$thumb_h = (int)$this->config->get('config_image_thumb_height');

		$data['text_sizes'] = sprintf($this->language->get('text_sizes'), $thumb_w, $thumb_h, (int)$this->config->get('config_image_popup_width'), (int)$this->config->get('config_image_popup_height'), $thumb_w * 2, $thumb_h * 2);

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
		$size = (int)($post['module_imagezoom_size'] ?? 0);

		if (!$json && ($size < 800 || $size > 4000)) {
			$json['error']['size'] = $this->language->get('error_size');
		}

		$post['module_imagezoom_min'] = (string)max(1, min(4, round((float)str_replace(',', '.', (string)($post['module_imagezoom_min'] ?? '1.3')), 2)));

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_imagezoom', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'imagezoom_product',
			'description' => 'Product Image Zoom: zoom and gallery on product pages',
			'trigger'     => 'catalog/view/product/product/after',
			'action'      => 'extension/imagezoom/module/imagezoom.product',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('imagezoom_product');
	}
}
