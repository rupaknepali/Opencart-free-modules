<?php

namespace Opencart\Admin\Controller\Extension\Thirdpartyjs\Analytics;

class Thirdpartyjs extends \Opencart\System\Engine\Controller
{
    private $error = array();

    public function index(): void
    {
        $this->load->language('extension/thirdpartyjs/analytics/thirdpartyjs');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['code'])) {
            $data['error_code'] = $this->error['code'];
        } else {
            $data['error_code'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=analytics', true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/analytics/google', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->request->get['store_id'], true),
        );

        $data['save'] = $this->url->link('extension/thirdpartyjs/analytics/thirdpartyjs|save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        $data['user_token'] = $this->session->data['user_token'];

        $data['analytics_status'] = $this->config->get('analytics_status');
        $data['analytics_code'] = $this->config->get('analytics_code');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/thirdpartyjs/analytics/thirdpartyjs', $data));
    }

    public function save(): void
    {
        $this->load->language('extension/thirdpartyjs/analytics/thirdpartyjs');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/thirdpartyjs/analytics/thirdpartyjs')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('analytics_thirdpartyjs', $this->request->post);

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
