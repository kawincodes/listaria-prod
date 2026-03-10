<?php
require '../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$code = $data['code'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['valid' => false, 'message' => 'Please login to use coupons']);
    exit;
}

if (strtolower(trim($code)) === 'listarianew') {
    // Check if used
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM coupon_usage WHERE user_id = ? AND coupon_code = ?");
    $stmt->execute([$user_id, 'listarianew']);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo json_encode(['valid' => false, 'message' => 'Coupon already used']);
    } else {
        // Mark session
        $_SESSION['apply_free_shipping'] = true;
        echo json_encode(['valid' => true, 'message' => 'Coupon Applied! Free Shipping.']);
    }
} else {
    echo json_encode(['valid' => false, 'message' => 'Invalid Coupon Code']);
}
?>
