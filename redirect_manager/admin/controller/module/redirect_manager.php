<?php
namespace Opencart\Admin\Controller\Extension\RedirectManager\Module;
class RedirectManager extends \Opencart\System\Engine\Controller {
    private array $error = [];

    public function menu(&$route, &$data, &$output) {
        // Only add the menu if the module is enabled
        $this->load->model('setting/setting');
        $module_status = $this->model_setting_setting->getValue('module_redirect_manager_status');
        
        if ($module_status) {
            $this->load->language('extension/redirect_manager/module/redirect_manager');
            
            $menu_text = 'Redirect Manager';
            $menu_link = $this->url->link('extension/redirect_manager/module/redirect_manager', 'user_token=' . $this->session->data['user_token'], true);
            
            $allproductslisting_menu = [
                'name'     => $menu_text,
                'icon' => 'fa fa-exchange-alt',
                'column'   => 1,
                'href'     => $menu_link
            ];
            array_push($data['menus'], $allproductslisting_menu);
        }
    }
    
    public function install(): void {
        $this->load->model('extension/redirect_manager/module/redirect_manager');
        $this->model_extension_redirect_manager_module_redirect_manager->install();
        
        // Register the event to add menu item
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent([
            'code' => 'redirect_manager_menu',
            'description' => 'Adds Redirect Manager to admin menu',
            'trigger' => 'admin/view/common/column_left/before',
            'action' => 'extension/redirect_manager/module/redirect_manager.menu',
            'status' => 1,
            'sort_order' => 1
        ]);
    }

    public function uninstall(): void {
        $this->load->model('extension/redirect_manager/module/redirect_manager');
        $this->model_extension_redirect_manager_module_redirect_manager->uninstall();
        
        // Remove the event
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('redirect_manager_menu');
    }

    public function index(): void {
        $this->load->language('extension/redirect_manager/module/redirect_manager');
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
            'href' => $this->url->link('extension/redirect_manager/module/redirect_manager', 'user_token=' . $this->session->data['user_token'])
        ];

        $data['save'] = $this->url->link('extension/redirect_manager/module/redirect_manager.save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        $data['add'] = $this->url->link('extension/redirect_manager/module/redirect_manager.add', 'user_token=' . $this->session->data['user_token']);
        $data['delete'] = $this->url->link('extension/redirect_manager/module/redirect_manager.delete', 'user_token=' . $this->session->data['user_token']);
        $data['import'] = $this->url->link('extension/redirect_manager/module/redirect_manager.import', 'user_token=' . $this->session->data['user_token']);
        $data['export'] = $this->url->link('extension/redirect_manager/module/redirect_manager.export', 'user_token=' . $this->session->data['user_token']);
        $data['clear_logs'] = $this->url->link('extension/redirect_manager/module/redirect_manager.clearLogs', 'user_token=' . $this->session->data['user_token']);

        $data['list'] = $this->getList();
        $data['not_found_list'] = $this->getNotFoundList();

        $data['module_redirect_manager_status'] = $this->config->get('module_redirect_manager_status');
    $data['module_redirect_manager_log_status'] = $this->config->get('module_redirect_manager_log_status');

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/redirect_manager/module/redirect_manager', $data));
    }
    
    public function save(): void {
        $this->load->language('extension/redirect_manager/module/redirect_manager');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/redirect_manager/module/redirect_manager')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('module_redirect_manager', $this->request->post);
            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function list(): void {
        $this->load->language('extension/redirect_manager/module/redirect_manager');
        $this->response->setOutput($this->getList());
    }

    protected function getList(): string {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'from_url';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['redirects'] = [];

        $filter_data = [
            'sort'  => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_pagination_admin'),
            'limit' => $this->config->get('config_pagination_admin')
        ];

        $this->load->model('extension/redirect_manager/module/redirect_manager');

        $redirect_total = $this->model_extension_redirect_manager_module_redirect_manager->getTotalRedirects();
        $results = $this->model_extension_redirect_manager_module_redirect_manager->getRedirects($filter_data);

        foreach ($results as $result) {
            $data['redirects'][] = [
                'redirect_id'   => $result['redirect_id'],
                'from_url'      => $result['from_url'],
                'to_url'        => $result['to_url'],
                'response_code' => $result['response_code'],
                'status'        => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'edit'          => $this->url->link('extension/redirect_manager/module/redirect_manager.edit', 'user_token=' . $this->session->data['user_token'] . '&redirect_id=' . $result['redirect_id'] . $url),
                'delete'        => $this->url->link('extension/redirect_manager/module/redirect_manager.delete', 'user_token=' . $this->session->data['user_token'] . '&redirect_id=' . $result['redirect_id'] . $url)
            ];
        }

        $url = '';

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        $data['sort_from_url'] = $this->url->link('extension/redirect_manager/module/redirect_manager.list', 'user_token=' . $this->session->data['user_token'] . '&sort=from_url' . $url);
        $data['sort_to_url'] = $this->url->link('extension/redirect_manager/module/redirect_manager.list', 'user_token=' . $this->session->data['user_token'] . '&sort=to_url' . $url);
        $data['sort_response_code'] = $this->url->link('extension/redirect_manager/module/redirect_manager.list', 'user_token=' . $this->session->data['user_token'] . '&sort=response_code' . $url);
        $data['sort_date_added'] = $this->url->link('extension/redirect_manager/module/redirect_manager.list', 'user_token=' . $this->session->data['user_token'] . '&sort=date_added' . $url);

        $data['sort'] = $sort;
        $data['order'] = $order;

        $data['pagination'] = $this->load->controller('common/pagination', [
            'total' => $redirect_total,
            'page'  => $page,
            'limit' => $this->config->get('config_pagination_admin'),
            'url'   => $this->url->link('extension/redirect_manager/module/redirect_manager.list', 'user_token=' . $this->session->data['user_token'] . '&page={page}' . $url)
        ]);

        $data['results'] = sprintf($this->language->get('text_pagination'), ($redirect_total) ? (($page - 1) * $this->config->get('config_pagination_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_pagination_admin')) > ($redirect_total - $this->config->get('config_pagination_admin'))) ? $redirect_total : ((($page - 1) * $this->config->get('config_pagination_admin')) + $this->config->get('config_pagination_admin')), $redirect_total, ceil($redirect_total / $this->config->get('config_pagination_admin')));

        $data['user_token'] = $this->session->data['user_token'];

        return $this->load->view('extension/redirect_manager/module/redirect_list', $data);
    }

    public function notFound(): void {
        $this->load->language('extension/redirect_manager/module/redirect_manager');
        $this->response->setOutput($this->getNotFoundList());
    }

    protected function getNotFoundList(): string {
        if (isset($this->request->get['filter_url'])) {
            $filter_url = $this->request->get['filter_url'];
        } else {
            $filter_url = '';
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'n.date_modified';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int)$this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_url'])) {
            $url .= '&filter_url=' . urlencode(html_entity_decode($this->request->get['filter_url'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['logs'] = [];

        $filter_data = [
            'filter_url' => $filter_url,
            'sort'       => $sort,
            'order'      => $order,
            'start'      => ($page - 1) * $this->config->get('config_pagination_admin'),
            'limit'      => $this->config->get('config_pagination_admin')
        ];

        $this->load->model('extension/redirect_manager/module/redirect_manager');

        $log_total = $this->model_extension_redirect_manager_module_redirect_manager->getTotalNotFoundLogs($filter_data);
        $results = $this->model_extension_redirect_manager_module_redirect_manager->getNotFoundLogs($filter_data);

        foreach ($results as $result) {
            $data['logs'][] = [
                'url'           => $result['url'],
                'hits'          => $result['hits'],
                'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'date_modified' => date($this->language->get('date_format_short'), strtotime($result['date_modified']))
            ];
        }

        $url = '';

        if (isset($this->request->get['filter_url'])) {
            $url .= '&filter_url=' . urlencode(html_entity_decode($this->request->get['filter_url'], ENT_QUOTES, 'UTF-8'));
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        $data['sort_url'] = $this->url->link('extension/redirect_manager/module/redirect_manager.notFound', 'user_token=' . $this->session->data['user_token'] . '&sort=n.url' . $url);
        $data['sort_hits'] = $this->url->link('extension/redirect_manager/module/redirect_manager.notFound', 'user_token=' . $this->session->data['user_token'] . '&sort=n.hits' . $url);
        $data['sort_date_added'] = $this->url->link('extension/redirect_manager/module/redirect_manager.notFound', 'user_token=' . $this->session->data['user_token'] . '&sort=n.date_added' . $url);
        $data['sort_date_modified'] = $this->url->link('extension/redirect_manager/module/redirect_manager.notFound', 'user_token=' . $this->session->data['user_token'] . '&sort=n.date_modified' . $url);

        $data['sort'] = $sort;
        $data['order'] = $order;
        $data['filter_url'] = $filter_url;

        $url = '';

        if (isset($this->request->get['filter_url'])) {
            $url .= '&filter_url=' . urlencode(html_entity_decode($this->request->get['filter_url'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $data['pagination'] = $this->load->controller('common/pagination', [
            'total' => $log_total,
            'page'  => $page,
            'limit' => $this->config->get('config_pagination_admin'),
            'url'   => $this->url->link('extension/redirect_manager/module/redirect_manager.notFound', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}')
        ]);

        $data['results'] = sprintf($this->language->get('text_pagination'), ($log_total) ? (($page - 1) * $this->config->get('config_pagination_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_pagination_admin')) > ($log_total - $this->config->get('config_pagination_admin'))) ? $log_total : ((($page - 1) * $this->config->get('config_pagination_admin')) + $this->config->get('config_pagination_admin')), $log_total, ceil($log_total / $this->config->get('config_pagination_admin')));

        return $this->load->view('extension/redirect_manager/module/not_found_list', $data);
    }

    public function add(): void {
        $this->load->language('extension/redirect_manager/module/redirect_manager');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->getForm();
    }

    public function edit(): void {
        $this->load->language('extension/redirect_manager/module/redirect_manager');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->getForm();
    }

    public function saveRedirect(): void {
        $this->load->language('extension/redirect_manager/module/redirect_manager');
        $json = [];

        if (!$this->validateForm()) {
            $json['error'] = $this->error;
        } else {
            $this->load->model('extension/redirect_manager/module/redirect_manager');
            $data = $this->request->post;
            // The form sends '1' for checked and '0' for unchecked (from the hidden input).
            $data['status'] = (int)($this->request->post['status'] ?? 0);
            $data['is_regex'] = (int)($this->request->post['is_regex'] ?? 0);

            if (empty($this->request->get['redirect_id'])) {
                $this->model_extension_redirect_manager_module_redirect_manager->addRedirect($data);
            } else {
                $this->model_extension_redirect_manager_module_redirect_manager->editRedirect($this->request->get['redirect_id'], $data);
            }
            $json['success'] = $this->language->get('text_success');
            $json['redirect'] = html_entity_decode($this->url->link('extension/redirect_manager/module/redirect_manager', 'user_token=' . $this->session->data['user_token']), ENT_QUOTES, 'UTF-8');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete(): void {
        $this->load->language('extension/redirect_manager/module/redirect_manager');
        $json = [];

        $this->load->model('extension/redirect_manager/module/redirect_manager');

        if (isset($this->request->post['selected'])) {
            $selected = $this->request->post['selected'];
        } else {
            $selected = [];
        }

        if (isset($this->request->get['redirect_id'])) {
            $selected[] = (int)$this->request->get['redirect_id'];
        }

        if ($selected && $this->validateDelete()) {
            foreach ($selected as $redirect_id) {
                $this->model_extension_redirect_manager_module_redirect_manager->deleteRedirect($redirect_id);
            }

            $json['success'] = $this->language->get('text_success');
        } else {
            $json['error'] = $this->language->get('error_permission');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    protected function getForm(): void {
        $this->load->model('extension/redirect_manager/module/redirect_manager');
        $data['text_form'] = !isset($this->request->get['redirect_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        $data['error_warning'] = $this->error['warning'] ?? '';
        $data['error_from_url'] = $this->error['from_url'] ?? '';
        $data['error_to_url'] = $this->error['to_url'] ?? '';

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])];
        $data['breadcrumbs'][] = ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')];
        $data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/redirect_manager/module/redirect_manager', 'user_token=' . $this->session->data['user_token'])];

        if (empty($this->request->get['redirect_id'])) {
            $data['save'] = $this->url->link('extension/redirect_manager/module/redirect_manager.saveRedirect', 'user_token=' . $this->session->data['user_token']);
        } else {
            $data['save'] = $this->url->link('extension/redirect_manager/module/redirect_manager.saveRedirect', 'user_token=' . $this->session->data['user_token'] . '&redirect_id=' . $this->request->get['redirect_id']);
        }

        $data['back'] = $this->url->link('extension/redirect_manager/module/redirect_manager', 'user_token=' . $this->session->data['user_token']);

        if (isset($this->request->get['redirect_id'])) {
            $redirect_info = $this->model_extension_redirect_manager_module_redirect_manager->getRedirect($this->request->get['redirect_id']);
        } else {
            $redirect_info = [];
        }

        $data['redirect_id'] = $this->request->get['redirect_id'] ?? 0;
        $data['from_url'] = $this->request->post['from_url'] ?? $redirect_info['from_url'] ?? '';
        $data['to_url'] = $this->request->post['to_url'] ?? $redirect_info['to_url'] ?? '';
        $data['response_code'] = $this->request->post['response_code'] ?? $redirect_info['response_code'] ?? 301;
        if (isset($this->request->post['status'])) {
            $data['status'] = $this->request->post['status'];
        } elseif (!empty($redirect_info)) {
            $data['status'] = $redirect_info['status'];
        } else {
            $data['status'] = true;
        }

        if (isset($this->request->post['is_regex'])) {
            $data['is_regex'] = $this->request->post['is_regex'];
        } elseif (!empty($redirect_info)) {
            $data['is_regex'] = $redirect_info['is_regex'];
        } else {
            $data['is_regex'] = false;
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/redirect_manager/module/redirect_form', $data));
    }

    protected function validateForm(): bool {
        if (!$this->user->hasPermission('modify', 'extension/redirect_manager/module/redirect_manager')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ((mb_strlen($this->request->post['from_url']) < 1) || (mb_strlen($this->request->post['from_url']) > 255)) {
            $this->error['from_url'] = $this->language->get('error_from_url');
        }

        if ((mb_strlen($this->request->post['to_url']) < 1) || (mb_strlen($this->request->post['to_url']) > 255)) {
            $this->error['to_url'] = $this->language->get('error_to_url');
        }

        return !$this->error;
    }

    protected function validateDelete(): bool {
        if (!$this->user->hasPermission('modify', 'extension/redirect_manager/module/redirect_manager')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    public function import(): void {
        $this->load->language('extension/redirect_manager/module/redirect_manager');
        $this->document->setTitle($this->language->get('heading_title_import'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateImport()) {
            $file = $this->request->files['import']['tmp_name'];

            if (is_uploaded_file($file)) {
                $this->load->model('extension/redirect_manager/module/redirect_manager');
                $handle = fopen($file, 'r');
                fgetcsv($handle); // Skip header row

                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) >= 4) {
                        $this->model_extension_redirect_manager_module_redirect_manager->addRedirect([
                            'from_url'      => $data[0],
                            'to_url'        => $data[1],
                            'response_code' => $data[2],
                            'status'        => $data[3],
                            'is_regex'      => isset($data[4]) ? $data[4] : 0
                        ]);
                    }
                }

                fclose($handle);
                $this->session->data['success'] = $this->language->get('text_success_import');
                $this->response->redirect($this->url->link('extension/redirect_manager/module/redirect_manager', 'user_token=' . $this->session->data['user_token']));
            }
        }

        $this->getImportForm();
    }

    protected function getImportForm(): void {
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])];
        $data['breadcrumbs'][] = ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')];
        $data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/redirect_manager/module/redirect_manager', 'user_token=' . $this->session->data['user_token'])];
        $data['breadcrumbs'][] = ['text' => $this->language->get('heading_title_import'), 'href' => $this->url->link('extension/redirect_manager/module/redirect_manager.import', 'user_token=' . $this->session->data['user_token'])];

        $data['action'] = $this->url->link('extension/redirect_manager/module/redirect_manager.import', 'user_token=' . $this->session->data['user_token']);
        $data['cancel'] = $this->url->link('extension/redirect_manager/module/redirect_manager', 'user_token=' . $this->session->data['user_token']);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/redirect_manager/module/redirect_import', $data));
    }

    protected function validateImport(): bool {
        if (!$this->user->hasPermission('modify', 'extension/redirect_manager/module/redirect_manager')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!isset($this->request->files['import']) || $this->request->files['import']['error'] != UPLOAD_ERR_OK) {
            $this->error['warning'] = $this->language->get('error_upload');
        }

        return !$this->error;
    }

    public function export(): void {
        $this->load->model('extension/redirect_manager/module/redirect_manager');
        $redirects = $this->model_extension_redirect_manager_module_redirect_manager->getRedirects();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=\"redirects.csv\"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['from_url', 'to_url', 'response_code', 'status', 'is_regex']);

        foreach ($redirects as $redirect) {
            fputcsv($output, [
                $redirect['from_url'],
                $redirect['to_url'],
                $redirect['response_code'],
                $redirect['status'],
                $redirect['is_regex']
            ]);
        }

        fclose($output);
        exit();
    }

    public function clearLogs(): void {
        $this->load->language('extension/redirect_manager/module/redirect_manager');

        if (!$this->user->hasPermission('modify', 'extension/redirect_manager/module/redirect_manager')) {
            $this->session->data['error_warning'] = $this->language->get('error_permission');
        } else {
            $this->load->model('extension/redirect_manager/module/redirect_manager');
            $this->model_extension_redirect_manager_module_redirect_manager->clearNotFoundLogs();
            $this->session->data['success'] = $this->language->get('text_success_clear_logs');
        }
        $this->response->redirect($this->url->link('extension/redirect_manager/module/redirect_manager', 'user_token=' . $this->session->data['user_token']));
    }
}