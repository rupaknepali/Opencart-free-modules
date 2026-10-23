<?php
// Heading
$_['heading_title']      = 'WebP Images';

// Text
$_['text_extension']     = 'Extensions';
$_['text_success']       = 'Success: You have modified WebP Images!';
$_['text_edit']          = 'Edit WebP Images';
$_['text_settings']      = 'Settings';
$_['text_server']        = 'Server support';
$_['text_files']         = 'Generated files';
$_['text_info']          = 'Every resized image OpenCart makes in <code>image/cache/</code> gets a WebP (or AVIF) copy the first time a page shows it. Your original uploads are never changed. Emails sent by cron jobs keep using JPEG/PNG.';
$_['text_webp']          = 'WebP (recommended)';
$_['text_avif']          = 'AVIF (smaller, slower to create)';
$_['text_yes']           = 'Yes';
$_['text_no']            = 'No';
$_['text_files_info']    = 'Generated files sit next to OpenCart\'s own cached images, for example <code>product-500x500.jpg.webp</code>. Deleting them is safe: they are created again on the next page view. Run "Delete" after changing the quality or format.';
$_['text_scan']          = '%s converted images. Originals: %s. Served now: %s (%s%% smaller).';
$_['text_clear']         = 'Success: %s generated images deleted.';
$_['text_cache_note']    = 'If you use a page cache or a CDN such as Cloudflare, purge it after enabling or disabling this extension so pages pick up the new image URLs.';

// Entry
$_['entry_status']       = 'Status';
$_['entry_format']       = 'Format';
$_['entry_quality']      = 'Quality';
$_['entry_gd']           = 'GD library';
$_['entry_webp']         = 'WebP support';
$_['entry_avif']         = 'AVIF support';
$_['entry_php']          = 'PHP version';

// Help
$_['help_format']        = 'Every current browser shows both. WebP is the safe choice; AVIF is usually 20-30% smaller still but takes several times longer to create the first time.';
$_['help_quality']       = '75-85 looks the same as the original for product photos. Lower is smaller. Between 30 and 100.';

// Button
$_['button_scan']        = 'Calculate savings';
$_['button_clear']       = 'Delete generated images';

// Error
$_['error_permission']   = 'Warning: You do not have permission to modify WebP Images!';
$_['error_warning']      = 'Warning: Please check the form carefully for errors!';
$_['error_quality']      = 'Quality must be between 30 and 100!';
$_['error_webp']         = 'Your PHP GD library was built without WebP support. Ask your host to enable it.';
$_['error_avif']         = 'Your PHP version or GD library does not support AVIF. Use WebP instead.';
