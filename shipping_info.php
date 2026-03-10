<?php
require 'includes/db.php';
require_once __DIR__ . '/includes/session.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=shipping_info.php?id=" . ($_GET['id'] ?? ''));
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $phoneRaw = preg_replace('/[^0-9]/', '', $phone);
    $address = $_POST['address'] ?? '';

    if ($phoneRaw && strlen($phoneRaw) === 10 && $address) {
        $stmt = $pdo->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?");
        if ($stmt->execute([$phoneRaw, $address, $user_id])) {
            header("Location: payment_method.php?id=" . urlencode($product_id) . (isset($_GET['source']) ? "&source=" . urlencode($_GET['source']) : ""));
            exit;
        } else {
            $error = "Failed to save details.";
        }
    } else {
        $error = "Please enter a valid 10-digit phone number and address.";
    }
}

$stmt = $pdo->prepare("SELECT full_name, email, phone, address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$product = null;
$price = 0;
$image_url = 'https://via.placeholder.com/150';
$title = 'Product Name';
$brand = 'Brand';

if ($product_id) {
    $stmtP = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmtP->execute([$product_id]);
    $product_data = $stmtP->fetch();

    if ($product_data) {
        $title = $product_data['title'];
        $brand = $product_data['brand'];
        $price = $product_data['price_min'];

        $images = json_decode($product_data['image_paths'], true);
        if (!empty($images)) {
            $image_url = $images[0];
        }

        $stmtNeg = $pdo->prepare("SELECT final_price FROM negotiations WHERE product_id = ? AND buyer_id = ? AND final_price IS NOT NULL");
        $stmtNeg->execute([$product_id, $user_id]);
        $offer = $stmtNeg->fetch();
        if ($offer) {
            $price = $offer['final_price'];
        }
    } else {
        $price = 13000.00;
    }
} else {
    $price = 13000.00;
}

$original_price = $price * 1.5;
$shipping_cost = 85.00;

if (isset($_SESSION['apply_free_shipping']) && $_SESSION['apply_free_shipping'] === true) {
    $shipping_cost = 0;
}

$coupon_discount = 0;
$applied_coupon_code = '';
if (isset($_SESSION['applied_coupon']) && !empty($_SESSION['applied_coupon']['discount_amount'])) {
    $coupon_discount = (float)$_SESSION['applied_coupon']['discount_amount'];
    $applied_coupon_code = $_SESSION['applied_coupon']['code'];
}

$total = $price + $shipping_cost - $coupon_discount;
if ($total < 0) $total = 0;
$discount = $original_price - $price;
$discount_pct = round(($discount / $original_price) * 100);

include 'includes/header.php';
?>

<?php if (isset($_GET['source']) && $_GET['source'] === 'thrift'): ?>
<style>
    body, .ship-container { background-color: #eae4cc !important; font-family: 'Courier New', monospace !important; }
    .ship-container .ship-title, .ship-container .ship-item-name, .ship-container label, .ship-container .ship-summary-label, .ship-container h3 { font-family: 'Courier New', monospace !important; color: #1a1a1a !important; }
    .ship-container .ship-title { font-family: 'Times New Roman', serif !important; font-weight: 800 !important; text-transform: uppercase; }
    .ship-summary-card, .ship-field input, .ship-field textarea, .ship-btn-pay, .ship-info-banner, .ship-deal-pill, .ship-hv-box { border: 2px solid #1a1a1a !important; box-shadow: 4px 4px 0 rgba(26,26,26,0.9) !important; border-radius: 0 !important; background: #fdfcf8 !important; }
    .ship-field input, .ship-field textarea { box-shadow: none !important; border: 2px solid #1a1a1a !important; }
    .ship-btn-pay { background: #1a1a1a !important; color: #fff !important; text-transform: uppercase; font-weight: 800 !important; }
    .ship-btn-pay:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0 rgba(26,26,26,0.9) !important; }
    .ship-item-thumb { border-radius: 0 !important; border: 2px solid #1a1a1a !important; }
    .ship-guarantee { border-radius: 0 !important; border: 2px solid #1a1a1a !important; background: #dcfce7 !important; }
</style>
<?php endif; ?>

<style>
    .ship-container { max-width: 1100px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }

    .ship-progress {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        margin-bottom: 2.5rem;
        padding: 0 2rem;
    }
    .ship-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        position: relative;
    }
    .ship-step span {
        font-size: 0.75rem;
        font-weight: 600;
        color: #ccc;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .ship-step.active span { color: #6B21A8; }
    .ship-step.current span { color: #6B21A8; font-weight: 700; }
    .ship-step-circle {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        color: #bbb;
        transition: all 0.3s;
    }
    .ship-step.active .ship-step-circle {
        background: linear-gradient(135deg, #6B21A8, #9333EA);
        color: white;
        box-shadow: 0 4px 15px rgba(107,33,168,0.3);
    }
    .ship-step.current .ship-step-circle {
        box-shadow: 0 0 0 4px rgba(107,33,168,0.15), 0 4px 15px rgba(107,33,168,0.3);
    }
    .ship-step-line {
        width: 60px;
        height: 3px;
        background: #e5e7eb;
        border-radius: 2px;
        margin: 0 0.5rem;
        margin-bottom: 20px;
    }
    .ship-step-line.active { background: linear-gradient(90deg, #6B21A8, #9333EA); }

    .ship-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 2rem; align-items: start; }

    .ship-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 24px rgba(0,0,0,0.04);
        border: 1px solid #f0f0f0;
    }
    .ship-card-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #f5f5f5;
    }
    .ship-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: linear-gradient(135deg, #f3e8ff, #ede9fe);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: #6B21A8;
        flex-shrink: 0;
    }
    .ship-title { font-size: 1.2rem; font-weight: 700; color: #1a1a1a; margin: 0; }
    .ship-subtitle { font-size: 0.85rem; color: #999; margin: 2px 0 0; }

    .ship-error {
        background: #fef2f2;
        color: #dc2626;
        padding: 12px 16px;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        font-size: 0.88rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #fecaca;
    }

    .ship-fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }

    .ship-field { margin-bottom: 1rem; }
    .ship-field label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #555;
        margin-bottom: 0.5rem;
    }
    .ship-field label ion-icon { font-size: 1rem; color: #6B21A8; }
    .ship-field input, .ship-field textarea {
        width: 100%;
        padding: 14px 16px;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.92rem;
        font-family: inherit;
        transition: all 0.2s;
        background: #fff;
        color: #333;
    }
    .ship-field input:focus, .ship-field textarea:focus {
        outline: none;
        border-color: #6B21A8;
        box-shadow: 0 0 0 4px rgba(107,33,168,0.08);
    }
    .ship-field textarea { resize: vertical; min-height: 90px; }
    .ship-readonly { background: #fafafa !important; color: #888 !important; cursor: not-allowed; }

    .ship-phone-wrap {
        display: flex;
        align-items: center;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        transition: all 0.2s;
    }
    .ship-phone-wrap:focus-within {
        border-color: #6B21A8;
        box-shadow: 0 0 0 4px rgba(107,33,168,0.08);
    }
    .ship-phone-prefix {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 0 16px;
        background: #fafafa;
        color: #555;
        font-weight: 600;
        font-size: 0.9rem;
        border-right: 1.5px solid #e5e7eb;
        height: 50px;
        white-space: nowrap;
    }
    .ship-phone-wrap input {
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        height: 50px;
        padding: 0 16px;
    }

    .ship-addr-search-wrap {
        position: relative;
        margin-bottom: 0.75rem;
    }
    .ship-addr-search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
        font-size: 1.1rem;
    }
    .ship-addr-search-wrap input {
        padding-left: 40px !important;
    }
    .ship-suggestions {
        display: none;
        position: absolute;
        z-index: 1000;
        width: 100%;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        max-height: 240px;
        overflow-y: auto;
        margin-top: -4px;
    }
    .ship-sug-item {
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f9f9f9;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.88rem;
        color: #333;
        transition: background 0.15s;
    }
    .ship-sug-item:last-child { border-bottom: none; }
    .ship-sug-item:hover { background: #f3e8ff; }
    .ship-sug-icon { color: #6B21A8; font-size: 1.1rem; flex-shrink: 0; }
    .ship-sug-text { display: flex; flex-direction: column; min-width: 0; }
    .ship-sug-main { font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ship-sug-sub { font-size: 0.75rem; color: #999; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .ship-save-check {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #f5f5f5;
    }
    .ship-save-check input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #6B21A8;
        cursor: pointer;
    }
    .ship-save-check label { font-size: 0.88rem; color: #666; cursor: pointer; }

    .ship-security-strip {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin-top: 1.5rem;
    }
    .ship-sec-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.78rem;
        color: #999;
        font-weight: 500;
    }
    .ship-sec-item ion-icon { font-size: 1rem; color: #22c55e; }

    .ship-summary-card {
        background: white;
        border-radius: 20px;
        padding: 1.75rem;
        box-shadow: 0 4px 24px rgba(0,0,0,0.04);
        border: 1px solid #f0f0f0;
        position: sticky;
        top: 100px;
    }
    .ship-summary-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 1.25rem;
        color: #1a1a1a;
    }

    .ship-info-banner {
        display: flex;
        gap: 12px;
        align-items: center;
        padding: 14px 16px;
        background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
        border: 1px solid #dcfce7;
        border-radius: 14px;
        margin-bottom: 1rem;
    }
    .ship-info-banner ion-icon { font-size: 1.6rem; color: #22c55e; flex-shrink: 0; }
    .ship-info-banner strong { display: block; font-size: 0.88rem; color: #166534; }
    .ship-info-banner span { font-size: 0.78rem; color: #4ade80; }

    .ship-deal-pill {
        display: flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #fef3c7, #fffbeb);
        border: 1px solid #fde68a;
        color: #92400e;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 0.82rem;
        font-weight: 600;
        margin-bottom: 1.25rem;
    }
    .ship-deal-pill ion-icon { color: #f59e0b; font-size: 1rem; }

    .ship-item-row {
        display: flex;
        gap: 14px;
        align-items: center;
        padding: 14px;
        background: #fafafa;
        border-radius: 14px;
        margin-bottom: 1.25rem;
    }
    .ship-item-thumb {
        width: 70px;
        height: 70px;
        border-radius: 12px;
        object-fit: cover;
        flex-shrink: 0;
        border: 1px solid #eee;
    }
    .ship-item-info { flex: 1; min-width: 0; }
    .ship-item-name {
        font-weight: 700;
        font-size: 0.92rem;
        color: #1a1a1a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ship-item-brand { font-size: 0.78rem; color: #999; margin: 2px 0; }
    .ship-item-price { font-size: 0.95rem; font-weight: 700; color: #6B21A8; }

    .ship-coupon-section { margin-bottom: 1rem; }
    .ship-coupon-section label { font-size: 0.82rem; color: #888; font-weight: 500; margin-bottom: 8px; display: block; }
    .ship-coupon-row { display: flex; gap: 8px; }
    .ship-coupon-row input {
        flex: 1;
        padding: 10px 14px;
        border: 1.5px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.88rem;
        font-family: inherit;
    }
    .ship-coupon-row input:focus { outline: none; border-color: #6B21A8; }
    .ship-coupon-row button {
        background: #1a1a1a;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s;
    }
    .ship-coupon-row button:hover { background: #333; }

    .ship-divider { height: 1px; background: #f0f0f0; margin: 1.25rem 0; }

    .ship-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.7rem;
        font-size: 0.9rem;
    }
    .ship-summary-label { color: #888; }
    .ship-summary-val { font-weight: 600; color: #333; }
    .ship-original-price { text-decoration: line-through; color: #ccc; margin-right: 6px; font-weight: 400; }
    .ship-free { color: #22c55e; font-weight: 700; }

    .ship-total-row {
        display: flex;
        justify-content: space-between;
        font-weight: 800;
        font-size: 1.2rem;
        padding-top: 1rem;
        margin-top: 0.5rem;
        border-top: 2px dashed #e5e7eb;
        color: #1a1a1a;
    }

    .ship-guarantee {
        margin: 1.25rem 0;
        padding: 14px 16px;
        background: #f0fdf4;
        border: 1px solid #dcfce7;
        border-radius: 14px;
    }
    .ship-guarantee-header {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 4px;
    }
    .ship-guarantee-header strong { font-size: 0.88rem; color: #166534; }
    .ship-guarantee p { font-size: 0.8rem; color: #4ade80; margin: 0; line-height: 1.5; }
    .ship-guarantee a { color: #2563eb; text-decoration: none; font-weight: 600; }
    .ship-guarantee a:hover { text-decoration: underline; }

    .ship-hv-box {
        background: #fffbeb;
        border: 1px solid #fde68a;
        padding: 14px 16px;
        border-radius: 14px;
        margin-bottom: 1rem;
    }
    .ship-hv-title {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 700;
        color: #92400e;
        font-size: 0.88rem;
        margin-bottom: 6px;
    }
    .ship-hv-box p { font-size: 0.8rem; color: #b45309; margin: 0 0 10px; line-height: 1.5; }
    .ship-hv-check {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .ship-hv-check input { margin-top: 3px; accent-color: #d97706; transform: scale(1.2); }
    .ship-hv-check label { font-size: 0.82rem; color: #92400e; font-weight: 600; cursor: pointer; }

    .ship-btn-pay {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, #6B21A8, #7c3aed);
        color: white;
        border: none;
        border-radius: 14px;
        font-weight: 700;
        font-size: 1.05rem;
        cursor: pointer;
        transition: all 0.25s;
        box-shadow: 0 6px 20px rgba(107,33,168,0.25);
        font-family: inherit;
    }
    .ship-btn-pay:hover {
        background: linear-gradient(135deg, #581c87, #6d28d9);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(107,33,168,0.35);
    }
    .ship-btn-pay:active { transform: translateY(0); }
    .ship-btn-pay ion-icon { font-size: 1.15rem; }

    .ship-payment-icons {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 1rem;
        font-size: 0.75rem;
        color: #bbb;
    }
    .ship-payment-icons ion-icon { font-size: 1.2rem; color: #ccc; }

    @media (max-width: 900px) {
        .ship-grid { grid-template-columns: 1fr; }
        .ship-summary-card { position: static; }
        .ship-fields-grid { grid-template-columns: 1fr; }
        .ship-progress { gap: 0; padding: 0; }
        .ship-step-line { width: 30px; }
    }

    @media (max-width: 768px) {
        .navbar { border-radius: 0 !important; width: 100% !important; margin: 0 !important; max-width: 100vw !important; }
        .btn-thrift { display: none !important; }
        .ship-container { padding: 85px 0.75rem 2rem !important; }
        .ship-card { padding: 1.5rem; border-radius: 16px; }
        .ship-summary-card { padding: 1.25rem; border-radius: 16px; }
        .ship-security-strip { gap: 1rem; flex-wrap: wrap; }
        .ship-step span { font-size: 0.65rem; }
        .ship-step-circle { width: 36px; height: 36px; font-size: 1rem; }
    }
</style>

<div class="ship-container">
    <div class="ship-progress">
        <div class="ship-step active">
            <div class="ship-step-circle">
                <ion-icon name="bag-handle-outline"></ion-icon>
            </div>
            <span>Cart</span>
        </div>
        <div class="ship-step-line active"></div>
        <div class="ship-step active current">
            <div class="ship-step-circle">
                <ion-icon name="location-outline"></ion-icon>
            </div>
            <span>Shipping</span>
        </div>
        <div class="ship-step-line"></div>
        <div class="ship-step">
            <div class="ship-step-circle">
                <ion-icon name="card-outline"></ion-icon>
            </div>
            <span>Payment</span>
        </div>
        <div class="ship-step-line"></div>
        <div class="ship-step">
            <div class="ship-step-circle">
                <ion-icon name="checkmark-done-outline"></ion-icon>
            </div>
            <span>Done</span>
        </div>
    </div>

    <div class="ship-grid">
        <div class="ship-left">
            <div class="ship-card">
                <div class="ship-card-header">
                    <div class="ship-card-icon"><ion-icon name="person-outline"></ion-icon></div>
                    <div>
                        <h3 class="ship-title">Contact & Shipping</h3>
                        <p class="ship-subtitle">Where should we deliver your order?</p>
                    </div>
                </div>

                <?php if(isset($error)): ?>
                <div class="ship-error">
                    <ion-icon name="alert-circle-outline"></ion-icon> <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <form action="" method="POST" id="shipping-form">
                    <div class="ship-fields-grid">
                        <div class="ship-field">
                            <label for="ship_fullname"><ion-icon name="person-outline"></ion-icon> Full Name</label>
                            <input type="text" id="ship_fullname" readonly value="<?php echo htmlspecialchars($user['full_name']); ?>" class="ship-readonly">
                        </div>
                        <div class="ship-field">
                            <label for="ship_email"><ion-icon name="mail-outline"></ion-icon> Email</label>
                            <input type="email" id="ship_email" readonly value="<?php echo htmlspecialchars($user['email']); ?>" class="ship-readonly">
                        </div>
                    </div>

                    <div class="ship-field">
                        <label for="ship_phone"><ion-icon name="call-outline"></ion-icon> Phone Number</label>
                        <div class="ship-phone-wrap">
                            <span class="ship-phone-prefix">
                                <img src="https://flagcdn.com/w20/in.png" alt="IN" style="width:20px;height:14px;border-radius:2px;"> +91
                            </span>
                            <input type="tel" name="phone" id="ship_phone" placeholder="Enter 10-digit number" required
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   maxlength="10" pattern="\d{10}" title="Please enter exactly 10 digits"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
                        </div>
                    </div>

                    <div class="ship-field" style="margin-top:0.5rem; position:relative;">
                        <label for="addr_search"><ion-icon name="location-outline"></ion-icon> Delivery Address</label>
                        <div class="ship-addr-search-wrap">
                            <ion-icon name="search-outline" class="ship-addr-search-icon"></ion-icon>
                            <input type="text" id="addr_search" placeholder="Search your area, landmark, or city..." autocomplete="off">
                        </div>
                        <div id="addr_suggestions" class="ship-suggestions"></div>
                        <textarea name="address" id="final_address" rows="3" placeholder="Full delivery address with pincode..." required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="ship-save-check">
                        <input type="checkbox" id="save_addr" checked>
                        <label for="save_addr">Save address for future orders</label>
                    </div>
                </form>
            </div>

            <div class="ship-security-strip">
                <div class="ship-sec-item">
                    <ion-icon name="shield-checkmark-outline"></ion-icon>
                    <span>Secure Checkout</span>
                </div>
                <div class="ship-sec-item">
                    <ion-icon name="lock-closed-outline"></ion-icon>
                    <span>SSL Encrypted</span>
                </div>
                <div class="ship-sec-item">
                    <ion-icon name="refresh-outline"></ion-icon>
                    <span>Easy Returns</span>
                </div>
            </div>
        </div>

        <div class="ship-right">
            <div class="ship-summary-card">
                <h3 class="ship-summary-title">Order Summary</h3>

                <div class="ship-info-banner">
                    <ion-icon name="car-outline"></ion-icon>
                    <div>
                        <strong>Delivery in 3-5 working days</strong>
                        <span>We'll contact you before delivery</span>
                    </div>
                </div>

                <div class="ship-deal-pill">
                    <ion-icon name="flash-outline"></ion-icon> Great deal — you're saving ₹<?php echo number_format($discount); ?> (<?php echo $discount_pct; ?>% off)
                </div>

                <div class="ship-item-row">
                    <img src="<?php echo htmlspecialchars($image_url); ?>" class="ship-item-thumb" alt="Product">
                    <div class="ship-item-info">
                        <div class="ship-item-name"><?php echo htmlspecialchars($title); ?></div>
                        <div class="ship-item-brand"><?php echo htmlspecialchars($brand); ?></div>
                        <div class="ship-item-price">₹<?php echo number_format($price); ?></div>
                    </div>
                </div>

                <div class="ship-coupon-section">
                    <label>Discount Code</label>
                    <div class="ship-coupon-row" id="coupon-input-row" style="<?php echo $applied_coupon_code ? 'display:none;' : ''; ?>">
                        <input type="text" id="coupon-code" placeholder="Enter code">
                        <button type="button" onclick="applyCoupon()">Apply</button>
                    </div>
                    <div id="coupon-applied" style="<?php echo $applied_coupon_code ? '' : 'display:none;'; ?>padding:8px 12px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:8px;display:flex;align-items:center;justify-content:space-between;font-size:0.9rem;">
                        <span style="color:#22c55e;font-weight:600;">
                            <ion-icon name="checkmark-circle" style="vertical-align:middle;margin-right:4px;"></ion-icon>
                            <span id="coupon-applied-code"><?php echo htmlspecialchars($applied_coupon_code); ?></span>
                            <span id="coupon-applied-msg" style="font-weight:400;color:#64748b;margin-left:4px;">-₹<?php echo number_format($coupon_discount); ?></span>
                        </span>
                        <button type="button" onclick="removeCoupon()" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;padding:0;">
                            <ion-icon name="close-circle"></ion-icon>
                        </button>
                    </div>
                </div>

                <div class="ship-divider"></div>

                <div class="ship-summary-row">
                    <span class="ship-summary-label">Product Price</span>
                    <span class="ship-summary-val">
                        <span class="ship-original-price">₹<?php echo number_format($original_price); ?></span>
                        ₹<?php echo number_format($price); ?>
                    </span>
                </div>
                <div class="ship-summary-row">
                    <span class="ship-summary-label">Shipping <ion-icon name="information-circle-outline" style="font-size:0.85rem;vertical-align:middle;color:#aaa;"></ion-icon></span>
                    <span class="ship-summary-val <?php echo $shipping_cost === 0 ? 'ship-free' : ''; ?>">
                        <?php echo $shipping_cost === 0 ? 'FREE' : '₹' . number_format($shipping_cost); ?>
                    </span>
                </div>
                <div class="ship-summary-row" id="coupon-discount-row" style="<?php echo $coupon_discount > 0 ? '' : 'display:none;'; ?>">
                    <span class="ship-summary-label" style="color:#22c55e;">Coupon Discount</span>
                    <span class="ship-summary-val" style="color:#22c55e;" id="coupon-discount-val">-₹<?php echo number_format($coupon_discount); ?></span>
                </div>

                <div class="ship-total-row">
                    <span>Total</span>
                    <span id="summary-total">₹<?php echo number_format($total); ?></span>
                </div>

                <div class="ship-guarantee">
                    <div class="ship-guarantee-header">
                        <ion-icon name="shield-checkmark" style="color:#22c55e;font-size:1.3rem;"></ion-icon>
                        <strong>Listaria Guarantee</strong>
                    </div>
                    <p>100% refund within 3 days of delivery under covered scenarios. <a href="refund.php" target="_blank">More Details</a></p>
                </div>

                <?php if ($total > 10000): ?>
                <div class="ship-hv-box">
                    <div class="ship-hv-title">
                        <ion-icon name="document-text-outline"></ion-icon> High-Value Item Consent
                    </div>
                    <p>Listaria provides logistic support but ultimate responsibility lies with the seller. Refunds for high-value goods (&gt;₹10k) are subject to manual review.</p>
                    <div class="ship-hv-check">
                        <input type="checkbox" id="high_value_consent">
                        <label for="high_value_consent">I've reviewed the listing and photos thoroughly</label>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" form="shipping-form" class="ship-btn-pay" id="proceed-btn" onclick="validateShipping(event)">
                    <ion-icon name="lock-closed-outline"></ion-icon> Proceed to Payment — ₹<span id="proceed-total"><?php echo number_format($total); ?></span>
                </button>

                <div class="ship-payment-icons">
                    <span>We accept:</span>
                    <ion-icon name="card-outline" title="Cards"></ion-icon>
                    <ion-icon name="phone-portrait-outline" title="UPI"></ion-icon>
                    <ion-icon name="cash-outline" title="COD"></ion-icon>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const searchInput = document.getElementById('addr_search');
const resultsBox = document.getElementById('addr_suggestions');
const finalBox = document.getElementById('final_address');
let debounceTimer;

searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const query = this.value.trim();
    if (query.length < 3) {
        resultsBox.style.display = 'none';
        return;
    }
    debounceTimer = setTimeout(() => fetchAddress(query), 300);
});

function fetchAddress(query) {
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&limit=5&countrycodes=in`)
        .then(r => r.json())
        .then(data => {
            resultsBox.innerHTML = '';
            if (data.length > 0) {
                data.forEach(place => {
                    const div = document.createElement('div');
                    div.className = 'ship-sug-item';
                    const parts = place.display_name.split(',');
                    div.innerHTML = `
                        <ion-icon name="location-outline" class="ship-sug-icon"></ion-icon>
                        <div class="ship-sug-text">
                            <span class="ship-sug-main">${parts[0]}</span>
                            <span class="ship-sug-sub">${parts.slice(1).join(',').trim()}</span>
                        </div>
                    `;
                    div.onclick = () => {
                        finalBox.value = place.display_name;
                        searchInput.value = '';
                        resultsBox.style.display = 'none';
                    };
                    resultsBox.appendChild(div);
                });
                resultsBox.style.display = 'block';
            } else {
                resultsBox.style.display = 'none';
            }
        })
        .catch(err => console.error('Address Fetch Error:', err));
}

document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
        resultsBox.style.display = 'none';
    }
});

function validateShipping(event) {
    const form = document.getElementById('shipping-form');
    if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
        return;
    }
    const consent = document.getElementById('high_value_consent');
    if (consent && !consent.checked) {
        event.preventDefault();
        alert('Please agree to the High-Value Item Consent to proceed.');
        consent.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function applyCoupon() {
    const code = document.getElementById('coupon-code').value.trim();
    if(!code) return;
    const productId = '<?php echo htmlspecialchars($product_id ?? ''); ?>';
    fetch('api/validate_coupon.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({code: code, product_id: productId})
    })
    .then(r => r.json())
    .then(data => {
        if(data.valid) {
            document.getElementById('coupon-input-row').style.display = 'none';
            document.getElementById('coupon-applied').style.display = 'flex';
            document.getElementById('coupon-applied-code').textContent = code.toUpperCase();
            document.getElementById('coupon-applied-msg').textContent = '-' + data.discount_display;
            document.getElementById('coupon-discount-row').style.display = 'flex';
            document.getElementById('coupon-discount-val').textContent = '-' + data.discount_display;
            const basePrice = <?php echo (float)$price; ?>;
            const shipping = <?php echo (float)$shipping_cost; ?>;
            const newTotal = Math.max(0, basePrice + shipping - data.discount_amount);
            document.getElementById('summary-total').textContent = '₹' + newTotal.toLocaleString('en-IN');
            document.getElementById('proceed-total').textContent = newTotal.toLocaleString('en-IN');
        } else {
            alert(data.message);
        }
    })
    .catch(() => alert('Error applying coupon. Please try again.'));
}

function removeCoupon() {
    fetch('api/remove_coupon.php', {method: 'POST'})
    .then(r => r.json())
    .then(() => {
        document.getElementById('coupon-input-row').style.display = 'flex';
        document.getElementById('coupon-applied').style.display = 'none';
        document.getElementById('coupon-code').value = '';
        document.getElementById('coupon-discount-row').style.display = 'none';
        const basePrice = <?php echo (float)$price; ?>;
        const shipping = <?php echo (float)$shipping_cost; ?>;
        const newTotal = basePrice + shipping;
        document.getElementById('summary-total').textContent = '₹' + newTotal.toLocaleString('en-IN');
        document.getElementById('proceed-total').textContent = newTotal.toLocaleString('en-IN');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
