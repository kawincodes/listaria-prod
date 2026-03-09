<?php
require 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        name TEXT,
        email TEXT,
        message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT 'open'
    )");
    echo "Table support_tickets created successfully via Web V2.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
