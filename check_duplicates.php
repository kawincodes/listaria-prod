<?php
require 'includes/db.php';

$stmt = $pdo->query("SELECT id, title, created_at FROM products ORDER BY id DESC LIMIT 10");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Last 10 Products:\n";
foreach ($products as $p) {
    echo "ID: " . $p['id'] . " | Title: " . $p['title'] . " | Created: " . $p['created_at'] . "\n";
}
?>
