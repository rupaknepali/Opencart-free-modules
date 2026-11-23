<?php
namespace Opencart\Admin\Controller\Extension\Livesearch\Module;
/**
 * Live Search: settings page, and the events that add the suggestions to the header search box.
 */
class Livesearch extends \Opencart\System\Engine\Controller {
	private const DEFAULTS = ['status' => 0, 'limit' => 5, 'min' => 2, 'image' => 1, 'image_size' => 60, 'price' => 1, 'categories' => 1, 'description' => 0];

	public function index(): void {
		$this->load->language('extension/livesearch/module/livesearch');

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')];
		$data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/livesearch/module/livesearch', $token)];

		$data['save'] = $this->url->link('extension/livesearch/module/livesearch.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		// Config::get() returns '' for settings never saved, so read the stored values for fresh-install defaults.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_livesearch');

		foreach (self::DEFAULTS as $key => $default) {
			$data[$key] = array_key_exists('module_livesearch_' . $key, $saved) ? $saved['module_livesearch_' . $key] : $default;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/livesearch/module/livesearch', $data));
	}

	public function save(): void {
		$this->load->language('extension/livesearch/module/livesearch');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/livesearch/module/livesearch')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		$ranges = ['limit' => [1, 20], 'min' => [1, 5], 'image_size' => [24, 200]];

		foreach ($ranges as $key => [$low, $high]) {
			$value = (int)($post[$key] ?? 0);

			if ($value < $low || $value > $high) {
				$json['error'][str_replace('_', '-', $key)] = sprintf($this->language->get('error_range'), $low, $high);
			}
		}

		if (!$json) {
			$settings = [];

			foreach (self::DEFAULTS as $key => $default) {
				$settings['module_livesearch_' . $key] = isset($ranges[$key]) ? (int)$post[$key] : (int)!empty($post[$key]);
			}

			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_livesearch', $settings);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model('setting/event');

		$events = [
			'livesearch_assets' => ['catalog/controller/common/header/before', 'extension/livesearch/module/livesearch.assets', 'Live Search: load the script and stylesheet'],
			'livesearch_form'   => ['catalog/view/common/search/after', 'extension/livesearch/module/livesearch.form', 'Live Search: settings on the header search form']
		];

		foreach ($events as $code => [$trigger, $action, $description]) {
			$this->model_setting_event->deleteEventByCode($code);

			$this->model_setting_event->addEvent([
				'code'        => $code,
				'description' => $description,
				'trigger'     => $trigger,
				'action'      => $action,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach (['livesearch_assets', 'livesearch_form'] as $code) {
			$this->model_setting_event->deleteEventByCode($code);
		}
	}
}
