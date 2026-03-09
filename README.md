# Listaria Marketplace

A luxury e-commerce marketplace platform with a comprehensive admin panel built in PHP.

## Features

### User Features
- User registration and login
- Product browsing and search
- Shopping cart and checkout
- Order tracking
- Wallet system
- Chat with sellers
- KYC verification

### Seller Features
- Product listing management
- Order management
- Sales analytics
- Wallet withdrawals

### Admin Panel Features
- **Dashboard**: Overview stats, pending approvals, daily activity
- **User Management**: KYC verification, wallet credit/debit, block/suspend users, bulk actions
- **Listings Management**: Approval workflow, featured listings, boost system
- **Orders Management**: Track and manage all orders
- **Analytics**: Revenue charts, top sellers, category performance
- **Support Tickets**: Priority levels, admin assignment, reply system
- **Security Center**: Session management, IP/email blacklist
- **Site Settings**: Maintenance mode, commission rates, feature toggles, payment methods
- **Role Management**: Admin roles and permissions

---

## Deployment on cPanel

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled

### Step 1: Prepare Files

1. Download all files from the repository
2. Create a ZIP file of the entire project (excluding `node_modules` if present)

### Step 2: Upload to cPanel

1. Log in to your cPanel account
2. Navigate to **File Manager**
3. Go to `public_html` folder (or your desired subdomain folder)
4. Click **Upload** and select your ZIP file
5. After upload, right-click the ZIP file and select **Extract**
6. Delete the ZIP file after extraction

### Step 3: Create MySQL Database

1. In cPanel, go to **MySQL Databases**
2. Create a new database (e.g., `listaria_db`)
3. Create a new MySQL user with a strong password
4. Add the user to the database with **All Privileges**
5. Note down:
   - Database name
   - Database username
   - Database password

### Step 4: Import Database Schema

1. Go to **phpMyAdmin** in cPanel
2. Select your newly created database
3. Click **Import** tab
4. Upload the `database.sql` file (if provided) or run the SQL schema manually
5. If no SQL file exists, the tables will be created automatically on first run

### Step 5: Configure Database Connection

1. Open `includes/db.php` in File Manager or via FTP
2. Update the database credentials:

```php
<?php
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'your_database_user';
$password = 'your_database_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
```

### Step 6: Set File Permissions

1. In File Manager, set the following permissions:
   - `uploads/` folder: **755** or **775**
   - `assets/` folder: **755** or **775**
   - All PHP files: **644**
   - All folders: **755**

2. To set permissions:
   - Right-click the folder/file
   - Select **Change Permissions**
   - Enter the numeric value (e.g., 755)

### Step 7: Configure .htaccess (Optional)

Create or update `.htaccess` in your root folder:

```apache
RewriteEngine On

# Redirect to HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove .php extension
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^([^\.]+)$ $1.php [NC,L]

# Protect sensitive files
<Files "db.php">
    Order Allow,Deny
    Deny from all
</Files>

# Protect uploads from PHP execution
<Directory "uploads">
    php_flag engine off
</Directory>
```

### Step 8: Create Admin Account

1. Visit your website URL
2. Register a new account
3. Access phpMyAdmin and run:

```sql
UPDATE users SET is_admin = 1, role = 'super_admin' WHERE email = 'your-email@example.com';
```

4. Now you can access `/admin_dashboard.php`

---

## File Structure

```
├── admin_dashboard.php      # Admin main dashboard
├── admin_users.php          # User management
├── admin_listings.php       # Product listings management
├── admin_orders.php         # Order management
├── admin_analytics.php      # Analytics and reports
├── admin_support.php        # Support tickets
├── admin_settings.php       # Site configuration
├── admin_security.php       # Security center
├── admin_roles.php          # Role management
├── admin_activity.php       # Activity logs
├── includes/
│   ├── db.php              # Database connection
│   ├── header.php          # Site header
│   ├── footer.php          # Site footer
│   └── admin_sidebar.php   # Admin navigation
├── uploads/                 # User uploaded files
├── assets/                  # Static assets (CSS, JS, images)
├── index.php               # Homepage
├── login.php               # User login
├── register.php            # User registration
├── product.php             # Product details
├── cart.php                # Shopping cart
└── checkout.php            # Checkout process
```

---

## Configuration Options

### Site Settings (via Admin Panel)

| Setting | Description |
|---------|-------------|
| Maintenance Mode | Disable site for regular users |
| Registration | Enable/disable new signups |
| Listing Approval | Require admin approval for listings |
| KYC Required | Require identity verification to sell |
| Commission Rate | Platform fee percentage |
| Payment Methods | Razorpay, COD, Wallet toggles |

### SMTP Email Configuration

Configure in Admin Panel > Site Settings:
- SMTP Host (e.g., smtp.gmail.com)
- SMTP Port (e.g., 587)
- SMTP Username
- SMTP Password

---

## Troubleshooting

### Common Issues

**1. Database Connection Error**
- Verify database credentials in `includes/db.php`
- Ensure MySQL user has proper privileges
- Check if database exists

**2. 500 Internal Server Error**
- Check PHP error logs in cPanel
- Verify file permissions
- Ensure PHP version is 7.4+

**3. Images Not Uploading**
- Set `uploads/` folder permission to 755 or 775
- Check PHP `upload_max_filesize` in php.ini
- Verify folder exists and is writable

**4. Admin Panel Access Denied**
- Ensure `is_admin = 1` in users table
- Clear browser cache and cookies
- Check session configuration

### PHP Settings (php.ini)

Recommended settings for cPanel:
```ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300
memory_limit = 256M
```

---

## Security Recommendations

1. **Use HTTPS**: Enable SSL certificate in cPanel
2. **Strong Passwords**: Use complex passwords for admin accounts
3. **Regular Backups**: Set up automatic backups in cPanel
4. **Update PHP**: Keep PHP version updated
5. **File Permissions**: Never use 777 permissions
6. **Hide Errors**: In production, set `display_errors = Off`

---

## Support

For issues or questions, please create a support ticket in the admin panel or contact the development team.

---

## License

This project is proprietary software. All rights reserved.
