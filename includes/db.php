<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/config.php';

if (!extension_loaded('pdo_sqlite')) {
    die('<h2>Setup Error</h2><p>The <strong>pdo_sqlite</strong> PHP extension is not enabled on this server. Please enable it in cPanel &rarr; Select PHP Version &rarr; Extensions, then reload.</p>');
}

$db_file = __DIR__ . '/../database.sqlite';
$dsn     = "sqlite:$db_file";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, null, null, $options);
    $pdo->exec("PRAGMA journal_mode=WAL");
    $pdo->exec("CREATE TABLE IF NOT EXISTS custom_pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        content TEXT DEFAULT '',
        meta_description TEXT DEFAULT '',
        is_published INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    try { $pdo->exec("ALTER TABLE products ADD COLUMN quantity INTEGER DEFAULT 1"); } catch (\PDOException $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN last_login_ip TEXT DEFAULT NULL"); } catch (\PDOException $e) {}
    try { $pdo->exec("ALTER TABLE products ADD COLUMN rejection_reason TEXT DEFAULT NULL"); } catch (\PDOException $e) {}
    try { $pdo->exec("ALTER TABLE orders ADD COLUMN rejection_reason TEXT DEFAULT NULL"); } catch (\PDOException $e) {}
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        email TEXT,
        ip_address TEXT,
        user_agent TEXT,
        login_status TEXT DEFAULT 'success',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS sent_emails (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        from_email TEXT,
        to_email TEXT,
        subject TEXT,
        body TEXT,
        status TEXT DEFAULT 'sent',
        sent_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS boost_orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        plan_days INTEGER NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method TEXT DEFAULT 'wallet',
        status TEXT DEFAULT 'pending',
        boosted_from DATETIME,
        boosted_until DATETIME,
        admin_note TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        budget REAL DEFAULT NULL,
        status TEXT DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
} catch (\PDOException $e) {
    $msg = $e->getMessage();
    if (str_contains($msg, 'unable to open') || str_contains($msg, 'permission')) {
        die('<h2>Database Error</h2><p>Cannot open <code>database.sqlite</code>. Check that the file exists in your project root and is writable by the web server (chmod 664). <br>Full path: <code>' . htmlspecialchars($db_file) . '</code></p>');
    }
    throw new \PDOException($msg, (int)$e->getCode());
}
