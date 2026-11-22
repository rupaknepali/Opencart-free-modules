<?php
// Heading
$_['heading_title']            = 'Speed Booster';

// Text
$_['text_extension']           = 'Extensions';
$_['text_success']             = 'Success: You have modified Speed Booster! The page cache was emptied.';
$_['text_edit']                = 'Edit Speed Booster';
$_['text_info']                = 'Guests get a ready-made copy of each page instead of OpenCart building it again on every visit. Logged-in customers, visitors with something in the cart, and checkout and account pages always get a live page.';
$_['text_cache']               = 'Page cache';
$_['text_frontend']            = 'Front-end';
$_['text_report']              = 'Speed check';
$_['text_indexes']             = 'Database indexes';
$_['text_purged']              = 'Success: %s cached pages removed.';
$_['text_on']                  = 'On';
$_['text_off']                 = 'Off';
$_['text_unknown']             = 'Couldn\'t check';
$_['text_cache_pages']         = '%s pages cached (%s)';
$_['text_opcache']             = 'On: %s MB used, %s scripts cached';
$_['text_level']               = 'OpenCart, level %s';
$_['text_server_compresses']   = 'Web server (%s)';
$_['text_sessions']            = '%s sessions, %s expired';
$_['text_images']              = '%s resized images';
$_['text_indexes_ok']          = 'All the indexes OpenCart needs are in place.';
$_['text_indexes_missing']     = 'OpenCart looks up these columns without an index, so MySQL reads the whole table each time. Adding them is safe and takes seconds on most stores; they stay if you uninstall Speed Booster.';
$_['text_test']                = 'How to test: open your store in a private window and reload a page twice. In the browser\'s Network tab, the page\'s response headers show X-Speed-Booster: MISS the first time and HIT the second.';
$_['text_loading']             = 'Checking your store...';
$_['text_fixed_product_count'] = 'Success: Category Product Count is off in every store.';
$_['text_fixed_customer_online'] = 'Success: Customers Online and Customer Activity tracking are off.';
$_['text_fixed_compression']   = 'Success: Output Compression Level set to 5.';
$_['text_fixed_sessions']      = 'Success: Expired sessions deleted.';
$_['text_fixed_indexes']       = 'Success: Indexes added.';
$_['text_confirm']             = 'Are you sure?';

// Column
$_['column_check']             = 'Check';
$_['column_value']             = 'Your store';
$_['column_advice']            = 'Advice';
$_['column_table']             = 'Table';
$_['column_column']            = 'Column';
$_['column_rows']              = 'Rows';

// Checks
$_['check_page_cache']         = 'Page cache';
$_['check_php']                = 'PHP version';
$_['check_opcache']            = 'OPcache';
$_['check_cache_engine']       = 'Cache engine';
$_['check_product_count']      = 'Category Product Count';
$_['check_customer_online']    = 'Customers Online / Activity';
$_['check_compression']        = 'Output compression';
$_['check_sessions']           = 'Session table';
$_['check_error_log']          = 'Error log';
$_['check_image_cache']        = 'Image cache';

// Advice
$_['advice_page_cache']        = 'Turn on Status and Page cache above.';
$_['advice_php']               = 'PHP 8.2 or newer is noticeably faster. Ask your host to switch.';
$_['advice_opcache']           = 'Without OPcache PHP compiles every file on every request. Ask your host to enable it: it is the biggest single server-side win.';
$_['advice_cache_engine']      = 'File cache works everywhere. On a busy store, APCu or Redis is faster: set CACHE_ENGINE in config.php.';
$_['advice_product_count']     = 'Shows "(12)" after each category name, at the cost of a count query per category on every page with the menu. Turning it off is usually the biggest database saving.';
$_['advice_customer_online']   = 'Writes to the database on every page view. Turn it off unless you watch the Customers Online report.';
$_['advice_compression']       = 'Pages are sent uncompressed. Turn on gzip or Brotli in your web server, or let OpenCart compress (level 5).';
$_['advice_compression_unknown'] = 'Your server couldn\'t load its own storefront to check. Test it at a site such as giftofspeed.com/gzip-test: if pages aren\'t compressed, set Output Compression Level to 5.';
$_['advice_sessions']         = 'Old sessions make the session table slower. Delete them now; set up the OpenCart cron so it keeps happening.';
$_['advice_error_log']         = 'A large log means something is logging on every request. Look at System > Maintenance > Error Logs, fix the cause and clear it.';
$_['advice_image_cache']       = 'Resized product images. Safe to delete if you change image sizes: OpenCart makes them again.';

// Entry
$_['entry_status']             = 'Status';
$_['entry_cache_status']       = 'Cache pages for guests';
$_['entry_cache_ttl']          = 'Keep pages for (minutes)';
$_['entry_cache_exclude']      = 'Never cache these routes';
$_['entry_vary_cookies']       = 'Separate copies for cookies';
$_['entry_lazy_status']        = 'Lazy-load images';
$_['entry_lazy_skip']          = 'Load first images straight away';
$_['entry_minify_status']      = 'Minify HTML';
$_['entry_preconnect']         = 'Preconnect to';

// Help
$_['help_cache_ttl']           = 'Saving a product, category, setting or layout empties the cache straight away, and an order refreshes the pages of the products in it. This is only the upper limit, for things like specials that end on a date.';
$_['help_cache_exclude']       = 'One route per line, * matches anything. Checkout, account and cart pages never show a cached copy anyway, because those visitors are logged in or have a cart.';
$_['help_vary_cookies']        = 'Pages that look different depending on a cookie. "policy" is OpenCart\'s cookie consent bar. Add the cookie names other extensions use, separated by commas.';
$_['help_lazy_skip']           = 'The logo and main product image should never be lazy-loaded: that hurts Largest Contentful Paint. 3 suits the default theme.';
$_['help_minify']              = 'Removes indentation and comments. Scripts, styles and text areas are left exactly as they are.';
$_['help_preconnect']          = 'One origin per line, for example https://fonts.gstatic.com. Use it for fonts, a CDN or a payment script that every page loads.';

// Button
$_['button_purge']             = 'Empty cache';
$_['button_fix']               = 'Fix';
$_['button_add_indexes']       = 'Add missing indexes';
$_['button_recheck']           = 'Check again';

// Error
$_['error_permission']         = 'Warning: You do not have permission to modify Speed Booster!';
$_['error_warning']            = 'Warning: Please check the form carefully for errors!';
$_['error_ttl']                = 'Enter a number of minutes from 1 to 10080 (a week).';
$_['error_preconnect']         = '%s isn\'t an origin. Use the form https://example.com, without a path.';
$_['error_fix']                = 'Unknown fix.';
