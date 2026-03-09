<?php
require 'includes/db.php';

echo "Cleaning up duplicate requests based on Title and User...\n<br>";

// Find duplicates (same title, same user)
$stmt = $pdo->query("
    SELECT user_id, title, COUNT(*) as count 
    FROM product_requests 
    GROUP BY user_id, title 
    HAVING count > 1
");

$duplicates_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($duplicates_groups)) {
    echo "No duplicates found based on title and user.\n";
    exit;
}

$total_deleted = 0;

foreach ($duplicates_groups as $group) {
    // Keep only the most recent one (highest ID assuming auto-increment)
    $stmt = $pdo->prepare("
        SELECT id FROM product_requests 
        WHERE user_id = ? AND title = ? 
        ORDER BY id DESC
    ");
    $stmt->execute([$group['user_id'], $group['title']]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Remove the first one from the list of IDs to delete (keep the newest)
    $keep_id = array_shift($ids);
    
    if (!empty($ids)) {
        // Delete the rest
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $deleteStmt = $pdo->prepare("DELETE FROM product_requests WHERE id IN ($placeholders)");
        $deleteStmt->execute($ids);
        $total_deleted += count($ids);
        echo "Deleted " . count($ids) . " older copies of '" . htmlspecialchars($group['title']) . "'.<br>";
    }
}

echo "Cleanup complete. Total deleted: $total_deleted\n";
?>
