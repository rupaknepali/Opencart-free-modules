<?php
/*** As our file is testimonial.php so Class name is ControllerExtensionModuleTestimonial which extends the Controller base class ***/
class ControllerExtensionModuleTestimonial extends Controller
{
	/*** Declaration of the private property 'error' so that we can get check if any error occurs in the whole class**/
	private $error = array();
	/*** Index method is called automatically or by default, if no parameters are passed, check this video tutorial for details https://www.youtube.com/watch?v=X6bsMmReT-4 . ***/
	public function index()
	{
		/*** Loads the language file admin/language/en-gb/extension/module/testimonial.php by which the varaibles of the language file are accessible in twig file. ***/
		$this->load->language('extension/module/testimonial');
		/*** Set the Document title ***/
		$this->document->setTitle($this->language->get('heading_title'));
		/*** Loads the model admin/model/setting/module.php so that we can use the methods defined there. The difference in single instance and multi-instance is here, in single instance we used to load  $this->load->model('setting/setting'); but here we load setting/module ***/
		$this->load->model('setting/module');
		/*** This is how we check if it is form submitted or not. When we submit the form then this block of code also run. Then it also validate the modify permission and other validation. ***/
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			/*** Look at these lines of code, this code section which distinguishes from Single Instance to Multi-instance. If it is single instance then it will be like 	$this->model_setting_setting->editSetting('***', $this->request->post); This editSetting save the data to  oc_setting database table. But in multi-instance the code is like below it checks if the module_id is set if it is set then it get edit module data by editModule method, but if someone click Add button then no module_id is set and it adds new module data to the module database table. ***/
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('testimonial', $this->request->post);
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			}
			/*** This is to delete the cache of the testimonial. ***/
			$this->cache->delete('testimonial');
			/*** This set the success message in the session. ***/
			$this->session->data['success'] = $this->language->get('text_success');
			/*** This is to redirect to the extensions page. ***/
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}
		/*** This is to check if there are any warnings	 ***/	
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		/*** As we are validating 'Module Name' in the validate method so if validate method returns name error then it is assigned here and the error is shown in the form field. ***/
		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}
		/*** This is also to set the error for the form field width ***/
		if (isset($this->error['width'])) {
			$data['error_width'] = $this->error['width'];
		} else {
			$data['error_width'] = '';
		}
		/*** This is also to set the error for the form field height ***/
		if (isset($this->error['height'])) {
			$data['error_height'] = $this->error['height'];
		} else {
			$data['error_height'] = '';
		}

		/*** Following are for breadcrumbs ***/
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);
		/*** Check this section of if else statement, we see the difference in single instance and multi-instance. Here it is checking if module_id is set then it has different href URl and if not set different URL. ***/
		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/testimonial', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/testimonial', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}
		/*** Form action URL as per the module edited or for new module ***/
		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/testimonial', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/testimonial', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}
		/*** Form cancel URL ***/
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
		/*** This is to get the module data, if module_id is set then it gets module data from database and set it to $module_info variable. ***/
		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}
		/*** This will check if it form filled and clicked save button and occurs some errors then it will set the name with POST name value. If it is edit then it will set the name value with the module detail. If it is add then empty is set. ***/
		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}
		/*** Same as above this will check if it form filled and clicked save button and occurs some errors then it will set the limit with POST limit value. If it is edit then it will set the limit value with the module detail. If it is add then empty is set. ***/
		if (isset($this->request->post['limit'])) {
			$data['limit'] = $this->request->post['limit'];
		} elseif (!empty($module_info)) {
			$data['limit'] = $module_info['limit'];
		} else {
			$data['limit'] = 5;
		}
		/*** Same as above this will check if it form filled and clicked save button and occurs some errors then it will set the width with POST width value. If it is edit then it will set the width value with the module detail. If it is add then default value is set to 200. ***/
		if (isset($this->request->post['width'])) {
			$data['width'] = $this->request->post['width'];
		} elseif (!empty($module_info)) {
			$data['width'] = $module_info['width'];
		} else {
			$data['width'] = 200;
		}
		/*** Same as above this will check if it form filled and clicked save button and occurs some errors then it will set the height with POST height value. If it is edit then it will set the height value with the module detail. If it is add then default value is set to 200. ***/
		if (isset($this->request->post['height'])) {
			$data['height'] = $this->request->post['height'];
		} elseif (!empty($module_info)) {
			$data['height'] = $module_info['height'];
		} else {
			$data['height'] = 200;
		}
		/*** Same as above this will check if it form filled and clicked save button and occurs some errors then it will set the status with POST status value. If it is edit then it will set the status value with the module detail. If it is add then default value is set to empty. ***/
		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($module_info)) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = '';
		}
		/*** This is how we load the header, column left and footer ***/
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		/*** This is to set output data variables to the view or twig files and twig file is loaded and html rendering is done with it. ***/
		$this->response->setOutput($this->load->view('extension/module/testimonial', $data));
	}
	/*** This is how validation is done, we check whether the user has permission to modify or not.***/
	/*** Here we validate name, width and height ***/
	/*** If there is error then $this->error['warning'] is set and warning are shown.***/
	protected function validate()
	{

		if (!$this->user->hasPermission('modify', 'extension/module/testimonial')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (!$this->request->post['width']) {
			$this->error['width'] = $this->language->get('error_width');
		}

		if (!$this->request->post['height']) {
			$this->error['height'] = $this->language->get('error_height');
		}

		return !$this->error;
	}
}