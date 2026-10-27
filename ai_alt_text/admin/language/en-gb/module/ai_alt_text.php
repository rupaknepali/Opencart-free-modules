<?php
// Heading
$_['heading_title']      = 'AI Image Alt Text';

// Text
$_['text_extension']     = 'Extensions';
$_['text_success']       = 'Success: You have modified AI Image Alt Text!';
$_['text_edit']          = 'Edit AI Image Alt Text';
$_['text_settings']      = 'Settings';
$_['text_images']        = 'Images';
$_['text_saved']         = 'Success: %s alt texts saved.';
$_['text_info']          = 'Click <strong>Generate</strong> to have Claude look at an image and suggest alt text in each language. Suggestions are put in the fields for you to check. Nothing is saved or shown on your store until you click <strong>Save alt text</strong>. An empty field means the product name is used, as before.';
$_['text_privacy']       = 'Product images are sent to Anthropic\'s API to generate text. Only use this for images you have the right to process this way.';
$_['text_missing']       = 'Only products whose main image has no alt text';
$_['text_main']          = 'Main';
$_['text_ai']            = 'AI';
$_['text_all_languages'] = 'All languages';
$_['text_generating']    = 'Generating %s of %s...';
$_['text_generated']     = 'Done. Check the texts, then click Save alt text.';
$_['text_cost']          = 'Each image is one API request, billed by Anthropic to your API key. Claude Opus 5.5 gives the best descriptions; Claude Sonnet 5.5 and Claude Haiku 5.5 cost less per image. Try a few images with each before running a whole catalog.';

// Entry
$_['entry_status']       = 'Show on store';
$_['entry_api_key']      = 'Anthropic API key';
$_['entry_model']        = 'Model';
$_['entry_language']     = 'Languages';
$_['entry_instructions'] = 'Extra instructions';
$_['entry_name']         = 'Product name';

// Help
$_['help_status']        = 'Replaces the alt attribute of product images on product pages and in product lists with the saved text.';
$_['help_api_key']       = 'Create one at console.anthropic.com under API Keys. It starts with sk-ant-.';
$_['help_language']      = 'Leave all unticked to write alt text in every enabled language.';
$_['help_instructions']  = 'Optional. For example: "Mention the fabric for clothing" or "We sell refurbished phones; say Refurbished".';

// Button
$_['button_generate']    = 'Generate';
$_['button_generate_page'] = 'Generate missing on this page';
$_['button_save_alt']    = 'Save alt text';

// Error
$_['error_warning']      = 'Warning: Please check the form carefully for errors!';
$_['error_permission']   = 'Warning: You do not have permission to modify AI Image Alt Text!';
$_['error_api_key']      = 'Anthropic API keys start with sk-ant-';
$_['error_no_key']       = 'Add your Anthropic API key on the Settings tab first!';
$_['error_image']        = 'Image not found or not readable!';
$_['error_gd']           = 'This image is too large to send as it is, and the PHP GD extension isn\'t installed to resize it.';
$_['error_refusal']      = 'Claude declined to describe this image. Write the alt text by hand.';
$_['error_max_tokens']   = 'The answer was cut off. Try again, or use fewer languages at once.';
$_['error_response']     = 'Unexpected answer from the API. Try again.';
