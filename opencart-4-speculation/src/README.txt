Instant Pages (Speculation Rules) for OpenCart 4
================================================
Adds a <script type="speculationrules"> block so Chrome, Edge and other Chromium browsers
prerender (or prefetch) the page a visitor is about to open: product and category pages then
appear instantly on click. Checkout, account, logout, compare, language and currency links are
never prerendered, and checkout/account pages don't get rules at all. Other browsers ignore it.

Install
1. Extensions > Installer: upload speculation.ocmod.zip and click the green + button.
2. Extensions > Extensions > Modules: install "Instant Pages (Speculation Rules)".
3. Edit: turn Status on and save.

Tested on OpenCart 4.1.0.4 with Chrome.
Guide: https://webocreation.com/opencart-4-instant-pages-speculation-rules/
