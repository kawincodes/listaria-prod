<?php
require 'includes/db.php';

try {
    echo "Database connection successful!\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'products' exists.\n";
    } else {
        echo "ERROR: Table 'products' does not exist. Please run setup.sql.\n";
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>
