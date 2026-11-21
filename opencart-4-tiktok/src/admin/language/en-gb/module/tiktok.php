<?php
// Heading
$_['heading_title']      = 'TikTok Pixel and Events API';

// Text
$_['text_extension']     = 'Extensions';
$_['text_success']       = 'Success: You have modified TikTok Pixel and Events API!';
$_['text_edit']          = 'Edit TikTok Pixel and Events API';
$_['text_info']          = 'Tracks PageView, ViewContent, AddToCart, InitiateCheckout and Purchase with the TikTok Pixel, and also sends Purchase from your server with the Events API, so purchases are counted even when the browser blocks the pixel. TikTok counts a purchase once even when both send it.';
$_['text_pixel']         = 'TikTok Pixel';
$_['text_events_api']    = 'Events API (server)';
$_['text_privacy']       = 'Privacy';
$_['text_consent_opencart'] = 'OpenCart cookie bar: track only after the visitor clicks Agree';
$_['text_consent_custom']   = 'Another consent tool: it calls ocTikTokConsent(true) when the visitor agrees';
$_['text_consent_none']     = 'No consent needed: track every visitor';
$_['text_content_product_id'] = 'Product ID (40)';
$_['text_content_model']      = 'Model (product 11)';
$_['text_no_cookie_bar'] = 'OpenCart\'s cookie bar is off, so nobody can agree and nothing will be tracked. Choose a cookie policy page in <a href="%s">System > Settings > Option > Legal</a>, or pick another option.';
$_['text_log']           = 'Recent server-side purchases';
$_['text_no_log']        = 'No purchases sent from the server yet.';

// Entry
$_['entry_status']       = 'Status';
$_['entry_pixel_id']     = 'Pixel ID';
$_['entry_access_token'] = 'Access token';
$_['entry_test_code']    = 'Test event code';
$_['entry_consent']      = 'Consent';
$_['entry_matching']     = 'Advanced matching';
$_['entry_content_id']   = 'Content ID';

// Help
$_['help_pixel_id']      = 'TikTok Ads Manager > Tools > Events > Web Events: the code under your pixel\'s name, like C4ABC123DEF456GHI789.';
$_['help_access_token']  = 'Generated in the pixel\'s Settings tab under Events API. Leave empty to use the pixel only.';
$_['help_test_code']     = 'From Test Events in Events Manager, while you check the setup. Remove it afterwards.';
$_['help_matching']      = 'Sends the customer\'s email and ID, SHA-256 hashed, so TikTok can match more conversions to its users.';
$_['help_content_id']    = 'Must match the IDs in your TikTok catalog, if you have one.';

// Column
$_['column_order']       = 'Order';
$_['column_date']        = 'Sent';
$_['column_result']      = 'TikTok\'s answer';

// Error
$_['error_permission']   = 'Warning: You do not have permission to modify TikTok Pixel and Events API!';
$_['error_warning']      = 'Warning: Please check the form carefully for errors!';
$_['error_pixel_id']     = 'Enter your pixel ID: 10 to 30 letters and numbers.';
