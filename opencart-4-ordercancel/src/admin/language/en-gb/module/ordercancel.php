<?php
// Heading
$_['heading_title']        = 'Customer Order Cancellation';

// Text
$_['text_extension']       = 'Extensions';
$_['text_success']         = 'Success: Customer Order Cancellation settings saved!';
$_['text_edit']            = 'Edit Customer Order Cancellation';
$_['text_default_reasons'] = "Ordered by mistake\nFound a better price\nDelivery takes too long\nWant to change the order\nOther";
$_['text_recent']          = 'Recent cancellations by customers';
$_['text_no_recent']       = 'No customer has cancelled an order yet.';
$_['text_no_limit']        = '0 = no time limit';
$_['text_restock']         = 'OpenCart puts the stock back by itself when an order moves from a processing or complete status (System > Settings > Option) to a status that is neither, such as Canceled. Refunds are not automatic: refund the payment in your payment provider.';
$_['text_card_title']      = 'Cancelled by the customer';
$_['text_card_help']       = 'Refund the payment in your payment provider if the customer already paid.';

// Column
$_['column_order']         = 'Order';
$_['column_customer']      = 'Customer';
$_['column_reason']        = 'Reason';
$_['column_comment']       = 'Comment';
$_['column_date']          = 'Cancelled';

// Entry
$_['entry_status']         = 'Status';
$_['entry_statuses']       = 'Can cancel while the order is';
$_['entry_hours']          = 'Time limit (hours after ordering)';
$_['entry_cancel_status']  = 'Set the order to';
$_['entry_reasons']        = 'Reasons';
$_['entry_reason_required'] = 'Reason required';
$_['entry_notify']         = 'Email the store owner';

// Help
$_['help_statuses']        = 'Pick the statuses before you ship, usually Pending and Processing. Never pick Shipped or Complete.';
$_['help_cancel_status']   = 'The status the order gets. The customer receives the normal order status email.';
$_['help_reasons']         = 'One per line. Shown as a list in the cancel form. Leave empty to only ask for a comment.';
$_['help_notify']          = 'Sends an alert to the store email and the additional alert emails (System > Settings > Mail).';

// Error
$_['error_permission']     = 'Warning: You do not have permission to modify Customer Order Cancellation!';
$_['error_statuses']       = 'Pick at least one order status.';
$_['error_cancel_status']  = 'The cancel status cannot be one of the statuses that allow cancelling.';
