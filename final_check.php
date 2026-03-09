<?php
require 'includes/db.php';

$stmt = $pdo->query("PRAGMA table_info(users)");
echo "Final USER columns:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['name'] . "\n";
}
?>
