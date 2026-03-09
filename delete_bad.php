<?php
require 'includes/db.php';
$stmt = $pdo->exec("DELETE FROM products WHERE description = 'Mumbai, India'");
echo "Deleted " . $stmt . " rows.\n";
?>
