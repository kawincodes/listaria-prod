<?php
require 'includes/db.php';

echo "--- VENDOR STATUS OVERVIEW ---\n";
$stmt = $pdo->query("SELECT id, full_name, email, account_type, vendor_status, is_public, is_verified_vendor FROM users WHERE account_type = 'vendor' OR vendor_status != 'none'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['full_name'] . "\n";
    echo "Email: " . $row['email'] . "\n";
    echo "Account Type: " . $row['account_type'] . "\n";
    echo "Vendor Status: " . $row['vendor_status'] . "\n";
    echo "Is Public: " . $row['is_public'] . "\n";
    echo "Is Verified: " . $row['is_verified_vendor'] . "\n";
    echo "--------------------------\n";
}
?>
