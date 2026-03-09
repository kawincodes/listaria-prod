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
- `includes/db.php` - Database connection (SQLite via PDO)
- `includes/config.php` - Environment variable loader
- `includes/email_templates.php` - Email template helper (getEmailTemplate, renderEmailTemplate)
- `database.sqlite` - SQLite database
- `.env` - Environment variables (SITE_ROOT_URL, Google OAuth, reCAPTCHA keys)
- Env vars for SMTP: `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` (optional, overrides DB settings)
- `php.ini` - PHP configuration

## Project Structure

- Root `.php` files - Frontend pages (index, thrift, about, stores, vendor, etc.)
- `admin_*.php` - Admin panel pages (dashboard, settings, email templates, etc.)
- `includes/` - Shared PHP includes (db, config, header, footer, email templates, etc.)
- `uploads/` - User-uploaded images

## Email Template System

- `admin_email_templates.php` - Admin UI for managing email templates
- `includes/email_templates.php` - Helper with defaults and rendering functions
- DB table: `email_templates` (template_key, name, subject, body, variables, is_active)
- Templates use `{{variable_name}}` placeholders replaced at send time
- 7 default templates: order_confirmation, shipping_update, listing_approved, listing_rejected, welcome_email, order_delivered, support_reply
- Use `renderEmailTemplate($pdo, 'template_key', ['var' => 'value'])` to render

## Running

The app runs via PHP's built-in server on port 5000:
```
php -c php.ini -S 0.0.0.0:5000
```

## Deployment

Configured for autoscale deployment with the same PHP server command.
