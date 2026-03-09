<?php
require 'includes/db.php';

// Product ID for Arijith Singh Tee is 43
$productId = 43;
echo "Updating Product ID: $productId\n";

// New image paths
$newImages = [
    "uploads/arijith_tee_front.jpg",
    "uploads/arijith_tee_detail.jpg",
    "uploads/arijith_tee_collar.jpg",
    "uploads/arijith_tee_back.jpg"
];

$jsonPaths = json_encode($newImages);

$updateStmt = $pdo->prepare("UPDATE products SET image_paths = ? WHERE id = ?");
if ($updateStmt->execute([$jsonPaths, $productId])) {
    echo "Database updated successfully with new image paths for Arijith Singh Tee.\n";
} else {
    echo "Failed to update database.\n";
}
?>
