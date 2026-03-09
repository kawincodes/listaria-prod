<?php
require 'includes/db.php';

echo "<h1>February Feature Migration</h1>";

try {
    // 1. Update Users Table
    $user_columns = [
        "account_type TEXT DEFAULT 'customer'",
        "business_name TEXT DEFAULT NULL",
        "business_bio TEXT DEFAULT NULL",
        "business_logo TEXT DEFAULT NULL",
        "whatsapp_number TEXT DEFAULT NULL",
        "is_public INTEGER DEFAULT 0"
    ];

    foreach ($user_columns as $col) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col");
            echo "<p style='color:green'>✔ Added column to users: $col</p>";
        } catch (PDOException $e) {
            echo "<p style='color:orange'>⚠ Info: Column might already exist - " . $e->getMessage() . "</p>";
        }
    }

    // 2. Create Product Requests Table
    $sql_requests = "CREATE TABLE IF NOT EXISTS product_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        budget REAL DEFAULT NULL,
        status TEXT DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $pdo->exec($sql_requests);
    echo "<p style='color:green'>✔ Checked/Created 'product_requests' table.</p>";

    echo "<h3>Migration Complete!</h3>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Fatal Error: " . $e->getMessage() . "</h3>";
}
