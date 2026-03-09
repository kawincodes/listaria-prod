<?php
require 'includes/db.php';

try {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([1]);
    
    if ($stmt->rowCount() > 0) {
        echo "Successfully deleted Product ID 1 (iPhone 13).";
    } else {
        echo "Product ID 1 not found or already deleted.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
