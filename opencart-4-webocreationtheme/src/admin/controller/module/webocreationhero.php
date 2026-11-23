<?php
namespace Opencart\Admin\Controller\Extension\Webocreationtheme\Module;
/**
 * Webocreation Hero: slideshow with text tabs and a side panel (blog articles, specials or new products).
 */
class Webocreationhero extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/webocreationtheme/module/webocreationhero';

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];
		$module_id = (int)($this->request->get['module_id'] ?? 0);

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token . ($module_id ? '&module_id=' . $module_id : ''))]
		];

		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$this->load->model('setting/module');

		$module = $module_id ? $this->model_setting_module->getModule($module_id) : [];

		$defaults = ['name' => '', 'banner_id' => 0, 'width' => 1140, 'height' => 500, 'tabs' => 1, 'panel' => 'specials', 'panel_title' => [], 'panel_limit' => 4, 'status' => 1];

		foreach ($defaults as $key => $value) {
			$data[$key] = $module[$key] ?? $value;
		}

		$data['module_id'] = $module_id;

		$this->load->model('design/banner');

		$data['banners'] = $this->model_design_banner->getBanners();

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

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

		$post = $this->request->post + ['module_id' => 0, 'name' => '', 'banner_id' => 0];

		if (!oc_validate_length((string)$post['name'], 3, 64)) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!(int)$post['banner_id']) {
			$json['error']['banner'] = $this->language->get('error_banner');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$setting = [
				'name'        => (string)$post['name'],
				'banner_id'   => (int)$post['banner_id'],
				'width'       => max(200, min(2400, (int)($post['width'] ?? 1140))),
				'height'      => max(100, min(1600, (int)($post['height'] ?? 500))),
				'tabs'        => (int)!empty($post['tabs']),
				'panel'       => in_array($post['panel'] ?? '', ['none', 'articles', 'specials', 'latest']) ? $post['panel'] : 'none',
				'panel_title' => array_map(fn($title) => oc_substr(trim((string)$title), 0, 64), (array)($post['panel_title'] ?? [])),
				'panel_limit' => max(1, min(8, (int)($post['panel_limit'] ?? 4))),
				'status'      => (int)!empty($post['status'])
			];

			$this->load->model('setting/module');

			if (!(int)$post['module_id']) {
				$json['module_id'] = $this->model_setting_module->addModule('webocreationtheme.webocreationhero', $setting);
			} else {
				$this->model_setting_module->editModule((int)$post['module_id'], $setting);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
