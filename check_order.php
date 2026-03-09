<?php
require 'includes/db.php';
try {
    $stmt = $pdo->query("SELECT id FROM orders ORDER BY id DESC LIMIT 1");
    $id = $stmt->fetchColumn();
    if ($id) {
        echo "Order Found: " . $id;
    } else {
        echo "No Orders";
        // Create one
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, product_id, amount, status, created_at) VALUES (1, 1, 30085.00, 'pending', datetime('now'))");
        $stmt->execute();
        echo "\nCreated Order: " . $pdo->lastInsertId();
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
