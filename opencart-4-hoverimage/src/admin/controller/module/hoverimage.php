<?php
namespace Opencart\Admin\Controller\Extension\Hoverimage\Module;
/**
 * Swap Image on Hover: shows a product's second image when shoppers hover over its card.
 */
class Hoverimage extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/hoverimage/module/hoverimage';

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
			'effect' => 'fade'
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_hoverimage');

		foreach ($defaults as $key => $value) {
			$data['module_hoverimage_' . $key] = array_key_exists('module_hoverimage_' . $key, $saved) ? $saved['module_hoverimage_' . $key] : $value;
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

			$this->model_setting_setting->editSetting('module_hoverimage', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'hoverimage_thumb',
			'description' => 'Swap Image on Hover: second image on product cards',
			'trigger'     => 'catalog/view/product/thumb/after',
			'action'      => 'extension/hoverimage/event/hoverimage.thumb',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'hoverimage_header',
			'description' => 'Swap Image on Hover: hover styles',
			'trigger'     => 'catalog/view/common/header/after',
			'action'      => 'extension/hoverimage/event/hoverimage.header',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('hoverimage_thumb');
		$this->model_setting_event->deleteEventByCode('hoverimage_header');
	}
}
