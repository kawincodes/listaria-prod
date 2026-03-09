<?php
require 'includes/db.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_public INTEGER DEFAULT 0");
    echo "Successfully added 'is_public' column to users table.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "'is_public' already exists.\n";
    } else {
        echo "Error adding column: " . $e->getMessage() . "\n";
    }
}

echo "\nVerification of store-related columns:\n";
$stmt = $pdo->query("PRAGMA table_info(users)");
$required = ['id', 'business_name', 'full_name', 'profile_image', 'business_bio', 'account_type', 'is_public'];
$existing = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $existing[] = $row['name'];
}

foreach ($required as $col) {
    if (in_array($col, $existing)) {
        echo "[OK] $col exists\n";
    } else {
        echo "[MISSING] $col\n";
    }
}
?>
