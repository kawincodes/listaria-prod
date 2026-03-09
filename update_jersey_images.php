<?php
require 'includes/db.php';

// First, get the product ID for the Jersey
$stmt = $pdo->prepare("SELECT id FROM products WHERE title LIKE '%FC Barcelona 15/16 Home Messi Jersey%' LIMIT 1");
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.\n");
}

$productId = $product['id'];
echo "Found Product ID: $productId\n";

// New image paths
$newImages = [
    "uploads/barca_jersey_front.jpg",
    "uploads/barca_jersey_sleeve.jpg",
    "uploads/barca_jersey_back.jpg",
    "uploads/barca_jersey_tag.jpg"
];

$jsonPaths = json_encode($newImages);

$updateStmt = $pdo->prepare("UPDATE products SET image_paths = ? WHERE id = ?");
if ($updateStmt->execute([$jsonPaths, $productId])) {
    echo "Database updated successfully with new image paths.\n";
} else {
    echo "Failed to update database.\n";
}
?>
