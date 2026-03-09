<?php
require 'includes/db.php';

echo "<h1>Search Index Optimizer</h1>";

try {
    // Add indexes for columns used in search
    $queries = [
        "CREATE INDEX IF NOT EXISTS idx_products_title ON products(title)",
        "CREATE INDEX IF NOT EXISTS idx_products_brand ON products(brand)",
        "CREATE INDEX IF NOT EXISTS idx_products_category ON products(category)",
        "CREATE INDEX IF NOT EXISTS idx_products_status ON products(approval_status, is_published)"
    ];

    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p style='color:green'>✔ Executed: $sql</p>";
        } catch (PDOException $e) {
            echo "<p style='color:orange'>⚠ Info: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h3>Optimization Complete!</h3>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>Fatal Error: " . $e->getMessage() . "</h3>";
}
