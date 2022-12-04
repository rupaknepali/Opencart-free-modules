Seo Module
==========
The first professional SEO extension for opencart 2 and 3

##### Table of content
1. [Installation & Update](#installation-and-update)
2. [API](#api)
	1. [Admin events](#admin-list-of-events-and-their-methods)
		* [common](#admin-common)
		* [localisation](#localisation)
		* [setting](#setting)
		* [catalog](#catalog)
	2. [Catalog events](#catalog-list-of-events-and-their-methods)
		* [common](#catalog-common)
		* [product](#product)
		* [information](#information)


Installation and Update
=======================
The easiest way is to use Shopunity.net extension to install the module.

###[Shopunity](https://shopunity.net) (recomended)
1. In Shopunty module search for SEO module and click install
2. After installation is complete click Admin
3. Click install inside the SEO module to complete the installation.

###Extension Installer (shopunity module required)
1. Go to Admin / Extensions / Extension Installer
2. Upload zip archive
3. Go to Admin / Extensions / Modules
4. Next to SEO module Click install
5. Edit SEO module
6. Click install to complete the installation process.

###FTP (shopunity module required)
1. Upload all the files from the folder upload
2. Go to Admin / Extensions / Modules
3. Next to SEO module Click install
4. Edit SEO module
5. Click install to complete the installation process.


###Update
You can update practically the same way as you have install the module. Only you will not need to click the final install inside the module since the module has already been installed. Though if the new version of the module locates missing parts, it will display an update button.

API
===
You can extend the SEO Module functionality by using the built-in API. The SEO module will look inside the ```admin/controller/extension/d_seo_module/``` and if your extension was found, will call specially named methods. The result will be used to modify the output using Opencart Event Methods.

####For the API to work you will need
1. Install your extension in Opencart (table `oc_extension`).
2. Add your extension in the list ```d_seo_extension_install``` in the Opencart table `oc_setting`.
3. Add method, that corresponds to the event you want to subscribe to.

Here is an example of adding a new item to the SEO Module Menu in admin panel:

```php
private $codename = 'd_seo_module_myfeature';
private $route = 'd_seo_module/d_seo_module_myfeature';

public function menu() {
	$_language = new Language();
	$_language->load($this->route);
	
	$url_token = '';
		
	if (isset($this->session->data['token'])) {
		$url_token .= 'token=' . $this->session->data['token'];
	}
		
	if (isset($this->session->data['user_token'])) {
		$url_token .= 'user_token=' . $this->session->data['user_token'];
	}
			
	$menu = array();
		
	if ($this->user->hasPermission('access', 'extension/module/' . $this->codename)) {
		$menu[] = array(
			'name'	   		=> $_language->get('heading_title_main'),
			'href'     		=> $this->url->link('extension/module/' . $this->codename, $url_token, true),
			'sort_order' 	=> 20,
			'children' 		=> array()
		);
	}

	return $menu;
}
```

---

##Admin list of events and their methods
> ####How to use it?
> This is how you should understand the following events:

> In Opencart 2.2.0 and below `admin/view/common/menu/after` is called after the `menu.tpl` or `menu.twig` is rendered to the screen.
> In Opencart 2.3.0 and above `admin/view/common/column_left/before` is called before the `column_left.tpl` or `column_left.twig` is rendered to the screen.

> To subsribe you will need to add the method `public function menu()` to your controller file `admin/controller/d_seo_module/d_seo_module_myfeature.php`.

> You will populate `$menu` with your menu item(s) `array('name' => ..., 'href' => ..., 'sort_order' => ..., 'children' => ...)` and `return $menu;`


###common
####1. admin/view/common/menu/after or admin/view/common/column_left/before
#####menu()
_Add an item(s) in admin to seo menu. You will add your menu item(s) and return the menu array._

* **method:** `public function menu()`
* **parameters:** `$menu[] = array('name' => ..., 'href' => ..., 'sort_order' => ..., 'children' => ...);`
* **return:** `$menu = array(...)`

Example
```php
private $codename = 'd_seo_module_myfeature';

public function menu() {
	$_language = new Language();
	$_language->load($this->route);
	
	$url_token = '';
		
	if (isset($this->session->data['token'])) {
		$url_token .= 'token=' . $this->session->data['token'];
	}
		
	if (isset($this->session->data['user_token'])) {
		$url_token .= 'user_token=' . $this->session->data['user_token'];
	}
		
	$menu = array();
		
	if ($this->user->hasPermission('access', 'extension/module/' . $this->codename)) {
		$menu[] = array(
			'name'	   		=> $_language->get('heading_title_main'),
			'href'     		=> $this->url->link('extension/module/' . $this->codename, $url_token, true),
			'sort_order' 	=> 20,
			'children' 		=> array()
		);
	}

	return $menu;
}
```
####2. admin/view/common/dashboard/after
#####dashboard()
_Add dashboard module item(s) in admin dashbord for Opencart 2.2.0 and under. You will add your dashboard module item(s) and return the dashboards array._

* **method:** `public function dashboard()`
* **parameters:** `$dashboards[] = array('html' => ..., 'width' => ..., 'sort_order' => ...);`
* **return:** `$dashboards = array(...)`

Example
```php
private $codename = 'd_seo_module_myfeature';

public function dashboard() {		
	$dashboards = array();
		
	if ($this->user->hasPermission('access', 'extension/module/' . $this->codename)) {
		$dashboards[] = array(
			'html' 			=> $this->load->controller('extension/dashboard/d_seo_module_myfeature'),
			'width' 		=> 12,
			'sort_order' 	=> 50
		);
	}

	return $dashboards;
}
```

###localisation
####1. admin/model/localisation/language/addLanguage/after
#####language_add_language()
_After new language has been added, you can preform your own actions like add a new column to a table._

* **method:** ```public function language_add_language($data)```
* **parameters:** ```$data = array('language_id' => ..., ...);```
* **output:** `none`

####2. admin/model/localisation/language/editLanguage/after
#####language_edit_language()
_Called when language has been edited. Similar to `language_add_language($data)`._

* **method:** ```public function language_edit_language($data)```
* **parameters:** ```$data = array('language_id' => ..., ...);```
* **output:** `none`

####3. admin/model/localisation/language/deleteLanguage/after
#####language_delete_language()
_Called when language has been deleted. Similar to `language_add_language($data)`._

* **method:** ```public function language_delete_language($data)```
* **parameters:** ```$data = array('language_id' => ...);```
* **output:** `none`

---

###setting
####1. admin/view/setting/setting/after
#####setting_tab_general()
_Modify the output of store setting form and new store create form. You simply return an HTML of the input or anything else that you want to place into the form and tab._

* **method:** `public function setting_tab_general()`
* **parameters:** `none`
* **output:** `html`

Example
**admin/controller/extension/d_seo_module/d_seo_module_myfeature.php**
```php
private $codename = 'd_seo_module_myfeature';
private $route = 'extension/d_seo_module/d_seo_module_myfeature';

public function setting_tab_general() {	
	$_language = new Language();
	$_language->load($this->route);
	
	//get language data
	$data['entry_myfeature'] = $_language->get('entry_myfeature');
	$data['help_myfeature'] = $_language->get('help_myfeature');
	
	//get validate error data
	$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();
		
	//add config_myfeature value to the $data for settings general tab
	if (isset($this->request->post['mydata'])) {
		$data['mydata'] = $this->request->post['mydata'];
	} else {
		$data['mydata'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getMyData();
	}
	
	//render the $data with the setting_tab_general.tpl or setting_tab_general.twig. the HTML will be returned and added to the final HTML inside the Setting General tab.						
	return $this->load->view($this->route . '/setting_tab_general', $data);
}
```

#####setting_tab_general_language()
_You can add html to the language tabs._

* **method:** `public function setting_tab_general_language()`
* **parameters:** `none`
* **output:** `$html_tab_general_language = array(...)`

Example
**admin/controller/extension/d_seo_module/d_seo_module_myfeature.php**
```php
private $codename = 'd_seo_module_myfeature';
private $route = 'extension/d_seo_module/d_seo_module_myfeature';

public function setting_tab_general_language() {
	//load models and language files
	$_language = new Language();
	$_language->load($this->route);
	
	$this->load->model($this->route);
	
	//get languages
	$languages = $this->{'model_extension_d_seo_module_d_seo_module_myfeature'}->getLanguages();
	
	//get language data
	$data['entry_myfeature'] = $_language->get('entry_myfeature');
	$data['help_myfeature'] = $_language->get('help_myfeature');
	
	//get validate error data
	$data['error'] = ($this->config->get($this->codename . '_error')) ? $this->config->get($this->codename . '_error') : array();

	//add config_myfeature value to the $data for settings general tab
	if (isset($this->request->post['mydata'])) {
		$data['mydata'] = $this->request->post['mydata'];
	} else {
		$data['mydata'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getMyData();
	}

	//render the $data with the setting_tab_general_language.tpl or setting_tab_general_language.twig. the HTML will be returned and added to the final HTML inside the Setting General tab.
	$html_tab_general_language = array();
		
	foreach ($languages as $language) {
		$data['language_id'] = $language['language_id'];
		
		$html_tab_general_language[$data['language_id']] = $this->load->view($this->route . '/setting_tab_general_language', $data);
	}
				
	return $html_tab_general_language;
}
```

#####setting_tab_store()
* **method:** `public function setting_tab_store()`
* **parameters:** `none`
* **output:** `html`

#####setting_tab_local()
* **method:** `public function setting_tab_local()`
* **parameters:** `none`
* **output:** `html`

#####setting_tab_option()
* **method:** `public function setting_tab_option()`
* **parameters:** `none`
* **output:** `html`

#####setting_style()
_This is a style input. You can use this for adding CSS to the form. Yet we recommend using he default `$this->document->addStyle($href, $rel = 'stylesheet', $media = 'screen')`;_

* **method:** `public function setting_style()`
* **parameters:** `none`
* **output:** `html`

#####setting_script()
_Add js scripts to the form._

* **method:** `public function setting_script()`
* **parameters:** `none`
* **output:** `html`

####2. admin/controller/setting/setting/validate
#####setting_validate()
_After Opencart validate actions has been completed, you can preform your own actions using an array $error._

* **method:** ```public function setting_validate($error)```
* **parameters:** ```$error = array(...);```
* **output:** `$error = array(...)`

Example:
**admin/controller/extension/d_seo_module/d_seo_module_myfeature.php**
```php
private $codename = 'd_seo_module_myfeature';
private $route = 'extension/d_seo_module/d_seo_module_myfeature';

public function setting_validate($error) {
	if (isset($this->request->post['mydata'])) {		
		$_language = new Language();
		$_language->load($this->route);
			
		if ((utf8_strlen($this->request->post['mydata']) < 3) || (utf8_strlen($this->request->post['mydata']) > 255)) {
			$error['mydata'] = $_language->get('error_mydata');
		}
		
		$this->config->set($this->codename . '_error', $error);
	}
				
	return $error;
}
```

####3. admin/controller/setting/setting/index
#####setting_edit_setting()
_Before setting has been edited, you can preform your own actions using an array $data._

* **method:** ```public function setting_edit_setting($data)```
* **parameters:** ```$data = array('store_id' => ..., ...);```
* **output:** `none`

####4. admin/view/setting/store_form/after
#####store_form_tab_general()
_Modify the output of store form and new store create form. You simply return an HTML of the input or anything else that you want to place into the form and tab._

* **method:** `public function store_form_tab_general()`
* **parameters:** `none`
* **output:** `html`

#####store_form_tab_general_language()
_You can add html to the language tabs._

* **method:** `public function store_form_tab_general_language()`
* **parameters:** `none`
* **output:** `$html_tab_general_language = array(...)`

#####store_form_tab_store()
* **method:** `public function store_form_tab_store()`
* **parameters:** `none`
* **output:** `html`

#####store_form_tab_local()
* **method:** `public function store_form_tab_local()`
* **parameters:** `none`
* **output:** `html`

#####store_form_tab_option()
* **method:** `public function store_form_tab_option()`
* **parameters:** `none`
* **output:** `html`

#####store_form_style()
_This is a style input. You can use this for adding CSS to the form. Yet we recommend using he default `$this->document->addStyle($href, $rel = 'stylesheet', $media = 'screen')`;_

* **method:** `public function store_form_style()`
* **parameters:** `none`
* **output:** `html`

#####store_form_script()
_Add js scripts to the form._

* **method:** `public function store_form_script()`
* **parameters:** `none`
* **output:** `html`

####5. admin/controller/setting/store_form/validateForm
#####store_validate_form()
_After Opencart validate actions has been completed, you can preform your own actions using an array $error._

* **method:** ```public function store_validate_form($error)```
* **parameters:** ```$error = array(...);```
* **output:** `$error = array(...)`

####6. admin/model/setting/store/addStore/after
#####store_add_store()
_Before new store has been added, you can preform your own actions using an array $data._

* **method:** ```public function store_add_store($data)```
* **parameters:** ```$data = array('store_id' => ..., ...);```
* **output:** `none`

####7. admin/model/setting/store/editStore/after
#####store_edit_store()
_Before store settings has been edited, you can preform your own actions using an array $data._

* **method:** ```public function store_edit_store($data)```
* **parameters:** ```$data = array('store_id' => ..., ...);```
* **output:** `none`

####8. admin/model/setting/store/deleteStore/after
#####store_delete_store()
_Before store settings has been deleted, you can preform your own actions using an array $data._

* **method:** ```public function store_delete_store($data)```
* **parameters:** ```$data = array('store_id' => ...);```
* **output:** `none`

---

###catalog
####1. admin/view/catalog/category_form/after
#####category_form_tab_general()
_Modify the HTML output of category form. You simply return an HTML of the input or anything else that you want to place into the form based on the tab._

* **method:** `public function category_form_tab_general()`
* **parameters:** `none`
* **output:** `html`

#####category_form_tab_general_language()
_You can add html to the language tabs._

* **method:** `public function category_form_tab_general_language()`
* **parameters:** `none`
* **output:** `$html_tab_general_language = array(...)`

#####category_form_tab_data()
* **method:** `public function category_form_tab_data()`
* **parameters:** `none`
* **output:** `html`

#####category_form_style()
_This is a style input. You can use this for adding CSS to the form. Yet we recomend using he default `$this->document->addStyle($href, $rel = 'stylesheet', $media = 'screen')`;_

* **method:** `public function category_form_style()`
* **parameters:** `none`
* **output:** `html`

#####category_form_script()
_Add js scripts to the form._

* **method:** `public function category_form_script()`
* **parameters:** `none`
* **output:** `html`

####2. admin/controller/catalog/category/validateForm
#####category_validate_form()
_After Opencart validate actions has been completed, you can preform your own actions using an array $error._

* **method:** ```public function category_validate_form($error)```
* **parameters:** ```$error = array(...);```
* **output:** `$error = array(...)`

####3. admin/model/catalog/category/addCategory/after
#####category_add_category()
_After new category has been added, you can preform your own actions using an array $data._

* **method:** ```public function category_add_category($data)```
* **parameters:** ```$data = array('category_id' => ..., ...);```
* **output:** `none`

####4. admin/model/catalog/category/editCategory/after
#####category_edit_category()
_After category has been edited, you can preform your own actions using an array $data._

* **method:** ```public function category_edit_category($data)```
* **parameters:** ```$data = array('category_id' => ..., ...);```
* **output:** `none`

####5. admin/model/catalog/category/deleteCategory/after
#####category_delete_category()
_After category has been deleted, you can preform your own actions using an array $data._

* **method:** ```public function category_delete_category($data)```
* **parameters:** ```$data = array('category_id' => ...);```
* **output:** `none`

####6. admin/view/catalog/product_form/after
#####product_form_tab_general()
_Modify the HTML output of product form. You simply return an HTML of the input or anything else that you want to place into the form based on the tab._

* **method:** `public function product_form_tab_general()`
* **parameters:** `none`
* **output:** `html`

#####product_form_tab_general_language()
_You can add html to the language tabs._

* **method:** `public function product_form_tab_general_language()`
* **parameters:** `none`
* **output:** `$html_tab_general_language = array(...)`

#####product_form_tab_data()
* **method:** `public function product_form_tab_data()`
* **parameters:** `none`
* **output:** `html`

#####product_form_tab_links()
* **method:** `public function product_form_tab_links()`
* **parameters:** `none`
* **output:** `html`

#####product_form_style()
_This is a style input. You can use this for adding CSS to the form. We recommended using the default `$this->document->addStyle($href, $rel = 'stylesheet', $media = 'screen')`;_

* **method:** `public function product_form_style()`
* **parameters:** `none`
* **output:** `html`

#####product_form_script()
_Add js scripts to the form._

* **method:** `public function product_form_script()`
* **parameters:** `none`
* **output:** `html`

####7. admin/controller/catalog/product/validateForm
#####product_validate_form()
_After Opencart validate actions has been completed, you can preform your own actions using an array $error._

* **method:** ```public function product_validate_form($error)```
* **parameters:** ```$error = array(...);```
* **output:** `$error = array(...)`

####8. admin/model/catalog/product/addProduct/after
#####product_add_product()
_After new product has been added, you can preform your own actions using an array $data._

* **method:** `public function product_add_product($data)`
* **parameters:** `$data = array('product_id' => ..., ...)`
* **output:** `none`

####9. model/catalog/product/editProduct/after
#####product_edit_product()
_After product has been edited, you can preform your own actions using an array $data._

* **method:** `public function product_edit_product($data)`
* **parameters:** `$data = array('product_id' => ..., ...)`
* **output:** `none`

####10. model/catalog/product/deleteProduct/after
#####product_delete_product()
_After product has been deleted, you can preform your own actions using an array $data._

* **method:** `public function product_delete_product($data)`
* **parameters:** `$data = array('product_id' => ...)`
* **output:** `none`

####11. admin/view/catalog/manufacturer_form/after
#####manufacturer_form_tab_general()
_Modify the HTML output of manufacturer form. You simply return an HTML of the input or anything else that you want to place into the form based on the tab._

* **method:** `public function manufacturer_form_tab_general()`
* **parameters:** `none`
* **output:** `html`

#####manufacturer_form_tab_general_language()
_You can add html to the language tabs._

* **method:** `public function manufacturer_form_tab_general_language()`
* **parameters:** `none`
* **output:** `$html_tab_general_language = array(...)`

#####manufacturer_form_tab_data()
* **method:** `public function manufacturer_form_tab_data()`
* **parameters:** `none`
* **output:** `html`

#####manufacturer_form_style()
_This is a style input. You can use this for adding CSS to the form. We recommended using the default `$this->document->addStyle($href, $rel = 'stylesheet', $media = 'screen')`;_

* **method:** `public function manufacturer_form_style()`
* **parameters:** `none`
* **output:** `html`

#####manufacturer_form_script()
_Add js scripts to the form._

* **method:** `public function manufacturer_form_script()`
* **parameters:** `none`
* **output:** `html`

####12. admin/controller/catalog/manufacturer/validateForm
#####manufacturer_validate_form()
_After Opencart validate actions has been completed, you can preform your own actions using an array $error._

* **method:** ```public function manufacturer_validate_form($error)```
* **parameters:** ```$error = array(...);```
* **output:** `$error = array(...)`

####13. admin/model/catalog/manufacturer/addManufacturer/after
#####manufacturer_add_manufacturer()
_After a new manufacturer has been added, you can preform your own actions using an array $data._

* **method:** `public function manufacturer_add_manufacturer($data)`
* **parameters:** `$data = array('manufacturer_id' => ..., ...)`
* **output:** `none`

####14. admin/model/catalog/manufacturer/editManufacturer/after
#####manufacturer_edit_manufacturer()
_After a new manufacturer has been added, you can preform your own actions using an array $data._

* **method:** `public function manufacturer_edit_manufacturer($data)`
* **parameters:** `$data = array('manufacturer_id' => ..., ...)`
* **output:** `none`

####15. admin/model/catalog/manufacturer/deleteManufacturer/after
#####manufacturer_delete_manufacturer()
_After a new manufacturer has been deleted, you can preform your own actions using an array $data._

* **method:** `public function manufacturer_delete_manufacturer($data)`
* **parameters:** `$data = array('manufacturer_id' => ...)`
* **output:** `none`

####16. admin/view/catalog/information_form/after
#####information_form_tab_general()
_Modify the HTML output of information form. You simply return an HTML of the input or anything else that you want to place into the form based on the tab._

* **method:** `public function information_form_tab_general()`
* **parameters:** `none`
* **output:** `html`

#####information_form_tab_general_language()
_You can add html to a language tabs._

* **method:** `public function information_form_tab_general_language()`
* **parameters:** `none`
* **output:** `$html_tab_general_language = array(...)`

#####information_form_tab_data()
* **method:** `public function information_form_tab_data()`
* **parameters:** `none`
* **output:** `html`

#####information_form_style()
_This is a style input. You can use this for adding CSS to the form. We recommended using the default `$this->document->addStyle($href, $rel = 'stylesheet', $media = 'screen')`;_

* **method:** `public function information_form_style()`
* **parameters:** `none`
* **output:** `html`

#####information_form_script()
_Add js scripts to the form._

* **method:** `public function information_form_script()`
* **parameters:** `none`
* **output:** `html`

####17. admin/controller/catalog/information/validateForm
#####information_validate_form()
_After Opencart validate actions has been completed, you can preform your own actions using an array $error._

* **method:** ```public function information_validate_form($error)```
* **parameters:** ```$error = array(...);```
* **output:** `$error = array(...)`

####18. admin/model/catalog/information/addInformation/after
#####information_add_information()
_After a information has been edited, you can preform your own actions using an array $data._

* **method:** `public function information_add_information($data)`
* **parameters:** `$data = array('information_id' => ..., ...)`
* **output:** `none`

####19. admin/model/catalog/information/editInformation/after
#####information_edit_information()
_After a information has been edited, you can preform your own actions using an array $data._

* **method:** `public function information_edit_information($data)`
* **parameters:** `$data = array('information_id' => ..., ...)`
* **output:** `none`

####20. admin/model/catalog/information/deleteInformation/after
#####information_delete_information()
_After a information has been deleted, you can preform your own actions using an array $data._

* **method:** `public function information_delete_information($data)`
* **parameters:** `$data = array('information_id' => ...)`
* **output:** `none`

---

##Catalog list of events and their methods
> ####How to use it?
> For the frontend you have two basic events:
> - `before` (before event - here you modify the data array)
> - `after` (after event - here you modify the HTML).

1. `catalog/view/common/home/before` is called before the `home.tpl` or `home.twig` is rendered to the screen.
2. To subsribe you will need to add the method `public function home_before($data)` to your controller file `catalog/controller/extension/d_seo_module/d_seo_module_myfeature.php` with a parameter `$data`
3.  You will modify `$data` accordingly and `return $data;`

###catalog common
####1. catalog/view/common/header/before
#####header_before()
_Modify the data that will be rendered to the `header.tpl` or `header.twig`._

* **method:** `public function header_before($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

Example
**catalog/controller/extension/d_seo_module/d_seo_module_myfeature.php**
```php
private $codename = 'd_seo_module_myfeature';
private $route = 'extension/d_seo_module/d_seo_module_myfeature';

public function header_before($data) {
	//load models and language files
	$_language = new Language();
	$_language->load($this->route);
	
	$this->load->model($this->route);
	
	//get language data
	$data['myfeature'] = $_language->get('myfeature');
	
	return $data;
}
```

####2. catalog/view/common/header/after
#####header_after()
_Modify the HTML of the `header.tpl` or `header.twig` before browser renders it._

* **method:** `public function header_after($html)`
* **parameters:** `(string) $html`
* **output:** `(string) $html`

Example
**catalog/controller/extension/d_seo_module/d_seo_module_myfeature.php**
```php
private $codename = 'd_seo_module_myfeature';
private $route = 'extension/d_seo_module/d_seo_module_myfeature';

public function header_after($html) {
	//load models and language files
	$_language = new Language();
	$_language->load($this->route);
	
	$this->load->model($this->route);
	
	//get language data
	$myfeature = $_language->get('myfeature');
	
	if (file_exists(DIR_SYSTEM . 'library/d_simple_html_dom.php')) {
		$html_dom = new d_simple_html_dom();
		$html_dom->load((string)$html, $lowercase = true, $stripRN = false, $defaultBRText = DEFAULT_BR_TEXT);
		
		foreach ($html_dom->find('#myfeature') as $element) {
			$element->innertext = $myfeature;
		}
				
		return (string)$html_dom;
	} return $html;
}
```

####3. catalog/view/common/footer/before
#####footer_before()
_Modify the data that will be rendered to the `footer.tpl` or `footer.twig`._

* **method:** `public function footer_before($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####4. catalog/view/common/footer/after
#####footer_after()
_Modify the HTML of the `footer.tpl` or `footer.twig` before browser renders it._

* **method:** `public function footer_after($html)`
* **parameters:** `(string) $html`
* **output:** `(string) $html`

####5. catalog/view/common/home/before
#####home_before()
_Modify the data that will be rendered to the `home.tpl` or `home.twig`._

* **method:** `public function home_before($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####6. catalog/view/common/home/after
#####home_after()
_Modify the HTML of the `home.tpl` or `home.twig` before browser renders it._

* **method:** `public function home_after($html)`
* **parameters:** `(string) $html`
* **output:** `(string) $html`

####7. catalog/controller/common/language/language
#####language_language()
_When switching the language you can preform your own actions._

* **method:** `public function language_language()`
* **parameters:** `none`
* **output:** `none`

Example
**admin/controller/extension/d_seo_module/d_seo_module_myfeature.php**
```php
private $codename = 'd_seo_module_myfeature';
private $route = 'extension/d_seo_module/d_seo_module_myfeature';

public function language_language() {
	$this->load->model($this->route);
		
	if (isset($this->request->post['redirect'])) {
		$this->request->post['redirect'] = $this->{'model_extension_d_seo_module_' . $this->codename}->getURLForLanguage($this->request->post['redirect'], $this->session->data['language']);
	}
}
```

####8. catalog/controller/common/seo_url/index or catalog/controller/startup/seo_url/index
#####seo_url()
_Here you can get route of your page by seo keyword or preform your own actions until the route has not yet been determined._

* **method:** `public function seo_url()`
* **parameters:** `none`
* **output:** `none`

#####seo_url_check()
_Here you can preform your own actions after route of the page has been already determined._

* **method:** `public function seo_url_check()`
* **parameters:** `none`
* **output:** `html`

---

###product
####1. catalog/view/product/category/before
#####category_before()
_Modify the data that will be rendered to the `category.tpl` or `category.twig`._

* **method:** `public function category_before($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####2. catalog/view/product/category/after
#####category_after()
_Modify the HTML of the `category.tpl` or `category.twig` before browser renders it._

* **method:** `public function category_after($html)`
* **parameters:** `(string) $html`
* **output:** `(string) $html`

####3. catalog/model/catalog/category/getCategory/after
#####category_get_category()
_After category data has been returned, you can preform your own actions using an array $data._

* **method:** `public function category_get_category($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####4. catalog/model/catalog/category/getCategories/after
#####category_get_categories()
_After categories data has been returned, you can preform your own actions using an array $data._

* **method:** `public function category_get_categories($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####5. catalog/view/product/product/before
#####product_before()
_Modify the data that will be rendered to the `product.tpl` or `product.twig`._

* **method:** `public function product_before($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####6. catalog/view/product/product/after
#####product_after()
_Modify the HTML of the `product.tpl` or `product.wig` before browser renders it._

* **method:** `public function product_after($html)`
* **parameters:** `(string) $html`
* **output:** `(string) $html`

####7. catalog/model/catalog/product/getProduct/after
#####product_get_product()
_After product data has been returned, you can preform your own actions using an array $data._

* **method:** `public function product_get_product($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####8. catalog/model/catalog/product/getProducts/after
#####product_get_products()
_After products data has been returned, you can preform your own actions using an array $data._

* **method:** `public function product_get_products($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####9. catalog/view/product/manufacturer_list/before
#####manufacturer_list_before()
_Modify the data that will be rendered to the `manufacturer_list.tpl` or `manufacturer_list.twig`._

* **method:** `public function manufacturer_list_data($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####10. catalog/view/product/manufacturer_list/after
#####manufacturer_list_after()
_Modify the HTML of the `manufacturer_list.tpl` or `manufacturer_list.twig` before browser renders it._

* **method:** `public function manufacturer_list_after($html)`
* **parameters:** `(string) $html`
* **output:** `(string) $html`

####11. catalog/view/product/manufacturer_info/before
#####manufacturer_info_before()
_Modify the data that will be rendered to the `manufacturer_info.tpl` or `manufacturer_info.twig`._

* **method:** `public function manufacturer_info_before($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####12. catalog/view/product/manufacturer_info/after
#####manufacturer_info_after()
_Modify the HTML of the `manufacturer_info.tpl` or `manufacturer_info.twig` before browser renders it._

* **method:** `public function manufacturer_info_after($html)`
* **parameters:** `(string) $html`
* **output:** `(string) $html`

####13. catalog/model/catalog/manufacturer/getManufacturer/after
#####manufacturer_get_manufacturer()
_After manufacturer data has been returned, you can preform your own actions using an array $data._

* **method:** `public function manufacturer_get_manufacturer($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####14. catalog/model/catalog/manufacturer/getManufacturers/after
#####manufacturer_get_manufacturers()
_After manufacturers data has been returned, you can preform your own actions using an array $data._

* **method:** `public function manufacturer_get_manufacturers($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####15. catalog/view/product/search/before
#####search_before()
_Modify the data that will be rendered to the `search.tpl` or `search.twig`._

* **method:** `public function search_before($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####16. catalog/view/product/search/after
#####search_after()
_Modify the HTML of the `search.tpl` or `search.twig` before browser renders it._

* **method:** `public function search_after($html)`
* **parameters:** `(string) $html`
* **output:** `(string) $html`

####17. catalog/view/product/special/before
#####special_before()
_Modify the data that will be rendered to the `special.tpl` or `special.twig`._

* **method:** `public function special_before($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####18. catalog/view/product/special/after
#####special_after()
_Modify the HTML of the `special.tpl` or `special.twig` before browser renders it._

* **method:** `public function special_after($html)`
* **parameters:** `(string) $html`
* **output:** `(string) $html`

---

###information
####1. catalog/view/information/information/before
#####information_before()
_Modify the data that will be rendered to the `information.tpl` or `information.twig`._

* **method:** `public function information_before($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####2. catalog/view/information/information/after
#####information_after()
_Modify the HTML of the `information.tpl` or `information.twig` before browser renders it._

* **method:** `public function information_after($html)`
* **parameters:** `(string) $html`
* **output:** `(string) $html`

####3. catalog/model/catalog/information/getInformation/after
#####information_get_information()
_After information data has been returned, you can preform your own actions using an array $data._

* **method:** `public function information_get_information($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

####4. catalog/model/catalog/information/getInformations/after
#####information_get_informations()
_After informations data has been returned, you can preform your own actions using an array $data._

* **method:** `public function information_get_informations($data)`
* **parameters:** `$data = array(...)`
* **output:** `$data = array(...)`

---
