<?php
require 'includes/db.php';

echo "--- SEARCHING FOR PRODUCT 'Loq' ---\n";
$stmt = $pdo->prepare("SELECT id, title, image_paths FROM products WHERE title LIKE '%Loq%'");
$stmt->execute();
$products = $stmt->fetchAll();

foreach ($products as $p) {
    echo "ID: " . $p['id'] . "\n";
    echo "Title: " . $p['title'] . "\n";
    echo "Image Paths (RAW): " . $p['image_paths'] . "\n";
    
    $paths = json_decode($p['image_paths'], true);
    if ($paths) {
        foreach ($paths as $path) {
            echo "Checking path: $path -> " . (file_exists($path) ? "EXISTS" : "MISSING") . "\n";
        }
    } else {
        echo "Failed to decode JSON image paths.\n";
    }
    echo "--------------------------\n";
}
?>
