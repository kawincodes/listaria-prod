<?php
require 'includes/db.php';
require_once __DIR__ . '/includes/session.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=shipping_info.php?id=" . ($_GET['id'] ?? ''));
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_GET['id'] ?? null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    // Clean phone number: remove non-digits
    $phoneRaw = preg_replace('/[^0-9]/', '', $phone);
    
    $address = $_POST['address'] ?? '';
    
    // Server-side validation
    if ($phoneRaw && strlen($phoneRaw) === 10 && $address) {
        $stmt = $pdo->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?");
        if ($stmt->execute([$phoneRaw, $address, $user_id])) {
            // Redirect to Payment Method
            header("Location: payment_method.php?id=" . urlencode($product_id) . (isset($_GET['source']) ? "&source=" . urlencode($_GET['source']) : ""));
            exit;
        } else {
            $error = "Failed to save details.";
        }
    } else {
        $error = "Please enter a valid 10-digit phone number and address.";
    }
}

// Fetch User Details
$stmt = $pdo->prepare("SELECT full_name, email, phone, address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch Product Details
$product = null;
$price = 0;
// Default
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

        // Check for negotiation price
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

// Calculations
$original_price = $price * 1.5;
$shipping_cost = 85.00;

if (isset($_SESSION['apply_free_shipping']) && $_SESSION['apply_free_shipping'] === true) {
    $shipping_cost = 0;
}

$total = $price + $shipping_cost;

include 'includes/header.php';
?>

<?php if (isset($_GET['source']) && $_GET['source'] === 'thrift'): ?>
<style>
    /* Thrift+ Theme Overrides - Shipping Page */
    body, .container { 
        background-color: #eae4cc !important; 
        font-family: 'Courier New', monospace !important;
    }
    
    .checkout-section-title, .item-title, label, .summary-row span, .deal-text, h2 {
        font-family: 'Courier New', monospace !important;
        color: #1a1a1a !important;
    }
    
    .checkout-section-title {
        font-family: 'Times New Roman', serif !important;
        font-weight: 800 !important;
        font-size: 2rem !important;
        text-transform: uppercase;
    }

    /* Containers */
    .summary-box, .form-input, .input-group, .btn-primary, .info-box, .deal-text, .high-value-disclaimer {
        border: 2px solid #1a1a1a !important;
        box-shadow: 4px 4px 0 rgba(26,26,26,0.9) !important;
        border-radius: 0 !important;
        background: #fdfcf8 !important;
    }

    .form-input {
        box-shadow: none !important; /* Inputs don't need drop shadow usually, but let's see */
        border: 2px solid #1a1a1a !important;
    }

    .form-input:focus {
        background: #fff !important;
    }

    .btn-primary {
        background: #1a1a1a !important;
        color: #fff !important;
        text-transform: uppercase;
        font-weight: 800 !important;
        padding: 18px !important;
    }

    .btn-primary:hover {
        transform: translate(-2px, -2px);
        box-shadow: 6px 6px 0 rgba(26,26,26,0.9) !important;
    }

    .summary-box {
        background: #fdfcf8 !important;
    }
    
    .deal-text {
        background: #fdfcf8 !important;
        color: #1a1a1a !important;
    }

    /* Remove default roundedness */
    .item-thumb {
        border-radius: 0 !important;
        border: 2px solid #1a1a1a !important;
    }
    
    .guarantee-box {
        border-radius: 0 !important;
        border: 2px solid #1a1a1a !important;
        background: #dcfce7 !important; /* Keep green but boxy */
    }
</style>
<?php endif; ?>

<div class="container" style="padding-top: 2rem;">
    
    <div class="checkout-grid">
        
        <!-- Left: Shipping Information -->
        <div class="checkout-left">
            <h2 class="checkout-section-title">Shipping Information</h2>

            <?php if(isset($error)): ?>
                <div style="background:#ffebee; color:#c62828; padding:10px; border-radius:8px; margin-bottom:1rem; font-size:0.9rem;">
                    <ion-icon name="alert-circle-outline" style="vertical-align:middle;"></ion-icon> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="shipping-form">
                <div class="form-group">
                    <input type="text" class="form-input" placeholder="Full Name*" style="background:#f9f9f9; height: 50px;" readonly value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>
                
                <div class="form-group">
                    <input type="email" class="form-input" placeholder="Email ID*" style="background:#f9f9f9; height: 50px;" readonly value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-prefix">+91</span>
                        <input type="tel" name="phone" class="form-input" placeholder="Enter Contact Number" required 
                               style="height: 50px;" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                               maxlength="10" pattern="\d{10}" title="Please enter exactly 10 digits" 
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
                    </div>
                </div>

                <div style="margin-top:2rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                        <label style="font-weight:600;">Address</label>
                    </div>
                    
                    <div class="form-group" style="position:relative;">
                        <input type="text" id="addr_search" class="form-input" placeholder="Search Address (e.g. Bangalore)" style="padding-left:12px; margin-bottom:0.5rem;" autocomplete="off">
                        <div id="addr_suggestions" class="suggestions-dropdown" style="display:none;"></div>
                        
                        <textarea name="address" id="final_address" class="form-input" rows="4" placeholder="Selected address will appear here... (You can edit this)" style="background:#fff; padding-top:10px; color:#333;" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <style>
                        .suggestions-dropdown {
                            position: absolute;
                            top: 55px; /* Adjust based on input height */
                            left: 0;
                            width: 100%;
                            background: white;
                            border: 1px solid #ddd;
                            border-radius: 8px;
                            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                            z-index: 1000;
                            max-height: 250px;
                            overflow-y: auto;
                        }
                        .suggestion-item {
                            padding: 12px 16px;
                            cursor: pointer;
                            border-bottom: 1px solid #f9f9f9;
                            display: flex;
                            align-items: center;
                            gap: 10px;
                            font-size: 0.9rem;
                            color: #333;
                            transition: background 0.2s;
                        }
                        .suggestion-item:last-child { border-bottom: none; }
                        .suggestion-item:hover { background: #f1f5f9; }
                        .s-icon { color: #888; font-size: 1.1rem; }
                        .s-text { display: flex; flex-direction: column; }
                        .s-main { font-weight: 500; }
                        .s-sub { font-size: 0.75rem; color: #777; }
                    </style>

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

                            debounceTimer = setTimeout(() => fetchAddress(query), 300); // Reduced to 300ms for speed
                        });

                        function fetchAddress(query) {
                            // Using standard OpenStreetMap Nominatim API - Optimized for India
                            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&limit=5&countrycodes=in`)
                                .then(response => response.json())
                                .then(data => {
                                    resultsBox.innerHTML = '';
                                    if (data.length > 0) {
                                        data.forEach(place => {
                                            const div = document.createElement('div');
                                            div.className = 'suggestion-item';
                                            // Format: Display Name
                                            // We can split it for better layout if needed, but display_name is usually good
                                            const parts = place.display_name.split(',');
                                            const mainText = parts[0];
                                            const subText = parts.slice(1).join(',').trim();

                                            div.innerHTML = `
                                                <ion-icon name="location-outline" class="s-icon"></ion-icon>
                                                <div class="s-text">
                                                    <span class="s-main">${mainText}</span>
                                                    <span class="s-sub">${subText}</span>
                                                </div>
                                            `;
                                            div.onclick = () => {
                                                finalBox.value = place.display_name;
                                                searchInput.value = ''; // Clear search or keep it? User might want to search again. Let's clear to show it's "selected".
                                                // Or maybe keep mainText in search input?
                                                // Let's populate textarea and maybe clear search to avoid confusion.
                                                // Actually, standard behavior:
                                                // searchInput.value = mainText; // Optional
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

                        // Close dropdown when clicking outside
                        document.addEventListener('click', function(e) {
                            if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
                                resultsBox.style.display = 'none';
                            }
                        });
                    </script>

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
                    Avail 100% refund within 3 days of delivery under covered scenarios. <a href="refund.php" style="color:#0984e3; text-decoration:none;" target="_blank">More Details</a>
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

                <button type="submit" form="shipping-form" class="btn-primary" onclick="validateShipping(event)" style="display:block; text-align:center; text-decoration:none; width:100%;">Proceed to Payment</button>

            </div>
        </div>

    </div>
</div>

<style>
    .checkout-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem; margin-bottom: 3rem; }
    .checkout-left { }
    .checkout-right { }

    .checkout-section-title { font-size: 1.5rem; margin-bottom: 2rem; }
    
    /* Form Styles */
    .form-group { margin-bottom: 1.5rem; }
    .form-input { 
        width: 100%; 
        padding: 12px; 
        border: 1px solid #eee; 
        border-radius: 8px; 
        font-size: 0.95rem; 
        font-family: inherit;
        transition: border-color 0.2s;
    }
    .form-input:focus { border-color: #333; outline: none; }
    
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

    /* Summary Box Styles */
    .summary-box {
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        position: sticky;
        top: 100px;
    }

    .info-box {
        display: flex;
        gap: 12px;
        margin-bottom: 1.5rem;
        align-items: flex-start;
    }

    .deal-text {
        font-weight: 700;
        color: #e67e22;
        margin-bottom: 1.5rem;
        background: #fff8e1;
        padding: 8px 12px;
        border-radius: 8px;
        display: inline-block;
        font-size: 0.9rem;
    }

    .item-preview { display: flex; align-items: center; margin-bottom: 2rem; }
    .item-thumb { object-fit: cover; }
    .item-details { flex: 1; }
    .item-title { font-weight: 600; line-height: 1.4; color: #333; }

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
        white-space: nowrap;
    }

    .summary-divider { height: 1px; background: #eee; margin: 1.5rem 0; }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.8rem;
        font-size: 0.95rem;
    }
    
    .summary-total {
        display: flex;
        justify-content: space-between;
        font-weight: 800;
        font-size: 1.2rem;
        padding-top: 1rem;
        border-top: 2px dashed #eee;
    }

    .guarantee-box {
        margin: 1.5rem 0;
        padding: 1rem;
        background: #f0fdf4;
        border: 1px solid #dcfce7;
        border-radius: 8px;
        font-size: 0.85rem;
        color: #166534;
    }
    .guarantee-title {
        font-weight: 700;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .btn-primary {
        padding: 16px;
        background: #333;
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        font-size: 1.1rem;
        transition: background 0.2s;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .btn-primary:hover { background: #000; transform: translateY(-2px); }

    @media (max-width: 900px) {
        .checkout-grid { grid-template-columns: 1fr; }
        .summary-box { position: static; }
        .checkout-left { margin-bottom: 2rem; }
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
        
        .checkout-section-title {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
        }

        /* Fix suggestions dropdown width on mobile */
        .suggestions-dropdown {
            width: 100% !important;
            left: 0 !important;
        }
    }
</style>

<script>
function validateShipping(event) {
    const form = document.getElementById('shipping-form');
    // Check form validity
    if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity(); // Shows native browser tips
        return;
    }

    // Check High Value Consent if present
    const consent = document.getElementById('high_value_consent');
    if (consent && !consent.checked) {
        event.preventDefault();
        alert('Please agree to the High-Value Item Consent to proceed.');
        consent.scrollIntoView({ behavior: 'smooth', block: 'center' });
        consent.parentElement.style.animation = 'shake 0.5s'; 
    }
}

function applyCoupon() {
    const code = document.getElementById('coupon-code').value;
    if(!code) return;
    
    // Mock coupon logic
    if(code.toLowerCase() === 'listaria_new') {
        alert("Coupon Applied! (Mock)");
    } else {
        alert("Invalid Coupon");
    }
}
</script>

<?php include 'includes/footer.php'; ?>
