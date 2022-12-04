<?php 
$_['d_seo_module_setting'] = array(
	'list_limit' => '20',
	'cache_expire' => '2592000',
	'default_target_keywords' => array(
		'common/home' => array('webshop'),
		'product/manufacturer' => array('brand'),
		'product/special' => array('special'),
		'information/contact' => array('contact')
	),
	'default_htaccess' => <<<TEXT
# 1.To use URL Alias you need to be running apache with mod_rewrite enabled.

# 2. In your opencart directory rename htaccess.txt to .htaccess.

# For any support issues please visit: http://www.opencart.com

Options +SymLinksIfOwnerMatch

# Prevent Directoy listing
Options -Indexes

# Prevent Direct Access to files
<FilesMatch "(?i)((\.tpl|.twig|\.ini|\.log|(?<!robots)\.txt))">
 Require all denied
## For apache 2.2 and older, replace "Require all denied" with these two lines :
# Order deny,allow
# Deny from all
</FilesMatch>

# SEO URL Settings
RewriteEngine On
# If your opencart installation does not run on the main web folder make sure you folder it does run in ie. / becomes /shop/

RewriteBase [catalog_url_path]
RewriteRule ^sitemap.xml$ index.php?route=extension/feed/google_sitemap [L]
RewriteRule ^googlebase.xml$ index.php?route=extension/feed/google_base [L]
RewriteRule ^system/storage/(.*) index.php?route=error/not_found [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !.*\.(ico|gif|jpg|jpeg|png|js|css)
RewriteRule ^([^?]*) index.php?_route_=$1 [L,QSA]

### Additional Settings that may need to be enabled for some servers
### Uncomment the commands by removing the # sign in front of it.
### If you get an "Internal Server Error 500" after enabling any of the following settings, restore the # as this means your host doesn't allow that.

# 1. If your cart only allows you to add one item at a time, it is possible register_globals is on. This may work to disable it:
# php_flag register_globals off

# 2. If your cart has magic quotes enabled, This may work to disable it:
# php_flag magic_quotes_gpc Off

# 3. Set max upload file size. Most hosts will limit this and not allow it to be overridden but you can try
# php_value upload_max_filesize 999M

# 4. set max post size. uncomment this line if you have a lot of product options or are getting errors where forms are not saving all fields
# php_value post_max_size 999M

# 5. set max time script can take. uncomment this line if you have a lot of product options or are getting errors where forms are not saving all fields
# php_value max_execution_time 200

# 6. set max time for input to be recieved. Uncomment this line if you have a lot of product options or are getting errors where forms are not saving all fields
# php_value max_input_time 200

# 7. disable open_basedir limitations
# php_admin_value open_basedir none

TEXT
	,
	'default_robots' => <<<TEXT
User-agent: *
Disallow: /*route=account/
Disallow: /*route=affiliate/
Disallow: /*route=checkout/
Disallow: /*route=product/search
Disallow: /index.php?route=product/product*&manufacturer_id=
Disallow: /admin
Disallow: /catalog
Disallow: /system
Disallow: /*?sort=
Disallow: /*&sort=
Disallow: /*?order=
Disallow: /*&order=
Disallow: /*?limit=
Disallow: /*&limit=
Disallow: /*?format=
Disallow: /*&format=
Disallow: /*?tracking=
Disallow: /*&tracking=
Disallow: /*?filter=
Disallow: /*&filter=
Disallow: /*?filter_name=
Disallow: /*&filter_name=
Disallow: /*?filter_sub_category=
Disallow: /*&filter_sub_category=
Disallow: /*?filter_description=
Disallow: /*&filter_description=

Sitemap: [catalog_url]sitemap.xml
Host: [catalog_url_host]

TEXT
);
$_['d_seo_module_field_setting'] = array(
	'sheet' => array(
		'category' => array(
			'code' => 'category',
			'icon' => 'fa-navicon',
			'name' => 'text_category',
			'sort_order' => '1',
			'field' => array(
				'target_keyword' => array(
					'code' => 'target_keyword',
					'name' => 'text_target_keyword',
					'description' => 'help_target_keyword',
					'type' => 'textarea',
					'sort_order' => '20',
					'multi_store' => true,
					'multi_language' => true,
					'multi_store_status' => false,
					'required' => false
				)
			)
		),
		'product' => array(
			'code' => 'product',
			'icon' => 'fa-shopping-cart',
			'name' => 'text_product',
			'sort_order' => '2',
			'field' => array(
				'target_keyword' => array(
					'code' => 'target_keyword',
					'name' => 'text_target_keyword',
					'description' => 'help_target_keyword',
					'type' => 'textarea',
					'sort_order' => '20',
					'multi_store' => true,
					'multi_language' => true,
					'multi_store_status' => false,
					'required' => false
				)
			)
		),
		'manufacturer' => array(
			'code' => 'manufacturer',
			'icon' => 'fa-tag',
			'name' => 'text_manufacturer',
			'sort_order' => '3',
			'field' => array(
				'target_keyword' => array(
					'code' => 'target_keyword',
					'name' => 'text_target_keyword',
					'description' => 'help_target_keyword',
					'type' => 'textarea',
					'sort_order' => '20',
					'multi_store' => true,
					'multi_language' => true,
					'multi_store_status' => false,
					'required' => false
				)
			)
		),
		'information' => array(
			'code' => 'information',
			'icon' => 'fa-info-circle',
			'name' => 'text_information',
			'sort_order' => '4',
			'field' => array(
				'target_keyword' => array(
					'code' => 'target_keyword',
					'name' => 'text_target_keyword',
					'description' => 'help_target_keyword',
					'type' => 'textarea',
					'sort_order' => '20',
					'multi_store' => true,
					'multi_language' => true,
					'multi_store_status' => false,
					'required' => false
				)
			)
		),
		'custom_page' => array(
			'code' => 'custom_page',
			'icon' => 'fa-file-o',
			'name' => 'text_custom_page',
			'sort_order' => '50',
			'field' => array(
				'target_keyword' => array(
					'code' => 'target_keyword',
					'name' => 'text_target_keyword',
					'description' => 'help_target_keyword',
					'type' => 'textarea',
					'sort_order' => '20',
					'multi_store' => true,
					'multi_language' => true,
					'multi_store_status' => false,
					'required' => false
				)
			)
		)
	)
);
$_['d_seo_module_target_setting'] = array(
	'sheet' => array(
		'category' => array(
			'code' => 'category',
			'icon' => 'fa-navicon',
			'name' => 'text_category',
			'sort_order' => '1'
		),
		'product' => array(
			'code' => 'product',
			'icon' => 'fa-shopping-cart',
			'name' => 'text_product',
			'sort_order' => '2'
		),
		'manufacturer' => array(
			'code' => 'manufacturer',
			'icon' => 'fa-tag',
			'name' => 'text_manufacturer',
			'sort_order' => '3'
		),
		'information' => array(
			'code' => 'information',
			'icon' => 'fa-info-circle',
			'name' => 'text_information',
			'sort_order' => '4'
		),
		'custom_page' => array(
			'code' => 'custom_page',
			'icon' => 'fa-file-o',
			'name' => 'text_custom_page',
			'sort_order' => '50'
		)
	)
);
$_['d_seo_module_manager_setting'] = array(
	'sheet' => array(
		'category' => array(
			'code' => 'category',
			'field' => array(
				'target_keyword' => array(
					'code' => 'target_keyword',
					'name' => 'text_target_keyword',
					'type' => 'textarea',
					'sort_order' => '20',
					'multi_store' => true,
					'multi_language' => true,
					'list_status' => true,
					'export_status' => true,
					'required' => false
				)
			)
		),
		'product' => array(
			'code' => 'product',
			'field' => array(
				'target_keyword' => array(
					'code' => 'target_keyword',
					'name' => 'text_target_keyword',
					'type' => 'textarea',
					'sort_order' => '20',
					'multi_store' => true,
					'multi_language' => true,
					'list_status' => true,
					'export_status' => true,
					'required' => false
				)
			)
		),
		'manufacturer' => array(
			'code' => 'manufacturer',
			'field' => array(
				'target_keyword' => array(
					'code' => 'target_keyword',
					'name' => 'text_target_keyword',
					'type' => 'textarea',
					'sort_order' => '20',
					'multi_store' => true,
					'multi_language' => true,
					'list_status' => true,
					'export_status' => true,
					'required' => false
				)
			)
		),
		'information' => array(
			'code' => 'information',
			'field' => array(
				'target_keyword' => array(
					'code' => 'target_keyword',
					'name' => 'text_target_keyword',
					'type' => 'textarea',
					'sort_order' => '20',
					'multi_store' => true,
					'multi_language' => true,
					'list_status' => true,
					'export_status' => true,
					'required' => false
				)
			)
		)
	)
);
$_['d_seo_module_feature_setting'] = array(
	'dashboard_widget_for_duplicate_and_empty_target_keywords' => array(
		'name' => 'text_dashboard_widget_for_duplicate_and_empty_target_keywords',
		'image' => 'd_seo_module/feature/dashboard_widget_for_duplicate_and_empty_target_keywords.svg',
		'href' => 'https://opencartseomodule.com/dashboard-widget-for-duplicate-target-keywords',
	),
	'opencart_tag_search_fix' => array(
		'name' => 'text_opencart_tag_search_fix',
		'image' => 'd_seo_module/feature/opencart_tag_search_fix.svg',
		'href' => 'https://opencartseomodule.com/opencart-tag-search-fix',
	),
	'opencart_first_load_of_comments_fix' => array(
		'name' => 'text_opencart_first_load_of_comments_fix',
		'image' => 'd_seo_module/feature/opencart_first_load_of_comments_fix.svg',
		'href' => 'https://opencartseomodule.com/opencart-first-load-of-comments-fix',
	),
	'target_keyword_planner' => array(
		'name' => 'text_target_keyword_planner',
		'image' => 'd_seo_module/feature/target_keyword_planner.svg',
		'href' => 'https://opencartseomodule.com/target-keyword-planner',
	),
	'seo_module_api' => array(
		'name' => 'text_seo_module_api',
		'image' => 'd_seo_module/feature/seo_module_api.svg',
		'href' => 'https://opencartseomodule.com/seo-module-api',
	),
	'export_import_target_keywords_for_custom_pages' => array(
		'name' => 'text_export_import_target_keywords_for_custom_pages',
		'image' => 'd_seo_module/feature/export_import_target_keywords_for_custom_pages.svg',
		'href' => 'https://opencartseomodule.com/export-import-for-custom-page-keywords',
	),
	'robots_txt_editor' => array(
		'name' => 'text_robots_txt_editor',
		'image' => 'd_seo_module/feature/robots_txt_editor.svg',
		'href' => 'https://opencartseomodule.com/robots-txt-editor',
	),
	'htaccess_editor' => array(
		'name' => 'text_htaccess_editor',
		'image' => 'd_seo_module/feature/htaccess_editor.svg',
		'href' => 'https://opencartseomodule.com/opencart-htaccess-editor',
	),
	'seo_module_multi_store_support' => array(
		'name' => 'text_seo_module_multi_store_support',
		'image' => 'd_seo_module/feature/seo_module_multi_store_support.svg',
		'href' => 'https://opencartseomodule.com/multistore-seo-support',
	),
	'seo_module_quick_setup' => array(
		'name' => 'text_seo_module_quick_setup',
		'image' => 'd_seo_module/feature/seo_module_quick_setup.svg',
		'href' => 'https://opencartseomodule.com/quick-setup',
	)
);
?>