<?php
require 'includes/db.php';
// Use product ID 17 (Samsung s25 ultra) since it's prominent, or 16 (Watch)
try {
    $stmt = $pdo->prepare("UPDATE products SET status = 'sold' WHERE id = 17");
    $stmt->execute();
    echo "Product 17 (Samsung s25 ultra) marked as sold successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
