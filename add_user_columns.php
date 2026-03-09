<?php
require 'includes/db.php';

try {
    // Add address to users
    try {
        $sqlUsers = "ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL";
        $pdo->exec($sqlUsers);
        echo "Successfully added 'address' column to users table.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Column 'address' already exists in users table.\n";
        } else {
            echo "Error adding address: " . $e->getMessage() . "\n";
        }
    }

    // Add user_id to products
    try {
        $sqlProducts = "ALTER TABLE products ADD COLUMN user_id INT DEFAULT NULL";
        $pdo->exec($sqlProducts);
        echo "Successfully added 'user_id' column to products table.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Column 'user_id' already exists in products table.\n";
        } else {
            echo "Error adding user_id: " . $e->getMessage() . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Critical Error: " . $e->getMessage();
}
?>
