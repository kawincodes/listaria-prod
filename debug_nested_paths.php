<?php
require 'includes/db.php';

echo "--- CHECKING FOR NESTED PATHS IN DB ---\n";
$stmt = $pdo->query("SELECT id, title, image_paths FROM products WHERE image_paths LIKE '%uploads/uploads/%'");
$products = $stmt->fetchAll();

if (empty($products)) {
    echo "No products found with 'uploads/uploads/' in their paths.\n";
} else {
    foreach ($products as $p) {
        echo "ID: " . $p['id'] . " | Title: " . $p['title'] . " | Paths: " . $p['image_paths'] . "\n";
    }
}

echo "\n--- CHECKING FOR MISSING FILES IN DB (ROOT UPLOADS VS NESTED) ---\n";
$stmt = $pdo->query("SELECT id, title, image_paths FROM products LIMIT 50");
$all = $stmt->fetchAll();

foreach ($all as $p) {
    $paths = json_decode($p['image_paths'], true);
    if ($paths) {
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                $nested = str_replace('uploads/', 'uploads/uploads/', $path);
                if (file_exists($nested)) {
                    echo "ID " . $p['id'] . " ($p[title]): Path '$path' MISSING but found at '$nested'\n";
                }
            }
        }
    }
}
?>
