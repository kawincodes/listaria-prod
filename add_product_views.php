<?php
require 'includes/db.php';

try {
    $sql = "ALTER TABLE products ADD COLUMN views INTEGER DEFAULT 0";
    $pdo->exec($sql);
    echo "Added 'views' column to products table.<br>";
} catch (PDOException $e) {
    echo "Error adding 'views' (might already exist): " . $e->getMessage() . "<br>";
}
?>
