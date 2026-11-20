<?php
namespace Opencart\Admin\Controller\Extension\Featuredcategory\Module;
/**
 * Featured Categories: show the categories you choose, with images, on any layout. Multi-instance.
 */
class Featuredcategory extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/featuredcategory/module/featuredcategory';

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

		$module_info = [];

		if ($module_id) {
			$this->load->model('setting/module');

			$module_info = $this->model_setting_module->getModule($module_id);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['limit'] = $module_info['limit'] ?? 4;
		$data['width'] = $module_info['width'] ?? 300;
		$data['height'] = $module_info['height'] ?? 300;
		$data['columns'] = $module_info['columns'] ?? 4;
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;

		$this->load->model('catalog/category');

		$data['categories'] = [];

		foreach ((array)($module_info['category'] ?? []) as $category_id) {
			$category_info = $this->model_catalog_category->getCategory((int)$category_id);

			if ($category_info) {
				$data['categories'][] = [
					'category_id' => $category_info['category_id'],
					'name'        => (!empty($category_info['path']) ? $category_info['path'] . ' &gt; ' : '') . $category_info['name']
				];
			}
		}

		$data['user_token'] = $this->session->data['user_token'];

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

		$post = $this->request->post + ['module_id' => 0, 'name' => '', 'width' => 0, 'height' => 0, 'limit' => 4, 'columns' => 4, 'category' => []];

		if (!oc_validate_length($post['name'], 3, 64)) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if ((int)$post['width'] < 1) {
			$json['error']['width'] = $this->language->get('error_width');
		}

		if ((int)$post['height'] < 1) {
			$json['error']['height'] = $this->language->get('error_height');
		}

		if (empty($post['category'])) {
			$json['error']['category'] = $this->language->get('error_category');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['category'] = array_values(array_unique(array_map('intval', (array)$post['category'])));
			$post['columns'] = in_array((int)$post['columns'], [2, 3, 4, 6]) ? (int)$post['columns'] : 4;

			$this->load->model('setting/module');

			if (!$post['module_id']) {
				$json['module_id'] = $this->model_setting_module->addModule('featuredcategory.featuredcategory', $post);
			} else {
				$this->model_setting_module->editModule((int)$post['module_id'], $post);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
