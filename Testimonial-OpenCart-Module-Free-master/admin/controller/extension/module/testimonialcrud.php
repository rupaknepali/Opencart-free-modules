<?php
class ControllerDesignTestimonial extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('design/testimonial');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('design/testimonial');



		$this->getList();
	}

	public function add() {
		$this->load->language('design/testimonial');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('design/testimonial');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_design_testimonial->addTestimonial($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('design/testimonial', 'token=' . $this->session->data['token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('design/testimonial');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('design/testimonial');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_design_testimonial->editTestimonial($this->request->get['testimonial_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('design/testimonial', 'token=' . $this->session->data['token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('design/testimonial');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('design/testimonial');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $testimonial_id) {
				$this->model_design_testimonial->deleteTestimonial($testimonial_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('design/testimonial', 'token=' . $this->session->data['token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('design/testimonial', 'token=' . $this->session->data['token'] . $url, true)
		);

		$data['add'] = $this->url->link('design/testimonial/add', 'token=' . $this->session->data['token'] . $url, true);
		$data['delete'] = $this->url->link('design/testimonial/delete', 'token=' . $this->session->data['token'] . $url, true);

		$data['testimonials'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$testimonial_total = $this->model_design_testimonial->getTotalTestimonials();

		$results = $this->model_design_testimonial->getTestimonials($filter_data);

		foreach ($results as $result) {
			$data['testimonials'][] = array(
				'testimonial_id' => $result['testimonial_id'],
				'name'      => $result['name'],
				'status'    => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
				'edit'      => $this->url->link('design/testimonial/edit', 'token=' . $this->session->data['token'] . '&testimonial_id=' . $result['testimonial_id'] . $url, true)
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');

		$data['column_name'] = $this->language->get('column_name');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_action'] = $this->language->get('column_action');

		$data['button_add'] = $this->language->get('button_add');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_delete'] = $this->language->get('button_delete');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('design/testimonial', 'token=' . $this->session->data['token'] . '&sort=name' . $url, true);
		$data['sort_status'] = $this->url->link('design/testimonial', 'token=' . $this->session->data['token'] . '&sort=status' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $testimonial_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('design/testimonial', 'token=' . $this->session->data['token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($testimonial_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($testimonial_total - $this->config->get('config_limit_admin'))) ? $testimonial_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $testimonial_total, ceil($testimonial_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('design/testimonial_list', $data));
	}

	protected function getForm() {
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_form'] = !isset($this->request->get['testimonial_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_default'] = $this->language->get('text_default');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_title'] = $this->language->get('entry_title');
		$data['entry_link'] = $this->language->get('entry_link');
		$data['entry_image'] = $this->language->get('entry_image');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_testimonial_add'] = $this->language->get('button_testimonial_add');
		$data['button_remove'] = $this->language->get('button_remove');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['testimonial_image'])) {
			$data['error_testimonial_image'] = $this->error['testimonial_image'];
		} else {
			$data['error_testimonial_image'] = array();
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('design/testimonial', 'token=' . $this->session->data['token'] . $url, true)
		);

		if (!isset($this->request->get['testimonial_id'])) {
			$data['action'] = $this->url->link('design/testimonial/add', 'token=' . $this->session->data['token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('design/testimonial/edit', 'token=' . $this->session->data['token'] . '&testimonial_id=' . $this->request->get['testimonial_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('design/testimonial', 'token=' . $this->session->data['token'] . $url, true);

		if (isset($this->request->get['testimonial_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$testimonial_info = $this->model_design_testimonial->getTestimonial($this->request->get['testimonial_id']);
		}

		$data['token'] = $this->session->data['token'];

		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($testimonial_info)) {
			$data['name'] = $testimonial_info['name'];
		} else {
			$data['name'] = '';
		}



		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($testimonial_info)) {
			$data['status'] = $testimonial_info['status'];
		} else {
			$data['status'] = true;
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('tool/image');

		if (isset($this->request->post['testimonial_image'])) {
			$testimonial_images = $this->request->post['testimonial_image'];
		} elseif (isset($this->request->get['testimonial_id'])) {
			$testimonial_images = $this->model_design_testimonial->getTestimonialImages($this->request->get['testimonial_id']);
		} else {
			$testimonial_images = array();
		}

		$data['testimonial_images'] = array();

		foreach ($testimonial_images as $testimonial_image) {
			if (is_file(DIR_IMAGE . $testimonial_image['image'])) {
				$image = $testimonial_image['image'];
				$thumb = $testimonial_image['image'];
			} else {
				$image = '';
				$thumb = 'no_image.png';
			}

			$data['testimonial_images'][] = array(
				'testimonial_image_description' => $testimonial_image['testimonial_image_description'],
				'link'                     => $testimonial_image['link'],
				'image'                    => $image,
				'thumb'                    => $this->model_tool_image->resize($thumb, 100, 100),
				'sort_order'               => $testimonial_image['sort_order']
			);
		}

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('design/testimonial_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'design/testimonial')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (isset($this->request->post['testimonial_image'])) {
			foreach ($this->request->post['testimonial_image'] as $testimonial_image_id => $testimonial_image) {
				foreach ($testimonial_image['testimonial_image_description'] as $language_id => $testimonial_image_description) {
					if ((utf8_strlen($testimonial_image_description['title']) < 2) || (utf8_strlen($testimonial_image_description['title']) > 64)) {
						$this->error['testimonial_image'][$testimonial_image_id][$language_id] = $this->language->get('error_title');
					}
				}
			}
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'design/testimonial')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}


	public function install() {
		$this->load->language('extension/module');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/extension');

		$this->load->model('extension/module');

		if ($this->validate()) {
			$this->model_extension_extension->install('module', $this->request->get['extension']);

			$this->load->model('user/user_group');

			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'module/' . $this->request->get['extension']);
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'module/' . $this->request->get['extension']);

			// Call install method if it exsits
			$this->load->controller('module/' . $this->request->get['extension'] . '/install');

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], true));
		}

		$this->getList();
	}

	public function uninstall() {
		$this->load->language('extension/module');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/extension');

		$this->load->model('extension/module');

		if ($this->validate()) {
			$this->model_extension_extension->uninstall('module', $this->request->get['extension']);

			$this->model_extension_module->deleteModulesByCode($this->request->get['extension']);

			// Call uninstall method if it exsits
			$this->load->controller('module/' . $this->request->get['extension'] . '/uninstall');

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], true));
		}

		$this->getList();
	}



}