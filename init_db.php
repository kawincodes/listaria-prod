<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    // Connect to SQLite DB (creates file if not exists)
    $db_file = 'database.sqlite';
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to SQLite database successfully.\n";

    // SQLite Table Creation
    $sql = "
    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        brand TEXT NOT NULL,
        condition_tag TEXT CHECK(condition_tag IN ('Brand New', 'Lightly Used', 'Regularly Used')) NOT NULL,
        price_min DECIMAL(10, 2) NOT NULL,
        price_max DECIMAL(10, 2) NOT NULL,
        image_paths TEXT NOT NULL, -- JSON array
        is_published INTEGER DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ";
    
    $pdo->exec($sql);
    echo "Table 'products' created or already exists.\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS coupons (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT NOT NULL UNIQUE,
        type TEXT NOT NULL DEFAULT 'percentage',
        value REAL NOT NULL DEFAULT 0,
        min_order_amount REAL DEFAULT 0,
        max_discount_amount REAL DEFAULT 0,
        usage_limit INTEGER DEFAULT 0,
        used_count INTEGER DEFAULT 0,
        per_user_limit INTEGER DEFAULT 1,
        start_date TEXT,
        end_date TEXT,
        is_active INTEGER DEFAULT 1,
        created_by INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Table 'coupons' created or already exists.\n";

    $pdo->exec("CREATE TABLE IF NOT EXISTS coupon_usage (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        coupon_code TEXT NOT NULL,
        order_id INTEGER,
        discount_amount REAL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Table 'coupon_usage' created or already exists.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
