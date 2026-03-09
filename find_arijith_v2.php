<?php
require 'includes/db.php';

$stmt = $pdo->query("SELECT id, title, image_paths FROM products WHERE title LIKE '%Arijith Singh%'");
$products = $stmt->fetchAll();

foreach ($products as $p) {
    echo "ID: " . $p['id'] . " | Title: " . $p['title'] . "\n";
    echo "Paths: " . $p['image_paths'] . "\n";
}
?>
