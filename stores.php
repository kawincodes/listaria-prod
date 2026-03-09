<?php
require 'includes/db.php';

// Fetch all public vendors
$stmt = $pdo->query("SELECT id, business_name, full_name, profile_image, business_bio 
                     FROM users 
                     WHERE account_type = 'vendor' AND is_public = 1 
                     ORDER BY business_name ASC");
$vendors = $stmt->fetchAll();

// Fetch item counts for each vendor to display
$vendor_counts = [];
$count_stmt = $pdo->query("SELECT user_id, COUNT(*) as item_count FROM products WHERE approval_status = 'approved' AND is_published = 1 GROUP BY user_id");
while($row = $count_stmt->fetch()) {
    $vendor_counts[$row['user_id']] = $row['item_count'];
}

include 'includes/header.php';
?>

<div class="container" style="max-width:1200px; padding: 100px 20px 40px;">
    <div class="page-header" style="margin-bottom: 40px; text-align: left;">
        <h1 style="font-size: 2.2rem; font-weight: 800; color: #1a1a1a; margin-bottom: 8px; font-family: 'Inter', sans-serif;">Our Stores</h1>
        <p style="color: #666; font-size: 1rem; line-height: 1.5;">Discover specialized boutiques and curated collections from our verified vendors.</p>
    </div>

    <?php if (count($vendors) > 0): ?>
        <div class="stores-grid">
            <?php foreach ($vendors as $v): 
                $name = !empty($v['business_name']) ? $v['business_name'] : $v['full_name'];
                $item_count = $vendor_counts[$v['id']] ?? 0;
            ?>
                <div class="store-card-wrapper">
                    <a href="vendor.php?id=<?php echo $v['id']; ?>&view=normal" class="store-card">
                        <!-- Store Image / Logo -->
                        <div class="store-image-container">
                            <?php if (!empty($v['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($v['profile_image']); ?>" alt="<?php echo htmlspecialchars($name); ?>">
                            <?php else: ?>
                                <div class="logo-fallback"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
                            <?php endif; ?>
                            <span class="verify-badge-top">
                                <ion-icon name="checkmark-circle"></ion-icon>
                            </span>
                        </div>

                        <!-- Store Title & Info -->
                        <div class="store-info">
                            <h3 class="store-name"><?php echo htmlspecialchars($name); ?></h3>
                            <div class="store-meta">
                                <p class="item-count"><?php echo $item_count; ?> Products</p>
                                <p class="store-type">Professional Vendor</p>
                            </div>
                            
                            <div class="btn-visit">
                                Visit Store
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <ion-icon name="storefront-outline"></ion-icon>
            <h3>No stores found</h3>
            <p>Check back later as our community grows!</p>
        </div>
    <?php endif; ?>
</div>

<style>
    /* Clean Product-like Design */
    .stores-grid {
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
        gap: 20px;
    }

    .store-card {
        display: block;
        text-decoration: none;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #f0f0f0;
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }

    .store-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.08);
    }

    .store-image-container {
        position: relative;
        width: 100%;
        aspect-ratio: 1/1;
        background: #fdfdfd;
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid #f0f0f0;
    }

    .store-image-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .logo-fallback {
        font-size: 3rem;
        font-weight: 800;
        color: #6B21A8;
        font-family: 'Inter', sans-serif;
    }

    .verify-badge-top {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: #fff;
        color: #10b981;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        font-size: 1.2rem;
    }

    .store-info {
        padding: 15px;
    }

    .store-name {
        margin: 0 0 5px;
        font-size: 1.1rem;
        font-weight: 700;
        color: #1a1a1a;
        font-family: 'Inter', sans-serif;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .store-meta {
        margin-bottom: 15px;
    }

    .item-count {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
        color: #1a1a1a;
    }

    .store-type {
        margin: 2px 0 0;
        font-size: 0.8rem;
        color: #888;
        font-weight: 500;
    }

    .btn-visit {
        background: #f8fafc;
        color: #1a1a1a;
        text-align: center;
        padding: 8px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 700;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }

    .store-card:hover .btn-visit {
        background: #1a1a1a;
        color: #fff;
        border-color: #1a1a1a;
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #999;
    }

    @media (max-width: 768px) {
        .stores-grid { 
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            padding: 5px;
        }
        .container { padding-top: 80px; }
        .page-header h1 { font-size: 1.6rem; }
        .store-info { padding: 10px; }
        .store-name { font-size: 0.95rem; }
        .item-count { font-size: 0.9rem; }
        .btn-visit { font-size: 0.8rem; padding: 6px; }
    }
</style>

<?php include 'includes/footer.php'; ?>
