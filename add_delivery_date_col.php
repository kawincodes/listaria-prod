<?php
require 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN delivery_date TEXT DEFAULT NULL");
    echo "Column added successfully";
} catch (PDOException $e) {
    echo "Error (maybe exists): " . $e->getMessage();
}
?>
