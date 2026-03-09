<?php
require 'includes/db.php';

try {
    echo "Checking schema...\n";

    // 1. Ensure 'orders' table has 'order_status' (renamed from status to avoid reserved word conflicts if any, but plan said status. Let's stick to plan but use 'order_status' as seen in admin_dashboard code already?
    // Admin dashboard uses `order_status` in the update query: UPDATE orders SET order_status = ?...
    // Let's check what currently exists.
    
    // Check if 'order_status' column exists in 'orders' table
    $cols = $pdo->query("PRAGMA table_info(orders)")->fetchAll(PDO::FETCH_ASSOC);
    $hasOrderStatus = false;
    $hasTransactionId = false;

    foreach ($cols as $col) {
        if ($col['name'] === 'order_status') $hasOrderStatus = true;
        if ($col['name'] === 'transaction_id') $hasTransactionId = true;
    }

    if (!$hasOrderStatus) {
        echo "Adding 'order_status' column...\n";
        $pdo->exec("ALTER TABLE orders ADD COLUMN order_status TEXT DEFAULT 'Pending'");
    } else {
        echo "'order_status' column already exists.\n";
    }

    if (!$hasTransactionId) {
        echo "Adding 'transaction_id' column...\n";
        $pdo->exec("ALTER TABLE orders ADD COLUMN transaction_id TEXT DEFAULT NULL");
    } else {
        echo "'transaction_id' column already exists.\n";
    }

    echo "Schema update complete.\n";

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
?>
