Speed Booster for OpenCart 4
============================
Makes OpenCart fast for the visitors who matter most for SEO and first impressions: guests.

- Full-page cache for guests: pages are built once and served from storage/cache in a few
  milliseconds. Logged-in customers, visitors with a cart, wishlist or compare list, and
  checkout/account pages always get a live page. Review and upload form tokens stay valid.
- The cache empties itself when you save a product, category, layout, setting or module,
  and an order refreshes the pages of the products in it (stock, price).
- Lazy-loads images below the first few, plus every iframe.
- Minifies HTML (scripts, styles, pre and textarea are left alone).
- Preconnect hints for fonts, CDNs and payment scripts.
- Speed check: OPcache, PHP version, cache engine, Category Product Count, Customers Online,
  output compression, expired sessions, error log size, with one-click fixes.
- Adds the database indexes OpenCart is missing (order_history.order_id and 14 more).

Install
1. Extensions > Installer: upload speedbooster.ocmod.zip and click the green + button.
2. Extensions > Extensions > Modules: install "Speed Booster".
3. Edit: turn Status on and save. Run the speed check fixes you want.

Test: open the store in a private window, reload twice; the response header
X-Speed-Booster shows MISS, then HIT.

Tested on OpenCart 4.1.0.4.
Guide: https://webocreation.com/opencart-4-speed-booster/
