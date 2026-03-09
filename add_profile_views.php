<?php
require 'includes/db.php';

try {
    $sql = "ALTER TABLE users ADD COLUMN profile_views INTEGER DEFAULT 0";
    $pdo->exec($sql);
    echo "Added 'profile_views' column to users table.<br>";
} catch (PDOException $e) {
    echo "Error adding 'profile_views' (might already exist): " . $e->getMessage() . "<br>";
}
?>
