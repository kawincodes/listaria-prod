<?php
require 'includes/db.php';

$new_password = 'adminpanel@listaria@20010';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // Update all admins
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE is_admin = 1");
    $stmt->execute([$hashed_password]);
    $count = $stmt->rowCount();
    
    echo "Updated password for $count admin user(s).\n";
    
    // List who was updated
    $stmt = $pdo->query("SELECT id, full_name, email FROM users WHERE is_admin = 1");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($admins as $admin) {
        echo " - Updated: {$admin['full_name']} ({$admin['email']})\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
