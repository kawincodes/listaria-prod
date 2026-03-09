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
- `database.sqlite` - SQLite database
- `.env` - Environment variables (SITE_ROOT_URL, Google OAuth, reCAPTCHA keys)
- `php.ini` - PHP configuration

## Project Structure

- Root `.php` files - Frontend pages (index, thrift, about, stores, vendor, etc.)
- `admin/` - Laravel-based admin panel (separate app)
- `includes/` - Shared PHP includes (db, config, header, footer, etc.)
- `uploads/` - User-uploaded images

## Running

The app runs via PHP's built-in server on port 5000:
```
php -c php.ini -S 0.0.0.0:5000
```

## Deployment

Configured for autoscale deployment with the same PHP server command.
