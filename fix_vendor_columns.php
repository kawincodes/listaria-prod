<?php
require 'includes/db.php';

$columns = [
    'rejection_reason' => 'TEXT',
    'vendor_applied_at' => 'DATETIME',
    'business_bio' => 'TEXT',
    'whatsapp_number' => 'TEXT',
    'gst_number' => 'TEXT'
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
