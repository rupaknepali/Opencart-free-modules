<?php
// Heading
$_['heading_title']        = 'Order Tracking';

// Text
$_['text_extension']       = 'Extensions';
$_['text_success']         = 'Success: You have modified Order Tracking!';
$_['text_edit']            = 'Edit Order Tracking';
$_['text_info']            = 'Adds a Shipment tracking card to Sales > Orders > (order). Pick the carrier, paste the tracking number and the extension saves it, adds an order history entry with the tracking link and notifies the customer by email (through OpenCart\'s own order history). Customers also see a Track package button in My Account > Order History.';
$_['text_default_comment'] = "Your order is on its way with {carrier}.\nTracking number: {tracking}\nTrack your package: {url}";
$_['text_carriers']        = 'Carriers';
$_['text_none']            = '--- Don\'t change the status ---';
$_['text_tracking']        = 'Shipment tracking';
$_['text_no_tracking']     = 'No tracking numbers yet.';
$_['text_added']           = 'Tracking number saved.';
$_['text_removed']         = 'Tracking number removed.';
$_['text_history_note']    = 'Adds an order history entry with this status and the tracking link, using the form below.';
$_['text_confirm_remove']  = 'Remove this tracking number? The history entry and any email already sent stay as they are.';

// Column
$_['column_carrier']       = 'Carrier';
$_['column_tracking']      = 'Tracking number';
$_['column_date_added']    = 'Added';
$_['column_action']        = 'Action';

// Entry
$_['entry_status']         = 'Status';
$_['entry_carrier_name']   = 'Name';
$_['entry_carrier_url']    = 'Tracking link';
$_['help_carriers']        = 'Use {tracking} where the tracking number goes. Spaces and dashes are removed from the number first.';
$_['entry_order_status']   = 'Order status';
$_['help_order_status']    = 'The status the order history entry gets when you add a tracking number.';
$_['entry_notify']         = 'Notify customer';
$_['help_notify']          = 'Default for the Notify switch. OpenCart emails the comment below to the customer.';
$_['entry_comment']        = 'History comment';
$_['help_comment']         = 'Placeholders: {carrier}, {tracking} and {url}.';
$_['entry_carrier']        = 'Carrier';
$_['entry_tracking']       = 'Tracking number';
$_['entry_add_history']    = 'Add history and notify';

// Button
$_['button_add_carrier']   = 'Add carrier';
$_['button_add_tracking']  = 'Add tracking';
$_['button_track']         = 'Track';

// Error
$_['error_permission']     = 'Warning: You do not have permission to modify Order Tracking!';
$_['error_warning']        = 'Warning: Please check the form carefully for errors!';
$_['error_carrier']        = 'Each carrier needs a name and an http(s) tracking link that contains {tracking}.';
$_['error_carriers']       = 'Warning: Add at least one carrier!';
$_['error_order']          = 'Order not found!';
$_['error_carrier_select'] = 'Choose a carrier!';
$_['error_tracking']       = 'Tracking numbers are 4 to 40 letters, digits, spaces or dashes.';
