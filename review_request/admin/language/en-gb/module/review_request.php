<?php
// Heading
$_['heading_title']             = 'Review Request Emails';

// Text
$_['text_extension']            = 'Extensions';
$_['text_success']              = 'Success: You have modified Review Request Emails!';
$_['text_delete']               = 'Success: The request has been deleted.';
$_['text_edit']                 = 'Edit Review Request Emails';
$_['text_general']              = 'General';
$_['text_email']                = 'Email';
$_['text_reminder']             = 'Reminder';
$_['text_report']               = 'Report';
$_['text_days']                 = 'days';
$_['text_cron_info']            = 'Review requests are sent by the <strong>review_request</strong> job in <a href="%s" class="alert-link">Extensions &gt; Cron Jobs</a>. Your server must run OpenCart\'s <code>cron.php</code> every hour for it to work. You can also run the job by hand from that page while testing.';
$_['text_review_disabled']      = 'Reviews are turned off in System &gt; Settings &gt; Option &gt; Reviews. Customers will not be able to submit reviews until you turn them on.';
$_['text_placeholders']         = 'Placeholders: <code>{firstname}</code> <code>{store_name}</code> <code>{order_id}</code> <code>{products}</code> (products with clickable stars) <code>{review_link}</code> <code>{unsubscribe_link}</code>. An unsubscribe line is always added at the bottom of every email.';
$_['text_moderation']           = 'New reviews appear in <a href="%s" target="_blank">Catalog &gt; Reviews</a>.';
$_['text_requested']            = 'Orders emailed';
$_['text_clicked']              = 'Click rate';
$_['text_reviews']              = 'Reviews collected';
$_['text_rate']                 = 'Response rate';
$_['text_rating']               = 'Average rating';
$_['text_status_1']             = 'Sent';
$_['text_status_2']             = 'Reviewed';
$_['text_status_3']             = 'Unsubscribed';
$_['text_status_4']             = 'Skipped';

// Column
$_['column_email']              = 'Customer';
$_['column_order']              = 'Order';
$_['column_products']           = 'Products';
$_['column_emails']             = 'Emails';
$_['column_clicks']             = 'Clicks';
$_['column_reviews']            = 'Reviews';
$_['column_status']             = 'Status';
$_['column_date_sent']          = 'Sent';
$_['column_action']             = 'Action';

// Entry
$_['entry_status']              = 'Status';
$_['entry_order_status']        = 'Send when order status is';
$_['entry_delay']               = 'Send after';
$_['entry_reminder']            = 'Send a reminder';
$_['entry_reminder_delay']      = 'Reminder after';
$_['entry_approve']             = 'Publish without approval';
$_['entry_start']               = 'Only orders completed on or after';
$_['entry_retention']           = 'Delete data after (days)';
$_['entry_bcc']                 = 'BCC email';
$_['entry_subject']             = 'Subject';
$_['entry_message']             = 'Message';
$_['entry_filter_email']        = 'Email';
$_['entry_filter_order_id']     = 'Order ID';

// Help
$_['help_order_status']         = 'Usually Complete, or Shipped/Delivered if you use those. The email goes out once the order has had one of these statuses for the number of days below.';
$_['help_delay']                = 'Days after the order reaches the status above. Give the customer time to receive and use the product: 7 days for most stores, longer for slow shipping. Between 1 and 60.';
$_['help_reminder']             = 'One reminder to customers who did not leave any review. Never more than one.';
$_['help_reminder_delay']       = 'Days after the first email.';
$_['help_approve']              = 'Reviews from this page come from verified buyers. Turn this on to publish them straight away, or leave it off to approve each one in Catalog &gt; Reviews. It applies to every rating, good or bad.';
$_['help_start']                = 'Stops the extension from emailing everyone who ever ordered from you when you first install it. Orders are only picked up for 14 days after they become due.';
$_['help_retention']            = 'Request data older than this is deleted. Reviews are kept. Minimum 90.';
$_['help_bcc']                  = 'Receive a copy of every email. Leave empty in production.';

// Error
$_['error_permission']          = 'Warning: You do not have permission to modify Review Request Emails!';
$_['error_warning']             = 'Warning: Please check the form carefully for errors!';
$_['error_order_status']        = 'Select at least one order status!';
$_['error_delay']               = 'Enter a number of days between 1 and 60!';
$_['error_reminder_delay']      = 'Enter a number of days between 1 and 30!';
$_['error_start']               = 'Enter a date in the format YYYY-MM-DD!';
$_['error_bcc']                 = 'BCC email does not appear to be valid!';
$_['error_subject']             = 'Subject must be at least 3 characters!';
$_['error_message']             = 'Message is required!';
