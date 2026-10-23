<?php
// Heading
$_['heading_title']          = 'Back-in-Stock Notifications';

// Text
$_['text_extension']         = 'Extensions';
$_['text_success']           = 'Success: You have modified Back-in-Stock Notifications!';
$_['text_delete']            = 'Success: The subscription has been deleted.';
$_['text_edit']              = 'Edit Back-in-Stock Notifications';
$_['text_general']           = 'General';
$_['text_email']             = 'Email';
$_['text_wanted']            = 'Most Wanted';
$_['text_report']            = 'Subscriptions';
$_['text_cron_info']         = 'Notification emails are sent by the <strong>back_in_stock</strong> job in <a href="%s" class="alert-link">Extensions &gt; Cron Jobs</a>. Your server must run OpenCart\'s <code>cron.php</code> every hour for it to work. You can also run the job by hand from that page while testing.';
$_['text_placeholders']      = 'Placeholders: <code>{firstname}</code> <code>{store_name}</code> <code>{product_name}</code> <code>{product}</code> (image, name and price block) <code>{product_image}</code> <code>{product_price}</code> <code>{product_link}</code>.';
$_['text_stock_checkout']    = 'Your store allows checkout of out-of-stock products (System &gt; Settings &gt; Option &gt; Stock Checkout), so the Add to Cart button is never hidden.';
$_['text_wanted_info']       = 'Products with the most customers waiting. Use this list to decide what to reorder first.';
$_['text_waiting']           = 'Waiting';
$_['text_notified']          = 'Notified';
$_['text_purchased']         = 'Purchased';
$_['text_rate']              = 'Conversion rate';
$_['text_revenue']           = 'Revenue';
$_['text_status_0']          = 'Waiting';
$_['text_status_1']          = 'Notified';
$_['text_status_2']          = 'Purchased';

// Column
$_['column_email']           = 'Customer';
$_['column_product']         = 'Product';
$_['column_model']           = 'Model';
$_['column_quantity']        = 'Stock';
$_['column_waiting']         = 'Waiting';
$_['column_oldest']          = 'Waiting Since';
$_['column_clicks']          = 'Clicks';
$_['column_status']          = 'Status';
$_['column_order']           = 'Order';
$_['column_date_added']      = 'Subscribed';
$_['column_date_notified']   = 'Notified';
$_['column_action']          = 'Action';

// Entry
$_['entry_status']           = 'Status';
$_['entry_hide_cart']        = 'Hide Add to Cart when out of stock';
$_['entry_max_emails']       = 'Max emails per hour';
$_['entry_retention']        = 'Delete data after (days)';
$_['entry_bcc']              = 'BCC email';
$_['entry_subject']          = 'Subject';
$_['entry_message']          = 'Message';
$_['entry_filter_email']     = 'Email';
$_['entry_filter_product']   = 'Product';

// Help
$_['help_hide_cart']         = 'Shows only the notify form on out-of-stock product pages, so customers don\'t add a product they can\'t buy.';
$_['help_max_emails']        = 'Keeps you under your mail provider\'s sending limit. Gmail allows about 500 a day; most SMTP services allow more. The rest are sent in the next hour, oldest subscriptions first.';
$_['help_retention']         = 'Subscriptions older than this are deleted, whether they were notified or not. Minimum 30.';
$_['help_bcc']               = 'Receive a copy of every notification. Leave empty in production.';

// Error
$_['error_permission']       = 'Warning: You do not have permission to modify Back-in-Stock Notifications!';
$_['error_warning']          = 'Warning: Please check the form carefully for errors!';
$_['error_bcc']              = 'BCC email does not appear to be valid!';
$_['error_subject']          = 'Subject must be at least 3 characters!';
$_['error_message']          = 'Message is required!';
