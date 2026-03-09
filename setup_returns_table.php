<?php
// Direct connection to avoid include issues for this script
$db_file = __DIR__ . '/includes/database.sqlite'; // Adjusted path if needed, or just use 'database.sqlite' if in root
// Check if includes/db.php uses 'includes/database.sqlite' or just 'database.sqlite'. 
// Based on previous files, it seems to be in __DIR__ . '/database.sqlite' relative to db.php.
// Let's assume root for now or check file existence.
$db_file = __DIR__ . '/includes/database.sqlite';
if (!file_exists($db_file)) {
    $db_file = __DIR__ . '/database.sqlite';
}

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create returns table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS returns (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        reason TEXT NOT NULL,
        details TEXT,
        status TEXT DEFAULT 'pending', -- pending, approved, rejected, pickup_scheduled, collected, refunded
        pickup_date DATETIME, -- New Column
        admin_comments TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )";
    
    $pdo->exec($sql);
    echo "Table 'returns' verified/created.\n";
    
    // Check if pickup_date column exists in returns table
    $columns = $pdo->query("PRAGMA table_info(returns)")->fetchAll(PDO::FETCH_ASSOC);
    $has_pickup_date = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'pickup_date') {
            $has_pickup_date = true;
            break;
        }
    }
    
    if (!$has_pickup_date) {
        $pdo->exec("ALTER TABLE returns ADD COLUMN pickup_date DATETIME");
        echo "Column 'pickup_date' added to 'returns' table.\n";
    } else {
        echo "Column 'pickup_date' already exists in 'returns' table.\n";
    }

} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
?>
