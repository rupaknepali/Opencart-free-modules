<?php
// Heading
$_['heading_title']         = 'Order Webhooks';

// Text
$_['text_extension']        = 'Extensions';
$_['text_success']          = 'Success: You have modified Order Webhooks!';
$_['text_edit']             = 'Edit Order Webhooks';
$_['text_endpoints']        = 'Endpoints';
$_['text_deliveries']       = 'Delivery Log';
$_['text_cron_info']        = 'Webhooks are sent right after the event. Failed deliveries are retried by the <strong>order_webhooks</strong> job in <a href="%s" class="alert-link">Extensions &gt; Cron Jobs</a> (after 1, 5, 30, 120, 300, 600 and 1440 minutes). For retries to work your server must run OpenCart\'s <code>cron.php</code>, ideally every 5 minutes.';
$_['text_signature']        = 'Every request has <code>webhook-id</code>, <code>webhook-timestamp</code> and <code>webhook-signature</code> headers (Standard Webhooks). Verify the signature with the endpoint\'s secret before trusting the payload.';
$_['text_order_created']    = 'order.created';
$_['text_order_status']     = 'order.status_changed';
$_['text_customer_created'] = 'customer.created';
$_['text_test_sent']        = 'Test webhook sent. Response: HTTP %s';
$_['text_resent']           = 'Delivery sent again. Response: HTTP %s';
$_['text_status_0']         = 'Pending';
$_['text_status_1']         = 'Delivered';
$_['text_status_2']         = 'Failed';
$_['text_no_endpoints']     = 'No endpoints yet. Click the + button to add one.';

// Column
$_['column_url']            = 'Endpoint URL';
$_['column_secret']         = 'Signing secret';
$_['column_event']          = 'Events';
$_['column_status']         = 'Status';
$_['column_action']         = 'Action';
$_['column_date_added']     = 'Created';
$_['column_attempts']       = 'Attempts';
$_['column_response']       = 'Last response';
$_['column_next']           = 'Next attempt';

// Entry
$_['entry_status']          = 'Status';
$_['entry_retention']       = 'Keep log for (days)';

// Help
$_['help_retention']        = 'Delivered and failed log entries older than this are deleted by the cron job.';

// Button
$_['button_test']           = 'Send test';
$_['button_resend']         = 'Resend';
$_['button_secret']         = 'New secret';

// Error
$_['error_warning']         = 'Warning: Please check the form carefully for errors!';
$_['error_permission']      = 'Warning: You do not have permission to modify Order Webhooks!';
$_['error_url']             = 'Endpoint URL must be a valid https:// URL!';
$_['error_event']           = 'Choose at least one event!';
$_['error_endpoint']        = 'Save the endpoint before sending a test!';
$_['error_delivery']        = 'Delivery not found!';
