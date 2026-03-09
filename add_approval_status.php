<?php
require 'includes/db.php';

try {
    // Add approval_status column
    // SQLite doesn't support modifying column default easily if data exists, 
    // but adding a new column with default works fine.
    $pdo->exec("ALTER TABLE products ADD COLUMN approval_status TEXT DEFAULT 'pending'");
    echo "Added 'approval_status' column to products table.\n";
    
    // Update existing products to 'approved' so we don't hide everything currently on the site
    $pdo->exec("UPDATE products SET approval_status = 'approved'");
    echo "Updated existing products to 'approved'.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column name') !== false) {
        echo "Column 'approval_status' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
