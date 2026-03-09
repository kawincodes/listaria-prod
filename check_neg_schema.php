<?php
require 'includes/db.php';

try {
    $stmt = $pdo->query("PRAGMA table_info(negotiations)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Negotiations Table Schema:\n";
    foreach ($columns as $col) {
        echo $col['name'] . " (" . $col['type'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
