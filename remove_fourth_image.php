<?php
require 'includes/db.php';

$id = 45;
$stmt = $pdo->prepare("SELECT image_paths FROM products WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if ($p) {
    $paths = json_decode($p['image_paths'], true);
    if (count($paths) >= 4) {
        $removed = array_splice($paths, 3, 1);
        echo "Removed: " . $removed[0] . "\n";
        
        $jsonPaths = json_encode($paths);
        $updateStmt = $pdo->prepare("UPDATE products SET image_paths = ? WHERE id = ?");
        if ($updateStmt->execute([$jsonPaths, $id])) {
            echo "Successfully removed the fourth image.\n";
            echo "New paths: $jsonPaths\n";
        }
    } else {
        echo "Product only has " . count($paths) . " images. Nothing to remove at index 4.\n";
    }
} else {
    echo "Product not found.\n";
}
?>
