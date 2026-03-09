<?php
// listaria.in/update_db_schema.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'includes/db.php';

echo "<h1>Database Schema Updater</h1>";

try {
    // 1. Create Negotiations Table
    $sql1 = "CREATE TABLE IF NOT EXISTS negotiations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        buyer_id INTEGER NOT NULL,
        seller_id INTEGER NOT NULL,
        final_price REAL DEFAULT NULL,
        status TEXT DEFAULT 'pending', 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql1);
    echo "<span style='color:green'>✔ Checked/Created 'negotiations' table.</span><br>";

    // 2. Create Messages Table
    $sql2 = "CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        negotiation_id INTEGER NOT NULL,
        sender_id INTEGER NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (negotiation_id) REFERENCES negotiations(id)
    )";
    $pdo->exec($sql2);
    echo "<span style='color:green'>✔ Checked/Created 'messages' table.</span><br>";

    echo "<h3>Success! Tables are ready.</h3>";
    echo "You can now go back and chat.";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Error: " . $e->getMessage() . "</h3>";
}
?>
