# Listaria Marketplace

A luxury e-commerce marketplace platform built in PHP with SQLite.

## Architecture

- **Language**: PHP 8.2
- **Database**: SQLite (`database.sqlite` in project root)
- **Server**: PHP built-in server (`php -S 0.0.0.0:5000`)
- **Config**: `php.ini` in project root (Linux-compatible)
- **DB Connection**: `includes/db.php` (PDO/SQLite)
- **Env Config**: `includes/config.php` loads `.env` file

## Key Files

- `index.php` - Main marketplace homepage
- `includes/db.php` - Database connection (SQLite via PDO), requires session.php
- `includes/session.php` - Centralized session init (sets save path to `sessions/`, cookie params, ob_start)
- `includes/config.php` - Environment variable loader
- `includes/email_templates.php` - Email template helper (getEmailTemplate, renderEmailTemplate)
- `database.sqlite` - SQLite database
- `sessions/` - Session file storage (protected by .htaccess, gitignored except .htaccess)
- `.env` - Environment variables (SITE_ROOT_URL, Google OAuth)
- `.user.ini` - PHP settings for LiteSpeed/FastCGI (output_buffering, session cookie flags)
- Env vars for SMTP: `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` (optional, overrides DB settings)
- Env vars for CAPTCHA: `CAPTCHA_ENABLED` (true/false), `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` (Cloudflare Turnstile)
- `php.ini` - PHP configuration

## Session Management

- All PHP pages use `require_once __DIR__ . '/includes/session.php'` (never raw `session_start()`)
- `session.php` sets `session_save_path()` to `sessions/` directory in project root
- This ensures consistent session storage across all pages (critical for login to work)
- The `sessions/` directory has `.htaccess` with `Deny from all` for web protection
- Login redirects use relative URLs (not absolute with protocol) to avoid http/https mismatch

## Project Structure

- Root `.php` files - Frontend pages (index, thrift, about, stores, vendor, etc.)
- `admin_*.php` - Admin panel pages (dashboard, settings, email templates, pages, etc.)
- `includes/` - Shared PHP includes (db, config, header, footer, email templates, etc.)
- `uploads/` - User-uploaded images

## Email Template System

- `admin_email_templates.php` - Admin UI for managing email templates
- `includes/email_templates.php` - Helper with defaults and rendering functions
- DB table: `email_templates` (template_key, name, subject, body, variables, is_active)
- Templates use `{{variable_name}}` placeholders replaced at send time
- 7 default templates: order_confirmation, shipping_update, listing_approved, listing_rejected, welcome_email, order_delivered, support_reply
- Use `renderEmailTemplate($pdo, 'template_key', ['var' => 'value'])` to render

## CAPTCHA (Dual Provider: Turnstile + reCAPTCHA)

- Supports both Cloudflare Turnstile and Google reCAPTCHA v2
- `config.php` defines `getCaptchaConfig($pdo)`, `isCaptchaActive($pdo)`, `getCaptchaProvider($pdo)`, `getCaptchaSiteKey($pdo)`, `verifyCaptcha($token, $pdo)`
- Admin Settings > CAPTCHA Protection: toggle on/off, choose provider, edit all 4 keys (saved to DB `site_settings`)
- DB keys: `captcha_enabled`, `captcha_provider`, `turnstile_site_key`, `turnstile_secret_key`, `recaptcha_site_key`, `recaptcha_secret_key`
- Env vars used as defaults, DB values override them
- Active on `login.php` and `register.php` forms, auto-switches widget/script per provider

## Product Quantity/Stock

- Products table has `quantity INTEGER DEFAULT 1` column (auto-migrated in db.php)
- `sell.php` form includes quantity input field
- `product_details.php` shows "X available" badge + quantity in details table
- `place_order.php` validates stock before ordering, decrements quantity on COD, marks sold when qty=0
- `admin_transactions.php` decrements quantity on payment verification (PhonePe)
- `api/update_listing.php` mark_sold sets quantity=0
- `api/bulk_listing_action.php` bulk sold sets quantity=0
- `api/bulk_upload.php` supports quantity as 9th CSV column (optional, defaults to 1)

## Running

The app runs via PHP's built-in server on port 5000:
```
php -c php.ini -S 0.0.0.0:5000
```

## Deployment

Configured for autoscale deployment with the same PHP server command.
