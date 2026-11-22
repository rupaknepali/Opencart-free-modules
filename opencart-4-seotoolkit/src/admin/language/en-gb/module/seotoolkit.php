<?php
// Heading
$_['heading_title']          = 'SEO Toolkit';

// Text
$_['text_extension']         = 'Extensions';
$_['text_success']           = 'Success: You have modified SEO Toolkit!';
$_['text_edit']              = 'Edit SEO Toolkit';
$_['text_info']              = 'Find and fix what holds your catalog back in search: missing SEO URLs, empty or duplicate meta tags and thin pages. Meta templates fill the gaps automatically, robots rules keep sorted and filtered copies of your pages out of the index, and structured data tells Google who you are.';
$_['text_audit']             = 'SEO audit';
$_['text_settings']          = 'Settings';
$_['text_robots']            = 'robots.txt';
$_['text_meta']              = 'Meta templates';
$_['text_meta_help']         = 'Used for the title and meta description of pages whose own fields are empty. Placeholders: {name}, {store}, {description} (fills the room that\'s left), and for products {brand}, {model}, {price}, {category}. Put words that belong to a placeholder in square brackets, as in {name}[ by {brand}]: they are left out when it is empty. Separators next to an empty placeholder are removed too.';
$_['text_indexing']          = 'Indexing';
$_['text_schema']            = 'Structured data';
$_['text_editor']            = 'Admin forms';
$_['text_mode_empty']        = 'Only when the page\'s own field is empty';
$_['text_mode_always']       = 'Always (replace what\'s written on each page)';
$_['text_type_product']      = 'Products';
$_['text_type_category']     = 'Categories';
$_['text_type_information']  = 'Information pages';
$_['text_type_manufacturer'] = 'Brands';
$_['text_title']             = 'Title';
$_['text_description']       = 'Description';
$_['text_noindex_search']    = 'Search results';
$_['text_noindex_params']    = 'Sorted, filtered and "show 100" copies of category, brand and special pages';
$_['text_noindex_page']      = 'Page 2 and later of category listings';
$_['text_noindex_private']   = 'Account, checkout, compare and affiliate pages';
$_['text_schema_organization'] = 'Organization (name, logo, phone, address, social profiles) on the home page';
$_['text_schema_website']    = 'WebSite with site search on the home page';
$_['text_schema_breadcrumb'] = 'BreadcrumbList on product, category, information, brand and special pages';
$_['text_generated']         = 'Success: %s SEO keywords created.';
$_['text_filled']            = 'Success: meta tags written for %s pages.';
$_['text_robots_saved']      = 'Success: robots.txt saved.';
$_['text_robots_missing']    = 'Your store has no robots.txt yet. Start from the recommended rules and save.';
$_['text_robots_readonly']   = 'robots.txt isn\'t writable. Change its permissions, or copy the text and upload it yourself.';
$_['text_robots_open']       = 'Open robots.txt';
$_['text_robots_help']       = 'Don\'t block pages you want removed from Google: it has to crawl a page to see its noindex. Use the Indexing rules above for sorted and filtered pages.';
$_['text_audit_loading']     = 'Auditing your catalog...';
$_['text_audit_clean']       = 'Nothing to fix here.';
$_['text_issues']            = '%s issues';
$_['text_items']             = '%s enabled';
$_['text_more']              = 'and %s more';
$_['text_confirm_generate']  = 'Create SEO keywords for every product, category, information page and brand that has none, in every store and language?';
$_['text_confirm_fill']      = 'Write meta titles and descriptions from the templates into every empty field? Fields that already have text are not changed.';

// Issues
$_['issue_keyword']          = 'SEO URL keyword missing';
$_['issue_meta_title']       = 'Meta title empty';
$_['issue_meta_description'] = 'Meta description empty';
$_['issue_long_title']       = 'Meta title over 60 characters';
$_['issue_long_description'] = 'Meta description over 160 characters';
$_['issue_duplicate_title']  = 'Same meta title as another page';
$_['issue_thin']             = 'Description under 50 words';
$_['issue_image']            = 'No product image';
$_['help_issue_keyword']     = 'Counted per store and language. Without one the page has an index.php?route=... address.';
$_['help_issue_meta_title']  = 'The meta template covers it on the storefront; Fill writes it into the field.';
$_['help_issue_meta_description'] = 'Google writes its own snippet, often from the menu or footer.';
$_['help_issue_long_title']  = 'Google cuts titles at about 600 pixels, roughly 60 characters.';
$_['help_issue_long_description'] = 'Anything after about 160 characters is cut off in results.';
$_['help_issue_duplicate_title'] = 'Pages that look alike compete with each other.';
$_['help_issue_thin']        = 'Short or copied manufacturer descriptions give Google little reason to rank the page.';
$_['help_issue_image']       = 'No image in search results, Google Shopping or social shares.';

// Entry
$_['entry_status']           = 'Status';
$_['entry_meta_mode']        = 'Use templates';
$_['entry_noindex']          = 'Keep out of Google (noindex)';
$_['entry_schema']           = 'Add';
$_['entry_same_as']          = 'Social profiles';
$_['entry_auto_keyword']     = 'Type SEO keywords for me';
$_['entry_transliterate']    = 'Plain letters in keywords';

// Help
$_['help_noindex']           = 'Adds <meta name="robots" content="noindex, follow">: links on the page are still followed.';
$_['help_same_as']           = 'One address per line (Facebook, Instagram, X, LinkedIn, YouTube...). Name, logo, phone and address come from System > Settings.';
$_['help_schema']            = 'If your theme or another extension already prints one of these, untick it here so it isn\'t there twice.';
$_['help_auto_keyword']      = 'In the product, category, information and brand forms, empty SEO keyword and meta title fields fill in as you type the name.';
$_['help_transliterate']     = 'Café Crème becomes cafe-creme, Футболка becomes futbolka. Off keeps accented, Cyrillic and Greek letters.';

// Button
$_['button_audit']           = 'Run audit';
$_['button_generate']        = 'Generate missing keywords';
$_['button_fill']            = 'Fill empty meta tags';
$_['button_robots_save']     = 'Save robots.txt';
$_['button_robots_recommended'] = 'Use recommended rules';
$_['button_fix_generate']    = 'Generate';
$_['button_fix_fill']        = 'Fill';
$_['button_edit']            = 'Edit';

// Error
$_['error_permission']       = 'Warning: You do not have permission to modify SEO Toolkit!';
$_['error_robots_write']     = 'robots.txt couldn\'t be written. Check the permissions of your store\'s root folder.';
$_['error_robots_size']      = 'robots.txt is limited to 500 KB (Google reads only the first 500 KiB).';
