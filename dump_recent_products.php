<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT id, title, category, approval_status, condition_tag FROM products ORDER BY id DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$out = "";
foreach($rows as $r) {
    $out .= "ID: {$r['id']} | Title: {$r['title']} | Category: {$r['category']} | Status: {$r['approval_status']} | Condition: {$r['condition_tag']}\n";
}
file_put_contents('recent_products_utf8.txt', $out);
echo "Done.";
