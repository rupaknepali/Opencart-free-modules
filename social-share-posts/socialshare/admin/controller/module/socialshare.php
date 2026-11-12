<?php
namespace Opencart\Admin\Controller\Extension\Socialshare\Module;

class Socialshare extends \Opencart\System\Engine\Controller {
	private array $networks = ['facebook', 'x', 'linkedin', 'pinterest', 'whatsapp', 'telegram', 'reddit', 'bluesky', 'email', 'copy', 'native'];

	private array $positions = ['inline', 'left', 'right', 'top', 'bottom'];

	public function index(): void {
		$this->load->language('extension/socialshare/module/socialshare');

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $token)
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', $token . '&type=module')
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/socialshare/module/socialshare', $token)
		];

		$data['save'] = $this->url->link('extension/socialshare/module/socialshare.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$data['networks'] = [];

		foreach ($this->networks as $code) {
			$data['networks'][] = [
				'code' => $code,
				'name' => $this->language->get('text_network_' . $code)
			];
		}

		$data['positions'] = [];

		foreach ($this->positions as $code) {
			$data['positions'][] = [
				'code' => $code,
				'name' => $this->language->get('text_position_' . $code)
			];
		}

		// First time on the page: a sensible default selection.
		$selected = $this->config->get('module_socialshare_networks');

		$data['module_socialshare_networks'] = is_array($selected) ? $selected : ['facebook', 'x', 'pinterest', 'whatsapp', 'email', 'copy', 'native'];
		$data['module_socialshare_heading'] = $this->config->get('module_socialshare_heading') ?? $this->language->get('text_default_heading');
		$data['module_socialshare_position'] = $this->config->get('module_socialshare_position') ?: 'inline';
		$data['module_socialshare_label'] = $this->config->get('module_socialshare_label');
		$data['module_socialshare_og'] = $this->config->get('module_socialshare_og') ?? 1;
		$data['module_socialshare_status'] = $this->config->get('module_socialshare_status');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/socialshare/module/socialshare', $data));
	}

	public function save(): void {
		$this->load->language('extension/socialshare/module/socialshare');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/socialshare/module/socialshare')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$post = $this->request->post;

			// Only known networks; keep an empty list when every box is unticked.
			$post['module_socialshare_networks'] = array_values(array_intersect($this->networks, (array)($post['module_socialshare_networks'] ?? [])));

			if (!in_array($post['module_socialshare_position'] ?? '', $this->positions)) {
				$post['module_socialshare_position'] = 'inline';
			}

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_socialshare', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'socialshare',
			'description' => 'Social Share: add Open Graph and Twitter Card tags to the page head',
			'trigger'     => 'catalog/view/common/header/after',
			'action'      => 'extension/socialshare/event/socialshare',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('socialshare');
	}
}
