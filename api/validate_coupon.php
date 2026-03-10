<?php
require '../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$code = strtoupper(trim($data['code'] ?? ''));
$product_id = $data['product_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['valid' => false, 'message' => 'Please login to use coupons']);
    exit;
}

if (empty($code)) {
    echo json_encode(['valid' => false, 'message' => 'Please enter a coupon code']);
    exit;
}

$product_price = 0;
if ($product_id) {
    $pStmt = $pdo->prepare("SELECT price_min FROM products WHERE id = ?");
    $pStmt->execute([$product_id]);
    $product_price = (float)($pStmt->fetchColumn() ?: 0);

    if ($user_id) {
        $nStmt = $pdo->prepare("SELECT final_price FROM negotiations WHERE product_id = ? AND buyer_id = ? AND final_price IS NOT NULL");
        $nStmt->execute([$product_id, $user_id]);
        $neg = $nStmt->fetch();
        if ($neg) {
            $product_price = (float)$neg['final_price'];
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo json_encode(['valid' => false, 'message' => 'System error. Please try again.']);
    exit;
}

if (!$coupon) {
    echo json_encode(['valid' => false, 'message' => 'Invalid coupon code']);
    exit;
}

$now = date('Y-m-d H:i:s');
if (!empty($coupon['start_date']) && $now < $coupon['start_date']) {
    echo json_encode(['valid' => false, 'message' => 'This coupon is not yet active']);
    exit;
}
if (!empty($coupon['end_date']) && $now > $coupon['end_date']) {
    echo json_encode(['valid' => false, 'message' => 'This coupon has expired']);
    exit;
}

if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
    echo json_encode(['valid' => false, 'message' => 'This coupon has reached its usage limit']);
    exit;
}

$userUsage = $pdo->prepare("SELECT COUNT(*) FROM coupon_usage WHERE user_id = ? AND coupon_code = ?");
$userUsage->execute([$user_id, $code]);
$userUsedCount = (int)$userUsage->fetchColumn();

if ($coupon['per_user_limit'] > 0 && $userUsedCount >= $coupon['per_user_limit']) {
    echo json_encode(['valid' => false, 'message' => 'You have already used this coupon']);
    exit;
}

if ($coupon['min_order_amount'] > 0 && $product_price < $coupon['min_order_amount']) {
    echo json_encode([
        'valid' => false,
        'message' => 'Minimum order of ₹' . number_format($coupon['min_order_amount']) . ' required for this coupon'
    ]);
    exit;
}

$discount_amount = 0;
if ($coupon['type'] === 'percentage') {
    $discount_amount = round($product_price * ($coupon['value'] / 100), 2);
    if ($coupon['max_discount_amount'] > 0 && $discount_amount > $coupon['max_discount_amount']) {
        $discount_amount = $coupon['max_discount_amount'];
    }
} else {
    $discount_amount = $coupon['value'];
}

if ($product_price > 0 && $discount_amount > $product_price) {
    $discount_amount = $product_price;
}

$_SESSION['applied_coupon'] = [
    'code' => $code,
    'type' => $coupon['type'],
    'value' => $coupon['value'],
    'discount_amount' => $discount_amount,
    'coupon_id' => $coupon['id']
];

$message = 'Coupon applied! ';
if ($coupon['type'] === 'percentage') {
    $message .= $coupon['value'] . '% off';
    if ($coupon['max_discount_amount'] > 0) {
        $message .= ' (up to ₹' . number_format($coupon['max_discount_amount']) . ')';
    }
} else {
    $message .= '₹' . number_format($discount_amount) . ' off';
}

echo json_encode([
    'valid' => true,
    'message' => $message,
    'discount_amount' => $discount_amount,
    'discount_display' => '₹' . number_format($discount_amount)
]);
