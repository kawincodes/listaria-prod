<?php
require 'includes/db.php';
require_once 'includes/email_templates.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login if not
        header("Location: login.php?redirect=payment_method.php?id=" . $_POST['product_id']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'] ?? null;
    $pay_method = $_POST['pay_method'] ?? 'phonepe';
    $amount = $_POST['amount'] ?? 0;

    if ($product_id) {
        try {
            // Start Transaction
            $pdo->beginTransaction();

            // 1. Insert Order
            // Default status: 'Pending' for Online, 'Success' for COD (assuming COD is auto-confirmed here, or 'Item Collected' as per admin view?)
            // Admin view shows 'Item Collected'. Let's stick to 'Pending' for PhonePe.
            $initialStatus = ($pay_method === 'cod') ? 'Processing' : 'Pending';
            
            // Check if columns exist (handled by schema update, but if logic fails, we assume they exist now)
            // SQL: INSERT INTO orders (user_id, product_id, amount, payment_method, order_status)
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, product_id, amount, payment_method, order_status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $amount, $pay_method, $initialStatus]);
            $order_id = $pdo->lastInsertId();

            // 2. Update Product Status to 'sold' ONLY IF COD
            // For PhonePe, we wait for callback.
            if ($pay_method === 'cod') {
                $stmtUpdate = $pdo->prepare("UPDATE products SET status = 'sold' WHERE id = ?");
                $stmtUpdate->execute([$product_id]);
            }

            // 3. Record Coupon Usage if applied
            if (isset($_SESSION['apply_free_shipping']) && $_SESSION['apply_free_shipping'] === true) {
                $stmtCoupon = $pdo->prepare("INSERT INTO coupon_usage (user_id, coupon_code) VALUES (?, 'listarianew')");
                $stmtCoupon->execute([$user_id]);
                unset($_SESSION['apply_free_shipping']); 
            }

            // Commit
            $pdo->commit();

            // Send order emails for COD (PhonePe emails sent after payment verified by admin)
            if ($pay_method === 'cod') {
                try {
                    $buyerRow = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
                    $buyerRow->execute([$user_id]);
                    $buyer = $buyerRow->fetch();

                    $prodRow = $pdo->prepare("SELECT p.title, p.price_max, u.email AS seller_email, u.full_name AS seller_name FROM products p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
                    $prodRow->execute([$product_id]);
                    $prod = $prodRow->fetch();

                    $profileUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/profile.php';

                    if ($buyer && $prod) {
                        sendTemplateMail($pdo, 'order_confirmation', $buyer['email'], [
                            'customer_name'  => $buyer['full_name'],
                            'order_id'       => $order_id,
                            'product_title'  => $prod['title'],
                            'order_amount'   => '₹' . number_format((float)$amount, 2),
                            'order_date'     => date('M j, Y'),
                            'payment_method' => 'Cash on Delivery',
                            'profile_url'    => $profileUrl,
                        ], $buyer['full_name']);

                        sendTemplateMail($pdo, 'new_sale_notification', $prod['seller_email'], [
                            'seller_name'   => $prod['seller_name'],
                            'order_id'      => $order_id,
                            'product_title' => $prod['title'],
                            'order_amount'  => '₹' . number_format((float)$amount, 2),
                            'buyer_name'    => $buyer['full_name'],
                            'order_date'    => date('M j, Y'),
                            'profile_url'   => $profileUrl,
                        ], $prod['seller_name']);
                    }
                } catch (Exception $ex) {
                    error_log("Order email error (order #$order_id): " . $ex->getMessage());
                }
            }

            // Redirect based on Method
            if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'order_id' => $order_id]);
                exit;
            }

            if ($pay_method === 'phonepe') {
                header("Location: phonepe_request.php?order_id=" . $order_id);
                exit;
            } else {
                // COD
                header("Location: index.php?order_success=1");
                exit;
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            echo "Error placing order: " . $e->getMessage();
            exit;
        }
    } else {
        echo "Invalid Product";
    }
} else {
    // Direct access not allowed
    header("Location: index.php");
    exit;
}
?>
