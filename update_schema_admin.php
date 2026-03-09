<?php
require 'includes/db.php';

try {
    // Add is_admin column to users
    // SQLite: separate try-catch since column might exist
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT DEFAULT 0");
        echo "Added is_admin column.\n";
    } catch (PDOException $e) {
        echo "is_admin column likely exists.\n";
    }

    // Create a default admin user
    // Email: admin@listaria.com, Password: admin123(hashed)
    $email = 'admin@listaria.com';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $name = 'Admin';
    $phone = '0000000000';
    $address = 'HQ';
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "Admin user already exists.\n";
        // Update to ensure is_admin is 1
        $pdo->prepare("UPDATE users SET is_admin = 1 WHERE email = ?")->execute([$email]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, address, is_admin) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$name, $email, $password, $phone, $address]);
        echo "Admin user created: admin@listaria.com / admin123\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
