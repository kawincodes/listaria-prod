<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='products'");
echo "Products Table Schema:\n";
echo $stmt->fetchColumn() . "\n\n";

$stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
echo "Users Table Schema:\n";
echo $stmt->fetchColumn() . "\n\n";
