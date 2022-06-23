<?php
namespace Opencart\Admin\Controller\Extension\Webocreation4\Theme;

class ThemeStandard extends \Opencart\System\Engine\Controller
{
    public function index(): void
    {

        $this->load->language('extension/webocreation4/theme/theme_standard');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']),
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=theme'),
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/webocreation4/theme/theme_standard', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->request->get['store_id']),
        ];

        $data['save'] = $this->url->link('extension/webocreation4/theme/theme_standard|save', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->request->get['store_id']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=theme');

        if (isset($this->request->get['store_id'])) {
            $this->load->model('setting/setting');

            $setting_info = $this->model_setting_setting->getSetting('theme_standard', $this->request->get['store_id']);
        }

        if (isset($setting_info['theme_standard_status'])) {
            $data['theme_standard_status'] = $setting_info['theme_standard_status'];
        } else {
            $data['theme_standard_status'] = '';
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/webocreation4/theme/theme_standard', $data));
    }

    public function save(): void
    {
        $this->load->language('extension/webocreation4/theme/theme_standard');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/webocreation4/theme/theme_standard')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('theme_standard', $this->request->post, $this->request->get['store_id']);

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    public function install(): void
    {
        if ($this->user->hasPermission('modify', 'extension/webocreation4/theme/theme_standard')) {
            $this->load->model('setting/startup');
            $this->model_setting_startup->addStartup('theme_standard', 'catalog/extension/webocreation4/startup/theme_standard', 1, 2);

        }
    }

    public function uninstall(): void
    {
        if ($this->user->hasPermission('modify', 'extension/webocreation4/theme/theme_standard')) {
            $this->load->model('setting/startup');

            $this->model_setting_startup->deleteStartupByCode('theme_standard');
        }
    }
}
