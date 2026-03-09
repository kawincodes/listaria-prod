<?php
require 'includes/db.php';

$product_id = $_GET['id'] ?? null;
$pay_method = $_GET['pay_method'] ?? 'phonepe';

// Logic check: if Online selected, we might want to redirect to a card page instead
// For now, we will assume 'order_summary.php' handles the confirmation for all, 
// OR specific logic for 'online' to show card details.
// Since user asked for "separate page", let's handle the "Place Order" logic here.
// But if card is needed, we should probably have a 'card_entry.php'.
// Let's stick to the flow: Select -> Summary (Place Order). 
// If Online, maybe show card form here? 
// User specific request: "Summary page should open... with place order button" for COD.

$product = null;
$price = 0;
$image_url = 'https://via.placeholder.com/150'; 
$title = 'Product Name';
$brand = 'Brand';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'] ?? null;

if ($product_id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product_data = $stmt->fetch();
    
    if ($product_data) {
        $title = $product_data['title'];
        $brand = $product_data['brand'];
        $price = $product_data['price_min'];
        $images = json_decode($product_data['image_paths'], true);
        if (!empty($images)) $image_url = $images[0];


        // Check for Accepted Negotiation (Offer)
        $negotiated_price = null;
        if ($user_id) {
            $neg_stmt = $pdo->prepare("SELECT final_price FROM negotiations WHERE product_id = ? AND buyer_id = ?");
            $neg_stmt->execute([$product_id, $user_id]);
            $negotiation = $neg_stmt->fetch();
            
            if ($negotiation && !empty($negotiation['final_price'])) {
                $negotiated_price = $negotiation['final_price'];
            }
        }

    } else { $price = 13000.00; }
} else { $price = 13000.00; }

$shipping_cost = 85.00;

if (isset($_SESSION['apply_free_shipping']) && $_SESSION['apply_free_shipping'] === true) {
    $shipping_cost = 0;
}

$discount = 0;
$final_item_price = $price;

if ($negotiated_price !== null) {
    $discount = $price - $negotiated_price;
    $final_item_price = $negotiated_price;
}

$total = $final_item_price + $shipping_cost;

include 'includes/header.php';
?>

<?php if (isset($_GET['source']) && $_GET['source'] === 'thrift'): ?>
<style>
    /* Thrift+ Theme Overrides - Summary Page */
    body, .container { 
        background-color: #eae4cc !important; 
        font-family: 'Courier New', monospace !important;
    }
    
    h2, h3, .step span, .btn-success {
        font-family: 'Courier New', monospace !important;
        color: #1a1a1a !important;
        text-transform: uppercase;
    }
    
    h3, .btn-success, h2 {
        font-family: 'Times New Roman', serif !important;
        font-weight: 800 !important;
    }

    /* Containers */
    .container > div, .btn-success, .item-preview, .summary-row {
        border-radius: 0 !important;
        background: #fdfcf8 !important;
    }
    
    .container > div {
        border: 2px solid #1a1a1a !important;
        box-shadow: 4px 4px 0 rgba(26,26,26,0.9) !important;
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
    .btn-success {
        background: #1a1a1a !important;
        color: #fff !important;
        padding: 1rem !important;
        font-weight: 800 !important;
        border: 2px solid #1a1a1a !important;
        box-shadow: 4px 4px 0 rgba(26,26,26,0.9) !important;
        text-transform: uppercase;
    }
    
    .btn-success:hover {
        transform: translate(-2px, -2px);
        box-shadow: 6px 6px 0 rgba(26,26,26,0.9) !important;
    }
    
    /* Order Success Modal */
    #orderSuccessModal > div {
        border: 3px solid #1a1a1a !important;
        box-shadow: 8px 8px 0 rgba(26,26,26,0.9) !important;
        border-radius: 0 !important;
        background: #eae4cc !important;
    }
    
    #orderSuccessModal h2 {
        color: #1a1a1a !important;
        font-family: 'Times New Roman', serif !important;
    }
    
    #orderSuccessModal .btn-success {
        background: #1a1a1a !important;
        color: #fff !important;
    }
</style>
<?php endif; ?>

<div class="container" style="padding-top: 2rem;">
    <div style="max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
        
        <div style="margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 1rem; display:flex; justify-content:space-between; align-items:center;">
            <a href="payment_method.php?id=<?php echo $product_id; ?><?php echo (isset($_GET['source']) && $_GET['source'] === 'thrift') ? '&source=thrift' : ''; ?>" style="color:#333; font-size:1.5rem;"><ion-icon name="arrow-back"></ion-icon></a>
            <h3 style="margin: 0; flex-grow:1; text-align:center;">ORDER SUMMARY</h3>
            <div style="width:24px;"></div> <!-- Spacer for center alignment -->
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
            <div class="step completed">
                <a href="payment_method.php?id=<?php echo $product_id; ?><?php echo (isset($_GET['source']) && $_GET['source'] === 'thrift') ? '&source=thrift' : ''; ?>" style="text-decoration:none;">
                    <div class="circle"><ion-icon name="checkmark"></ion-icon></div>
                    <span>Payment</span>
                </a>
            </div>
            <div class="step active">
                <div class="circle">4</div>
                <span>Summary</span>
            </div>
            <div class="line"></div>
            <div class="line val-100" style="width: 100%;"></div>
        </div>

        <div class="summary-details">
            <!-- Product -->
            <div class="item-preview" style="display:flex; align-items:center; margin-bottom:1.5rem; padding-bottom:1.5rem; border-bottom:1px solid #eee;">
                <img src="<?php echo htmlspecialchars($image_url); ?>" style="width:80px; height:80px; object-fit:cover; border-radius:8px; background:#f0f0f0;" alt="Product">
                <div class="item-details" style="margin-left:1rem;">
                    <div style="font-weight:700; font-size:1rem; margin-bottom:0.2rem;"><?php echo htmlspecialchars($title); ?></div>
                    <div style="color:#666; font-size:0.85rem;">Brand: <?php echo htmlspecialchars($brand); ?></div>
                    <div style="color:#666; font-size:0.85rem; margin-top:0.2rem;">Method: <?php echo strtoupper($pay_method); ?></div>
                </div>
            </div>

            <!-- Price Breakdown -->
            <div>
                <div class="summary-row">
                    <span style="color:#666;">Price</span>
                    <span>₹<?php echo number_format($price, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span style="color:#666;">Discount</span>
                    <span style="color:#2ecc71;">- ₹<?php echo number_format($discount, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span style="color:#666;">Shipping Fee</span>
                    <span>₹<?php echo number_format($shipping_cost, 2); ?></span>
                </div>
                <div class="summary-row" style="margin-top:1rem; padding-top:1rem; border-top:1px dashed #eee; font-weight:800; font-size:1.2rem; display:flex; justify-content:space-between;">
                    <span>Total Amount</span>
                    <span>₹<?php echo number_format($total, 2); ?></span>
                </div>
            </div>
        </div>

        <form action="place_order.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
            <input type="hidden" name="pay_method" value="<?php echo htmlspecialchars($pay_method); ?>">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($total); ?>">

            <?php if($pay_method == 'cod'): ?>
                <button type="submit" class="btn-success">Place Order</button>

            <?php else: ?>
                <!-- If Online, we'd normally show card form here or have redirected. For simplicity/mockup: -->
                 <div style="margin-top:2rem; padding:1rem; background:#fff8e1; border-radius:8px; color:#f57f17; font-size:0.9rem; text-align:center;">
                    <ion-icon name="card-outline" style="font-size:1.2rem; vertical-align:middle;"></ion-icon>
                    Simulating Card Payment...
                </div>
                <button type="submit" class="btn-success">Pay & Place Order</button>
            <?php endif; ?>
        </form>

    </div>

    <!-- Order Success Modal -->
    <div id="orderSuccessModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:white; width:90%; max-width:400px; padding:2rem; border-radius:16px; text-align:center; animation: popIn 0.3s ease-out;">
            <div style="width:80px; height:80px; background:#dcfce7; color:#27ae60; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem; font-size:3rem;">
                <ion-icon name="checkmark-circle"></ion-icon>
            </div>
            <h2 style="margin:0 0 10px; color:#333;">Order Placed!</h2>
            <p style="color:#666; margin-bottom:2rem;">Your order has been successfully placed via Cash on Delivery.</p>
            <a href="profile.php" class="btn-success" style="display:block; text-decoration:none; line-height:1.5;">Track Order</a>
        </div>
    </div>
</div>

<style>
    @keyframes popIn {
        from { transform: scale(0.8); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
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
    .progress-track .line.val-100 { background: #333; z-index: 0; width: 100%; }

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
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.8rem;
        font-size: 0.95rem;
    }

    .btn-success {
        width: 100%;
        padding: 1.2rem;
        background: linear-gradient(135deg, #2ecc71, #219150);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 10px 20px rgba(46, 204, 113, 0.2);
        margin-top: 2rem;
    }
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 30px rgba(46, 204, 113, 0.4);
    }

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
        
        /* Adjust steps for mobile */
        .step .circle { width: 24px; height: 24px; font-size: 0.7rem; }
        .step span { font-size: 0.65rem; }
        
        h3 { font-size: 1.1rem !important; }
        
        .btn-success {
            padding: 1rem;
            font-size: 1rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="place_order.php"]');
    const payMethod = form.querySelector('input[name="pay_method"]').value;

    if (payMethod === 'cod') {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = form.querySelector('button');
            const originalText = btn.innerText;
            btn.innerText = 'Processing...';
            btn.disabled = true;

            const formData = new FormData(form);
            formData.append('ajax', '1');

            fetch('place_order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Show Modal
                    const modal = document.getElementById('orderSuccessModal');
                    modal.style.display = 'flex';
                } else {
                    alert('Order failed. Please try again.');
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
                btn.innerText = originalText;
                btn.disabled = false;
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
