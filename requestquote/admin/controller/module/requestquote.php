<?php
namespace Opencart\Admin\Controller\Extension\Requestquote\Module;
/**
 * Request a Quote: request list, request view and settings.
 */
class Requestquote extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/requestquote/module/requestquote';

	public function index(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = $this->breadcrumbs();
		$data['save'] = $this->url->link($this->route . '.save', $token);
		$data['delete'] = $this->url->link($this->route . '.delete', $token);
		$data['back'] = $this->url->link('marketplace/extension', $token . '&type=module');
		$data['list_url'] = $this->url->link($this->route . '.list', $token);

		$data['filter'] = in_array($this->request->get['filter'] ?? '', ['new', 'replied']) ? $this->request->get['filter'] : '';
		$data['list'] = $this->getList();

		$data['module_requestquote_mode'] = $this->config->get('module_requestquote_mode') ?: 'all';
		$data['module_requestquote_email'] = $this->config->get('module_requestquote_email') ?? 1;
		$data['module_requestquote_status'] = $this->config->get('module_requestquote_status');

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

		$filter = in_array($this->request->get['filter'] ?? '', ['new', 'replied']) ? $this->request->get['filter'] : '';
		$page = max(1, (int)($this->request->get['page'] ?? 1));
		$limit = (int)$this->config->get('config_pagination_admin') ?: 10;

		$this->load->model($this->route);

		$data['requests'] = [];

		foreach ($this->model_extension_requestquote_module_requestquote->getRequests($filter, ($page - 1) * $limit, $limit) as $result) {
			$data['requests'][] = [
				'quote_request_id' => $result['quote_request_id'],
				'product'          => $result['product'] ?: '#' . $result['product_id'],
				'name'             => $result['name'],
				'company'          => $result['company'],
				'quantity'         => $result['quantity'],
				'status'           => (int)$result['status'],
				'date_added'       => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'view'             => $this->url->link($this->route . '.view', $token . '&quote_request_id=' . $result['quote_request_id'])
			];
		}

		$total = $this->model_extension_requestquote_module_requestquote->getTotalRequests($filter);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link($this->route . '.list', $token . '&filter=' . $filter . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), $total ? (($page - 1) * $limit) + 1 : 0, min($total, $page * $limit), $total, ceil($total / $limit));
		$data['action'] = $this->url->link($this->route . '.list', $token . '&filter=' . $filter . '&page=' . $page);

		return $this->load->view('extension/requestquote/module/requestquote_list', $data);
	}

	public function view(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$this->load->model($this->route);

		$request = $this->model_extension_requestquote_module_requestquote->getRequest((int)($this->request->get['quote_request_id'] ?? 0));

		if (!$request) {
			$this->response->redirect($this->url->link($this->route, $token));
		}

		$data['breadcrumbs'] = $this->breadcrumbs();
		$data['back'] = $this->url->link($this->route, $token);
		$data['status_url'] = $this->url->link($this->route . '.status', $token . '&quote_request_id=' . $request['quote_request_id']);

		$data['request'] = $request;
		$data['product'] = $request['product'] ?: '#' . $request['product_id'];
		$data['product_href'] = $this->url->link('catalog/product.form', $token . '&product_id=' . $request['product_id']);
		$data['message'] = nl2br($request['message']);
		$data['date_added'] = date($this->language->get('datetime_format'), strtotime($request['date_added']));

		// A reply in the shop owner's own mail program, with the details filled in.
		$subject = sprintf($this->language->get('text_reply_subject'), html_entity_decode($data['product'], ENT_QUOTES, 'UTF-8'));
		$data['reply'] = 'mailto:' . rawurlencode(html_entity_decode($request['email'], ENT_QUOTES, 'UTF-8')) . '?subject=' . rawurlencode($subject);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/requestquote/module/requestquote_view', $data));
	}

	public function status(): void {
		$this->load->language($this->route);

		$json = [];

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model($this->route);
			$this->model_extension_requestquote_module_requestquote->editStatus((int)($this->request->get['quote_request_id'] ?? 0), (int)!empty($this->request->get['status']));

			$json['success'] = $this->language->get('text_success_status');
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

			foreach ((array)($this->request->post['selected'] ?? []) as $id) {
				$this->model_extension_requestquote_module_requestquote->deleteRequest((int)$id);
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

		if (!in_array($this->request->post['module_requestquote_mode'] ?? '', ['all', 'zero'])) {
			$json['error']['warning'] = $this->language->get('error_mode');
		}

		if (!$json) {
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('module_requestquote', $this->request->post);

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
		$this->model_extension_requestquote_module_requestquote->install();

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'requestquote_product',
			'description' => 'Request a Quote: button and form on product pages',
			'trigger'     => 'catalog/view/product/product/after',
			'action'      => 'extension/requestquote/event/requestquote',
			'status'      => 1,
			'sort_order'  => 0
		]);

		$this->model_setting_event->addEvent([
			'code'        => 'requestquote_menu',
			'description' => 'Request a Quote: Sales menu item with the number of new requests',
			'trigger'     => 'admin/view/common/column_left/before',
			'action'      => 'extension/requestquote/event/requestquote.menu',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		// The requests table is kept on purpose, so reinstalling doesn't lose them.
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('requestquote_product');
		$this->model_setting_event->deleteEventByCode('requestquote_menu');
	}
}
