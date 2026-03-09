# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

## [1.0.0] - 2026-02-01

### Added
- **Environment Configuration**: Introduced `.env` support for managing `SITE_ROOT_URL` and `GOOGLE_CLIENT_ID`.
- **Checkout Flow**: 
    - New `shipping_info.php` page to collect Name, Address, and Phone Number.
    - **Address Autocomplete**: Integrated OpenStreetMap suggestions for easy address entry.
    - Redirect logic to ensure shipping details are captured before payment.
    - "Cash on Delivery" (COD) payment option in `payment_method.php`.
    - Instant "Order Successful" popup for COD orders in `order_summary.php`.
- **User Profile**:
    - "Negotiations" tab consolidating both Buyer and Seller offers.
    - Phone Number field in profile settings.
    - Last message preview in listing cards.
    - Notification bubbles for unread messages.
- **Admin Panel**:
    - Display of Buyer Phone Number and Address in `admin_transactions.php`.
    - "Cancelled" status option in Admin Transactions.
    - Automatic product availability reversion when an order is cancelled or rejected.

### Changed
- **Authentication**: 
    - Login and Register pages now redirect users back to their previous location (e.g., Checkout) after successful auth.
    - Updated Google Auth to support dynamic redirection.
- **Profile UI**: 
    - Improved mobile responsiveness for tabs and listing cards.
    - Unified interaction for tracking orders.

### Fixed
- **Chat**: Messages are now correctly marked as read when opened.
- **Navigation**: Fixed redirection loops by properly handling session states in checkout.
