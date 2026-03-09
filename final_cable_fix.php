<?php
require 'includes/db.php';

$files = glob("uploads/699f246bc3a58*");
if ($files) {
    $filePath = $files[0];
    echo "Found file: $filePath\n";
    
    $paths = json_encode([$filePath]);
    $stmt = $pdo->prepare("UPDATE products SET image_paths = ? WHERE id = 41");
    if ($stmt->execute([$paths])) {
        echo "Successfully updated ID 41 with path: $paths\n";
    } else {
        echo "Database update failed.\n";
    }
} else {
    echo "Cable image file not found in uploads/.\n";
    
    // Fallback: search for ANY cable image
    $allFiles = glob("uploads/699f*");
    echo "Found " . count($allFiles) . " total 699f files.\n";
    if (count($allFiles) > 0) {
        $filePath = $allFiles[0];
        echo "Using fallback file: $filePath\n";
        $paths = json_encode([$filePath]);
        $stmt = $pdo->prepare("UPDATE products SET image_paths = ? WHERE id = 41");
        $stmt->execute([$paths]);
    }
}
?>
