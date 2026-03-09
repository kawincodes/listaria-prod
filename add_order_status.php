<?php
require 'includes/db.php';

try {
    echo "Adding order_status column to orders table...\n";
    
    // SQLite: Add column if not exists
    $sql = "ALTER TABLE orders ADD COLUMN order_status TEXT DEFAULT 'Item Collected'";
    $pdo->exec($sql);
    
    echo "Column 'order_status' added successfully.\n";
    
} catch (PDOException $e) {
    echo "Error (might already exist): " . $e->getMessage() . "\n";
}
?>
