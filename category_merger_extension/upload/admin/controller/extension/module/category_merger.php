<?php
class ControllerExtensionModuleCategoryMerger extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/category_merger');
        
        $this->document->setTitle($this->language->get('heading_title'));
        
        $this->load->model('extension/module/category_merger');
        $this->load->model('catalog/category');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $source_category_id = $this->request->post['source_category_id'];
            $target_category_id = $this->request->post['target_category_id'];
            
            // Perform the category merge operation
            $result = $this->model_extension_module_category_merger->mergeCategories($source_category_id, $target_category_id);
            
            if ($result) {
                $this->session->data['success'] = $this->language->get('text_success');
            } else {
                $this->session->data['error_warning'] = $this->language->get('error_merge_failed');
            }
            
            $this->response->redirect($this->url->link('extension/module/category_merger', 'user_token=' . $this->session->data['user_token'], true));
        }
        
        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } elseif (isset($this->session->data['error_warning'])) {
            $data['error_warning'] = $this->session->data['error_warning'];
            unset($this->session->data['error_warning']);
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
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/category_merger', 'user_token=' . $this->session->data['user_token'], true)
        );
        
        $data['action'] = $this->url->link('extension/module/category_merger', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        
        // Get all categories for dropdown selection
        $data['categories'] = $this->model_catalog_category->getCategories(array('sort' => 'name'));
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        $this->response->setOutput($this->load->view('extension/module/category_merger', $data));
    }
    
    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/category_merger')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (empty($this->request->post['source_category_id'])) {
            $this->error['warning'] = $this->language->get('error_source_category');
        }
        
        if (empty($this->request->post['target_category_id'])) {
            $this->error['warning'] = $this->language->get('error_target_category');
        }
        
        if (!empty($this->request->post['source_category_id']) && 
            !empty($this->request->post['target_category_id']) && 
            $this->request->post['source_category_id'] == $this->request->post['target_category_id']) {
            $this->error['warning'] = $this->language->get('error_same_category');
        }
        
        return !$this->error;
    }
    
    public function install() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_category_merger', array('module_category_merger_status' => 1));
        
        $this->load->model('user/user_group');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/category_merger');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/category_merger');
    }
    
    public function uninstall() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('module_category_merger');
    }
}
