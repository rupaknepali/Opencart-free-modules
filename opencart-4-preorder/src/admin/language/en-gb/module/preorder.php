<?php
// Heading
$_['heading_title']          = 'Pre-order';

// Text
$_['text_extension']         = 'Extensions';
$_['text_success']           = 'Success: Pre-order settings saved!';
$_['text_edit']              = 'Edit Pre-order';
$_['text_default_button']    = 'Pre-order now';
$_['text_default_ship']      = 'Ships by %s';
$_['text_default_no_date']   = 'Ships as soon as it is back in stock';
$_['text_stock_checkout_off'] = 'Your store blocks checkout of out-of-stock products (System > Settings > Option > Stock Checkout: No). Pre-order products are the exception: customers can check out with them, but not with other out-of-stock products.';
$_['text_stock_checkout_on']  = 'Your store allows checkout of every out-of-stock product (System > Settings > Option > Stock Checkout: Yes). Pre-order still adds the button, ship date and order notes, but other out-of-stock products can be bought too. Set Stock Checkout to No to sell only pre-order products when stock is gone.';
$_['text_products']          = 'Products with pre-order switched on';
$_['text_no_products']       = 'No products yet. Open a product in Catalog > Products and use its Pre-order tab.';
$_['text_active']            = 'On pre-order';
$_['text_waiting']           = 'In stock (sells normally)';
$_['text_no_date']           = 'No date';
$_['text_module_off']        = 'The Pre-order extension is switched off in Extensions > Extensions > Modules, so these settings have no effect yet.';
$_['text_order_title']       = 'Pre-order items';
$_['text_order_help']        = 'These lines were on pre-order when the customer ordered. The date is the one shown to the customer.';

// Column
$_['column_name']            = 'Product';
$_['column_stock']           = 'Quantity';
$_['column_ship_date']       = 'Ship date';
$_['column_ordered']         = 'Pre-ordered';
$_['column_status']          = 'Status';
$_['column_quantity']        = 'Quantity';

// Tab and entries on the product form
$_['tab_preorder']           = 'Pre-order';
$_['entry_preorder_status']  = 'Allow pre-orders';
$_['entry_ship_date']        = 'Expected ship date';
$_['entry_note']             = 'Note for customers';
$_['help_preorder_status']   = 'When the quantity is 0 or less, the product shows a Pre-order button and can be checked out. When stock comes back it sells normally again.';
$_['help_ship_date']         = 'Optional. Shown as "Ships by ...". After this date passes, the no-date text is shown instead.';
$_['help_note']              = 'Optional, shown under the Pre-order button, e.g. "Second batch arrives from the factory in December."';

// Entry
$_['entry_status']           = 'Status';
$_['entry_button']           = 'Button text';
$_['entry_ship_text']        = 'Ship date text';
$_['entry_no_date_text']     = 'Text without a date';
$_['help_ship_text']         = '%s is replaced with the date.';

// Error
$_['error_permission']       = 'Warning: You do not have permission to modify Pre-order!';
$_['error_ship_text']        = 'The ship date text must contain %s exactly once (it is replaced with the date).';
