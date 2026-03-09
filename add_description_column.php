<?php
require 'includes/db.php';

try {
    $sql = "ALTER TABLE products ADD COLUMN description TEXT DEFAULT NULL";
    $pdo->exec($sql);
    echo "Successfully added 'description' column to products table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'description' already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
