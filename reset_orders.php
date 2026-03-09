<?php
require 'includes/db.php';

try {
    // 1. Reset Orders
    $count = $pdo->exec("DELETE FROM orders");
    echo "Deleted $count rows from 'orders'.\n";
    
    // Reset Auto Increment for orders if sqlite_sequence exists
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name='orders'");
    echo "Reset auto-increment for 'orders'.\n";

    // 2. Check for transactions table and reset if exists
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='transactions'")->fetchAll();
    if (count($tables) > 0) {
        $countTrx = $pdo->exec("DELETE FROM transactions");
        echo "Deleted $countTrx rows from 'transactions'.\n";
        $pdo->exec("DELETE FROM sqlite_sequence WHERE name='transactions'");
    } else {
        echo "Table 'transactions' not found (skipping).\n";
    }

    echo "Successfully reset all transaction and revenue data.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
