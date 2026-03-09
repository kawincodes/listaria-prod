<?php
require 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS coupon_usage (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        coupon_code TEXT NOT NULL,
        used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    echo "Table coupon_usage created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
