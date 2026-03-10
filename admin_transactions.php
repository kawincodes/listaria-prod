<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';
require_once 'includes/email_templates.php';

$activePage = 'transactions';

// Check Admin Access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

// Handle Reject Payment
if (isset($_POST['reject_payment_id'])) {
    $oid = $_POST['reject_payment_id'];
    $stmt = $pdo->prepare("UPDATE orders SET order_status = 'Payment Failed' WHERE id = ?");
    $stmt->execute([$oid]);
    
    // Also revert product
    $p_stmt = $pdo->prepare("SELECT product_id FROM orders WHERE id = ?");
    $p_stmt->execute([$oid]);
    $prod = $p_stmt->fetch();
    if ($prod) {
        $pdo->prepare("UPDATE products SET quantity = COALESCE(quantity,0) + 1, status = 'available' WHERE id = ?")->execute([$prod['product_id']]);
    }

    $msg = "Payment rejected for Order #$oid. Status marked as 'Payment Failed'. Stock restored.";
}

// Handle Verify Payment (idempotent: only transition from Pending to Success)
if (isset($_POST['verify_payment_id'])) {
    $oid = $_POST['verify_payment_id'];
    $verifyStmt = $pdo->prepare("UPDATE orders SET order_status = 'Success' WHERE id = ? AND order_status = 'Pending'");
    $verifyStmt->execute([$oid]);
    
    if ($verifyStmt->rowCount() > 0) {
        $msg = "Payment verified for Order #$oid.";
    } else {
        $msg = "Order #$oid was already verified or is not in Pending state.";
    }
}

// Handle Order Status Update
if (isset($_POST['update_order_status'])) {
    $oid = $_POST['order_id'];
    $status = $_POST['order_status'];
    $delivery_date = $_POST['delivery_date'] ?? null;
    
    $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, delivery_date = ? WHERE id = ?");
    if($stmt->execute([$status, $delivery_date, $oid])) {
        $msg = "Order #$oid updated. Status: '$status', Delivery Date: '$delivery_date'.";
        
        // Revert product if Cancelled
        if ($status === 'Cancelled') {
            $p_stmt = $pdo->prepare("SELECT product_id FROM orders WHERE id = ?");
            $p_stmt->execute([$oid]);
            $prod = $p_stmt->fetch();
            if ($prod) {
                $pdo->prepare("UPDATE products SET quantity = COALESCE(quantity,0) + 1, status = 'available' WHERE id = ?")->execute([$prod['product_id']]);
                $msg .= " Stock restored. Product marked as AVAILABLE.";
            }
        }

        // Send email notification to buyer
        $oRow = $pdo->prepare("SELECT o.*, u.email, u.full_name, p.title as product_title FROM orders o JOIN users u ON o.user_id = u.id JOIN products p ON o.product_id = p.id WHERE o.id = ?");
        $oRow->execute([$oid]);
        $oData = $oRow->fetch();
        if ($oData) {
            $profileUrl = 'https://listaria.in/profile.php';
            $statusLower = strtolower($status);
            if ($statusLower === 'shipped' || $statusLower === 'out for delivery') {
                sendTemplateMail($pdo, 'shipping_update', $oData['email'], [
                    'customer_name'   => $oData['full_name'],
                    'order_id'        => $oid,
                    'product_title'   => $oData['product_title'],
                    'delivery_date'   => $delivery_date ? date('M j, Y', strtotime($delivery_date)) : 'To be confirmed',
                    'shipping_status' => $status,
                    'profile_url'     => $profileUrl,
                ], $oData['full_name']);
            } elseif ($statusLower === 'delivered') {
                sendTemplateMail($pdo, 'order_delivered', $oData['email'], [
                    'customer_name' => $oData['full_name'],
                    'order_id'      => $oid,
                    'product_title' => $oData['product_title'],
                    'profile_url'   => $profileUrl,
                ], $oData['full_name']);
            } else {
                sendTemplateMail($pdo, 'order_status_update', $oData['email'], [
                    'customer_name' => $oData['full_name'],
                    'order_id'      => $oid,
                    'product_title' => $oData['product_title'],
                    'new_status'    => $status,
                    'profile_url'   => $profileUrl,
                ], $oData['full_name']);
            }
        }
    } else {
        $msg = "Failed to update status.";
    }
}

// Send email after admin verifies PhonePe payment
if (isset($_POST['verify_payment_id'])) {
    $verifiedOid = $_POST['verify_payment_id'];
    $vRow = $pdo->prepare("SELECT o.*, u.email, u.full_name, p.title as product_title, sel.email as seller_email, sel.full_name as seller_name FROM orders o JOIN users u ON o.user_id = u.id JOIN products p ON o.product_id = p.id JOIN users sel ON p.user_id = sel.id WHERE o.id = ?");
    $vRow->execute([$verifiedOid]);
    $vData = $vRow->fetch();
    if ($vData) {
        $profileUrl = 'https://listaria.in/profile.php';
        sendTemplateMail($pdo, 'order_confirmation', $vData['email'], [
            'customer_name'  => $vData['full_name'],
            'order_id'       => $verifiedOid,
            'product_title'  => $vData['product_title'],
            'order_amount'   => '₹' . number_format((float)$vData['amount'], 2),
            'order_date'     => date('M j, Y'),
            'payment_method' => 'PhonePe / UPI',
            'profile_url'    => $profileUrl,
        ], $vData['full_name']);
        sendTemplateMail($pdo, 'new_sale_notification', $vData['seller_email'], [
            'seller_name'   => $vData['seller_name'],
            'order_id'      => $verifiedOid,
            'product_title' => $vData['product_title'],
            'order_amount'  => '₹' . number_format((float)$vData['amount'], 2),
            'buyer_name'    => $vData['full_name'],
            'order_date'    => date('M j, Y'),
            'profile_url'   => $profileUrl,
        ], $vData['seller_name']);
    }
}

// Fetch All Orders
$ordersStmt = $pdo->query("
    SELECT o.*, u.full_name as buyer_name, u.address as buyer_address, u.phone as buyer_phone, p.title as product_title, p.price_min, p.image_paths 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN products p ON o.product_id = p.id 
    ORDER BY o.created_at DESC
");
$orders = $ordersStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { 
            --primary: #6B21A8; 
            --primary-dark: #581c87;
            --accent: #6B21A8; 
            --success: #22c55e;
            --bg: #f8f9fa; 
            --sidebar-bg: #1a1a1a;
            --text-light: #a1a1aa;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #333; }
        
        .main-content { 
            margin-left: 260px; 
            padding: 2.5rem 3rem; 
            width: calc(100% - 260px); 
            min-height: 100vh;
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 2.5rem; 
        }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        
        .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.2rem; color: #334155; display: flex; align-items: center; gap: 8px;}
        .table-container { 
            background: white; 
            border-radius: 16px; 
            overflow: hidden; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); 
            margin-bottom: 3rem; 
            border: 1px solid #f1f5f9;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1.2rem 1.5rem; text-align: left; font-size: 0.9rem; }
        th { 
            background: #f8fafc; 
            color: #64748b; 
            font-weight: 600; 
            text-transform: uppercase; 
            font-size: 0.75rem; 
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }
        td { color: #334155; border-bottom: 1px solid #f1f5f9; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }
        
        /* Include Sidebar styles via external CSS if extracted, or inline for now as in Dashboard */
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }
        .btn-verify {
            border: none; background: #6B21A8; color: white; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600;
            box-shadow: 0 2px 5px rgba(107, 33, 168, 0.3);
        }
        .btn-verify:hover { background: #581c87; }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <h1>All Transactions</h1>
            <div>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
        </div>

        <?php if(isset($msg)) echo "<div style='margin-bottom:20px; color:#22c55e; font-weight:600; padding:1rem; background:#f0fdf4; border-radius:10px;'>$msg</div>"; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Buyer</th>
                        <th>Contact Details</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Order Status</th>
                        <th>Payment</th>
                        <th>Txn ID</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($orders) > 0): ?>
                        <?php foreach($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($order['buyer_name']); ?></div>
                            </td>
                            <td>
                                <div style="font-size:0.85rem; color:#333; max-width:180px; margin-bottom:4px;">
                                    <ion-icon name="location-outline" style="vertical-align:text-bottom; color:#888;"></ion-icon> 
                                    <?php echo htmlspecialchars($order['buyer_address'] ?? 'N/A'); ?>
                                </div>
                                <div style="font-size:0.85rem; color:#666;">
                                    <ion-icon name="call-outline" style="vertical-align:text-bottom; color:#888;"></ion-icon> 
                                    <?php echo htmlspecialchars($order['buyer_phone'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600;"><?php echo htmlspecialchars($order['product_title']); ?></div>
                            </td>
                            <td>₹<?php echo number_format($order['amount']); ?></td>
                            <td>
                                <?php if($order['order_status'] === 'Verification Pending'): ?>
                                    <div style="margin-bottom:5px; color:#d35400; font-weight:bold; font-size:0.8rem;">Action Required</div>
                                    <form method="POST" onsubmit="return confirm('Verify this payment? This will mark the order as Success.');" style="display:inline-block; margin-right:5px;">
                                        <input type="hidden" name="verify_payment_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" class="btn-verify">Verify</button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Reject this payment? Status will be marked as Failed.');" style="display:inline-block;">
                                        <input type="hidden" name="reject_payment_id" value="<?php echo $order['id']; ?>">
                                        <button type="submit" class="btn-verify" style="background:#e74c3c; box-shadow:0 2px 5px rgba(231,76,60,0.3);">Reject</button>
                                    </form>
                                <?php elseif($order['order_status'] === 'Payment Failed'): ?>
                                    <div style="color:#cf1322; font-weight:bold; font-size:0.9rem; padding: 4px 8px; background: #fff1f0; border-radius: 4px; border: 1px solid #ffa39e; display: inline-block;">
                                        <ion-icon name="close-circle" style="vertical-align: text-bottom;"></ion-icon> Rejected
                                    </div>
                                <?php else: ?>
                                    <form method="POST" style="display:flex; flex-direction:column; gap:5px;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="order_status" style="padding:4px; border-radius:4px; border:1px solid #ccc; font-size:0.8rem;">
                                            <?php 
                                                $steps = ['Processing', 'Item Collected', 'Reached Listaria', 'Verified', 'Out for Delivery', 'Delivered', 'Cancelled'];
                                                $current = $order['order_status'];
                                                if($current === 'Success') $current = 'Processing'; // Map Success to Processing
                                                
                                                foreach($steps as $s) {
                                                    $sel = ($s === $current) ? 'selected' : '';
                                                    $color = ($s === 'Cancelled') ? 'color:red;' : '';
                                                    echo "<option value='$s' $sel style='$color'>$s</option>";
                                                }
                                            ?>
                                        </select>
                                        <div style="display:flex; gap:5px;">
                                            <?php 
                                                $defaultDate = date('Y-m-d');
                                                $valDate = !empty($order['delivery_date']) ? $order['delivery_date'] : $defaultDate;
                                            ?>
                                            <input type="date" name="delivery_date" value="<?php echo htmlspecialchars($valDate); ?>" style="padding:4px; border-radius:4px; border:1px solid #ccc; font-size:0.8rem; width:100%;">
                                            <button type="submit" name="update_order_status" style="border:none; background:#6B21A8; color:white; border-radius:4px; padding:4px 8px; cursor:pointer; font-size:0.8rem;">Save</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td style="text-transform:uppercase;">
                                <?php echo htmlspecialchars($order['payment_method']); ?>
                                <?php if(isset($order['payment_status'])): ?>
                                    <div style="font-size:0.75rem; color:#666;"><?php echo htmlspecialchars($order['payment_status']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size:0.8rem; font-family:monospace; color:#555;">
                                    <?php echo htmlspecialchars($order['transaction_id'] ?? '-'); ?>
                                </div>
                            </td>
                            <td><?php echo date('M j, Y h:i A', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center;">No orders yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
