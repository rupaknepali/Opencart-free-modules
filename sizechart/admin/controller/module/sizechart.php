<?php
namespace Opencart\Admin\Controller\Extension\Sizechart\Module;
/**
 * Size Chart: chart list, chart form and settings.
 */
class Sizechart extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/sizechart/module/sizechart';

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = $this->breadcrumbs();

		$data['add'] = $this->url->link($this->route . '.form', $token);
		$data['delete'] = $this->url->link($this->route . '.delete', $token);
		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');
		$data['list_url'] = $this->url->link($this->route . '.list', $token);

		$data['list'] = $this->getList();

		$data['module_sizechart_label'] = $this->config->get('module_sizechart_label') ?: $this->language->get('text_default_label');
		$data['module_sizechart_status'] = $this->config->get('module_sizechart_status');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view($this->route, $data));
	}

	public function list(): void {
		$this->load->language($this->route);

		$this->response->setOutput($this->getList());
	}

	private function getList(): string {
		$token = 'user_token=' . $this->session->data['user_token'];

		$this->load->model($this->route);

		$data['charts'] = [];

		foreach ($this->model_extension_sizechart_module_sizechart->getCharts() as $result) {
			$data['charts'][] = [
				'size_chart_id' => $result['size_chart_id'],
				'name'          => $result['name'],
				'categories'    => (int)$result['categories'],
				'sort_order'    => $result['sort_order'],
				'status'        => (bool)$result['status'],
				'edit'          => $this->url->link($this->route . '.form', $token . '&size_chart_id=' . $result['size_chart_id'])
			];
		}

		$data['action'] = $this->url->link($this->route . '.list', $token);

		return $this->load->view('extension/sizechart/module/sizechart_list', $data);
	}

	public function form(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$this->load->model($this->route);
		$this->load->model('catalog/category');

		$size_chart_id = (int)($this->request->get['size_chart_id'] ?? 0);

		$chart = $size_chart_id ? $this->model_extension_sizechart_module_sizechart->getChart($size_chart_id) : [];

		$data['breadcrumbs'] = $this->breadcrumbs();

		$data['save'] = $this->url->link($this->route . '.saveChart', $token);
		$data['back'] = $this->url->link($this->route, $token);

		$data['size_chart_id'] = $chart['size_chart_id'] ?? 0;
		$data['name'] = $chart['name'] ?? '';
		$data['content'] = $chart['content'] ?? "Size | Chest (cm) | Waist (cm) | Hips (cm)\nS | 86-91 | 71-76 | 89-94\nM | 96-101 | 81-86 | 99-104\nL | 106-111 | 91-96 | 109-114\nXL | 116-121 | 101-106 | 119-124";
		$data['note'] = $chart['note'] ?? '';
		$data['sort_order'] = $chart['sort_order'] ?? 0;
		$data['status'] = $chart['status'] ?? 1;

		$data['category_ids'] = $size_chart_id ? $this->model_extension_sizechart_module_sizechart->getCategories($size_chart_id) : [];
		$data['categories'] = $this->model_catalog_category->getCategories(['sort' => 'name', 'order' => 'ASC']);

		// Ticked categories first, so a long category list still shows what the chart is used for.
		usort($data['categories'], fn($a, $b) => (int)in_array($b['category_id'], $data['category_ids']) <=> (int)in_array($a['category_id'], $data['category_ids']));

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/sizechart/module/sizechart_form', $data));
	}

	public function saveChart(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (oc_strlen(trim($post['name'] ?? '')) < 1 || oc_strlen($post['name']) > 64) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (count(array_filter(preg_split('/\R/', trim($post['content'] ?? '')))) < 2) {
			$json['error']['content'] = $this->language->get('error_content');
		}

		if (!$json) {
			$this->load->model($this->route);

			$post['name'] = trim($post['name']);
			$post['content'] = trim($post['content']);

			if (empty($post['size_chart_id'])) {
				$json['size_chart_id'] = $this->model_extension_sizechart_module_sizechart->addChart($post);
			} else {
				$this->model_extension_sizechart_module_sizechart->editChart((int)$post['size_chart_id'], $post);
			}

			$json['success'] = $this->language->get('text_success_chart');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function delete(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model($this->route);

			foreach ((array)($this->request->post['selected'] ?? []) as $size_chart_id) {
				$this->model_extension_sizechart_module_sizechart->deleteChart((int)$size_chart_id);
			}

			$json['success'] = $this->language->get('text_success_delete');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function save(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_sizechart', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function breadcrumbs(): array {
		$token = 'user_token=' . $this->session->data['user_token'];

		return [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token)]
		];
	}

	public function install(): void {
		$this->load->model($this->route);

		$this->model_extension_sizechart_module_sizechart->install();

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'sizechart_product',
			'description' => 'Size Chart: add the Size guide link and popup to product pages',
			'trigger'     => 'catalog/view/product/product/after',
			'action'      => 'extension/sizechart/event/sizechart',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		// The charts are kept on purpose, so reinstalling doesn't lose them.
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('sizechart_product');
	}
}
