<?php
namespace Opencart\Admin\Controller\Extension\Testimonial\Module;
/**
 * Testimonials: manage customer testimonials (Catalog > Testimonials) and show them in a slider. Multi-instance.
 */
class Testimonial extends \Opencart\System\Engine\Controller {
	private string $route = 'extension/testimonial/module/testimonial';

	/**
	 * Module settings (one per slider instance).
	 */
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
		$data['manage'] = $this->url->link($this->route . '.manage', $token);

		$module_info = [];

		if ($module_id) {
			$this->load->model('setting/module');

			$module_info = $this->model_setting_module->getModule($module_id);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['limit'] = $module_info['limit'] ?? 10;
		$data['width'] = $module_info['width'] ?? 100;
		$data['height'] = $module_info['height'] ?? 100;
		$data['interval'] = $module_info['interval'] ?? 6;
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;

		$this->load->model($this->route);

		$data['total'] = $this->model_extension_testimonial_module_testimonial->getTotalTestimonials();

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

		$post = $this->request->post + ['module_id' => 0, 'name' => '', 'limit' => 10, 'width' => 0, 'height' => 0, 'interval' => 6];

		if (!oc_validate_length($post['name'], 3, 64)) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if ((int)$post['width'] < 1 || (int)$post['height'] < 1) {
			$json['error']['width'] = $this->language->get('error_size');
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['limit'] = max(1, (int)$post['limit']);
			$post['interval'] = max(0, (int)$post['interval']);

			$this->load->model('setting/module');

			if (!$post['module_id']) {
				$json['module_id'] = $this->model_setting_module->addModule('testimonial.testimonial', $post);
			} else {
				$this->model_setting_module->editModule((int)$post['module_id'], $post);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Catalog > Testimonials: the list.
	 */
	public function manage(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('text_testimonials'));

		$token = 'user_token=' . $this->session->data['user_token'];

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_testimonials'), 'href' => $this->url->link($this->route . '.manage', $token)]
		];

		$data['add'] = $this->url->link($this->route . '.form', $token);
		$data['delete'] = $this->url->link($this->route . '.delete', $token);
		$data['list'] = $this->getList();
		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/testimonial/module/testimonial_manage', $data));
	}

	public function list(): void {
		$this->load->language($this->route);

		$this->response->setOutput($this->getList());
	}

	private function getList(): string {
		$this->load->model($this->route);
		$this->load->model('tool/image');

		$token = 'user_token=' . $this->session->data['user_token'];
		$page = max(1, (int)($this->request->get['page'] ?? 1));
		$limit = 20;

		$data['testimonials'] = [];

		foreach ($this->model_extension_testimonial_module_testimonial->getTestimonials(($page - 1) * $limit, $limit) as $result) {
			$image = $result['image'] && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')) ? $result['image'] : 'no_image.png';

			$data['testimonials'][] = [
				'testimonial_id' => $result['testimonial_id'],
				'thumb'          => $this->model_tool_image->resize(html_entity_decode($image, ENT_QUOTES, 'UTF-8'), 40, 40),
				'name'           => $result['name'],
				'excerpt'        => $this->excerpt((string)$result['description']),
				'rating'         => (int)$result['rating'],
				'sort_order'     => $result['sort_order'],
				'status'         => $result['status'],
				'edit'           => $this->url->link($this->route . '.form', $token . '&testimonial_id=' . $result['testimonial_id'])
			];
		}

		$total = $this->model_extension_testimonial_module_testimonial->getTotalTestimonials();

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link($this->route . '.list', $token . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), $total ? (($page - 1) * $limit) + 1 : 0, min($total, $page * $limit), $total, ceil($total / $limit));

		return $this->load->view('extension/testimonial/module/testimonial_list', $data);
	}

	private function excerpt(string $text): string {
		$text = trim(strip_tags(html_entity_decode($text, ENT_QUOTES, 'UTF-8')));

		return oc_strlen($text) > 90 ? rtrim(oc_substr($text, 0, 90)) . '…' : $text;
	}

	public function form(): void {
		$this->load->language($this->route);

		$this->document->setTitle($this->language->get('text_testimonials'));

		$token = 'user_token=' . $this->session->data['user_token'];
		$testimonial_id = (int)($this->request->get['testimonial_id'] ?? 0);

		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', $token)],
			['text' => $this->language->get('text_testimonials'), 'href' => $this->url->link($this->route . '.manage', $token)]
		];

		$data['text_form'] = $testimonial_id ? $this->language->get('text_edit_testimonial') : $this->language->get('text_add_testimonial');
		$data['save'] = $this->url->link($this->route . '.saveTestimonial', $token);
		$data['back'] = $this->url->link($this->route . '.manage', $token);

		$this->load->model($this->route);
		$this->load->model('localisation/language');
		$this->load->model('tool/image');

		$info = $testimonial_id ? $this->model_extension_testimonial_module_testimonial->getTestimonial($testimonial_id) : [];

		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['testimonial_id'] = $info ? (int)$info['testimonial_id'] : 0;
		$data['testimonial_description'] = $info ? $this->model_extension_testimonial_module_testimonial->getDescriptions($testimonial_id) : [];
		$data['image'] = $info['image'] ?? '';
		$data['rating'] = (int)($info['rating'] ?? 5);
		$data['sort_order'] = $info['sort_order'] ?? 0;
		$data['status'] = $info['status'] ?? 1;

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		$data['thumb'] = $data['image'] && is_file(DIR_IMAGE . html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')) ? $this->model_tool_image->resize(html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8'), 100, 100) : $data['placeholder'];

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/testimonial/module/testimonial_form', $data));
	}

	public function saveTestimonial(): void {
		$this->load->language($this->route);

		$json = [];
		$post = $this->request->post;

		if (!$this->user->hasPermission('modify', $this->route)) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		foreach ((array)($post['testimonial_description'] ?? []) as $language_id => $value) {
			if (!oc_validate_length(trim($value['name'] ?? ''), 2, 64)) {
				$json['error']['name-' . $language_id] = $this->language->get('error_customer');
			}

			if (oc_strlen(trim(strip_tags(html_entity_decode($value['description'] ?? '', ENT_QUOTES, 'UTF-8')))) < 10) {
				$json['error']['description-' . $language_id] = $this->language->get('error_description');
			}
		}

		if (isset($json['error']) && !isset($json['error']['warning'])) {
			$json['error']['warning'] = $this->language->get('error_warning');
		}

		if (!$json) {
			$post['image'] = (string)($post['image'] ?? '');
			$post['rating'] = max(0, min(5, (int)($post['rating'] ?? 5)));
			$post['sort_order'] = (int)($post['sort_order'] ?? 0);
			$post['status'] = (int)!empty($post['status']);

			$this->load->model($this->route);

			if (empty($post['testimonial_id'])) {
				$json['testimonial_id'] = $this->model_extension_testimonial_module_testimonial->addTestimonial($post);
			} else {
				$this->model_extension_testimonial_module_testimonial->editTestimonial((int)$post['testimonial_id'], $post);
			}

			$json['success'] = $this->language->get('text_success_testimonial');
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

			foreach ((array)($this->request->post['selected'] ?? []) as $testimonial_id) {
				$this->model_extension_testimonial_module_testimonial->deleteTestimonial((int)$testimonial_id);
			}

			$json['success'] = $this->language->get('text_success_delete');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install(): void {
		$this->load->model($this->route);

		$this->model_extension_testimonial_module_testimonial->install();

		$this->load->model('setting/event');

		$this->model_setting_event->addEvent([
			'code'        => 'testimonial_menu',
			'description' => 'Testimonials: Catalog > Testimonials in the admin menu',
			'trigger'     => 'admin/view/common/column_left/before',
			'action'      => 'extension/testimonial/event/testimonial.menu',
			'status'      => 1,
			'sort_order'  => 0
		]);
	}

	public function uninstall(): void {
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('testimonial_menu');

		// The testimonial tables are kept, so your testimonials survive a reinstall.
	}
}
