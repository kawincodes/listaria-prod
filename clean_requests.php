<?php
require 'includes/db.php';

echo "Cleaning up duplicate requests...\n";

// Get all requests ordered by creation
$stmt = $pdo->query("SELECT id, title, description, user_id FROM product_requests ORDER BY created_at ASC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$seen = [];
$duplicates = [];

foreach ($requests as $req) {
    // Create a unique hash for the request
    $hash = md5($req['title'] . $req['description'] . $req['user_id']);
    
    if (isset($seen[$hash])) {
        // This is a duplicate
        $duplicates[] = $req['id'];
    } else {
        // First time seeing this
        $seen[$hash] = true;
    }
}

if (!empty($duplicates)) {
    echo "Found " . count($duplicates) . " duplicate requests. Deleting...\n";
    
    $placeholders = str_repeat('?,', count($duplicates) - 1) . '?';
    $deleteStmt = $pdo->prepare("DELETE FROM product_requests WHERE id IN ($placeholders)");
    $deleteStmt->execute($duplicates);
    
    echo "Deletion complete.\n";
} else {
    echo "No duplicate requests found.\n";
}
?>
