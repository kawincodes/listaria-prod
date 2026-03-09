<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'includes/db.php';
require 'includes/SimpleSMTP.php';

$message = '';

if (isset($_GET['msg']) && $_GET['msg'] === 'pending_approval') {
    $message = '<div class="alert success"><ion-icon name="checkmark-circle-outline"></ion-icon> Product listed successfully! It is now pending approval.</div>';
}


$is_thrift = (isset($_GET['source']) && $_GET['source'] == 'thrift');

// Check if vendor has required Thrift profile fields
if ($is_thrift && ($_SESSION['account_type'] ?? 'customer') === 'vendor') {
    $stmt = $pdo->prepare("SELECT business_name, profile_image FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $vendor_check = $stmt->fetch();
    
    if (empty($vendor_check['business_name']) || empty($vendor_check['profile_image'])) {
        header("Location: profile.php?msg=thrift_profile_required");
        exit;
    }
}

$vendors = [];
if (($_SESSION['account_type'] ?? '') === 'admin') {
    $stmt = $pdo->query("SELECT id, business_name, full_name FROM users WHERE account_type = 'vendor'");
    $vendors = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $brand = $_POST['brand'];
    $location = $_POST['location'];
    $condition = $_POST['condition_tag'];
    $description = $_POST['description'] ?? '';
    
    $price = $_POST['price'];
    $price_min = $price;
    $price_max = $price;

    $video_path = null;
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $video_upload_dir = 'uploads/videos/';
        if (!is_dir($video_upload_dir)) mkdir($video_upload_dir, 0755, true);
        
        $tmp_name = $_FILES['video']['tmp_name'];
        $name = basename($_FILES['video']['name']);
        $target_video = $video_upload_dir . uniqid() . '_' . $name;
        
        if (move_uploaded_file($tmp_name, $target_video)) {
            $video_path = $target_video;
        }
    }

    $uploaded_files = [];
    if (isset($_FILES['images'])) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $count = count($_FILES['images']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['images']['tmp_name'][$i];
                $name = basename($_FILES['images']['name'][$i]);
                $target_file = $upload_dir . uniqid() . '_' . $name;
                
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $uploaded_files[] = $target_file;
                }
            }
        }

        if (count($uploaded_files) >= 3) {
            $stmt = $pdo->prepare("INSERT INTO products (title, brand, category, location, video_path, condition_tag, description, price_min, price_max, image_paths, user_id, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $title, 
                $brand,
                $_POST['category'],
                $location,
                $video_path,
                $condition, 
                $description,
                $price_min, 
                $price_max, 
                json_encode($uploaded_files),
                $_SESSION['user_id']
            ]);
            
            // Send Email Notification
            try {
                // Fetch user email
                $uStmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
                $uStmt->execute([$_SESSION['user_id']]);
                $user = $uStmt->fetch();

                if ($user) {
                    $smtp = createSmtp($pdo);

                    $subject = "Product Listed Successfully: " . $title;
                    $body = "
                        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                            <h2 style='color: #6B21A8;'>Hello " . htmlspecialchars($user['full_name']) . "!</h2>
                            <p>Your product has been successfully listed on Listaria and is currently <strong>pending approval</strong>.</p>
                            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                            <h3 style='color: #6B21A8;'>Product Details:</h3>
                            <ul style='list-style: none; padding: 0;'>
                                <li><strong>Title:</strong> " . htmlspecialchars($title) . "</li>
                                <li><strong>Brand:</strong> " . htmlspecialchars($brand) . "</li>
                                <li><strong>Category:</strong> " . htmlspecialchars($_POST['category']) . "</li>
                                <li><strong>Price:</strong> ₹" . number_format($price, 2) . "</li>
                                <li><strong>Location:</strong> " . htmlspecialchars($location) . "</li>
                                <li><strong>Condition:</strong> " . htmlspecialchars($condition) . "</li>
                            </ul>
                            <p style='margin-top: 20px;'>Once our team approves your listing, it will be visible to thousands of buyers!</p>
                            <p>Thank you for choosing Listaria.</p>
                            <br>
                            <p style='font-size: 0.8rem; color: #999;'>This is an automated email, please do not reply.</p>
                        </div>
                    ";

                    $smtp->send($user['email'], $subject, $body, 'Listaria Support');
                }
            } catch (Exception $e) {
                error_log("Failed to send product listing email: " . $e->getMessage());
            }

            header("Location: sell.php?msg=pending_approval");
            exit;
        } else {
             $message = '<div class="alert error"><ion-icon name="alert-circle-outline"></ion-icon> You must upload at least 3 images.</div>';
        }
    }
}

include 'includes/header.php';
?>

<?php if (isset($_GET['source']) && $_GET['source'] === 'thrift'): ?>
<style>
    /* Thrift+ Theme Overrides - Sell Page */
    body { 
        background-color: #eae4cc !important; 
        font-family: 'Courier New', monospace !important;
    }
    
    .sell-page {
        background: transparent !important;
    }
    
    .sell-container {
        background: #fdfcf8 !important;
        border: 2px solid #1a1a1a !important;
        box-shadow: 8px 8px 0 rgba(26,26,26,0.9) !important;
        border-radius: 0 !important;
    }
    
    h1, h2, .form-label, .btn-primary, .upload-label {
        font-family: 'Courier New', monospace !important;
        color: #1a1a1a !important;
    }
    
    h1 {
        font-family: 'Times New Roman', serif !important;
        font-weight: 800 !important;
        text-transform: uppercase;
    }

    /* Form Elements */
    .form-input, .form-select, textarea, .btn-primary, .drop-zone {
        border: 2px solid #1a1a1a !important;
        border-radius: 0 !important;
        box-shadow: 4px 4px 0 rgba(26,26,26,0.1) !important;
    }
    
    .form-input:focus, .form-select:focus, textarea:focus {
        box-shadow: 4px 4px 0 rgba(26,26,26,0.9) !important;
        background: #fff !important;
    }

    /* Buttons */
    .btn-primary {
        background: #1a1a1a !important;
        color: #fff !important;
        text-transform: uppercase;
        font-weight: 800 !important;
        box-shadow: 4px 4px 0 rgba(26,26,26,0.9) !important;
    }

    .btn-primary:hover {
        transform: translate(-2px, -2px);
        box-shadow: 6px 6px 0 rgba(26,26,26,0.9) !important;
    }
    
    /* Drop Zone */
    .drop-zone {
        background: #fdfcf8 !important;
        border: 2px dashed #1a1a1a !important;
    }
    
    .sell-header p {
        font-family: 'Courier New', monospace !important;
    }
</style>
<?php endif; ?>

<?php
// $is_thrift is already defined at the top
?>

<div class="sell-page" <?php if($is_thrift) echo 'style="background: #fdfcf8;"'; ?>>
    <div class="sell-container">
        <div class="sell-header">
            <?php if($is_thrift): ?>
                 <!-- Thrift Branding -->
                 <div style="margin-bottom:1rem;">
                    <span style="background:#a8c6a0; color:#1a1a1a; padding:5px 15px; border-radius:4px; font-weight:bold; border:1px solid #1a1a1a; transform:rotate(-2deg); display:inline-block;">Thrift+ Mode</span>
                 </div>
                 <h1 style="font-family:'Inter', sans-serif;">Sell on Thrift+</h1>
                 <p>Give your clothes a second life. List in seconds.</p>
            <?php else: ?>
                <h1>Sell Your Item</h1>
                <p>List your product and reach thousands of buyers</p>
            <?php endif; ?>
        </div>
        
        <?php echo $message; ?>

        <div class="upload-tabs" style="display:flex; margin-bottom:20px; border-bottom:1px solid #eee;">
            <div class="u-tab active" id="tab-single-btn" onclick="switchUploadTab('single')" style="padding:10px 20px; cursor:pointer; font-weight:600; border-bottom:2px solid #1a1a1a;">Single Item</div>
            <div class="u-tab" id="tab-bulk-btn" onclick="switchUploadTab('bulk')" style="padding:10px 20px; cursor:pointer; font-weight:600; color:#666;">Bulk Upload (CSV)</div>
        </div>

        <div id="single-upload-section">
            <form action="sell.php" method="POST" enctype="multipart/form-data" id="sell-form">
            <div class="form-card">
                <div class="card-header">
                    <ion-icon name="information-circle-outline" style="<?php if($is_thrift) echo 'color:#27ae60;'; ?>"></ion-icon>
                    <span>Basic Information</span>
                </div>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">Product Title</label>
                        <input type="text" name="title" class="form-input" required placeholder="<?php echo $is_thrift ? 'e.g. Vintage Levi\'s Jacket' : 'e.g. iPhone 13 Pro Max'; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-input" required placeholder="<?php echo $is_thrift ? 'e.g. Zara, H&M, Levi\'s' : 'e.g. Apple'; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <?php if($is_thrift): ?>
                            <!-- Thrift Categories -->
                            <select name="category" class="form-select" required style="font-family: 'Courier New', monospace !important;">
                                <option value="" disabled selected>Select Category</option>
                                <option value="Tops">Tops</option>
                                <option value="Bottoms">Bottoms</option>
                                <option value="Jackets">Jackets</option>
                                <option value="Shoes">Shoes</option>
                                <option value="Bags">Bags</option>
                                <option value="Accessories">Accessories</option>
                            </select>
                        <?php else: ?>
                            <select name="category" class="form-select" required>
                                <option value="" disabled selected>Select Category</option>
                                <option value="Phones">Phones</option>
                                <option value="Laptops">Laptops</option>
                                <option value="Fashion">Fashion</option>
                                <option value="Books">Books</option>
                                <option value="Home">Home</option>
                                <option value="Gaming">Gaming</option>
                                <option value="Sports">Sports</option>
                                <option value="Kids">Kids</option>
                                <option value="Beds">Beds</option>
                                <option value="Sofas">Sofas</option>
                                <option value="Dining & Coffee">Dining & Coffee</option>
                                <option value="Home Office">Home Office</option>
                                <option value="Home Furniture">Home Furniture</option>
                                <option value="Fridge">Fridge</option>
                                <option value="Washing Machine">Washing Machine</option>
                                <option value="Others">Others</option>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-input" rows="4" placeholder="Describe your item (condition, age, features)..." required></textarea>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="card-header">
                    <ion-icon name="pricetag-outline" style="<?php if($is_thrift) echo 'color:#27ae60;'; ?>"></ion-icon>
                    <span>Pricing & Condition</span>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Price (₹)</label>
                        <input type="number" name="price" step="0.01" class="form-input" required placeholder="Enter amount">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Condition</label>
                        <select name="condition_tag" class="form-select">
                            <option value="Brand New">Brand New</option>
                            <option value="Lightly Used" <?php if($is_thrift) echo 'selected'; ?>>Lightly Used</option>
                            <option value="Regularly Used">Regularly Used</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-input" required placeholder="e.g. Bangalore, Indiranagar">
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="card-header">
                    <ion-icon name="images-outline" style="<?php if($is_thrift) echo 'color:#27ae60;'; ?>"></ion-icon>
                    <span>Photos & Video</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Photos <span class="required-note">(Minimum 3 required)</span></label>
                    <div class="photo-upload-grid" id="image-inputs-container">
                        <div class="photo-upload-box" onclick="showSourcePicker(this)">
                            <input type="file" name="images[]" accept="image/*" style="display:none;" onchange="handlePhotoSelect(this)" onclick="event.stopPropagation()">
                            <ion-icon name="images-outline" class="placeholder-icon"></ion-icon>
                            <span>Photo 1</span>
                        </div>
                        <div class="photo-upload-box" onclick="showSourcePicker(this)">
                            <input type="file" name="images[]" accept="image/*" style="display:none;" onchange="handlePhotoSelect(this)" onclick="event.stopPropagation()">
                            <ion-icon name="add-outline" class="placeholder-icon"></ion-icon>
                            <span>Photo 2</span>
                        </div>
                        <div class="photo-upload-box" onclick="showSourcePicker(this)">
                            <input type="file" name="images[]" accept="image/*" style="display:none;" onchange="handlePhotoSelect(this)" onclick="event.stopPropagation()">
                            <ion-icon name="add-outline" class="placeholder-icon"></ion-icon>
                            <span>Photo 3</span>
                        </div>
                    </div>
                    <button type="button" id="add-photo-btn" class="add-photo-btn">
                        <ion-icon name="add-circle-outline"></ion-icon> Add More Photos
                    </button>
                </div>

                <div class="form-group">
                    <label class="form-label">Video <span class="optional-note">(Optional)</span></label>
                    <div class="video-upload-box" onclick="this.querySelector('input').click()">
                        <input type="file" name="video" accept="video/*" style="display:none;" onchange="handleVideoSelect(this)" onclick="event.stopPropagation()">
                        <ion-icon name="videocam-outline"></ion-icon>
                        <span id="video-label">Upload a short video of your product</span>
                    </div>
                </div>
            </div>

            <!-- Source Picker Modal -->
            <div id="source-modal" class="source-modal" onclick="if(event.target == this) closeSourcePicker()">
                <div class="source-card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h4 style="margin:0; font-size:1.1rem; color:#1e293b;">Choose Photo</h4>
                        <ion-icon name="close-outline" style="font-size:1.5rem; cursor:pointer;" onclick="closeSourcePicker()"></ion-icon>
                    </div>
                    <div class="source-options">
                        <div class="source-option" onclick="selectSource('camera')">
                            <div class="source-icon"><ion-icon name="camera-outline"></ion-icon></div>
                            <div class="source-text">
                                <strong>Take Photo</strong>
                                <span>Use device camera</span>
                            </div>
                        </div>
                        <div class="source-option" onclick="selectSource('file')">
                            <div class="source-icon"><ion-icon name="cloud-upload-outline"></ion-icon></div>
                            <div class="source-text">
                                <strong>Upload File</strong>
                                <span>Select from gallery</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pre-submission Modal for Camera -->
            <div id="camera-modal" class="camera-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Capture Product Photo</h3>
                        <button type="button" class="close-modal" onclick="closeCamera()">
                            <ion-icon name="close-outline"></ion-icon>
                        </button>
                    </div>
                    <div class="camera-view">
                        <video id="camera-stream" autoplay playsinline></video>
                        <div class="camera-overlay"></div>
                        <canvas id="photo-canvas" style="display:none;"></canvas>
                    </div>
                    <div class="camera-controls">
                        <div style="width:50px;"></div> <!-- Spacer -->
                        <button type="button" class="capture-btn" id="capture-btn">
                            <div class="inner-circle"></div>
                        </button>
                        <button type="button" class="switch-btn" id="switch-camera-btn">
                            <ion-icon name="camera-reverse-outline"></ion-icon>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-card guarantee-card" <?php if($is_thrift) echo 'style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border:1px solid #bbf7d0;"'; ?>>
                <ion-icon name="leaf-outline" style="<?php echo $is_thrift ? 'color:#15803d;' : 'color: #6B21A8;'; ?>"></ion-icon>
                <div>
                    <?php if($is_thrift): ?>
                        <strong style="color:#14532d;">Verified Sustainable</strong>
                        <p style="color:#166534;">Thank you for contributing to circular fashion! Your listing will be reviewed shortly.</p>
                    <?php else: ?>
                        <strong>Listaria Verification</strong>
                        <p>Your listing will be reviewed by our team before going live. This ensures quality and trust for all buyers.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div id="item-queue-container" style="display:none; margin-bottom:20px;">
                <h3 style="font-size:1.1rem; margin-bottom:10px; color:#1a1a1a; display:flex; justify-content:space-between; align-items:center;">
                    <span>Queued Items (<span id="queue-count">0</span>)</span>
                    <button type="button" onclick="clearQueue()" style="background:none; border:none; color:#ef4444; font-size:0.85rem; cursor:pointer;">Clear All</button>
                </h3>
                <div id="queue-list" style="display:flex; flex-direction:column; gap:10px;">
                    <!-- Queued items show here -->
                </div>
            </div>

            <div class="action-buttons-container" style="display:flex; gap:15px; margin-top:20px;">
                <button type="button" id="add-to-queue-btn" class="btn-secondary-action" style="flex:1;">
                    <ion-icon name="add-circle-outline"></ion-icon> Add to List
                </button>
                <button type="submit" id="main-submit-btn" class="btn-primary-action" style="flex:1;">
                    <ion-icon name="rocket-outline"></ion-icon> <?php echo $is_thrift ? 'List on Thrift+' : 'Publish Now'; ?>
                </button>
            </div>
            
            <button type="button" id="publish-all-btn" class="submit-btn" style="display:none; margin-top:15px; background:linear-gradient(135deg, #1a1a1a 0%, #333 100%);">
                <ion-icon name="cloud-upload-outline"></ion-icon> Publish All (<span id="pub-count">0</span>)
            </button>
            </form>
        </div>

        <div id="bulk-upload-section" style="display:none;">
            <div style="background:#f9f5ff; border:1px solid #d8b4fe; padding:25px; border-radius:16px; text-align:center;">
                <ion-icon name="document-text-outline" style="font-size:3rem; color:#6B21A8; margin-bottom:15px;"></ion-icon>
                <h3 style="margin:0 0 10px;">Bulk Upload via Excel/CSV</h3>
                <p style="font-size:0.9rem; color:#666; margin-bottom:20px;">Download our template, fill in your product details, and upload it back.</p>
                
                <a href="api/download_template.php" class="btn-secondary" style="display:inline-flex; align-items:center; gap:8px; margin-bottom:15px; padding:10px 20px; border:1px solid #1a1a1a; border-radius:30px; text-decoration:none; color:#1a1a1a; font-weight:600;">
                    <ion-icon name="download-outline"></ion-icon> Download Template
                </a>
                
                <div style="color: #dc2626; font-size: 0.85rem; text-align: left; max-width: 500px; margin: 0 auto 25px; background: #fff1f2; padding: 15px; border-radius: 12px; border: 1px solid #fecaca;">
                    <strong style="display: block; margin-bottom: 8px; font-size: 0.9rem;">Excel Sheet Rules:</strong>
                    <ul style="margin: 0; padding-left: 20px; line-height: 1.5;">
                        <li>Fill all required columns: Title, Brand, Category, Condition, Location, Price.</li>
                        <li><b>Category</b> must match: Tops, Bottoms, Jackets, Shoes, Bags, Accessories, or Others.</li>
                        <li><b>Condition</b> must match: Brand New, Lightly Used, or Regularly Used.</li>
                        <li><b>Image Filenames</b>: List names (e.g., img1.jpg, img2.png) separated by commas.</li>
                        <li style="font-weight: 600;">Names in the sheet must <u>exactly match</u> the files you upload in Step 2.</li>
                    </ul>
                </div>

                <form action="api/bulk_upload.php" method="POST" enctype="multipart/form-data" style="border-top:1px solid #eee; padding-top:25px;">
                    <div class="form-group" style="text-align:left; margin-bottom:15px;">
                        <label style="font-weight:600; margin-bottom:8px; display:block;">1. Select CSV/Excel File</label>
                        <input type="file" name="bulk_file" accept=".csv, .xlsx" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    </div>
                    <div class="form-group" style="text-align:left; margin-bottom:20px;">
                        <label style="font-weight:600; margin-bottom:8px; display:block;">2. Select Referenced Images</label>
                        <p style="font-size:0.8rem; color:#666; margin-top:0; margin-bottom:8px;">Select all image files named in your CSV file so we can attach them to your products.</p>
                        <input type="file" name="bulk_images[]" multiple accept="image/*" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    </div>
                    <?php if (($_SESSION['account_type'] ?? '') === 'admin'): ?>
                    <div class="form-group" style="text-align:left; margin-bottom:20px;">
                        <label style="font-weight:600; margin-bottom:8px; display:block;">3. Assign to Vendor (Admin Only)</label>
                        <p style="font-size:0.8rem; color:#666; margin-top:0; margin-bottom:8px;">Assign these bulk products to a specific vendor's closet.</p>
                        <select name="vendor_id" class="form-select" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                            <option value="">-- Assing to Admin (Default) --</option>
                            <?php foreach ($vendors as $v): ?>
                                <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['business_name'] ?: $v['full_name']); ?> (ID: <?php echo $v['id']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="submit-btn" id="bulk-submit-btn">
                        <ion-icon name="cloud-upload-outline"></ion-icon> Start Bulk Upload
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.queued-item-pill {
    background: #fff;
    padding: 10px 15px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
}
.queued-item-pill .title { font-weight: 600; font-size: 0.9rem; }
.queued-item-pill .price { color: #6B21A8; font-weight: 700; font-size: 0.85rem; }
</style>

<style>
.sell-page {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 2rem 1rem;
    padding-top: 100px; /* Added to keep content below sticky header */
}

.sell-container {
    max-width: 700px;
    margin: 0 auto;
}

.sell-header {
    text-align: center;
    margin-bottom: 2rem;
}

.sell-header h1 {
    font-size: 2rem;
    font-weight: 800;
    color: #1a1a1a;
    margin: 0 0 0.5rem 0;
}

.sell-header p {
    color: #666;
    font-size: 1rem;
}

.form-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    font-size: 1.1rem;
    color: #1a1a1a;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f0f0f0;
}

.card-header ion-icon {
    font-size: 1.4rem;
    color: #6B21A8;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
    color: #1a1a1a;
    margin-bottom: 0.5rem;
}

.required-note {
    color: #6B21A8;
    font-weight: 500;
    font-size: 0.8rem;
}

.optional-note {
    color: #999;
    font-weight: 400;
    font-size: 0.8rem;
}

.form-input,
.form-select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.2s;
    background: #fff;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: #6B21A8;
    box-shadow: 0 0 0 3px rgba(107, 33, 168, 0.1);
}

textarea.form-input {
    resize: vertical;
    min-height: 100px;
}

.photo-upload-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.2rem;
    margin-bottom: 1.5rem;
}

.photo-upload-box {
    aspect-ratio: 1;
    border: 2px dashed #e2e8f0;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #fbfbfb;
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    cursor: pointer;
}

.photo-upload-box:hover {
    border-color: #6B21A8;
    background: #fff;
    transform: translateY(-4px);
    box-shadow: 0 12px 20px -8px rgba(107, 33, 168, 0.2);
}

/* Source Picker Modal Styles */
.source-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(10, 10, 10, 0.5);
    backdrop-filter: blur(8px);
    z-index: 2050;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.source-modal.active {
    display: flex;
}

.source-card {
    background: #fff;
    width: 100%;
    max-width: 320px;
    border-radius: 24px;
    padding: 24px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.2);
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.source-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.source-option {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 16px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.2s;
}

.source-option:hover {
    background: #f1f5f9;
    border-color: #6B21A8;
}

.source-icon {
    width: 44px;
    height: 44px;
    background: #fff;
    color: #6B21A8;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.source-text strong {
    display: block;
    font-size: 0.95rem;
    color: #1e293b;
    margin-bottom: 2px;
}

.source-text span {
    font-size: 0.8rem;
    color: #64748b;
}

.box-action-btn:hover {
    transform: scale(1.02);
    background: #f8f9fa;
}

.box-action-btn.camera-btn {
    background: #6B21A8;
    color: #fff;
}

.box-action-btn.camera-btn:hover {
    background: #581c87;
}

.box-action-btn ion-icon {
    font-size: 1.2rem;
}

.photo-upload-box .placeholder-icon {
    font-size: 2.5rem;
    color: #cbd5e1;
    margin-bottom: 0.8rem;
    transition: color 0.3s;
}

.photo-upload-box:hover .placeholder-icon {
    color: #6B21A8;
}

.photo-upload-box span {
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 500;
}

.photo-upload-box img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 5;
}

/* Camera Modal - Premium Look */
.camera-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(10, 10, 10, 0.95);
    backdrop-filter: blur(10px);
    z-index: 2100;
    align-items: center;
    justify-content: center;
    padding: 0; /* Full screen on mobile */
}

@media (min-width: 600px) {
    .camera-modal {
        padding: 40px;
    }
}

.camera-modal.active {
    display: flex;
}

.camera-modal .modal-content {
    background: #000;
    width: 100%;
    height: 100%;
    max-width: 500px;
    max-height: 800px;
    border-radius: 0;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
}

@media (min-width: 600px) {
    .camera-modal .modal-content {
        border-radius: 32px;
        box-shadow: 0 0 100px rgba(107, 33, 168, 0.3);
        border: 1px solid rgba(255,255,255,0.1);
    }
}

.camera-modal .modal-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(to bottom, rgba(0,0,0,0.8), transparent);
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    z-index: 10;
}

.camera-modal h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    color: #fff;
}

.camera-modal .camera-view {
    flex: 1;
    position: relative;
    background: #000;
}

.camera-modal video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.camera-overlay {
    position: absolute;
    inset: 0;
    border: 2px solid rgba(255,255,255,0.15);
    margin: 40px;
    pointer-events: none;
    box-shadow: 0 0 0 2000px rgba(0,0,0,0.3);
}

.camera-controls {
    padding: 40px 20px;
    background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-around;
    align-items: center;
    z-index: 10;
}

.capture-btn {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 5px solid #fff;
    background: transparent;
    padding: 6px;
    cursor: pointer;
    transition: all 0.2s;
    outline: none;
}

.capture-btn .inner-circle {
    width: 100%;
    height: 100%;
    background: #fff;
    border-radius: 50%;
    transition: all 0.2s;
}

.capture-btn:hover {
    transform: scale(1.05);
}

.capture-btn:active {
    transform: scale(0.9);
}

.capture-btn:active .inner-circle {
    background: #ddd;
}

.switch-btn, .close-modal {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    color: #fff;
    font-size: 1.6rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.switch-btn:hover, .close-modal:hover {
    background: rgba(255,255,255,0.2);
    transform: rotate(15deg);
}

.add-photo-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px;
    background: #fff;
    border: 1px dashed #e5e7eb;
    border-radius: 10px;
    font-size: 0.9rem;
    color: #666;
    cursor: pointer;
    transition: all 0.2s;
}

.add-photo-btn:hover {
    border-color: #6B21A8;
    color: #6B21A8;
}

.video-upload-box {
    padding: 2rem;
    border: 2px dashed #e5e7eb;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    background: #fafafa;
}

.video-upload-box:hover {
    border-color: #6B21A8;
    background: #f9f5ff;
}

.video-upload-box.has-video {
    border-style: solid;
    border-color: #22c55e;
    background: #f0fdf4;
}

.video-upload-box ion-icon {
    font-size: 2.5rem;
    color: #999;
    margin-bottom: 0.5rem;
}

.video-upload-box span {
    font-size: 0.9rem;
    color: #666;
}

.guarantee-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: linear-gradient(135deg, #f3e8ff 0%, #ede9fe 100%);
}

.guarantee-card ion-icon {
    font-size: 1.5rem;
    color: #6B21A8;
    flex-shrink: 0;
}

.guarantee-card strong {
    color: #581c87;
    display: block;
    margin-bottom: 4px;
}

.guarantee-card p {
    color: #6B21A8;
    font-size: 0.85rem;
    margin: 0;
    line-height: 1.5;
}

.btn-primary-action {
    background: linear-gradient(135deg, #6B21A8 0%, #4c1d95 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    padding: 16px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(107, 33, 168, 0.25);
}

.btn-primary-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(107, 33, 168, 0.35);
    background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
}

.btn-primary-action:active {
    transform: translateY(0);
}

.btn-secondary-action {
    background: #fff;
    color: #1a1a1a;
    border: 2px solid #1a1a1a;
    border-radius: 12px;
    padding: 16px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.btn-secondary-action:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.submit-btn {
    width: 100%;
    padding: 16px;
    background: #1a1a1a;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.2s;
}

.submit-btn:hover {
    background: #000;
    transform: translateY(-2px);
}

.submit-btn:disabled, .btn-primary-action:disabled, .btn-secondary-action:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.alert.error {
    background-color: #fef2f2;
    color: #dc2626;
}

.alert ion-icon {
    font-size: 1.2rem;
}

@media (max-width: 600px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .photo-upload-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
    }
    
    .sell-header h1 {
        font-size: 1.5rem;
    }

    .action-buttons-container {
        flex-direction: column !important;
        gap: 12px !important;
    }
    
    .btn-primary-action, .btn-secondary-action {
        width: 100% !important;
    }
}
</style>

<script src="assets/js/compress.js"></script>
<script>
const itemQueue = [];

function switchUploadTab(tab) {
    const singleSection = document.getElementById('single-upload-section');
    const bulkSection = document.getElementById('bulk-upload-section');
    const singleBtn = document.getElementById('tab-single-btn');
    const bulkBtn = document.getElementById('tab-bulk-btn');
    
    if (tab === 'single') {
        singleSection.style.display = 'block';
        bulkSection.style.display = 'none';
        singleBtn.style.borderBottom = '2px solid #1a1a1a';
        singleBtn.style.color = '#1a1a1a';
        bulkBtn.style.borderBottom = 'none';
        bulkBtn.style.color = '#666';
    } else {
        singleSection.style.display = 'none';
        bulkSection.style.display = 'block';
        bulkBtn.style.borderBottom = '2px solid #1a1a1a';
        bulkBtn.style.color = '#1a1a1a';
        singleBtn.style.borderBottom = 'none';
        singleBtn.style.color = '#666';
    }
}

function handlePhotoSelect(input) {
    const box = input.closest('.photo-upload-box');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            updatePhotoPreview(box, e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updatePhotoPreview(box, dataUrl) {
    box.classList.add('has-image');
    // Remove existing image if any
    const existingImg = box.querySelector('img');
    if (existingImg) existingImg.remove();
    
    const img = document.createElement('img');
    img.src = dataUrl;
    img.alt = "Preview";
    box.appendChild(img);
}

document.getElementById('add-photo-btn').addEventListener('click', function() {
    const container = document.getElementById('image-inputs-container');
    const count = container.children.length + 1;
    
    const div = document.createElement('div');
    div.className = 'photo-upload-box';
    div.onclick = function() { showSourcePicker(this); };
    div.innerHTML = `
        <input type="file" name="images[]" accept="image/*" style="display:none;" onchange="handlePhotoSelect(this)" onclick="event.stopPropagation()">
        <ion-icon name="add-outline" class="placeholder-icon"></ion-icon>
        <span>Photo ${count}</span>
    `;
    container.appendChild(div);
});

// Photo Source Picker Logic
let sourcePickerActiveBox = null;
const sourceModal = document.getElementById('source-modal');

function showSourcePicker(box) {
    sourcePickerActiveBox = box;
    sourceModal.classList.add('active');
}

function closeSourcePicker() {
    sourceModal.classList.remove('active');
    sourcePickerActiveBox = null;
}

function selectSource(method) {
    const box = sourcePickerActiveBox;
    closeSourcePicker();
    
    if (method === 'camera') {
        openCamera(box);
    } else {
        box.querySelector('input').click();
    }
}

// Camera Integration
let cameraStream = null;
let currentCameraBox = null;
let currentFacingMode = 'environment'; // Default to back camera

const cameraModal = document.getElementById('camera-modal');
const videoElement = document.getElementById('camera-stream');
const canvasElement = document.getElementById('photo-canvas');
const captureBtn = document.getElementById('capture-btn');
const switchBtn = document.getElementById('switch-camera-btn');

async function openCamera(box) {
    currentCameraBox = box;
    try {
        cameraStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: currentFacingMode },
            audio: false
        });
        videoElement.srcObject = cameraStream;
        cameraModal.classList.add('active');
    } catch (err) {
        console.error("Camera error:", err);
        alert("Unable to access camera. Please ensure permissions are granted.");
    }
}

function closeCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    cameraModal.classList.remove('active');
    videoElement.srcObject = null;
}

switchBtn.onclick = async () => {
    currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
    if (cameraStream) {
        closeCamera();
        // Re-open camera with new facing mode
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: currentFacingMode },
                audio: false
            });
            videoElement.srcObject = cameraStream;
            cameraModal.classList.add('active');
        } catch (err) {
            console.error("Switch camera error:", err);
        }
    }
};

captureBtn.onclick = () => {
    if (!cameraStream) return;

    const width = videoElement.videoWidth;
    const height = videoElement.videoHeight;
    canvasElement.width = width;
    canvasElement.height = height;
    
    const context = canvasElement.getContext('2d');
    context.drawImage(videoElement, 0, 0, width, height);

    const dataUrl = canvasElement.toDataURL('image/jpeg');
    updatePhotoPreview(currentCameraBox, dataUrl);

    // Convert dataUrl to File and put in hidden input
    fetch(dataUrl)
        .then(res => res.blob())
        .then(blob => {
            const fileName = `capture_${Date.now()}.jpg`;
            const file = new File([blob], fileName, { type: "image/jpeg" });
            
            const input = currentCameraBox.querySelector('input');
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            
            closeCamera();
        });
};

async function clearQueue() {
    if (confirm('Clear all queued items?')) {
        itemQueue.length = 0;
        renderQueue();
    }
}

function renderQueue() {
    const list = document.getElementById('queue-list');
    const container = document.getElementById('item-queue-container');
    const pubBtn = document.getElementById('publish-all-btn');
    const countSpan = document.getElementById('queue-count');
    const pubCount = document.getElementById('pub-count');
    
    list.innerHTML = '';
    
    if (itemQueue.length > 0) {
        container.style.display = 'block';
        pubBtn.style.display = 'flex';
        countSpan.textContent = itemQueue.length;
        pubCount.textContent = itemQueue.length;
        
        itemQueue.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'queued-item-pill';
            div.innerHTML = `
                <div>
                    <div class="title">${item.get('title')}</div>
                    <div class="price">₹${item.get('price_min')}</div>
                </div>
                <ion-icon name="trash-outline" onclick="removeFromQueue(${index})" style="color:#ef4444; font-size:1.2rem; cursor:pointer;"></ion-icon>
            `;
            list.appendChild(div);
        });
    } else {
        container.style.display = 'none';
        pubBtn.style.display = 'none';
    }
}

function removeFromQueue(index) {
    itemQueue.splice(index, 1);
    renderQueue();
}

document.getElementById('add-to-queue-btn').addEventListener('click', async function() {
    const form = document.getElementById('sell-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const fileInputs = document.querySelectorAll('.photo-upload-box input[type="file"]');
    const filesToUpload = [];
    fileInputs.forEach(input => {
        if (input.files.length > 0) {
            filesToUpload.push(input.files[0]);
        }
    });

    if (filesToUpload.length < 3) {
        alert("Please upload at least 3 photos.");
        return;
    }

    const btn = document.getElementById('add-to-queue-btn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> Processing...';

    try {
        const formData = new FormData(form);
        formData.delete('images[]');
        
        const compressedBlobs = await Promise.all(filesToUpload.map(file => compressImage(file, 1280, 0.7)));
        compressedBlobs.forEach((blob, index) => {
            const originalName = filesToUpload[index].name;
            const newName = originalName.replace(/\.[^/.]+$/, "") + ".jpg";
            formData.append('images[]', blob, newName);
        });

        itemQueue.push(formData);
        renderQueue();
        
        // Reset form but keep some fields if vendor wants?
        // Let's just reset for now.
        form.reset();
        document.getElementById('image-inputs-container').innerHTML = `
            <div class="photo-upload-box" onclick="showSourcePicker(this)">
                <input type="file" name="images[]" accept="image/*" style="display:none;" onchange="handlePhotoSelect(this)" onclick="event.stopPropagation()">
                <ion-icon name="camera-outline" class="placeholder-icon"></ion-icon>
                <span>Photo 1</span>
            </div>
            <div class="photo-upload-box" onclick="showSourcePicker(this)">
                <input type="file" name="images[]" accept="image/*" style="display:none;" onchange="handlePhotoSelect(this)" onclick="event.stopPropagation()">
                <ion-icon name="add-outline" class="placeholder-icon"></ion-icon>
                <span>Photo 2</span>
            </div>
            <div class="photo-upload-box" onclick="showSourcePicker(this)">
                <input type="file" name="images[]" accept="image/*" style="display:none;" onchange="handlePhotoSelect(this)" onclick="event.stopPropagation()">
                <ion-icon name="add-outline" class="placeholder-icon"></ion-icon>
                <span>Photo 3</span>
            </div>
        `;
        
        btn.disabled = false;
        btn.innerHTML = originalText;
        
    } catch (err) {
        console.error(err);
        alert("Compression failed.");
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

document.getElementById('publish-all-btn').addEventListener('click', async function() {
    if (itemQueue.length === 0) return;
    
    if (!confirm(`Publish all ${itemQueue.length} items?`)) return;
    
    const btn = this;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    
    let successCount = 0;
    let failCount = 0;
    
    for (let i = 0; i < itemQueue.length; i++) {
        btn.innerHTML = `<ion-icon name="hourglass-outline"></ion-icon> Uploading ${i+1}/${itemQueue.length}...`;
        try {
            const response = await fetch('sell.php', {
                method: 'POST',
                body: itemQueue[i]
            });
            if (response.ok) {
                successCount++;
            } else {
                failCount++;
            }
        } catch (e) {
            failCount++;
        }
    }
    
    if (failCount === 0) {
        alert(`Successfully published all ${successCount} items!`);
        window.location.href = 'profile.php?msg=bulk_pending';
    } else {
        alert(`Finished: ${successCount} success, ${failCount} failed. Check dashboard.`);
        window.location.href = 'profile.php';
    }
});

document.getElementById('sell-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = this.querySelector('.submit-btn');
    const originalText = btn.innerHTML;
    
    // 1. Gather files
    // The photo-upload-box now contains the inputs with files
    const fileInputs = document.querySelectorAll('.photo-upload-box input[type="file"]');
    const filesToUpload = [];
    fileInputs.forEach(input => {
        if (input.files.length > 0) {
            filesToUpload.push(input.files[0]);
        }
    });

    if (filesToUpload.length < 3) {
        alert("Please upload at least 3 photos.");
        return;
    }

    // 2. Show loading state
    btn.disabled = true;
    btn.innerHTML = '<ion-icon name="hourglass-outline"></ion-icon> Compressing Images...';

    const formData = new FormData(this);
    // Remove original uncompressed images from formData to avoid duplication/large upload
    formData.delete('images[]');

    try {
        // 3. Compress images
        const compressedBlobs = await Promise.all(filesToUpload.map(file => compressImage(file, 1280, 0.7)));
        
        // 4. Append compressed images
        compressedBlobs.forEach((blob, index) => {
            // Keep original filename but change extension to .jpg if needed
            const originalName = filesToUpload[index].name;
            const newName = originalName.replace(/\.[^/.]+$/, "") + ".jpg";
            formData.append('images[]', blob, newName);
        });

        // 5. Update Status
        btn.innerHTML = '<ion-icon name="cloud-upload-outline"></ion-icon> Uploading...';

        // 6. Send via AJAX
        const response = await fetch('sell.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.redirected) {
             window.location.href = response.url;
        } else if (response.ok) {
             window.location.href = 'sell.php?msg=pending_approval';
        } else {
            alert("Upload failed. Please try again.");
            btn.innerHTML = originalText;
            btn.disabled = false;
        }

    } catch (err) {
        console.error(err);
        alert("An error occurred during image processing.");
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});
</script>
<?php include 'includes/footer.php'; ?>
