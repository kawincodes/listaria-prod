<?php
require 'includes/db.php';
$stmt = $pdo->prepare("UPDATE products SET status = 'sold', approval_status = 'approved', is_published = 1 WHERE id = 17");
$stmt->execute();
echo "Product 17 marked as sold.";
?>
