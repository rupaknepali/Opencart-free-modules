<?php
// Heading
$_['heading_title']     = 'Delivery Date and Time Slots';

// Text
$_['text_extension']    = 'Extensions';
$_['text_success']      = 'Success: You have modified Delivery Date and Time Slots!';
$_['text_edit']         = 'Settings';
$_['text_upcoming']     = 'Upcoming deliveries';
$_['text_no_upcoming']  = 'No upcoming deliveries yet.';
$_['text_intro']        = 'Customers choose a delivery day and time slot in checkout. Only orders in your processing and complete statuses use up a slot.';
$_['text_delivery']     = 'Delivery slot';
$_['text_unlimited']    = 'unlimited';
$_['text_sunday']       = 'Sunday';
$_['text_monday']       = 'Monday';
$_['text_tuesday']      = 'Tuesday';
$_['text_wednesday']    = 'Wednesday';
$_['text_thursday']     = 'Thursday';
$_['text_friday']       = 'Friday';
$_['text_saturday']     = 'Saturday';

// Column
$_['column_date']       = 'Date';
$_['column_slot']       = 'Time slot';
$_['column_booked']     = 'Booked';
$_['column_orders']     = 'Orders';

// Entry
$_['entry_status']      = 'Status';
$_['entry_required']    = 'Required';
$_['entry_lead']        = 'Earliest delivery (days from today)';
$_['entry_cutoff']      = 'Same-day cutoff time';
$_['entry_days']        = 'Days customers can book ahead';
$_['entry_closed']      = 'No deliveries on';
$_['entry_holidays']    = 'Closed dates';
$_['entry_slots']       = 'Time slots';

// Help
$_['help_required']     = 'Customers must choose a slot before the payment button appears.';
$_['help_lead']         = '0 = same day, 1 = tomorrow at the earliest.';
$_['help_cutoff']       = 'Orders placed at or after this time (store time zone, 24-hour HH:MM) count as the next day. Leave empty for no cutoff.';
$_['help_holidays']     = 'One date per line, as YYYY-MM-DD, for example 2026-12-25.';
$_['help_slots']        = 'One slot per line as START-END|CAPACITY, for example 09:00-12:00|10. Capacity is the number of orders per slot; 0 means unlimited.';

// Error
$_['error_permission']  = 'Warning: You do not have permission to modify Delivery Date and Time Slots!';
$_['error_slots']       = 'Add at least one time slot as START-END|CAPACITY, for example 09:00-12:00|10.';
$_['error_slot_line']   = 'This line is not a valid slot: %s';
$_['error_cutoff']      = 'Enter the cutoff as HH:MM, for example 14:00, or leave it empty.';
$_['error_holiday']     = 'This line is not a valid date: %s';
$_['error_days']        = 'Choose between 1 and 90 days.';
