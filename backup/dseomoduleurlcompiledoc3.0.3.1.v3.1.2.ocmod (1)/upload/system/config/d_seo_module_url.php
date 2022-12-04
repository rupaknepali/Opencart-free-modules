<?php 
$_['d_seo_module_url_setting'] = array(
	'list_limit' => '20',
	'multi_language_sub_directory' => array(
		'status' => false,
		'name' => array()
	),
	'redirect' => array(
		'status' => true,
		'exception_data' => 'search, tag, description, category_id, sub_category, sort, order, page, limit, review_id, codename, secret, bfilter, ajaxfilter, edit, gclid, utm_source, utm_medium, utm_campaign'
	),
	'sheet' => array(
		'category' => array(
			'code' => 'category',
			'icon' => 'fa-navicon',
			'name' => 'text_category',
			'unique_url' => true,
			'exception_data' => 'sort, order, page, limit, codename, secret, bfilter, ajaxfilter, edit, gclid, utm_source, utm_medium, utm_campaign',
			'short_url' => false,
			'canonical_link_page' => true
		),
		'product' => array(
			'code' => 'product',
			'icon' => 'fa-shopping-cart',
			'name' => 'text_product',
			'unique_url' => true,
			'exception_data' => 'sort, order, page, limit, review_id, codename, secret, bfilter, ajaxfilter, edit, gclid, utm_source, utm_medium, utm_campaign',
			'short_url' => true
		),
		'manufacturer' => array(
			'code' => 'manufacturer',
			'icon' => 'fa-tag',
			'name' => 'text_manufacturer',
			'unique_url' => true,
			'exception_data' => 'sort, order, page, limit, codename, secret, bfilter, ajaxfilter, edit, gclid, utm_source, utm_medium, utm_campaign',
			'canonical_link_page' => true
		),
		'information' => array(
			'code' => 'information',
			'icon' => 'fa-info-circle',
			'name' => 'text_information',
			'unique_url' => true,
			'exception_data' => 'codename, secret, edit, gclid, utm_source, utm_medium, utm_campaign'
		),
		'search' => array(
			'code' => 'search',
			'icon' => 'fa-search',
			'name' => 'text_search',
			'unique_url' => true,
			'exception_data' => 'search, tag, description, category_id, sub_category, sort, order, page, limit, codename, secret, bfilter, ajaxfilter, edit, gclid, utm_source, utm_medium, utm_campaign',
			'canonical_link_search' => true,
			'canonical_link_tag' => true,
			'canonical_link_page' => true
		),
		'special' => array(
			'code' => 'special',
			'icon' => 'fa-gift',
			'name' => 'text_special',
			'unique_url' => true,
			'exception_data' => 'sort, order, page, limit, codename, secret, bfilter, ajaxfilter, edit, gclid, utm_source, utm_medium, utm_campaign',
			'canonical_link_page' => true
		),
		'custom_page' => array(
			'code' => 'custom_page',
			'icon' => 'fa-file-o',
			'name' => 'text_custom_page',
			'unique_url' => true,
			'exception_data' => 'page, remove, codename, secret, edit, gclid, utm_source, utm_medium, utm_campaign'
		)
	),
	'cache_expire' => '2592000',
	'default_url_keywords' => array(
		'account/account' => 'my-account',
		'account/address' => 'address-book',
		'account/download' => 'downloads',
		'account/edit' => 'edit-account',
		'account/forgotten' => 'forgot-password',
		'account/login' => 'login',
		'account/logout' => 'logout',
		'account/newsletter' => 'newsletter',
		'account/order' => 'order-history',
		'account/password' => 'change-password',
		'account/register' => 'create-account',
		'account/return' => 'returns',
		'account/return/add' => 'request-return',
		'account/reward' => 'reward-points',
		'account/transaction' => 'transactions',
		'account/voucher' => 'account-voucher',
		'account/wishlist' => 'wishlist',
		'affiliate/account' => 'affiliates',
		'affiliate/edit' => 'edit-affiliate-account',
		'affiliate/forgotten' => 'affiliate-forgot-password',
		'affiliate/login' => 'affiliate-login',
		'affiliate/logout' => 'affiliate-logout',
		'affiliate/password' => 'change-affiliate-password',
		'affiliate/payment' => 'affiliate-payment-options',
		'affiliate/register' => 'create-affiliate-account',
		'affiliate/tracking' => 'affiliate-tracking-code',
		'affiliate/transaction' => 'affiliate-transactions',
		'checkout/cart' => 'cart',
		'checkout/checkout' => 'checkout',
		'checkout/success' => 'checkout-success',
		'checkout/voucher' => 'gift-vouchers',
		'common/home' => '/',
		'product/compare' => 'compare-products',
		'product/manufacturer' => 'brands',
		'product/search' => 'search',
		'product/special' => 'specials',
		'information/contact' => 'contact-us',
		'information/sitemap' => 'sitemap'
	)
);
$_['d_seo_module_url_field_setting'] = array(
	'sheet' => array(
		'category' => array(
			'code' => 'category',
			'field' => array(
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'description' => 'help_url_keyword',
					'type' => 'text',
					'sort_order' => '30',
					'multi_store' => true,
					'multi_language' => true,
					'multi_store_status' => false,
					'required' => false
				)
			)
		),
		'product' => array(
			'code' => 'product',
			'field' => array(
				'category_id' => array(
					'code' => 'category_id',
					'name' => 'text_category_id',
					'description' => 'help_category_id',
					'type' => 'text',
					'sort_order' => '30',
					'multi_store' => false,
					'multi_language' => false,
					'multi_store_status' => false,
					'required' => false
				),
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'description' => 'help_url_keyword',
					'type' => 'text',
					'sort_order' => '31',
					'multi_store' => true,
					'multi_language' => true,
					'multi_store_status' => false,
					'required' => false
				)
			)
		),
		'manufacturer' => array(
			'code' => 'manufacturer',
			'field' => array(
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'description' => 'help_url_keyword',
					'type' => 'text',
					'sort_order' => '30',
					'multi_store' => true,
					'multi_language' => true,
					'multi_store_status' => false,
					'required' => false
				)
			)
		),
		'information' => array(
			'code' => 'information',
			'field' => array(
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'description' => 'help_url_keyword',
					'type' => 'text',
					'sort_order' => '30',
					'multi_store' => true,
					'multi_language' => true,
					'multi_store_status' => false,
					'required' => false
				)
			)
		),
		'custom_page' => array(
			'code' => 'custom_page',
			'field' => array(
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'description' => 'help_url_keyword',
					'type' => 'text',
					'sort_order' => '30',
					'multi_store' => true,
					'multi_language' => true,
					'multi_store_status' => false,
					'required' => false
				)
			)
		)
	)
);
$_['d_seo_module_url_generator_setting'] = array(
	'sheet' => array(
		'category' => array(
			'code' => 'category',
			'icon' => 'fa-navicon',
			'name' => 'text_category',
			'sort_order' => '1',
			'field' => array(
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'description' => 'help_generate_category_url_keyword',
					'sort_order' => '1',
					'template_default' => '[name]',
					'template_button' => array(
						'[name]' => '[name]', 
						'[target_keyword]' => '[target_keyword]'
					),
					'multi_language' => true,
					'translit_language_symbol_status' => false,
					'transform_language_symbol_id' => '1',
					'trim_symbol_status' => true,
					'overwrite' => false,
					'button_generate' => true,
					'button_clear' => true
				)
			),
			'button_popup' => array(
				'[target_keyword]' => array(
					'code' => '[target_keyword]',
					'name' => 'text_insert_target_keyword',
					'field' => array(
						'number' => array(
							'code' => 'number',
							'name' => 'text_keyword_number',
							'type' => 'text',
							'value' => '1'
						)
					)
				)
			)
		),
		'product' => array(
			'code' => 'product',
			'icon' => 'fa-shopping-cart',
			'name' => 'text_product',
			'sort_order' => '2',
			'field' => array(
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'description' => 'help_generate_product_url_keyword',
					'sort_order' => '1',
					'template_default' => '[name]',
					'template_button' => array(
						'[name]' => '[name]', 
						'[target_keyword]' => '[target_keyword]', 
						'[model]' => '[model]', 
						'[sku]' => '[sku]', 
						'[upc]' => '[upc]'
					),
					'multi_language' => true,
					'translit_language_symbol_status' => false,
					'transform_language_symbol_id' => '1',
					'trim_symbol_status' => true,
					'overwrite' => false,
					'button_generate' => true,
					'button_clear' => true
				)
			),
			'button_popup' => array(
				'[target_keyword]' => array(
					'code' => '[target_keyword]',
					'name' => 'text_insert_target_keyword',
					'field' => array(
						'number' => array(
							'code' => 'number',
							'name' => 'text_keyword_number',
							'type' => 'text',
							'value' => '1'
						)
					)
				)
			)
		),
		'manufacturer' => array(
			'code' => 'manufacturer',
			'icon' => 'fa-tag',
			'name' => 'text_manufacturer',
			'sort_order' => '3',
			'field' => array(
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'description' => 'help_generate_manufacturer_url_keyword',
					'sort_order' => '1',
					'template_default' => '[name]',
					'template_button' => array(
						'[name]' => '[name]', 
						'[target_keyword]' => '[target_keyword]'
					),
					'multi_language' => true,
					'translit_language_symbol_status' => false,
					'transform_language_symbol_id' => '1',
					'trim_symbol_status' => true,
					'overwrite' => false,
					'button_generate' => true,
					'button_clear' => true
				)
			),
			'button_popup' => array(
				'[target_keyword]' => array(
					'code' => '[target_keyword]',
					'name' => 'text_insert_target_keyword',
					'field' => array(
						'number' => array(
							'code' => 'number',
							'name' => 'text_keyword_number',
							'type' => 'text',
							'value' => '1'
						)
					)
				)
			)
		),
		'information' => array(
			'code' => 'information',
			'icon' => 'fa-info-circle',
			'name' => 'text_information',
			'sort_order' => '4',
			'field' => array(
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'description' => 'help_generate_information_url_keyword',
					'sort_order' => '1',
					'template_default' => '[title]',
					'template_button' => array(
						'[title]' => '[title]', 
						'[target_keyword]' => '[target_keyword]'
					),
					'multi_language' => true,
					'translit_language_symbol_status' => false,
					'transform_language_symbol_id' => '1',
					'trim_symbol_status' => true,
					'overwrite' => false,
					'button_generate' => true,
					'button_clear' => true
				)
			),
			'button_popup' => array(
				'[target_keyword]' => array(
					'code' => '[target_keyword]',
					'name' => 'text_insert_target_keyword',
					'field' => array(
						'number' => array(
							'code' => 'number',
							'name' => 'text_keyword_number',
							'type' => 'text',
							'value' => '1'
						)
					)
				)
			)
		)
	),
	'transform_language_symbol' => array(
		'0' => array(
			'id'	=> '0',
			'name'	=> 'text_transform_none'
		),
		'1' => array(
			'id'	=> '1',
			'name'	=> 'text_transform_upper_to_lower'
		),
		'2' => array(
			'id'	=> '2',
			'name'	=> 'text_transform_lower_to_upper'
		),
	)
);
$_['d_seo_module_url_url_setting'] = array(
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
$_['d_seo_module_url_manager_setting'] = array(
	'sheet' => array(
		'category' => array(
			'code' => 'category',
			'field' => array(
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'type' => 'text',
					'sort_order' => '30',
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
				'category_id' => array(
					'code' => 'category_id',
					'name' => 'text_category_id',
					'type' => 'text',
					'sort_order' => '30',
					'multi_store' => false,
					'multi_language' => false,
					'list_status' => false,
					'export_status' => true,
					'required' => false
				),
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'type' => 'text',
					'sort_order' => '31',
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
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'type' => 'text',
					'sort_order' => '30',
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
				'url_keyword' => array(
					'code' => 'url_keyword',
					'name' => 'text_url_keyword',
					'type' => 'text',
					'sort_order' => '30',
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
$_['d_seo_module_url_feature_setting'] = array(
	'dashboard_widget_for_duplicate_and_empty_url_keywords' => array(
		'name' => 'text_dashboard_widget_for_duplicate_and_empty_url_keywords',
		'image' => 'd_seo_module_url/feature/dashboard_widget_for_duplicate_and_empty_url_keywords.svg',
		'href' => 'https://opencartseomodule.com/dashboard-widget-for-duplicate-url-keywords',
	),
	'dashboard_widget_for_redirects' => array(
		'name' => 'text_dashboard_widget_for_redirects',
		'image' => 'd_seo_module_url/feature/dashboard_widget_for_redirects.svg',
		'href' => 'https://opencartseomodule.com/dashboard-widget-for-redirects',
	),
	'export_import_urls_for_custom_pages_to_excel' => array(
		'name' => 'text_export_import_urls_for_custom_pages_to_excel',
		'image' => 'd_seo_module_url/feature/export_import_urls_for_custom_pages_to_excel.svg',
		'href' => 'https://opencartseomodule.com/export-import-urls-for-custom-pages-to-excel',
	),
	'export_import_urls_for_redirects_to_excel' => array(
		'name' => 'text_export_import_urls_for_redirects_to_excel',
		'image' => 'd_seo_module_url/feature/export_import_urls_for_redirects_to_excel.svg',
		'href' => 'https://opencartseomodule.com/export-import-urls-for-redirects-to-excel',
	),
	'seo_module_url_api' => array(
		'name' => 'text_seo_module_url_api',
		'image' => 'd_seo_module_url/feature/seo_module_url_api.svg',
		'href' => 'https://opencartseomodule.com/seo-module-url-api',
	),
	'redirects_with_multi_language_support' => array(
		'name' => 'text_redirects_with_multi_language_support',
		'image' => 'd_seo_module_url/feature/redirects_with_multi_language_support.svg',
		'href' => 'https://opencartseomodule.com/redirect-404-with-multilanguage-support',
	),
	'autogenerate_urls_for_all_pages' => array(
		'name' => 'text_autogenerate_urls_for_all_pages',
		'image' => 'd_seo_module_url/feature/autogenerate_urls_for_all_pages.svg',
		'href' => 'https://opencartseomodule.com/autogenerate-urls-for-all-pages',
	),
	'clear_all_urls' => array(
		'name' => 'text_clear_all_urls',
		'image' => 'd_seo_module_url/feature/clear_all_urls.svg',
		'href' => 'https://opencartseomodule.com/clear-all-urls',
	),
	'overwrite_old_seo_urls' => array(
		'name' => 'text_overwrite_old_seo_urls',
		'image' => 'd_seo_module_url/feature/overwrite_old_seo_urls.svg',
		'href' => 'https://opencartseomodule.com/overwrite-old-seo-urls',
	),
	'translit_symbols_and_letters_to_latin' => array(
		'name' => 'text_translit_symbols_and_letters_to_latin',
		'image' => 'd_seo_module_url/feature/translit_symbols_and_letters_to_latin.svg',
		'href' => 'https://opencartseomodule.com/translit-meta-symbols-and-letters-to-latin',
	),
	'unique_urls_for_all_pages' => array(
		'name' => 'text_unique_urls_for_all_pages',
		'image' => 'd_seo_module_url/feature/unique_urls_for_all_pages.svg',
		'href' => 'https://opencartseomodule.com/unique-urls-for-all-pages',
	),
	'long_or_short_urls_for_category_and_product' => array(
		'name' => 'text_long_or_short_urls_for_category_and_product',
		'image' => 'd_seo_module_url/feature/long_or_short_urls_for_category_and_product.svg',
		'href' => 'https://opencartseomodule.com/long-or-short-urls-for-category-and-product',
	),
	'multi_language_urls_for_all_pages' => array(
		'name' => 'text_multi_language_urls_for_all_pages',
		'image' => 'd_seo_module_url/feature/multi_language_urls_for_all_pages.svg',
		'href' => 'https://opencartseomodule.com/multilanguage-urls-for-all-pages',
	),
	'set_canonicals_for_all_pages' => array(
		'name' => 'text_set_canonicals_for_all_pages',
		'image' => 'd_seo_module_url/feature/set_canonicals_for_all_pages.svg',
		'href' => 'https://opencartseomodule.com/canonicals-for-all-pages',
	),
	'pagination_canonicals' => array(
		'name' => 'text_pagination_canonicals',
		'image' => 'd_seo_module_url/feature/pagination_canonicals.svg',
		'href' => 'https://opencartseomodule.com/pagination-canonicals',
	),
	'pagination_links_next_and_prev' => array(
		'name' => 'text_pagination_links_next_and_prev',
		'image' => 'd_seo_module_url/feature/pagination_links_next_and_prev.svg',
		'href' => 'https://opencartseomodule.com/pagination-links-next-and-prev',
	),
	'alternate_hreflang_tag' => array(
		'name' => 'text_alternate_hreflang_tag',
		'image' => 'd_seo_module_url/feature/alternate_hreflang_tag.svg',
		'href' => 'https://opencartseomodule.com/alternate-hreflang-tag',
	)
);
?>