<?php
namespace Opencart\Admin\Controller\Extension\CustomMenuLinks\Module;

class CustomMenuLinks extends \Opencart\System\Engine\Controller {
    public function index(): void {
        $this->load->language('extension/custom_menu_links/module/custom_menu_links');

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
            'href' => $this->url->link('extension/custom_menu_links/module/custom_menu_links', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['save'] = $this->url->link('extension/custom_menu_links/module/custom_menu_links.save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        $data['module_custom_menu_links_status'] = $this->config->get('module_custom_menu_links_status');
        $data['module_custom_menu_links_items'] = $this->config->get('module_custom_menu_links_items');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/custom_menu_links/module/custom_menu_links', $data));
    }

    public function save(): void {
        $this->load->language('extension/custom_menu_links/module/custom_menu_links');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/custom_menu_links/module/custom_menu_links')) {
            $json['error'] = $this->language->get('error_permission');
        }
        
        if (isset($this->request->post['module_custom_menu_links_items'])) {
            foreach ($this->request->post['module_custom_menu_links_items'] as $key => $value) {
                if (empty($value['title'])) {
                    $json['error']['menu_item'][$key]['title'] = $this->language->get('error_title');
                }
                if (empty($value['link'])) {
                    $json['error']['menu_item'][$key]['link'] = $this->language->get('error_link');
                }
            }
        }

        if (!$json) {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('module_custom_menu_links', $this->request->post);
            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install(): void {
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent([
            'code'        => 'custom_menu_links_menu',
            'description' => 'Add custom links to top menu',
            'trigger'     => 'catalog/view/common/menu/before',
            'action'      => 'extension/custom_menu_links/module/custom_menu_links.menu',
            'status'      => 1,
            'sort_order'  => 0
        ]);
    }

    public function uninstall(): void {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('custom_menu_links_menu');
    }
}
