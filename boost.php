<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$userId = $_SESSION['user_id'];
$msg = ''; $msgType = 'success';
$currency = '₹';
try { $currency = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key='currency_symbol'")->fetchColumn() ?: '₹'; } catch(Exception $e){}

// Boost plan pricing from settings (or defaults)
function boostPrice($pdo, $days) {
    try {
        $v = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key=?");
        $v->execute(["boost_price_$days"]); $r = $v->fetchColumn();
        return $r !== false ? (float)$r : ($days === 7 ? 99 : ($days === 14 ? 179 : 299));
    } catch(Exception $e) { return $days === 7 ? 99 : ($days === 14 ? 179 : 299); }
}
$plans = [
    ['days' => 7,  'label' => '7 Days',  'price' => boostPrice($pdo, 7),  'icon' => '🚀', 'perks' => 'Top of search results for 1 week'],
    ['days' => 14, 'label' => '14 Days', 'price' => boostPrice($pdo, 14), 'icon' => '⚡', 'perks' => 'Top of search results for 2 weeks'],
    ['days' => 30, 'label' => '30 Days', 'price' => boostPrice($pdo, 30), 'icon' => '👑', 'perks' => 'Top of search results for 1 month + Featured badge'],
];

// Handle boost purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_boost'])) {
    $productId  = (int)$_POST['product_id'];
    $planDays   = (int)$_POST['plan_days'];
    $payMethod  = $_POST['payment_method'] ?? 'wallet';

    // Validate plan
    $validDays = [7, 14, 30];
    if (!in_array($planDays, $validDays)) { $msg = 'Invalid plan.'; $msgType = 'error'; goto done; }

    // Validate product belongs to user and is approved
    $prod = $pdo->prepare("SELECT * FROM products WHERE id=? AND user_id=? AND approval_status='approved'");
    $prod->execute([$productId, $userId]);
    $product = $prod->fetch();
    if (!$product) { $msg = 'Listing not found or not approved yet.'; $msgType = 'error'; goto done; }

    $amount = boostPrice($pdo, $planDays);

    if ($payMethod === 'wallet') {
        // Check wallet balance
        $bal = (float)$pdo->prepare("SELECT wallet_balance FROM users WHERE id=?")->execute([$userId]) ? 
               (float)$pdo->query("SELECT wallet_balance FROM users WHERE id=$userId")->fetchColumn() : 0;
        $balStmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id=?");
        $balStmt->execute([$userId]);
        $bal = (float)$balStmt->fetchColumn();

        if ($bal < $amount) { $msg = "Insufficient wallet balance. You have {$currency}" . number_format($bal, 2) . ", need {$currency}" . number_format($amount, 2) . "."; $msgType = 'error'; goto done; }

        // Deduct wallet & activate boost immediately
        $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id=?")->execute([$amount, $userId]);
        $boostedFrom  = date('Y-m-d H:i:s');
        $boostedUntil = date('Y-m-d H:i:s', strtotime("+$planDays days"));
        $pdo->prepare("UPDATE products SET is_featured=1, boosted_until=? WHERE id=?")->execute([$boostedUntil, $productId]);
        $pdo->prepare("INSERT INTO boost_orders (product_id,user_id,plan_days,amount,payment_method,status,boosted_from,boosted_until) VALUES (?,?,?,?,'wallet','active',?,?)")
            ->execute([$productId, $userId, $planDays, $amount, $boostedFrom, $boostedUntil]);
        $msg = "Boost activated! Your listing is now featured for $planDays days. 🎉";

    } else {
        // Manual / bank transfer — pending admin approval
        $pdo->prepare("INSERT INTO boost_orders (product_id,user_id,plan_days,amount,payment_method,status) VALUES (?,?,?,?,'manual','pending')")
            ->execute([$productId, $userId, $planDays, $amount]);
        $msg = "Boost request submitted! Once your payment is verified, your listing will be featured. We'll notify you.";
    }
    done:;
}

// Fetch user's approved listings
$myListings = $pdo->prepare("SELECT * FROM products WHERE user_id=? AND approval_status='approved' ORDER BY created_at DESC");
$myListings->execute([$userId]);
$listings = $myListings->fetchAll();

// Fetch user's boost history
$boostHistory = $pdo->prepare("SELECT bo.*, p.title as product_title FROM boost_orders bo JOIN products p ON bo.product_id=p.id WHERE bo.user_id=? ORDER BY bo.created_at DESC LIMIT 20");
$boostHistory->execute([$userId]);
$history = $boostHistory->fetchAll();

// Wallet balance
$walletStmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id=?");
$walletStmt->execute([$userId]);
$walletBalance = (float)$walletStmt->fetchColumn();

include 'includes/header.php';
?>
<style>
.boost-wrap { max-width: 960px; margin: 0 auto; padding: 100px 20px 60px; }
.boost-hero { text-align:center; margin-bottom: 2.5rem; }
.boost-hero h1 { font-size:2rem; font-weight:800; margin:0 0 8px; }
.boost-hero p { color:#64748b; font-size:1rem; margin:0; }
.wallet-bar { display:flex; align-items:center; justify-content:space-between; background:linear-gradient(135deg,#6B21A8,#9333ea); color:white; border-radius:14px; padding:1.25rem 1.5rem; margin-bottom:2rem; }
.wallet-bar .wb-label { font-size:0.85rem; opacity:0.85; }
.wallet-bar .wb-balance { font-size:1.6rem; font-weight:800; }
.wallet-bar .wb-btn { background:rgba(255,255,255,0.2); color:white; border:1px solid rgba(255,255,255,0.3); padding:8px 18px; border-radius:8px; font-weight:600; font-size:0.85rem; cursor:pointer; text-decoration:none; transition:background 0.2s; }
.wallet-bar .wb-btn:hover { background:rgba(255,255,255,0.35); }

.section-title { font-size:1.1rem; font-weight:700; color:#1a1a1a; margin:0 0 1.25rem; }
.plans-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:2rem; }
.plan-card { border:2px solid #e2e8f0; border-radius:14px; padding:1.5rem 1.25rem; text-align:center; cursor:pointer; transition:all 0.2s; position:relative; background:white; }
.plan-card:hover { border-color:#6B21A8; transform:translateY(-2px); box-shadow:0 8px 24px rgba(107,33,168,0.12); }
.plan-card.selected { border-color:#6B21A8; background:#faf5ff; box-shadow:0 8px 24px rgba(107,33,168,0.15); }
.plan-card.popular { border-color:#f59e0b; }
.plan-card.popular.selected { border-color:#6B21A8; }
.popular-badge { position:absolute; top:-10px; left:50%; transform:translateX(-50%); background:#f59e0b; color:white; font-size:0.68rem; font-weight:700; padding:3px 12px; border-radius:50px; white-space:nowrap; }
.plan-icon { font-size:2rem; margin-bottom:8px; }
.plan-label { font-weight:700; font-size:1rem; color:#1a1a1a; margin-bottom:4px; }
.plan-price { font-size:1.6rem; font-weight:800; color:#6B21A8; margin:8px 0; }
.plan-perks { font-size:0.78rem; color:#64748b; line-height:1.4; }
.plan-radio { display:none; }

.compose-card { background:white; border:1px solid #e2e8f0; border-radius:14px; padding:1.75rem; margin-bottom:2rem; }
.form-field { margin-bottom:1.25rem; }
.form-field label { display:block; font-weight:600; font-size:0.85rem; color:#333; margin-bottom:0.4rem; }
.form-field select { width:100%; padding:10px 14px; border:1px solid #e2e8f0; border-radius:8px; font-size:0.9rem; font-family:inherit; background:white; }
.form-field select:focus { outline:none; border-color:#6B21A8; }
.pay-options { display:flex; gap:1rem; }
.pay-opt { flex:1; border:2px solid #e2e8f0; border-radius:10px; padding:1rem; text-align:center; cursor:pointer; transition:all 0.2s; }
.pay-opt:hover { border-color:#6B21A8; }
.pay-opt.active { border-color:#6B21A8; background:#faf5ff; }
.pay-opt input { display:none; }
.pay-opt-icon { font-size:1.5rem; margin-bottom:4px; }
.pay-opt-label { font-weight:700; font-size:0.85rem; color:#1a1a1a; }
.pay-opt-sub { font-size:0.72rem; color:#94a3b8; }
.bank-info { background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:1rem 1.25rem; margin-top:1rem; font-size:0.85rem; color:#78350f; display:none; }
.bank-info strong { display:block; margin-bottom:6px; }
.submit-btn { width:100%; padding:14px; background:#6B21A8; color:white; border:none; border-radius:10px; font-weight:700; font-size:1rem; cursor:pointer; transition:background 0.2s; display:flex; align-items:center; justify-content:center; gap:8px; }
.submit-btn:hover { background:#581c87; }
.summary-row { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #f0f0f0; font-size:0.88rem; }
.summary-row:last-child { border-bottom:none; font-weight:700; font-size:0.95rem; }

.history-card { background:white; border:1px solid #e2e8f0; border-radius:14px; padding:1.75rem; }
.boost-row { display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid #f5f5f5; }
.boost-row:last-child { border-bottom:none; }
.boost-pill { padding:3px 10px; border-radius:50px; font-size:0.72rem; font-weight:700; }
.pill-active { background:#dcfce7; color:#166534; }
.pill-pending { background:#fef3c7; color:#92400e; }
.pill-expired { background:#f3f4f6; color:#6b7280; }

.alert { padding:1rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-weight:500; font-size:0.9rem; display:flex; align-items:center; gap:8px; }
.alert-success { background:#f0fdf4; color:#166534; border-left:4px solid #22c55e; }
.alert-error { background:#fef2f2; color:#991b1b; border-left:4px solid #ef4444; }

@media(max-width:680px) { .plans-grid { grid-template-columns:1fr; } .pay-options { flex-direction:column; } }
</style>

<div class="boost-wrap">
    <div class="boost-hero">
        <h1>🚀 Boost Your Listing</h1>
        <p>Get more visibility — featured listings appear at the top of search results</p>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msgType; ?>">
        <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <!-- Wallet Bar -->
    <div class="wallet-bar">
        <div>
            <div class="wb-label">Your Wallet Balance</div>
            <div class="wb-balance"><?php echo $currency; ?><?php echo number_format($walletBalance, 2); ?></div>
        </div>
        <a href="profile.php" class="wb-btn">Top Up</a>
    </div>

    <?php if (empty($listings)): ?>
    <div style="text-align:center;padding:3rem;background:white;border-radius:14px;border:1px solid #e2e8f0;">
        <div style="font-size:3rem;">📦</div>
        <h3 style="color:#1a1a1a;">No approved listings yet</h3>
        <p style="color:#64748b;">You need at least one approved listing to use Boost.</p>
        <a href="sell.php" style="display:inline-block;margin-top:1rem;padding:10px 24px;background:#6B21A8;color:white;border-radius:8px;font-weight:700;text-decoration:none;">Create a Listing</a>
    </div>
    <?php else: ?>

    <form method="POST" id="boostForm">
        <input type="hidden" name="buy_boost" value="1">
        <input type="hidden" name="plan_days" id="hiddenPlanDays" value="7">
        <input type="hidden" name="payment_method" id="hiddenPayMethod" value="wallet">

        <!-- Plan Selection -->
        <div class="section-title">Choose a Boost Plan</div>
        <div class="plans-grid" id="plansGrid">
            <?php foreach ($plans as $i => $plan): ?>
            <div class="plan-card <?php echo $plan['days'] === 14 ? 'popular' : ''; ?> <?php echo $i === 0 ? 'selected' : ''; ?>"
                 onclick="selectPlan(<?php echo $plan['days']; ?>, <?php echo $plan['price']; ?>, this)">
                <?php if ($plan['days'] === 14): ?><span class="popular-badge">Most Popular</span><?php endif; ?>
                <div class="plan-icon"><?php echo $plan['icon']; ?></div>
                <div class="plan-label"><?php echo $plan['label']; ?></div>
                <div class="plan-price"><?php echo $currency; ?><?php echo number_format($plan['price']); ?></div>
                <div class="plan-perks"><?php echo htmlspecialchars($plan['perks']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Listing + Payment -->
        <div class="compose-card">
            <div class="form-field">
                <label>Select Listing to Boost</label>
                <select name="product_id" id="productSelect" required onchange="updateBoostStatus()">
                    <option value="">-- Choose a listing --</option>
                    <?php foreach ($listings as $l):
                        $isBoosted = !empty($l['boosted_until']) && strtotime($l['boosted_until']) > time();
                        $label = htmlspecialchars($l['title']);
                        if ($isBoosted) $label .= ' [Boosted until ' . date('M j', strtotime($l['boosted_until'])) . ']';
                    ?>
                    <option value="<?php echo $l['id']; ?>" data-boosted="<?php echo $isBoosted ? '1' : '0'; ?>" data-until="<?php echo htmlspecialchars($l['boosted_until'] ?? ''); ?>">
                        <?php echo $label; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div id="boostStatusNote" style="margin-top:6px;font-size:0.78rem;"></div>
            </div>

            <!-- Payment Method -->
            <div class="form-field">
                <label>Payment Method</label>
                <div class="pay-options">
                    <div class="pay-opt active" id="payWallet" onclick="selectPayment('wallet')">
                        <div class="pay-opt-icon">👜</div>
                        <div class="pay-opt-label">Wallet</div>
                        <div class="pay-opt-sub">Instant activation</div>
                    </div>
                    <div class="pay-opt" id="payManual" onclick="selectPayment('manual')">
                        <div class="pay-opt-icon">🏦</div>
                        <div class="pay-opt-label">Bank Transfer</div>
                        <div class="pay-opt-sub">Manual, ~24h approval</div>
                    </div>
                </div>
                <div class="bank-info" id="bankInfoBox">
                    <strong>Bank Transfer Details</strong>
                    Transfer the amount to our account and send proof to our WhatsApp / email. Your boost will be activated within 24 hours after verification.
                    <?php
                    $bankDetails = '';
                    try { $bankDetails = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key='bank_details'")->fetchColumn(); } catch(Exception $e){}
                    if ($bankDetails): ?>
                    <div style="margin-top:8px;padding:8px;background:rgba(0,0,0,0.05);border-radius:6px;font-family:monospace;font-size:0.82rem;"><?php echo nl2br(htmlspecialchars($bankDetails)); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Summary -->
            <div style="background:#faf5ff;border-radius:10px;padding:1rem;margin-bottom:1.25rem;">
                <div style="font-weight:700;font-size:0.85rem;color:#6B21A8;margin-bottom:8px;">Order Summary</div>
                <div class="summary-row"><span>Boost Plan</span><span id="sumPlan">7 Days</span></div>
                <div class="summary-row"><span>Duration</span><span id="sumDuration">Until <?php echo date('M j, Y', strtotime('+7 days')); ?></span></div>
                <div class="summary-row"><span>Payment</span><span id="sumPay">Wallet</span></div>
                <div class="summary-row"><span>Total</span><span id="sumTotal" style="color:#6B21A8;"><?php echo $currency; ?><?php echo number_format($plans[0]['price']); ?></span></div>
            </div>

            <button type="submit" class="submit-btn">
                <span>🚀</span> <span id="submitLabel">Activate Boost — <?php echo $currency; ?><?php echo number_format($plans[0]['price']); ?></span>
            </button>
        </div>
    </form>
    <?php endif; ?>

    <!-- Boost History -->
    <?php if (!empty($history)): ?>
    <div class="section-title" style="margin-top:2rem;">Your Boost History</div>
    <div class="history-card">
        <?php foreach ($history as $h):
            $statusClass = match($h['status']) { 'active' => 'pill-active', 'pending' => 'pill-pending', default => 'pill-expired' };
        ?>
        <div class="boost-row">
            <div style="flex:1;">
                <div style="font-weight:600;font-size:0.9rem;color:#1a1a1a;"><?php echo htmlspecialchars($h['product_title']); ?></div>
                <div style="font-size:0.78rem;color:#64748b;margin-top:2px;">
                    <?php echo $h['plan_days']; ?> days &middot; <?php echo $currency; ?><?php echo number_format($h['amount']); ?>
                    &middot; <?php echo ucfirst($h['payment_method']); ?>
                    &middot; <?php echo date('M j, Y', strtotime($h['created_at'])); ?>
                </div>
            </div>
            <span class="boost-pill <?php echo $statusClass; ?>"><?php echo ucfirst($h['status']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
var planPrices = { <?php foreach($plans as $p) echo $p['days'] . ':' . $p['price'] . ','; ?> };
var currency   = '<?php echo $currency; ?>';
var selectedDays  = 7;
var selectedPrice = <?php echo $plans[0]['price']; ?>;

function selectPlan(days, price, el) {
    document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    selectedDays  = days;
    selectedPrice = price;
    document.getElementById('hiddenPlanDays').value = days;
    var until = new Date(); until.setDate(until.getDate() + days);
    var opts  = { month:'short', day:'numeric', year:'numeric' };
    document.getElementById('sumPlan').textContent     = days + ' Days';
    document.getElementById('sumDuration').textContent = 'Until ' + until.toLocaleDateString('en-IN', opts);
    document.getElementById('sumTotal').textContent    = currency + price.toLocaleString('en-IN');
    document.getElementById('submitLabel').textContent = 'Activate Boost — ' + currency + price.toLocaleString('en-IN');
}

function selectPayment(method) {
    document.getElementById('hiddenPayMethod').value = method;
    document.getElementById('payWallet').classList.toggle('active', method === 'wallet');
    document.getElementById('payManual').classList.toggle('active', method === 'manual');
    document.getElementById('bankInfoBox').style.display = method === 'manual' ? 'block' : 'none';
    document.getElementById('sumPay').textContent = method === 'wallet' ? 'Wallet (instant)' : 'Bank Transfer (~24h)';
}

// Pre-select product from URL param
(function() {
    var params = new URLSearchParams(window.location.search);
    var pid = params.get('product');
    if (pid) {
        var sel = document.getElementById('productSelect');
        if (sel) { sel.value = pid; updateBoostStatus(); }
    }
})();

function updateBoostStatus() {
    var sel = document.getElementById('productSelect');
    var opt = sel.options[sel.selectedIndex];
    var note = document.getElementById('boostStatusNote');
    if (!opt || !opt.value) { note.innerHTML = ''; return; }
    if (opt.dataset.boosted === '1') {
        var d = new Date(opt.dataset.until);
        note.innerHTML = '<span style="color:#f59e0b;font-weight:600;">⚡ Already boosted until ' + d.toLocaleDateString('en-IN',{month:'short',day:'numeric',year:'numeric'}) + '. Purchasing again will extend the boost.</span>';
    } else {
        note.innerHTML = '<span style="color:#22c55e;font-weight:600;">✓ Ready to boost</span>';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
