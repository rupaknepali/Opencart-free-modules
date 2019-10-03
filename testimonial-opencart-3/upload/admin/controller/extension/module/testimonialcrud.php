<?php

/*** We create a file named ‘testimonialcrud.php’ in the admin/controller/extension/module/ folder. Since we named the file testimonialcrud.php and put it at admin/controller/extension/module/ folder, the controller class name will be ControllerExtensionModuleTestimonialcrud which inherits the Controller. ***/
class ControllerExtensionModuleTestimonialcrud extends Controller
{
	/*** private error property for this class only which will hold the error message if occurs any. ***/
	private $error = array();
	/*** The index method is default method, it is called whenever the main controller ControllerExtensionModuleTestimonialcrud is called through route URL, like http://opencart.loc/admin/index.php?route=extension/module/testimonialcrud&user_token=5XdZM31DgqUkg4uEmrInmL3pp7uiaYUr.
	 * Here the language file is loaded
	 * Then Document title is set
	 * Then model file is loaded
	 * Then protected method getList is called which list out all the testimonials. Thus default page is the listing page because we called getList in index() method. ***/
	public function index()
	{
		$this->load->language('extension/module/testimonialcrud');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('extension/module/testimonial');
		$this->getList();
	}
	/*** add() - This method is called when someone clicks the add button in the listing page and the save button on the form. If the add button is clicked then it shows the forms with blank fields. If the save button is clicked on the form then it validates the data and saves data in the database and redirects to the listing page. ***/
	public function add()
	{
		$this->load->language('extension/module/testimonialcrud');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('extension/module/testimonial');
		/*** This is the section when someone clicks save button while adding the testimonial. It checks if the request method is post and if form is validated. Then it will call the addTestimonial method of model class which save the new testimonial to the database ***/
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_module_testimonial->addTestimonial($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
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
			/*** This line of code is to redirect to the listing page ***/
			$this->response->redirect($this->url->link('extension/module/testimonialcrud', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}
		/*** This is to show the form ***/
		$this->getForm();
	}
	/*** edit() - Edit method is called when someone clicks the edit button in the listing page of the testimonial which will show the form with the data, and similarly it is called when someone clicks the save button on the form while editing, when saved it will validate the form and update the data in the database and redirects to the listing page. ***/
	public function edit()
	{
		$this->load->language('extension/module/testimonialcrud');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('extension/module/testimonial');
		/*** This is the section when someone clicks edit button and save the testimonial. It checks if the request method is post and if form is validated. Then it will call the editTestimonial method of model class which save the updated testimonial to the database ***/
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_module_testimonial->editTestimonial($this->request->get['testimonial_id'], $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
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
			/*** This line of code is to redirect to the listing page ***/
			$this->response->redirect($this->url->link('extension/module/testimonialcrud', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}
		/*** This is to show the form ***/
		$this->getForm();
	}
	/*** delete() - Delete method is called when someone clicks delete button by selecting the testimonial to delete. Once testimonial/s is/are deleted then it is redirected to the listing page.***/
	public function delete()
	{
		$this->load->language('extension/module/testimonialcrud');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('extension/module/testimonial');
		/*** This is the section which find which testimonial are selected that need to be deleted. The deleteTestimonial method of the model class is called which remove the testimonial from the database ***/
		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $testimonial_id) {
				$this->model_extension_module_testimonial->deleteTestimonial($testimonial_id);
			}
			$this->session->data['success'] = $this->language->get('text_success');
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
			$this->response->redirect($this->url->link('extension/module/testimonialcrud', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}
		$this->getList();
	}

	/*** getList() - This method creates logic to create a listing and pass variables to template twig files where they are manipulated and shown in the table.
	the listing page will look like in the image url https://webocreation.com/blog/wp-content/uploads/2019/09/testimonial-listings.jpg  ***/
	protected function getList()
	{
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
		}
		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
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
		/*** Breadcrumbs variables set ***/
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/testimonialcrud', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);
		/*** Add and delete button URL setup for the form ***/
		$data['add'] = $this->url->link('extension/module/testimonialcrud/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('extension/module/testimonialcrud/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
		/*** testimonials variables is set to empty array, latter we will set the testimonials in it ***/
		$data['testimonials'] = array();
		/*** We set filter_data like below, $sort, $order, $page are assigned in above code, we can get from the URL paramaters or the config values. We pass this array and in model the SQL will be create as per this filter data   ***/
		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);
		/*** This is to get the total of number of testimonials as this is needed for the pagination ***/
		$testimonialcrud_total = $this->model_extension_module_testimonial->getTotalTestimonials();
		/*** This is to get filtered testimonials ***/
		$results = $this->model_extension_module_testimonial->getTestimonials($filter_data);
		/*** This is how we set data to the testimonials array, we can get many variables in the $results variables so we separate what is needed in template twig file and pass them to it ***/
		foreach ($results as $result) {
			$data['testimonials'][] = array(
				'testimonial_id' => $result['testimonial_id'],
				'name'        => $result['name'],
				'sort_order'  => $result['sort_order'],
				'edit'        => $this->url->link('extension/module/testimonialcrud/edit', 'user_token=' . $this->session->data['user_token'] . '&testimonial_id=' . $result['testimonial_id'] . $url, true),
				'delete'      => $this->url->link('extension/module/testimonialcrud/delete', 'user_token=' . $this->session->data['user_token'] . '&testimonial_id=' . $result['testimonial_id'] . $url, true)
			);
		}
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
		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array) $this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}
		$url = '';
		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}
		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		$data['sort_name'] = $this->url->link('extension/module/testimonialcrud', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
		$data['sort_sort_order'] = $this->url->link('extension/module/testimonialcrud', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_order' . $url, true);
		$url = '';
		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}
		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}
		/*** Pagination in Opencart they are self explainatory ***/
		$pagination = new Pagination();
		$pagination->total = $testimonialcrud_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/testimonialcrud', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);
		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($testimonialcrud_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($testimonialcrud_total - $this->config->get('config_limit_admin'))) ? $testimonialcrud_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $testimonialcrud_total, ceil($testimonialcrud_total / $this->config->get('config_limit_admin')));
		$data['sort'] = $sort;
		$data['order'] = $order;
		/*** Pass the header, column_left and footer to the testimonialcrud_list.twig template ***/
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		/*** Set the response output ***/
		$this->response->setOutput($this->load->view('extension/module/testimonialcrud_list', $data));
	}
	/*** getForm() - This method creates logic to create a form. When someone clicks the add button then it shows form with blank fields, if someone clicks the edit button then it shows form with data of that testimonial.
	 ***/
	protected function getForm()
	{
		$data['text_form'] = !isset($this->request->get['testimonial_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
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
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/testimonialcrud', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);
		/*** This is the code which separate the action of edit or add action, if the URL parameter contains testimonial_id then it is edit else it is add  ***/
		if (!isset($this->request->get['testimonial_id'])) {
			$data['action'] = $this->url->link('extension/module/testimonialcrud/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('extension/module/testimonialcrud/edit', 'user_token=' . $this->session->data['user_token'] . '&testimonial_id=' . $this->request->get['testimonial_id'] . $url, true);
		}
		$data['cancel'] = $this->url->link('extension/module/testimonialcrud', 'user_token=' . $this->session->data['user_token'] . $url, true);
		/*** This is the code which pulls the testimonial that we have to edit  ***/
		if (isset($this->request->get['testimonial_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$testimonialcrud_info = $this->model_extension_module_testimonial->getTestimonial($this->request->get['testimonial_id']);
		}
		$data['user_token'] = $this->session->data['user_token'];
		/*** These two line of codes are to pull all active language ***/
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		/*** This is the code to check testimonial_description field values, if it empty, or just clicked save button and request method is Post or set the available data in it while editing. We do this for all the fields. ***/
		if (isset($this->request->post['testimonial_description'])) {
			$data['testimonial_description'] = $this->request->post['testimonial_description'];
		} elseif (isset($this->request->get['testimonial_id'])) {
			$data['testimonial_description'] = $this->model_extension_module_testimonial->getTestimonialDescriptions($this->request->get['testimonial_id']);
		} else {
			$data['testimonial_description'] = array();
		}
		/*** This is code is to pull all active stores and set the selected stores. ***/
		$this->load->model('setting/store');
		$data['stores'] = array();
		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);
		$stores = $this->model_setting_store->getStores();
		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}
		if (isset($this->request->post['testimonial_store'])) {
			$data['testimonial_store'] = $this->request->post['testimonial_store'];
		} elseif (isset($this->request->get['testimonial_id'])) {
			$data['testimonial_store'] = $this->model_extension_module_testimonial->getTestimonialStores($this->request->get['testimonial_id']);
		} else {
			$data['testimonial_store'] = array(0);
		}
		/*** This is for image field ***/
		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($testimonialcrud_info)) {
			$data['image'] = $testimonialcrud_info['image'];
		} else {
			$data['image'] = '';
		}
		$this->load->model('tool/image');
		/*** This is for resizing of image to show thumbnails ***/
		if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
		} elseif (!empty($testimonialcrud_info) && is_file(DIR_IMAGE . $testimonialcrud_info['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($testimonialcrud_info['image'], 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		/*** This is for sort order field ***/
		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($testimonialcrud_info)) {
			$data['sort_order'] = $testimonialcrud_info['sort_order'];
		} else {
			$data['sort_order'] = 0;
		}
		/*** This is for status field ***/
		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($testimonialcrud_info)) {
			$data['status'] = $testimonialcrud_info['status'];
		} else {
			$data['status'] = true;
		}
		/*** This is for layout field ***/
		if (isset($this->request->post['testimonial_layout'])) {
			$data['testimonial_layout'] = $this->request->post['testimonial_layout'];
		} elseif (isset($this->request->get['testimonial_id'])) {
			$data['testimonial_layout'] = $this->model_extension_module_testimonial->getTestimonialLayouts($this->request->get['testimonial_id']);
		} else {
			$data['testimonial_layout'] = array();
		}
		/*** This is to get all layouts ***/
		$this->load->model('design/layout');
		$data['layouts'] = $this->model_design_layout->getLayouts();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/module/testimonialcrud_form', $data));
	}
	/***
	 * validateForm() - This method is to check whether the user has permission to edit or add the data from the form. In this method, we can validate any form field if needed.
	 ***/
	protected function validateForm()
	{
		/*** This is how we check if the user has permission to modify or not. ***/
		if (!$this->user->hasPermission('modify', 'extension/module/testimonialcrud')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		/*** This is to check if the testimonial_description name contiains more than 1 character and less than 255 characters ***/
		foreach ($this->request->post['testimonial_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}
		}
		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}
		return !$this->error;
	}
	/*** validateDelete() - This method is to check if the user has permission to delete or not ***/
	protected function validateDelete()
	{
		if (!$this->user->hasPermission('modify', 'extension/module/testimonialcrud')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
}