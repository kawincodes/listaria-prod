<?php
require 'includes/db.php';

try {
    $sql = "ALTER TABLE products ADD COLUMN category VARCHAR(100) DEFAULT 'All'";
    $pdo->exec($sql);
    echo "Successfully added 'category' column to products table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'category' already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
