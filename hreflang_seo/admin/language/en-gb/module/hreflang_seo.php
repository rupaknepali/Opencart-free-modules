<?php
// Heading
$_['heading_title']      = 'Hreflang & Canonical SEO';

// Text
$_['text_extension']     = 'Extensions';
$_['text_success']       = 'Success: You have modified Hreflang & Canonical SEO!';
$_['text_edit']          = 'Edit Hreflang & Canonical SEO';
$_['text_info']          = 'Adds a <code>&lt;link rel="alternate" hreflang="..."&gt;</code> tag for every enabled language, plus <code>x-default</code>, to the home, product, category, brand, specials, information, contact and blog pages. A language is skipped on a page if that product, category or article has no name in that language.';
$_['text_seo_url']       = 'SEO URLs are off (System &gt; Settings &gt; Server). Hreflang still works with ?route= URLs, but turn SEO URLs on and add keywords in every language for the best results.';
$_['text_disabled']      = 'disabled';
$_['text_languages']     = 'Hreflang codes';

// Column
$_['column_language']    = 'Language';
$_['column_code']        = 'OpenCart code';
$_['column_hreflang']    = 'hreflang value';

// Entry
$_['entry_status']       = 'Status';
$_['entry_canonical']    = 'Add missing canonicals';
$_['entry_noindex']      = 'Noindex utility pages';
$_['entry_x_default']    = 'x-default language';

// Help
$_['help_canonical']     = 'OpenCart 4 has no canonical tag on the home page, information pages, contact page or blog articles. This adds one. Pages that already have one are left alone.';
$_['help_noindex']       = 'Adds &lt;meta name="robots" content="noindex, follow"&gt; to search results, compare, cart, checkout, account, affiliate and sitemap pages.';
$_['help_x_default']     = 'The version shown to visitors whose language you don\'t have. Usually your main language.';
$_['help_hreflang']      = 'Leave empty to use the OpenCart code (en-gb becomes en-GB). Use just the language (en) if one English version serves every country, or a different region (en-US) if that is your market.';

// Error
$_['error_warning']      = 'Warning: Please check the form carefully for errors!';
$_['error_permission']   = 'Warning: You do not have permission to modify Hreflang & Canonical SEO!';
$_['error_code']         = 'Use a language code like en, en-US or zh-Hant-TW!';
