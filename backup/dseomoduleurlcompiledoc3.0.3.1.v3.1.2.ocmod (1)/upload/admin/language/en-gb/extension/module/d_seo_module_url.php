<?php
// Heading
$_['heading_title']       						= '<span style="color:#449DD0; font-weight:bold">SEO Module URL</span><span style="font-size:0.9em; color:#999"> by <a href="http://www.opencart.com/index.php?route=extension/extension&filter_username=Dreamvention" style="font-size:1em; color:#999" target="_blank">Dreamvention</a></span>'; 
$_['heading_title_main']  						= 'SEO Module URL';

// Text
$_['text_edit']            						= 'Edit SEO Module URL';
$_['text_install']              				= 'Install SEO Module URL';
$_['text_modules']         						= 'Modules';
$_['text_settings']       						= 'Settings';
$_['text_generator']          					= 'Generator';
$_['text_url_keywords']          				= 'URL Keywords';
$_['text_redirects']          					= 'Redirects';
$_['text_export_import']          				= 'Export & Import';
$_['text_instructions']   						= 'Instructions';
$_['text_basic_settings'] 						= 'Basic Settings';
$_['text_multi_language_sub_directories'] 		= 'Multi Language Subdirectories';
$_['text_category'] 							= 'Category';
$_['text_product'] 								= 'Product';
$_['text_manufacturer']							= 'Manufacturer';
$_['text_information'] 							= 'Information';
$_['text_search']								= 'Search';
$_['text_special']								= 'Special';
$_['text_custom_page'] 							= 'Custom Page';
$_['text_export'] 								= 'Export';
$_['text_import'] 								= 'Import';
$_['text_setup']								= 'Install SEO Module URL now';
$_['text_full_setup']							= 'Full';
$_['text_custom_setup']							= 'Custom';
$_['text_all_stores']     						= 'All Stores';
$_['text_all_languages']  						= 'All Languages';
$_['text_yes'] 									= 'Yes';
$_['text_no'] 									= 'No';
$_['text_enabled']          					= 'Enabled';
$_['text_disabled']          					= 'Disabled';
$_['text_transform_none']          				= 'No Transform';
$_['text_transform_lower_to_upper']     		= 'Lower To Upper';
$_['text_transform_upper_to_lower']    			= 'Upper To Lower';
$_['text_insert_target_keyword'] 				= 'Insert Target Keyword';
$_['text_keyword_number'] 						= 'Number of keyword';
$_['text_url_keyword']          				= 'URL Keyword';
$_['text_redirect']          					= 'Redirect';
$_['text_add_url_keyword']   					= 'Add URL Keyword';
$_['text_add_redirect']   						= 'Add Redirect';
$_['text_seo_module']   						= 'SEO Module';
$_['text_url']   								= 'URL';
$_['text_generate_confirm']          			= 'Are you sure you want to generate data for this field?';
$_['text_clear_confirm']          				= 'Are you sure you want to clear all the data for this field. The data will be permanently deleted, this can not be undone?';
$_['text_create_default_url_keywords_confirm']	= 'Creation default URL Keywords for Custom Pages will delete all old URL Keywords for Custom Pages. Are you sure you want to create default URL Keywords for Custom Pages?';
$_['text_delete_url_keywords_confirm']   		= 'Are you sure you want to delete selected URL Keywords?';
$_['text_delete_redirects_confirm']   			= 'Are you sure you want to delete selected Redirects?';
$_['text_uninstall_confirm']          			= 'After uninstalling of SEO Module URL will delete all additional fields in the product, category, manufacturer and information that have been added after installation. Are you sure you want to uninstall SEO Module URL?';
$_['text_powered_by']               			= 'Tested with <a href="https://shopunity.net/">Shopunity.net</a><br/>Find more extensions at <a href="https://dreamvention.ee/">Dreamvention.com</a>';
$_['text_instructions_full'] 					= '
<div class="row">
	<div class="col-sm-2">
		<ul class="nav nav-pills nav-stacked">
			<li class="active"><a href="#vtab_instruction_install"  data-toggle="tab">Installation and Updating</a></li>
			<li><a href="#vtab_instruction_setting" data-toggle="tab">Settings</a></li>
			<li><a href="#vtab_instruction_generator" data-toggle="tab">Generator</a></li>
			<li><a href="#vtab_instruction_custom_page" data-toggle="tab">Custom Pages</a></li>
			<li><a href="#vtab_instruction_redirect" data-toggle="tab">Redirects</a></li>
			<li><a href="#vtab_instruction_export_import" data-toggle="tab">Export & Import</a></li>
			<li><a href="#vtab_instruction_dashboard" data-toggle="tab">Dashboard</a></li>
		</ul>
	</div>
	<div class="col-sm-10">
		<div class="tab-content">
			<div id="vtab_instruction_install" class="tab-pane active">
				<div class="tab-body">
					<h3>Installation</h3>
					<ol>
						<li>Unzip distribution file.</li>
						<li>Upload everything from the folder <code>UPLOAD</code> into the root folder of you shop.</li>
						<li>Goto admin of your shop and navigate to extensions -> modules -> SEO Module URL.</li>
						<li>Click install button.</li>
					</ol>
					<div class="bs-callout bs-callout-info">
						<h4>Note!</h4>
						<p>Our installation process requires you to have access to the internet because we will install all the required dependencies before we install the module. Install SEO Module URL is possible only after installing SEO Module.</p>
					</div>
					<div class="bs-callout bs-callout-warning">
						<h4>Warning!</h4>
						<p>If you get an error on this step, be sure to make you <code>DOWNLOAD</code> folder (usually in system folder of you shop) writable.</p>
					</div>
					<h3>Updating</h3>
					<ol>
						<li>Unzip distribution file.</li>
						<li>Upload everything from the folder <code>UPLOAD</code> into the root folder of you shop.</li>
						<li>Click overwrite for all files.</li>
					</ol>
					<div class="bs-callout bs-callout-info">
						<h4>Note!</h4>
						<p>Although we follow strict standards that do not allow feature updates to cause a full reinstall of the module, still it may happen that major releases require you to uninstall/install the module again before new feature take place.</p>
					</div>
					<div class="bs-callout bs-callout-warning">
						<h4>Warning!</h4>
						<p>If you have made custom corrections to the code, your code will be rewritten and lost once you update the module.</p>
					</div>
				</div>
			</div>
			<div id="vtab_instruction_setting" class="tab-pane">
				<div class="tab-body">
					<h3>Basic Settings</h3>
					<p>Here you can:</p>
					<ol>
						<li>Enable/Disable SEO Module URL on the pages of your shop by click Status.</li>
						<li>Specify in the field "List Items Per Page" the maximum number of items in the list on the tabs "Custom Pages" and "Redirects".</li>
						<li>Uninstall SEO Module URL.</li>
					</ol>
					<div class="bs-callout bs-callout-info">
						<h4>Note!</h4>
						<p>After installing of SEO Module URL in the admin panel of Opencart in the category, product, manufacturer, information and store settings on the tab "General" will appear multilingual field "URL Keyword". Also, in the product on the tab "Links" will appear field "Category Path", which allows you to specify the unique path to the product in product links and breadcrumbs.</p>
					</div>
					<div class="bs-callout bs-callout-warning">
						<h4>Warning!</h4>
						<p>After uninstalling of SEO Module URL will delete all additional fields in the product, category, manufacturer, information and store settings that have been added after installation.</p>
					</div>
					<div class="bs-callout bs-callout-warning">
						<h4>Warning!</h4>
						<p>Correct operation of SEO Module URL is possible, if option "Use SEO URLs" in the Opencart Settings is enabled.</p>
					</div>
					<h3>Redirects</h3>
					<p>Here you can:</p>
					<ol>
						<li>Enable/Disable Redirects, specified on the tab "Redirects", on the pages of your shop by click Status.</li>
						<li>Set the <strong>Exception Data</strong> - comma separated list of exception parameters, that should remain in URL on the page after redirect. Everything else will be deleted from URL after redirect.</li>
					</ol>
					<h3>Category</h3>
					<p>Here you can set the following fields:</p>
					<ol>
						<li><strong>Unique URL</strong> increasess the uniqueness URL, by redirecting and removing all unnecessary data from URL on the category page.</li>
						<li><strong>Exception Data</strong> is comma separated list of exception parameters, that should remain in URL on the category page when set Unique URL. Everything else will redirect to the Unique URL.</li>
						<li><strong>Short URL</strong> removes the categories and subcategories in links to the categories.</li>
					</ol>
					<h3>Product</h3>
					<p>Here you can set the following fields:</p>
					<ol>
						<li><strong>Unique URL</strong> increasess the uniqueness URL, by redirecting and removing all unnecessary data from URL on the product page.</li>
						<li><strong>Exception Data</strong> is comma separated list of exception parameters, that should remain in URL on product the page when set Unique URL. Everything else will redirect to the Unique URL.</li>
						<li><strong>Short URL</strong> removes the categories and subcategories in links to the products.</li>
					</ol>
					<h3>Manufacturer</h3>
					<p>Here you can set the following fields:</p>
					<ol>
						<li><strong>Unique URL</strong> increasess the uniqueness URL, by redirecting and removing all unnecessary data from URL on the manufacturer page.</li>
						<li><strong>Exception Data</strong> is comma separated list of exception parameters, that should remain in URL on the manufacturer page when set Unique URL. Everything else will redirect to the Unique URL.</li>
					</ol>
					<h3>Search</h3>
					<p>Here you can set the following fields:</p>
					<ol>
						<li><strong>Unique URL</strong> increasess the uniqueness URL, by redirecting and removing all unnecessary data from URL on the search page.</li>
						<li><strong>Exception Data</strong> is comma separated list of exception parameters, that should remain in URL on the search page when set Unique URL. Everything else will redirect to the Unique URL.</li>
					</ol>
					<h3>Special</h3>
					<p>Here you can set the following fields:</p>
					<ol>
						<li><strong>Unique URL</strong> increasess the uniqueness URL, by redirecting and removing all unnecessary data from URL on the special page.</li>
						<li><strong>Exception Data</strong> is comma separated list of exception parameters, that should remain in URL on the special page when set Unique URL. Everything else will redirect to the Unique URL.</li>
					</ol>
					<h3>Information</h3>
					<p>Here you can set the following fields:</p>
					<ol>
						<li><strong>Unique URL</strong> increasess the uniqueness URL, by redirecting and removing all unnecessary data from URL on the information page.</li>
						<li><strong>Exception Data</strong> is comma separated list of exception parameters, that should remain in URL on the information page when set Unique URL. Everything else will redirect to the Unique URL.</li>
					</ol>
					<h3>Custom Page</h3>
					<p>Here you can set the following fields:</p>
					<ol>
						<li><strong>Unique URL</strong> increasess the uniqueness URL, by redirecting and removing all unnecessary data from URL on the custom page.</li>
						<li><strong>Exception Data</strong> is comma separated list of exception parameters, that should remain in URL on the custom page when set Unique URL. Everything else will redirect to the Unique URL.</li>
					</ol>
					<div class="bs-callout bs-callout-warning">
						<h4>Warning!</h4>
						<p>Be careful with the removal from the list of exception parameters. After removal of the important parameters can be broken shop work.</p>
					</div>
				</div>
			</div>
			<div id="vtab_instruction_generator" class="tab-pane">
				<div class="tab-body">
					<h3>Category</h3>
					<p>Here you can generate URL Keyword for categories. For this you must fill the multilingual field "Template" and click on the button "Generate". To set a template, you can use shortcodes, that allow you to get the values of other fields of categories and replace shortcode on them. To insert the desired shortcode, just click on its button <span class="btn btn-default btn-xs"><i class="fa fa-plus-circle"></i></span>. For categories you can use the following shortcodes:</p>
					<ol>
						<li><strong>[name]</strong> will be replaced on the category name.</li>
						<li><strong>[target_keyword]</strong> will be replaced on the certain target keyword from this category. You will need to enter the number of target keyword.</li>
					</ol>
					<h3>Product</h3>
					<p>Here you can generate URL Keyword for products. For this you must fill the multilingual field "Template" and click on the button "Generate". To set a template, you can use shortcodes, that allow you to get the values of other fields of products and replace shortcode on them. To insert the desired shortcode, just click on its button <span class="btn btn-default btn-xs"><i class="fa fa-plus-circle"></i></span>. For products you can use the following shortcodes:</p>
					<ol>
						<li><strong>[name]</strong> will be replaced on the product name.</li>
						<li><strong>[target_keyword]</strong> will be replaced on the certain target keyword from this product. You will need to enter the number of target keyword.</li>
						<li><strong>[model]</strong> will be replaced on the product model.</li>
						<li><strong>[sku]</strong> will be replaced on the product sku.</li>
						<li><strong>[upc]</strong> will be replaced on the product upc.</li>
					</ol>
					<h3>Manufacturer</h3>
					<p>Here you can generate URL Keyword for manufacturers. For this you must fill the multilingual field "Template" and click on the button "Generate". To set a template, you can use shortcodes, that allow you to get the values of other fields of manufacturers and replace shortcode on them. To insert the desired shortcode, just click on its button <span class="btn btn-default btn-xs"><i class="fa fa-plus-circle"></i></span>. For manufacturers you can use the following shortcodes:</p>
					<ol>
						<li><strong>[name]</strong> will be replaced on the manufacturer title or name.</li>
						<li><strong>[target_keyword]</strong> will be replaced on the certain target keyword from this manufacturer. You will need to enter the number of target keyword.</li>
					</ol>
					<h3>Information</h3>
					<p>Here you can generate URL Keyword for informations. For this you must fill the multilingual field "Template" and click on the button "Generate". To set a template, you can use shortcodes, that allow you to get the values of other fields of informations and replace shortcode on them. To insert the desired shortcode, just click on its button <span class="btn btn-default btn-xs"><i class="fa fa-plus-circle"></i></span>. For informations you can use the following shortcodes:</p>
					<ol>
						<li><strong>[name]</strong> will be replaced on the information title.</li>
						<li><strong>[target_keyword]</strong> will be replaced on the certain target keyword from this information. You will need to enter the number of target keyword.</li>
					</ol>
					<div class="bs-callout bs-callout-info">
						<h4>Note!</h4>
						<p>To generate you can also use the following options:</p>
						<ol>
							<li><strong>Transform Language Symbols</strong> converts the language characters to large or small characters.</li>
							<li><strong>Translit Language Symbols</strong> converts language characters to the characters shown in the module Translit.</li>
						</ol>
					</div>
				</div>
			</div>
			<div id="vtab_instruction_custom_page" class="tab-pane">
				<div class="tab-body">
					<h3>Custom Pages</h3>
					<p>You can add URL Keywords on either custom static page of Opencart, including on homepage. To add the Custom Page, press button <span class="btn btn-primary btn-xs"><i class="fa fa-plus"></i></span> in top right of the page. Then you must specify the field "Route" of the custom static page (example common/home, checkout/cart, account/login, product/special, etc.) and a multilingual field "URL Keyword".</p>
					<p>You can also create default structure of Custom Pages with their URL Keywords, by press button <span class="btn btn-success btn-xs"><i class="fa fa-cogs"></i></span> in top right of the page.</p>
					<p>To edit field "URL Keyword", you need to click on the field table cell, change the value of field and press button <span class="btn btn-primary btn-xs"><i class="fa fa-save"></i></span>. If you decide not to save the new value, press button <span class="btn btn-danger btn-xs"><i class="fa fa-remove"></i></span>.</p>
					<p>To delete Custom Pages, select the pages that you want to delete and press button <span class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i></span> in top right of the page.</p>
				</div>
			</div>
			<div id="vtab_instruction_redirect" class="tab-pane">
				<div class="tab-body">
					<h3>Redirects</h3>
					<p>You can add Redirect on either page of Opencart. To add the redirect, press button <span class="btn btn-primary btn-xs"><i class="fa fa-plus"></i></span> in top right of the page. Then you must specify the field "Redirect From URL" of the page, from which you want to redirect, and a multilingual field "Redirect To URL" of the pages, on which you want to redirect.</p>
					<p>To edit fields "Redirect From URL" and "Redirect To URL", you need to click on the field table cell, change the value of field and press button <span class="btn btn-primary btn-xs"><i class="fa fa-save"></i></span>. If you decide not to save the new value, press button <span class="btn btn-danger btn-xs"><i class="fa fa-remove"></i></span>.</p>
					<p>To delete Redirects, select the redirects that you want to delete and press button <span class="btn btn-danger btn-xs"><i class="fa fa-trash-o"></i></span> in top right of the page.</p>
					<p>To filter redirects on different fields, you need to write the values under the titles of those fields, for which you want to filter, and press button <span class="btn btn-primary btn-xs"><i class="fa fa-search"></i> Filter</span>.</p>
					<p>To sort redirects by any field, you need to click the title of this field, for which you want to sort. First click will sort ascending, the second - descending.</p>
				</div>
			</div>
			<div id="vtab_instruction_export_import" class="tab-pane">
				<div class="tab-body">
					<h3>Export</h3>
					<p>Export is used to upload a list of Custom Pages and Redirects to Excel file. To export the Custom Pages and Redirects on the tab "Export", choose their and press button "Export".</p>
					<h3>Import</h3>
					<p>Import is used to download a list of Custom Pages and Redirects from Excel file. To import the Custom Pages and Redirects on the tab "Import", choose the xls file and press button "Import".</p>
				</div>
			</div>
			<div id="vtab_instruction_dashboard" class="tab-pane">
				<div class="tab-body">
					<h3>Dashboard</h3>
					<p>After installing of SEO Module URL in the admin panel of Opencart in the dashboard will appear modules:</p>
					<ol>
						<li><strong>SEO Module URL Alias</strong> informs you of the empty or duplicate URL Keywords.</li>
						<li><strong>SEO Module URL Redirect</strong> informs you of the empty Redirects To URL.</li>
					</ol>
					<p>To change their settings, you can in the extensions -> dashboard.</p>
					<h3>SEO Module URL Alias</h3>
					<p>Here you can edit field "URL Keyword". For this you need to click on the field table cell, change the value of field and press button <span class="btn btn-primary btn-xs"><i class="fa fa-save"></i></span>. If you decide not to save the new value, press button <span class="btn btn-danger btn-xs"><i class="fa fa-remove"></i></span>.</p>
					<h3>SEO Module URL Redirect</h3>
					<p>Here you can edit fields "Redirect From URL" and "Redirect To URL". For this you need to click on the field table cell, change the value of field and press button <span class="btn btn-primary btn-xs"><i class="fa fa-save"></i></span>. If you decide not to save the new value, press button <span class="btn btn-danger btn-xs"><i class="fa fa-remove"></i></span>.</p>
				</div>
			</div>
		</div>
	</div>
</div>';
$_['text_not_found'] = '
<div class="jumbotron">
	<h1>Please install Shopunity</h1>
	<p>Before you can use this module you will need to install Shopunity. Simply download the archive for your version of opencart and install it view Extension Installer or unzip the archive and upload all the files into your root folder from the UPLOAD folder.</p>
	<p><a class="btn btn-primary btn-lg" href="https://shopunity.net/download" target="_blank">Download</a></p>
</div>';

// Features
$_['text_dashboard_widget_for_duplicate_and_empty_url_keywords']	= 'Dashboard widget for duplicate and empty URL Keywords';
$_['text_dashboard_widget_for_redirects']							= 'Dashboard widget for redirects';
$_['text_export_import_urls_for_custom_pages_to_excel']				= 'Export & Import Urls for Custom pages to Excel';
$_['text_export_import_urls_for_redirects_to_excel']				= 'Export & Import Urls for Redirects to Excel';
$_['text_seo_module_url_api']										= 'SEO Module Url API';
$_['text_redirects_with_multi_language_support']					= 'Redirects with Multi Language support';
$_['text_autogenerate_urls_for_all_pages']							= 'Autogenerate urls for all pages';
$_['text_clear_all_urls']											= 'Clear all urls';
$_['text_overwrite_old_seo_urls']									= 'Overwrite old SEO urls';
$_['text_translit_symbols_and_letters_to_latin']					= 'Translit symbols and letters to latin';
$_['text_unique_urls_for_all_pages']								= 'Unique urls for all pages';
$_['text_long_or_short_urls_for_category_and_product']				= 'Long or short urls for Category and Product';
$_['text_multi_language_urls_for_all_pages']						= 'Multi Language Urls for all pages';
$_['text_set_canonicals_for_all_pages']								= 'Set Canonicals for all pages';
$_['text_pagination_canonicals']									= 'Pagination Canonicals';
$_['text_pagination_links_next_and_prev']							= 'Pagination links next and prev';
$_['text_alternate_hreflang_tag']									= 'Alternate hreflang tag';

// Column	
$_['column_route']								= 'Route';
$_['column_url_keyword']						= 'URL Keyword';
$_['column_url_from']							= 'Redirect from URL';
$_['column_url_to']								= 'Redirect to URL';

// Entry
$_['entry_status']        						= 'Status';
$_['entry_list_limit']        					= 'List Items Per Page';
$_['entry_uninstall']							= 'Uninstall Module';
$_['entry_multi_language_sub_directory_name'] 	= 'Subdirectory Name';
$_['entry_unique_url']        					= 'Unique URL';
$_['entry_exception_data']        				= 'Exception Data';
$_['entry_short_url']        					= 'Short URL';
$_['entry_canonical_link_search'] 				= 'Search in Canonical Link';
$_['entry_canonical_link_tag'] 					= 'Tag in Canonical Link';
$_['entry_canonical_link_page'] 				= 'Page in Canonical Link';
$_['entry_template']							= 'Template';
$_['entry_translit_symbol']						= 'Translit Symbols';
$_['entry_translit_language_symbol']			= 'Translit Language Symbols';
$_['entry_transform_language_symbol']			= 'Transform Language Symbols';
$_['entry_trim_symbol']							= 'Trim Symbols';
$_['entry_overwrite']							= 'Overwrite old value';
$_['entry_keyword_number'] 						= 'Number of keyword';
$_['entry_generation']          				= 'Generation';
$_['entry_route']								= 'Route';
$_['entry_url_keyword']							= 'URL Keyword';
$_['entry_url_from']							= 'Redirect from URL';
$_['entry_url_to']								= 'Redirect to URL';
$_['entry_store']        						= 'Choose Store';
$_['entry_sheet']        						= 'Choose Sheets';
$_['entry_export']        						= 'Export to File';
$_['entry_upload']        						= 'Upload File';
$_['entry_import']        						= 'Import from File';
$_['entry_category']        					= 'Category';

// Help
$_['help_setup']								= 'With SEO module Url you get multilingual support for your SEO urls. Mass generate urls using a variaty of placeholders and latin translit. Monitor broken 404 links, 301 redirect for duplicate urls and much more. Join the team of professionals with SEO module Url and learn how to make your webshop rank number one in Google. Click setup!';
$_['help_full_setup']							= 'Full Setup will install all available SEO modules and automatically generate meta data and SEO URLs for all pages of your store. Recommended for installing on the new store.';
$_['help_custom_setup']							= 'Custom Setup will install only required SEO modules. All further settings you have to do manually. Recommended for installing on the work store.';
$_['help_multi_language_sub_directory_status']  = 'Enable/disable multi language subdirectories.';
$_['help_multi_language_sub_directory_name'] 	= 'Name of subdirectories for each language.';
$_['help_redirect_status']        				= 'Enable/disable redirects on the tab Redirects.';
$_['help_redirect_exception_data']        		= 'Redirect Exception Data is comma separated list of exception parameters, that should remain in URL on the page after redirect. Everything else will be deleted from URL after redirect.';
$_['help_unique_url']							= 'Unique URL increasess the uniqueness URL, by redirecting and removing all unnecessary data from URL on the page.';
$_['help_exception_data']						= 'Exception Data is comma separated list of exception parameters, that should remain in URL on the page when set Unique URL. Everything else will redirect to the Unique URL.';
$_['help_short_url']							= 'Short URL removes the categories and subcategories from URL on the page.';
$_['help_canonical_link_search'] 				= 'Enable/disable search in the canonical link.';
$_['help_canonical_link_tag'] 					= 'Enable/disable tag in the canonical link.';
$_['help_canonical_link_page'] 					= 'Enable/disable page in the canonical link.';
$_['help_template'] 							= 'Template is multilingual field, in which you can use shortcodes, that allow you to get the values of other fields and replace shortcode with them.';
$_['help_translit_symbol'] 						= 'Translit Symbols converts special characters (%, &, #, etc.) to the characters shown in the module Translit.';
$_['help_translit_language_symbol'] 			= 'Translit Language Symbols converts language characters to the characters shown in the module Translit.';
$_['help_trim_symbol'] 							= 'Trim Symbols truncates the characters located at the beginning, at the end and which are duplicated in the middle of the text. These characters shown in the module Translit.';
$_['help_transform_language_symbol'] 			= 'Transform Language Symbols converts the language characters to large or small characters.';
$_['help_overwrite'] 							= 'If the parameter is not set, after generation will be overwritten only empty values. If the parameter is set, will be overwritten all the values.';

// Button	
$_['button_save'] 								= 'Save';
$_['button_save_and_stay'] 						= 'Save and Stay';
$_['button_cancel'] 							= 'Cancel';
$_['button_setup'] 								= 'Setup';
$_['button_uninstall'] 							= 'Uninstall';
$_['button_submit'] 							= 'Submit';	
$_['button_generate'] 							= 'Generate';
$_['button_filter'] 							= 'Filter';
$_['button_clear_filter'] 						= 'Clear filter';
$_['button_create_default_url_keywords']		= 'Create Default URL Keywords for Custom Pages';
$_['button_add_url_keyword']   					= 'Add URL Keyword';
$_['button_delete_url_keywords'] 				= 'Delete URL Keywords';
$_['button_add_redirect']   					= 'Add Redirect';
$_['button_delete_redirect'] 					= 'Delete Redirects';
$_['button_export'] 							= 'Export';
$_['button_import'] 							= 'Import';

// Success
$_['success_save']        						= 'Success: You have modified module SEO Module URL!';
$_['success_install']        					= 'Success: You have installed module SEO Module URL!';
$_['success_uninstall']							= 'Success: You have uninstalled module SEO Module URL!';
$_['success_generate']        					= 'Success: You have generated URL Keywords!';
$_['success_clear']        						= 'Success: You have cleared URL Keywords!';
$_['success_create_default_url_keywords']		= 'Success: You have successfully created URL Keywords for Custom Pages!';
$_['success_add_url_keyword']       			= 'Success: You have successfully added URL Keyword!';
$_['success_delete_url_keywords']      			= 'Success: You have successfully deleted URL Keywords!';
$_['success_add_redirect']       				= 'Success: You have successfully added Redirect!';
$_['success_delete_redirects']      			= 'Success: You have successfully deleted Redirects!';
$_['success_import']        					= 'Success: You have successfully imported your data!';

// Error
$_['error_warning']          					= 'Warning: Please check the form carefully for errors!';
$_['error_permission']    						= 'Warning: You do not have permission to modify module SEO Module URL!';
$_['error_installed']							= 'Warning: You can not install this module because it is already installed!';
$_['error_dependence_d_seo_module']    			= 'Warning: You can not install this module until you install module SEO Module!';
$_['error_route']								= 'Warning: Route does not appear to be valid!';
$_['error_route_exists']						= 'Warning: Route %s is already exists!';
$_['error_url_keyword']							= 'Warning: URL Keyword does not appear to be valid!';
$_['error_url_keyword_exists'] 					= 'Warning: URL Keyword %s is already exists!';
$_['error_url_from']							= 'Warning: Redirect from URL does not appear to be valid!';
$_['error_url_from_exists']						= 'Warning: Redirect from URL %s is already exists!';
$_['error_upload_name']							= 'Warning: Missing file name for upload!';
$_['error_upload_ext']							= 'Warning: Uploaded file has not one of the \'.xls\', \'.xlsx\' or \'.ods\' file name extensions, it might not be a spreadsheet file!';

?>