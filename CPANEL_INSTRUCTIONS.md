# How to Fix Chat on cPanel Hosting

## Step 1: Import the Database Tables
1.  Log in to your **cPanel**.
2.  Open **phpMyAdmin**.
3.  Select your website's database from the left sidebar (it will look something like `username_listaria_db`).
4.  Click the **Import** tab at the top.
5.  Click **Choose File** and select the file `negotiations_cpanel.sql` (I have just created this file in your extracted folder).
6.  Click **Go** (or **Import**) at the bottom.
    *   *Success Message:* "Import has been successfully finished."

## Step 2: Connect Code to MySQL
Your code is currently set up for SQLite (a local file database). You need to switch it to MySQL for cPanel.

1.  Open the file: `includes/db.php` on your **cPanel File Manager**.
2.  **Edit** the file and change it to look like this (fill in your actual cPanel database details):

```php
<?php
// cPanel MySQL Connection
$host = 'localhost';          // Usually localhost
$db   = 'YOUR_CPANEL_DB_NAME'; // e.g., hemur_listaria
$user = 'YOUR_CPANEL_DB_USER'; // e.g., hemur_admin
$pass = 'YOUR_DB_PASSWORD';    // The password you set for that user
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Shows errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // If connection fails, stop and show error
    die("Database Connection Failed: " . $e->getMessage()); 
}
?>
```

## Step 3: Test It
1.  Go to your live website.
2.  Login as a user (User A).
3.  Go to a product posted by another user (User B).
4.  Click **"Make an Offer"**.
5.  Send a message.
6.  If the message appears in the chat box immediately, **IT IS WORKING!**
