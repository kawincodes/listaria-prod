<?php
require 'includes/db.php';
$stmt = $pdo->query("PRAGMA table_info(products)");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $c) {
    if ($c['notnull'] == 1 && $c['dflt_value'] === null) {
        echo $c['name'] . " is REQUIRED and has NO DEFAULT\n";
    }
}
