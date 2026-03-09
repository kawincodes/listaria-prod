<?php
require 'includes/db.php';

echo "--- USERS TABLE ---\n";
$stmt = $pdo->query("PRAGMA table_info(users)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['name'] . "\n";
}

echo "\n--- PRODUCTS TABLE ---\n";
$stmt = $pdo->query("PRAGMA table_info(products)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['name'] . "\n";
}
?>
