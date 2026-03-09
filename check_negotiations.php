<?php
require 'includes/db.php';

echo "--- NEGOTIATIONS TABLE ---\n";
try {
    $stmt = $pdo->query("PRAGMA table_info(negotiations)");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['name'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
