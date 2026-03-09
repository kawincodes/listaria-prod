<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'includes/db.php';
require_once 'includes/email_templates.php';

$user_id = $_SESSION['user_id'];
$message = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'thrift_profile_required') {
        $message = '<div class="alert error" style="background:#fef2f2; color:#dc2626; padding:15px; border-radius:8px; margin-bottom:20px; border-left:4px solid #dc2626;"><ion-icon name="alert-circle-outline" style="vertical-align:middle; font-size:1.2rem; margin-right:8px;"></ion-icon> <strong>Action Required:</strong> You must set your Store / Business Name and upload a Store Logo in your Business Profile settings before you can list items on Thrift+.</div>';
    }
}

// Handle Profile Update (Address & Phone)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    
    $stmt = $pdo->prepare("UPDATE users SET address = ?, phone = ? WHERE id = ?");
    if ($stmt->execute([$address, $phone, $user_id])) {
        $message = '<div class="alert success">Profile details updated successfully!</div>';
    } else {
        $message = '<div class="alert error">Failed to update details.</div>';
    }
}

// Handle Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_id'])) {
    $product_id = $_POST['delete_product_id'];
    
    // Verify ownership
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$product_id, $user_id])) {
        $message = '<div class="alert success">Product deleted successfully.</div>';
    } else {
        $message = '<div class="alert error">Failed to delete product.</div>';
    }
}

// Handle Delete Vendor Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_vendor_profile'])) {
    $stmt = $pdo->prepare("UPDATE users SET account_type = 'customer', vendor_status = 'none', is_verified_vendor = 0 WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        $_SESSION['account_type'] = 'customer';
        header("Location: profile.php?msg=vendor_profile_deleted");
        exit;
    } else {
        $message = '<div class="alert error">Failed to delete vendor profile.</div>';
    }
}

// Handle Return Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_order_id'])) {
    $r_order_id = $_POST['return_order_id'];
    $r_product_id = $_POST['return_product_id'];
    $r_reason = $_POST['return_reason'];
    $r_details = $_POST['return_details'];
    
    // Check if return already exists
    $stmt = $pdo->prepare("SELECT id FROM returns WHERE order_id = ?");
    $stmt->execute([$r_order_id]);
    if($stmt->rowCount() > 0) {
        $message = '<div class="alert error">Return request already submitted for this order.</div>';
    } else {
        // Handle File Uploads
        $photoPaths = [];
        $videoPath = null;
        $uploadDir = 'uploads/returns/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        // Process Photos
        if (isset($_FILES['evidence_photos']) && !empty($_FILES['evidence_photos']['name'][0])) {
            foreach ($_FILES['evidence_photos']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['evidence_photos']['error'][$key] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['evidence_photos']['name'][$key], PATHINFO_EXTENSION);
                    $filename = 'return_' . $r_order_id . '_img_' . $key . '_' . time() . '.' . $ext;
                    $target = $uploadDir . $filename;
                    if (move_uploaded_file($tmpName, $target)) {
                        $photoPaths[] = $target;
                    }
                }
            }
        }

        // Process Video
        if (isset($_FILES['evidence_video']) && $_FILES['evidence_video']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['evidence_video']['name'], PATHINFO_EXTENSION);
            $filename = 'return_' . $r_order_id . '_vid_' . time() . '.' . $ext;
            $target = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['evidence_video']['tmp_name'], $target)) {
                $videoPath = $target;
            }
        }

        if (empty($photoPaths)) {
            $message = '<div class="alert error">At least one photo is required for the return request.</div>';
        } else {
            $stmt = $pdo->prepare("INSERT INTO returns (order_id, user_id, product_id, reason, details, evidence_photos, evidence_video) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $evidencePhotosJson = json_encode($photoPaths);
            if ($stmt->execute([$r_order_id, $user_id, $r_product_id, $r_reason, $r_details, $evidencePhotosJson, $videoPath])) {
                 $message = '<div class="alert success">Return request submitted successfully with evidence.</div>';
                 // Email buyer confirmation
                 $buyerRow = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
                 $buyerRow->execute([$user_id]);
                 $buyerInfo = $buyerRow->fetch();
                 $prodRow = $pdo->prepare("SELECT title FROM products WHERE id = ?");
                 $prodRow->execute([$r_product_id]);
                 $prodTitle = $prodRow->fetchColumn();
                 if ($buyerInfo && $prodTitle) {
                     sendTemplateMail($pdo, 'return_submitted', $buyerInfo['email'], [
                         'customer_name' => $buyerInfo['full_name'],
                         'order_id'      => $r_order_id,
                         'product_title' => $prodTitle,
                         'return_reason' => $r_reason,
                         'profile_url'   => 'https://listaria.in/profile.php',
                     ], $buyerInfo['full_name']);
                 }
            } else {
                 $message = '<div class="alert error">Failed to submit return request.</div>';
            }
        }
    }
}

// Fetch User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Sync session account_type with DB (admin may have approved/rejected since last login)
if (isset($user['account_type']) && $_SESSION['account_type'] !== $user['account_type']) {
    $_SESSION['account_type'] = $user['account_type'];
}

// Fetch User's Products
$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$my_products = $stmt->fetchAll();

// Fetch Incoming Offers (as Seller)
$offers_sql = "
    SELECT n.*, p.title as product_title, u.full_name as buyer_name,
    (SELECT message FROM messages WHERE negotiation_id = n.id ORDER BY created_at DESC LIMIT 1) as last_message
    FROM negotiations n 
    JOIN products p ON n.product_id = p.id 
    JOIN users u ON n.buyer_id = u.id 
    WHERE n.seller_id = ? 
    ORDER BY n.created_at DESC
";
$stmt = $pdo->prepare($offers_sql);
$stmt->execute([$user_id]);
$incoming_offers = $stmt->fetchAll();

// Fetch Sent Offers (as Buyer)
$sent_offers_sql = "
    SELECT n.*, p.title as product_title, p.image_paths, u.full_name as seller_name,
    (SELECT message FROM messages WHERE negotiation_id = n.id ORDER BY created_at DESC LIMIT 1) as last_message
    FROM negotiations n 
    JOIN products p ON n.product_id = p.id 
    JOIN users u ON n.seller_id = u.id 
    WHERE n.buyer_id = ? 
    ORDER BY n.created_at DESC
";
$stmt = $pdo->prepare($sent_offers_sql);
$stmt->execute([$user_id]);
$sent_offers = $stmt->fetchAll();

// Fetch My Purchases
$purchases_sql = "
    SELECT o.*, p.title as product_title, p.image_paths, p.price_min as original_price, u.full_name as seller_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
";

$stmt = $pdo->prepare($purchases_sql);
$stmt->execute([$user_id]);
$my_purchases = $stmt->fetchAll();

// Fetch My Sales (as Seller — orders placed on my products)
$my_sales = [];
$sales_sql = "
    SELECT o.*, p.title as product_title, p.image_paths, u.full_name as buyer_name
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.user_id = u.id
    WHERE p.user_id = ?
    ORDER BY o.created_at DESC
";
$stmt = $pdo->prepare($sales_sql);
$stmt->execute([$user_id]);
$my_sales = $stmt->fetchAll();

// Fetch Returns (As Seller)
$seller_returns_sql = "
    SELECT r.*, p.title, u.full_name as buyer_name
    FROM returns r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    WHERE p.user_id = ?
    ORDER BY r.created_at DESC
";
$stmt = $pdo->prepare($seller_returns_sql);
$stmt->execute([$user_id]);
$my_seller_returns = $stmt->fetchAll();

// Fetch My Returns (As Buyer)
$buyer_returns_sql = "
    SELECT r.*, p.title, p.image_paths, u.full_name as seller_name
    FROM returns r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
";
$stmt = $pdo->prepare($buyer_returns_sql);
$stmt->execute([$user_id]);
$my_buyer_returns = $stmt->fetchAll();

include 'includes/header.php';
// Merge and Sort Negotiations
$all_negotiations = [];
foreach($incoming_offers as $offer) {
    $offer['role'] = 'seller';
    $offer['other_party_name'] = $offer['buyer_name'];
    $all_negotiations[] = $offer;
}
foreach($sent_offers as $offer) {
    $offer['role'] = 'buyer';
    $offer['other_party_name'] = $offer['seller_name'];
    $all_negotiations[] = $offer;
}

// Sort by latest message or creation date (descending)
usort($all_negotiations, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'vendor_profile_deleted'): ?>
    <div class="alert success" style="margin: 20px;">Your vendor profile has been successfully deleted. You are now a standard customer.</div>
    <?php elseif ($_GET['msg'] === 'already_vendor'): ?>
    <div class="alert success" style="margin: 20px; background:#f0fdf4; color:#15803d; border-left:4px solid #22c55e; padding:15px; border-radius:8px;">
        <ion-icon name="checkmark-circle-outline" style="vertical-align:middle; margin-right:6px;"></ion-icon>
        <strong>You are already a verified vendor.</strong> Use the Vendor Dashboard to manage your store.
    </div>
    <?php elseif ($_GET['msg'] === 'business_updated'): ?>
    <div class="alert success" style="margin: 20px; background:#f0fdf4; color:#15803d; border-left:4px solid #22c55e; padding:15px; border-radius:8px;">
        <ion-icon name="checkmark-circle-outline" style="vertical-align:middle; margin-right:6px;"></ion-icon>
        Business profile updated successfully.
    </div>
    <?php elseif ($_GET['msg'] === 'update_failed'): ?>
    <div class="alert error" style="margin: 20px; background:#fef2f2; color:#dc2626; border-left:4px solid #dc2626; padding:15px; border-radius:8px;">
        <ion-icon name="alert-circle-outline" style="vertical-align:middle; margin-right:6px;"></ion-icon>
        Failed to update business profile. Please try again.
    </div>
    <?php endif; ?>
<?php endif; ?>

<div class="profile-page-wrapper">
    <!-- Top Dark Section -->
    <div class="profile-header-section">
        <!-- Header Actions: Back, Heart, Menu -->
        <div class="header-actions">
            <a href="index.php" class="icon-btn circle-white"><ion-icon name="chevron-back-outline"></ion-icon></a>
            <div style="display:flex; gap:10px;">
                <a href="wishlist.php" class="icon-btn circle-white"><ion-icon name="heart-outline"></ion-icon></a>
                <div class="icon-btn circle-white menu-trigger" onclick="toggleMenu()"><ion-icon name="menu-outline"></ion-icon></div>
            </div>
        </div>

        <!-- User Profile Info -->
        <div class="user-profile-info">
            <div class="avatar-circle">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div class="user-details">
                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <p>Member since <?php echo isset($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'Jan 2026'; ?></p>
            </div>
        </div>

        <?php 
        $user_account_type = $user['account_type'] ?? 'customer';
        $vendor_status = $user['vendor_status'] ?? 'none';
        
        if ($user_account_type === 'vendor'): 
        ?>
        <div class="info-banner-card" onclick="openVendorSettings()" style="cursor: pointer; background: rgba(139, 92, 246, 0.2); border-color: rgba(139, 92, 246, 0.3);">
            <div style="display:flex; gap:12px; align-items:center;">
                <div style="flex:1;">
                    <h3 style="margin:0; font-size:1rem;">Vendor Dashboard</h3>
                    <p style="margin-top:2px; font-size:0.8rem; opacity:0.8;">Manage your business profile, bulk uploads, and analytics.</p>
                </div>
                <button class="btn-text-add">Settings</button>
            </div>
        </div>
        <?php else: ?>
        <div class="info-banner-card" onclick="openProfileModal()" style="cursor: pointer;">
            <div style="display:flex; gap:12px; align-items:center;">
                <div style="flex:1;">
                    <h3 style="margin:0; font-size:1rem;">Edit Profile</h3>
                    <p style="margin-top:2px; font-size:0.8rem; opacity:0.8;">Your listing is live! Add bank details to complete your profile.</p>
                </div>
                <button class="btn-text-add">Add +</button>
            </div>
        </div>
        
        <?php if($vendor_status === 'pending'): ?>
        <div class="info-banner-card" onclick="location.href='switch_to_vendor.php'" style="cursor: pointer; background: #fffbeb; border: 1px solid #fde68a; margin-top: 10px;">
            <div style="display:flex; gap:12px; align-items:center;">
                <ion-icon name="time-outline" style="color:#d97706; font-size:1.5rem;"></ion-icon>
                <div style="flex:1;">
                    <h3 style="margin:0; font-size:1rem; color:#d97706;">Application Pending</h3>
                    <p style="margin-top:2px; font-size:0.8rem; color:#92400e;">Your vendor application is under review.</p>
                </div>
                <button style="background:#fef3c7; color:#d97706; border:none; padding:5px 12px; border-radius:15px; font-size:0.8rem; font-weight:700;">View</button>
            </div>
        </div>
        <?php elseif($vendor_status === 'rejected'): ?>
        <div class="info-banner-card" onclick="location.href='switch_to_vendor.php'" style="cursor: pointer; background: #fef2f2; border: 1px solid #fecaca; margin-top: 10px;">
            <div style="display:flex; gap:12px; align-items:center;">
                <ion-icon name="alert-circle-outline" style="color:#dc2626; font-size:1.5rem;"></ion-icon>
                <div style="flex:1;">
                    <h3 style="margin:0; font-size:1rem; color:#dc2626;">Application Rejected</h3>
                    <p style="margin-top:2px; font-size:0.8rem; color:#991b1b;">Your recent application was not approved. Click to re-apply.</p>
                </div>
                <button style="background:#fee2e2; color:#dc2626; border:none; padding:5px 12px; border-radius:15px; font-size:0.8rem; font-weight:700;">Re-apply</button>
            </div>
        </div>
        <?php else: ?>
        <div class="info-banner-card" onclick="location.href='switch_to_vendor.php'" style="cursor: pointer; background: #1a1a1a; margin-top: 10px;">
            <div style="display:flex; gap:12px; align-items:center;">
                <ion-icon name="storefront-outline" style="color:white; font-size:1.5rem;"></ion-icon>
                <div style="flex:1;">
                    <h3 style="margin:0; font-size:1rem; color:white;">Start Selling Professionally</h3>
                    <p style="margin-top:2px; font-size:0.8rem; color:rgba(255,255,255,0.7);">Get bulk uploads, business analytics, and a public store.</p>
                </div>
                <button style="background:white; color:#1a1a1a; border:none; padding:5px 12px; border-radius:15px; font-size:0.8rem; font-weight:700;">Upgrade</button>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- White Bottom Section with Tabs -->
    <?php 
    $active_tab = $_GET['tab'] ?? 'offers'; 
    $valid_tabs = ['offers', 'listings', 'orders', 'returns'];
    if (!in_array($active_tab, $valid_tabs)) $active_tab = 'offers';
    ?>
    <div class="profile-content-section">
        <!-- Tabs -->
        <div class="custom-tabs">
             <div class="c-tab <?php echo $active_tab === 'offers' ? 'active' : ''; ?>" onclick="switchTab('offers')">Chat</div>
             <div class="c-tab <?php echo $active_tab === 'listings' ? 'active' : ''; ?>" onclick="switchTab('listings')">Listings</div>
             <div class="c-tab <?php echo $active_tab === 'orders' ? 'active' : ''; ?>" onclick="switchTab('orders')">Orders</div>

             <div class="c-tab <?php echo $active_tab === 'returns' ? 'active' : ''; ?>" onclick="switchTab('returns')">Returns</div>
        </div>

        <!-- Offers Tab Content -->
        <div id="tab-offers" class="tab-content <?php echo $active_tab === 'offers' ? 'active' : ''; ?>">
             <?php if (count($all_negotiations) > 0): ?>
                <div class="listings-list">
                    <?php foreach ($all_negotiations as $offer): 
                        $is_unread = false;
                        if($offer['role'] == 'seller' && isset($offer['is_read']) && $offer['is_read'] == 0) $is_unread = true;
                        if($offer['role'] == 'buyer' && isset($offer['is_buyer_read']) && $offer['is_buyer_read'] == 0) $is_unread = true;
                    ?>
                        <div class="listing-item" onclick="openChat(<?php echo $offer['id']; ?>)">
                            <div class="listing-info">
                                <div class="listing-title">
                                    <?php echo htmlspecialchars($offer['product_title']); ?>
                                </div>
                                <div class="listing-date">
                                    <?php echo ucfirst($offer['role'] == 'seller' ? 'Buyer' : 'Seller'); ?>: 
                                    <?php echo htmlspecialchars($offer['other_party_name']); ?>
                                </div>
                                <div style="font-size:0.9rem; color:#555; margin-top:5px; font-style:italic;">
                                    "<?php echo htmlspecialchars(substr($offer['last_message'] ?? 'No messages yet', 0, 50)) . '...'; ?>"
                                </div>
                                <?php if($offer['final_price']): ?>
                                    <div style="color: #27ae60; font-weight: bold; font-size: 0.9rem; margin-top:5px;">
                                        Price: ₹<?php echo number_format($offer['final_price']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="listing-actions">
                                <button type="button" class="btn-view-sm" onclick="openChat(<?php echo $offer['id']; ?>); event.stopPropagation();">Chat</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align:center; padding:2rem; color:#888;">No active offers.</p>
            <?php endif; ?>
        </div>

        <!-- Listings Tab Content -->
        <div id="tab-listings" class="tab-content <?php echo $active_tab === 'listings' ? 'active' : ''; ?>">
            <div style="background: #ede9fe; padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                 <h2 style="font-size:1.1rem; margin:0; color:#1a1a1a;">My Listings</h2>
                 <a href="sell.php" class="btn-new-listing">+ New Listing</a>
            </div>

             <!-- Filter Pills -->
             <div class="filter-pills">
                <span class="pill active" onclick="filterListings('all', this)">All</span>
                <span class="pill" onclick="filterListings('active', this)">Active</span>
                <span class="pill" onclick="filterListings('in_review', this)">In Review</span>
                <span class="pill" onclick="filterListings('sold', this)">Sold</span>
             </div>

            <?php if (count($my_products) > 0): ?>
                <?php if ($_SESSION['account_type'] === 'vendor'): ?>
                <div style="display:flex; align-items:center; gap:10px; padding: 8px 4px; margin-bottom:6px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.85rem; color:#555; user-select:none;">
                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" style="width:18px; height:18px; cursor:pointer; accent-color: var(--brand-color);">
                        Select All
                    </label>
                    <span id="selectionHint" style="font-size:0.78rem; color:#999; display:none;">Tap items to select</span>
                </div>
                <?php endif; ?>
                <div class="listings-list">
                    <?php 
                    foreach ($my_products as $product): 
                        $images = json_decode($product['image_paths']);
                        $thumb = $images[0] ?? 'https://via.placeholder.com/100';
                        $pStatus = $product['approval_status'] ?? 'pending';
                        if(isset($product['status']) && $product['status'] === 'sold') $pStatus = 'Sold';
                        $isUnpublished = isset($product['is_published']) && $product['is_published'] == 0;
                    ?>
                        <div class="listing-card" data-status="<?php echo strtolower($product['status'] ?? 'active'); ?>" data-approval="<?php echo strtolower($product['approval_status'] ?? 'pending'); ?>" data-id="<?php echo $product['id']; ?>" <?php if($isUnpublished): ?>style="opacity:0.6;"<?php endif; ?>>
                            <?php if ($_SESSION['account_type'] === 'vendor'): ?>
                            <div class="batch-checkbox-wrapper" style="padding: 0 5px; display: flex; align-items: center;">
                                <input type="checkbox" class="listing-batch-checkbox" value="<?php echo $product['id']; ?>" onchange="onCheckboxChange(this);" style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--brand-color);">
                            </div>
                            <?php endif; ?>
                            <div class="card-img">
                                <img src="<?php echo htmlspecialchars($thumb); ?>" alt="Product">
                                <?php if($isUnpublished): ?>
                                <div style="position:absolute;top:4px;left:4px;background:#6b7280;color:#fff;font-size:0.65rem;padding:2px 5px;border-radius:4px;font-weight:600;">HIDDEN</div>
                                <?php endif; ?>
                            </div>
                            <div class="card-details">
                                <div style="display:flex; justify-content:space-between; align-items:start;">
                                    <h3 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                    <span class="status-badge <?php echo strtolower($pStatus); ?>"><?php echo ucfirst($pStatus); ?></span>
                                </div>
                                <div class="card-price">Listed: ₹<?php echo number_format($product['price_min']); ?></div>
                                <div class="card-actions-row">
                                     <a href="product_details.php?id=<?php echo $product['id']; ?>" class="action-link"><ion-icon name="eye-outline"></ion-icon> View</a>
                                     <a href="javascript:void(0)" onclick='openEditModal(<?php echo json_encode($product); ?>)' class="action-link"><ion-icon name="create-outline"></ion-icon> Edit</a>
                                     <a href="javascript:void(0)" onclick='shareListing("<?php echo $product['id']; ?>", "<?php echo addslashes($product['title']); ?>")' class="action-link"><ion-icon name="share-social-outline"></ion-icon> Share</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align:center; padding:2rem; color:#888;">No listings found.</p>
            <?php endif; ?>
        </div>

        <!-- Orders Tab Content -->
        <div id="tab-orders" class="tab-content <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">
            <?php if (count($my_purchases) > 0): ?>
                <div class="listings-list">
                    <?php foreach ($my_purchases as $order): 
                         $images = json_decode($order['image_paths']);
                         $thumb = $images[0] ?? 'https://via.placeholder.com/100';
                    ?>
                        <div class="listing-card" onclick='openOrderSummary(<?php echo htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8'); ?>)'>
                             <div class="card-img">
                                <img src="<?php echo htmlspecialchars($thumb); ?>" alt="Product">
                            </div>
                            <div class="card-details">
                                <h3 class="card-title"><?php echo htmlspecialchars($order['product_title']); ?></h3>
                                <p style="font-size:0.85rem; color:#666;">Seller: <?php echo htmlspecialchars($order['seller_name']); ?></p>
                                <div class="card-price" style="margin-top:5px;">₹<?php echo number_format($order['amount']); ?></div>
                                <div class="status-text" style="color:#27ae60; font-size:0.8rem; margin-top:5px;">
                                    Status: <?php echo htmlspecialchars($order['order_status'] ?? 'Processing'); ?>
                                </div>
                                <?php
                                    // Helper to check return eligibility
                                    $deliveryDate = $order['delivery_date'] ?? $order['created_at'];
                                    $isDelivered = strtolower($order['order_status'] ?? '') === 'delivered';

                                    // Show return button for all delivered items
                                    if ($isDelivered):
                                        $checkDate = !empty($order['delivery_date']) ? $order['delivery_date'] : date('Y-m-d');
                                        $isSameDay = date('Y-m-d') === date('Y-m-d', strtotime($checkDate));
                                    ?>
                                        <button class="btn-return-professional" 
                                                onclick='<?php echo $isSameDay ? "openReturnModal(" . json_encode($order) . ")" : "alert(\"Return is not available. Returns are only accepted on the same day the product is delivered.\")"; ?>; event.stopPropagation();'
                                                style="<?php echo $isSameDay ? '' : 'border-color: #e2e8f0; color: #94a3b8; cursor: pointer;'; ?>">
                                            <ion-icon name="arrow-undo-outline"></ion-icon> Return Item
                                        </button>
                                    <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                 <p style="text-align:center; padding:2rem; color:#888;">No orders found.</p>
            <?php endif; ?>

            <!-- My Sales (as Seller) -->
            <?php if (count($my_sales) > 0): ?>
            <div style="margin-top:30px;">
                <div style="background:#f0fdf4; padding:10px 15px; border-radius:8px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center; border-left:4px solid #22c55e;">
                    <h2 style="font-size:1.1rem; margin:0; color:#15803d;">My Sales</h2>
                    <span style="font-size:0.85rem; color:#16a34a; font-weight:600;"><?php echo count($my_sales); ?> order<?php echo count($my_sales) !== 1 ? 's' : ''; ?></span>
                </div>
                <div class="listings-list">
                    <?php foreach ($my_sales as $sale):
                        $simages = json_decode($sale['image_paths']);
                        $sthumb = $simages[0] ?? 'https://via.placeholder.com/100';
                        $statusColor = '#27ae60';
                        $sts = strtolower($sale['order_status'] ?? 'processing');
                        if ($sts === 'processing') $statusColor = '#f59e0b';
                        elseif ($sts === 'shipped') $statusColor = '#3b82f6';
                        elseif ($sts === 'cancelled') $statusColor = '#ef4444';
                    ?>
                        <div class="listing-card">
                            <div class="card-img">
                                <img src="<?php echo htmlspecialchars($sthumb); ?>" alt="Product">
                            </div>
                            <div class="card-details">
                                <h3 class="card-title"><?php echo htmlspecialchars($sale['product_title']); ?></h3>
                                <p style="font-size:0.85rem; color:#666;">Buyer: <?php echo htmlspecialchars($sale['buyer_name']); ?></p>
                                <div class="card-price" style="margin-top:5px;">₹<?php echo number_format($sale['amount']); ?></div>
                                <div style="display:flex; gap:8px; align-items:center; margin-top:5px; flex-wrap:wrap;">
                                    <span style="color:<?php echo $statusColor; ?>; font-size:0.8rem; font-weight:600;">
                                        <?php echo ucfirst($sale['order_status'] ?? 'Processing'); ?>
                                    </span>
                                    <span style="color:#94a3b8; font-size:0.75rem;">
                                        <?php echo date('M j, Y', strtotime($sale['created_at'])); ?>
                                    </span>
                                    <?php if(!empty($sale['payment_method'])): ?>
                                    <span style="background:#f1f5f9; color:#64748b; font-size:0.75rem; padding:2px 8px; border-radius:10px;">
                                        <?php echo htmlspecialchars(strtoupper($sale['payment_method'])); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Returns Tab Content -->
        <div id="tab-returns" class="tab-content <?php echo $active_tab === 'returns' ? 'active' : ''; ?>">
            
            <!-- My Returns (As Buyer) -->
             <?php if (count($my_buyer_returns) > 0): ?>
                <h3 style="font-size:1rem; margin-bottom:15px; color:#555;">My Returns</h3>
                <?php $returnsJsData = []; ?>
                <div class="listings-list" style="margin-bottom:30px;">
                    <?php foreach ($my_buyer_returns as $ret): 
                        $st = strtolower($ret['status']);
                        $statusText = ucfirst(str_replace('_', ' ', $st));
                        $statusStyle = 'background:#f3f4f6; color:#1f2937;';
                        $borderStyle = 'border-left: 4px solid #ccc;';
                        
                        // Status Logic
                        if($st == 'pending') { $statusStyle = 'background:#fef9c3; color:#854d0e;'; $borderStyle = 'border-left: 4px solid #f1c40f;'; }
                        elseif($st == 'approved') { $statusStyle = 'background:#dcfce7; color:#166534;'; $borderStyle = 'border-left: 4px solid #2ecc71;'; }
                        elseif($st == 'rejected') { $statusStyle = 'background:#fee2e2; color:#991b1b;'; $borderStyle = 'border-left: 4px solid #e74c3c;'; }
                        elseif($st == 'pickup_scheduled') { $statusStyle = 'background:#e0f2fe; color:#075985;'; $borderStyle = 'border-left: 4px solid #075985;'; $statusText = "Pickup Scheduled"; }
                        elseif($st == 'collected') { $statusStyle = 'background:#f3e8ff; color:#6b21a8;'; $borderStyle = 'border-left: 4px solid #6b21a8;'; }
                         elseif($st == 'refunded') { $statusStyle = 'background:#dcfce7; color:#166534;'; $borderStyle = 'border-left: 4px solid #27ae60;'; }

                        $images = json_decode($ret['image_paths'] ?? '[]');
                        $thumb = $images[0] ?? 'https://via.placeholder.com/100';
                        $pickupDate = $ret['pickup_date'] ? date('M j, Y', strtotime($ret['pickup_date'])) : null;
                        
                        // Collect data for JS
                        $cleanData = [
                            'id' => $ret['id'],
                            'title' => $ret['title'],
                            'image' => $thumb,
                            'status' => $ret['status'], 
                            'statusText' => $statusText,
                            'reason' => $ret['reason'],
                            'details' => $ret['details'],
                            'created_at' => date('M j, Y', strtotime($ret['created_at'])),
                            'pickup_date' => $pickupDate,
                            'expected_return_date' => $ret['expected_return_date'] ? date('M j, Y', strtotime($ret['expected_return_date'])) : null,
                            'seller' => $ret['seller_name']
                        ];
                        $returnsJsData[$ret['id']] = $cleanData;
                    ?>
                        <div class="listing-card" style="<?php echo $borderStyle; ?> cursor:pointer;" onclick="openReturnSummary(<?php echo $ret['id']; ?>)">
                            <div class="card-img" style="width:70px; height:70px;">
                                <img src="<?php echo htmlspecialchars($thumb); ?>" alt="Product">
                            </div>
                            <div class="card-details" style="flex:1;">
                                <div style="display:flex; justify-content:space-between;">
                                    <h3 class="card-title"><?php echo htmlspecialchars($ret['title']); ?></h3>
                                    <span class="status-badge" style="<?php echo $statusStyle; ?>"><?php echo $statusText; ?></span>
                                </div>
                                <p style="font-size:0.85rem; color:#666; margin-top:5px;">Seller: <?php echo htmlspecialchars($ret['seller_name']); ?></p>
                                <div style="font-size:0.75rem; color:#999; margin-top:8px;">
                                    Requested: <?php echo date('M j, Y', strtotime($ret['created_at'])); ?>
                                </div>
                                <?php if($st == 'pickup_scheduled'): ?>
                                    <div style="margin-top:8px; font-size:0.8rem; color:#075985; background:#e0f2fe; padding:6px; border-radius:4px;">
                                        <ion-icon name="calendar-outline" style="vertical-align:middle;"></ion-icon> Pickup: <strong><?php echo $pickupDate; ?></strong>
                                    </div>
                                <?php elseif($st == 'collected'): ?>
                                    <div style="margin-top:8px; font-size:0.8rem; color:#6b21a8; background:#f3e8ff; padding:6px; border-radius:4px;">
                                        <ion-icon name="checkmark-circle-outline" style="vertical-align:middle;"></ion-icon> Item collected. Processing refund.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <script>
                    window.returnsMap = <?php echo json_encode($returnsJsData); ?>;
                </script>
            <?php endif; ?>

            <!-- Incoming Return Requests (As Seller) -->
            <?php if (count($my_seller_returns) > 0): ?>
                <h3 style="font-size:1rem; margin-bottom:15px; color:#555;">Incoming Return Requests</h3>
                <div class="listings-list">
                    <?php foreach ($my_seller_returns as $ret): 
                        $st = strtolower($ret['status']);
                        $statusStyle = 'background:#f3f4f6; color:#1f2937;';
                        $borderStyle = 'border-left: 4px solid #ccc;';
                        
                        if($st == 'pending') { $statusStyle = 'background:#fef9c3; color:#854d0e;'; $borderStyle = 'border-left: 4px solid #f1c40f;'; }
                        elseif($st == 'approved') { $statusStyle = 'background:#dcfce7; color:#166534;'; $borderStyle = 'border-left: 4px solid #2ecc71;'; }
                        elseif($st == 'rejected') { $statusStyle = 'background:#fee2e2; color:#991b1b;'; $borderStyle = 'border-left: 4px solid #e74c3c;'; }
                    ?>
                        <div class="listing-card" style="<?php echo $borderStyle; ?>">
                            <div class="card-details" style="width:100%;">
                                <div style="display:flex; justify-content:space-between;">
                                    <h3 class="card-title"><?php echo htmlspecialchars($ret['title']); ?></h3>
                                    <span class="status-badge" style="<?php echo $statusStyle; ?>"><?php echo ucfirst($ret['status']); ?></span>
                                </div>
                                <p style="font-size:0.85rem; color:#666; margin-top:5px;">Buyer: <?php echo htmlspecialchars($ret['buyer_name']); ?></p>
                                <div style="background:#f9f9f9; padding:8px; border-radius:4px; margin-top:8px; font-size:0.85rem;">
                                    <strong>Reason:</strong> <?php echo htmlspecialchars($ret['reason']); ?><br>
                                    <span style="color:#555;">"<?php echo htmlspecialchars($ret['details']); ?>"</span>
                                </div>
                                <div style="font-size:0.75rem; color:#999; margin-top:8px; text-align:right;">
                                    Requested: <?php echo date('M j', strtotime($ret['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if(count($my_buyer_returns) == 0 && count($my_seller_returns) == 0): ?>
                 <p style="text-align:center; padding:2rem; color:#888;">No return requests.</p>
            <?php endif; ?>
        </div>



    </div>
</div>

    </div>
</div>

<!-- Bulk Actions Bar (Vendor Only) -->
<?php if ($_SESSION['account_type'] === 'vendor'): ?>
<div id="bulkActionsBar" style="display:none; position:fixed; bottom:80px; left:50%; transform:translateX(-50%); background:white; padding:12px 16px; border-radius:16px; box-shadow:0 8px 32px rgba(0,0,0,0.18); z-index:9000; width:92%; max-width:520px; border:2px solid var(--brand-color);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <div style="font-weight:700; color:var(--brand-color); font-size:0.95rem;"><span id="selectedCount">0</span> listing(s) selected</div>
        <button onclick="clearSelection()" style="background:none;border:none;color:#999;cursor:pointer;font-size:0.8rem;padding:0;">Clear</button>
    </div>
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:8px;">
        <button onclick="bulkAction('publish')" style="background:#10b981;color:white;border:none;padding:9px 6px;border-radius:10px;font-weight:600;cursor:pointer;font-size:0.78rem;display:flex;flex-direction:column;align-items:center;gap:3px;">
            <ion-icon name="eye-outline" style="font-size:1.1rem;"></ion-icon>Publish
        </button>
        <button onclick="bulkAction('unpublish')" style="background:#6b7280;color:white;border:none;padding:9px 6px;border-radius:10px;font-weight:600;cursor:pointer;font-size:0.78rem;display:flex;flex-direction:column;align-items:center;gap:3px;">
            <ion-icon name="eye-off-outline" style="font-size:1.1rem;"></ion-icon>Hide
        </button>
        <button onclick="bulkAction('sold')" style="background:#f59e0b;color:white;border:none;padding:9px 6px;border-radius:10px;font-weight:600;cursor:pointer;font-size:0.78rem;display:flex;flex-direction:column;align-items:center;gap:3px;">
            <ion-icon name="checkmark-done-outline" style="font-size:1.1rem;"></ion-icon>Sold
        </button>
        <button onclick="bulkAction('delete')" style="background:#e74c3c;color:white;border:none;padding:9px 6px;border-radius:10px;font-weight:600;cursor:pointer;font-size:0.78rem;display:flex;flex-direction:column;align-items:center;gap:3px;">
            <ion-icon name="trash-outline" style="font-size:1.1rem;"></ion-icon>Delete
        </button>
    </div>
</div>

<script>
function onCheckboxChange(checkbox) {
    const card = checkbox.closest('.listing-card');
    if (checkbox.checked) {
        card.style.outline = '2px solid var(--brand-color)';
        card.style.background = '#f5f3ff';
    } else {
        card.style.outline = '';
        card.style.background = '';
    }
    updateBatchBar();
    syncSelectAll();
}

function toggleSelectAll(masterCb) {
    const boxes = document.querySelectorAll('.listing-batch-checkbox');
    boxes.forEach(cb => {
        cb.checked = masterCb.checked;
        onCheckboxChange(cb);
    });
    updateBatchBar();
}

function syncSelectAll() {
    const all = document.querySelectorAll('.listing-batch-checkbox');
    const checked = document.querySelectorAll('.listing-batch-checkbox:checked');
    const master = document.getElementById('selectAllCheckbox');
    if (!master) return;
    master.indeterminate = checked.length > 0 && checked.length < all.length;
    master.checked = all.length > 0 && checked.length === all.length;
}

function clearSelection() {
    document.querySelectorAll('.listing-batch-checkbox:checked').forEach(cb => {
        cb.checked = false;
        onCheckboxChange(cb);
    });
    const master = document.getElementById('selectAllCheckbox');
    if (master) { master.checked = false; master.indeterminate = false; }
    updateBatchBar();
}

function updateBatchBar() {
    const checked = document.querySelectorAll('.listing-batch-checkbox:checked');
    const bar = document.getElementById('bulkActionsBar');
    const countSpan = document.getElementById('selectedCount');
    if (checked.length > 0) {
        bar.style.display = 'block';
        countSpan.textContent = checked.length;
    } else {
        bar.style.display = 'none';
    }
}

const actionLabels = {
    publish: 'publish',
    unpublish: 'hide',
    sold: 'mark as Sold',
    delete: 'permanently delete'
};

async function bulkAction(action) {
    const checked = document.querySelectorAll('.listing-batch-checkbox:checked');
    const ids = Array.from(checked).map(c => c.value);
    if (ids.length === 0) return;

    const label = actionLabels[action] || action;
    if (!confirm(`${ids.length} listing(s) will be ${label}d. Continue?`)) return;

    const buttons = document.querySelectorAll('#bulkActionsBar button:not([onclick="clearSelection()"])');
    buttons.forEach(b => { b.disabled = true; b.style.opacity = '0.6'; });

    try {
        const response = await fetch('api/bulk_listing_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ids })
        });
        const res = await response.json();
        if (res.success) {
            location.reload();
        } else {
            alert('Action failed: ' + (res.error || 'Unknown error'));
            buttons.forEach(b => { b.disabled = false; b.style.opacity = '1'; });
        }
    } catch (e) {
        alert('A network error occurred. Please try again.');
        buttons.forEach(b => { b.disabled = false; b.style.opacity = '1'; });
    }
}
</script>
<?php endif; ?>

<!-- Chat Modal (Reused) -->
<!-- ... (Keep existing chat modal logic/HTML but ensure it works with new styling) ... -->
<div id="chatModal" class="chat-modal">
    <div class="chat-content">
        <div class="chat-header">
            <h3>Negotiation</h3>
            <span class="close-chat" onclick="closeChat()">&times;</span>
        </div>
        <div class="chat-messages" id="chatMessages">
            <!-- Messages load here -->
        </div>
        
        <div class="finalize-area">
            <input type="number" id="finalPriceInput" placeholder="Final Price..." />
            <button onclick="setFinalPrice()" class="btn-finalize">Set Price</button>
        </div>

        <div class="chat-input-area">
            <input type="text" id="chatInput" placeholder="Reply..." />
            <button onclick="sendMessage()" class="btn-send"><ion-icon name="send"></ion-icon></button>
        </div>
    </div>
</div>

<style>
    /* Reset & Layout */
    body { background-color: var(--bg-color); margin: 0; font-family: 'Inter', sans-serif; color: var(--primary-text); }
    
    .profile-page-wrapper {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        padding-bottom: 80px; /* To prevent cutoff by the bottom navbar */
    }

    /* Top Section */
    .profile-header-section {
        background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand-color) 100%);
        color: white;
        padding: 20px;
        padding-bottom: 40px;
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .icon-btn.circle-white {
        width: 40px; height: 40px;
        background: rgba(255, 255, 255, 0.2); /* Semi-transparent */
        backdrop-filter: blur(5px);
        color: white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; font-size: 1.2rem;
        cursor: pointer;
        transition: background 0.2s;
    }
    .icon-btn.circle-white:hover { background: rgba(255, 255, 255, 0.3); }

    .user-profile-info {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
    }

    .avatar-circle {
        width: 64px; height: 64px;
        background-color: var(--brand-light);
        color: #fff;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; font-weight: 600;
        text-transform: uppercase;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .user-details h1 { margin: 0; font-size: 1.25rem; font-weight: 700; color: white; }
    .user-details p { margin: 2px 0 0; font-size: 0.85rem; color: #ddd6fe; } /* Light purple text */

    /* Banner Card */
    .info-banner-card {
        background: rgba(255, 255, 255, 0.1); /* Glassmorphism */
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 10px;
        backdrop-filter: blur(10px);
    }
    .info-banner-card h3 { margin: 0 0 4px; font-size: 0.95rem; color: white; font-weight: 600; }
    .info-banner-card p { margin: 0; font-size: 0.8rem; color: #e9d5ff; line-height: 1.4; } /* Soft purple white */
    .btn-text-add {
        background: none; border: none; color: #fff; font-weight: 600; cursor: pointer; font-size: 0.9rem;
    }

    /* Content Section */
    .profile-content-section {
        background-color: var(--bg-color); 
        border-top-left-radius: 24px;
        border-top-right-radius: 24px;
        flex: 1;
        padding: 20px;
        margin-top: -10px;
        padding-top: 25px;
        transition: background-color 0.3s ease;
    }

    /* Tabs */
    .custom-tabs {
        display: flex;
        background: var(--border-light);
        padding: 5px;
        border-radius: 30px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }
    .c-tab {
        flex: 1;
        text-align: center;
        padding: 10px;
        border-radius: 25px;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--secondary-text);
        cursor: pointer;
        transition: all 0.2s;
    }
    .c-tab.active {
        background-color: var(--surface-color);
        color: var(--brand-color); /* Deep Purple */
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    [data-theme="dark"] .c-tab.active {
        background-color: var(--surface-color);
        color: var(--brand-light);
    }

    /* Tab Logic */
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    /* Listings List */
    .listings-list { display: flex; flex-direction: column; gap: 15px; }
    
    .listing-item {
        background: var(--surface-color); padding: 15px; border-radius: 12px;
        display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        cursor: pointer;
        border: 1px solid var(--border-light);
    }
    .listing-title { font-weight: 600; color: var(--primary-text); font-size:1rem; }
    .listing-date { font-size: 0.8rem; color: var(--secondary-text); margin-top: 2px; }
    .btn-view-sm {
        background: var(--surface-color); border: 1px solid var(--border-color); padding: 6px 12px;
        border-radius: 6px; font-size: 0.85rem; color: var(--primary-text); cursor: pointer;
    }
    .btn-view-sm:hover {
        background: var(--hover-bg);
    }

    /* Card Style for Listings/Orders */
    .listing-card {
        background: var(--surface-color); padding: 12px; border-radius: 12px;
        display: flex; gap: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid var(--border-light);
    }
    .card-img {
        width: 80px; height: 80px; background: var(--hover-bg); border-radius: 8px; overflow: hidden;
        display: flex; align-items: center; justify-content: center; position: relative;
    }
    .card-img img { width: 100%; height: 100%; object-fit: contain; mix-blend-mode: multiply; }
    
    [data-theme="dark"] .card-img img { mix-blend-mode: normal; }
    
    .card-details { flex: 1; display: flex; flex-direction: column; justify-content: center; }
    .card-title { font-size: 0.95rem; font-weight: 600; margin: 0 0 5px; color: var(--primary-text); }
    .card-price { font-weight: 600; color: var(--primary-text); font-size: 0.95rem; }
    
    .status-badge { font-size: 0.75rem; padding: 2px 6px; border-radius: 4px; font-weight: 600; }
    .status-badge.active, .status-badge.approved { background: #dcfce7; color: #166534; }
    .status-badge.pending { background: #fef9c3; color: #854d0e; }
    .status-badge.sold { background: #fee2e2; color: #991b1b; }
    
    [data-theme="dark"] .status-badge.sold { border: 1px solid #fff; }

    .card-actions-row {
        display: flex; gap: 15px; margin-top: 8px;
        border-top: 1px solid var(--border-light); padding-top: 8px;
    }
    .action-link {
        color: var(--brand-color); text-decoration: none; font-size: 0.85rem; font-weight: 500;
        display: flex; align-items: center; gap: 4px;
    }
    [data-theme="dark"] .action-link { color: var(--brand-light); }

    .btn-return-professional {
        margin-top: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 16px;
        background: none;
        border: 1px solid #ef4444;
        color: #ef4444;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        align-self: flex-start; /* Prevent stretching horizontally */
    }
    .btn-return-professional ion-icon {
        font-size: 1.1rem;
    }
    .btn-return-professional:hover {
        background: #ef4444;
        color: white;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
    }
    
    /* Smaller return button length for desktop */
    @media (min-width: 769px) {
        .btn-return-professional {
            padding: 5px 12px; /* Shorter padding for desktop length */
            font-size: 0.8rem;
            margin-top: 10px;
        }
    }

    .btn-new-listing {
        background: var(--brand-color); color: #ffffff; padding: 6px 12px; border-radius: 6px; 
        text-decoration: none; font-size: 0.9rem; font-weight: 600;
        transition: background 0.2s;
    }
    .btn-new-listing:hover { background: var(--brand-dark); }

    .filter-pills {
        display: flex; overflow-x: auto; gap: 10px; margin-bottom: 15px; padding-bottom: 5px;
    }
    .filter-pills::-webkit-scrollbar { display: none; }
    .pill {
        white-space: nowrap; padding: 6px 16px; border-radius: 20px; border: 1px solid var(--border-color);
        background: var(--surface-color); color: var(--secondary-text); font-size: 0.85rem; font-weight: 500; cursor: pointer;
    }
    .pill.active {
        background: #f3e8ff; border-color: #d8b4fe; color: #7c3aed;
    }
    [data-theme="dark"] .pill.active {
        background: rgba(124, 58, 237, 0.2); border-color: var(--brand-light); color: var(--brand-light);
    }
    
    /* Ensure chat modal matches */
    .chat-modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.6); /* Slightly darker overlay */
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(5px);
    }
    
    .chat-content {
        background-color: var(--surface-color);
        width: 90%;
        max-width: 400px;
        height: 80vh; /* Responsive height */
        max-height: 600px;
        border-radius: 16px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        position: relative; /* Ensure stacking context */
        z-index: 10001; /* Above modal overlay */
    }

    .chat-header {
        background: var(--bg-color);
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .chat-header h3 { margin: 0; font-size: 1.1rem; color: var(--primary-text); }
    .close-chat { cursor: pointer; font-size: 1.5rem; color: var(--secondary-text); }
    
    .chat-messages {
        flex: 1;
        padding: 1rem;
        overflow-y: auto;
        background: var(--surface-color);
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .chat-input-area {
        padding: 1rem;
        background: var(--surface-color);
        border-top: 1px solid var(--border-color);
        display: flex;
        gap: 10px;
    }
    
    .finalize-area {
        padding: 10px;
        background: var(--bg-color); 
        display: flex;
        gap: 10px;
        align-items: center;
        border-top: 1px solid var(--border-color);
    }
    
    /* Ensure Inputs are visible */
    #chatInput, #finalPriceInput {
        border: 1px solid var(--border-color);
        background: var(--input-bg);
        color: var(--primary-text);
        padding: 8px;
        border-radius: 6px;
        flex: 1;
    }
    
    .message-bubble {
        padding: 8px 12px;
        border-radius: 12px;
        font-size: 0.9rem;
        max-width: 75%;
        line-height: 1.4;
    }
    .msg-me {
        background: var(--brand-color);
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 2px;
    }
    .msg-other {
        background: var(--hover-bg);
        color: var(--primary-text);
        align-self: flex-start;
        border-bottom-left-radius: 2px;
    }
    [data-theme="dark"] .msg-other {
        background: #374151;
    }

    /* Hide Footer Visuals on Profile Page */
    .site-footer { display: none; }
</style>

<script>
let currentNegotiationId = null;
let pollInterval = null;

function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.remove('active');
        el.style.display = 'none';
    });
    document.querySelectorAll('.c-tab').forEach(el => el.classList.remove('active'));

    const target = document.getElementById('tab-' + tabName);
    if (target) { target.classList.add('active'); target.style.display = 'block'; }

    document.querySelectorAll('.c-tab').forEach(t => {
        if (t.getAttribute('onclick') && t.getAttribute('onclick').includes(tabName)) t.classList.add('active');
    });

    if (tabName === 'offers') { if (typeof renderNegotiations === 'function') renderNegotiations(); }
}

function openChat(id) {
    currentNegotiationId = id;
    document.getElementById('chatModal').style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevent background scroll
    loadMessages();
    if(pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(loadMessages, 3000);
}

function closeChat() {
    document.getElementById('chatModal').style.display = 'none';
    document.body.style.overflow = '';
    currentNegotiationId = null;
    if(pollInterval) clearInterval(pollInterval);
}

function loadMessages() {
    if(!currentNegotiationId) return;
    
    const formData = new FormData();
    formData.append('action', 'get_messages');
    formData.append('negotiation_id', currentNegotiationId);
    
    fetch('api/chat.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            const chatDiv = document.getElementById('chatMessages');
            chatDiv.innerHTML = '';
            
            data.messages.forEach(msg => {
                const isMe = msg.sender_id == data.current_user_id;
                const div = document.createElement('div');
                div.className = 'message-bubble ' + (isMe ? 'msg-me' : 'msg-other');
                div.textContent = msg.message;
                chatDiv.appendChild(div);
            });
            // Auto scroll to bottom
            // chatDiv.scrollTop = chatDiv.scrollHeight; 
            
            // Final Price handling (if needed)
            if(data.final_price) {
                 // Update UI to show final price if not already shown
            }
        }
    })
    .catch(console.error);
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if(!msg || !currentNegotiationId) return;
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('negotiation_id', currentNegotiationId);
    formData.append('message', msg);
    
    fetch('api/chat.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            input.value = '';
            loadMessages();
        }
    })
    .catch(console.error);
}

function setFinalPrice() {
    const price = document.getElementById('finalPriceInput').value;
    if(!price || !currentNegotiationId) return;
    
    const formData = new FormData();
    formData.append('action', 'set_final_price');
    formData.append('negotiation_id', currentNegotiationId);
    formData.append('price', price);
    
    fetch('api/chat.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            alert('Price set successfully!');
            loadMessages();
        } else {
            alert(data.message);
        }
    })
    .catch(console.error);
}

// Global variable to track product for editing
let currentEditProduct = null;

function openEditModal(productJson) {
    if(typeof productJson === 'string') {
        productJson = JSON.parse(productJson);
    }
    
    currentEditProduct = productJson;
    
    // Reset Modal State
    switchEditTab('price');
    
    document.getElementById('editProductTitle').innerText = 'Product: ' + productJson.title;
    document.getElementById('editPriceInput').value = ''; // productJson.price_min;
    
    // Show Modal
    const modal = document.getElementById('editListingModal');
    modal.style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editListingModal').style.display = 'none';
    currentEditProduct = null;
}

function switchEditTab(tab) {
    // Buttons
    const priceBtn = document.getElementById('tabBtnPrice');
    const soldBtn = document.getElementById('tabBtnSold');
    
    // Content Areas
    const priceArea = document.getElementById('editPriceArea');
    const soldArea = document.getElementById('markSoldArea');
    
    // Reset Classes
    priceBtn.classList.remove('active');
    soldBtn.classList.remove('active');
    priceArea.style.display = 'none';
    soldArea.style.display = 'none';
    
    if(tab === 'price') {
        priceBtn.classList.add('active');
        priceArea.style.display = 'block';
        document.getElementById('editActionBtn').innerText = 'Update';
        document.getElementById('editActionBtn').onclick = updatePrice;
        document.getElementById('editActionBtn').className = 'btn-primary';
    } else {
        soldBtn.classList.add('active');
        soldArea.style.display = 'block';
        document.getElementById('editActionBtn').innerText = 'Mark as Sold';
        document.getElementById('editActionBtn').onclick = markAsSold;
        document.getElementById('editActionBtn').className = 'btn-primary btn-danger';
    }
}

function updatePrice() {
    if(!currentEditProduct) return;
    const newPrice = document.getElementById('editPriceInput').value;
    
    if(!newPrice || newPrice < 0) {
        alert("Please enter a valid price.");
        return;
    }
    
    const formData = new FormData();
    formData.append('product_id', currentEditProduct.id);
    formData.append('action', 'update_price');
    formData.append('price', newPrice);
    
    fetch('api/update_listing.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            alert('Price updated successfully!');
            location.reload(); 
        } else {
            alert(data.message || 'Error updating price');
        }
    })
    .catch(console.error);
}

function markAsSold() {
    if(!currentEditProduct) return;
    
    if(!confirm("Are you sure you want to mark this item as sold? This cannot be undone from here.")) return;
    
    const formData = new FormData();
    formData.append('product_id', currentEditProduct.id);
    formData.append('action', 'mark_sold');
    
    fetch('api/update_listing.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
         if(data.success) {
            alert('Item marked as sold!');
            location.reload(); 
        } else {
            alert(data.message || 'Error marking as sold');
        }
    })
    .catch(console.error);
}

    // Tab switching logic on load
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if(tab && typeof switchTab === 'function') {
            switchTab(tab);
        }
    });

    // Filter Logic
    function filterListings(type, element) {
        // Toggle Active Class
        document.querySelectorAll('.filter-pills .pill').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
        
        const items = document.querySelectorAll('.listing-card');
        
        items.forEach(item => {
            const status = item.getAttribute('data-status');
            const approval = item.getAttribute('data-approval');
            let show = false;

            if (type === 'all') {
                show = true;
            } else if (type === 'active') {
                // Active = Approved AND Not Sold
                if (approval === 'approved' && status !== 'sold') show = true;
            } else if (type === 'in_review') {
                // In Review = Pending
                if (approval === 'pending') show = true;
            } else if (type === 'sold') {
                // Sold = Sold status
                if (status === 'sold') show = true;
            }

            item.style.display = show ? 'flex' : 'none';
        });
    }
</script>

<!-- Edit Listing Modal -->
<div id="editListingModal" class="edit-modal-overlay" style="display:none;">
    <div class="edit-modal-content">
        <div class="edit-header">
            <h3>Edit Listing</h3>
            <span class="close-edit" onclick="closeEditModal()">&times;</span>
        </div>
        <p id="editProductTitle" style="font-size:0.9rem; color:#666; margin-bottom:15px; padding:0 20px;">Product Title</p>
        
        <div class="edit-tabs">
            <button id="tabBtnPrice" class="edit-tab-btn active" onclick="switchEditTab('price')">Edit Price</button>
            <button id="tabBtnSold" class="edit-tab-btn" onclick="switchEditTab('sold')">Mark as Sold</button>
        </div>
        
        <div class="edit-body">
            <!-- Edit Price Section -->
            <div id="editPriceArea">
                <label style="font-weight:600; font-size:0.9rem; color:#333; display:block; margin-bottom:5px;">Ask Price (₹)</label>
                <div class="input-wrapper">
                    <input type="number" id="editPriceInput" class="modal-input" placeholder="Enter new price">
                </div>
            </div>
            
            <!-- Mark Sold Section -->
            <div id="markSoldArea" style="display:none; text-align:center; padding:20px 0;">
                <ion-icon name="pricetag-outline" style="font-size:3rem; color:#666; margin-bottom:10px;"></ion-icon>
                <p>Mark this item as sold outside of Listaria?</p>
                <p style="font-size:0.8rem; color:#888;">This will display the item as "Sold" to all users.</p>
            </div>
        </div>
        
        <div class="edit-footer">
            <button id="editActionBtn" class="btn-primary" style="width:100%;">Update</button>
        </div>
    </div>
</div>

<style>
/* Edit Modal Styles */
.edit-modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 10002;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
}

.edit-modal-content {
    background: white; width: 95%; max-width: 420px;
    border-radius: 20px; overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    animation: slideUpFade 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex; flex-direction: column;
    max-height: 90vh;
}

.edit-header {
    padding: 20px 20px 5px; display: flex; justify-content: space-between; align-items: center;
}
.edit-header h3 { margin: 0; font-size: 1.2rem; color: #1f2937; }
.close-edit { font-size: 1.5rem; color: #9ca3af; cursor: pointer; line-height: 1; }

.edit-tabs {
    display: flex; gap: 10px; padding: 0 20px 20px;
}
.edit-tab-btn {
    flex: 1; padding: 10px; border-radius: 8px; border: 1px solid #e5e7eb;
    background: white; color: #4b5563; font-weight: 500; cursor: pointer;
    transition: all 0.2s;
}
.edit-tab-btn.active {
    background: #1f2937; color: white; border-color: #1f2937;
}

.edit-body { padding: 0 20px 20px; }

.input-wrapper { position: relative; }
.currency-symbol {
    position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
    color: #6b7280; font-weight: 600;
}
.modal-input {
    width: 100%; padding: 12px; border: 1px solid #d1d5db;
    border-radius: 8px; font-size: 1rem; color: #111827; outline: none;
    transition: border-color 0.15s;
}
.modal-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

.edit-footer {
    padding: 20px; background: #f9fafb; border-top: 1px solid #f3f4f6;
}

.btn-danger { background-color: #ef4444 !important; }
.btn-danger:hover { background-color: #dc2626 !important; }

@keyframes slideUpFade {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>

<!-- Order Summary Modal -->
<div id="orderSummaryModal" class="summary-modal-overlay" style="display:none;">
    <div class="summary-modal-content">
        <div class="summary-header">
            <h3>Order Summary</h3>
            <span class="close-summary" onclick="closeOrderSummary()">&times;</span>
        </div>
        <div class="summary-body">
            <div class="summary-product">
                <img id="summaryImg" src="" alt="Product" class="summary-thumb">
                <div class="summary-details">
                    <h4 id="summaryTitle">Product Title</h4>
                    <p id="summarySeller">Seller: Name</p>
                    <div id="summaryPrice" class="summary-price">₹0</div>
                </div>
            </div>
            <div class="summary-status-row">
                <span>Status:</span>
                <span id="summaryStatus" class="status-badge">Processing</span>
            </div>
             <div class="summary-info-row">
                <span>Order Date:</span>
                <span id="summaryDate">-</span>
            </div>
             <div class="summary-info-row">
                <span>Order ID:</span>
                <span id="summaryId">-</span>
            </div>
             <div id="summaryDeliveryRow" class="summary-info-row" style="display:none;">
                <span>Expected Delivery:</span>
                <span id="summaryDelivery" style="color:#2ecc71; font-weight:600;">-</span>
            </div>
        </div>
    </div>
</div>

<style>
/* Summary Modal Styles */
.summary-modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 10005;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
}
.summary-modal-content {
    background: white; width: 90%; max-width: 400px;
    border-radius: 16px; overflow: hidden;
    padding-bottom: 20px;
}
.summary-header {
    background: #f8fafc; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee;
}
.summary-header h3 { margin: 0; font-size: 1.1rem; }
.close-summary { font-size: 1.5rem; color: #999; cursor: pointer; }
.summary-body { padding: 20px; }
.summary-product { display: flex; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
.summary-thumb { width: 60px; height: 60px; border-radius: 8px; object-fit: cover; }
.summary-details h4 { margin: 0 0 5px; font-size: 1rem; }
.summary-details p { margin: 0; font-size: 0.85rem; color: #666; }
.summary-price { font-weight: 700; color: var(--brand-color); margin-top: 5px; }
.summary-info-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem; }
.status-badge { padding: 4px 10px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
</style>

<!-- Return Modal -->
<div id="returnModal" class="edit-modal-overlay" style="display:none;">
    <div class="edit-modal-content">
        <div class="edit-header">
            <h3>Request Return</h3>
            <span class="close-edit" onclick="document.getElementById('returnModal').style.display='none'">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data" style="padding: 20px; overflow-y: auto; flex: 1; scrollbar-width: thin;">
            <input type="hidden" name="return_order_id" id="returnOrderId">
            <input type="hidden" name="return_product_id" id="returnProductId">
            
            <label style="display:block; margin-bottom:5px; font-weight:600;">Reason for Return</label>
            <select name="return_reason" class="modal-input" required>
                <option value="Damaged Product">Damaged Product</option>
                <option value="Wrong Item Received">Wrong Item Received</option>
                <option value="Item Not As Described">Item Not As Described</option>
                <option value="Other">Other</option>
            </select>
            
            <label style="display:block; margin-top:15px; margin-bottom:5px; font-weight:600;">Additional Details</label>
            <textarea name="return_details" rows="3" placeholder="Please describe the issue..." style="width:100%; padding:12px; border:1px solid #e2e8f0; border-radius:12px; font-family: inherit; font-size:0.9rem; margin-bottom:15px;" required></textarea>

            <!-- Professional Upload UI -->
            <div class="upload-section">
                <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.95rem;">Evidence Photos <span style="color:#ef4444;">*</span></label>
                <div class="upload-grid">
                    <label class="upload-box" id="photoUploadBox">
                        <input type="file" name="evidence_photos[]" accept="image/*" capture="environment" multiple required onchange="previewImages(this)">
                        <ion-icon name="camera-outline"></ion-icon>
                        <span>Add Photos</span>
                    </label>
                    <div id="imagePreviewContainer" class="preview-scroll"></div>
                </div>
                <p style="font-size:0.75rem; color:#64748b; margin-top:6px;">Minimum 1 photo required. Drag to select multiple.</p>
            </div>

            <div class="upload-section" style="margin-top:15px;">
                <label style="display:block; margin-bottom:8px; font-weight:600; font-size:0.95rem;">Video Documentation (Optional)</label>
                <label class="upload-box video-box" id="videoUploadBox">
                    <input type="file" name="evidence_video" accept="video/*" onchange="updateVideoStatus(this)">
                    <ion-icon name="videocam-outline"></ion-icon>
                    <span id="videoStatusText">Upload Video</span>
                </label>
            </div>

            <style>
                .upload-section { background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px dashed #cbd5e1; }
                .upload-grid { display: flex; gap: 10px; align-items: center; overflow-x: auto; padding-bottom: 5px; }
                .upload-box {
                    flex: 0 0 100px; height: 100px; border: 2px dashed #cbd5e1; border-radius: 12px;
                    display: flex; flex-direction: column; align-items: center; justify-content: center;
                    cursor: pointer; background: white; transition: all 0.2s ease; color: #64748b;
                }
                .upload-box:hover { border-color: #6B21A8; color: #6B21A8; background: #f5f3ff; }
                .upload-box ion-icon { font-size: 1.8rem; margin-bottom: 4px; }
                .upload-box span { font-size: 0.75rem; font-weight: 600; text-align: center; }
                .upload-box input { display: none; }
                
                .video-box { flex: 1; height: auto; padding: 12px; flex-direction: row; gap: 10px; border-style: solid; border-width: 1px; }
                .video-box ion-icon { font-size: 1.4rem; margin-bottom: 0; }
                
                .preview-scroll { display: flex; gap: 8px; }
                .preview-item { width: 100px; height: 100px; border-radius: 10px; object-fit: cover; border: 1px solid #e2e8f0; }
                
                .btn-submit-return {
                    width:100%; margin-top:20px; background:#6B21A8; color:white; border:none; 
                    padding:14px; border-radius:12px; font-weight:700; font-size:1rem; cursor:pointer;
                    transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(107, 33, 168, 0.2);
                }
                .btn-submit-return:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(107, 33, 168, 0.3); }
            </style>

            <script>
                function previewImages(input) {
                    const container = document.getElementById('imagePreviewContainer');
                    container.innerHTML = '';
                    if (input.files) {
                        Array.from(input.files).forEach(file => {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.className = 'preview-item';
                                container.appendChild(img);
                            }
                            reader.readAsDataURL(file);
                        });
                    }
                }

                function updateVideoStatus(input) {
                    const statusText = document.getElementById('videoStatusText');
                    const icon = document.querySelector('.video-box ion-icon');
                    if (input.files.length > 0) {
                        statusText.innerText = input.files[0].name;
                        statusText.style.color = '#166534';
                        icon.style.color = '#166534';
                        document.getElementById('videoUploadBox').style.background = '#dcfce7';
                    }
                }
            </script>
            
            <div style="margin-top: 20px; padding: 12px; background: #fff8e1; border-left: 4px solid #ffc107; border-radius: 4px; display: flex; align-items: start; gap: 10px;">
                <ion-icon name="warning-outline" style="color: #ffc107; font-size: 1.2rem; flex-shrink: 0;"></ion-icon>
                <p style="margin: 0; font-size: 0.85rem; color: #856404; font-weight: 500;">
                    <strong>Disclaimer:</strong> Returns are only accepted on the same day you receive the product.
                </p>
            </div>
            
            <button type="submit" class="btn-submit-return">Submit Return Request</button>
        </form>
    </div>
</div>

<script>
function openOrderSummary(order) {
    document.getElementById('orderSummaryModal').style.display = 'flex';
    
    // Populate Data
    let images = [];
    try { images = JSON.parse(order.image_paths); } catch(e) {}
    document.getElementById('summaryImg').src = images[0] || 'https://via.placeholder.com/100';
    document.getElementById('summaryTitle').textContent = order.product_title;
    document.getElementById('summarySeller').textContent = 'Seller: ' + order.seller_name;
    document.getElementById('summaryPrice').textContent = '₹' + order.amount;
    document.getElementById('summaryStatus').textContent = order.order_status || 'Processing';
    document.getElementById('summaryDate').textContent = new Date(order.created_at).toLocaleDateString();
    document.getElementById('summaryId').textContent = '#' + order.id;
    
    if(order.delivery_date) {
        document.getElementById('summaryDeliveryRow').style.display = 'flex';
        document.getElementById('summaryDelivery').textContent = new Date(order.delivery_date).toLocaleDateString();
    } else {
        document.getElementById('summaryDeliveryRow').style.display = 'none';
    }
}

function closeOrderSummary() {
    document.getElementById('orderSummaryModal').style.display = 'none';
}

function openReturnModal(order) {
    document.getElementById('returnOrderId').value = order.id;
    document.getElementById('returnProductId').value = order.product_id;
    document.getElementById('returnModal').style.display = 'flex';
}
</script>

<style>
.summary-header {
    padding: 20px; display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid #f3f4f6;
}
.summary-header h3 { margin: 0; font-size: 1.1rem; color: #1f2937; }
.close-summary { font-size: 1.5rem; color: #9ca3af; cursor: pointer; line-height: 1; }
.summary-body { padding: 20px; }
.summary-product { display: flex; gap: 15px; margin-bottom: 20px; }
.summary-thumb { width: 70px; height: 70px; border-radius: 8px; object-fit: cover; background: #f9fafb; display: block; }
.summary-details h4 { margin: 0 0 5px; font-size: 1rem; color: #111827; }
.summary-details p { margin: 0 0 5px; font-size: 0.85rem; color: #6b7280; }
.summary-price { font-weight: 700; color: #111827; font-size: 1.1rem; }
.summary-status-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px; background: #f9fafb; border-radius: 8px; margin-bottom: 10px;
}
.summary-status-row span:first-child { font-weight: 500; color: #4b5563; }
.summary-info-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 0.9rem; color: #6b7280;
}
.summary-info-row:last-child { border-bottom: none; }
</style>

<script>
function openOrderSummary(order) {
    if(typeof order === 'string') {
        try {
            order = JSON.parse(order);
        } catch(e) { console.error('Error parsing order JSON', e); return; }
    }
    
    // Populate Data
    document.getElementById('summaryTitle').innerText = order.product_title || 'Unknown Product';
    document.getElementById('summarySeller').innerText = 'Seller: ' + (order.seller_name || 'Unknown');
    document.getElementById('summaryPrice').innerText = '₹' + (Number(order.amount).toLocaleString('en-IN') || 0);
    
    // Status
    const status = order.order_status || 'Processing';
    const badge = document.getElementById('summaryStatus');
    badge.innerText = status;
    // Map status to class
    let statusClass = 'pending';
    if(status.toLowerCase().includes('delivered') || status.toLowerCase().includes('completed')) statusClass = 'approved';
    if(status.toLowerCase().includes('cancel') || status.toLowerCase().includes('fail')) statusClass = 'sold'; // reusing red
    
    badge.className = 'status-badge ' + statusClass;
    
    // Image
    let imgPath = 'https://via.placeholder.com/100';
    if(order.image_paths) {
        try {
            const images = typeof order.image_paths === 'string' ? JSON.parse(order.image_paths) : order.image_paths;
            if(Array.isArray(images) && images.length > 0) imgPath = images[0];
        } catch(e) {}
    }
    document.getElementById('summaryImg').src = imgPath;
    
    // Extra Info
    document.getElementById('summaryDate').innerText = order.created_at ? new Date(order.created_at).toLocaleDateString() : '-';
    document.getElementById('summaryId').innerText = '#' + (order.id || '-');

    // Delivery Date
    let deliveryDate = order.delivery_date;
    if(!deliveryDate && order.created_at) {
        // Default to 7 days after order
        const created = new Date(order.created_at);
        created.setDate(created.getDate() + 7);
        deliveryDate = created;
    }

    const deliveryRow = document.getElementById('summaryDeliveryRow');
    if(deliveryDate) {
        // Handle both string (from DB) and Date object (calculated)
        const dDate = new Date(deliveryDate);
        if(!isNaN(dDate.getTime())) {
             document.getElementById('summaryDelivery').innerText = dDate.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
             deliveryRow.style.display = 'flex';
        } else {
             deliveryRow.style.display = 'none';
        }
    } else {
         deliveryRow.style.display = 'none';
    }

    // Show
    document.getElementById('orderSummaryModal').style.display = 'flex';
}

function closeOrderSummary() {
    document.getElementById('orderSummaryModal').style.display = 'none';
}

window.openProfileModal = function() {
    const modal = document.getElementById('editProfileModal');
    if(modal) {
        modal.style.display = 'flex';
    } else {
        console.error('Profile modal not found');
        alert('Error: Profile modal could not be loaded.');
    }
}

window.closeProfileModal = function() {
    const modal = document.getElementById('editProfileModal');
    if(modal) modal.style.display = 'none';
}

function shareListing(id, title) {
    if (navigator.share) {
        navigator.share({
            title: title,
            text: 'Check out this ' + title + ' on Listaria!',
            url: window.location.origin + '/product_details.php?id=' + id,
        })
        .then(() => console.log('Successful share'))
        .catch((error) => console.log('Error sharing', error));
    } else {
        // Fallback
        const url = window.location.origin + '/product_details.php?id=' + id;
        navigator.clipboard.writeText(url).then(function() {
            alert('Link copied to clipboard!');
        }, function(err) {
            console.error('Could not copy text: ', err);
            prompt('Copy this link:', url);
        });
    }
}
</script>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="edit-modal-overlay" style="display:none;">
    <div class="edit-modal-content">
        <div class="edit-header">
            <h3>Complete Profile</h3>
            <span class="close-edit" onclick="closeProfileModal()">&times;</span>
        </div>
        <div class="edit-body">
            <form method="POST" action="">
                <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">
                    Add your contact details to speed up checkout for future purchases.
                </p>
                
                <div class="form-group" style="margin-bottom:15px;">
                    <label style="font-weight:600; font-size:0.9rem; color:#333; display:block; margin-bottom:5px;">Phone Number</label>
                    <input type="tel" name="phone" class="modal-input" placeholder="10-digit number" 
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                           pattern="\d{10}" title="Enter 10 digit number" required>
                </div>

                <div class="form-group" style="margin-bottom:15px; position:relative;">
                    <label style="font-weight:600; font-size:0.9rem; color:#333; display:block; margin-bottom:5px;">Delivery Address</label>
                    
                    <!-- Search Input -->
                    <input type="text" id="profile_addr_search" class="modal-input" placeholder="Search Area (e.g. Bangalore)" style="margin-bottom:10px;" autocomplete="off">
                    <div id="profile_addr_suggestions" class="suggestions-dropdown" style="display:none;"></div>

                    <textarea name="address" id="profile_final_address" class="modal-input" rows="4" placeholder="Enter your full address" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>

                <style>
                    /* Reusing/Adding Suggestion Styles */
                    .suggestions-dropdown {
                        position: absolute; top: 70px; left: 0; width: 100%;
                        background: white; border: 1px solid #ddd; border-radius: 8px;
                        box-shadow: 0 4px 15px rgba(0,0,0,0.1); z-index: 1000;
                        max-height: 200px; overflow-y: auto;
                    }
                    .suggestion-item {
                        padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f9f9f9;
                        display: flex; gap: 10px; align-items: flex-start;
                        font-size: 0.9rem; color: #333;
                    }
                    .suggestion-item:hover { background: #f1f5f9; }
                </style>

                <script>
                    (function(){
                        const searchInput = document.getElementById('profile_addr_search');
                        const resultsBox = document.getElementById('profile_addr_suggestions');
                        const finalBox = document.getElementById('profile_final_address');
                        let debounceTimer;

                        if(searchInput) {
                            searchInput.addEventListener('input', function() {
                                clearTimeout(debounceTimer);
                                const query = this.value.trim();
                                if (query.length < 3) { resultsBox.style.display = 'none'; return; }
                                debounceTimer = setTimeout(() => fetchAddress(query), 300);
                            });
                        }

                        function fetchAddress(query) {
                            // Optimized for India (countrycodes=in)
                            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&addressdetails=1&limit=5&countrycodes=in`)
                                .then(r => r.json())
                                .then(data => {
                                    resultsBox.innerHTML = '';
                                    if (data.length > 0) {
                                        data.forEach(place => {
                                            const div = document.createElement('div');
                                            div.className = 'suggestion-item';
                                            div.innerHTML = `<span>📍</span><div>${place.display_name}</div>`;
                                            div.onclick = () => {
                                                finalBox.value = place.display_name;
                                                resultsBox.style.display = 'none';
                                                searchInput.value = ''; 
                                            };
                                            resultsBox.appendChild(div);
                                        });
                                        resultsBox.style.display = 'block';
                                    } else { resultsBox.style.display = 'none'; }
                                })
                                .catch(e => console.error(e));
                        }
                        
                        // Close suggestions on click outside
                        document.addEventListener('click', function(e) {
                            if (searchInput && !searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
                                resultsBox.style.display = 'none';
                            }
                        });
                    })();
                </script>

                <button type="submit" name="update_profile" class="btn-primary" style="width:100%;">Save Details</button>
            </form>
        </div>
    </div>
</div>

<!-- Return Summary Modal Styles -->
<style>
/* Ensure Modal is above bottom nav (z-index 10000) */
.modal {
    display: none; position: fixed; z-index: 10010; 
    left: 0; top: 0; width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.5); align-items: flex-end; justify-content: center;
    backdrop-filter: blur(2px);
}
.modal-content {
    background-color: #fff; width: 100%; max-width: 600px; border-radius: 20px 20px 0 0;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.2); animation: slideUp 0.3s ease-out; overflow: hidden;
    /* Add padding for Safe Area + Bottom Nav */
    padding-bottom: 20px; 
    max-height: 85vh; display: flex; flex-direction: column;
}
@media (max-width: 768px) {
    .modal-content {
        padding-bottom: 90px; /* Clear the bottom nav */
    }
}
@media (min-width: 768px) {
    .modal { align-items: center; }
    .modal-content { border-radius: 16px; width: 90%; max-width: 500px; padding-bottom: 0; }
}
/* Animation */
@keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>

<!-- Return Summary Modal -->
<div id="returnSummaryModal" class="modal" style="display:none; align-items:flex-end;">
    <div class="modal-content" style="border-radius:20px 20px 0 0; max-height:85vh; overflow-y:auto;">
        <div class="summary-header">
            <h3>Return Status</h3>
            <span class="close-summary" onclick="closeReturnSummary()">&times;</span>
        </div>
        <div class="summary-body">
            <div class="summary-product">
                <img id="rsImage" src="" class="summary-thumb" alt="Product">
                <div class="summary-details">
                    <h4 id="rsTitle">Product Title</h4>
                    <p id="rsSeller">Seller: Name</p>
                    <p id="rsId" style="font-size:0.75rem; color:#999;">ID: #123</p>
                </div>
            </div>

            <!-- Status Timeline / Banner -->
            <div class="summary-status-row" id="rsStatusRow" style="background:#f3f4f6;">
                <span id="rsStatusText">Pending</span>
                <ion-icon id="rsStatusIcon" name="time-outline" style="font-size:1.2rem;"></ion-icon>
            </div>

            <!-- Key Info -->
            <div class="summary-info-row">
                <span>Request Date</span>
                <span id="rsDate">Jan 1, 2026</span>
            </div>
            <div class="summary-info-row" id="rsPickupRow" style="display:none;">
                <span>Pickup Scheduled</span>
                <span id="rsPickupDate" style="font-weight:600; color:#075985;"></span>
            </div>
            <div class="summary-info-row" id="rsExpectedRow" style="display:none;">
                <span>Expected Return</span>
                <span id="rsExpectedDate" style="font-weight:600; color:#166534;"></span>
            </div>
             <div class="summary-info-row">
                <span>Reason</span>
                <span id="rsReason">Defective</span>
            </div>

            <div style="margin-top:15px; background:#f9f9f9; padding:12px; border-radius:8px;">
                <p style="margin:0; font-size:0.8rem; color:#666;">Your Comments:</p>
                <p id="rsDetails" style="margin:5px 0 0; font-size:0.9rem; color:#333; font-style:italic;">"Details..."</p>
            </div>
             
             <div style="text-align:center; margin-top:20px; font-size:0.8rem; color:#888;">
                <p>Need help? <a href="#" style="color:#6B21A8;">Contact Support</a></p>
            </div>
        </div>
    </div>
</div>

<script>
function openVendorSettings() {
    window.location.href = 'vendor_settings.php';
}

function openReturnSummary(id) {
    console.log('Opening return summary for ID:', id);
    // Ensure map exists
    if(typeof window.returnsMap === 'undefined') {
        console.error('returnsMap is undefined');
        alert('Error: Return data not loaded.');
        return;
    }
    const data = window.returnsMap[id];
    if(!data) { 
        console.error('Return data not found for ID:', id, window.returnsMap); 
        alert('Error: Details not found for this return.');
        return; 
    }
    document.getElementById('rsImage').src = data.image;
    document.getElementById('rsTitle').textContent = data.title;
    document.getElementById('rsSeller').textContent = 'Seller: ' + data.seller;
    document.getElementById('rsId').textContent = 'Return ID: #' + data.id;
    document.getElementById('rsStatusText').textContent = data.statusText;
    document.getElementById('rsDate').textContent = data.created_at;
    document.getElementById('rsReason').textContent = data.reason;
    document.getElementById('rsDetails').textContent = '"' + data.details + '"';

    // Status Styling
    const statusRow = document.getElementById('rsStatusRow');
    const icon = document.getElementById('rsStatusIcon');
    let bg = '#f3f4f6'; let col = '#1f2937'; let iconName = 'time-outline';

    if(data.status == 'pending') { bg='#fef9c3'; col='#854d0e'; iconName='time-outline'; }
    if(data.status == 'approved') { bg='#dcfce7'; col='#166534'; iconName='checkmark-circle-outline'; }
    if(data.status == 'rejected') { bg='#fee2e2'; col='#991b1b'; iconName='close-circle-outline'; }
    if(data.status == 'pickup_scheduled') { bg='#e0f2fe'; col='#075985'; iconName='calendar-outline'; }
    if(data.status == 'collected') { bg='#f3e8ff'; col='#6b21a8'; iconName='cube-outline'; }
    if(data.status == 'refunded') { bg='#dcfce7'; col='#166534'; iconName='cash-outline'; }

    statusRow.style.background = bg;
    statusRow.style.color = col;
    icon.name = iconName;

    // Pickup Date
    if(data.pickup_date) {
        document.getElementById('rsPickupRow').style.display = 'flex';
        document.getElementById('rsPickupDate').textContent = data.pickup_date;
    } else {
        document.getElementById('rsPickupRow').style.display = 'none';
    }

    // Expected Return Date
    if(data.expected_return_date) {
        document.getElementById('rsExpectedRow').style.display = 'flex';
        document.getElementById('rsExpectedDate').textContent = data.expected_return_date;
    } else {
        document.getElementById('rsExpectedRow').style.display = 'none';
    }

    document.getElementById('returnSummaryModal').style.display = 'flex';
}

function closeReturnSummary() {
    document.getElementById('returnSummaryModal').style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
