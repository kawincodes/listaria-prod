<?php
require '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$product_id = $_POST['product_id'] ?? 0;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid Product ID']);
    exit;
}

try {
    if ($action === 'toggle') {
        // Check if exists
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Remove
            $del = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $del->execute([$user_id, $product_id]);
            echo json_encode(['success' => true, 'status' => 'removed']);
        } else {
            // Add
            $add = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $add->execute([$user_id, $product_id]);
            echo json_encode(['success' => true, 'status' => 'added']);
        }

    } elseif ($action === 'check') {
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $exists = $stmt->fetch();
        
        echo json_encode(['success' => true, 'in_wishlist' => (bool)$exists]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid Action']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
