<?php
require 'includes/db.php';
$stmt = $pdo->query("PRAGMA table_info(orders)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($columns);
echo "</pre>";
?>
