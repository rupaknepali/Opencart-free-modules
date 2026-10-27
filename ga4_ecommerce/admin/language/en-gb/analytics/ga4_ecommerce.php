<?php
// Heading
$_['heading_title']        = 'GA4 Ecommerce Events';

// Text
$_['text_extension']       = 'Extensions';
$_['text_success']         = 'Success: You have modified GA4 Ecommerce Events!';
$_['text_edit']            = 'Edit GA4 Ecommerce Events';
$_['text_product_id']      = 'Product ID';
$_['text_model']           = 'Model';
$_['text_sku']             = 'SKU (from product identifiers)';
$_['text_events']          = 'Events sent: <code>view_item_list</code>, <code>view_item</code>, <code>add_to_cart</code>, <code>view_cart</code>, <code>begin_checkout</code> and <code>purchase</code>. Check them in GA4 under <strong>Admin &gt; DebugView</strong> with Debug mode switched on.';
$_['text_cookie_policy']   = 'Your store has a cookie policy (System &gt; Settings &gt; Option &gt; Cookie Policy). OpenCart only loads analytics extensions after the visitor accepts it, so visitors who decline are not tracked.';
$_['text_duplicate']       = 'Remove any other Google Analytics or GA4 code from your theme, header or the Google Analytics extension before switching this on, or every page view and purchase is counted twice.';

// Entry
$_['entry_status']         = 'Status';
$_['entry_measurement_id'] = 'Measurement ID';
$_['entry_item_id']        = 'Item ID';
$_['entry_tax']            = 'Prices include tax';
$_['entry_list']           = 'Send view_item_list';
$_['entry_debug']          = 'Debug mode';

// Help
$_['help_measurement_id']  = 'Starts with G-. Find it in GA4 under Admin &gt; Data streams &gt; your web stream.';
$_['help_item_id']         = 'Use the same ID you use in Google Merchant Center, so GA4 and Merchant Center reports match.';
$_['help_tax']             = 'Off (recommended) sends prices without tax. The purchase value is always the order total.';
$_['help_list']            = 'Sends the products shown on category, search and module pages. Turn off if you are near GA4 event limits.';
$_['help_debug']           = 'Sends debug_mode so events show in DebugView. Turn off after testing.';

// Error
$_['error_warning']        = 'Warning: Please check the form carefully for errors!';
$_['error_permission']     = 'Warning: You do not have permission to modify GA4 Ecommerce Events!';
$_['error_measurement_id'] = 'Measurement ID must look like G-XXXXXXXXXX!';
