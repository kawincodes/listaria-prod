<?php
require 'includes/db.php';

try {
    // Add location column
    $sql = "ALTER TABLE products ADD COLUMN location VARCHAR(255) NOT NULL DEFAULT 'Bangalore, India'";
    $pdo->exec($sql);
    echo "Added 'location' column.<br>";
} catch (PDOException $e) {
    echo "Error adding 'location' (might already exist): " . $e->getMessage() . "<br>";
}

try {
    // Add video_path column
    $sql = "ALTER TABLE products ADD COLUMN video_path VARCHAR(255) DEFAULT NULL";
    $pdo->exec($sql);
    echo "Added 'video_path' column.<br>";
} catch (PDOException $e) {
    echo "Error adding 'video_path' (might already exist): " . $e->getMessage() . "<br>";
}
?>
