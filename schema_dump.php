<?php
require 'includes/db.php';
$stmt = $pdo->query('PRAGMA table_info(products)');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['name'] . " - NotNull: " . $col['notnull'] . " - Default: " . $col['dflt_value'] . "\n";
}
?>
