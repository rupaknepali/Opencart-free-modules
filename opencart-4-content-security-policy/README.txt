Content Security Policy for OpenCart 4 - free extension by Webocreation
https://webocreation.com/opencart-4-content-security-policy-extension/

Tested on OpenCart 4.1.0.4 (PHP 8.3).
Sends Content-Security-Policy and Content-Security-Policy-Report-Only headers on storefront pages,
configured per directive in the admin. Violations are logged in the admin, where one click adds
a blocked host to the policy. Optional nonce for the store's own inline scripts (template files only).

Install: Extensions > Installer (upload csp.ocmod.zip, click Install), then Extensions > Extensions > Modules:
install Content Security Policy, Edit, turn Status on and save. The Report-only policy is on by default:
it blocks nothing. Turn on the Enforced policy once Violations stays empty.
Free to use and modify. No attribution required.
