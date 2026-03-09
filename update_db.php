<?php
require 'includes/db.php';

try {
    // 1. Create Orders Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");
    echo "Orders table created or already exists.\n";

    // 2. Add Status Column to Products
    // SQLite doesn't support IF NOT EXISTS for columns easily, so we wrap in try-catch
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN status TEXT DEFAULT 'available'");
        echo "Status column added to products.\n";
    } catch (PDOException $e) {
        // Expected if column exists
        echo "Status column likely already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
