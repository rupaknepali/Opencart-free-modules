<?php
namespace Opencart\Admin\Controller\Extension\Productqa\Module;
/**
 * Product Questions & Answers: question list, answer form and settings.
 */
class Productqa extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/productqa/module/productqa';

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = $this->breadcrumbs();

		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');

		$data['filter'] = in_array($this->request->get['filter'] ?? '', ['unanswered', 'published', 'hidden']) ? $this->request->get['filter'] : '';
		$data['list'] = $this->getList();
		$data['list_url'] = $this->url->link($this->route . '.list', $token);
		$data['delete'] = $this->url->link($this->route . '.delete', $token);

		$data['module_productqa_status'] = $this->config->get('module_productqa_status');
		$data['module_productqa_login'] = $this->config->get('module_productqa_login');
		$data['module_productqa_limit'] = $this->config->get('module_productqa_limit') ?: 5;

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

		$filter = in_array($this->request->get['filter'] ?? '', ['unanswered', 'published', 'hidden']) ? $this->request->get['filter'] : '';
		$page = max(1, (int)($this->request->get['page'] ?? 1));
		$limit = (int)$this->config->get('config_pagination_admin') ?: 10;

		$this->load->model($this->route);

		$data['questions'] = [];

		foreach ($this->model_extension_productqa_module_productqa->getQuestions(['filter' => $filter, 'start' => ($page - 1) * $limit, 'limit' => $limit]) as $result) {
			$data['questions'][] = [
				'question_id' => $result['question_id'],
				'product'     => $result['product'] ?: '#' . $result['product_id'],
				'name'        => $result['name'],
				'question'    => oc_substr(html_entity_decode($result['question'], ENT_QUOTES, 'UTF-8'), 0, 120),
				'answered'    => $result['answer'] !== '',
				'status'      => (bool)$result['status'],
				'date_added'  => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'edit'        => $this->url->link($this->route . '.form', $token . '&question_id=' . $result['question_id'])
			];
		}

		$total = $this->model_extension_productqa_module_productqa->getTotalQuestions(['filter' => $filter]);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link($this->route . '.list', $token . '&filter=' . $filter . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), $total ? (($page - 1) * $limit) + 1 : 0, min($total, $page * $limit), $total, ceil($total / $limit));

		// Where OpenCart reloads the list from after a delete.
		$data['action'] = $this->url->link($this->route . '.list', $token . '&filter=' . $filter . '&page=' . $page);

		return $this->load->view('extension/productqa/module/productqa_list', $data);
	}

	public function form(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$this->load->model($this->route);

		$question = $this->model_extension_productqa_module_productqa->getQuestion((int)($this->request->get['question_id'] ?? 0));

		if (!$question) {
			$this->response->redirect($this->url->link($this->route, $token));
		}

		$data['breadcrumbs'] = $this->breadcrumbs();

		$data['save'] = $this->url->link($this->route . '.saveQuestion', $token . '&question_id=' . $question['question_id']);
		$data['back'] = $this->url->link($this->route, $token);

		$data['product'] = $question['product'] ?: '#' . $question['product_id'];
		$data['product_href'] = $this->url->link('catalog/product.form', $token . '&product_id=' . $question['product_id']);
		$data['name'] = $question['name'];
		$data['question'] = $question['question'];
		$data['answer'] = $question['answer'];
		$data['status'] = $question['status'];
		$data['date_added'] = date($this->language->get('datetime_format'), strtotime($question['date_added']));
		$data['customer'] = $question['customer_id'] ? $this->url->link('customer/customer.form', $token . '&customer_id=' . $question['customer_id']) : '';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/productqa/module/productqa_form', $data));
	}

	public function saveQuestion(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$post = $this->request->post;

		if (oc_strlen(trim($post['name'] ?? '')) < 1 || oc_strlen($post['name']) > 64) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (oc_strlen(trim($post['question'] ?? '')) < 3) {
			$json['error']['question'] = $this->language->get('error_question');
		}

		// A published question with no answer would show an empty reply.
		if (!empty($post['status']) && trim($post['answer'] ?? '') === '') {
			$json['error']['answer'] = $this->language->get('error_answer');
		}

		if (!$json) {
			$this->load->model($this->route);

			$this->model_extension_productqa_module_productqa->editQuestion((int)($this->request->get['question_id'] ?? 0), [
				'name'     => trim($post['name']),
				'question' => trim($post['question']),
				'answer'   => trim($post['answer'] ?? ''),
				'status'   => !empty($post['status'])
			]);

			$json['success'] = $this->language->get('text_success_question');
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

			foreach ((array)($this->request->post['selected'] ?? []) as $question_id) {
				$this->model_extension_productqa_module_productqa->deleteQuestion((int)$question_id);
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

			$this->model_setting_setting->editSetting('module_productqa', $this->request->post);

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

		$this->model_extension_productqa_module_productqa->install();

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'productqa_menu',
			'description' => 'Product Questions & Answers: Catalog menu item with the unanswered count',
			'trigger'     => 'admin/view/common/column_left/before',
			'action'      => 'extension/productqa/event/productqa.menu',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		// The questions table is kept on purpose, so reinstalling doesn't lose your answers.
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('productqa_menu');
	}
}
