<?php
require 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE negotiations ADD COLUMN is_read INTEGER DEFAULT 0");
    echo "Column 'is_read' added successfully.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "Column 'is_read' already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
