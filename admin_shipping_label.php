<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';

$activePage = 'transactions';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    echo "<h2>Invalid Order ID</h2>";
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*,
           u.full_name AS buyer_name, u.address AS buyer_address, u.phone AS buyer_phone, u.email AS buyer_email,
           p.title AS product_title, p.price_min, p.image_paths,
           sel.full_name AS seller_name, sel.address AS seller_address, sel.phone AS seller_phone, sel.email AS seller_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN products p ON o.product_id = p.id
    JOIN users sel ON p.user_id = sel.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    echo "<h2>Order not found</h2>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shipping Label - Order #<?php echo $order['id']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root {
            --primary: #6B21A8;
            --primary-dark: #581c87;
            --bg: #f8f9fa;
            --sidebar-bg: #1a1a1a;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); display: flex; color: #333; }

        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }

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
            margin-bottom: 2rem;
        }
        .header h1 { font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }

        .toolbar {
            display: flex;
            gap: 0.8rem;
            align-items: center;
        }
        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-outline { background: white; color: #333; border: 1px solid #ddd; }
        .btn-outline:hover { background: #f1f5f9; }

        .label-container {
            background: white;
            border: 2px solid #1a1a1a;
            border-radius: 4px;
            max-width: 800px;
            margin: 0 auto;
            overflow: hidden;
        }

        .label-header {
            background: #1a1a1a;
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .label-header .logo-text {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .label-header .order-info {
            text-align: right;
            font-size: 0.85rem;
        }
        .label-header .order-info .order-num {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .label-body {
            padding: 1.5rem;
        }

        .address-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .address-box {
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.2rem;
        }
        .address-box.to-box {
            border-color: #1a1a1a;
            border-width: 2px;
        }
        .address-box .box-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            margin-bottom: 0.8rem;
        }
        .address-box.to-box .box-label {
            color: #1a1a1a;
        }
        .address-box .name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.4rem;
        }
        .address-box .detail {
            font-size: 0.88rem;
            color: #555;
            margin-bottom: 0.3rem;
            display: flex;
            align-items: flex-start;
            gap: 6px;
        }
        .address-box .detail ion-icon {
            font-size: 0.95rem;
            color: #94a3b8;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .product-section {
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .product-section .section-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            margin-bottom: 0.8rem;
        }
        .product-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .product-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a1a;
        }
        .product-price {
            font-size: 1.2rem;
            font-weight: 800;
            color: #1a1a1a;
        }

        .meta-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            border-top: 1.5px solid #e2e8f0;
            padding-top: 1.2rem;
        }
        .meta-item .meta-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 0.3rem;
        }
        .meta-item .meta-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1a1a1a;
        }

        .label-footer {
            background: #f8fafc;
            border-top: 1.5px dashed #ccc;
            padding: 0.8rem 1.5rem;
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
        }

        @media print {
            body {
                background: white;
                display: block;
            }
            .sidebar, .header, .toolbar, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                padding: 0;
                width: 100%;
            }
            .label-container {
                max-width: 100%;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header no-print">
            <h1>Shipping Label</h1>
            <div class="toolbar">
                <a href="admin_transactions.php" class="btn btn-outline">
                    <ion-icon name="arrow-back-outline"></ion-icon> Back to Transactions
                </a>
                <button class="btn btn-primary" onclick="window.print()">
                    <ion-icon name="print-outline"></ion-icon> Print Label
                </button>
            </div>
        </div>

        <div class="label-container">
            <div class="label-header">
                <div class="logo-text">LISTARIA</div>
                <div class="order-info">
                    <div class="order-num">Order #<?php echo $order['id']; ?></div>
                    <div><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                </div>
            </div>

            <div class="label-body">
                <div class="address-row">
                    <div class="address-box">
                        <div class="box-label">From (Seller)</div>
                        <div class="name"><?php echo htmlspecialchars($order['seller_name']); ?></div>
                        <div class="detail">
                            <ion-icon name="location-outline"></ion-icon>
                            <span><?php echo htmlspecialchars($order['seller_address'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail">
                            <ion-icon name="call-outline"></ion-icon>
                            <span><?php echo htmlspecialchars($order['seller_phone'] ?? 'N/A'); ?></span>
                        </div>
                    </div>

                    <div class="address-box to-box">
                        <div class="box-label">To (Buyer)</div>
                        <div class="name"><?php echo htmlspecialchars($order['buyer_name']); ?></div>
                        <div class="detail">
                            <ion-icon name="location-outline"></ion-icon>
                            <span><?php echo htmlspecialchars($order['buyer_address'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail">
                            <ion-icon name="call-outline"></ion-icon>
                            <span><?php echo htmlspecialchars($order['buyer_phone'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="product-section">
                    <div class="section-label">Product Details</div>
                    <div class="product-row">
                        <div class="product-title"><?php echo htmlspecialchars($order['product_title']); ?></div>
                        <div class="product-price">₹<?php echo number_format($order['amount']); ?></div>
                    </div>
                </div>

                <div class="meta-row">
                    <div class="meta-item">
                        <div class="meta-label">Order Date</div>
                        <div class="meta-value"><?php echo date('M j, Y h:i A', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Payment Method</div>
                        <div class="meta-value"><?php echo htmlspecialchars(strtoupper($order['payment_method'])); ?></div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Order Status</div>
                        <div class="meta-value"><?php echo htmlspecialchars($order['order_status']); ?></div>
                    </div>
                </div>
            </div>

            <div class="label-footer">
                Listaria Marketplace &mdash; Handle with care &mdash; Order #<?php echo $order['id']; ?>
            </div>
        </div>
    </main>
</body>
</html>
