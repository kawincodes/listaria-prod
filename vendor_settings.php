<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['account_type'] ?? 'customer') !== 'vendor') {
    header("Location: profile.php");
    exit;
}

require 'includes/db.php';

$user_id = $_SESSION['user_id'];
$message = '';

// Fetch User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch Total Product Loves
$stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE product_id IN (SELECT id FROM products WHERE user_id = ?)");
$stmt->execute([$user_id]);
$total_loves = $stmt->fetchColumn();

// Fetch Total Product Views
$stmt = $pdo->prepare("SELECT SUM(views) FROM products WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_product_views = $stmt->fetchColumn() ?: 0;

include 'includes/header.php';
?>

<div class="profile-page-wrapper">
    <!-- Top Dark Section -->
    <div class="profile-header-section" style="padding-bottom: 20px;">
        <!-- Header Actions: Back -->
        <div class="header-actions">
            <a href="profile.php" class="icon-btn circle-white"><ion-icon name="chevron-back-outline"></ion-icon></a>
        </div>

        <!-- Title -->
        <div class="user-details" style="margin-top: 10px;">
            <h1 style="color:white; margin:0; font-size:1.5rem;">Vendor Settings</h1>
            <p style="color:#ddd6fe; margin:5px 0 0; font-size:0.9rem;">Manage your business profile and analytics.</p>
        </div>
    </div>

    <!-- White Bottom Section -->
    <div class="profile-content-section" style="padding-top:20px;">
        <div class="business-settings-card" style="background: var(--surface-color); padding: 20px; border-radius: 12px; border: 1px solid var(--border-light); margin-bottom: 20px;">
            <div class="card-header" style="border-bottom: 1px solid var(--border-light); margin-bottom: 20px; padding-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                <ion-icon name="business-outline" style="font-size: 1.4rem; color: var(--brand-color);"></ion-icon>
                <span style="font-weight: 600; font-size: 1.1rem; color: var(--primary-text);">Business Profile</span>
                <button onclick="togglePublicPageModal()" class="public-link-badge" style="margin-left: auto; font-size: 0.8rem; background: var(--brand-light); color: white; padding: 4px 10px; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 4px;">View Public Page <ion-icon name="open-outline"></ion-icon></button>
            </div>
            
            <form method="POST" action="update_vendor_profile.php" enctype="multipart/form-data">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 5px;">Thrift Store / Business Name</label>
                    <input type="text" name="business_name" class="form-input" value="<?php echo htmlspecialchars($user['business_name'] ?? ''); ?>" placeholder="e.g. Luxury Vintage Hub" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 5px;">Store Photo / Logo (Square format recommended)</label>
                    <?php if(!empty($user['profile_image'])): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Store Photo" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 2px solid #ddd;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="profile_image" accept="image/*" class="form-input" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
                    <p style="font-size: 0.8rem; color: #666; margin-top: 5px;">This will be displayed as your Store Icon on the Thrift page.</p>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 5px;">Business Bio</label>
                    <textarea name="business_bio" class="form-input" rows="3" placeholder="Tell buyers about your shop..." style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;"><?php echo htmlspecialchars($user['business_bio'] ?? ''); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 5px;">WhatsApp Number (For Buyers)</label>
                    <input type="tel" name="whatsapp_number" class="form-input" value="<?php echo htmlspecialchars($user['whatsapp_number'] ?? ''); ?>" placeholder="10-digit number" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
                </div>

                <div class="form-group" style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <label class="form-label" style="margin-bottom: 0; font-weight: 600; font-size: 0.9rem;">Public Profile Visible</label>
                    <input type="checkbox" name="is_public" value="1" <?php echo ($user['is_public'] ?? 0) ? 'checked' : ''; ?> style="width: 20px; height: 20px;">
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; background: var(--brand-color); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Update Business Info</button>
            </form>

            <hr style="margin: 25px 0; border: none; border-top: 1px solid var(--border-light);">
            
            <h4 style="margin: 0 0 10px; font-size: 1rem; color: #dc2626;">Danger Zone</h4>
            <p style="font-size: 0.85rem; color: var(--secondary-text); margin-bottom: 15px;">Deleting your vendor profile will revert your account back to a standard customer account and remove your public store page. This cannot be undone.</p>
            <form method="POST" action="profile.php" onsubmit="return confirm('Are you absolutely sure you want to delete your vendor profile? All seller privileges will be lost immediately.');">
                <input type="hidden" name="delete_vendor_profile" value="1">
                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; background: white; color: #dc2626; border: 1px solid #dc2626; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='white'">Delete Vendor Profile</button>
            </form>
        </div>

        <div class="analytics-card" style="background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 12px; padding: 20px;">
            <h3 style="margin: 0 0 15px; font-size: 1rem; color: var(--brand-color);">Store Analytics</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="background: white; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--border-light);">
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-text);"><?php echo number_format($total_product_views); ?></div>
                    <div style="font-size: 0.8rem; color: var(--secondary-text);">Product Views</div>
                </div>
                <div style="background: white; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid var(--border-light);">
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-text);"><?php echo number_format($total_loves ?: 0); ?></div>
                    <div style="font-size: 0.8rem; color: var(--secondary-text);">Product Loves</div>
                </div>
            </div>
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
    }

    /* Top Section */
    .profile-header-section {
        background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand-color) 100%);
        color: white;
        padding: 20px;
        padding-bottom: 20px;
    }

    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    /* Selection Modal */
    .selection-modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 20px;
    }
    .selection-modal {
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 400px;
        padding: 24px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .selection-option {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border_radius: 12px;
        margin-bottom: 12px;
        text-decoration: none;
        color: var(--primary-text);
        transition: all 0.2s;
    }
    .selection-option:hover {
        background: #f1f5f9;
        border-color: var(--brand-color);
        transform: translateY(-2px);
    }
    .selection-option ion-icon {
        font-size: 1.5rem;
        color: var(--brand-color);
    }
    .selection-option .opt-text {
        flex: 1;
    }
    .selection-option .opt-title {
        font-weight: 700;
        font-size: 1rem;
        display: block;
    }
    .selection-option .opt-desc {
        font-size: 0.8rem;
        color: var(--secondary-text);
    }

    .icon-btn.circle-white {
        width: 40px; height: 40px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        color: white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        text-decoration: none; font-size: 1.2rem;
        cursor: pointer;
        transition: background 0.2s;
    }
    .icon-btn.circle-white:hover { background: rgba(255, 255, 255, 0.3); }

    .user-details h1 { margin: 0; font-size: 1.25rem; font-weight: 700; color: white; }
    .user-details p { margin: 2px 0 0; font-size: 0.85rem; color: #ddd6fe; }

    /* Content Section */
    .profile-content-section {
        background-color: var(--bg-color); 
        border-top-left-radius: 24px;
        border-top-right-radius: 24px;
        flex: 1;
        padding: 20px;
        margin-top: -10px;
        transition: background-color 0.3s ease;
    }

    /* Hide Footer Visuals if needed */
    .site-footer { display: none; }
</style>

<!-- Selection Modal -->
<div id="publicPageModal" class="selection-modal-overlay" onclick="togglePublicPageModal()">
    <div class="selection-modal" onclick="event.stopPropagation()">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0; font-size:1.1rem;">Choose Listing View</h3>
            <ion-icon name="close-outline" style="font-size:1.5rem; cursor:pointer;" onclick="togglePublicPageModal()"></ion-icon>
        </div>
        
        <a href="vendor.php?id=<?php echo $user_id; ?>&view=normal" target="_blank" class="selection-option">
            <ion-icon name="grid-outline"></ion-icon>
            <div class="opt-text">
                <span class="opt-title">Normal Listing</span>
                <span class="opt-desc">View all your products on a standard page.</span>
            </div>
            <ion-icon name="chevron-forward-outline" style="font-size:1rem; color:#cbd5e1;"></ion-icon>
        </a>

        <a href="vendor.php?id=<?php echo $user_id; ?>&view=thrift" target="_blank" class="selection-option">
            <ion-icon name="sparkles-outline"></ion-icon>
            <div class="opt-text">
                <span class="opt-title">Thrift Listing</span>
                <span class="opt-desc">View only Fashion & Thrift items (Vibe check).</span>
            </div>
            <ion-icon name="chevron-forward-outline" style="font-size:1rem; color:#cbd5e1;"></ion-icon>
        </a>
    </div>
</div>

<script>
function togglePublicPageModal() {
    const modal = document.getElementById('publicPageModal');
    modal.style.display = modal.style.display === 'flex' ? 'none' : 'flex';
}
</script>

<?php include 'includes/footer.php'; ?>
