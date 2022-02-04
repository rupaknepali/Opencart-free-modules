<?php
class ControllerExtensionModuleAllreviews extends Controller
{
	private $error = array();

	public function index()
	{
		$this->load->language('extension/module/allreviews');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$this->model_setting_setting->editSetting('module_allreviews', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

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
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/allreviews', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/allreviews', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}

		$data['action'] = $this->url->link('extension/module/allreviews', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);


		if (isset($this->request->post['module_allreviews_name'])) {
			$data['module_allreviews_name'] = $this->request->post['module_allreviews_name'];
		} else {
			$data['module_allreviews_name'] = $this->config->get('module_allreviews_name');
		}

		if (isset($this->request->post['module_allreviews_description'])) {
			$data['module_allreviews_description'] = $this->request->post['module_allreviews_description'];
		} else {
			$data['module_allreviews_description'] = $this->config->get('module_allreviews_description');
		}

		if (isset($this->request->post['module_allreviews_limit'])) {
			$data['module_allreviews_limit'] = $this->request->post['module_allreviews_limit'];
		} else {
			$data['module_allreviews_limit'] = $this->config->get('module_allreviews_limit');
		}

		if (isset($this->request->post['module_allreviews_width'])) {
			$data['module_allreviews_width'] = $this->request->post['module_allreviews_width'];
		} else {
			$data['module_allreviews_width'] = $this->config->get('module_allreviews_width');
		}

		if (isset($this->request->post['module_allreviews_height'])) {
			$data['module_allreviews_height'] = $this->request->post['module_allreviews_height'];
		} else {
			$data['module_allreviews_height'] = $this->config->get('module_allreviews_height');
		}

		if (isset($this->request->post['module_allreviews_show_main_menu'])) {
			$data['module_allreviews_show_main_menu'] = $this->request->post['module_allreviews_show_main_menu'];
		} else {
			$data['module_allreviews_show_main_menu'] = $this->config->get('module_allreviews_show_main_menu');
		}

		if (isset($this->request->post['module_allreviews_status'])) {
			$data['module_allreviews_status'] = $this->request->post['module_allreviews_status'];
		} else {
			$data['module_allreviews_status'] = $this->config->get('module_allreviews_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/allreviews', $data));
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/module/allreviews')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['module_allreviews_name']) < 3) || (utf8_strlen($this->request->post['module_allreviews_name']) > 64)) {
			$this->error['module_allreviews_name'] = $this->language->get('error_name');
		}

		if (!$this->request->post['module_allreviews_width']) {
			$this->error['module_allreviews_width'] = $this->language->get('error_width');
		}

		if (!$this->request->post['module_allreviews_height']) {
			$this->error['module_allreviews_height'] = $this->language->get('error_height');
		}

		return !$this->error;
	}
}
