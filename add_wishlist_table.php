<?php
require 'includes/db.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS wishlist (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, product_id)
    );
    ";
    
    $pdo->exec($sql);
    echo "Table 'wishlist' created successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
