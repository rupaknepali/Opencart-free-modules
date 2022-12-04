<?php
class ControllerExtensionModuleNewsticker extends Controller {
	private $error = array();

	public function index() {
        if($this->checkDatabase()) {

            $this->language->load('newsticker/install');

            $this->document->setTitle($this->language->get('error_database'));

            $data['install_database'] = $this->url->link('newsticker/install/installDatabase', 'user_token=' . $this->session->data['user_token'], 'SSL');

            $data['text_install_message'] = $this->language->get('text_install_message');

            $data['text_upgread'] = $this->language->get('text_upgread');

            $data['error_database'] = $this->language->get('error_database');

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text'      => $this->language->get('text_home'),
                'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
                'separator' => false
            );

            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('newsticker/notification', $data));

        }
        else {
            $this->load->language('extension/module/newsticker');
            $data['linkuser_token'] = $this->session->data['user_token'];
            $data['limited_time_offer'] = $this->language->get('limited_time_offer');

            $this->document->setTitle($this->language->get('heading_title'));
            $this->document->addStyle('view/stylesheet/bootstrap-colorpicker.min.css');
            $this->document->addScript('view/javascript/bootstrap-colorpicker.min.js');


            $this->load->model('setting/module');

            if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
                if (!isset($this->request->get['module_id'])) {
                    $this->model_setting_module->addModule('newsticker', $this->request->post);
                } else {
                    $this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
                }
    
                $this->session->data['success'] = $this->language->get('text_success');
    
                $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
            }

            $data['heading_title'] = $this->language->get('heading_title');
            $data['insertfromhere'] = $this->language->get('insertfromhere');
            $data['here'] = $this->language->get('here');
            $data['entry_color'] = $this->language->get('entry_color');
            $data['text_edit'] = $this->language->get('text_edit');
            $data['text_enabled'] = $this->language->get('text_enabled');
            $data['text_disabled'] = $this->language->get('text_disabled');

            $data['entry_name'] = $this->language->get('entry_name');
            $data['entry_newsticker'] = $this->language->get('entry_newsticker');

            $data['entry_status'] = $this->language->get('entry_status');

            $data['button_save'] = $this->language->get('button_save');
            $data['button_cancel'] = $this->language->get('button_cancel');

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



            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
            );

            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
            );

            if (!isset($this->request->get['module_id'])) {
                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('heading_title'),
                    'href' => $this->url->link('extension/module/newsticker', 'user_token=' . $this->session->data['user_token'], true)
                );
            } else {
                $data['breadcrumbs'][] = array(
                    'text' => $this->language->get('heading_title'),
                    'href' => $this->url->link('extension/module/newsticker', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
                );
            }

            if (!isset($this->request->get['module_id'])) {
                $data['action'] = $this->url->link('extension/module/newsticker', 'user_token=' . $this->session->data['user_token'], true);
            } else {
                $data['action'] = $this->url->link('extension/module/newsticker', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
            }
            
            $data['linktoken'] = $this->session->data['user_token'];
            $data['cancel'] = $this->url->link('marketplace/module', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

            if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
                $module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
            }

            if (isset($this->request->post['name'])) {
                $data['name'] = $this->request->post['name'];
            } elseif (!empty($module_info)) {
                $data['name'] = $module_info['name'];
            } else {
                $data['name'] = '';
            }

            if (isset($this->request->post['color'])) {
                $data['color'] = $this->request->post['color'];
            } elseif (!empty($module_info)) {
                $data['color'] = $module_info['color'];
            } else {
                $data['color'] = '#3597de';
            }

            if (isset($this->request->post['limitedtime'])) {
                $data['limitedtime'] = $this->request->post['limitedtime'];
            } elseif (!empty($module_info)) {
                $data['limitedtime'] = $module_info['limitedtime'];
            } else {
                $data['limitedtime'] = 'Limited Time Offer';
            }

            if (isset($this->request->post['newsticker_id'])) {
                $data['newsticker_id'] = $this->request->post['newsticker_id'];
            } elseif (!empty($module_info)) {
                $data['newsticker_id'] = $module_info['newsticker_id'];
            } else {
                $data['newsticker_id'] = '';
            }

            $this->load->model('design/newsticker');

            $data['newstickers'] = $this->model_design_newsticker->getnewstickers();



            if (isset($this->request->post['status'])) {
                $data['status'] = $this->request->post['status'];
            } elseif (!empty($module_info)) {
                $data['status'] = $module_info['status'];
            } else {
                $data['status'] = '';
            }

            $data['header'] = $this->load->controller('common/header');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['footer'] = $this->load->controller('common/footer');

            $this->response->setOutput($this->load->view('extension/module/newsticker', $data));
        }
	}
    public function checkDatabase() {
        $database_not_found = $this->load->controller('newsticker/install/validateTable');

        if(!$database_not_found) {
            return true;
        }

        return false;
    }
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/newsticker')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}



		return !$this->error;
	}
}