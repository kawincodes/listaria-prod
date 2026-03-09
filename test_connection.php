<?php
require 'includes/db.php';
try {
    echo "New Test: Database connection successful!\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'"); // Mysql syntax but let's see if it errors on sqlite differently or works
    // Sqlite doesn't support SHOW TABLES usually, it uses .tables or selects from sqlite_master
    // But let's just check connection first.
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?>
