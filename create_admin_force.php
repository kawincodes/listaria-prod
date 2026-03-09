<?php
require 'includes/db.php';

echo "Attempting to reset admin user...\n";

try {
    // 1. Delete if exists
    $pdo->prepare("DELETE FROM users WHERE email = ?")->execute(['admin@listaria.com']);
    echo "Deleted old admin entry (if any).\n";

    // 2. Create New Admin
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, is_admin) VALUES (?, ?, ?, 1)");
    $stmt->execute(['Super Admin', 'admin@listaria.com', $password]);
    
    echo "Admin user created successfully!\n";
    echo "Email: admin@listaria.com\n";
    echo "Pass:  admin123\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
