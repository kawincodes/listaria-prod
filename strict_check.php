<?php
require 'includes/db.php';

$required = [
    'id', 'full_name', 'email', 'is_verified_vendor', 'vendor_status', 
    'account_type', 'business_name', 'profile_image', 'profile_views'
];

$stmt = $pdo->query("PRAGMA table_info(users)");
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
