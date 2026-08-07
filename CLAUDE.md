# CLAUDE.md

## Project Overview

This is an OpenCart eCommerce application.

Primary objectives:
- Maintain compatibility with the existing OpenCart version.
- Follow OpenCart MVC architecture.
- Prefer OCMOD/Event system over core modifications.
- Keep backward compatibility whenever possible.
- Minimize breaking database changes.

---

# Tech Stack

- PHP 8.x (follow project requirement)
- OpenCart
- MySQL / MariaDB
- Twig Templates
- JavaScript (Vanilla + existing libraries)
- Bootstrap (existing version)
- jQuery (existing version)

---

# Directory Structure

Typical directories:

```
admin/
catalog/
system/
extension/
image/
storage/
```

Important MVC locations:

```
admin/controller/
admin/model/
admin/view/

catalog/controller/
catalog/model/
catalog/view/
```

Language files:

```
admin/language/
catalog/language/
```

Twig templates:

```
*.twig
```

---

# Coding Standards

## PHP

- Follow PSR-12 where practical.
- Use strict comparisons.
- Use descriptive variable names.
- Keep controller actions small.
- Business logic belongs in models.
- Avoid duplicated SQL.
- Prefer OpenCart database abstraction.

Example:

```php
$query = $this->db->query(
    "SELECT * FROM `" . DB_PREFIX . "product` WHERE product_id = '" . (int)$product_id . "'"
);
```

Always cast IDs to integers.

Never concatenate raw user input into SQL.

---

## Controllers

Controllers should:

- Validate permissions
- Validate input
- Call models
- Prepare `$data`
- Load language
- Render view

Avoid business logic inside controllers.

---

## Models

Models should:

- Handle SQL
- Handle reusable business logic
- Return structured arrays
- Never echo output

---

## Views

Views should:

- Contain presentation only
- Avoid business logic
- Use Twig syntax
- Escape output when appropriate

---

# OpenCart Conventions

Always load dependencies using OpenCart loaders.

Example:

```php
$this->load->language('extension/module/example');

$this->load->model('catalog/product');

$this->load->model('setting/setting');
```

Avoid direct includes.

---

# Language Files

Never hardcode UI strings.

Always use:

```php
$_['text_success']
$_['entry_name']
$_['error_permission']
```

Controller:

```php
$data['heading_title'] = $this->language->get('heading_title');
```

---

# Configuration

Use configuration values:

```php
$this->config->get('config_name');
```

Avoid hardcoded configuration.

---

# URL Generation

Generate admin URLs using:

```php
$this->url->link(...)
```

Always include:

- user_token (OpenCart 3)
- route

Do not hardcode admin URLs.

---

# Security

Always:

- Validate permissions

```php
$this->user->hasPermission(...)
```

- Validate CSRF tokens where applicable.
- Escape output.
- Sanitize filenames.
- Cast numeric IDs.
- Validate uploaded files.
- Prevent directory traversal.
- Prevent SQL injection.
- Prevent XSS.

Never trust:

- GET
- POST
- COOKIE
- FILES

---

# Database

Prefer existing tables.

Before creating a new table, verify one does not already exist.

Use:

```php
DB_PREFIX
```

Never hardcode prefixes.

Example:

```php
"SELECT * FROM `" . DB_PREFIX . "customer`"
```

---

# Events

Prefer Events over core edits.

If extending functionality:

1. Events
2. OCMOD
3. Core modification (last resort)

---

# OCMOD

If modifying OpenCart behavior:

Prefer generating an OCMOD XML instead of editing core files.

Only edit core when explicitly requested.

---

# Extension Development

Structure:

```
extension/example/

admin/
catalog/
system/
```

Include:

- controller
- model
- language
- view

Keep admin and catalog separated.

---

# Settings

Persist module settings using:

```php
model_setting_setting
```

Do not write configuration directly to the database.

---

# Error Handling

Return meaningful errors.

Avoid:

```php
die();
exit();
print_r();
var_dump();
```

Use:

- logs
- exceptions
- OpenCart error handling

---

# Logging

Use:

```php
$this->log->write(...)
```

Do not leave debug statements in production.

---

# JavaScript

Prefer existing OpenCart patterns.

Avoid introducing new frameworks.

Use vanilla JS where possible.

If existing code uses jQuery, remain consistent.

---

# CSS

Reuse existing Bootstrap classes.

Avoid large custom CSS unless necessary.

---

# Performance

Prefer:

- single SQL query
- indexed lookups
- pagination
- lazy loading where applicable

Avoid:

- N+1 queries
- unnecessary loops
- repeated model loading

---

# Cache

Respect OpenCart cache.

Use:

```php
$this->cache
```

when appropriate.

Clear caches only when necessary.

---

# File Uploads

Validate:

- extension
- MIME type
- file size

Never trust filenames.

Generate safe filenames.

---

# API

When adding API endpoints:

- validate authentication
- validate permissions
- return JSON
- use proper HTTP status codes where supported

---

# Admin UI

Follow existing OpenCart UI.

Use:

- breadcrumbs
- success messages
- warning messages
- pagination
- tokenized URLs

Maintain consistency with the admin theme.

---

# Forms

Always validate:

- required fields
- permissions
- data types

Populate validation errors via:

```php
$error['field']
```

---

# Installation

Installation scripts should:

- create tables only if absent
- add indexes if missing
- avoid destructive changes
- support repeated execution safely

Uninstall should clean up only extension-owned data.

---

# Backward Compatibility

Do not remove:

- existing events
- hooks
- language keys
- config values
- database columns

without explicit approval.

---

# Version Compatibility

Before using new APIs, verify compatibility with the target OpenCart version.

Avoid features unavailable in supported versions.

---

# Testing Checklist

Before submitting changes:

- PHP syntax passes
- Admin pages load
- Catalog pages load
- No warnings/notices
- No fatal errors
- Language strings resolve
- URLs generate correctly
- Permissions verified
- SQL queries work
- Module installs
- Module uninstalls
- Cache cleared if needed

---

# When Making Changes

Claude should:

1. Search for existing implementations before creating new ones.
2. Preserve OpenCart coding style.
3. Minimize file modifications.
4. Explain architectural changes.
5. Avoid unnecessary refactoring.
6. Keep patches focused.
7. Maintain backward compatibility.
8. Update language files when UI changes.
9. Update both admin and catalog sides when required.
10. Prefer Events/OCMOD over core edits.

---

# Avoid

- Editing OpenCart core without request
- Hardcoded SQL prefixes
- Hardcoded URLs
- Inline HTML in controllers
- Business logic in Twig
- Business logic in controllers
- Duplicate code
- Unvalidated input
- Direct SQL with raw input
- Debug output in production

---

# Preferred Workflow

When implementing a feature:

1. Understand the OpenCart version.
2. Identify existing patterns.
3. Reuse existing models where possible.
4. Create language entries.
5. Implement model.
6. Implement controller.
7. Implement Twig template.
8. Validate permissions.
9. Test admin.
10. Test storefront.
11. Check logs for warnings/errors.

Always aim for maintainable, OpenCart-native solutions that integrate cleanly with the existing architecture.