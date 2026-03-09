<?php
require 'includes/db.php';

// Filter strictly for Fashion/Thrift items
$category = $_GET['category'] ?? 'All'; // "All" within Thrift context means "All Fashion"
$search = $_GET['search'] ?? '';

// Define valid thrift categories
$valid_categories = ['Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories'];

// Base constraints for Thrift
if ($category !== 'All' && in_array($category, $valid_categories)) {
    // Specific Category
    $thrift_condition = "AND p.category = '$category'";
} else {
    // Show All Thrift Items (Include 'Fashion' for backward compatibility + new categories)
    $thrift_condition = "AND p.category IN ('Fashion', 'Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories')";
}

if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT p.*, MAX(o.created_at) as sold_at_date, u.account_type, u.business_name, u.full_name, u.profile_image 
                         FROM products p 
                         LEFT JOIN orders o ON p.id = o.product_id 
                         LEFT JOIN users u ON p.user_id = u.id
                         WHERE p.is_published = 1 
                         AND p.approval_status = 'approved'
                         $thrift_condition
                         AND (p.title LIKE ? OR p.condition_tag LIKE ?)
                         GROUP BY p.id 
                         ORDER BY p.created_at DESC");
    $stmt->execute(["%$search%", "%$search%"]); 
} else {
    // Default or Category View
    $stmt = $pdo->query("SELECT p.*, MAX(o.created_at) as sold_at_date, u.account_type, u.business_name, u.full_name, u.profile_image 
                         FROM products p 
                         LEFT JOIN orders o ON p.id = o.product_id 
                         LEFT JOIN users u ON p.user_id = u.id
                         WHERE p.is_published = 1 
                         AND p.approval_status = 'approved'
                         $thrift_condition
                         GROUP BY p.id 
                         ORDER BY p.created_at DESC");
    $products = $stmt->fetchAll();
}

$product_count = count($products);

// Grouping logic
$vendor_groups = [];
$community_products = [];

foreach ($products as $p) {
    // Always add to community products so everything appears in the Community Closet
    $community_products[] = $p;
    
    if (($p['account_type'] ?? '') === 'vendor') {
        $vid = $p['user_id'];
        if (!isset($vendor_groups[$vid])) {
            $vendor_groups[$vid] = [
                'name' => !empty($p['business_name']) ? $p['business_name'] : $p['full_name'],
                'photo' => $p['profile_image'] ?? null,
                'id' => $vid,
                'products' => []
            ];
        }
        $vendor_groups[$vid]['products'][] = $p;
    }
}

$product_count = count($products);

include 'includes/header.php';
?>

<!-- Thrift+ Custom Banner -->
<style id="thrift-theme">
    /* Force Light/Retro Theme for Thrift Page */
    body { 
        background-color: #eae4cc !important; 
        color: #1a1a1a !important;
    }
    
    .thrift-page-text { 
        color: #1a1a1a !important; 
    }

    /* Override Dark Mode Variables for this page specifically */
    :root {
        --bg-color: #eae4cc !important;
        --surface-color: #fdfcf8 !important;
        --primary-text: #1a1a1a !important;
        --secondary-text: #555555 !important;
        --border-color: #1a1a1a !important;
    }

    /* Thrift Hero Mobile Adjustment */
    @media (max-width: 768px) {
        .thrift-hero {
            margin-top: 5.5rem !important; /* Clear fixed header */
            margin-bottom: 2rem !important;
        }
    }
</style>

<div class="thrift-hero" style="background: transparent; padding: 0; margin: 30px auto; max-width: 1240px; border-radius: 24px; position:relative; overflow:hidden; display:flex; justify-content:center; align-items:center;">
    <?php 
        $tBanner = 'assets/thrift_banner.png';
        if(!file_exists($tBanner)) {
            // Fallback to old one if exists, or placeholder
            $tBanner = 'assets/thrift_banner_final.png';
        }
    ?>
    <img src="<?php echo $tBanner; ?>" alt="Listaria Thrift+ Banner" style="width: 100%; height: auto; display: block; border-radius: 20px;">
</div>

<!-- Categories -->
<div class="categories-container" style="background:transparent; padding: 10px 20px; display:flex; align-items:center; justify-content:center; margin-bottom:2rem; gap: 10px;">
    <div class="scroll-btn" id="scrollLeft" style="cursor:pointer; background:#fff; border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 5px rgba(0,0,0,0.1); min-width:40px; color:#1a1a1a;">
        <ion-icon name="chevron-back-outline"></ion-icon>
    </div>
     <div class="categories-wrapper" id="catWrapper" style="display:flex; gap:12px; overflow-x:auto; scroll-behavior:smooth; scrollbar-width:none; -ms-overflow-style:none; padding: 5px; white-space:nowrap; max-width: 100%;">
        <?php
        $cats = ['All', 'Tops', 'Bottoms', 'Jackets', 'Shoes', 'Bags', 'Accessories'];
        foreach ($cats as $cat) {
            $isActive = ($category === $cat);
            $bg = $isActive ? '#1a1a1a' : '#fff'; 
            $fg = $isActive ? '#fff' : '#1a1a1a';
            
            $url = "thrift.php?category=" . urlencode($cat);
            
            echo "<a href='$url' class='category-pill' style='background:$bg; color:$fg; border-radius:50px; padding:12px 28px; font-weight:600; border:none; box-shadow:0 2px 4px rgba(0,0,0,0.03); font-size:0.95rem; text-decoration:none; flex-shrink:0; transition:transform 0.2s;'>$cat</a>";
        }
        ?>
        <a href="thrift.php?search=Perfectly+Loved" class="category-pill" style="background:#1a1a1a; color:#fff; border-radius:50px; padding:12px 28px; font-weight:600; border:none; margin-left:10px; text-decoration:none; flex-shrink:0;">Perfectly Loved <ion-icon name="arrow-forward" style="vertical-align:middle; margin-left:5px;"></ion-icon></a>
    </div>
    <div class="scroll-btn" id="scrollRight" style="cursor:pointer; background:#fff; border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 5px rgba(0,0,0,0.1); min-width:40px; color:#1a1a1a;">
        <ion-icon name="chevron-forward-outline"></ion-icon>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const wrapper = document.getElementById('catWrapper');
        const leftBtn = document.getElementById('scrollLeft');
        const rightBtn = document.getElementById('scrollRight');

        if(wrapper && leftBtn && rightBtn) {
            leftBtn.addEventListener('click', () => {
                wrapper.scrollBy({ left: -200, behavior: 'smooth' });
            });

            rightBtn.addEventListener('click', () => {
                wrapper.scrollBy({ left: 200, behavior: 'smooth' });
            });
            
            // Optional: Hide scrollbars via CSS injection just in case
            const style = document.createElement('style');
            style.innerHTML = '.categories-wrapper::-webkit-scrollbar { display: none; }';
            document.head.appendChild(style);
        }
    });
</script>

<div class="container" style="max-width:1240px;">
    
    <div class="section-header" style="text-align:left; display:block; margin-bottom:2.5rem; padding-left:10px;">
        <h2 style="font-size:1.8rem; margin-bottom:1rem; display:flex; align-items:center; gap:12px;">
            <span style="font-weight:900; letter-spacing:-0.5px;">Thrift+</span> 
            <span style="font-weight:400; font-size:1.3rem; color:#555; position:relative; top:2px;">Re-Cycled, Sustainable Style.</span>
        </h2>
        <div class="sub-tags" style="display:flex; justify-content:flex-start; gap:10px; flex-wrap:wrap;">
            <span style="background:#e5e7eb; padding:8px 18px; border-radius:30px; font-size:0.9rem; color:#1a1a1a; font-weight:600;">Apparel</span>
            <span style="background:#e5e7eb; padding:8px 18px; border-radius:30px; font-size:0.9rem; color:#1a1a1a; font-weight:600;">Accessories</span>
            <span style="background:#e5e7eb; padding:8px 18px; border-radius:30px; font-size:0.9rem; color:#1a1a1a; font-weight:600;">Vintage Finds</span>
            <span style="background:#e5e7eb; padding:8px 18px; border-radius:30px; font-size:0.9rem; color:#1a1a1a; font-weight:600;">Condition: Used</span>
        </div>
    </div>

    <style>
    @media (max-width: 768px) {
        .product-grid {
            grid-template-columns: 1fr 1fr !important; /* Force 2 columns on mobile */
            gap: 15px !important;
            padding: 0 5px !important;
        }
        .card-wrapper .product-card {
            padding: 10px !important;
            border-width: 2.5px !important;
            box-shadow: 5px 5px 0px #1a1a1a !important;
        }
        .card-wrapper .product-title {
            font-size: 1rem !important;
            margin-bottom: 5px !important;
        }
        .card-wrapper .btn-claim {
            font-size: 0.8rem !important;
            padding: 8px 4px !important;
            white-space: nowrap; /* Prevent wrapping */
        }
        .card-wrapper .price-tag {
            font-size: 0.9rem !important;
            padding: 3px 8px !important;
            right: -8px !important;
            top: -8px !important;
        }
        .card-wrapper .product-condition {
            font-size: 0.85rem !important;
            margin-bottom: 10px !important;
        }
    }
</style>

<?php
// Function to render product card cleanly
function renderThriftProduct($product) {
    if (isset($product['status']) && $product['status'] === 'sold' && !empty($product['sold_at_date'])) {
        if (time() - strtotime($product['sold_at_date']) > 86400) { 
            return; 
        }
    }
    $images = json_decode($product['image_paths']);
    $main_image = $images[0] ?? 'https://via.placeholder.com/300';
    $price = number_format($product['price_min'], 0);
    $title = htmlspecialchars($product['title']);
    $cond = htmlspecialchars($product['condition_tag']);
    $url = "product_details.php?id={$product['id']}&source=thrift";
    
    echo <<<HTML
    <div class="card-wrapper">
        <a href="{$url}" class="product-card" style="display:block; text-decoration:none; border: 2.5px solid #1a1a1a; background: #fff; padding: 15px; box-shadow: 6px 6px 0px #1a1a1a; border-radius: 12px; transition: transform 0.2s; position:relative;">
            <div class="product-image-container" style="position:relative; margin-bottom:15px; border-bottom: 1.5px solid #1a1a1a; padding-bottom: 12px;">
                <div class="price-tag" style="position:absolute; top: -10px; right: -10px; background: #ef4444; color: white; padding: 5px 12px; font-weight: 800; font-family: 'Courier New', monospace; font-size: 1.1rem; transform: rotate(5deg); z-index: 5; border: 2px solid #1a1a1a; border-radius: 4px;">
                    ₹{$price}
                </div>
HTML;
    if(isset($product['status']) && $product['status'] === 'sold'){
        echo '<span class="condition-badge sold-badge" style="position:absolute; top:10px; left:10px; background:#1a1a1a; color:white; padding:5px 10px; z-index:4; font-weight:bold; font-family:serif; border-radius:6px;">SOLD</span>';
    }
    echo <<<HTML
                <img src="{$main_image}" alt="{$title}" class="product-image" style="width:100%; aspect-ratio:1/1; object-fit:cover; display:block; filter: sepia(0.05) contrast(1.05); border-radius: 8px;" loading="lazy">
            </div>
             <div class="product-info" style="color:#1a1a1a;">
                <div class="product-title" style="font-family: 'Times New Roman', serif; font-weight: 800; font-size: 1.3rem; line-height: 1.2; margin-bottom: 8px; text-transform: capitalize; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {$title}
                </div>
                <div class="product-condition" style="font-family: 'Courier New', monospace; font-size: 0.95rem; color: #333; margin-bottom: 15px; font-weight:600; line-height: 1.4;">
                    Condition:<br>{$cond}
                </div>
                <div class="btn-claim" style="background:#1a1a1a; color:#fff; text-align:center; padding: 10px; font-weight: 700; font-family: 'Courier New', monospace; display:block; width:100%; border:none; letter-spacing:1px; font-size:1.1rem; cursor:pointer; border-radius: 6px;">
                    CLAIM PIECE
                </div>
            </div>
        </a>
    </div>
HTML;
}
?>

<?php if (count($products) == 0): ?>
    <div style="text-align:center; padding: 4rem; color: #1a1a1a;">
        <h3 style="font-family: 'Times New Roman', serif; font-size:2rem;">No thrift finds yet.</h3>
        <p style="font-family: 'Courier New', monospace;">Be the first to list something from your closet!</p>
        <a href="sell.php?source=thrift" class="btn-sell" style="display:inline-block; margin-top:1rem; background:#1a1a1a; color:white; padding:10px 20px; text-decoration:none; font-family:'Courier New', monospace;">Sell Pre-Loved</a>
    </div>
<?php else: ?>

    <?php if (count($vendor_groups) > 0): ?>
        <div class="vendor-container" style="margin-bottom: 4rem;">
            <h2 style="font-family: 'Times New Roman', serif; font-weight: 800; font-size: 1.8rem; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px dashed #1a1a1a;">Featured Stores</h2>
            <div class="product-grid" style="gap: 2rem; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                
                <?php foreach ($vendor_groups as $vid => $group): ?>
                    <div class="card-wrapper">
                        <a href="vendor.php?id=<?php echo $vid; ?>" class="product-card" style="display:block; text-decoration:none; border: 2.5px solid #1a1a1a; background: #fff; padding: 25px 20px; box-shadow: 6px 6px 0px #1a1a1a; border-radius: 12px; transition: transform 0.2s; text-align:center;">
                            
                            <div style="width: 100px; height: 100px; margin: 0 auto 15px; border-radius: 50%; border: 2px solid #1a1a1a; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #fff;">
                                <?php if (!empty($group['photo'])): ?>
                                    <img src="<?php echo htmlspecialchars($group['photo']); ?>" alt="Store Logo" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: #1a1a1a; color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; font-family: 'Times New Roman', serif;">
                                        <?php echo strtoupper(substr($group['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; align-items: center; justify-content: center; gap: 6px; margin-bottom: 10px;">
                                <h2 style="font-family: 'Times New Roman', serif; font-weight: 800; font-size: 1.6rem; margin: 0; color: #1a1a1a; text-transform: capitalize; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; max-width: 80%;">
                                    <?php echo htmlspecialchars($group['name']); ?>
                                </h2>
                                <ion-icon name="checkmark-circle" style="color:#27ae60; font-size:1.2rem; flex-shrink: 0;"></ion-icon>
                            </div>
                            
                            <p style="font-family: 'Courier New', monospace; font-size: 0.9rem; margin: 0 0 20px; color: #555; font-weight: 600;">
                                Verified Thrift Vendor<br>
                                <span style="display:inline-block; margin-top:5px; background:#eae4cc; color:#1a1a1a; padding:3px 10px; border-radius:20px; border:1px solid #1a1a1a; font-size: 0.8rem;"><?php echo count($group['products']); ?> Items Available</span>
                            </p>
                            
                            <div style="background:#1a1a1a; color:white; padding:12px; text-decoration:none; font-family:'Courier New', monospace; font-weight:bold; border-radius:8px; display:inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%;">
                                <ion-icon name="storefront-outline" style="font-size: 1.2rem;"></ion-icon> Visit Store
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    <?php endif; ?>

    <?php if (count($community_products) > 0): ?>
        <div class="vendor-container" style="margin-bottom: 4rem;">
            <h2 style="font-family: 'Times New Roman', serif; font-weight: 800; font-size: 1.8rem; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px dashed #1a1a1a;">Community Closet</h2>
            <div class="product-grid" style="gap: 2rem; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                <?php foreach ($community_products as $product) { renderThriftProduct($product); } ?>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>
</div>

<?php 
// Include standard modal logic...
// To avoid duplication, I'll rely on the existing code below this block if I didn't verify it was distinct. 
// But earlier view revealed I truncated the file logic so I need to be careful not to double up if I just replace a chunk.
// I am replacing from 58 to 265, so I need to include the footer/end of file.
?>
<!-- Reusing Success Modals -->
<?php 
$show_success_modal = false;
if (isset($_GET['order_success']) && $_GET['order_success'] == '1' && isset($_SESSION['user_id'])) {
    $v_stmt = $pdo->prepare("SELECT order_status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $v_stmt->execute([$_SESSION['user_id']]);
    $latest_order = $v_stmt->fetch();
    if ($latest_order && $latest_order['order_status'] === 'Success') {
        $show_success_modal = true;
    }
}
if ($show_success_modal): 
?>
<div id="successModal" class="success-modal">
    <div class="success-content">
        <div class="success-icon">
            <ion-icon name="checkmark-circle"></ion-icon>
        </div>
        <h2>Thrifted Successfully!</h2>
        <p>Your sustainable purchase is on its way.</p>
        <button onclick="closeModal()" class="btn-primary" style="margin-top:1.5rem; width:100%;">Keep Thrifting</button>
    </div>
</div>
<style>
/* Reusing modal styles inline or from style.css, assuming style.css covers it or I need to include them again if they were inline in index.php */
.success-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 2000; animation: fadeIn 0.3s ease; }
.success-content { background: white; padding: 2.5rem; border-radius: 20px; text-align: center; max-width: 350px; width: 90%; animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.success-icon { font-size: 4rem; color: #27ae60; margin-bottom: 1rem; }
.success-content h2 { margin: 0 0 0.5rem 0; color: #333; }
.btn-primary { background: #333; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>
<script>
    if (history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.delete('order_success');
        url.searchParams.delete('payment_pending');
        history.replaceState(null, '', url.toString());
    }
    function closeModal() {
        document.getElementById('successModal').style.display = 'none';
        if(document.getElementById('pendingModal')) document.getElementById('pendingModal').style.display = 'none';
    }
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
