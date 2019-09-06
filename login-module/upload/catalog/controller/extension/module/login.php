<?php
//As our file is login.php so Class name is ControllerExtensionModuleLogin which extends the Controller base class
class ControllerExtensionModuleLogin extends Controller {
//Create index method. Index method is called automatically if no parameters are passed, check this video tutorial for details https://www.youtube.com/watch?v=X6bsMmReT-4
public function index() {
//Loads the language file by which the varaibles of language file are accessible in twig files
$this->load->language('extension/module/login');
// This is to set the value if the customer is logged in or not, if customer are logged in then the value is 1
$data['logged'] = $this->customer->isLogged();

//This is to set the form action URL, register link, and forgotten password URL 
$data['action'] = $this->url->link('account/login', '', true);
$data['register'] = $this->url->link('account/register', '', true);
$data['forgotten'] = $this->url->link('account/forgotten', '', true);

//This is to set redirect so that when someone is logged in then they will get redirected to that page.
if (isset($this->request->post['redirect']) && (strpos($this->request->post['redirect'], $this->config->get('config_url')) !== false || strpos($this->request->post['redirect'], $this->config->get('config_ssl')) !== false)) {
	$data['redirect'] = $this->request->post['redirect'];
} elseif (isset($this->session->data['redirect'])) {
	$data['redirect'] = $this->session->data['redirect'];
	unset($this->session->data['redirect']);
} else {
	$data['redirect'] = '';
}

//This is to set the success message
if (isset($this->session->data['success'])) {
	$data['success'] = $this->session->data['success'];
	unset($this->session->data['success']);
} else {
	$data['success'] = '';
}

//To check is there is post value of email is set, if not then it is empty
if (isset($this->request->post['email'])) {
	$data['email'] = $this->request->post['email'];
} else {
	$data['email'] = '';
}

//To check is there is post value of email is set, if not then it is empty
if (isset($this->request->post['password'])) {
	$data['password'] = $this->request->post['password'];
} else {
	$data['password'] = '';
}

//To load the login view
return $this->load->view('extension/module/login', $data);
}
}