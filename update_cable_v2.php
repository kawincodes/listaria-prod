<?php
require 'includes/db.php';

$id = 41;
$newImages = [
    "uploads/cable_v2_1.png",
    "uploads/cable_v2_2.png",
    "uploads/cable_v2_3.png"
];

$jsonPaths = json_encode($newImages);
$stmt = $pdo->prepare("UPDATE products SET image_paths = ? WHERE id = ?");
if ($stmt->execute([$jsonPaths, $id])) {
    echo "Product 41 (Charging Cable) updated with new images.\n";
} else {
    echo "Update failed.\n";
}
?>
