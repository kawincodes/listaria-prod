<?php
require 'includes/db.php';

// Correct ID for Real Madrid Jersey is 45
$correctId = 45;
$wrongId = 41;

echo "Correcting Product ID: $correctId\n";

// New image paths for Real Madrid (ID 45)
$newImages = [
    "uploads/real_madrid_front.jpg",
    "uploads/real_madrid_collar.jpg",
    "uploads/real_madrid_flat.jpg",
    "uploads/real_madrid_back.jpg"
];

$jsonPaths = json_encode($newImages);

$stmt = $pdo->prepare("UPDATE products SET image_paths = ? WHERE id = ?");
if ($stmt->execute([$jsonPaths, $correctId])) {
    echo "Product 45 (Real Madrid) updated successfully.\n";
}

// RESTORE PRODUCT 41 (I accidentally broke it)
// It had these paths before (from my previous truncated output analysis):
// ["uploads\/699f2521c2c0_1000419874.jpg"]
$oldPaths = '["uploads\/699f2521c2c0_1000419874.jpg"]';
$stmt = $pdo->prepare("UPDATE products SET image_paths = ? WHERE id = ?");
if ($stmt->execute([$oldPaths, $wrongId])) {
    echo "Product 41 restored successfully.\n";
}
?>
