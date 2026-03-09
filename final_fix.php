<?php
require 'includes/db.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columns = [
    'rejection_reason' => 'TEXT',
    'vendor_applied_at' => 'DATETIME',
    'business_bio' => 'TEXT',
    'whatsapp_number' => 'TEXT',
    'gst_number' => 'TEXT'
];

foreach ($columns as $column => $type) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN $column $type");
        echo "Successfully added $column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate column name') !== false) {
            echo "$column already exists\n";
        } else {
            echo "Error adding $column: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n--- Verification ---\n";
$stmt = $pdo->query("PRAGMA table_info(users)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['name'] . "\n";
}
?>
