# Listaria Marketplace

A luxury e-commerce marketplace platform rebuilt with CodeIgniter 4 (CI4) MVC framework and SQLite.

## Architecture

- **Framework**: CodeIgniter 4 (CI4)
- **Language**: PHP 8.2
- **Database**: SQLite (`database.sqlite` in project root)
- **Server**: PHP built-in server with CI4 router script
- **Config**: `php.ini` in project root
- **Web Root**: `ci4public/` directory (CI4 front controller)

## Key Files & Directories

- `ci4public/` - Web root (index.php, .htrouter.php, assets/, uploads/)
- `ci4public/.htrouter.php` - PHP built-in server router script
- `app/Config/Routes.php` - All application routes
- `app/Config/Database.php` - SQLite database config (`WRITEPATH . '../database.sqlite'`)
- `app/Config/Filters.php` - Auth/admin filter registration
- `app/Config/App.php` - Base URL and app config
- `app/Controllers/` - All frontend controllers
- `app/Controllers/Admin/` - All admin controllers (16 controllers)
- `app/Filters/AuthFilter.php` - Authentication filter
- `app/Filters/AdminFilter.php` - Admin access filter
- `app/Models/` - 14 Eloquent-style models
- `app/Views/` - All view templates (layouts, partials, auth, home, product, profile, pages, order, admin)
- `database.sqlite` - SQLite database with all data
- `php.ini` - PHP configuration (uploads, memory)

## Admin Credentials

- Email: `admin@listaria.com`
- Password: `admin123`
- Role: `super_admin` (access to Roles, Activity, Security pages)

## Models (14)

UserModel, ProductModel, OrderModel, BlogModel, BannerModel, SiteSettingModel, CustomPageModel, EmailTemplateModel, NegotiationModel, MessageModel, WishlistModel, ProductRequestModel, ReturnModel, SupportTicketModel

## Admin Controllers (16)

DashboardController, UsersController, ListingsController, TransactionsController, ReturnsController, SupportController, ChatsController, BlogsController, PagesController, BannersController, RequestsController, RolesController, ActivityController, SecurityController, SettingsController, EmailTemplatesController

## Frontend Routes

- `/` - Homepage with products, banners, category filters
- `/login`, `/register` - Authentication
- `/product/:id` - Product detail page
- `/sell` - List new product (auth required)
- `/profile` - User dashboard (auth required)
- `/profile/settings` - Profile settings
- `/profile/orders` - Order history
- `/blogs`, `/blog/:id` - Blog listing and detail
- `/stores` - Vendor stores
- `/about`, `/terms`, `/privacy`, `/founders`, `/refund` - Static pages
- `/wishlist` - User wishlist (auth required)
- `/requests` - Product requests
- `/shipping`, `/payment-method`, `/place-order` - Checkout flow
- `/order-summary/:id` - Order confirmation
- `/page/:slug` - Custom CMS pages

## Admin Routes (under `/admin/`)

Dashboard, Analytics, Users, Listings, Transactions, Returns, Support, Chats, Blogs, Pages, Banners, Requests, Roles, Activity, Security, Settings, Email Templates

## API Endpoints (under `/api/`)

- `POST /api/chat/send` - Send chat message
- `GET /api/chat/messages/:id` - Get chat messages
- `GET /api/search` - Search products
- `POST /api/wishlist/toggle` - Toggle wishlist
- `POST /api/bulk-upload` - CSV bulk product upload
- `POST /api/validate-coupon` - Validate coupon
- `POST /api/update-listing` - Update product listing
- `POST /api/bulk-listing-action` - Bulk listing actions

## Session & Auth

- CI4 session-based authentication
- Session keys: `user_id`, `full_name`, `account_type`, `is_admin`, `role`
- `AuthFilter` protects user routes, `AdminFilter` protects admin routes
- Super admin features: Roles, Activity Logs, Security (requires `role === 'super_admin'`)

## Design

- Primary color: `#6B21A8` (purple)
- Admin sidebar: `#1a1a1a` dark theme
- Font: Inter (Google Fonts)
- Icons: Ionicons 5.5.2

## Running

```
php -c php.ini -S 0.0.0.0:5000 -t ci4public ci4public/.htrouter.php
```
