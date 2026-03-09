<?php
require 'includes/db.php';

try {
    echo "--- Admin Users ---\n";
    $stmt = $pdo->query("SELECT id, full_name, email, is_admin FROM users WHERE is_admin = 1");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($admins) > 0) {
        foreach ($admins as $admin) {
            echo "ID: {$admin['id']} | Name: {$admin['full_name']} | Email: {$admin['email']}\n";
        }
    } else {
        echo "No admin users found.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
