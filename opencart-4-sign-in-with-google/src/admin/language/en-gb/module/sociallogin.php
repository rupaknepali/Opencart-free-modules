<?php
// Heading
$_['heading_title']          = 'Sign in with Google';

// Text
$_['text_extension']         = 'Extensions';
$_['text_success']           = 'Success: You have modified Sign in with Google!';
$_['text_edit']              = 'Edit Sign in with Google';
$_['text_info']              = 'Adds a "Continue with Google" button to the login, register and checkout pages. Create an OAuth client (type: Web application) in Google Cloud Console > APIs & Services > Credentials, add the redirect URI below, then paste the Client ID and Client secret here.';
$_['text_google']            = 'Google OAuth client';
$_['text_pages']             = 'Where to show the button';
$_['text_accounts']          = 'Accounts';
$_['text_copy']              = 'Copy';
$_['text_https']             = 'Your store URL is not HTTPS. Google only accepts http:// redirect URIs for localhost, so a live store must use HTTPS.';

// Entry
$_['entry_status']           = 'Status';
$_['entry_redirect_uri']     = 'Authorized redirect URI';
$_['help_redirect_uri']      = 'Paste this exactly (same http/https, www or not) into the OAuth client in Google Cloud Console.';
$_['entry_javascript_origin'] = 'Authorized JavaScript origin';
$_['help_javascript_origin'] = 'Optional for this extension (it doesn\'t use Google\'s JavaScript), but harmless to add.';
$_['entry_client_id']        = 'Client ID';
$_['entry_client_secret']    = 'Client secret';
$_['help_client_secret']     = 'Stored in your OpenCart settings table. Never put it in a theme file or JavaScript.';
$_['entry_login']            = 'Login page';
$_['entry_register']         = 'Register page';
$_['entry_checkout']         = 'Checkout';
$_['help_checkout']          = 'Shown above the checkout account form for guests. After signing in they come back to checkout.';
$_['entry_link_existing']    = 'Link existing accounts';
$_['help_link_existing']     = 'If a customer already has an account with the same email address, and Google says that address is verified, sign them in to that account. Off: they must log in with their password.';
$_['entry_create']           = 'Create new accounts';
$_['help_create']            = 'Create a customer account for new Google users, in the default customer group. If that group needs approval, they can sign in once you approve them.';

// Error
$_['error_permission']       = 'Warning: You do not have permission to modify Sign in with Google!';
$_['error_warning']          = 'Warning: Please check the form carefully for errors!';
$_['error_client_id']        = 'Enter the Client ID from Google Cloud Console. It ends in .apps.googleusercontent.com';
$_['error_client_secret']    = 'Enter the Client secret!';
