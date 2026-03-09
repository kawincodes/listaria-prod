<?php
require 'includes/db.php';
$stmt = $pdo->query("PRAGMA table_info(products)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($columns);
echo "</pre>";
?>
