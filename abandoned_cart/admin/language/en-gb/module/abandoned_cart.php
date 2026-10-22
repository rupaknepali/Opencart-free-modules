<?php
// Heading
$_['heading_title']          = 'Abandoned Cart Recovery';

// Text
$_['text_extension']         = 'Extensions';
$_['text_success']           = 'Success: You have modified Abandoned Cart Recovery!';
$_['text_delete']            = 'Success: The cart has been deleted.';
$_['text_edit']              = 'Edit Abandoned Cart Recovery';
$_['text_general']           = 'General';
$_['text_emails']            = 'Emails';
$_['text_coupon']            = 'Coupon';
$_['text_report']            = 'Report';
$_['text_stage']             = 'Email %s';
$_['text_no_coupon']         = '--- No coupon ---';
$_['text_cron_info']         = 'Reminder emails are sent by the <strong>abandoned_cart</strong> job in <a href="%s" class="alert-link">Extensions &gt; Cron Jobs</a>. Your server must run OpenCart\'s <code>cron.php</code> every hour for it to work. You can also run the job by hand from that page while testing.';
$_['text_placeholders']      = 'Placeholders: <code>{firstname}</code> <code>{store_name}</code> <code>{cart}</code> <code>{cart_total}</code> <code>{restore_link}</code> <code>{coupon}</code> <code>{coupon_code}</code> <code>{coupon_discount}</code> <code>{coupon_expiry}</code> <code>{unsubscribe_link}</code>. An unsubscribe line is always added at the bottom of every email.';
$_['text_coupon_info']       = 'A unique, single-use coupon is created for each cart when the selected email is sent. It appears under Marketing &gt; Coupons and is applied automatically by the restore link. Use <code>{coupon}</code> in that email\'s message to show it.';
$_['text_captured']          = 'Carts captured';
$_['text_emails_sent']       = 'Emails sent';
$_['text_recovered']         = 'Carts recovered';
$_['text_rate']              = 'Recovery rate';
$_['text_revenue']           = 'Recovered revenue';
$_['text_status_0']          = 'Open';
$_['text_status_1']          = 'Recovered';
$_['text_status_2']          = 'Ordered without email';
$_['text_status_3']          = 'Unsubscribed';
$_['text_status_4']          = 'Expired';
$_['text_hours']             = 'hours';

// Column
$_['column_email']           = 'Customer';
$_['column_products']        = 'Products';
$_['column_total']           = 'Cart Total';
$_['column_stage']           = 'Emails Sent';
$_['column_clicks']          = 'Clicks';
$_['column_status']          = 'Status';
$_['column_order']           = 'Order';
$_['column_date_modified']   = 'Last Activity';
$_['column_action']          = 'Action';

// Entry
$_['entry_status']           = 'Status';
$_['entry_min_total']        = 'Minimum cart total';
$_['entry_consent']          = 'Require newsletter consent';
$_['entry_retention']        = 'Delete data after (days)';
$_['entry_bcc']              = 'BCC email';
$_['entry_stage_status']     = 'Send this email';
$_['entry_delay']            = 'Send after';
$_['entry_subject']          = 'Subject';
$_['entry_message']          = 'Message';
$_['entry_coupon_stage']     = 'Add coupon to';
$_['entry_coupon_discount']  = 'Discount (%)';
$_['entry_coupon_days']      = 'Valid for (days)';
$_['entry_filter_email']     = 'Email';
$_['entry_filter_status']    = 'Status';

// Help
$_['help_min_total']         = 'Carts below this amount (in %s) are not emailed.';
$_['help_consent']           = 'Only email customers who ticked the newsletter box (logged-in customers, or guests at checkout).';
$_['help_retention']         = 'Cart data older than this is deleted. Minimum 15. Open carts with no activity for 14 days are marked Expired and get no more emails.';
$_['help_bcc']               = 'Receive a copy of every reminder. Leave empty in production.';
$_['help_delay']             = 'Hours after the customer\'s last cart activity. Use 0 while testing.';

// Error
$_['error_permission']       = 'Warning: You do not have permission to modify Abandoned Cart Recovery!';
$_['error_warning']          = 'Warning: Please check the form carefully for errors!';
$_['error_bcc']              = 'BCC email does not appear to be valid!';
$_['error_retention']        = 'Keep data for at least 15 days!';
$_['error_subject']          = 'Subject must be at least 3 characters!';
$_['error_message']          = 'Message is required!';
$_['error_coupon_discount']  = 'Discount must be between 1 and 100!';
