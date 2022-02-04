<?php
class ControllerInformationTestimonial extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('information/testimonial');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$this->response->redirect($this->url->link('information/testimonial/success'));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/testimonial')
		);

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_store'] = $this->language->get('text_store');
		$data['text_testimonial'] = $this->language->get('text_testimonial');
		$data['text_address'] = $this->language->get('text_address');
		$data['text_telephone'] = $this->language->get('text_telephone');
		$data['text_fax'] = $this->language->get('text_fax');
		$data['text_open'] = $this->language->get('text_open');
		$data['text_comment'] = $this->language->get('text_comment');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_email'] = $this->language->get('entry_email');
		$data['entry_title'] = $this->language->get('entry_title');
		$data['entry_enquiry'] = $this->language->get('entry_enquiry');

		$data['button_map'] = $this->language->get('button_map');

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['title'])) {
			$data['error_title'] = $this->error['title'];
		} else {
			$data['error_title'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		if (isset($this->error['enquiry'])) {
			$data['error_enquiry'] = $this->error['enquiry'];
		} else {
			$data['error_enquiry'] = '';
		}

		$data['button_submit'] = $this->language->get('button_submit');

		$data['action'] = $this->url->link('information/testimonial', '', true);

		$this->load->model('tool/image');

		if ($this->config->get('config_image')) {
			$data['image'] = $this->model_tool_image->resize($this->config->get('config_image'), $this->config->get($this->config->get('config_theme') . '_image_location_width'), $this->config->get($this->config->get('config_theme') . '_image_location_height'));
		} else {
			$data['image'] = false;
		}


		$data['comment'] = $this->config->get('config_comment');


		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} else {
			$data['name'] = $this->customer->getFirstName();
		}

		if (isset($this->request->post['title'])) {
			$data['title'] = $this->request->post['title'];
		} else {
			$data['title'] = $this->customer->getFirstName();
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = $this->customer->getEmail();
		}

		if (isset($this->request->post['enquiry'])) {
			$data['enquiry'] = $this->request->post['enquiry'];
		} else {
			$data['enquiry'] = '';
		}

		// Captcha
		if ($this->config->get($this->config->get('config_captcha') . '_status') && in_array('testimonial', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('captcha/' . $this->config->get('config_captcha'), $this->error);
		} else {
			$data['captcha'] = '';
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/testimonial', $data));
	}

	protected function validate() {
		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 32)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if ((utf8_strlen($this->request->post['title']) < 3) || (utf8_strlen($this->request->post['title']) > 32)) {
			$this->error['title'] = $this->language->get('error_title');
		}

		if (!filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if ((utf8_strlen($this->request->post['enquiry']) < 10) || (utf8_strlen($this->request->post['enquiry']) > 3000)) {
			$this->error['enquiry'] = $this->language->get('error_enquiry');
		}

		// Captcha
		if ($this->config->get($this->config->get('config_captcha') . '_status') && in_array('testimonial', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('captcha/' . $this->config->get('config_captcha') . '/validate');

			if ($captcha) {
				$this->error['captcha'] = $captcha;
			}
		}

		return !$this->error;
	}

	public function success() {
		$this->load->language('information/testimonial');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('information/testimonial')
		);

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_message'] = $this->language->get('text_success');

		$data['button_continue'] = $this->language->get('button_continue');

		$data['continue'] = $this->url->link('common/home');

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/success', $data));
	}
}
