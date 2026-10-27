<?php
// Heading
$_['heading_title']        = 'AI Shopping Product Feed';

// Text
$_['text_extension']       = 'Extensions';
$_['text_success']         = 'Success: You have modified AI Shopping Product Feed!';
$_['text_edit']            = 'Edit AI Shopping Product Feed';
$_['text_cron_info']       = 'The feed is rebuilt every hour by the <strong>ai_product_feed</strong> job in <a href="%s" class="alert-link">Extensions &gt; Cron Jobs</a>. Run the job there to build it right away.';
$_['text_feed']            = 'Feed URL';
$_['text_preview']         = 'Preview (first 20 items)';
$_['text_generated']       = 'Last built %s: %s items, %s KB.';
$_['text_not_generated']   = 'Not built yet. Run the ai_product_feed cron job, or open the feed URL once.';
$_['text_upload']          = 'OpenAI receives feeds by SFTP, after you are approved as a merchant. Download the file from the feed URL (or from storage/ai_product_feed/ on your server) and upload it with the SFTP details OpenAI gives you. Other AI shopping services and your own tools can fetch the URL directly.';
$_['text_none']            = '--- None ---';
$_['text_product_id']      = 'Product ID';
$_['text_model']           = 'Model';
$_['text_sku']             = 'SKU (from product identifiers)';

// Entry
$_['entry_status']         = 'Status';
$_['entry_item_id']        = 'item_id';
$_['entry_currency']       = 'Currency';
$_['entry_tax']            = 'Prices include tax';
$_['entry_out_of_stock']   = 'Include out-of-stock products';
$_['entry_brand_fallback'] = 'Use store name as brand';
$_['entry_return']         = 'Return policy page';
$_['entry_privacy']        = 'Privacy policy page';
$_['entry_terms']          = 'Terms page';

// Help
$_['help_item_id']         = 'Must never change for a product. Use the same value as your Google Merchant Center feed.';
$_['help_currency']        = 'All prices in the feed use this currency, converted with your OpenCart currency rates.';
$_['help_tax']             = 'US stores normally show prices without tax; EU and UK stores with tax. Match what the product page shows.';
$_['help_out_of_stock']    = 'Out-of-stock products are listed with availability out_of_stock (or backorder / pre_order if Stock Checkout is on).';
$_['help_brand_fallback']  = 'brand is required. Products without a manufacturer get your store name. Turn off to leave those products out instead.';
$_['help_pages']           = 'Sent as return_policy, seller_privacy_policy and seller_tos. Privacy and terms are required if you ever enable checkout in ChatGPT.';

// Error
$_['error_permission']     = 'Warning: You do not have permission to modify AI Shopping Product Feed!';
