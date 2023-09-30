<?php
namespace Opencart\Admin\Controller\Extension\Webocreationproductsfeed\Feed;
class ProductsFeed extends \Opencart\System\Engine\Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/webocreationproductsfeed/feed/productsfeed');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/webocreationproductsfeed/feed/productsfeed', 'user_token=' . $this->session->data['user_token'], true)
		);


		$data['save'] = $this->url->link('extension/webocreationproductsfeed/feed/productsfeed.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=feed');

		$data['user_token'] = $this->session->data['user_token'];

		$data['data_feed'] = HTTP_CATALOG . 'index.php?route=extension/webocreationproductsfeed/feed/productsfeed';

		if (isset($this->request->post['feed_productsfeed_status'])) {
			$data['feed_productsfeed_status'] = $this->request->post['feed_productsfeed_status'];
		} else {
			$data['feed_productsfeed_status'] = $this->config->get('feed_productsfeed_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/webocreationproductsfeed/feed/productsfeed', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/webocreationproductsfeed/feed/productsfeed')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function save(): void {
		$this->load->language('extension/webocreationproductsfeed/feed/productsfeed');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/webocreationproductsfeed/feed/productsfeed')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('feed_productsfeed', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

}
