<?php
class ControllerExtensionModuleCsvReviewImport extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/csv_review_import');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('extension/module/csv_review_import');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if (isset($this->request->files['file']['tmp_name']) && is_uploaded_file($this->request->files['file']['tmp_name'])) {
                $this->model_extension_module_csv_review_import->importReviews($this->request->files['file']['tmp_name']);
                $this->session->data['success'] = $this->language->get('text_success_import');
            } else {
                $this->error['warning'] = $this->language->get('error_upload');
            }
            
            $this->response->redirect($this->url->link('extension/module/csv_review_import', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->getForm();
    }

    public function install() {
        $this->load->model('setting/setting');
        $this->load->model('extension/module/csv_review_import');
        $this->model_extension_module_csv_review_import->install();
    }

    public function uninstall() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('module_csv_review_import');
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/csv_review_import')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!isset($this->request->files['file']['tmp_name']) || !is_uploaded_file($this->request->files['file']['tmp_name'])) {
            $this->error['warning'] = $this->language->get('error_upload');
        }

        return !$this->error;
    }

    protected function getForm() {
        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_import_info'] = $this->language->get('text_import_info');
        
        $data['entry_file'] = $this->language->get('entry_file');
        $data['entry_status'] = $this->language->get('entry_status');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_import'] = $this->language->get('button_import');

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

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/csv_review_import', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/csv_review_import', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/csv_review_import', $data));
    }
}
