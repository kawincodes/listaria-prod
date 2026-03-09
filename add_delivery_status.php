<?php
require 'includes/db.php';

try {
    echo "Adding delivery_status column to products table...\n";
    
    // Check if column exists (SQLite specific check, but simple alter typically works or throws if exists)
    // For SQLite, ADD COLUMN is supported.
    
    $sql = "ALTER TABLE products ADD COLUMN delivery_status TEXT DEFAULT 'Processing'";
    $pdo->exec($sql);
    
    echo "Column 'delivery_status' added successfully.\n";
    
} catch (PDOException $e) {
    echo "Error (might already exist): " . $e->getMessage() . "\n";
}
?>
