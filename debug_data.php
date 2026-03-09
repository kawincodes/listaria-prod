<?php
require 'includes/db.php';

try {
    echo "--- Users ---\n";
    $stmt = $pdo->query("SELECT id, full_name, email, created_at FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        echo "ID: {$user['id']} | Name: {$user['full_name']} | Email: {$user['email']} | Created: {$user['created_at']}\n";
    }

    echo "\n--- Products (Listing sample) ---\n";
    // Check if products have a user_id column
    $cols = $pdo->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_ASSOC);
    $has_user_id = false;
    foreach ($cols as $col) {
        if ($col['name'] == 'user_id') $has_user_id = true;
    }

    if ($has_user_id) {
        $stmt = $pdo->query("SELECT id, title, user_id FROM products LIMIT 10");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as $p) {
            echo "Product ID: {$p['id']} | Title: {$p['title']} | User ID: {$p['user_id']}\n";
        }
        
        echo "\n--- Product Counts per User ID ---\n";
        $stmt = $pdo->query("SELECT user_id, COUNT(*) as count FROM products GROUP BY user_id");
        $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($counts as $c) {
            echo "User ID: {$c['user_id']} has {$c['count']} products.\n";
        }
    } else {
        echo "Products table does not have a user_id column!\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
