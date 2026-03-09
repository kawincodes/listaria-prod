<?php
require 'includes/db.php';

try {
    // 1. Get the latest user (assuming the user just logged in)
    $stmt = $pdo->query("SELECT id, full_name, email FROM users ORDER BY id DESC LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "No users found. Please log in first to create your account.\n";
        exit;
    }

    echo "Found user: {$user['full_name']} (ID: {$user['id']})\n";
    $new_user_id = $user['id'];

    // 2. Count orphaned products
    // SQLite: valid subquery
    $countStmt = $pdo->query("SELECT COUNT(*) FROM products WHERE user_id NOT IN (SELECT id FROM users)");
    $orphaned_count = $countStmt->fetchColumn();

    echo "Found $orphaned_count orphaned products (belonging to deleted users).\n";

    if ($orphaned_count > 0) {
        // 3. Re-assign them
        $updateStmt = $pdo->prepare("UPDATE products SET user_id = ? WHERE user_id NOT IN (SELECT id FROM users)");
        $updateStmt->execute([$new_user_id]);
        
        echo "Successfully assigned $orphaned_count products to user {$user['full_name']}.\n";
    } else {
        echo "No orphaned products found.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
