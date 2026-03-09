<?php
require 'includes/db.php';

$output = "--- USERS TABLE COLUMNS ---\n";
$stmt = $pdo->query("PRAGMA table_info(users)");
$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $output .= ($count + 1) . ". " . $row['name'] . "\n";
    $count++;
}
$output .= "\nTOTAL: $count\n";

file_put_contents('full_schema_log.txt', $output);
echo "Schema written to full_schema_log.txt\n";
?>
