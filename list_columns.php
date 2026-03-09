<?php
require 'includes/db.php';

function listColumns($table, $pdo) {
    echo "Columns in $table:\n";
    $stmt = $pdo->query("PRAGMA table_info($table)");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['name'] . " (" . $row['type'] . ")\n";
    }
    echo "\n";
}

listColumns('users', $pdo);
listColumns('products', $pdo);
?>
