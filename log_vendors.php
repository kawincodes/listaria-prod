<?php
require 'includes/db.php';

$output = "--- VENDOR STATUS OVERVIEW ---\n";
$stmt = $pdo->query("SELECT id, full_name, email, account_type, vendor_status, is_public, is_verified_vendor FROM users WHERE account_type = 'vendor' OR vendor_status != 'none'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $output .= "ID: " . $row['id'] . "\n";
    $output .= "Name: " . $row['full_name'] . "\n";
    $output .= "Email: " . $row['email'] . "\n";
    $output .= "Account Type: " . $row['account_type'] . "\n";
    $output .= "Vendor Status: " . $row['vendor_status'] . "\n";
    $output .= "Is Public: " . $row['is_public'] . "\n";
    $output .= "Is Verified: " . $row['is_verified_vendor'] . "\n";
    $output .= "--------------------------\n";
}
file_put_contents('vendor_status.txt', $output);
echo "Report written to vendor_status.txt\n";
?>
