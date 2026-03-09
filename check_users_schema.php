<?php
require 'includes/db.php';

echo "<h2>Users Schema Check</h2>";
$stmt = $pdo->query("PRAGMA table_info(users)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$missingCols = ['kyc_status', 'role', 'wallet_balance', 'status'];
$foundCols = [];

echo "<table border='1'><tr><th>CID</th><th>Name</th><th>Type</th></tr>";
foreach ($columns as $col) {
    echo "<tr><td>{$col['cid']}</td><td>{$col['name']}</td><td>{$col['type']}</td></tr>";
    if (in_array($col['name'], $missingCols)) $foundCols[] = $col['name'];
}
echo "</table>";

foreach ($missingCols as $col) {
    if (!in_array($col, $foundCols)) {
        echo "<p style='color:red;'>$col is MISSING. Attempting to add...</p>";
        try {
            if ($col == 'wallet_balance') {
                $pdo->exec("ALTER TABLE users ADD COLUMN wallet_balance DECIMAL(10,2) DEFAULT 0.00");
            } else {
                $val = ($col == 'status') ? "DEFAULT 'active'" : "DEFAULT NULL";
                $pdo->exec("ALTER TABLE users ADD COLUMN $col VARCHAR(50) $val");
            }
            echo "Added $col.<br>";
        } catch (Exception $e) { echo "Error adding $col: ".$e->getMessage() . "<br>"; }
    } else {
        echo "<p style='color:green;'>$col exists.</p>";
    }
}
?>
