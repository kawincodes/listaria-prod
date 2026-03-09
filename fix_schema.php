<?php
require 'includes/db.php';

echo "<h2>Fixing Database Schema...</h2>";

try {
    // 1. Add order_status if not exists
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN order_status TEXT DEFAULT 'Pending'");
        echo "Added 'order_status' column.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column') !== false) {
             echo "'order_status' already exists.<br>";
        } else {
             echo "Note on order_status: " . $e->getMessage() . "<br>";
        }
    }

    // 2. Add transaction_id if not exists
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN transaction_id TEXT DEFAULT NULL");
        echo "Added 'transaction_id' column.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column') !== false) {
             echo "'transaction_id' already exists.<br>";
        } else {
             echo "Note on transaction_id: " . $e->getMessage() . "<br>";
        }
    }
    
    // 3. Add payment_status if not exists (Admin panel code I added uses it, plan said so)
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN payment_status TEXT DEFAULT NULL");
        echo "Added 'payment_status' column.<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column') !== false) {
             echo "'payment_status' already exists.<br>";
        } else {
             echo "Note on payment_status: " . $e->getMessage() . "<br>";
        }
    }

    echo "<h3>Success! Schema updated.</h3>";
    echo "<a href='index.php'>Go Home</a>";

} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage();
}
?>
