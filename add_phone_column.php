<?php
require 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN phone TEXT DEFAULT NULL");
    echo "Added 'phone' column to users table.\n";
} catch (PDOException $e) {
    echo "Error adding column: " . $e->getMessage() . "\n";
}
?>
