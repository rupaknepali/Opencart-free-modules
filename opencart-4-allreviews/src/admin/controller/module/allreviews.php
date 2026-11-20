<?php
namespace Opencart\Admin\Controller\Extension\Allreviews\Module;
/**
 * All Reviews Page: lists every approved product review on one page.
 */
class Allreviews extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/allreviews/module/allreviews';

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

		$data['page'] = HTTP_CATALOG . 'index.php?route=extension/allreviews/product/allreviews';

		$defaults = [
			'status' => 0,
			'name' => 'Customer Reviews',
			'description' => 'What our customers say about the products they bought.',
			'limit' => 10,
			'image' => 80,
			'footer' => 1
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_allreviews');

		foreach ($defaults as $key => $value) {
			$data['module_allreviews_' . $key] = array_key_exists('module_allreviews_' . $key, $saved) ? $saved['module_allreviews_' . $key] : $value;
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

		if (oc_strlen(trim((string)($post['module_allreviews_name'] ?? ''))) < 3 || oc_strlen((string)$post['module_allreviews_name']) > 64) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		$post['module_allreviews_limit'] = max(1, min(100, (int)($post['module_allreviews_limit'] ?? 10)));
		$post['module_allreviews_image'] = max(20, min(500, (int)($post['module_allreviews_image'] ?? 80)));

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_allreviews', $post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'allreviews_footer',
			'description' => 'All Reviews Page: footer link',
			'trigger'     => 'catalog/view/common/footer/after',
			'action'      => 'extension/allreviews/event/allreviews.footer',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('allreviews_footer');
	}
}
