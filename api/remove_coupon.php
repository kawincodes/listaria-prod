<?php
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

unset($_SESSION['applied_coupon']);
unset($_SESSION['apply_free_shipping']);

echo json_encode(['success' => true, 'message' => 'Coupon removed']);
