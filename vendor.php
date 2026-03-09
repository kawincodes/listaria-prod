<?php
session_start();
require 'includes/db.php';

$vendor_id = $_GET['id'] ?? null;

if (!$vendor_id) {
    header("Location: index.php");
    exit;
}

// Fetch Vendor Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND account_type = 'vendor' AND is_public = 1");
$stmt->execute([$vendor_id]);
$vendor = $stmt->fetch();

if (!$vendor) {
    echo "<h1>Vendor not found or profile is private.</h1>";
    exit;
}

$view = $_GET['view'] ?? 'thrift'; // Default to thrift for existing links

// Increment Profile Views
if (!isset($_SESSION['user_id']) || $vendor_id != $_SESSION['user_id']) {
    $stmt = $pdo->prepare("UPDATE users SET profile_views = profile_views + 1 WHERE id = ?");
    $stmt->execute([$vendor_id]);
}

// Fetch Vendor's Products
if ($view === 'thrift') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? AND approval_status = 'approved' AND is_published = 1 AND category IN ('Fashion', 'Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories') ORDER BY created_at DESC");
} else {
    // Normal View: All approved and published products
    $stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? AND approval_status = 'approved' AND is_published = 1 ORDER BY created_at DESC");
}
$stmt->execute([$vendor_id]);
$products = $stmt->fetchAll();

include 'includes/header.php';
?>

<?php if ($view === 'thrift'): ?>
<style id="thrift-theme">
    body { 
        background-color: #eae4cc !important; 
        color: #1a1a1a !important;
    }
    :root {
        --bg-color: #eae4cc !important;
        --surface-color: #fdfcf8 !important;
        --primary-text: #1a1a1a !important;
        --secondary-text: #555555 !important;
        --border-color: #1a1a1a !important;
    }
</style>
<?php endif; ?>

<div class="vendor-profile-wrapper" style="padding: 100px 20px 40px; max-width: 1200px; margin: 0 auto;">
    <!-- Vendor Header -->
    <div class="vendor-header" style="background: transparent; padding: 20px 0; border-bottom: <?php echo $view === 'thrift' ? '2px dashed #1a1a1a' : '1px solid #f1f5f9'; ?>; text-align: center; margin-bottom: 40px;">
        
        <div class="vendor-logo-container" style="width: 120px; height: 120px; border-radius: 50%; border: <?php echo $view === 'thrift' ? '2px solid #1a1a1a' : '1px solid #f1f5f9'; ?>; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #fff; margin: 0 auto 20px; box-shadow: <?php echo $view === 'thrift' ? 'none' : '0 4px 12px rgba(0,0,0,0.05)'; ?>;">
            <?php if (!empty($vendor['profile_image'])): ?>
                <img src="<?php echo htmlspecialchars($vendor['profile_image']); ?>" alt="Store Logo" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 100%; background: #f8fafc; color: var(--brand-color, #6B21A8); display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 800; font-family: <?php echo $view === 'thrift' ? "'Times New Roman', serif" : "'Inter', sans-serif"; ?>;">
                    <?php echo strtoupper(substr($vendor['business_name'] ?? $vendor['full_name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
        </div>

        <h1 style="margin: 0 0 10px; font-size: 2.2rem; color: #1a1a1a; font-family: <?php echo $view === 'thrift' ? "'Times New Roman', serif" : "'Inter', sans-serif"; ?>; font-weight: 800; display:flex; align-items:center; justify-content:center; gap:10px;">
            <?php echo htmlspecialchars($vendor['business_name'] ?? $vendor['full_name']); ?>
            <ion-icon name="checkmark-circle" style="color:#10b981; font-size:1.6rem;"></ion-icon>
        </h1>
        <p style="color: #666; max-width: 600px; margin: 0 auto; font-family: <?php echo $view === 'thrift' ? "'Courier New', monospace" : "'Inter', sans-serif"; ?>; font-weight: 600; font-size: 0.95rem;">
            Verified <?php echo $view === 'thrift' ? 'Thrift' : 'Professional'; ?> Vendor
        </p>
    </div>

    <!-- Catalogue Section -->
    <h2 style="font-size: 1.6rem; margin-bottom: 30px; color: #1a1a1a; font-family: <?php echo $view === 'thrift' ? "'Times New Roman', serif" : "'Inter', sans-serif"; ?>; font-weight: 800; <?php echo $view === 'thrift' ? 'border-left: 5px solid #1a1a1a; padding-left: 10px;' : ''; ?>">
        <?php echo $view === 'thrift' ? "Thrift Finds" : "Product Catalogue"; ?> 
        <span style="color: #888; font-weight: 500; font-size: 1.2rem;">(<?php echo count($products); ?>)</span>
    </h2>
    
    <?php if (count($products) > 0): ?>
    <div class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
        <?php foreach ($products as $product): 
            $images = json_decode($product['image_paths']);
            $thumb = $images[0] ?? 'https://via.placeholder.com/300';
            $cond = htmlspecialchars($product['condition_tag']);
        ?>
        <div class="card-wrapper">
            <a href="product_details.php?id=<?php echo $product['id']; ?><?php echo $view === 'thrift' ? '&source=thrift' : ''; ?>" class="product-card <?php echo $view === 'thrift' ? 'neubrutalism-card' : 'clean-card'; ?>">
                <div class="product-image-container">
                    <div class="price-tag">
                        ₹<?php echo number_format($product['price_min']); ?>
                    </div>
                    <?php if(isset($product['status']) && $product['status'] === 'sold'): ?>
                        <span class="sold-badge">SOLD</span>
                    <?php endif; ?>
                    <img src="<?php echo htmlspecialchars($thumb); ?>" class="product-thumb">
                </div>
                <div class="product-info">
                    <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                    <?php if ($view === 'thrift'): ?>
                        <div class="thrift-meta">Condition:<br><?php echo $cond; ?></div>
                    <?php else: ?>
                        <div class="clean-meta"><?php echo $cond; ?></div>
                    <?php endif; ?>
                    
                    <div class="btn-action">
                        <?php echo $view === 'thrift' ? 'CLAIM PIECE' : 'VIEW PRODUCT'; ?>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 80px 20px; background: white; border-radius: 16px; color: #999; border: 1px dashed #e2e8f0;">
        <ion-icon name="basket-outline" style="font-size: 3.5rem; margin-bottom: 15px;"></ion-icon>
        <p style="font-weight: 600;">No active listings from this vendor yet.</p>
    </div>
    <?php endif; ?>
</div>

<style>
/* Base Reset & Transition */
.product-card {
    display: block;
    text-decoration: none;
    transition: all 0.2s ease;
    height: 100%;
    overflow: hidden;
}

/* NEUBRUTALISM STYLE (Thrift View) */
.neubrutalism-card {
    border: 2.5px solid #1a1a1a;
    background: #fff;
    padding: 15px;
    box-shadow: 6px 6px 0px #1a1a1a;
    border-radius: 12px;
}
.neubrutalism-card:hover {
    transform: translate(-3px, -3px) !important;
    box-shadow: 10px 10px 0px #1a1a1a !important;
}
.neubrutalism-card .product-image-container {
    border-bottom: 1.5px solid #1a1a1a;
    padding-bottom: 12px;
}
.neubrutalism-card .price-tag {
    position:absolute; top: -10px; right: -10px; background: #ef4444; color: white; padding: 5px 12px; 
    font-weight: 800; font-family: 'Courier New', monospace; transform: rotate(5deg); z-index: 5; border: 2px solid #1a1a1a; border-radius: 4px;
}
.neubrutalism-card .product-title {
    font-family: 'Times New Roman', serif; font-size: 1.3rem; font-weight: 800; margin: 15px 0 8px;
}
.neubrutalism-card .btn-action {
    background:#1a1a1a; color:#fff; text-align:center; padding: 10px; font-weight: 700; font-family: 'Courier New', monospace; border-radius: 6px;
}
.neubrutalism-card .product-thumb {
    filter: sepia(0.05) contrast(1.05); border-radius: 8px;
}

/* CLEAN MODERN STYLE (Normal View) */
.clean-card {
    background: #fff;
    border: 1px solid #f0f0f0;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.04);
}
.clean-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.08);
}
.clean-card .product-image-container {
    padding: 0;
}
.clean-card .price-tag {
    position:absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.9); color: #1a1a1a; padding: 4px 10px; 
    font-weight: 800; font-family: 'Inter', sans-serif; z-index: 5; border-radius: 8px; font-size: 0.95rem;
}
.clean-card .product-info { padding: 15px; }
.clean-card .product-title {
    font-family: 'Inter', sans-serif; font-size: 1.1rem; font-weight: 700; margin: 0 0 5px; color: #1a1a1a;
}
.clean-card .clean-meta {
    font-family: 'Inter', sans-serif; font-size: 0.9rem; font-weight: 500; color: #1a1a1a; margin-bottom: 15px;
}
.clean-card .btn-action {
    background:#f8fafc; color:#1a1a1a; text-align:center; padding: 10px; font-weight: 700; font-family: 'Inter', sans-serif; 
    border-radius: 10px; border: 1px solid #e2e8f0; font-size: 0.9rem;
}
.clean-card:hover .btn-action {
    background: #1a1a1a; color: #fff; border-color: #1a1a1a;
}

/* Common Components */
.product-image-container { position:relative; width: 100%; aspect-ratio: 1/1; }
.product-thumb { width:100%; height:100%; object-fit:cover; }
.sold-badge {
    position:absolute; top:10px; left:10px; background:#1a1a1a; color:white; padding:5px 10px; z-index:4; font-weight:bold; border-radius:6px;
}

@media (max-width: 768px) {
    .product-grid { grid-template-columns: 1fr 1fr !important; gap: 12px !important; }
    .vendor-profile-wrapper { padding-top: 80px !important; }
    .clean-card .product-title { font-size: 0.95rem; }
    .clean-card .price-tag { font-size: 0.8rem; padding: 3px 8px; }
    .clean-card .btn-action { font-size: 0.8rem; padding: 8px; }
    .vendor-header h1 { font-size: 1.6rem; }
    .vendor-logo-container { width: 90px !important; height: 90px !important; }
}
</style>

<?php include 'includes/footer.php'; ?>
