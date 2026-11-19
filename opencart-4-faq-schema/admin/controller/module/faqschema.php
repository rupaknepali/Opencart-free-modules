<?php
namespace Opencart\Admin\Controller\Extension\Faqschema\Module;
/**
 * FAQ with Schema: question-and-answer accordions on product, category and information pages,
 * with FAQPage structured data (JSON-LD).
 */
class Faqschema extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/faqschema/module/faqschema';

	private array $types = ['product', 'category', 'information'];

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
		$data['add'] = $this->url->link($this->route . '.form', $token);

		$defaults = [
			'status'  => 0,
			'schema'  => 1,
			'heading' => $this->language->get('text_default_heading')
		];

		// Config::get() returns '' for settings that were never saved, so check what's actually stored.
		$this->load->model('setting/setting');

		$saved = $this->model_setting_setting->getSetting('module_faqschema');

		foreach ($defaults as $key => $value) {
			$data['module_faqschema_' . $key] = array_key_exists('module_faqschema_' . $key, $saved) ? $saved['module_faqschema_' . $key] : $value;
		}

		$data['types'] = $this->types;
		$data['list'] = $this->getList();
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

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('module_faqschema', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function list(): void {
		$this->load->language($this->route);

		$this->response->setOutput($this->getList());
	}

	private function getList(): string {
		$this->load->model($this->route);

		$type = in_array($this->request->get['filter_type'] ?? '', $this->types) ? $this->request->get['filter_type'] : '';
		$page = max(1, (int)($this->request->get['page'] ?? 1));
		$limit = 20;

		$filter = ['type' => $type, 'start' => ($page - 1) * $limit, 'limit' => $limit];

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['faqs'] = [];

		foreach ($this->model_extension_faqschema_module_faqschema->getFaqs($filter) as $faq) {
			if (!$faq['target_id']) {
				$target = $this->language->get('text_all_' . $faq['type']);
			} else {
				$target = $faq['target_name'] ?? sprintf($this->language->get('text_missing'), $faq['target_id']);
			}

			$data['faqs'][] = [
				'faq_id'     => $faq['faq_id'],
				'question'   => $faq['question'],
				'type'       => $this->language->get('text_type_' . $faq['type']),
				'target'     => $target,
				'sort_order' => $faq['sort_order'],
				'status'     => $faq['status'],
				'edit'       => $this->url->link($this->route . '.form', $token . '&faq_id=' . $faq['faq_id'])
			];
		}

		$total = $this->model_extension_faqschema_module_faqschema->getTotalFaqs($filter);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link($this->route . '.list', $token . '&filter_type=' . $type . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), $total ? (($page - 1) * $limit) + 1 : 0, min($total, $page * $limit), $total, ceil($total / $limit));

		return $this->load->view('extension/faqschema/module/faqschema_list', $data);
	}

	public function form(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('heading_title'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$faq_id = (int)($this->request->get['faq_id'] ?? 0);

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', $token . '&type=module')],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link($this->route, $token)]
		];

		$data['text_form'] = $faq_id ? $this->language->get('text_edit_faq') : $this->language->get('text_add_faq');
		$data['save'] = $this->url->link($this->route . '.saveFaq', $token);
		$data['back'] = $this->url->link($this->route, $token);
		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model($this->route);
		$this->load->model('localisation/language');
		$this->load->model('catalog/information');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$faq_info = $faq_id ? $this->model_extension_faqschema_module_faqschema->getFaq($faq_id) : [];

		$data['faq_id'] = $faq_info ? $faq_info['faq_id'] : 0;
		$data['type'] = $faq_info['type'] ?? 'product';
		$data['target_id'] = (int)($faq_info['target_id'] ?? 0);
		$data['sort_order'] = $faq_info['sort_order'] ?? 0;
		$data['status'] = $faq_info['status'] ?? 1;
		$data['faq_description'] = $faq_info ? $this->model_extension_faqschema_module_faqschema->getDescriptions($faq_id) : [];

		// Name of the chosen product or category, for the autocomplete box.
		$data['target_name'] = '';

		if ($data['target_id'] && $data['type'] == 'product') {
			$this->load->model('catalog/product');

			$data['target_name'] = $this->model_catalog_product->getProduct($data['target_id'])['name'] ?? '';
		} elseif ($data['target_id'] && $data['type'] == 'category') {
			$this->load->model('catalog/category');

			$category_info = $this->model_catalog_category->getCategory($data['target_id']);

			$data['target_name'] = $category_info ? (!empty($category_info['path']) ? $category_info['path'] . ' &gt; ' : '') . $category_info['name'] : '';
		}

		$data['informations'] = $this->model_catalog_information->getInformations();
		$data['types'] = $this->types;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/faqschema/module/faqschema_form', $data));
	}

	public function saveFaq(): void {
		$this->load->language($this->route);

		$json = [];
		$post = $this->request->post;

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		if (!in_array($post['type'] ?? '', $this->types)) {
			$json['error']['type'] = $this->language->get('error_type');
		}

		$post['target_id'] = empty($post['all']) ? (int)($post['target_id'] ?? 0) : 0;

		if (empty($post['all']) && !$post['target_id']) {
			$json['error']['target'] = $this->language->get('error_target');
		}

		foreach ((array)($post['faq_description'] ?? []) as $language_id => $value) {
			if (oc_strlen(trim($value['question'] ?? '')) < 3 || oc_strlen($value['question']) > 255) {
				$json['error']['question-' . $language_id] = $this->language->get('error_question');
			}

			if (oc_strlen(trim(strip_tags(html_entity_decode($value['answer'] ?? '', ENT_QUOTES, 'UTF-8')))) < 2) {
				$json['error']['answer-' . $language_id] = $this->language->get('error_answer');
			}
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$this->load->model($this->route);

			$post['sort_order'] = (int)($post['sort_order'] ?? 0);
			$post['status'] = (int)!empty($post['status']);

			if (empty($post['faq_id'])) {
				$json['faq_id'] = $this->model_extension_faqschema_module_faqschema->addFaq($post);
			} else {
				$this->model_extension_faqschema_module_faqschema->editFaq((int)$post['faq_id'], $post);
			}

			$json['success'] = $this->language->get('text_success_faq');
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

			foreach ((array)($this->request->post['selected'] ?? []) as $faq_id) {
				$this->model_extension_faqschema_module_faqschema->deleteFaq((int)$faq_id);
			}

			$json['success'] = $this->language->get('text_success_delete');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model($this->route);

		$this->model_extension_faqschema_module_faqschema->install();

		$this->load->model('setting/event');

		foreach ($this->types as $type) {
			$this->model_setting_event->addEvent([
				'code'        => 'faqschema_' . $type,
				'description' => 'FAQ with Schema: FAQs on ' . $type . ' pages',
				'trigger'     => 'catalog/view/' . ($type == 'information' ? 'information/information' : 'product/' . $type) . '/after',
				'action'      => 'extension/faqschema/event/faqschema.' . $type,
				'status'      => 1,
				'sort_order'  => 0
			]);
		}
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		foreach ($this->types as $type) {
			$this->model_setting_event->deleteEventByCode('faqschema_' . $type);
		}

		// The FAQ tables are kept, so reinstalling doesn't lose your questions and answers.
	}
}
