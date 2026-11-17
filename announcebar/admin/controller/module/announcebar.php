<?php
namespace Opencart\Admin\Controller\Extension\Announcebar\Module;
/**
 * Announcement Bar: a message bar across the top of every page, with dates and a dismiss button.
 */
class Announcebar extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/announcebar/module/announcebar';

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
			'message' => 'Free UK delivery on orders over £50',
			'link_text' => 'See delivery options',
			'link_url' => '',
			'background' => '#111827',
			'color' => '#ffffff',
			'date_start' => '',
			'date_end' => '',
			'dismiss' => 1,
			'status' => 0
		];

		foreach ($defaults as $key => $value) {
			$setting = $this->config->get('module_announcebar_' . $key);

			$data['module_announcebar_' . $key] = ($setting === null) ? $value : $setting;
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

		if (oc_strlen(trim((string)($post['module_announcebar_message'] ?? ''))) < 1) {
			$json['error']['message'] = $this->language->get('error_message');
		}

		foreach (['date_start', 'date_end'] as $key) {
			if (!empty($post['module_announcebar_' . $key]) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $post['module_announcebar_' . $key])) {
				$json['error']['warning'] = $this->language->get('error_date');
			}
		}

		foreach (['background', 'color'] as $key) {
			if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)($post['module_announcebar_' . $key] ?? ''))) {
				$json['error']['warning'] = $this->language->get('error_colour');
			}
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_announcebar', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'announcebar_header',
			'description' => 'Announcement Bar: bar at the top of every page',
			'trigger'     => 'catalog/view/common/header/after',
			'action'      => 'extension/announcebar/event/announcebar.header',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('announcebar_header');
	}
}
