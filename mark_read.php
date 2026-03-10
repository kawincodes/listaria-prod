<?php
require_once __DIR__ . '/includes/session.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

require 'includes/db.php';

$negotiation_id = $_POST['id'] ?? 0;
$user_id = $_SESSION['user_id'];

if ($negotiation_id) {
    // Only mark as read if I am the seller for this negotiation
    $stmt = $pdo->prepare("UPDATE negotiations SET is_read = 1 WHERE id = ? AND seller_id = ?");
    $success = $stmt->execute([$negotiation_id, $user_id]);
    
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'error' => 'No ID']);
}
?>
