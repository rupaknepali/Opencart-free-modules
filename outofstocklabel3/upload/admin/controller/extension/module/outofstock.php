<?php

class ControllerExtensionModuleOutOfStock extends Controller
{
    public function index()
    {
        $this->load->language('extension/module/outofstock');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        $data = array();
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_outofstock', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('extension/module', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/outofstock', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['action'] = $this->url->link('extension/module/outofstock', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['cancel'] = $this->url->link('extension/module', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        // Status of the module
        if (isset($this->request->post['module_outofstock_status'])) {
            $data['module_outofstock_status'] = $this->request->post['module_outofstock_status'];
        } else {
            $data['module_outofstock_status'] = $this->config->get('module_outofstock_status');
        }
        // Marker show in Product Page
        if (isset($this->request->post['module_outofstock_show_marker_in_product_page'])) {
            $data['module_outofstock_show_marker_in_product_page'] = $this->request->post['module_outofstock_show_marker_in_product_page'];
        } else {
            $data['module_outofstock_show_marker_in_product_page'] = $this->config->get('module_outofstock_show_marker_in_product_page');
        }

        // label
        $this->load->model('localisation/language');
        $data['languages'] = $this->model_localisation_language->getLanguages();

        if (isset($this->request->post['module_outofstock_label'])) {
            $data['module_outofstock_label'] = $this->request->post['module_outofstock_label'];
        } else {
            $data['module_outofstock_label'] = $this->config->get('module_outofstock_label');
        }

        // style
        if (isset($this->request->post['module_outofstock_style'])) {
            $data['module_outofstock_style'] = $this->request->post['module_outofstock_style'];
        } else {
            $data['module_outofstock_style'] = $this->config->get('module_outofstock_style');
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/outofstock', $data));
    }

    public function install()
    {
        $this->load->model('setting/setting');
        $post = array();
        $post['module_outofstock_status'] = "1";
        $post['module_outofstock_show_marker_in_product_page'] = "1";
        $post['module_outofstock_style'] = ".box {
            position: relative;
            background: #EEE;
          }
          .ribbon {
            position: absolute;
            left: -5px; top: -5px;
            z-index: 1;
            overflow: hidden;
            width: 75px; height: 75px;
            text-align: right;
          }
          .ribbon span {
            font-size: 10px;
            font-weight: bold;
            color: #FFF;
            text-transform: uppercase;
            text-align: center;
            line-height: 20px;
            transform: rotate(-45deg);
            -webkit-transform: rotate(-45deg);
            width: 100px;
            display: block;
            background: #79A70A;
            background: linear-gradient(#F70505 0%, #8F0808 100%);
            box-shadow: 0 3px 10px -5px rgba(0, 0, 0, 1);
            position: absolute;
            top: 19px; left: -21px;
          }
          .ribbon span::before {
            content: '';
            position: absolute; left: 0px; top: 100%;
            z-index: -1;
            border-left: 3px solid #8F0808;
            border-right: 3px solid transparent;
            border-bottom: 3px solid transparent;
            border-top: 3px solid #8F0808;
          }
          .ribbon span::after {
            content: '';
            position: absolute; right: 0px; top: 100%;
            z-index: -1;
            border-left: 3px solid transparent;
            border-right: 3px solid #8F0808;
            border-bottom: 3px solid transparent;
            border-top: 3px solid #8F0808;
          }";
        $this->model_setting_setting->editSetting('module_outofstock', $post);
    }

    protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/outofstock')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
    }
    
}