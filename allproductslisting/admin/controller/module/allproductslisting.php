<?php
namespace Opencart\Admin\Controller\Extension\Allproductslisting\Module;

class Allproductslisting extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/allproductslisting/module/allproductslisting');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/allproductslisting/module/allproductslisting', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['save'] = $this->url->link('extension/allproductslisting/module/allproductslisting.save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        $data['module_allproductslisting_status'] = $this->config->get('module_allproductslisting_status');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/allproductslisting/module/allproductslisting', $data));
    }

    public function save(): void {
        $this->load->language('extension/allproductslisting/module/allproductslisting');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/allproductslisting/module/allproductslisting')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('module_allproductslisting', $this->request->post);
            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install(): void {
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent([
            'code'        => 'allproductslisting_menu',
            'description' => 'Add All Products link to top menu',
            'trigger'     => 'catalog/view/common/menu/before',
            'action'      => 'extension/allproductslisting/module/allproductslisting.menu',
            'status'      => 1,
            'sort_order'  => 0
        ]);
    }

    public function uninstall(): void {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('allproductslisting_menu');
    }
}
