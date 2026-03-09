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

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
