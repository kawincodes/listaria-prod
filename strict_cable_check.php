<?php
require 'includes/db.php';

$id = 41;
$stmt = $pdo->prepare("SELECT image_paths FROM products WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();

if ($p) {
    echo "RAW DB PATH: " . $p['image_paths'] . "\n";
    $paths = json_decode($p['image_paths'], true);
    if ($paths) {
        foreach ($paths as $path) {
            echo "DECODED PATH: $path\n";
            if (file_exists($path)) {
                echo "DISK STATUS: EXISTS\n";
            } else {
                echo "DISK STATUS: MISSING\n";
            }
        }
    } else {
        echo "JSON DECODE FAILED\n";
    }
} else {
    echo "ID 41 NOT FOUND\n";
}
?>
