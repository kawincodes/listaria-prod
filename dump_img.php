<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT id, title, image_paths FROM products WHERE title LIKE '%Prada Vintage%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
