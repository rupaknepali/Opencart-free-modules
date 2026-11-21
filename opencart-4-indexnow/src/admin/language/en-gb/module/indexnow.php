<?php
// Heading
$_['heading_title']      = 'IndexNow';

// Text
$_['text_extension']     = 'Extensions';
$_['text_success']       = 'Success: You have modified IndexNow!';
$_['text_regenerated']   = 'Success: A new key was created. Submissions use it from now on.';
$_['text_submitted']     = 'Success: %s URLs submitted.';
$_['text_edit']          = 'Edit IndexNow';
$_['text_info']          = 'IndexNow tells search engines the moment a page is added, changed or deleted, instead of waiting for them to crawl it. One submission is shared by Bing, Yandex, Seznam, Naver and Yep. Google doesn\'t use IndexNow: keep your sitemap for Google.';
$_['text_settings']      = 'Settings';
$_['text_key']           = 'Your key';
$_['text_log']           = 'Recent submissions';
$_['text_key_root']      = 'Key file in your store root: search engines can verify it.';
$_['text_key_route']     = 'The store root isn\'t writable, so the key is served from a storefront address and each submission says where to find it.';
$_['text_open_key']      = 'Open the key file';
$_['text_no_log']        = 'Nothing submitted yet. Save a product, or click Submit all URLs.';
$_['text_more']          = '+ %s more';
$_['text_product']       = 'Products';
$_['text_category']      = 'Categories';
$_['text_information']   = 'Information pages';
$_['text_manufacturer']  = 'Brands';
$_['text_trigger_add']   = 'Added';
$_['text_trigger_edit']  = 'Saved';
$_['text_trigger_delete']= 'Deleted';
$_['text_trigger_all']   = 'Submit all';
$_['text_confirm_all']   = 'Submit every enabled page now? Do this once after installing, not every day.';

// Column
$_['column_date']        = 'Date';
$_['column_trigger']     = 'Why';
$_['column_urls']        = 'URLs';
$_['column_result']      = 'Result';

// Entry
$_['entry_status']       = 'Status';
$_['entry_endpoint']     = 'Send to';
$_['entry_types']        = 'Submit';
$_['entry_key']          = 'Key';

// Help
$_['help_endpoint']      = 'Engines share submissions with each other, so one is enough.';
$_['help_types']         = 'Submitted when you add, save or delete one in the admin. Disabled items are skipped until you enable them.';
$_['help_key']           = 'Proves to search engines that the submissions come from you. Create a new one only if it has been misused.';

// Button
$_['button_submit_all']  = 'Submit all URLs';
$_['button_regenerate']  = 'New key';

// Response codes (from the IndexNow documentation)
$_['code_200']           = 'OK: URLs received';
$_['code_202']           = 'Accepted: key will be checked';
$_['code_400']           = 'Bad request';
$_['code_403']           = 'Key not valid: the key file wasn\'t found or doesn\'t match';
$_['code_422']           = 'URLs don\'t belong to this host or the key doesn\'t match';
$_['code_429']           = 'Too many requests';

// Error
$_['error_permission']   = 'Warning: You do not have permission to modify IndexNow!';
$_['error_status']       = 'Turn Status on and save first.';
$_['error_submit']       = 'The search engine answered %s: %s';
$_['error_nothing']      = 'There were no enabled pages to submit.';
