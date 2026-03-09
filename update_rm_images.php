<?php
require 'includes/db.php';

// Product ID for Real Madrid Jersey is 41
$productId = 41;
echo "Updating Product ID: $productId\n";

// New image paths
$newImages = [
    "uploads/real_madrid_front.jpg",
    "uploads/real_madrid_collar.jpg",
    "uploads/real_madrid_flat.jpg",
    "uploads/real_madrid_back.jpg"
];

$jsonPaths = json_encode($newImages);

$updateStmt = $pdo->prepare("UPDATE products SET image_paths = ? WHERE id = ?");
if ($updateStmt->execute([$jsonPaths, $productId])) {
    echo "Database updated successfully with new image paths for Real Madrid Jersey.\n";
} else {
    echo "Failed to update database.\n";
}
?>
