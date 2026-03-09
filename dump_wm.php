<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT * FROM products WHERE title LIKE '%Washing Machine%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
