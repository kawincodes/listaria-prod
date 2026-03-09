<?php
require 'includes/db.php';
session_start();

// Mock Response Handler
$transactionId = $_POST['transactionId'] ?? null;
$utr = $_POST['utr_number'] ?? null;

if (!$transactionId || !$utr) {
    die("Invalid Transaction or Missing UTR");
}

try {
    $pdo->beginTransaction();
    
    // Parse Order ID from MT string
    $parts = explode("_", substr($transactionId, 2));
    $order_id = $parts[0];

    // Update Order to 'Verification Pending'
    // Storing UTR in transaction_id column for now
    $stmt = $pdo->prepare("UPDATE orders SET order_status = 'Verification Pending', payment_method = 'PHONEPE', transaction_id = ? WHERE id = ?");
    $stmt->execute([$utr, $order_id]);
    
    // Do NOT mark product as sold yet. Wait for Admin Approval.
    
    $pdo->commit();
    
    // Redirect with Pending Flag
    header("Location: index.php?payment_pending=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error processing mock payment: " . $e->getMessage());
}
?>
