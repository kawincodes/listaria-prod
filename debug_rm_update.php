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
    
    $paths = json_decode($p['image_paths'], true);
    if ($paths) {
        foreach ($paths as $path) {
            if (file_exists($path)) {
                echo "FILE EXISTS: $path\n";
            } else {
                echo "FILE MISSING: $path\n";
            }
        }
    } else {
        echo "Failed to decode JSON image_paths.\n";
    }
} else {
    echo "Product ID $id not found.\n";
}

echo "\n--- SEARCHING AGAIN BY TITLE ---\n";
$stmt = $pdo->prepare("SELECT id, title, image_paths FROM products WHERE title LIKE '%Real Madrid%'");
$stmt->execute();
$all = $stmt->fetchAll();
foreach ($all as $item) {
    echo "ID: " . $item['id'] . " | Title: " . $item['title'] . " | Paths: " . $item['image_paths'] . "\n";
}
?>
