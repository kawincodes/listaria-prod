<?php
require 'includes/db.php';

// Correct ID for Arijith Singh Tee is 44
$productId = 44;
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
    echo "Database updated successfully for Arijith Singh Tee (ID 44).\n";
} else {
    echo "Failed to update database.\n";
}
?>
