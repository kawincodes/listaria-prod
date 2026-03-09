<?php
require 'includes/db.php';

$stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='product_requests'");
$schema = $stmt->fetchColumn();

echo "Schema for product_requests:\n";
echo $schema;
?>
