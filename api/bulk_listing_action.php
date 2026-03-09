<?php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['account_type'] ?? 'customer') !== 'vendor') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;
$ids = $input['ids'] ?? [];

if (!$action || empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

$user_id = $_SESSION['user_id'];
$placeholders = implode(',', array_fill(0, count($ids), '?'));

try {
    if ($action === 'sold') {
        $stmt = $pdo->prepare("UPDATE products SET status = 'sold' WHERE id IN ($placeholders) AND user_id = ?");
        $stmt->execute(array_merge($ids, [$user_id]));
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders) AND user_id = ?");
        $stmt->execute(array_merge($ids, [$user_id]));
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
