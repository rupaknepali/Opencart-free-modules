<?php
// Heading
$_['heading_title']      = 'Estimated Delivery Date';

// Text
$_['text_extension']     = 'Extensions';
$_['text_success']       = 'Success: You have modified Estimated Delivery Date!';
$_['text_edit']          = 'Edit Estimated Delivery Date';
$_['text_info']          = 'Shows "Order within 3 hrs 20 mins and get it Thu 20 Nov – Mon 24 Nov" on product pages. It is calculated from your own dispatch rules below; it does not ask a carrier.';
$_['text_clock']         = 'Store time now: %s (%s). Change the time zone in System > Settings > Local.';
$_['text_hide']          = 'Hide the estimate';
$_['text_show']          = 'Show it anyway (backorders ship quickly)';

// Entry
$_['entry_cutoff']       = 'Order cut-off time';
$_['entry_processing']   = 'Handling time (days)';
$_['entry_transit']      = 'Delivery time (days)';
$_['entry_days']         = 'Dispatch and delivery days';
$_['entry_holidays']     = 'Holidays';
$_['entry_format']       = 'Date format';
$_['entry_stock']        = 'Out-of-stock products';
$_['entry_status']       = 'Status';

// Help
$_['help_cutoff']        = '24-hour time, e.g. 14:00. Orders after this ship from the next working day.';
$_['help_processing']    = 'Working days to pack an order before it ships. 0 = it ships the same day.';
$_['help_transit']       = 'Shortest and longest working days in transit. The range is shown as "Thu 20 Nov – Mon 24 Nov"; use the same number twice for a single date.';
$_['help_days']          = 'Days you dispatch, and the carrier delivers. Other days are skipped.';
$_['help_holidays']      = 'One date per line, as YYYY-MM-DD. They are skipped like weekends.';
$_['help_format']        = 'PHP date() format. D j M = Thu 20 Nov; l, F j = Thursday, November 20.';

// Error
$_['error_permission']   = 'Warning: You do not have permission to modify Estimated Delivery Date!';
$_['error_cutoff']       = 'Enter the cut-off time as HH:MM, e.g. 14:00!';
$_['error_transit']      = 'Delivery time must be 0 to 60 days, with the longest at least the shortest!';
$_['error_days']         = 'Choose at least one working day!';
$_['error_holiday']      = 'Holiday "%s" is not a date in YYYY-MM-DD format!';
