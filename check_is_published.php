<?php
require 'includes/db.php';
$stmt = $pdo->query("PRAGMA table_info(products)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
