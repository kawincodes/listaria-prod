<?php
require 'includes/db.php';

$output = "--- SEARCHING FOR PRODUCT 'Loq' ---\n";
$stmt = $pdo->prepare("SELECT id, title, image_paths FROM products WHERE title LIKE '%Loq%'");
$stmt->execute();
$products = $stmt->fetchAll();

foreach ($products as $p) {
    $output .= "ID: " . $p['id'] . "\n";
    $output .= "Title: " . $p['title'] . "\n";
    $output .= "Image Paths (RAW): " . $p['image_paths'] . "\n";
    
    $paths = json_decode($p['image_paths'], true);
    if ($paths) {
        foreach ($paths as $path) {
            $output .= "Checking path: $path -> " . (file_exists($path) ? "EXISTS" : "MISSING") . "\n";
        }
    } else {
        $output .= "Failed to decode JSON image paths.\n";
    }
    $output .= "--------------------------\n";
}
file_put_contents('loq_debug.txt', $output);
echo "Analysis written to loq_debug.txt\n";
?>
