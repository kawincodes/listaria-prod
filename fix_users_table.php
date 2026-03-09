<?php
require 'includes/db.php';

$columns = [
    'is_verified_vendor' => 'INTEGER DEFAULT 0',
    'vendor_status' => "TEXT DEFAULT 'none'",
    'account_type' => "TEXT DEFAULT 'customer'",
    'business_name' => 'TEXT',
    'profile_image' => 'TEXT'
];

foreach ($columns as $column => $type) {
    echo "Checking column '$column'... ";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN $column $type");
        echo "ADDED\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "ALREADY EXISTS\n";
        } else {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nDone.\n";
?>
