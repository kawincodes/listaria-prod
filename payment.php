<?php
// Redirect to Shipping Information Page
$id = $_GET['id'] ?? '';
header("Location: shipping_info.php?id=" . urlencode($id) . (isset($_GET['source']) ? "&source=" . urlencode($_GET['source']) : ""));
exit;
?>

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

        // Check for negotiation price
        if (isset($_SESSION['user_id'])) {
            $stmtNeg = $pdo->prepare("SELECT final_price FROM negotiations WHERE product_id = ? AND buyer_id = ? AND final_price IS NOT NULL");
            $stmtNeg->execute([$product_id, $_SESSION['user_id']]);
            $offer = $stmtNeg->fetch();
            if ($offer) {
                $price = $offer['final_price'];
            }
        }

    } else {
        // Fallback if ID invalid
        $price = 13000.00;
    }
} else {
    // Default mock data if no product selected
    $price = 13000.00; 
}

// Logic for "Original Price" to show strikethrough (Mocking a higher price)
$original_price = $price * 1.5; // 50% higher
$shipping_cost = 85.00;

if (isset($_SESSION['apply_free_shipping']) && $_SESSION['apply_free_shipping'] === true) {
    $shipping_cost = 0;
}

$total = $price + $shipping_cost;

// Fetch User Details if logged in
$user_name = '';
$user_email = '';
$user_address = ''; // Default if not found

if (isset($_SESSION['user_id'])) {
    $stmtUser = $pdo->prepare("SELECT full_name, email, address FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $uData = $stmtUser->fetch();
    if ($uData) {
        $user_name = $uData['full_name'];
        $user_email = $uData['email'];
        $user_address = $uData['address'];
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding-top: 2rem;">
    
    <div class="checkout-grid">
        
        <!-- Left: Shipping Information -->
        <div class="checkout-left">
            <h2 class="checkout-section-title">Shipping Information</h2>

            <form action="#" id="shipping-form">
                <div class="form-group">
                    <input type="text" class="form-input" placeholder="Full Name*" style="background:#fff; height: 50px;" required value="<?php echo htmlspecialchars($user_name); ?>">
                </div>
                
                <div class="form-group">
                    <input type="email" class="form-input" placeholder="Email ID*" style="background:#fff; height: 50px;" required value="<?php echo htmlspecialchars($user_email); ?>">
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-prefix">+91</span>
                        <input type="tel" class="form-input" placeholder="Enter Contact Number" required style="height: 50px;" maxlength="10" pattern="\d{10}" title="Please enter exactly 10 digits" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
                    </div>
                </div>

                <div style="margin-top:2rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                        <label style="font-weight:600;">Address</label>
                    </div>
                    
                    <div class="form-group">
                        <textarea class="form-input" rows="4" placeholder="Enter your delivery address" style="background:#fff; padding-top:10px;" required><?php echo htmlspecialchars($user_address ?? ''); ?></textarea>
                    </div>

                    <div style="margin-top:1.5rem; display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" id="save_addr" checked>
                        <label for="save_addr" style="font-size:0.9rem; color:#333;">Save this address for future orders</label>
                    </div>
                </div>

            </form>
        </div>

        <!-- Right: Order Summary -->
        <div class="checkout-right">
            <div class="summary-box">
                
                <div class="info-box">
                    <ion-icon name="car-outline" style="color:#2ecc71; font-size:1.5rem;"></ion-icon>
                    <div>
                        <div style="font-weight:700; font-size:0.95rem;">Delivery in 3-5 working days</div>
                        <div style="font-size:0.85rem; color:#666;">We will contact you before delivery</div>
                    </div>
                </div>

                <div class="deal-text">
                    🔥 Awesome, that's a great deal!
                </div>

                <div class="item-preview" style="align-items:center; margin-bottom:2rem;">
                    <img src="<?php echo htmlspecialchars($image_url); ?>" class="item-thumb" style="border-radius:12px; width:60px; height:60px;" alt="Product">
                    <div class="item-details" style="display:flex; align-items:center; margin-left:1rem;">
                        <div class="item-title" style="font-size:1rem;"><?php echo htmlspecialchars($title); ?></div>
                    </div>
                </div>

                <div style="border-top:1px solid #eee; padding-top:1.5rem;">
                    <label style="font-size:0.9rem; color:#333;">Have a discount code?</label>
                    <div class="discount-group">
                        <input type="text" id="coupon-code" class="form-input" placeholder="Enter code" style="background:#f9f9f9; border:none;">
                        <button type="button" class="apply-btn" onclick="applyCoupon()">Apply</button>
                    </div>
                </div>

                <div class="summary-divider"></div>

                <div class="summary-row">
                    <span style="color:#666;">Product Price</span>
                    <span>
                        <span style="text-decoration:line-through; color:#999; margin-right:5px;">₹<?php echo number_format($original_price); ?></span>
                        <span style="font-weight:600;">₹<?php echo number_format($price); ?></span>
                    </span>
                </div>
                <div class="summary-row">
                    <span style="color:#666;">Listaria Assured Shipping <ion-icon name="information-circle-outline"></ion-icon></span>
                    <span style="font-weight:600;">₹<?php echo number_format($shipping_cost); ?></span>
                </div>
                
                <div class="summary-total" style="margin-top:1rem;">
                    <span>Total</span>
                    <span>₹<?php echo number_format($total); ?></span>
                </div>

                <div class="guarantee-box">
                    <div class="guarantee-title">
                        <ion-icon name="checkmark-circle" style="color:#2ecc71;"></ion-icon>
                        Listaria Guarantee: Shop with Confidence
                    </div>
                    Avail 100% refund within 3 days of delivery under covered scenarios. <a href="#" style="color:#0984e3; text-decoration:none;">More Details</a>
                </div>

                <?php if ($total > 10000): ?>
                <div class="high-value-disclaimer" style="background:#fff3cd; padding:15px; border:1px solid #ffeeba; border-radius:8px; margin-bottom:1.5rem;">
                    <div style="font-weight:700; color:#856404; margin-bottom:8px; display:flex; align-items:center; gap:5px;">
                        <ion-icon name="document-text-outline"></ion-icon> Consent for High-Value Item
                    </div>
                    <p style="font-size:0.85rem; color:#856404; margin:0 0 10px 0; line-height:1.4;">
                        Listaria provides logistic support but ultimate responsibility lies with the seller. Refunds for high-value goods (>₹10k) are subject to manual review.
                    </p>
                    <div style="display:flex; align-items:start; gap:10px;">
                        <input type="checkbox" id="high_value_consent" style="margin-top:4px; transform:scale(1.2);">
                        <label for="high_value_consent" style="font-size:0.85rem; color:#333; cursor:pointer; font-weight:600;">
                            I agree that I've reviewed the product listing and photos thoroughly.
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <a href="payment_method.php?id=<?php echo htmlspecialchars($product_id); ?>" class="btn-primary" onclick="validateShipping(event)" style="display:block; text-align:center; text-decoration:none;">Proceed to Payment</a>

            </div>
        </div>

    </div>
</div>

<style>
    .discount-group {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    .apply-btn {
        background: #212121;
        color: white;
        border: none;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        white-space: nowrap;
    }
    .apply-btn:hover {
        background: #000;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .apply-btn:active {
        transform: translateY(0);
    }
    /* Restoring helpful styles that might be missing */
    .form-input {
        width: 100%;
        padding: 12px;
        border: 1px solid #eee;
        border-radius: 8px;
        font-size: 0.95rem;
    }
    /* Phone Input Styles */
    .input-group {
        display: flex;
        align-items: center;
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }
    .input-group-prefix {
        background: #f9f9f9;
        color: #555;
        padding: 0 16px;
        font-weight: 600;
        font-size: 0.95rem;
        border-right: 1px solid #eee;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .input-group .form-input {
        border: none;
        border-radius: 0;
        height: 50px;
        padding-left: 16px;
    }
    .input-group:focus-within {
        border-color: #333;
        box-shadow: 0 0 0 2px rgba(0,0,0,0.05);
    }
</style>
<script>
function validateShipping(event) {
    const form = document.getElementById('shipping-form');
    // Check form validity
    if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
        const invalidInput = form.querySelector(':invalid');
        if (invalidInput) {
            invalidInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            invalidInput.focus();
        }
        return;
    }

    // Check High Value Consent
    const consent = document.getElementById('high_value_consent');
    if (consent && !consent.checked) {
        event.preventDefault();
        alert('Please agree to the High-Value Item Consent to proceed.');
        consent.scrollIntoView({ behavior: 'smooth', block: 'center' });
        consent.parentElement.style.animation = 'shake 0.5s'; // Optional visual cue
        return;
    }
}

function applyCoupon() {
    const code = document.getElementById('coupon-code').value;
    if(!code) return;

    fetch('api/validate_coupon.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({code: code})
    })
    .then(res => res.json())
    .then(data => {
        if(data.valid) {
            alert(data.message);
            // Updating UI
            // Find shipping element and total element
            // We need to reload just to be safe or update DOM? 
            // Updating DOM is cleaner but page reload ensures session state consistency if we navigate.
            // But let's just update text for now and rely on session for next page.
            // Wait, this page (payment.php) shows 'Total'. We need to update that.
            
            // Let's assume user stays here. Ideally we should create IDs for price spans.
            // But reloading is easiest to reflect PHP session changes if we modified payment.php to read session.
            location.reload(); 
        } else {
            alert(data.message);
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
