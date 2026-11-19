<?php
// Heading
$_['heading_title']          = 'Content Security Policy';

// Text
$_['text_extension']         = 'Extensions';
$_['text_success']           = 'Success: You have modified Content Security Policy!';
$_['text_edit']              = 'Edit Content Security Policy';
$_['text_info']              = 'Sends a Content-Security-Policy header with your storefront pages, telling browsers which scripts, styles, images and frames they may load. Start with the Report-only policy: nothing is blocked, and every violation shows up under Violations. When the list stays empty for a few days of normal traffic, turn on the Enforced policy.';
$_['text_general']           = 'General';
$_['text_enforce']           = 'Enforced policy';
$_['text_report']            = 'Report-only policy';
$_['text_violations']        = 'Violations';
$_['text_enforce_info']      = 'The browser BLOCKS anything this policy does not allow. Test it as Report-only first.';
$_['text_report_info']       = 'The browser blocks nothing and reports what this policy would have blocked. Safe to run on a live store.';
$_['text_preset']            = 'Load preset:';
$_['text_starter']           = 'OpenCart starter';
$_['text_strict']            = 'Strict scripts';
$_['text_copy_report']       = 'Copy from Report-only';
$_['text_clear_policy']      = 'Clear';
$_['text_preview']           = 'Header preview';
$_['text_preview_help']      = 'The nonce, handler hashes and report URLs are added on each page.';
$_['text_local']             = 'Built-in log (Violations tab)';
$_['text_remote']            = 'External service (report-uri.com, Sentry, URIports...)';
$_['text_off']               = 'Off';
$_['text_policy_enforce']    = 'Enforced';
$_['text_policy_report']     = 'Report-only';
$_['text_no_reports']        = 'No violations reported. Browse your store (product page, cart, checkout, account) to collect some.';
$_['text_allow_add']         = 'Allow in %2$s';
$_['text_allow_title']       = 'Add %s to %s in both policies';
$_['text_allowed']           = 'Success: Added %s to %s in both policies. Matching reports were removed.';
$_['text_deleted']           = 'Success: Violation reports deleted.';
$_['text_confirm_clear']     = 'Delete all violation reports?';
$_['text_refresh']           = 'Refresh';
$_['text_clear_all']         = 'Delete all';

// Column
$_['column_policy']          = 'Policy';
$_['column_directive']       = 'Directive';
$_['column_blocked']         = 'Blocked';
$_['column_page']            = 'Page / source';
$_['column_count']           = 'Count';
$_['column_date']            = 'Last seen';
$_['column_action']          = 'Action';

// Entry
$_['entry_status']           = 'Status';
$_['entry_nonce']            = 'Nonce for store scripts';
$_['help_nonce']             = 'Adds a nonce to every &lt;script&gt; in your template files, and the hashes of static inline handlers (onchange="...") in them. With script-src \'self\' (the Strict scripts preset), injected inline scripts are blocked while your theme\'s own scripts keep working. Code pasted into settings (analytics, chat widgets) is not a template and gets no nonce: move it into a template or a .js file, then allow the host it loads from.';
$_['entry_reporting']        = 'Send violation reports to';
$_['entry_report_url']       = 'Report URL';
$_['help_report_url']        = 'Your reporting service\'s CSP endpoint. Sent as the report-uri of both policies.';
$_['entry_skip']             = 'Skip routes';
$_['help_skip']              = 'One route per line, matched from the start (e.g. extension/opencart/payment/). These pages get no CSP header. Use it for a payment page you can\'t fix yet.';
$_['entry_policy_status']    = 'Send this policy';
$_['entry_upgrade']          = 'upgrade-insecure-requests';
$_['help_upgrade']           = 'Loads http:// images and scripts over https://. Only sent on HTTPS pages.';

// Directive help
$_['help_default_src']       = 'Fallback for every *-src directive that is empty below.';
$_['help_script_src']        = 'JavaScript files and inline scripts.';
$_['help_style_src']         = 'Stylesheets and inline styles.';
$_['help_img_src']           = 'Images, favicons, tracking pixels.';
$_['help_font_src']          = 'Web fonts (Google Fonts: https://fonts.gstatic.com).';
$_['help_connect_src']       = 'fetch/XHR/WebSocket: AJAX, analytics beacons.';
$_['help_media_src']         = 'Audio and video files.';
$_['help_object_src']        = 'Plugins (Flash, &lt;object&gt;). Keep \'none\'.';
$_['help_frame_src']         = 'Iframes on your pages: YouTube, maps, payment forms.';
$_['help_worker_src']        = 'Web workers and service workers.';
$_['help_manifest_src']      = 'Web app manifest.';
$_['help_base_uri']          = 'Allowed &lt;base href&gt; values. Keep \'self\'.';
$_['help_form_action']       = 'Where forms may submit. Add your payment gateway if it posts away from the store.';
$_['help_frame_ancestors']   = 'Who may put your store in an iframe (clickjacking). \'self\' = only you; \'none\' = nobody.';

// Hints
$_['hint_inline_script']     = 'An inline &lt;script&gt; without the nonce: turn on "Nonce for store scripts", or move the code into a template or a .js file. Allowing it with \'unsafe-inline\' would switch off most of CSP\'s protection.';
$_['hint_inline_handler']    = 'An inline event handler (onclick="...") that was built from data, so it can\'t be hashed. Move it into a script.';
$_['hint_frame_ancestors']   = 'Another site tried to show your store in an iframe. If you trust it, add its origin to frame-ancestors by hand.';
$_['hint_manual']            = 'Not something one click can allow. Check the directive and source, then edit the policy by hand.';

// Error
$_['error_permission']       = 'Warning: You do not have permission to modify Content Security Policy!';
$_['error_warning']          = 'Warning: Please check the form carefully for errors!';
$_['error_report_url']       = 'Enter a full http(s):// URL.';
$_['error_report']           = 'Warning: That report no longer exists.';
