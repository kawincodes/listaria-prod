# Listaria Marketplace

## Overview

Listaria Marketplace is a luxury e-commerce platform developed in PHP, utilizing SQLite as its database. It aims to provide a comprehensive online shopping experience with features for product listing, vendor management, secure transactions, and administrative oversight. The platform supports various user roles, including customers, vendors, and administrators, offering tailored functionalities for each. Key capabilities include a robust product catalog, user authentication, session management, an email notification system, and an integrated admin panel for full control over the marketplace operations. The project's vision is to create a scalable and feature-rich e-commerce solution for high-end goods.

## User Preferences

I prefer to work iteratively, with small, testable changes. Please provide clear explanations for any significant architectural decisions or complex code implementations. When suggesting changes, outline the pros and cons, and ask for confirmation before proceeding with major modifications. Ensure all database interactions are secure and follow best practices. I also prefer the use of modern PHP features and clean code.

## System Architecture

**Core Technologies:**
- **Language:** PHP 8.2
- **Database:** SQLite (managed via `database.sqlite` in the project root)
- **Server:** PHP's built-in web server.
- **Configuration:** `php.ini` for PHP settings and `.env` for environment variables.

**Project Structure:**
- **Frontend Pages:** Root-level `.php` files (e.g., `index.php`, `thrift.php`, `about.php`).
- **Admin Panel:** `admin_*.php` files for administrative functionalities.
- **Shared Includes:** `includes/` directory for common components like database connection (`db.php`), session management (`session.php`), configuration (`config.php`), and email templates.
- **Uploads:** `uploads/` for user-generated content.

**Key Features and Implementations:**

-   **Session Management:** Centralized session handling using `includes/session.php` with session files stored in a protected `sessions/` directory.
-   **Email System:** An admin-managed email templating system (`email_templates.php`) using `{{variable_name}}` placeholders, stored in the `email_templates` database table. Includes a custom email sender with a rich-text editor for composing and sending custom emails.
-   **CAPTCHA Integration:** Supports both Cloudflare Turnstile and Google reCAPTCHA v2, configurable via the admin panel with environment variable fallbacks.
-   **Product & Stock Management:** Products include quantity tracking. Stock is validated and decremented upon order placement. Admins have a dedicated product edit page and bulk actions for listings.
-   **Admin Database Editor:** A powerful SQLite database editor (`admin_database.php`) within the admin panel, allowing table browsing, inline editing, and raw SQL execution, protected by a `super_admin` role.
-   **Admin Product & Vendor Management:** Comprehensive tools for managing product listings, approving/rejecting vendor applications, KYC verification, and demoting vendors with automated email notifications.
-   **Payment Verification:** Dedicated admin interface (`admin_payment_verify.php`) for verifying pending payment slips and managing order statuses.
-   **Admin Panel Enhancements:**
    -   **User IPs Tab:** For monitoring user login IPs and activity.
    -   **Expanded Role Permissions:** Granular control over administrative actions.
    -   **Marquee/Announcement Bar:** Configurable scrolling announcement bar.
    -   **Server Statistics:** Page (`admin_server_stats.php`) displaying real-time server and application health metrics.
    -   **Login Logs:** Detailed logging of all login attempts for security monitoring.
    -   **File Manager:** Admin interface for uploading, browsing, and managing files stored in `uploads/files/`.
-   **Front-End Styling:** Non-render-blocking Google Fonts loading, `defer` for Ionicons scripts, and CSS cache busting for optimal performance.
-   **Coupon/Discount System:** Admin-managed coupon creation with validation logic covering usage limits, date ranges, and minimum order amounts. Applied coupons affect pricing displayed throughout the checkout process.
-   **Vendor Bulk Upload:** A vendor-facing interface for bulk product uploads via CSV and images.
-   **Shipping Labels:** Admin-only printable shipping label generation with order and address details.
-   **Founders Page:** Dynamically displays founder profiles with bios and social links, configurable via admin settings.

## External Dependencies

-   **Google OAuth:** For user authentication.
-   **Cloudflare Turnstile / Google reCAPTCHA v2:** For CAPTCHA protection on forms.
-   **SMTP Service:** For sending emails (configurable via `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` environment variables).
-   **Google Fonts (Inter):** For typography, loaded non-render-blocking.
-   **Ionicons:** For icons, loaded with `defer` attribute.
-   **Quill Rich Text Editor:** Used in the custom email sender for HTML email composition.
-   **PhonePe (implied by admin_transactions.php):** For payment processing, where payment verification is handled.