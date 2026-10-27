<?php
// Heading
$_['heading_title']    = 'Bulk Price & Stock CSV';

// Text
$_['text_extension']   = 'Extensions';
$_['text_export']      = 'Export products';
$_['text_export_info'] = 'Downloads every product with its ID, variant master ID, model, SKU, name, price (without tax), quantity and status. Open it in Excel, Google Sheets or LibreOffice, change the price, quantity or status columns, and import it below.';
$_['text_import']      = 'Import changes';
$_['text_import_info'] = 'Only the <strong>price</strong>, <strong>quantity</strong> and <strong>status</strong> columns are imported. Empty cells are left unchanged. Columns can be in any order and extra columns are ignored. Comma, semicolon and tab separated files all work.';
$_['text_backup']      = 'Back up your database first (System &gt; Maintenance &gt; Backup / Restore). Imports change products directly and can\'t be undone from here.';
$_['text_preview']     = 'Preview: %s products would change. Nothing has been saved yet.';
$_['text_applied']     = 'Success: %s products updated.';
$_['text_changes']     = 'Changes';
$_['text_errors']      = 'Rows skipped';
$_['text_unchanged']   = '%s rows already match the store and were skipped.';
$_['text_more']        = 'Only the first 500 changes are listed. All %s will be applied.';
$_['text_variant']     = 'variant';
$_['text_variant_info'] = 'Variant products keep their own price and quantity only if the matching override is on in the variant\'s form; otherwise the master product\'s values are copied to them when the master is saved.';
$_['text_enabled']     = 'Enabled';
$_['text_disabled']    = 'Disabled';
$_['text_no_changes']  = 'No changes found.';

// Column
$_['column_line']      = 'Line';
$_['column_product']   = 'Product';
$_['column_price']     = 'Price';
$_['column_quantity']  = 'Quantity';
$_['column_status']    = 'Status';
$_['column_message']   = 'Problem';

// Entry
$_['entry_file']       = 'CSV file';
$_['entry_match']      = 'Match products by';
$_['entry_apply']      = 'Save changes';

// Help
$_['help_file']        = 'Maximum %s MB, up to 20,000 rows.';
$_['help_match']       = 'Product ID is safest. Model and SKU must be unique: rows that match more than one product are skipped.';
$_['help_apply']       = 'Leave off to see a preview. Switch on and import the same file again to save.';

// Button
$_['button_export']    = 'Download CSV';
$_['button_import']    = 'Import';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify Bulk Price & Stock CSV!';
$_['error_upload']     = 'Please choose a CSV file to upload!';
$_['error_filetype']   = 'The file must have a .csv extension!';
$_['error_size']       = 'The file is larger than %s MB!';
$_['error_empty']      = 'The file is empty!';
$_['error_match']      = 'The file has no "%s" column. Add it or choose another "Match products by" option.';
$_['error_columns']    = 'The file needs at least one of these columns: price, quantity, status.';
$_['error_rows']       = 'Stopped after 20,000 rows. Split the file and import the rest separately.';
$_['error_key']        = 'The %s cell is empty.';
$_['error_not_found']  = 'No product with %s "%s".';
$_['error_duplicate']  = '%s "%s" matches %s products. Use product_id instead.';
$_['error_twice']      = '"%s" is already in the file on line %s.';
$_['error_price']      = 'Price "%s" is not a number.';
$_['error_quantity']   = 'Quantity "%s" is not a whole number.';
$_['error_status']     = 'Status "%s" must be 1/0, yes/no or enabled/disabled.';
