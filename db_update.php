<?php
// Fix for missing column error
require 'includes/db.php';

try {
    // Add is_read column to negotiations table
    // default 1 means 'read' (accessed), 0 means 'unread'
    // We default to 1 so old messages don't show up as new.
    $pdo->exec("ALTER TABLE negotiations ADD COLUMN is_read INTEGER DEFAULT 1");
    echo "<h1>Success!</h1><p>Column 'is_read' has been added to the database.</p>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate column') !== false) {
        echo "<h1>Already Done</h1><p>The column 'is_read' already exists.</p>";
    } else {
        echo "<h1>Error</h1><p>" . $e->getMessage() . "</p>";
    }
}
?>
