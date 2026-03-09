<?php
require 'includes/db.php';

$stmt = $pdo->query("PRAGMA table_info(users)");
$cols = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cols[] = $row['name'];
}

echo "TOTAL COLUMNS: " . count($cols) . "\n";
echo "COLUMNS:\n";
foreach ($cols as $c) {
    echo "- $c\n";
}
?>
