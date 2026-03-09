<?php
require 'includes/db.php';

// In SQLite, we can't easily change DEFAULT value via ALTER TABLE.
// Since we only recently added this column, we can recreate the table if needed, 
// but for now, making sure any new manual insertion or existing logic is handled is easier.
// However, the best practice is to have the column default to 1.

try {
    // Attempt to update existing records
    $pdo->exec("UPDATE users SET is_public = 1 WHERE is_public IS NULL OR is_public = 0");
    echo "Existing users set to public.\n";
    
    // To 'change' the default in SQLite, we'd usually need to rename table, create new, copy data.
    // Instead, I'll check if I can just re-run the column addition with default 1 if I drop it first?
    // No, dropping columns is only in very recent SQLite.
    
    // Better: Update the code that creates the table if it exists, or just ensure default 1 in the migration script.
    // Since I already ran ADD COLUMN with DEFAULT 0, I'll just keep it and ensure my fix script for future runs uses 1.
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
