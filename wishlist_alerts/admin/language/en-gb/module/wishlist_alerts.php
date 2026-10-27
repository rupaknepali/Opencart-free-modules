<?php
// Heading
$_['heading_title']        = 'Wishlist Alerts & Reward Points';

// Text
$_['text_extension']       = 'Extensions';
$_['text_success']         = 'Success: You have modified Wishlist Alerts & Reward Points!';
$_['text_edit']            = 'Edit Wishlist Alerts & Reward Points';
$_['text_general']         = 'General';
$_['text_reward_tab']      = 'Reward Points';
$_['text_reward_info']     = 'OpenCart only adds the reward points a customer earned when you click the reward button on each order (Sales &gt; Orders &gt; order info). With this on, points are added automatically when the order reaches one of your Complete Order Statuses (System &gt; Settings &gt; Option), and removed again if the order is later cancelled or refunded. Points per product are set on the product\'s Reward Points tab, per customer group.';
$_['text_email']           = 'Email';
$_['text_wished']          = 'Most Wished';
$_['text_cron_info']       = 'Alerts are sent once a day by the <strong>wishlist_alerts</strong> job in <a href="%s" class="alert-link">Extensions &gt; Cron Jobs</a>. Your server must run OpenCart\'s <code>cron.php</code>. The first run only records today\'s prices and stock, so the first emails go out on the second run.';
$_['text_statistics']      = '%s products on %s customers\' wishlists. %s alert emails sent in the last 30 days.';
$_['text_placeholders']    = 'Placeholders: <code>{firstname}</code> <code>{store_name}</code>. The list of products, with old and new prices and a link to each, is added below the message automatically.';
$_['text_wished_info']     = 'Products saved to the most wishlists. A good list for your next sale: these customers have already said they want them.';

// Column
$_['column_product']       = 'Product';
$_['column_model']         = 'Model';
$_['column_price']         = 'Price';
$_['column_quantity']      = 'Quantity';
$_['column_total']         = 'Wishlists';

// Entry
$_['entry_status']         = 'Status';
$_['entry_price_drop']     = 'Price drop alerts';
$_['entry_min_drop']       = 'Minimum drop (%)';
$_['entry_restock']        = 'Back in stock alerts';
$_['entry_newsletter']     = 'Only newsletter subscribers';
$_['entry_reward']         = 'Add earned points automatically';
$_['entry_cooldown']       = 'Days between emails';
$_['entry_max_emails']     = 'Max emails per run';
$_['entry_subject']        = 'Subject';
$_['entry_message']        = 'Message';

// Help
$_['help_min_drop']        = 'Compared with the price when we last checked, including specials for the customer\'s group. 10% avoids emails about tiny changes.';
$_['help_newsletter']      = 'Price-drop emails are marketing in most countries. Keep this on unless your privacy policy and sign-up form cover wishlist emails.';
$_['help_cooldown']        = 'A customer gets at most one alert email in this many days, however many products change.';
$_['help_max_emails']      = 'Protects your SMTP account from sending limits. The rest are sent the next day.';

// Error
$_['error_warning']        = 'Warning: Please check the form carefully for errors!';
$_['error_permission']     = 'Warning: You do not have permission to modify Wishlist Alerts & Reward Points!';
$_['error_subject']        = 'Subject must be at least 3 characters!';
