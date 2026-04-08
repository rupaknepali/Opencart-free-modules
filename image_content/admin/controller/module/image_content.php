<?php
namespace Opencart\Admin\Controller\Extension\ImageContent\Module;
/**
 * Class ImageContent
 *
 * @package Opencart\Admin\Controller\Extension\ImageContent\Module
 */
class ImageContent extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/image_content/module/image_content');

		$this->document->setTitle($this->language->get('heading_title'));

		// Check if we need to show the list of modules or edit a specific module
		if (!isset($this->request->get['module_id'])) {
			$this->getList();
		} else {
			$this->getForm();
		}
	}

	/**
	 * Get List
	 *
	 * @return void
	 */
	protected function getList(): void {
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
			'href' => $this->url->link('extension/image_content/module/image_content', 'user_token=' . $this->session->data['user_token'])
		];

		$data['add'] = $this->url->link('extension/image_content/module/image_content', 'user_token=' . $this->session->data['user_token'] . '&module_id=0');
		$data['delete'] = $this->url->link('extension/image_content/module/image_content.delete', 'user_token=' . $this->session->data['user_token']);

		$data['modules'] = [];

		$this->load->model('setting/module');

		$results = $this->model_setting_module->getModulesByCode('image_content.image_content');

		foreach ($results as $result) {
			$data['modules'][] = [
				'module_id' => $result['module_id'],
				'name'      => $result['name'],
				'status'    => (isset($result['status']) && $result['status']) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'edit'      => $this->url->link('extension/image_content/module/image_content', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $result['module_id'])
			];
		}

		$data['sort_name'] = $this->url->link('extension/image_content/module/image_content', 'user_token=' . $this->session->data['user_token'] . '&sort=name');
		$data['sort_status'] = $this->url->link('extension/image_content/module/image_content', 'user_token=' . $this->session->data['user_token'] . '&sort=status');

		$data['sort'] = 'name';
		$data['order'] = 'ASC';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/image_content/module/image_content_list', $data));
	}

	/**
	 * Get Form
	 *
	 * @return void
	 */
	protected function getForm(): void {
		$this->load->language('extension/image_content/module/image_content');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
		$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');

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
			'href' => $this->url->link('extension/image_content/module/image_content', 'user_token=' . $this->session->data['user_token'])
		];

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/image_content/module/image_content', 'user_token=' . $this->session->data['user_token'])
			];
		} else {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/image_content/module/image_content', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'])
			];
		}

		if (!isset($this->request->get['module_id'])) {
			$data['save'] = $this->url->link('extension/image_content/module/image_content.save', 'user_token=' . $this->session->data['user_token']);
		} else {
			$data['save'] = $this->url->link('extension/image_content/module/image_content.save', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id']);
		}

		$data['back'] = $this->url->link('extension/image_content/module/image_content', 'user_token=' . $this->session->data['user_token']);

		// Extension
		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$this->load->model('setting/module');

			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}

		if (isset($module_info['name'])) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($module_info['module_description'])) {
			$data['module_description'] = $module_info['module_description'];
		} else {
			$data['module_description'] = [];
		}

		// Image
		if (isset($module_info['image'])) {
			$data['image'] = $module_info['image'];
		} else {
			$data['image'] = '';
		}

		$this->load->model('tool/image');

		if (isset($module_info['image']) && is_file(DIR_IMAGE . $module_info['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($module_info['image'], 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		// Image Position
		if (isset($module_info['image_position'])) {
			$data['image_position'] = $module_info['image_position'];
		} else {
			$data['image_position'] = 'left';
		}

		// Image Width
		if (isset($module_info['image_width'])) {
			$data['image_width'] = $module_info['image_width'];
		} else {
			$data['image_width'] = 95;
		}

		// Image Border Radius
		if (isset($module_info['image_border_radius'])) {
			$data['image_border_radius'] = $module_info['image_border_radius'];
		} else {
			$data['image_border_radius'] = '5%';
		}

		// Image Box Shadow
		if (isset($module_info['image_box_shadow'])) {
			$data['image_box_shadow'] = $module_info['image_box_shadow'];
		} else {
			$data['image_box_shadow'] = '0 10px 30px rgba(0, 0, 0, 0.15)';
		}

		// Image Transition
		if (isset($module_info['image_transition'])) {
			$data['image_transition'] = $module_info['image_transition'];
		} else {
			$data['image_transition'] = 'transform 0.3s ease, box-shadow 0.3s ease';
		}

		// Image Hover Transform
		if (isset($module_info['image_hover_transform'])) {
			$data['image_hover_transform'] = $module_info['image_hover_transform'];
		} else {
			$data['image_hover_transform'] = 'translateY(-5px)';
		}

		// CTA Button
		if (isset($module_info['cta_text'])) {
			$data['cta_text'] = $module_info['cta_text'];
		} else {
			$data['cta_text'] = [];
		}

		if (isset($module_info['cta_url'])) {
			$data['cta_url'] = $module_info['cta_url'];
		} else {
			$data['cta_url'] = [];
		}

		if (isset($module_info['cta_style'])) {
			$data['cta_style'] = $module_info['cta_style'];
		} else {
			$data['cta_style'] = [];
		}

		if (isset($module_info['cta_text_color'])) {
			$data['cta_text_color'] = $module_info['cta_text_color'];
		} else {
			$data['cta_text_color'] = [];
		}

		if (isset($module_info['cta_border_color'])) {
			$data['cta_border_color'] = $module_info['cta_border_color'];
		} else {
			$data['cta_border_color'] = [];
		}

		if (isset($module_info['cta_border_radius'])) {
			$data['cta_border_radius'] = $module_info['cta_border_radius'];
		} else {
			$data['cta_border_radius'] = [];
		}

		if (isset($module_info['cta_padding'])) {
			$data['cta_padding'] = $module_info['cta_padding'];
		} else {
			$data['cta_padding'] = [];
		}

		if (isset($module_info['cta_hover_background'])) {
			$data['cta_hover_background'] = $module_info['cta_hover_background'];
		} else {
			$data['cta_hover_background'] = [];
		}

		if (isset($module_info['cta_hover_text_color'])) {
			$data['cta_hover_text_color'] = $module_info['cta_hover_text_color'];
		} else {
			$data['cta_hover_text_color'] = [];
		}

		if (isset($module_info['cta_open_in_new_tab'])) {
			$data['cta_open_in_new_tab'] = (bool)$module_info['cta_open_in_new_tab'];
		} else {
			$data['cta_open_in_new_tab'] = false;
		}

		if (isset($module_info['title_color'])) {
			$data['title_color'] = $module_info['title_color'];
		} else {
			$data['title_color'] = '#1a1a1a';
		}

		if (isset($module_info['description_color'])) {
			$data['description_color'] = $module_info['description_color'];
		} else {
			$data['description_color'] = '#555555';
		}

		// Full Width
		if (isset($module_info['full_width'])) {
			$data['full_width'] = $module_info['full_width'];
		} else {
			$data['full_width'] = 0;
		}

		// Background Color
		if (isset($module_info['background_color'])) {
			$data['background_color'] = $module_info['background_color'];
		} else {
			$data['background_color'] = '#f4f5f7';
		}

		// Margin
		if (isset($module_info['margin'])) {
			$data['margin'] = $module_info['margin'];
		} else {
			$data['margin'] = '20px 0px';
		}

		// Padding
		if (isset($module_info['padding'])) {
			$data['padding'] = $module_info['padding'];
		} else {
			$data['padding'] = '40px 20px';
		}

		// Language
		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		if (isset($module_info['status'])) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = '';
		}

		if (isset($this->request->get['module_id'])) {
			$data['module_id'] = (int)$this->request->get['module_id'];
		} else {
			$data['module_id'] = 0;
		}

		$data['ckeditor'] = $this->config->get('config_language_admin');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/image_content/module/image_content', $data));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/image_content/module/image_content');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/image_content/module/image_content')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if ((oc_strlen($this->request->post['name']) < 3) || (oc_strlen($this->request->post['name']) > 64)) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!$json) {
			$this->load->model('setting/module');

			if (!$this->request->post['module_id']) {
				$json['module_id'] = $this->model_setting_module->addModule('image_content.image_content', $this->request->post);
			} else {
				$this->model_setting_module->editModule($this->request->post['module_id'], $this->request->post);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Delete
	 *
	 * @return void
	 */
	public function delete(): void {
		$this->load->language('extension/image_content/module/image_content');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/image_content/module/image_content')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json && isset($this->request->post['selected'])) {
			$this->load->model('setting/module');

			foreach ((array)$this->request->post['selected'] as $module_id) {
				$this->model_setting_module->deleteModule($module_id);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}