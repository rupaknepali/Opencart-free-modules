<?php
namespace Opencart\Admin\Controller\Extension\Ageverify\Module;
/**
 * Age Verification: an age confirmation popup on the first visit.
 */
class Ageverify extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/ageverify/module/ageverify';

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
			'age' => 18,
			'title' => 'Are you %s or older?',
			'message' => 'You must be of legal age to enter this site.',
			'yes' => 'Yes, enter',
			'no' => 'No, leave',
			'exit_url' => 'https://www.google.com/',
			'days' => 30,
			'status' => 0
		];

		foreach ($defaults as $key => $value) {
			$setting = $this->config->get('module_ageverify_' . $key);

			$data['module_ageverify_' . $key] = ($setting === null) ? $value : $setting;
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

		if ((int)($post['module_ageverify_age'] ?? 0) < 1 || (int)$post['module_ageverify_age'] > 99) {
			$json['error']['age'] = $this->language->get('error_age');
		}

		if (!preg_match('~^https?://~i', html_entity_decode((string)($post['module_ageverify_exit_url'] ?? ''), ENT_QUOTES, 'UTF-8'))) {
			$json['error']['warning'] = $this->language->get('error_url');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_ageverify', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'ageverify_footer',
			'description' => 'Age Verification: age popup',
			'trigger'     => 'catalog/view/common/footer/after',
			'action'      => 'extension/ageverify/event/ageverify.footer',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {

		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('ageverify_footer');
	}
}
