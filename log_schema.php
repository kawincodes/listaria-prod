<?php
require 'includes/db.php';

$output = "--- USERS TABLE ---\n";
$stmt = $pdo->query("PRAGMA table_info(users)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $output .= "- " . $row['name'] . "\n";
}

file_put_contents('debug_schema.txt', $output);
echo "Schema written to debug_schema.txt\n";
?>
