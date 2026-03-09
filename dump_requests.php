<?php
require 'includes/db.php';

$stmt = $pdo->query("SELECT * FROM product_requests ORDER BY created_at DESC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($requests, JSON_PRETTY_PRINT);
?>
