<?php
require 'includes/db.php';

$product_id = $_GET['id'] ?? null;
$product = null;
$price = 0;
$image_url = 'https://via.placeholder.com/150';
$title = 'Product Name';
$brand = 'Brand';

if ($product_id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product_data = $stmt->fetch();

    if ($product_data) {
        $title = $product_data['title'];
        $brand = $product_data['brand'];
        $price = $product_data['price_min'];

        $images = json_decode($product_data['image_paths'], true);
        if (!empty($images)) {
            $image_url = $images[0];
        }

        if (isset($_SESSION['user_id'])) {
            $stmtNeg = $pdo->prepare("SELECT final_price FROM negotiations WHERE product_id = ? AND buyer_id = ? AND final_price IS NOT NULL");
            $stmtNeg->execute([$product_id, $_SESSION['user_id']]);
            $offer = $stmtNeg->fetch();
            if ($offer) {
                $price = $offer['final_price'];
            }
        }
    } else {
        $price = 13000.00;
    }
} else {
    $price = 13000.00;
}

$settingsStmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'payment_config'");
$payConfigRaw = $settingsStmt->fetchColumn();
$payConfig = json_decode($payConfigRaw ?: '{"cod":true,"phonepe":true}', true);

$original_price = $price * 1.5;
$shipping_cost = 85.00;
if (isset($_SESSION['apply_free_shipping']) && $_SESSION['apply_free_shipping'] === true) {
    $shipping_cost = 0;
}
$total = $price + $shipping_cost;
$discount = $original_price - $price;
$discount_pct = round(($discount / $original_price) * 100);

$sourceParam = (isset($_GET['source']) ? '&source=' . urlencode($_GET['source']) : '');

include 'includes/header.php';
?>

<?php if (isset($_GET['source']) && $_GET['source'] === 'thrift'): ?>
<style>
    body, .pay-container { background-color: #eae4cc !important; font-family: 'Courier New', monospace !important; }
    .pay-container h3, .pay-container h4, .pay-container label, .pay-container .pay-opt-name { font-family: 'Courier New', monospace !important; color: #1a1a1a !important; }
    .pay-container .pay-title { font-family: 'Times New Roman', serif !important; font-weight: 800 !important; text-transform: uppercase; }
    .pay-card, .pay-opt, .pay-btn-continue, .pay-mini-summary { border: 2px solid #1a1a1a !important; box-shadow: 4px 4px 0 rgba(26,26,26,0.9) !important; border-radius: 0 !important; background: #fdfcf8 !important; }
    .pay-opt-icon { border-radius: 0 !important; border: 2px solid #1a1a1a !important; }
    .pay-opt.selected { background: #dcfce7 !important; border-color: #1a1a1a !important; }
    .pay-btn-continue { background: #1a1a1a !important; color: #fff !important; text-transform: uppercase; font-weight: 800 !important; }
    .pay-btn-continue:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0 rgba(26,26,26,0.9) !important; }
    .pay-step-circle { border-radius: 0 !important; }
</style>
<?php endif; ?>

<style>
    .pay-container { max-width: 1000px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }

    .pay-progress {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 2.5rem;
        padding: 0 2rem;
    }
    .pay-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }
    .pay-step span { font-size: 0.75rem; font-weight: 600; color: #ccc; text-transform: uppercase; letter-spacing: 0.5px; }
    .pay-step.completed span { color: #6B21A8; }
    .pay-step.active span { color: #6B21A8; font-weight: 700; }
    .pay-step.current span { color: #6B21A8; }
    .pay-step-link { text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 6px; }
    .pay-step-circle {
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
    .pay-step.completed .pay-step-circle {
        background: linear-gradient(135deg, #6B21A8, #9333EA);
        color: white;
        box-shadow: 0 4px 12px rgba(107,33,168,0.25);
    }
    .pay-step.active .pay-step-circle {
        background: linear-gradient(135deg, #6B21A8, #9333EA);
        color: white;
        box-shadow: 0 0 0 4px rgba(107,33,168,0.15), 0 4px 15px rgba(107,33,168,0.3);
    }
    .pay-step-line {
        width: 60px;
        height: 3px;
        background: #e5e7eb;
        border-radius: 2px;
        margin: 0 0.5rem;
        margin-bottom: 20px;
    }
    .pay-step-line.completed { background: linear-gradient(90deg, #6B21A8, #9333EA); }

    .pay-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 2rem; align-items: start; }

    .pay-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 24px rgba(0,0,0,0.04);
        border: 1px solid #f0f0f0;
    }
    .pay-card-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #f5f5f5;
    }
    .pay-card-icon {
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
    .pay-title { font-size: 1.2rem; font-weight: 700; color: #1a1a1a; margin: 0; }
    .pay-subtitle { font-size: 0.85rem; color: #999; margin: 2px 0 0; }

    .pay-options { display: flex; flex-direction: column; gap: 12px; margin-bottom: 1.5rem; border: none; padding: 0; margin-top: 0; }
    .pay-sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }

    .pay-opt {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 20px;
        border: 1.5px solid #e5e7eb;
        border-radius: 16px;
        cursor: pointer;
        transition: all 0.25s;
        background: #fff;
    }
    .pay-opt:hover { border-color: #c4b5fd; background: #faf5ff; }
    .pay-opt:focus-within { border-color: #6B21A8; outline: 2px solid rgba(107,33,168,0.3); outline-offset: 2px; }
    .pay-opt.selected {
        border-color: #6B21A8;
        background: linear-gradient(135deg, #faf5ff, #f3e8ff);
        box-shadow: 0 0 0 3px rgba(107,33,168,0.1);
    }

    .pay-opt-left { display: flex; align-items: center; gap: 14px; }
    .pay-opt-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .pay-icon-purple { background: linear-gradient(135deg, #ede9fe, #e9d5ff); color: #7c3aed; }
    .pay-icon-green { background: linear-gradient(135deg, #dcfce7, #d1fae5); color: #16a34a; }
    .pay-icon-blue { background: linear-gradient(135deg, #dbeafe, #e0f2fe); color: #2563eb; }
    .pay-icon-cyan { background: linear-gradient(135deg, #cffafe, #e0f2fe); color: #0891b2; }

    .pay-opt-info { display: flex; flex-direction: column; }
    .pay-opt-name { font-weight: 700; font-size: 0.95rem; color: #1a1a1a; }
    .pay-opt-desc { font-size: 0.78rem; color: #999; margin-top: 2px; }

    .pay-opt-right { display: flex; align-items: center; }
    .pay-radio {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        border: 2px solid #d1d5db;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    .pay-radio-dot {
        width: 0;
        height: 0;
        border-radius: 50%;
        background: #6B21A8;
        transition: all 0.2s;
    }
    .pay-opt.selected .pay-radio {
        border-color: #6B21A8;
    }
    .pay-opt.selected .pay-radio-dot {
        width: 12px;
        height: 12px;
    }

    .pay-btn-continue {
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
    .pay-btn-continue:hover {
        background: linear-gradient(135deg, #581c87, #6d28d9);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(107,33,168,0.35);
    }
    .pay-btn-continue:active { transform: translateY(0); }
    .pay-btn-continue ion-icon { font-size: 1.15rem; }

    .pay-security-strip {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin-top: 1.5rem;
    }
    .pay-sec-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.78rem;
        color: #999;
        font-weight: 500;
    }
    .pay-sec-item ion-icon { font-size: 1rem; color: #22c55e; }

    .pay-mini-summary {
        background: white;
        border-radius: 20px;
        padding: 1.75rem;
        box-shadow: 0 4px 24px rgba(0,0,0,0.04);
        border: 1px solid #f0f0f0;
        position: sticky;
        top: 100px;
    }
    .pay-summary-title { font-size: 1.1rem; font-weight: 700; margin: 0 0 1.25rem; color: #1a1a1a; }

    .pay-item-row {
        display: flex;
        gap: 14px;
        align-items: center;
        padding: 14px;
        background: #fafafa;
        border-radius: 14px;
    }
    .pay-item-thumb {
        width: 65px;
        height: 65px;
        border-radius: 12px;
        object-fit: cover;
        flex-shrink: 0;
        border: 1px solid #eee;
    }
    .pay-item-info { flex: 1; min-width: 0; }
    .pay-item-name {
        font-weight: 700;
        font-size: 0.92rem;
        color: #1a1a1a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pay-item-brand { font-size: 0.78rem; color: #999; margin-top: 2px; }

    .pay-divider { height: 1px; background: #f0f0f0; margin: 1.25rem 0; }

    .pay-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.6rem;
        font-size: 0.88rem;
        color: #666;
    }
    .pay-row span:last-child { font-weight: 600; color: #333; }
    .pay-strike { text-decoration: line-through; color: #ccc; margin-right: 6px; font-weight: 400; }
    .pay-free-tag { color: #22c55e !important; font-weight: 700 !important; }
    .pay-savings {
        color: #16a34a !important;
        font-weight: 600;
    }
    .pay-savings span { color: #16a34a !important; }

    .pay-total-row {
        display: flex;
        justify-content: space-between;
        font-weight: 800;
        font-size: 1.15rem;
        padding-top: 1rem;
        margin-top: 0.5rem;
        border-top: 2px dashed #e5e7eb;
        color: #1a1a1a;
    }

    .pay-guarantee {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 1.25rem;
        padding: 12px 14px;
        background: #f0fdf4;
        border: 1px solid #dcfce7;
        border-radius: 12px;
        font-size: 0.82rem;
        color: #166534;
        font-weight: 600;
    }
    .pay-guarantee ion-icon { font-size: 1.2rem; flex-shrink: 0; }

    @media (max-width: 900px) {
        .pay-grid { grid-template-columns: 1fr; }
        .pay-mini-summary { position: static; }
        .pay-progress { padding: 0; }
        .pay-step-line { width: 30px; }
    }

    @media (max-width: 768px) {
        .navbar { border-radius: 0 !important; width: 100% !important; margin: 0 !important; max-width: 100vw !important; }
        .btn-thrift { display: none !important; }
        .pay-container { padding: 85px 0.75rem 2rem !important; }
        .pay-card { padding: 1.5rem; border-radius: 16px; }
        .pay-mini-summary { padding: 1.25rem; border-radius: 16px; }
        .pay-opt { padding: 14px 16px; }
        .pay-opt-icon { width: 38px; height: 38px; font-size: 1.2rem; border-radius: 10px; }
        .pay-opt-name { font-size: 0.88rem; }
        .pay-security-strip { gap: 1rem; flex-wrap: wrap; }
        .pay-step span { font-size: 0.65rem; }
        .pay-step-circle { width: 36px; height: 36px; font-size: 1rem; }
    }
</style>

<div class="pay-container">
    <div class="pay-progress">
        <div class="pay-step completed">
            <div class="pay-step-circle"><ion-icon name="checkmark"></ion-icon></div>
            <span>Cart</span>
        </div>
        <div class="pay-step-line completed"></div>
        <div class="pay-step completed">
            <a href="shipping_info.php?id=<?php echo $product_id; ?><?php echo $sourceParam; ?>" class="pay-step-link">
                <div class="pay-step-circle"><ion-icon name="checkmark"></ion-icon></div>
                <span>Shipping</span>
            </a>
        </div>
        <div class="pay-step-line completed"></div>
        <div class="pay-step active current">
            <div class="pay-step-circle"><ion-icon name="card-outline"></ion-icon></div>
            <span>Payment</span>
        </div>
        <div class="pay-step-line"></div>
        <div class="pay-step">
            <div class="pay-step-circle"><ion-icon name="checkmark-done-outline"></ion-icon></div>
            <span>Done</span>
        </div>
    </div>

    <div class="pay-grid">
        <div class="pay-left">
            <div class="pay-card">
                <div class="pay-card-header">
                    <div class="pay-card-icon"><ion-icon name="card-outline"></ion-icon></div>
                    <div>
                        <h3 class="pay-title">Choose Payment</h3>
                        <p class="pay-subtitle">How would you like to pay?</p>
                    </div>
                </div>

                <form action="order_summary.php" method="GET" id="paymentForm">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($product_id); ?>">
                    <?php if (isset($_GET['source'])): ?>
                    <input type="hidden" name="source" value="<?php echo htmlspecialchars($_GET['source']); ?>">
                    <?php endif; ?>

                    <fieldset class="pay-options" role="radiogroup" aria-label="Payment method">
                        <?php if(!empty($payConfig['phonepe'])): ?>
                        <label class="pay-opt" for="pay_phonepe">
                            <div class="pay-opt-left">
                                <div class="pay-opt-icon pay-icon-purple">
                                    <ion-icon name="phone-portrait-outline"></ion-icon>
                                </div>
                                <div class="pay-opt-info">
                                    <span class="pay-opt-name">PhonePe</span>
                                    <span class="pay-opt-desc">UPI, Wallet</span>
                                </div>
                            </div>
                            <div class="pay-opt-right">
                                <div class="pay-radio">
                                    <div class="pay-radio-dot"></div>
                                </div>
                            </div>
                            <input type="radio" name="pay_method" id="pay_phonepe" value="phonepe" class="pay-sr-only">
                        </label>
                        <?php endif; ?>

                        <?php if(!empty($payConfig['cod'])): ?>
                        <label class="pay-opt" for="pay_cod">
                            <div class="pay-opt-left">
                                <div class="pay-opt-icon pay-icon-green">
                                    <ion-icon name="cash-outline"></ion-icon>
                                </div>
                                <div class="pay-opt-info">
                                    <span class="pay-opt-name">Cash on Delivery</span>
                                    <span class="pay-opt-desc">Pay when you receive</span>
                                </div>
                            </div>
                            <div class="pay-opt-right">
                                <div class="pay-radio">
                                    <div class="pay-radio-dot"></div>
                                </div>
                            </div>
                            <input type="radio" name="pay_method" id="pay_cod" value="cod" class="pay-sr-only">
                        </label>
                        <?php endif; ?>

                        <?php if(!empty($payConfig['online'])): ?>
                        <label class="pay-opt" for="pay_online">
                            <div class="pay-opt-left">
                                <div class="pay-opt-icon pay-icon-blue">
                                    <ion-icon name="card-outline"></ion-icon>
                                </div>
                                <div class="pay-opt-info">
                                    <span class="pay-opt-name">Online Payment</span>
                                    <span class="pay-opt-desc">Debit Card, Credit Card, Net Banking, UPI</span>
                                </div>
                            </div>
                            <div class="pay-opt-right">
                                <div class="pay-radio">
                                    <div class="pay-radio-dot"></div>
                                </div>
                            </div>
                            <input type="radio" name="pay_method" id="pay_online" value="online" class="pay-sr-only">
                        </label>
                        <?php endif; ?>

                        <?php if(!empty($payConfig['paytm'])): ?>
                        <label class="pay-opt" for="pay_paytm">
                            <div class="pay-opt-left">
                                <div class="pay-opt-icon pay-icon-cyan">
                                    <ion-icon name="wallet-outline"></ion-icon>
                                </div>
                                <div class="pay-opt-info">
                                    <span class="pay-opt-name">Paytm</span>
                                    <span class="pay-opt-desc">Wallet, Cards, Net Banking, UPI</span>
                                </div>
                            </div>
                            <div class="pay-opt-right">
                                <div class="pay-radio">
                                    <div class="pay-radio-dot"></div>
                                </div>
                            </div>
                            <input type="radio" name="pay_method" id="pay_paytm" value="paytm" class="pay-sr-only">
                        </label>
                        <?php endif; ?>
                    </fieldset>

                    <button type="submit" class="pay-btn-continue">
                        <ion-icon name="arrow-forward-outline"></ion-icon> Continue to Review Order
                    </button>
                </form>
            </div>

            <div class="pay-security-strip">
                <div class="pay-sec-item">
                    <ion-icon name="shield-checkmark-outline"></ion-icon>
                    <span>Secure Payment</span>
                </div>
                <div class="pay-sec-item">
                    <ion-icon name="lock-closed-outline"></ion-icon>
                    <span>256-bit SSL</span>
                </div>
                <div class="pay-sec-item">
                    <ion-icon name="refresh-outline"></ion-icon>
                    <span>Easy Refunds</span>
                </div>
            </div>
        </div>

        <div class="pay-right">
            <div class="pay-mini-summary">
                <h4 class="pay-summary-title">Order Summary</h4>

                <div class="pay-item-row">
                    <img src="<?php echo htmlspecialchars($image_url); ?>" class="pay-item-thumb" alt="Product">
                    <div class="pay-item-info">
                        <div class="pay-item-name"><?php echo htmlspecialchars($title); ?></div>
                        <div class="pay-item-brand"><?php echo htmlspecialchars($brand); ?></div>
                    </div>
                </div>

                <div class="pay-divider"></div>

                <div class="pay-row">
                    <span>Product</span>
                    <span>
                        <span class="pay-strike">₹<?php echo number_format($original_price); ?></span>
                        ₹<?php echo number_format($price); ?>
                    </span>
                </div>
                <div class="pay-row">
                    <span>Shipping</span>
                    <span class="<?php echo $shipping_cost === 0 ? 'pay-free-tag' : ''; ?>">
                        <?php echo $shipping_cost === 0 ? 'FREE' : '₹' . number_format($shipping_cost); ?>
                    </span>
                </div>
                <div class="pay-row pay-savings">
                    <span>You Save</span>
                    <span>₹<?php echo number_format($discount); ?> (<?php echo $discount_pct; ?>%)</span>
                </div>

                <div class="pay-total-row">
                    <span>Total</span>
                    <span>₹<?php echo number_format($total); ?></span>
                </div>

                <div class="pay-guarantee">
                    <ion-icon name="shield-checkmark" style="color:#22c55e;"></ion-icon>
                    <span>Protected by Listaria Guarantee</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const options = document.querySelectorAll('.pay-opt');
    const radios = document.querySelectorAll('input[name="pay_method"]');

    function selectByRadio(radio) {
        options.forEach(o => o.classList.remove('selected'));
        radio.closest('.pay-opt').classList.add('selected');
    }

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            selectByRadio(this);
        });
    });

    options.forEach(opt => {
        opt.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            selectByRadio(radio);
        });
    });

    if (radios.length > 0) {
        radios[0].checked = true;
        selectByRadio(radios[0]);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
