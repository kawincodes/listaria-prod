<?php
require __DIR__ . '/includes/db.php';

echo "Starting Vendor Verification DB Migration...\n";

// Array of columns to add: 'column_name' => 'column_definition'
$columnsToAdd = [
    'vendor_status' => "TEXT DEFAULT 'none'", // none, pending, approved, rejected
    'is_verified_vendor' => "INTEGER DEFAULT 0",
    'rejection_reason' => "TEXT DEFAULT NULL",
    'vendor_applied_at' => "DATETIME DEFAULT NULL",
    'business_name' => "TEXT DEFAULT NULL",
    'business_bio' => "TEXT DEFAULT NULL",
    'whatsapp_number' => "TEXT DEFAULT NULL"
];

// Get existing columns
$existingCols = [];
$stmt = $pdo->query("PRAGMA table_info(users)");
while ($row = $stmt->fetch()) {
    $existingCols[] = $row['name'];
}

foreach ($columnsToAdd as $colName => $colDef) {
    if (!in_array($colName, $existingCols)) {
        try {
            $sql = "ALTER TABLE users ADD COLUMN $colName $colDef";
            $pdo->exec($sql);
            echo "[SUCCESS] Added column: $colName\n";
        } catch (PDOException $e) {
            echo "[ERROR] Failed to add column $colName: " . $e->getMessage() . "\n";
        }
    } else {
        echo "[INFO] Column $colName already exists. Skipping.\n";
    }
}

echo "Migration completed.\n";
?>
