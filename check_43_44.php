<?php
require 'includes/db.php';

$id = 43;
$stmt = $pdo->prepare("SELECT id, title, image_paths FROM products WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if ($p) {
    echo "ID: " . $p['id'] . " | Title: " . $p['title'] . " | Paths: " . $p['image_paths'] . "\n";
} else {
    echo "Product 43 not found.\n";
}

$id44 = 44;
$stmt = $pdo->prepare("SELECT id, title, image_paths FROM products WHERE id = ?");
$stmt->execute([$id44]);
$p44 = $stmt->fetch();
if ($p44) {
    echo "ID: " . $p44['id'] . " | Title: " . $p44['title'] . " | Paths: " . $p44['image_paths'] . "\n";
}
?>
