Faceted Filter for OpenCart 4
=============================
A filter box for category pages: price range, brand, attributes, your OpenCart filters, in stock only
and customer rating. Every option shows how many products it would match, options that would match
nothing are greyed out, and filtered pages are marked noindex so search engines index only the plain
category pages.

Install
1. Extensions > Installer: upload facetfilter.ocmod.zip and click the green + button.
2. Extensions > Extensions > Modules: install "Faceted Filter", click Edit, turn Status on and save.
3. Design > Layouts > Category: add "Faceted Filter" to Column Left (or Column Right) and save.

How it works
- The facets travel in OpenCart's own "filter" URL parameter, so sorting, "Show" limits and page links keep them.
- Filtered category listings are answered by the extension's model through two model events; no core files change.
- Attribute values come from Catalog > Products > edit > Attribute; brands from each product's manufacturer.

Tested on OpenCart 4.1.0.4.
Guide: https://webocreation.com/opencart-4-faceted-filter/
