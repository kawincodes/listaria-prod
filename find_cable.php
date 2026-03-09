<?php
require 'includes/db.php';

echo "--- SEARCHING FOR CHARGING CABLE ---\n";
$stmt = $pdo->prepare("SELECT id, title, image_paths FROM products WHERE title LIKE '%Charging Cable%'");
$stmt->execute();
$products = $stmt->fetchAll();

foreach ($products as $p) {
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
    echo "--------------------------\n";
}
?>
