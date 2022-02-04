<?php
class ControllerModuleTestimonial extends Controller {
	private $error = array();

	public function index() {

		if($this->checkDatabase()) {

			$this->language->load('testimonial/install');

			$this->document->setTitle($this->language->get('error_database'));

			$data['install_database'] = $this->url->link('testimonial/install/installDatabase', 'token=' . $this->session->data['token'], 'SSL');

			$data['text_install_message'] = $this->language->get('text_install_message');

			$data['text_upgread'] = $this->language->get('text_upgread');

			$data['error_database'] = $this->language->get('error_database');

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text'      => $this->language->get('text_home'),
				'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
				'separator' => false
			);

			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view('testimonial/notification.tpl', $data));

		}
		else {


				$this->load->language('module/testimonial');

				$this->document->setTitle($this->language->get('heading_title'));

				$this->load->model('extension/module');

				if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
					if (!isset($this->request->get['module_id'])) {
						$this->model_extension_module->addModule('testimonial', $this->request->post);
					} else {
						$this->model_extension_module->editModule($this->request->get['module_id'], $this->request->post);
					}

					$this->session->data['success'] = $this->language->get('text_success');

					$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], true));
				}

				$data['heading_title'] = $this->language->get('heading_title');

				$data['text_edit'] = $this->language->get('text_edit');
				$data['text_enabled'] = $this->language->get('text_enabled');
				$data['text_disabled'] = $this->language->get('text_disabled');

				$data['entry_name'] = $this->language->get('entry_name');
				$data['entry_testimonial'] = $this->language->get('entry_testimonial');
				$data['entry_width'] = $this->language->get('entry_width');
				$data['entry_height'] = $this->language->get('entry_height');
				$data['entry_status'] = $this->language->get('entry_status');

				$data['button_save'] = $this->language->get('button_save');
				$data['button_cancel'] = $this->language->get('button_cancel');

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

				if (isset($this->error['width'])) {
					$data['error_width'] = $this->error['width'];
				} else {
					$data['error_width'] = '';
				}

				if (isset($this->error['height'])) {
					$data['error_height'] = $this->error['height'];
				} else {
					$data['error_height'] = '';
				}

				$data['breadcrumbs'] = array();

				$data['breadcrumbs'][] = array(
					'text' => $this->language->get('text_home'),
					'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
				);

				$data['breadcrumbs'][] = array(
					'text' => $this->language->get('text_module'),
					'href' => $this->url->link('extension/module', 'token=' . $this->session->data['token'], true)
				);

				if (!isset($this->request->get['module_id'])) {
					$data['breadcrumbs'][] = array(
						'text' => $this->language->get('heading_title'),
						'href' => $this->url->link('module/testimonial', 'token=' . $this->session->data['token'], true)
					);
				} else {
					$data['breadcrumbs'][] = array(
						'text' => $this->language->get('heading_title'),
						'href' => $this->url->link('module/testimonial', 'token=' . $this->session->data['token'] . '&module_id=' . $this->request->get['module_id'], true)
					);
				}

				if (!isset($this->request->get['module_id'])) {
					$data['action'] = $this->url->link('module/testimonial', 'token=' . $this->session->data['token'], true);
				} else {
					$data['action'] = $this->url->link('module/testimonial', 'token=' . $this->session->data['token'] . '&module_id=' . $this->request->get['module_id'], true);
				}

				$data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], true);

				if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
					$module_info = $this->model_extension_module->getModule($this->request->get['module_id']);
				}

				if (isset($this->request->post['name'])) {
					$data['name'] = $this->request->post['name'];
				} elseif (!empty($module_info)) {
					$data['name'] = $module_info['name'];
				} else {
					$data['name'] = '';
				}

				if (isset($this->request->post['testimonial_id'])) {
					$data['testimonial_id'] = $this->request->post['testimonial_id'];
				} elseif (!empty($module_info)) {
					$data['testimonial_id'] = $module_info['testimonial_id'];
					$data['testimonial'] = $this->url->link('design/testimonial/edit', 'token=' . $this->session->data['token'].'&testimonial_id='.$module_info['testimonial_id'], true);
				} else {
					$data['testimonial_id'] = '';
					$data['testimonial'] = $this->url->link('design/testimonial', 'token=' . $this->session->data['token'], true);
				}


				$this->load->model('design/testimonial');

				$data['testimonials'] = $this->model_design_testimonial->getTestimonials();



				if (isset($this->request->post['status'])) {
					$data['status'] = $this->request->post['status'];
				} elseif (!empty($module_info)) {
					$data['status'] = $module_info['status'];
				} else {
					$data['status'] = '';
				}

				$data['header'] = $this->load->controller('common/header');
				$data['column_left'] = $this->load->controller('common/column_left');
				$data['footer'] = $this->load->controller('common/footer');

				$this->response->setOutput($this->load->view('module/testimonial', $data));
		}
	}
	public function checkDatabase() {
		$database_not_found = $this->load->controller('testimonial/install/validateTable');

		if(!$database_not_found) {
			return true;
		}

		return false;
	}
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/testimonial')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}



		return !$this->error;
	}
}