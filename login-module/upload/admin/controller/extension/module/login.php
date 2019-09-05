<?php
//As our file is login.php so Class name is ControllerExtensionModuleLogin which extends the Controller base class
class ControllerExtensionModuleLogin extends Controller {

//Declaration of the private property 'error' so that we can get check if any error occurs in the whole class.
private $error = array();

//Index method is called automatically if no parameters are passed, check this video tutorial for details https://www.youtube.com/watch?v=X6bsMmReT-4
public function index() {

//Loads the language file by which the varaibles of language file are accessible in twig files
$this->load->language('extension/module/login');

//Set the Document title
$this->document->setTitle($this->language->get('heading_title'));

//Loads the model admin/model/setting/setting.php so that we can use the methods defined there.
$this->load->model('setting/setting');

//This is how we check if it is form submit. When we submit the form then this block of code also run. Then it also validate the modify permission and other validation.
if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
	
	//Look at this line of code, this is the code section which distinguish from Single Instance to Multi Instance. If it is multi instance then it will look like below instead of editSetting it will be addModule and editModule and setting module model is called above. 
	// if (!isset($this->request->get['module_id'])) {
	// 	$this->model_setting_module->addModule('bestseller', $this->request->post);
	// } else {
	// 	$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
	// }
	//This editSetting save the data to  oc_setting database table, see module_ is important else it will not be saved. If you are creating shipping extension then it should be shipping_, for payment extension it should be payment_
	$this->model_setting_setting->editSetting('module_login', $this->request->post);
	//This set the success message in the session.
	$this->session->data['success'] = $this->language->get('text_success');
	//This is to redirect to the extensions page.
	$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
}

//This is to check if there are any warnings
if (isset($this->error['warning'])) {
	$data['error_warning'] = $this->error['warning'];
} else {
	$data['error_warning'] = '';
}

//Following are for breadcrumbs
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
	'href' => $this->url->link('extension/module/login', 'user_token=' . $this->session->data['user_token'], true)
);

//Form action  URL
$data['action'] = $this->url->link('extension/module/login', 'user_token=' . $this->session->data['user_token'], true);

//Form cancel URL
$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

//This is to check what we fill out in the form, whether the status is Enabled or Disabled. If it is the loading time then it gets the config value which we store in the oc_setting database table.
if (isset($this->request->post['module_login_status'])) {
	$data['module_login_status'] = $this->request->post['module_login_status'];
} else {
	$data['module_login_status'] = $this->config->get('module_login_status');
}

//This is how we load the header, column left and footer
$data['header'] = $this->load->controller('common/header');
$data['column_left'] = $this->load->controller('common/column_left');
$data['footer'] = $this->load->controller('common/footer');

//This is to set output data variables to the view or twig files and twig file is loaded and html rendering is done with it.
$this->response->setOutput($this->load->view('extension/module/login', $data));
}

//This is how validation is done, we check whether the user has permission to modify or not.
//If you want to validate the form data then you can check it here as well.
//If there is error then $this->error['warning'] is set and warning are shown.
protected function validate() {
	if (!$this->user->hasPermission('modify', 'extension/module/login')) {
		$this->error['warning'] = $this->language->get('error_permission');
	}

	return !$this->error;
}

//Closing of the Class
}