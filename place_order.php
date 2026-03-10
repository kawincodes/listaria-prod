<?php
require 'includes/db.php';
require_once 'includes/email_templates.php';
require_once __DIR__ . '/includes/session.php';

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

    if ($product_id) {
        try {
            $pdo->beginTransaction();

            $prodStmt = $pdo->prepare("SELECT price_min FROM products WHERE id = ?");
            $prodStmt->execute([$product_id]);
            $prodRow = $prodStmt->fetch();
            if (!$prodRow) {
                $pdo->rollBack();
                header("Location: index.php");
                exit;
            }
            $server_price = (float)$prodRow['price_min'];

            $negStmt = $pdo->prepare("SELECT final_price FROM negotiations WHERE product_id = ? AND buyer_id = ? AND final_price IS NOT NULL");
            $negStmt->execute([$product_id, $user_id]);
            $negRow = $negStmt->fetch();
            if ($negRow) {
                $server_price = (float)$negRow['final_price'];
            }

            $shipping_cost = 85.00;
            if (isset($_SESSION['apply_free_shipping']) && $_SESSION['apply_free_shipping'] === true) {
                $shipping_cost = 0;
            }

            $coupon_discount = 0;
            if (isset($_SESSION['applied_coupon']) && !empty($_SESSION['applied_coupon']['code'])) {
                $cpn = $_SESSION['applied_coupon'];
                if ($cpn['type'] === 'percentage') {
                    $coupon_discount = round($server_price * ($cpn['value'] / 100), 2);
                    if ($cpn['max_discount_amount'] > 0 && $coupon_discount > $cpn['max_discount_amount']) {
                        $coupon_discount = $cpn['max_discount_amount'];
                    }
                } else {
                    $coupon_discount = (float)$cpn['value'];
                }
                if ($coupon_discount > $server_price) {
                    $coupon_discount = $server_price;
                }
            }

            $amount = max(0, $server_price + $shipping_cost - $coupon_discount);

            $reserveStmt = $pdo->prepare("UPDATE products SET quantity = quantity - 1 WHERE id = ? AND COALESCE(quantity,1) > 0 AND COALESCE(status,'available') != 'sold'");
            $reserveStmt->execute([$product_id]);
            if ($reserveStmt->rowCount() === 0) {
                $pdo->rollBack();
                header("Location: product_details.php?id=" . urlencode($product_id) . "&error=out_of_stock");
                exit;
            }

            $pdo->prepare("UPDATE products SET status = 'sold' WHERE id = ? AND COALESCE(quantity,0) <= 0")->execute([$product_id]);

            $initialStatus = ($pay_method === 'cod') ? 'Processing' : 'Pending';
            
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, product_id, amount, payment_method, order_status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $amount, $pay_method, $initialStatus]);
            $order_id = $pdo->lastInsertId();

            if (isset($_SESSION['applied_coupon']) && !empty($_SESSION['applied_coupon']['code'])) {
                $cpn = $_SESSION['applied_coupon'];
                $stmtCoupon = $pdo->prepare("INSERT INTO coupon_usage (user_id, coupon_code, order_id, discount_amount) VALUES (?, ?, ?, ?)");
                $stmtCoupon->execute([$user_id, $cpn['code'], $order_id, $coupon_discount]);
                $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ? AND (usage_limit = 0 OR used_count < usage_limit)")->execute([$cpn['code']]);
                unset($_SESSION['applied_coupon']);
            }
            if (isset($_SESSION['apply_free_shipping'])) {
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
