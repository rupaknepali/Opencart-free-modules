<?php
// Heading
$_['heading_title']         = 'PDF Invoice';

// Text
$_['text_extension']        = 'Extensions';
$_['text_success']          = 'Success: PDF Invoice settings saved!';
$_['text_edit']             = 'Edit PDF Invoice';
$_['text_default_footer']   = 'Thank you for your business!';
$_['text_a4']               = 'A4';
$_['text_letter']           = 'US Letter';
$_['text_mail_off']         = 'Mail is not set up yet, so invoices can be downloaded but not emailed. Set a mail engine in System > Settings > edit store > Mail.';
$_['text_download']         = 'PDF invoice';
$_['text_send']             = 'Email PDF invoice to customer';
$_['text_sent']             = 'Success: the PDF invoice was emailed to the customer.';
$_['text_last_sent']        = 'Last emailed to %s on %s';
$_['text_never_sent']       = 'Not emailed yet';

// Entry
$_['entry_status']          = 'Status';
$_['entry_send_statuses']   = 'Email the invoice when an order reaches';
$_['entry_invoice_no']      = 'Create invoice number automatically';
$_['entry_account']         = 'Download button in customer account';
$_['entry_logo']            = 'Show store logo';
$_['entry_paper']           = 'Paper size';
$_['entry_tax_id']          = 'Tax ID line';
$_['entry_footer']          = 'Footer note';

// Help
$_['help_send_statuses']    = 'Each order is emailed once, the first time it reaches one of these statuses. Leave all unticked to only send from the order page.';
$_['help_invoice_no']       = 'Gives the order the next invoice number (Sales > Orders > Generate) before the PDF is made.';
$_['help_account']          = 'Shown on Account > Order History > order for orders in a processing or complete status.';
$_['help_tax_id']           = 'Printed under your store address, e.g. VAT No. GB123456789 or ABN 12 345 678 901.';

// Error
$_['error_permission']      = 'Warning: You do not have permission to modify PDF Invoice!';
$_['error_mail']            = 'Mail is not set up. Choose a mail engine in System > Settings > edit store > Mail.';
$_['error_order']           = 'The order was not found or has no valid email address.';
$_['error_send']            = 'The email could not be sent. See System > Maintenance > Error Logs.';

// Text printed on the PDF and in the invoice email
$_['pdf_title']           = 'Invoice';
$_['pdf_invoice_no']      = 'Invoice No.';
$_['pdf_order_id']        = 'Order';
$_['pdf_date']            = 'Date';
$_['pdf_date_format']     = 'M j, Y';
$_['pdf_bill_to']         = 'Bill to';
$_['pdf_ship_to']         = 'Ship to';
$_['pdf_payment_method']  = 'Payment:';
$_['pdf_shipping_method'] = 'Shipping:';
$_['pdf_product']         = 'Product';
$_['pdf_model']           = 'Model';
$_['pdf_quantity']        = 'Qty';
$_['pdf_price']           = 'Unit price';
$_['pdf_total']           = 'Total';
$_['pdf_comment']         = 'Order comment';
$_['pdf_page']            = 'Page %s of %s';
$_['pdf_mail_subject']    = 'Invoice %s from %s';
$_['pdf_mail_greeting']   = 'Hello %s,';
$_['pdf_mail_body']       = 'Thank you for your order. Your invoice %s for order #%s is attached as a PDF.';
