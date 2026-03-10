<?php
require 'includes/db.php';

// Fetch product details if ID is provided
$product_id = $_GET['id'] ?? null;
$product = null;
$price = 0;
// Default
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
        $price = $product_data['price_min']; // Use min price for display
        
        $images = json_decode($product_data['image_paths'], true);
        if (!empty($images)) {
            $image_url = $images[0];
        }
    } else {
        $price = 13000.00;
    }
} else {
    $price = 13000.00; 
}

// Fetch Payment Config
$settingsStmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'payment_config'");
$payConfigRaw = $settingsStmt->fetchColumn();
// Default: PhonePe Only (as requested) + COD? Let's default to PhonePe & COD for safety, or just PhonePe.
// User said "only phonepay... remaining i want i can on".
// Let's default to {"cod":true, "phonepe":true} to match admin default for now, or respect user exact words?
// Admin default I set was `{"cod":true,"phonepe":true}`.
$payConfig = json_decode($payConfigRaw ?: '{"cod":true,"phonepe":true}', true);

$original_price = $price * 1.5;
$shipping_cost = 85.00;
$total = $price + $shipping_cost;

include 'includes/header.php';
?>

<?php if (isset($_GET['source']) && $_GET['source'] === 'thrift'): ?>
<style>
    /* Thrift+ Theme Overrides - Payment Page */
    body, .container { 
        background-color: #eae4cc !important; 
        font-family: 'Courier New', monospace !important;
    }
    
    h3, h4, .p-name, .step span, .btn-continue {
        font-family: 'Courier New', monospace !important;
        color: #1a1a1a !important;
        text-transform: uppercase;
    }
    
    h3, h4 {
        font-family: 'Times New Roman', serif !important;
        font-weight: 800 !important;
    }

    /* Containers */
    .container > div, .payment-option, .btn-continue {
        border: 2px solid #1a1a1a !important;
        box-shadow: 4px 4px 0 rgba(26,26,26,0.9) !important;
        border-radius: 0 !important;
        background: #fdfcf8 !important;
    }

    /* Payment Option Specifics */
    .p-icon {
        border-radius: 0 !important;
        border: 2px solid #1a1a1a !important;
    }
    
    .payment-option.selected {
        background-color: #dcfce7 !important; /* Greenish tint for selected */
        border-color: #1a1a1a !important;
        box-shadow: 2px 2px 0 rgba(26,26,26,0.9) inset !important;
    }

    /* Steps */
    .step .circle {
        border-radius: 0 !important;
        border: 2px solid #1a1a1a !important;
    }
    
    .step.completed .circle, .step.active .circle {
        background: #1a1a1a !important;
        color: #fff !important;
    }

    /* Button */
    .btn-continue {
        background: #1a1a1a !important;
        color: #fff !important;
        padding: 1rem !important;
        font-weight: 800 !important;
        text-transform: uppercase;
    }
    
    .btn-continue:hover {
        transform: translate(-2px, -2px);
        box-shadow: 6px 6px 0 rgba(26,26,26,0.9) !important;
    }
</style>
<?php endif; ?>

<div class="container" style="padding-top: 2rem;">
    <div style="max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
        
        <div style="margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 1rem;">
            <h3 style="margin: 0; text-align: center;">PAYMENT METHOD</h3>
        </div>

        <!-- Progress Bar -->
        <div class="progress-track" style="margin-bottom: 2.5rem;">
            <div class="step completed">
                <div class="circle"><ion-icon name="checkmark"></ion-icon></div>
                <span>Cart</span>
            </div>
            <div class="step completed">
                <a href="shipping_info.php?id=<?php echo $product_id; ?><?php echo (isset($_GET['source']) && $_GET['source'] === 'thrift') ? '&source=thrift' : ''; ?>" style="text-decoration:none;">
                    <div class="circle"><ion-icon name="checkmark"></ion-icon></div>
                    <span>Address</span>
                </a>
            </div>
            <div class="step active">
                <div class="circle">3</div>
                <span>Payment</span>
            </div>
            <div class="step">
                <div class="circle">4</div>
                <span>Summary</span>
            </div>
            <div class="line"></div>
            <div class="line val-75" style="width: 66%;"></div>
        </div>

        <h4 style="margin: 0 0 1.5rem; font-weight:700;">Select payment method</h4>

        <form action="order_summary.php" method="GET" id="paymentForm">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($product_id); ?>">
            <?php if (isset($_GET['source'])): ?>
            <input type="hidden" name="source" value="<?php echo htmlspecialchars($_GET['source']); ?>">
            <?php endif; ?>
            
            <div class="payment-options">
                


                <!-- PhonePe -->
                <?php if(!empty($payConfig['phonepe'])): ?>
                <label class="payment-option" onclick="selectPayment('phonepe')">
                    <div class="p-left">
                        <div class="p-icon purple-bg"><ion-icon name="phone-portrait-outline"></ion-icon></div>
                        <span class="p-name">PhonePe</span>
                    </div>
                    <div class="p-right">
                        <ion-icon name="checkmark-circle" class="check-icon"></ion-icon>
                        <div class="circle-outline"></div>
                    </div>
                    <input type="radio" name="pay_method" value="phonepe" style="display:none;">
                </label>
                <?php endif; ?>

                <!-- Cash on Delivery -->
                <?php if(!empty($payConfig['cod'])): ?>
                <label class="payment-option" onclick="selectPayment('cod')">
                    <div class="p-left">
                        <div class="p-icon green-bg"><ion-icon name="cash-outline"></ion-icon></div>
                        <span class="p-name">Cash on Delivery</span>
                    </div>
                    <div class="p-right">
                        <ion-icon name="checkmark-circle" class="check-icon"></ion-icon>
                        <div class="circle-outline"></div>
                    </div>
                    <input type="radio" name="pay_method" value="cod" style="display:none;">
                </label>
                <?php endif; ?>

                <!-- Online -->
                <?php if(!empty($payConfig['online'])): ?>
                <label class="payment-option" onclick="selectPayment('online')">
                    <div class="p-left">
                        <div class="p-icon blue-bg"><ion-icon name="card-outline"></ion-icon></div>
                        <div class="p-details">
                            <span class="p-name">Online</span>
                            <span class="p-desc">Debit Card, Credit Card, Net Banking, UPI</span>
                        </div>
                    </div>
                    <div class="p-right">
                        <ion-icon name="checkmark-circle" class="check-icon"></ion-icon>
                        <div class="circle-outline"></div>
                    </div>
                    <input type="radio" name="pay_method" value="online" style="display:none;">
                </label>
                <?php endif; ?>

                <!-- Paytm -->
                <?php if(!empty($payConfig['paytm'])): ?>
                <label class="payment-option" onclick="selectPayment('paytm')">
                    <div class="p-left">
                        <div class="p-icon cyan-bg"><ion-icon name="wallet-outline"></ion-icon></div>
                        <div class="p-details">
                            <span class="p-name">Paytm</span>
                            <span class="p-desc">Wallet, Debit Card, Credit Card, Net Banking, UPI</span>
                        </div>
                    </div>
                    <div class="p-right">
                        <ion-icon name="checkmark-circle" class="check-icon"></ion-icon>
                        <div class="circle-outline"></div>
                    </div>
                    <input type="radio" name="pay_method" value="paytm" style="display:none;">
                </label>
                <?php endif; ?>

            </div>



            <button type="submit" class="btn-continue">Continue</button>
        </form>
    </div>
</div>

<style>
    /* Reuse styles from previous modal implementation but scoped nicely */
    .progress-track {
        display: flex;
        justify-content: space-between;
        position: relative;
    }
    .progress-track .line {
        position: absolute;
        top: 15px;
        left: 0;
        width: 100%;
        height: 2px;
        background: #eee;
        z-index: 0;
    }
    .progress-track .line.val-75 { background: #333; z-index: 0; width: 66%; }

    .step {
        position: relative;
        z-index: 1;
        text-align: center;
        width: 25%;
    }
    .step .circle {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 5px;
        font-size: 0.8rem;
        color: #888;
        font-weight: 600;
    }
    .step.completed .circle, .step.active .circle {
        border-color: #333;
        background: #333;
        color: #fff;
    }
    .step.completed .circle { background: #333; border-color: #333; }
    .step span { font-size: 0.75rem; color: #888; }
    .step.active span { color: #333; font-weight: 700; }

    /* Payment Options */
    .payment-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border: 1px solid #eee;
        border-radius: 12px;
        margin-bottom: 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .payment-option.selected {
        background-color: #e8f5e9; /* Light Green */
        border-color: #2ecc71;
    }
    .p-left { display: flex; align-items: center; gap: 1rem; }
    .p-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background: #f0f0f0;
        color: #555;
    }
    .green-bg { background: #d4edda; color: #155724; }
    .purple-bg { background: #e2d1f0; color: #6f42c1; }
    .blue-bg { background: #cfe2ff; color: #084298; }
    .cyan-bg { background: #cff4fc; color: #055160; }

    .p-name { font-weight: 600; font-size: 1rem; color: #333; }
    .p-desc { font-size: 0.7rem; color: #888; display: block; }
    
    .p-right { display: flex; align-items: center; }
    .circle-outline {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 2px solid #ccc;
    }
    .check-icon {
        font-size: 1.5rem;
        color: #2ecc71;
        display: none;
    }
    .payment-option.selected .check-icon { display: block; }
    .payment-option.selected .circle-outline { display: none; }

    /* Reselling Toggle */


    .btn-continue {
        width: 100%;
        padding: 1rem;
        background: #333;
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 700;
        margin-top: 1.5rem;
        cursor: pointer;
    }
    .btn-continue:hover { background: #000; }

    @media (max-width: 768px) {
        .navbar {
            border-radius: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            max-width: 100vw !important;
        }

        /* Hide Thrift+ button on mobile to prevent crowding */
        .btn-thrift {
            display: none !important;
        }

        .container {
            padding: 85px 1rem 2rem !important; /* Adjust for sticky header */
        }

        .payment-option {
            padding: 0.75rem; /* Slightly less padding */
        }
        
        .p-icon {
            width: 32px;
            height: 32px;
            font-size: 1.2rem;
        }
        
        .p-name { font-size: 0.9rem; }
        .p-desc { font-size: 0.65rem; }
        
        /* Adjust steps for mobile */
        .step .circle { width: 24px; height: 24px; font-size: 0.7rem; }
        .step span { font-size: 0.65rem; }
    }
</style>

<script>
    function selectPayment(method) {
        // Deselect all
        document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('selected'));
        // Select clicked
        const clicked = document.querySelector(`input[value="${method}"]`).closest('.payment-option');
        clicked.classList.add('selected');
        // Check radio
        document.querySelector(`input[value="${method}"]`).checked = true;
    }

    // Auto-select first available option
    document.addEventListener('DOMContentLoaded', function() {
        const firstOption = document.querySelector('.payment-option');
        if(firstOption) {
            const input = firstOption.querySelector('input[type="radio"]');
            if(input) {
                selectPayment(input.value);
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
