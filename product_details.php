<?php
require 'includes/db.php';
session_start();

$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare("SELECT p.*, u.full_name as seller_name FROM products p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found");
}

// Increment Views (if not the seller viewing their own product)
if (!isset($_SESSION['user_id']) || $product['user_id'] != $_SESSION['user_id']) {
    $stmt = $pdo->prepare("UPDATE products SET views = COALESCE(views, 0) + 1 WHERE id = ?");
    $stmt->execute([$id]);
    $product['views'] = ($product['views'] ?? 0) + 1; // Update for display
}

$negotiation = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM negotiations WHERE product_id = ? AND buyer_id = ?");
    $stmt->execute([$id, $user_id]);
    $negotiation = $stmt->fetch();
}

$images = json_decode($product['image_paths']);
$main_image = $images[0] ?? 'https://via.placeholder.com/600';

include 'includes/header.php';
?>

<?php if (isset($_GET['source']) && $_GET['source'] === 'thrift'): ?>
<style style="display:none" id="thrift-theme-styles">
    /* Thrift+ Theme Overrides - Retro/Vintage Style */
    body, .product-details-page { 
        background-color: #eae4cc !important; 
        font-family: 'Courier New', monospace !important;
    }
    
    /* Typography */
    h1, h2, h3, .product-title, .current-price {
        font-family: 'Times New Roman', serif !important;
        color: #1a1a1a !important;
    }

    .product-details-container {
        font-family: 'Courier New', monospace !important;
    }

    /* Containers - Retro Boxy Look */
    .product-info-section, 
    .main-image-wrapper, 
    .condition-verified-section, 
    .feature-item, 
    .guarantee-banner, 
    .green-future-section {
        border: 3px solid #1a1a1a !important;
        box-shadow: 6px 6px 0px rgba(26,26,26,0.9) !important;
        border-radius: 0 !important;
        background: #fdfcf8 !important;
        transition: none !important; /* Remove soft transitions */
    }

    /* Buttons */
    .btn-buy-now, 
    .btn-make-offer, 
    .need-help-btn, 
    .icon-btn {
        border-radius: 0 !important;
        border: 2px solid #1a1a1a !important;
        box-shadow: 4px 4px 0 rgba(26,26,26,0.2) !important;
        font-family: 'Courier New', monospace !important;
        text-transform: uppercase;
        font-weight: 800 !important;
    }

    .btn-buy-now {
        background: #6B21A8 !important;
        color: #fff !important;
        padding: 15px 20px !important;
    }

    .btn-make-offer {
        background: #fdfcf8 !important;
        color: #1a1a1a !important;
    }

    .btn-buy-now:hover, .btn-make-offer:hover {
        transform: translate(-2px, -2px);
        box-shadow: 6px 6px 0 rgba(26,26,26,0.9) !important;
    }

    /* Condition Badge */
    .condition-badge {
        border-radius: 0 !important;
        border: 2px solid #1a1a1a !important;
        box-shadow: 2px 2px 0 rgba(0,0,0,0.2) !important;
        font-family: 'Courier New', monospace !important;
        top: 0 !important;
        left: 0 !important;
        margin: 10px !important;
        transform: rotate(-2deg);
    }
    
    .condition-badge.brand-new, .condition-badge.lightly-used {
         background: #ef4444 !important;
         color: white !important;
    }

    /* Remove default shadows */
    .main-image-wrapper, .product-info-section {
        box-shadow: 6px 6px 0px #1a1a1a !important;
    }
    
    /* Table borders */
    .table-row {
        border-bottom: 1px dashed #ccc !important;
    }
</style>
<?php else: ?>
<style id="clean-theme-styles">
    /* Clean Modern Theme (Default) */
    body, .product-details-page { 
        background-color: #f8fafc !important; 
        font-family: 'Inter', sans-serif !important;
    }
    .product-title, .current-price {
        font-family: 'Inter', sans-serif !important;
        color: #1a1a1a !important;
    }
    .product-info-section, .main-image-wrapper {
        border: 1px solid #f1f5f9 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04) !important;
        border-radius: 16px !important;
    }
</style>
<?php endif; ?>

<div class="product-details-page">
    <div class="product-details-container">
        
        <!-- Left: Gallery -->
        <div class="product-gallery-section">
            <div class="main-image-wrapper" onclick="openZoom(document.getElementById('mainImage').src)" style="cursor:zoom-in;">
                <span class="condition-badge <?php 
                    if($product['condition_tag'] == 'Brand New') echo 'brand-new';
                    elseif($product['condition_tag'] == 'Lightly Used') echo 'lightly-used';
                    else echo 'regularly-used';
                ?>"><?php echo htmlspecialchars($product['condition_tag']); ?></span>
                <img src="<?php echo htmlspecialchars($main_image); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" id="mainImage" class="main-product-image">
                <div style="position:absolute; bottom:10px; right:10px; background:rgba(0,0,0,0.45); color:white; border-radius:50%; width:34px; height:34px; display:flex; align-items:center; justify-content:center; pointer-events:none;">
                    <ion-icon name="expand-outline" style="font-size:1.1rem;"></ion-icon>
                </div>
            </div>
            
            <?php if (count($images) > 1): ?>
            <div class="thumbnail-gallery">
                <?php foreach ($images as $index => $img): ?>
                    <img src="<?php echo htmlspecialchars($img); ?>" class="thumbnail-img <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeImage(this.src, this)" alt="Thumbnail" loading="lazy">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Product Info -->
        <div class="product-info-section">
            <div class="product-header">
                <div>
                    <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                    <div style="font-size: 0.85rem; color: #6B21A8; background: #f3e8ff; padding: 4px 10px; border-radius: 20px; margin-top: 10px; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #e9d5ff; font-weight: 500;">
                        <ion-icon name="eye" style="font-size: 1.1rem;"></ion-icon>
                        <span><strong style="font-weight: 700;"><?php echo number_format($product['views'] ?? 0); ?></strong> people viewed this product</span>
                    </div>
                </div>
                <div class="product-actions-icons">
                    <button class="icon-btn" title="Save" id="wishlistBtn"><ion-icon name="heart-outline"></ion-icon></button>
                    <button class="icon-btn" title="Share" id="shareBtn"><ion-icon name="share-social-outline"></ion-icon></button>
                </div>
            </div>

            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>

            <div class="price-section">
                <?php if (isset($negotiation['final_price']) && $negotiation['final_price'] > 0): ?>
                    <div>
                        <span class="current-price">₹<?php echo number_format($negotiation['final_price'], 0); ?></span>
                        <div style="font-size: 0.85rem; color: var(--tag-new); font-weight: 600;">
                            <ion-icon name="checkmark-circle-outline" style="vertical-align: text-bottom;"></ion-icon>
                            Negotiated Price
                        </div>
                    </div>
                    <?php if ($product['price_min'] > $negotiation['final_price']): ?>
                         <span class="original-price" style="margin-left: 10px; color: var(--secondary-text);">₹<?php echo number_format($product['price_min'], 0); ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="current-price">₹<?php echo number_format($product['price_min'], 0); ?></span>
                    <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price_min']): ?>
                        <span class="original-price" style="color: var(--secondary-text);">₹<?php echo number_format($product['original_price'], 0); ?></span>
                        <span class="discount-badge"><?php echo round((1 - $product['price_min'] / $product['original_price']) * 100); ?>% OFF</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php if (isset($product['status']) && $product['status'] === 'sold'): ?>
                    <button class="btn-sold-out" disabled>Sold Out</button>
                <?php else: ?>
                    <a href="payment.php?id=<?php echo $product['id']; ?><?php echo (isset($_GET['source']) && $_GET['source'] === 'thrift') ? '&source=thrift' : ''; ?>" class="btn-buy-now">
                        <ion-icon name="bag-handle-outline"></ion-icon> Buy Now
                    </a>
                    <?php if ($user_id): ?>
                        <button class="btn-make-offer" onclick="openChat()">
                            <ion-icon name="add-outline"></ion-icon> Make an Offer
                        </button>
                    <?php else: ?>
                        <a href="login.php" class="btn-make-offer">
                            <ion-icon name="add-outline"></ion-icon> Make an Offer
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Condition Verified -->
            <div class="condition-verified-section">
                <div class="condition-header">
                    <ion-icon name="checkmark-circle" class="verified-icon"></ion-icon>
                    <span>Condition Verified</span>
                    <span class="condition-tag <?php 
                        if($product['condition_tag'] == 'Brand New') echo 'tag-new';
                        elseif($product['condition_tag'] == 'Lightly Used') echo 'tag-light';
                        else echo 'tag-regular';
                    ?>"><?php echo htmlspecialchars($product['condition_tag']); ?></span>
                </div>
                <p class="quality-check"><ion-icon name="shield-checkmark-outline"></ion-icon> Listaria Assured - Quality Check</p>
                
                <div class="condition-details">

                    <div class="detail-row">
                        <span class="detail-label">Visual Condition</span>
                        <span class="detail-value">Appears in good condition</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Functional Condition</span>
                        <span class="detail-value">Fully Functional</span>
                    </div>
                </div>
            </div>

            <!-- Feature Icons -->
            <div class="feature-icons">
                <div class="feature-item">
                    <div class="feature-icon-box">
                        <ion-icon name="time-outline"></ion-icon>
                    </div>
                    <span class="feature-title">Fast</span>
                    <span class="feature-subtitle">Shipping</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon-box">
                        <ion-icon name="home-outline"></ion-icon>
                    </div>
                    <span class="feature-title">Doorstep</span>
                    <span class="feature-subtitle">Delivery</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon-box">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                    </div>
                    <span class="feature-title">Secure</span>
                    <span class="feature-subtitle">Transactions</span>
                </div>
            </div>

            <!-- Need Help -->
            <a href="help_support.php" class="need-help-btn" style="text-decoration:none;">
                Need Help? Ask Listaria <ion-icon name="chatbubble-ellipses-outline"></ion-icon>
            </a>

            <!-- Product Details Table -->
            <div class="product-details-table">
                <h3>Product Details</h3>
                <div class="details-table">
                    <div class="table-row">
                        <span class="table-label">Brand</span>
                        <span class="table-value"><?php echo htmlspecialchars($product['brand'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="table-row">
                        <span class="table-label">Category</span>
                        <span class="table-value"><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="table-row">
                        <span class="table-label">Condition</span>
                        <span class="table-value"><?php echo htmlspecialchars($product['condition_tag'] ?? 'N/A'); ?></span>
                    </div>
                    <?php if (!empty($product['location'])): ?>
                    <div class="table-row">
                        <span class="table-label">Location</span>
                        <span class="table-value"><?php echo htmlspecialchars($product['location']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="table-row">
                        <span class="table-label">Seller</span>
                        <span class="table-value" style="display:flex; align-items:center; gap:5px;">
                            <?php echo htmlspecialchars($product['seller_name']); ?>
                            <?php 
                            // Fetch seller verification status
                            $sellerStmt = $pdo->prepare("SELECT is_verified_vendor FROM users WHERE id = ?");
                            $sellerStmt->execute([$product['user_id']]);
                            $seller = $sellerStmt->fetch();
                            if ($seller && $seller['is_verified_vendor']): 
                            ?>
                                <span style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 12px; font-weight: bold; display: inline-flex; align-items: center; gap: 3px; box-shadow: 0 2px 4px rgba(16,185,129,0.3);"><ion-icon name="checkmark-done-outline"></ion-icon> Verified by Listaria</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Guarantee Banner -->
            <div class="guarantee-banner" style="background: var(--hover-bg);">
                <ion-icon name="checkmark-circle" style="color: var(--brand-color);"></ion-icon>
                <div>
                    <strong style="color: var(--primary-text);">Listaria Guarantee: Shop with Confidence</strong>
                    <p style="color: var(--secondary-text);">Claim full refund under policy</p>
                </div>
            </div>

            <a href="terms.php" class="terms-link"><ion-icon name="document-text-outline"></ion-icon> View terms and conditions</a>
        </div>
    </div>
</div>

<!-- Chat Modal -->
<div id="chatModal" class="chat-modal">
    <div class="chat-content">
        <div class="chat-header">
            <h3>Negotiate Price</h3>
            <span class="close-chat" onclick="closeChat()">&times;</span>
        </div>
        <div class="chat-messages" id="chatMessages"></div>
        <div class="chat-input-area">
            <input type="text" id="chatInput" placeholder="Make an offer..." />
            <button onclick="sendMessage()" class="btn-send"><ion-icon name="send"></ion-icon></button>
        </div>
    </div>
</div>

<!-- Mobile Sticky Footer -->
<div class="mobile-sticky-footer">
    <?php if (isset($product['status']) && $product['status'] === 'sold'): ?>
        <button class="btn-sold-out" disabled style="width:100%;">Sold Out</button>
    <?php else: ?>
        <div class="footer-actions-grid">
            <a href="payment.php?id=<?php echo $product['id']; ?><?php echo (isset($_GET['source']) && $_GET['source'] === 'thrift') ? '&source=thrift' : ''; ?>" class="btn-buy-now">
                <ion-icon name="bag-handle-outline"></ion-icon> Buy Now
            </a>
            <?php if ($user_id): ?>
                <button class="btn-make-offer" onclick="openChat()">
                    <ion-icon name="add-outline"></ion-icon> Make Offer
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Product Details Page Styles */
.product-details-page {
    background: var(--bg-color);
    min-height: 100vh;
    padding: 1.5rem;
    padding-bottom: 100px;
}

.product-details-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: start;
}

/* Gallery Section */
.product-gallery-section {
    position: sticky;
    top: 80px;
}

.main-image-wrapper {
    position: relative;
    background: var(--white);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.main-product-image {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
    display: block;
}

.condition-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 10;
}

.condition-badge.brand-new { background: #ede9fe; color: #6B21A8; }
.condition-badge.lightly-used { background: #ede9fe; color: #6B21A8; }
.condition-badge.regularly-used { background: #ede9fe; color: #6B21A8; }

/* Thumbnail Gallery */

.thumbnail-gallery {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
}

.thumbnail-img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 10px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s;
}

.thumbnail-img:hover,
.thumbnail-img.active {
    border-color: var(--brand-color);
}

/* Product Info Section */
.product-info-section {
    background: var(--surface-color);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.product-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.product-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-text);
    line-height: 1.3;
    margin: 0;
    white-space: normal; /* Override global nowrap */
    overflow: visible;
    text-overflow: clip;
    word-wrap: break-word; /* Ensure long words break */
}

.product-actions-icons {
    display: flex;
    gap: 0.5rem;
}

.icon-btn {
    width: 40px;
    height: 40px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--surface-color);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--primary-text);
}

.icon-btn:hover {
    border-color: var(--brand-color);
    color: var(--brand-color);
}

.icon-btn ion-icon {
    font-size: 1.2rem;
}

.product-description {
    color: var(--secondary-text);
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.price-section {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}

.current-price {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--primary-text);
}

.original-price {
    font-size: 1rem;
    color: var(--secondary-text);
    text-decoration: line-through;
}

.discount-badge {
    background: var(--tag-new);
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Action Buttons */
.action-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.btn-buy-now {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    background: #6B21A8;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-buy-now:hover {
    background: #581c87;
}

.btn-make-offer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    background: #1a1a1a;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

[data-theme="dark"] .btn-make-offer {
    background: #e5e5e5;
    color: #1a1a1a;
}

.btn-make-offer:hover {
    background: #000;
}

.btn-sold-out {
    padding: 14px 20px;
    background: var(--border-color);
    color: var(--secondary-text);
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: not-allowed;
    grid-column: 1 / -1;
}

/* Condition Verified */
.condition-verified-section {
    background: var(--hover-bg);
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
}

.condition-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    color: var(--primary-text);
}

.verified-icon {
    color: var(--brand-color);
    font-size: 1.25rem;
}

.condition-header span:first-of-type {
    font-weight: 600;
}

.condition-tag {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-left: auto;
}

.tag-new { background: #ede9fe; color: #6B21A8; }
.tag-light { background: #ede9fe; color: #6B21A8; }
.tag-regular { background: #ede9fe; color: #6B21A8; }

.quality-check {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--brand-color);
    font-size: 0.8rem;
    margin-bottom: 1rem;
}

.condition-details {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
}

.detail-label {
    color: var(--secondary-text);
    font-size: 0.9rem;
}

.detail-value {
    color: var(--primary-text);
    font-size: 0.9rem;
}

/* Feature Icons */
.feature-icons {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}

.feature-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1rem;
    background: var(--hover-bg);
    border-radius: 12px;
}

.feature-icon-box {
    width: 48px;
    height: 48px;
    background: var(--surface-color);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.5rem;
    border: 1px solid var(--border-color);
}

.feature-icon-box ion-icon {
    font-size: 1.5rem;
    color: var(--primary-text);
}

.feature-title {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--primary-text);
}

.feature-subtitle {
    font-size: 0.75rem;
    color: var(--secondary-text);
}

/* Need Help Button */
.need-help-btn {
    width: 100%;
    padding: 14px;
    background: var(--surface-color);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--primary-text);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 1.5rem;
    transition: all 0.2s;
}

.need-help-btn:hover {
    border-color: var(--brand-color);
    color: var(--brand-color);
}

/* Product Details Table */
.product-details-table {
    margin-bottom: 1.5rem;
}

.product-details-table h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--primary-text);
}

.details-table {
    display: flex;
    flex-direction: column;
}

.table-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid var(--border-light);
}

.table-row:last-child {
    border-bottom: none;
}

.table-label {
    color: var(--secondary-text);
    font-size: 0.9rem;
}

.table-value {
    color: var(--primary-text);
    font-size: 0.9rem;
    font-weight: 500;
}

/* Guarantee Banner */
.guarantee-banner {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 1rem;
    background: linear-gradient(135deg, #f3e8ff 0%, #ede9fe 100%);
    border-radius: 12px;
    margin-bottom: 1rem;
}

.guarantee-banner ion-icon {
    font-size: 1.5rem;
    color: #6B21A8;
    flex-shrink: 0;
}

.guarantee-banner strong {
    color: #581c87;
    display: block;
    font-size: 0.9rem;
}

.guarantee-banner p {
    color: #6B21A8;
    font-size: 0.8rem;
    margin: 0;
}

/* Green Future */
.green-future-section {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 1rem;
    background: var(--hover-bg);
    border-radius: 12px;
    margin-bottom: 1rem;
}

.leaf-icon {
    font-size: 1.5rem;
    color: #6B21A8;
    flex-shrink: 0;
}

.green-future-section strong {
    display: block;
    margin-bottom: 4px;
    font-size: 0.9rem;
    color: var(--primary-text);
}

.green-future-section p {
    color: var(--secondary-text);
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.5;
}

.green-future-content {
    flex: 1;
}

.terms-link {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #6B21A8;
    font-size: 0.9rem;
    text-decoration: none;
}

.terms-link:hover {
    text-decoration: underline;
}

/* Mobile Footer */
.mobile-sticky-footer {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    padding: 1rem;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    z-index: 100;
}

.footer-actions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

/* Chat Modal */
.chat-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
}

.chat-content {
    background-color: white;
    width: 90%;
    max-width: 400px;
    height: 500px;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chat-header {
    background: #f8f9fa;
    padding: 1rem;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-messages {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    background: #fff;
}

.chat-input-area {
    padding: 1rem;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
}

.chat-input-area input {
    flex: 1;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 25px;
    outline: none;
    font-size: 0.9rem;
}

.chat-input-area input:focus {
    border-color: #6B21A8;
}

.btn-send {
    background: #6B21A8;
    color: white;
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
}

.btn-send:hover {
    background: #581c87;
}

.close-chat {
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
}

.message-bubble {
    max-width: 80%;
    margin-bottom: 10px;
    padding: 10px 14px;
    border-radius: 18px;
    font-size: 0.9rem;
    line-height: 1.4;
}

.msg-me {
    background: #6B21A8;
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}

.msg-other {
    background: #f0f0f0;
    color: #333;
    margin-right: auto;
    border-bottom-left-radius: 4px;
}

/* Responsive */
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

    .product-details-page {
        padding: 60px 1rem 120px 1rem; /* Reduced from 85px to 60px to remove gap */
        overflow-x: hidden;
    }
    
    .product-details-container {
        grid-template-columns: 100% !important;
        gap: 1.5rem;
        display: flex;
        flex-direction: column;
    }

    .product-gallery-section {
        width: 100%;
        position: static; /* CRITICAL: Prevent image from sticking and covering text */
    }

    .product-info-section {
        width: 100%;
        padding: 1rem;
    }
    
    .main-product-image {
        aspect-ratio: 1; 
        max-height: 50vh;
        width: 100%;
        object-fit: contain;
        background: #f8f9fa;
        border-radius: 8px; /* Slight polish */
    }

    .mobile-sticky-footer {
        display: block;
        position: fixed !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        background: #fff !important;
        padding: 1rem !important;
        padding-bottom: max(1rem, env(safe-area-inset-bottom)) !important;
        z-index: 9999 !important;
        border-radius: 0 !important;
        margin: 0 !important;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1) !important;
        border-top: 1px solid #e5e5e5 !important;
    }

    /* Fix Feature Icons Alignment (Three Boxes - Fit in Row) */
    .feature-icons {
        grid-template-columns: repeat(3, 1fr) !important; /* Keep 3 in a row */
        gap: 0.5rem !important; /* Reduce gap */
    }

    .feature-item {
        display: flex !important;
        flex-direction: column !important; /* Stack icon/text vertically */
        align-items: center !important;
        text-align: center !important;
        padding: 0.5rem !important; /* Reduce padding to fit */
        min-height: 100%; /* Ensure equal height */
    }
    
    .feature-icon-box {
        width: 36px !important; /* Smaller icon box */
        height: 36px !important;
        margin-bottom: 0.5rem !important;
    }
    
    .feature-icon-box ion-icon {
        font-size: 1.2rem !important;
    }
    
    .feature-title { 
        font-size: 0.75rem !important; 
        line-height: 1.2;
    }
    .feature-subtitle { 
        font-size: 0.65rem !important;
        display: none !important; /* Hide subtitle to save space if needed, or keep? Let's hide for cleaner look on tight mobile, OR keep very small. User said "in that style", usually implies seeing everything. Let's keep it but very small. Actually, looking at the screenshot, removing subtitle might break the "style". Let's simply reduce size. */
    }
    /* Let's actually show subtitle but small */
    .feature-subtitle {
        display: block !important;
        font-size: 0.6rem !important;
    }
    
    .action-buttons {
        display: none;
    }
    
    .product-title {
        font-size: 1.35rem; /* Slightly larger for readability */
        margin-top: 0.5rem;
        word-wrap: break-word; /* Prevent overflow */
    }
    
    .current-price {
        font-size: 1.5rem;
    }

    /* Ensure site footer is visible above sticky buttons */
    .site-footer {
        padding-bottom: 100px !important;
    }
}
</style>

<script>
let negotiationId = null;
const productId = <?php echo $product['id']; ?>;
const sellerId = <?php echo $product['user_id']; ?>;
const currentUserId = <?php echo $user_id; ?>;

function changeImage(src, el) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumbnail-img').forEach(t => t.classList.remove('active'));
    if (el) el.classList.add('active');
}

async function openChat() {
    if (!currentUserId) {
        window.location.href = 'login.php';
        return;
    }
    
    const modal = document.getElementById('chatModal');
    const messagesDiv = document.getElementById('chatMessages');
    modal.style.display = 'flex';
    
    // Show Loading
    messagesDiv.innerHTML = '<div style="padding:20px; text-align:center; color:#666;">Starting secure chat...<br><small>Connecting...</small></div>';
    
    const formData = new FormData();
    formData.append('action', 'start_negotiation');
    formData.append('product_id', productId);
    formData.append('seller_id', sellerId);
    
    try {
        console.log("Starting negotiation...");
        const res = await fetch('api/chat.php', { method: 'POST', body: formData });
        const text = await res.clone().text();
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            messagesDiv.innerHTML = '<div style="padding:20px; text-align:center; color:red;"><strong>Connection Error</strong><br>Server returned invalid data.<br><br>Technical Details:<br>' + text.substring(0, 150) + '...</div>';
            return;
        }
        
        if (data.success) {
            negotiationId = data.negotiation_id;
            loadMessages(); // This clears the loading message
        } else {
            messagesDiv.innerHTML = '<div style="padding:20px; text-align:center; color:red;"><strong>Failed to Start Chat</strong><br>' + (data.message || 'Unknown error') + '</div>';
        }
    } catch (err) {
        console.error(err);
        messagesDiv.innerHTML = '<div style="padding:20px; text-align:center; color:red;"><strong>Network Error</strong><br>' + err.message + '</div>';
    }
}

function closeChat() {
    document.getElementById('chatModal').style.display = 'none';
    // Reset content so it's clean for next open
    document.getElementById('chatMessages').innerHTML = '';
}

async function loadMessages() {
    if (!negotiationId) return;
    
    const formData = new FormData();
    formData.append('action', 'get_messages');
    formData.append('negotiation_id', negotiationId);
    
    const res = await fetch('api/chat.php', { method: 'POST', body: formData });
    const data = await res.json();
    
    const container = document.getElementById('chatMessages');
    container.innerHTML = '';
    
    if (data.success) {
        data.messages.forEach(msg => {
            const el = document.createElement('div');
            el.className = 'message-bubble ' + (msg.sender_id == currentUserId ? 'msg-me' : 'msg-other');
            el.innerText = msg.message;
            container.appendChild(el);
        });
        container.scrollTop = container.scrollHeight;
    }
}

async function sendMessage() {
    console.log("Sending message...");
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if (!msg) return;
    
    if (!negotiationId) {
        alert("Error: No active negotiation found. Please refresh the page and try again.");
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('negotiation_id', negotiationId);
    formData.append('message', msg);
    
    try {
        const res = await fetch('api/chat.php', { method: 'POST', body: formData });
        
        // Clone response to read text if json fails
        const text = await res.clone().text(); 
        console.log("Server Response:", text);

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            alert("Server Error: " + text.substring(0, 100)); // Show preview of error
            return;
        }
        
        if (data.success) {
            input.value = '';
            loadMessages();
        } else {
            alert("Failed to send: " + (data.message || 'Unknown Error'));
        }
    } catch (err) {
        console.error(err);
        alert("Network Error: " + err.message);
    }
}

setInterval(() => {
    if (document.getElementById('chatModal').style.display === 'flex') {
        loadMessages();
    }
}, 3000);
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Share Button Logic
    const shareBtn = document.getElementById('shareBtn');
    if (shareBtn) {
        shareBtn.addEventListener('click', async () => {
             const shareData = {
                title: '<?php echo addslashes($product['title']); ?>',
                text: 'Check out this item on Listaria!',
                url: window.location.href
            };

            if (navigator.share) {
                try {
                    await navigator.share(shareData);
                } catch (err) {
                    console.error('Error sharing:', err);
                }
            } else {
                try {
                    await navigator.clipboard.writeText(window.location.href);
                    alert('Link copied to clipboard!');
                } catch (err) {
                    alert('Failed to copy link.');
                }
            }
        });
    }

    // Wishlist Button Logic
    const wishlistBtn = document.getElementById('wishlistBtn');
    const prodId = <?php echo $product['id']; ?>;
    const isUserLoggedIn = <?php echo $user_id ? 'true' : 'false'; ?>;

    if (wishlistBtn && isUserLoggedIn) {
        // Check initial state
        fetch('api/wishlist.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=check&product_id=${prodId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.in_wishlist) {
                wishlistBtn.innerHTML = '<ion-icon name="heart" style="color:red;"></ion-icon>';
            }
        });

        // Toggle on click
        wishlistBtn.addEventListener('click', () => {
             fetch('api/wishlist.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=toggle&product_id=${prodId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.status === 'added') {
                        wishlistBtn.innerHTML = '<ion-icon name="heart" style="color:red;"></ion-icon>';
                    } else {
                        wishlistBtn.innerHTML = '<ion-icon name="heart-outline"></ion-icon>';
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    } else if (wishlistBtn && !isUserLoggedIn) {
        wishlistBtn.addEventListener('click', () => {
            window.location.href = 'login.php';
        });
    }
});
</script>

<!-- Image Zoom Lightbox -->
<div id="zoomLightbox" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:99999; flex-direction:column; align-items:center; justify-content:center; touch-action:none;">
    <!-- Top Bar -->
    <div style="position:absolute; top:0; left:0; right:0; display:flex; justify-content:space-between; align-items:center; padding:12px 16px; z-index:2; background:linear-gradient(rgba(0,0,0,0.5),transparent);">
        <div id="zoomLevel" style="color:white; font-size:0.85rem; font-weight:600; background:rgba(255,255,255,0.15); padding:4px 12px; border-radius:20px;">100%</div>
        <div style="display:flex; gap:10px; align-items:center;">
            <button onclick="adjustZoom(0.5)" style="background:rgba(255,255,255,0.15); border:none; color:white; width:36px; height:36px; border-radius:50%; font-size:1.3rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">−</button>
            <button onclick="adjustZoom(-0.5)" style="background:rgba(255,255,255,0.15); border:none; color:white; width:36px; height:36px; border-radius:50%; font-size:1.3rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">+</button>
            <button onclick="resetZoom()" style="background:rgba(255,255,255,0.15); border:none; color:white; width:36px; height:36px; border-radius:50%; font-size:1rem; cursor:pointer; display:flex; align-items:center; justify-content:center;" title="Reset">
                <ion-icon name="refresh-outline"></ion-icon>
            </button>
            <button onclick="closeZoom()" style="background:rgba(255,255,255,0.15); border:none; color:white; width:36px; height:36px; border-radius:50%; font-size:1.3rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">✕</button>
        </div>
    </div>

    <!-- Image Container -->
    <div id="zoomContainer" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; overflow:hidden;">
        <img id="zoomImage" src="" style="max-width:100%; max-height:100%; object-fit:contain; transform-origin:center center; transition:none; will-change:transform; user-select:none; -webkit-user-drag:none;">
    </div>

    <!-- Hint -->
    <div id="zoomHint" style="position:absolute; bottom:20px; left:50%; transform:translateX(-50%); color:rgba(255,255,255,0.6); font-size:0.78rem; text-align:center; pointer-events:none; transition:opacity 0.5s;">
        Scroll or pinch to zoom · Drag to pan
    </div>
</div>

<style>
#zoomLightbox.active { display:flex !important; }
#zoomImage[data-zoomed="true"] { cursor:grab; }
#zoomImage[data-dragging="true"] { cursor:grabbing !important; transition:none !important; }
</style>

<script>
(function() {
    const allImages = <?php echo json_encode(array_values((array)$images)); ?>;
    let currentZoomSrc = '';
    let scale = 1, minScale = 1, maxScale = 5;
    let tx = 0, ty = 0;
    let isDragging = false, startX = 0, startY = 0, lastTx = 0, lastTy = 0;
    let lastPinchDist = 0;
    let hintTimer;

    const lb = document.getElementById('zoomLightbox');
    const img = document.getElementById('zoomImage');
    const levelEl = document.getElementById('zoomLevel');
    const hint = document.getElementById('zoomHint');

    function applyTransform(animated) {
        img.style.transition = animated ? 'transform 0.2s ease' : 'none';
        img.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
        levelEl.textContent = Math.round(scale * 100) + '%';
        img.dataset.zoomed = scale > 1 ? 'true' : 'false';
    }

    function clampTranslate() {
        // Allow panning only within scaled bounds
        const rect = img.getBoundingClientRect();
        const lbRect = lb.getBoundingClientRect();
        const maxTx = Math.max(0, (rect.width - lbRect.width) / 2);
        const maxTy = Math.max(0, (rect.height - lbRect.height) / 2);
        tx = Math.max(-maxTx, Math.min(maxTx, tx));
        ty = Math.max(-maxTy, Math.min(maxTy, ty));
    }

    function hideHint() {
        clearTimeout(hintTimer);
        hintTimer = setTimeout(() => { hint.style.opacity = '0'; }, 2000);
    }

    window.openZoom = function(src) {
        currentZoomSrc = src;
        img.src = src;
        scale = 1; tx = 0; ty = 0;
        applyTransform(false);
        lb.style.display = 'flex';
        lb.classList.add('active');
        document.body.style.overflow = 'hidden';
        hint.style.opacity = '1';
        hideHint();
    };

    window.closeZoom = function() {
        lb.style.display = 'none';
        lb.classList.remove('active');
        document.body.style.overflow = '';
    };

    window.resetZoom = function() {
        scale = 1; tx = 0; ty = 0;
        applyTransform(true);
    };

    window.adjustZoom = function(delta) {
        // delta > 0 = zoom out (−), delta < 0 = zoom in (+)
        scale = Math.min(maxScale, Math.max(minScale, scale - delta));
        if (scale === 1) { tx = 0; ty = 0; }
        clampTranslate();
        applyTransform(true);
        hideHint();
    };

    // Close on backdrop click
    lb.addEventListener('click', function(e) {
        if (e.target === lb || e.target === document.getElementById('zoomContainer')) closeZoom();
    });

    // Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeZoom();
    });

    // --- Mouse Wheel Zoom ---
    lb.addEventListener('wheel', function(e) {
        e.preventDefault();
        const delta = e.deltaY > 0 ? -0.3 : 0.3;
        scale = Math.min(maxScale, Math.max(minScale, scale + delta));
        if (scale === 1) { tx = 0; ty = 0; }
        clampTranslate();
        applyTransform(false);
        hideHint();
    }, { passive: false });

    // --- Mouse Drag ---
    img.addEventListener('mousedown', function(e) {
        if (scale <= 1) return;
        e.preventDefault();
        isDragging = true;
        img.dataset.dragging = 'true';
        startX = e.clientX - tx;
        startY = e.clientY - ty;
    });

    document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        tx = e.clientX - startX;
        ty = e.clientY - startY;
        clampTranslate();
        applyTransform(false);
    });

    document.addEventListener('mouseup', function() {
        isDragging = false;
        img.dataset.dragging = 'false';
    });

    // --- Touch: Pinch & Pan ---
    let touches = [];
    lb.addEventListener('touchstart', function(e) {
        touches = Array.from(e.touches);
        if (touches.length === 2) {
            lastPinchDist = Math.hypot(
                touches[0].clientX - touches[1].clientX,
                touches[0].clientY - touches[1].clientY
            );
        } else if (touches.length === 1 && scale > 1) {
            isDragging = true;
            startX = touches[0].clientX - tx;
            startY = touches[0].clientY - ty;
        }
    }, { passive: true });

    lb.addEventListener('touchmove', function(e) {
        e.preventDefault();
        touches = Array.from(e.touches);

        if (touches.length === 2) {
            // Pinch zoom
            const dist = Math.hypot(
                touches[0].clientX - touches[1].clientX,
                touches[0].clientY - touches[1].clientY
            );
            const ratio = dist / lastPinchDist;
            scale = Math.min(maxScale, Math.max(minScale, scale * ratio));
            lastPinchDist = dist;
            if (scale === 1) { tx = 0; ty = 0; }
            clampTranslate();
            applyTransform(false);
            hideHint();
        } else if (touches.length === 1 && isDragging && scale > 1) {
            // Pan
            tx = touches[0].clientX - startX;
            ty = touches[0].clientY - startY;
            clampTranslate();
            applyTransform(false);
        }
    }, { passive: false });

    lb.addEventListener('touchend', function(e) {
        if (e.touches.length === 0) isDragging = false;
        touches = Array.from(e.touches);
    }, { passive: true });

    // Double-tap to zoom in/out
    let lastTap = 0;
    lb.addEventListener('touchend', function(e) {
        const now = Date.now();
        if (now - lastTap < 300) {
            if (scale > 1) { scale = 1; tx = 0; ty = 0; }
            else { scale = 2.5; }
            clampTranslate();
            applyTransform(true);
            e.preventDefault();
        }
        lastTap = now;
    });
})();
</script>

<?php include 'includes/footer.php'; ?>
