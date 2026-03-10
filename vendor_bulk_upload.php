<?php
require_once __DIR__ . '/includes/session.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
if (!$isAdmin && ($_SESSION['account_type'] ?? 'customer') !== 'vendor') {
    header("Location: profile.php");
    exit;
}

require 'includes/db.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$isAdmin && empty($user['is_verified_vendor']) && ($user['vendor_status'] ?? '') !== 'approved') {
    header("Location: profile.php?msg=vendor_not_verified");
    exit;
}

$successCount = isset($_GET['success']) ? (int)$_GET['success'] : null;
$failCount = isset($_GET['fail']) ? (int)$_GET['fail'] : null;
$showResult = isset($_GET['msg']) && $_GET['msg'] === 'bulk_complete';

include 'includes/header.php';
?>

<style>
    .profile-page-wrapper {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    .profile-header-section {
        background: linear-gradient(135deg, var(--brand-dark, #4c1d95) 0%, var(--brand-color, #6B21A8) 100%);
        color: white;
        padding: 20px;
        padding-top: 80px;
    }
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .icon-btn.circle-white {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        font-size: 1.2rem;
    }
</style>

<div class="profile-page-wrapper">
    <div class="profile-header-section" style="padding-bottom: 20px;">
        <div class="header-actions">
            <a href="profile.php" class="icon-btn circle-white"><ion-icon name="chevron-back-outline"></ion-icon></a>
        </div>
        <div class="user-details" style="margin-top: 10px;">
            <h1 style="color:white; margin:0; font-size:1.5rem;">Bulk Upload Products</h1>
            <p style="color:#ddd6fe; margin:5px 0 0; font-size:0.9rem;">Upload multiple products at once using a CSV file.</p>
        </div>
    </div>

    <div style="max-width:700px; margin:-20px auto 2rem; padding:0 1rem;">

        <?php if ($showResult): ?>
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:20px; border-radius:16px; margin-bottom:20px; text-align:center;">
            <ion-icon name="checkmark-circle-outline" style="font-size:2.5rem; color:#22c55e; margin-bottom:10px;"></ion-icon>
            <h3 style="margin:0 0 8px; color:#14532d;">Upload Complete!</h3>
            <p style="color:#166534; margin:0;">
                <strong><?php echo $successCount; ?></strong> product(s) listed successfully.
                <?php if ($failCount > 0): ?>
                    <br><strong><?php echo $failCount; ?></strong> product(s) failed.
                <?php endif; ?>
            </p>
            <p style="color:#15803d; font-size:0.85rem; margin-top:10px;">Successfully listed products are now pending approval from our team.</p>
        </div>
        <?php endif; ?>

        <div style="background:#fff; border-radius:16px; padding:25px; box-shadow:0 2px 12px rgba(0,0,0,0.06); margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #f0f0f0;">
                <ion-icon name="cloud-upload-outline" style="font-size:1.4rem; color:#6B21A8;"></ion-icon>
                <span style="font-weight:600; font-size:1.1rem; color:#1a1a1a;">How It Works</span>
            </div>
            <div style="display:flex; flex-direction:column; gap:16px;">
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <div style="width:28px; height:28px; border-radius:50%; background:#f3e8ff; color:#6B21A8; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; flex-shrink:0;">1</div>
                    <div>
                        <strong style="font-size:0.9rem; color:#1a1a1a;">Download the CSV Template</strong>
                        <p style="font-size:0.8rem; color:#666; margin:4px 0 0;">Get the pre-formatted template with all required column headers.</p>
                    </div>
                </div>
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <div style="width:28px; height:28px; border-radius:50%; background:#f3e8ff; color:#6B21A8; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; flex-shrink:0;">2</div>
                    <div>
                        <strong style="font-size:0.9rem; color:#1a1a1a;">Fill In Your Products</strong>
                        <p style="font-size:0.8rem; color:#666; margin:4px 0 0;">Add one product per row. Include title, brand, category, condition, location, description, price, and image filenames.</p>
                    </div>
                </div>
                <div style="display:flex; align-items:flex-start; gap:12px;">
                    <div style="width:28px; height:28px; border-radius:50%; background:#f3e8ff; color:#6B21A8; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; flex-shrink:0;">3</div>
                    <div>
                        <strong style="font-size:0.9rem; color:#1a1a1a;">Upload CSV + Images</strong>
                        <p style="font-size:0.8rem; color:#666; margin:4px 0 0;">Upload the CSV file along with all referenced product images. Image filenames must match exactly.</p>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align:center; margin-bottom:20px;">
            <a href="api/download_template.php" style="display:inline-flex; align-items:center; gap:8px; padding:12px 24px; border:2px solid #6B21A8; border-radius:30px; text-decoration:none; color:#6B21A8; font-weight:600; font-size:0.9rem; transition:all 0.2s;">
                <ion-icon name="download-outline"></ion-icon> Download CSV Template
            </a>
        </div>

        <div style="background:#fff; border-radius:16px; padding:25px; box-shadow:0 2px 12px rgba(0,0,0,0.06); margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #f0f0f0;">
                <ion-icon name="document-text-outline" style="font-size:1.4rem; color:#6B21A8;"></ion-icon>
                <span style="font-weight:600; font-size:1.1rem; color:#1a1a1a;">CSV Format Guide</span>
            </div>

            <div style="background:#fff1f2; padding:15px; border-radius:12px; border:1px solid #fecaca; margin-bottom:15px;">
                <strong style="display:block; margin-bottom:8px; font-size:0.9rem; color:#dc2626;">Important Rules:</strong>
                <ul style="margin:0; padding-left:20px; line-height:1.7; font-size:0.85rem; color:#7f1d1d;">
                    <li>Fill all required columns: <b>Title, Brand, Category, Condition, Location, Price</b>.</li>
                    <li><b>Category</b> must be one of: Tops, Bottoms, Jackets, Shoes, Bags, Accessories, Phones, Laptops, Fashion, Books, Home, Gaming, Sports, Kids, or Others.</li>
                    <li><b>Condition</b> must be: Brand New, Lightly Used, or Regularly Used.</li>
                    <li><b>Image Filenames</b>: List names separated by commas (e.g., img1.jpg, img2.png).</li>
                    <li>Filenames in the CSV must <u>exactly match</u> the image files you upload.</li>
                    <li><b>Quantity</b> (column 9) is optional — defaults to 1 if not provided.</li>
                </ul>
            </div>

            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.8rem; min-width:600px;">
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th style="padding:8px 10px; text-align:left; border-bottom:2px solid #e5e7eb; color:#6B21A8; font-weight:600;">Column</th>
                            <th style="padding:8px 10px; text-align:left; border-bottom:2px solid #e5e7eb; color:#6B21A8; font-weight:600;">Required</th>
                            <th style="padding:8px 10px; text-align:left; border-bottom:2px solid #e5e7eb; color:#6B21A8; font-weight:600;">Example</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Title</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Yes</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0; color:#666;">Vintage Levi's Jacket</td></tr>
                        <tr><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Brand</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Yes</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0; color:#666;">Levi's</td></tr>
                        <tr><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Category</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Yes</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0; color:#666;">Jackets</td></tr>
                        <tr><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Condition</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Yes</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0; color:#666;">Lightly Used</td></tr>
                        <tr><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Location</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Yes</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0; color:#666;">Bangalore</td></tr>
                        <tr><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Description</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">No</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0; color:#666;">Great condition vintage jacket</td></tr>
                        <tr><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Price</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Yes</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0; color:#666;">1500</td></tr>
                        <tr><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Image Filenames</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">No</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0; color:#666;">jacket1.jpg, jacket2.jpg</td></tr>
                        <tr><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">Quantity</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0;">No</td><td style="padding:8px 10px; border-bottom:1px solid #f0f0f0; color:#666;">5</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="background:#fff; border-radius:16px; padding:25px; box-shadow:0 2px 12px rgba(0,0,0,0.06); margin-bottom:20px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #f0f0f0;">
                <ion-icon name="rocket-outline" style="font-size:1.4rem; color:#6B21A8;"></ion-icon>
                <span style="font-weight:600; font-size:1.1rem; color:#1a1a1a;">Upload Your Products</span>
            </div>

            <form action="api/bulk_upload.php" method="POST" enctype="multipart/form-data" id="bulk-upload-form">
                <div style="margin-bottom:20px;">
                    <label style="font-weight:600; margin-bottom:8px; display:block; font-size:0.9rem; color:#1a1a1a;">
                        <ion-icon name="document-attach-outline" style="vertical-align:middle; margin-right:4px; color:#6B21A8;"></ion-icon>
                        Step 1: Select CSV File
                    </label>
                    <input type="file" name="bulk_file" accept=".csv" required id="csv-file-input" style="width:100%; padding:12px; border:2px dashed #d8b4fe; border-radius:12px; background:#faf5ff; font-size:0.9rem; cursor:pointer;">
                </div>

                <div style="margin-bottom:20px;">
                    <label style="font-weight:600; margin-bottom:8px; display:block; font-size:0.9rem; color:#1a1a1a;">
                        <ion-icon name="images-outline" style="vertical-align:middle; margin-right:4px; color:#6B21A8;"></ion-icon>
                        Step 2: Select Product Images
                    </label>
                    <p style="font-size:0.8rem; color:#666; margin:0 0 8px;">Select all image files referenced in your CSV. Filenames must match exactly.</p>
                    <input type="file" name="bulk_images[]" multiple accept="image/*" id="images-input" style="width:100%; padding:12px; border:2px dashed #d8b4fe; border-radius:12px; background:#faf5ff; font-size:0.9rem; cursor:pointer;">
                    <div id="image-count" style="font-size:0.8rem; color:#6B21A8; margin-top:6px; display:none;"></div>
                </div>

                <div id="upload-progress" style="display:none; margin-bottom:20px; text-align:center; padding:20px; background:#f3e8ff; border-radius:12px;">
                    <div style="display:inline-block; width:24px; height:24px; border:3px solid #d8b4fe; border-top-color:#6B21A8; border-radius:50%; animation:spin 0.8s linear infinite; margin-bottom:10px;"></div>
                    <p style="margin:0; color:#6B21A8; font-weight:600;">Uploading and processing your products...</p>
                    <p style="margin:5px 0 0; font-size:0.8rem; color:#7c3aed;">This may take a moment. Please don't close this page.</p>
                </div>

                <button type="submit" id="submit-btn" style="width:100%; padding:14px; background:linear-gradient(135deg, #6B21A8, #9333EA); color:white; border:none; border-radius:12px; font-weight:700; font-size:1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all 0.2s;">
                    <ion-icon name="cloud-upload-outline"></ion-icon> Start Bulk Upload
                </button>
            </form>
        </div>

    </div>
</div>

<style>
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const imagesInput = document.getElementById('images-input');
    const imageCount = document.getElementById('image-count');
    const form = document.getElementById('bulk-upload-form');
    const submitBtn = document.getElementById('submit-btn');
    const progress = document.getElementById('upload-progress');

    if (imagesInput) {
        imagesInput.addEventListener('change', function() {
            const count = this.files.length;
            if (count > 0) {
                imageCount.style.display = 'block';
                imageCount.textContent = count + ' image(s) selected';
            } else {
                imageCount.style.display = 'none';
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
            submitBtn.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> Uploading...';
            if (progress) progress.style.display = 'block';
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
