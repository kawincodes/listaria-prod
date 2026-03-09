<?php
require 'includes/db.php';

try {
    echo "Using database: " . realpath($db_file) . "\n";
    
    // 1. Create returns table
    $sql = "CREATE TABLE IF NOT EXISTS returns (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        reason TEXT NOT NULL,
        details TEXT,
        status TEXT DEFAULT 'pending',
        pickup_date DATETIME,
        expected_return_date TEXT,
        evidence_photos TEXT,
        evidence_video TEXT,
        admin_comments TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )";
    $pdo->exec($sql);
    echo "Table 'returns' initialized successfully.\n";

    // 2. Verify columns
    $stmt = $pdo->query("PRAGMA table_info(returns)");
    $columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
    
    $required = ['pickup_date', 'expected_return_date', 'evidence_photos', 'evidence_video'];
    foreach ($required as $col) {
        if (!in_array($col, $columns)) {
            $pdo->exec("ALTER TABLE returns ADD COLUMN $col TEXT");
            echo "Added missing column: $col\n";
        } else {
            echo "Column $col already exists.\n";
        }
    }

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
?>
