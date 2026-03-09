<?php
require 'includes/db.php';

$id = 41;
$stmt = $pdo->prepare("SELECT id, title, image_paths FROM products WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if ($p) {
    echo "ID: " . $p['id'] . "\n";
    echo "Title: " . $p['title'] . "\n";
    echo "Image Paths: " . $p['image_paths'] . "\n";
} else {
    echo "Product ID $id not found.\n";
}
?>
