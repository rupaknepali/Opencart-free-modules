<?php
namespace Opencart\Admin\Controller\Extension\showmoduleinleftmodule4011\Module;

class MenuLink extends \Opencart\System\Engine\Controller
{
    public function index(): void
    {
        $this->load->language('extension/showmoduleinleftmodule4011/module/menulink');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']),
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module'),
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/showmoduleinleftmodule4011/module/menulink', 'user_token=' . $this->session->data['user_token']),
        ];

        $data['save'] = $this->url->link('extension/showmoduleinleftmodule4011/module/menulink|save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        $data['module_showmoduleinleftmodule4011_status'] = $this->config->get('module_showmoduleinleftmodule4011_status');

        $data['success'] = '';
        if (!empty($this->session->data['module_showmoduleinleftmodule4011_success'])) {
            $data['success'] = $this->session->data['module_showmoduleinleftmodule4011_success'];
            unset($this->session->data['module_showmoduleinleftmodule4011_success']);
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/showmoduleinleftmodule4011/module/menulink', $data));
    }

    public function save(): void
    {
        $this->load->language('extension/showmoduleinleftmodule4011/module/menulink');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/showmoduleinleftmodule4011/module/menulink')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('module_showmoduleinleftmodule4011', $this->request->post);

            $json['redirect'] = str_replace('&amp;', '&', $this->url->link('extension/showmoduleinleftmodule4011/module/menulink', 'user_token=' . $this->session->data['user_token']));
            $this->session->data['module_showmoduleinleftmodule4011_success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install(): void
    {
        // add events
        $this->load->model('setting/event');
        if (version_compare(VERSION, '4.0.1.0', '>=')) {
            $data = [
                'code' => 'module_showmoduleinleftmodule4011',
                'description' => '',
                'trigger' => 'admin/view/common/column_left/before',
                'action' => 'extension/showmoduleinleftmodule4011/module/menulink|eventViewCommonColumnLeftBefore',
                'status' => true,
                'sort_order' => 0,
            ];
            $this->model_setting_event->addEvent($data);
        } else {
            $this->model_setting_event->addEvent('module_showmoduleinleftmodule4011', '', 'admin/view/common/column_left/before', 'extension/showmoduleinleftmodule4011/module/menulink|eventViewCommonColumnLeftBefore');
        }
    }

    public function uninstall(): void
    {
        // remove events
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('module_showmoduleinleftmodule4011');
    }

    public function eventViewCommonColumnLeftBefore(&$route, &$data, &$code)
    {
        if (!$this->config->get('module_showmoduleinleftmodule4011_status')) {
            return null;
        }

        $this->load->language('extension/showmoduleinleftmodule4011/module/menulink');
        $text_showmoduleinleftmodule4011 = $this->language->get('menu_showmoduleinleftmodule4011');

        $data['menus'][] = [
            'id' => 'menu-export-impport',
            'icon' => 'fas fa-puzzle-piece',
            'name' => $text_showmoduleinleftmodule4011,
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module'),
            'children' => [],
        ];
        return null;
    }
}
