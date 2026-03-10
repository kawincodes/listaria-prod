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

## Database Editor (Admin)

- `admin_database.php` — Full-featured SQLite database editor in admin panel
- `api/admin_db.php` — Backend API for CRUD operations and raw SQL queries
- Features: table browser, search across columns, inline row editing, add/delete rows, SQL console (Ctrl+Enter to run)
- Protected by super_admin role check + CSRF token validation (only super_admin can access)
- Sidebar link added under Settings section

## Admin Product Management

- `admin_product_edit.php` — Full product edit page for admins (`?id=X`)
  - Edit: title, brand, description, category, price_min, price_max, condition_tag, quantity, status, approval_status, is_featured
  - Shows product images, seller info, meta info (views, location, boost status)
  - Breadcrumb navigation, "View Live" link to product_details.php
  - Logs edits to admin_activity_logs
- `admin_listings.php` — Product listings with View Live and Edit Product links in dropdown
  - Supports `?filter=vendor` to show only verified vendor products
  - Sets `$activePage = 'vendor_products'` when vendor filter active

## Admin Payment Verification

- `admin_payment_verify.php` — Dedicated page for verifying pending payment slips
  - Shows non-COD orders with `order_status IN ('Pending', 'Verification Pending')`
  - Verify Payment: sets status to Success, sends confirmation emails
  - Reject Payment: sets status to Payment Failed, restores product stock

## Admin Panel Improvements (March 2026)

- **User IPs Tab** (`admin_logs.php?tab=user_ips`): Shows all users with last known IP from `login_logs` table, login count, failed attempts, and last login time. Searchable by name, email, or IP.
- **Vendor Rejection Modal**: Replaced browser `prompt()` in `admin_users.php` with a proper styled modal containing a required textarea. Reason is stored in DB, emailed to applicant, and shown in user's profile dashboard.
- **Expanded Role Permissions** (`admin_roles.php`): Added 12 new granular permissions: `manage_kyc`, `manage_transactions`, `manage_wallet`, `manage_negotiations`, `manage_marquee`, `manage_founders`, `view_server_stats`, `manage_email_sender`, `view_user_ips`, `export_data`, `manage_categories`, `manage_seo`.
- **Marquee/Announcement Bar**: Fully implemented — settings in `admin_settings.php`, rendered in `includes/header.php`.
- **Founder Social Toggle**: `founder_socials_visible` setting in `admin_settings.php`, consumed in `founders.php`.
- **Vendor Demotion with Email**: Implemented in `admin_users.php` using `vendor_demoted` email template.
- **Custom Email Sender**: `admin_email_sender.php` with Quill rich-text editor.
- **Server Stats**: `admin_server_stats.php`.
- All settings persisted to `site_settings` table in SQLite.

## Admin Vendor Sidebar Section

The Vendor section in `includes/admin_sidebar.php` contains 7 items:
1. Vendor Applications (`admin_users.php?filter=vendor_apps`) — with pending count badge
2. KYC Verification (`admin_users.php?kyc=pending`) — with pending count badge
3. Verified Vendors (`admin_users.php?filter=verified_vendors`)
4. Vendor Products (`admin_listings.php?filter=vendor`)
5. Payment Verification (`admin_payment_verify.php`) — with pending count badge
6. Vendor Sales (`admin_transactions.php?filter=vendor_sales`)
7. Vendor Returns (`admin_returns.php?filter=vendor`)

## CSS/Font Loading (FOUC Prevention)

- Google Fonts (Inter) loaded non-render-blocking: `media="print" onload="this.media='all'"` with `<noscript>` fallback
- `preconnect` hints added for `fonts.googleapis.com` and `fonts.gstatic.com`
- Ionicons scripts use `defer` attribute to avoid parser blocking
- Local CSS files (`style.css`, `responsive.css`) load first (render-blocking, fast)
- Page-specific `<style>` blocks must be placed BEFORE HTML content, not after
- CSS version query string (`?v=1.0.3`) used for cache busting

## Coupon/Discount System

- `admin_coupons.php` — Admin coupon management (create, edit, toggle, delete)
- `api/validate_coupon.php` — Validates coupon codes against `coupons` table (checks active, date range, usage limits, per-user limits, min order amount)
- `api/remove_coupon.php` — Removes applied coupon from session
- DB tables: `coupons` (code, type percentage/flat, value, min_order_amount, max_discount_amount, usage_limit, per_user_limit, start_date, end_date, is_active, used_count), `coupon_usage` (user_id, coupon_code, order_id, discount_amount)
- Session key: `$_SESSION['applied_coupon']` stores code, type, value, discount_amount, coupon_id
- Coupon discount shown on: `shipping_info.php`, `payment_method.php`, `order_summary.php`
- `place_order.php` records usage in `coupon_usage`, increments `used_count` in `coupons` table
- Admin sidebar has "Marketing" section with Coupons link

## Vendor Bulk Upload

- `vendor_bulk_upload.php` — Vendor-facing page for CSV + image bulk upload
- Uses existing `api/bulk_upload.php` backend
- Accessible to verified vendors and admins
- CSV format: Title, Brand, Category, Condition, Location, Description, Price, Image Names, Quantity
- Admin sidebar has Bulk Upload link in Vendor section

## Shipping Labels (Admin)

- `admin_shipping_label.php` — Printable shipping label page (`?order_id=X`)
- Shows FROM (seller) and TO (buyer) addresses, product details, order metadata
- Clean print-optimized layout with `@media print` CSS rules
- Accessible via "Print Label" icon button in each row of `admin_transactions.php`
- Admin access required

## Founders Page

- `founders.php` — Displays founder profiles with photos, bios, and social links
- Social icons (LinkedIn, Instagram, X/Twitter) below each founder's name
- Founder 1: Harsh Vardhan Jaiswal (CEO) — placeholder social URLs
- Founder 2: Aryan Biswa (CFMO) — placeholder social URLs
- Bios and images managed via `site_settings` DB table (admin settings)

## File Manager (Admin)

- `admin_filemanager.php` — Upload, browse, and manage files with permanent URLs
- Files stored in `uploads/files/` directory
- Features: drag-and-drop upload, grid/list view toggle, copy URL button, file preview, delete
- Allowed types: images, documents, fonts, media, archives (20MB max per file)
- URL button copies full permanent URL (uses SITE_ROOT_URL for production, origin for dev)
- Admin sidebar link under "Developer Tools" section

## Login Logs (Admin)

- `admin_login_logs.php` — View all login attempts with IP, user agent, status
- DB table: `login_logs` (id, user_id, email, ip_address, user_agent, login_status, created_at) — auto-migrated in db.php
- `last_login_ip` column on users table — auto-migrated in db.php
- Logs success/failed/failed_unverified from `login.php` and `google_auth.php`
- Stats cards: total, successful, failed, unique IPs
- Filterable (All/Success/Failed), searchable, paginated

## Vendor Demote (Admin)

- `admin_users.php` has "Demote Vendor" action for verified vendors
- Sets `is_verified_vendor=0`, `account_type='customer'`, `vendor_status='demoted'`
- All vendor's approved products set to `approval_status='on_hold'`
- Sends `vendor_demoted` email template
- `profile.php` shows demoted status banner with re-apply link

## Vendor Rejection Reason

- `admin_users.php` reject_vendor prompts for reason, saved to `rejection_reason` column
- `vendor_rejected` email template includes the reason
- `profile.php` displays rejection reason in the rejected status banner

## Server & Site Logs (Admin)

- `admin_logs.php` — Tabs: Error Log (PHP error log), Activity Log (admin_activity_logs table), Login Log (links to admin_login_logs.php)
- Color-coded severity highlighting for error log entries
- Search and pagination for activity logs

## Server Stats (Admin)

- `admin_server_stats.php` — PHP version, OS, SQLite version, disk/memory usage, DB stats, app stats, loaded extensions
- Auto-refresh every 30 seconds via AJAX

## Custom Email Sender (Admin)

- `admin_email_sender.php` — Compose and send custom emails
- Send to: individual user, all users, all vendors, all customers, or custom email
- CC/BCC fields, Quill rich text editor for HTML body
- Uses `createSmtp($pdo)` from config.php
- DB table: `sent_emails` (id, from_email, to_email, subject, body, status, sent_by, created_at) — auto-migrated in db.php
- Sent email history with view/delete below composer

## Marquee Announcement Bar

- Scrolling announcement bar above header in `includes/header.php`
- DB settings: `marquee_enabled`, `marquee_text`, `marquee_bg_color`, `marquee_text_color`, `marquee_speed` (slow/medium/fast), `marquee_link`, `marquee_icon`
- Admin config in `admin_settings.php` > "Announcement Bar (Marquee)" section
- Dismissible per session via `marquee_dismiss.php` (sets `$_SESSION['marquee_dismissed']`)
- CSS animation with hover-pause

## Founder Social Links Toggle

- `founder_socials_visible` setting in `site_settings` (default: 1)
- Toggle in `admin_settings.php` > "Founders Page" section
- `founders.php` conditionally renders social icons based on this setting

## Enhanced User Role Management (Admin)

- `admin_roles.php` has expanded permissions: `manage_banners`, `manage_coupons`, `manage_pages`, `manage_email_templates`, `manage_vendors`, `view_logs`, `manage_files`, `manage_returns`, `view_reports`

## File Manager (Admin)

- `admin_filemanager.php` — Upload, browse, and manage files with permanent URLs
- Files stored in `uploads/files/` directory
- Features: drag-and-drop upload, grid/list view toggle, copy URL button, file preview, delete
- Allowed types: images, documents, fonts, media, archives (20MB max per file)
- URL button copies full permanent URL (uses SITE_ROOT_URL for production, origin for dev)
- Admin sidebar link under "Developer Tools" section

## Running

The app runs via PHP's built-in server on port 5000:
```
php -c php.ini -S 0.0.0.0:5000
```

## Deployment

Configured for autoscale deployment with the same PHP server command.
