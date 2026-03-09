<?php
require 'includes/db.php';
$stmt = $pdo->query("PRAGMA table_info(products)");
$columns = $stmt->fetchAll();
foreach ($columns as $column) {
    echo $column['name'] . " (" . $column['type'] . ")\n";
}
