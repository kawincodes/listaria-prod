<?php
require 'includes/db.php';

echo "--- SEARCHING FOR REAL MADRID JERSEY PRODUCT ---\n";
$stmt = $pdo->prepare("SELECT id, title, image_paths FROM products WHERE title LIKE '%Real Madrid%'");
$stmt->execute();
$products = $stmt->fetchAll();

foreach ($products as $p) {
    echo "ID: " . $p['id'] . "\n";
    echo "Title: " . $p['title'] . "\n";
    echo "Image Paths: " . $p['image_paths'] . "\n";
    echo "--------------------------\n";
}
?>
