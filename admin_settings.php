<?php
session_start();
require 'includes/db.php';

$activePage = 'settings';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$msg = '';

// Create settings table if not exists
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
} catch(Exception $e) {}

// Get current settings
function getSetting($pdo, $key, $default = '') {
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : $default;
}

// Save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => $_POST['site_name'] ?? 'Listaria',
        'site_tagline' => $_POST['site_tagline'] ?? '',
        'commission_rate' => $_POST['commission_rate'] ?? '5',
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'registration_enabled' => isset($_POST['registration_enabled']) ? '1' : '0',
        'listing_approval_required' => isset($_POST['listing_approval_required']) ? '1' : '0',
        'max_listing_images' => $_POST['max_listing_images'] ?? '5',
        'min_listing_price' => $_POST['min_listing_price'] ?? '100',
        'currency_symbol' => $_POST['currency_symbol'] ?? '₹',
        'smtp_host' => !defined('SMTP_HOST') ? ($_POST['smtp_host'] ?? '') : getSetting($pdo, 'smtp_host', ''),
        'smtp_port' => !defined('SMTP_PORT') ? ($_POST['smtp_port'] ?? '587') : getSetting($pdo, 'smtp_port', '587'),
        'smtp_user' => !defined('SMTP_USER') ? ($_POST['smtp_user'] ?? '') : getSetting($pdo, 'smtp_user', ''),
        'smtp_pass' => !defined('SMTP_PASS') ? ($_POST['smtp_pass'] ?? '') : getSetting($pdo, 'smtp_pass', ''),
        'razorpay_enabled' => isset($_POST['razorpay_enabled']) ? '1' : '0',
        'cod_enabled' => isset($_POST['cod_enabled']) ? '1' : '0',
        'wallet_enabled' => isset($_POST['wallet_enabled']) ? '1' : '0',
        'chat_enabled' => isset($_POST['chat_enabled']) ? '1' : '0',
        'kyc_required' => isset($_POST['kyc_required']) ? '1' : '0',
        'admin_dark_mode' => isset($_POST['admin_dark_mode']) ? '1' : '0',
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    
    $msg = "Settings saved successfully!";
    
    // Log activity
    try {
        $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
            ->execute([$_SESSION['user_id'], "Settings updated", "Site settings modified", $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch(Exception $e) {}
}

// Load current settings
$siteName = getSetting($pdo, 'site_name', 'Listaria');
$siteTagline = getSetting($pdo, 'site_tagline', '');
$commissionRate = getSetting($pdo, 'commission_rate', '5');
$maintenanceMode = getSetting($pdo, 'maintenance_mode', '0');
$registrationEnabled = getSetting($pdo, 'registration_enabled', '1');
$listingApproval = getSetting($pdo, 'listing_approval_required', '1');
$maxImages = getSetting($pdo, 'max_listing_images', '5');
$minPrice = getSetting($pdo, 'min_listing_price', '100');
$currencySymbol = getSetting($pdo, 'currency_symbol', '₹');
$smtpFromEnv = defined('SMTP_HOST') || defined('SMTP_PORT') || defined('SMTP_USER') || defined('SMTP_PASS');
$smtpHost = defined('SMTP_HOST') ? SMTP_HOST : getSetting($pdo, 'smtp_host', '');
$smtpPort = defined('SMTP_PORT') ? SMTP_PORT : getSetting($pdo, 'smtp_port', '587');
$smtpUser = defined('SMTP_USER') ? SMTP_USER : getSetting($pdo, 'smtp_user', '');
$smtpPass = defined('SMTP_PASS') ? SMTP_PASS : getSetting($pdo, 'smtp_pass', '');
$razorpayEnabled = getSetting($pdo, 'razorpay_enabled', '1');
$codEnabled = getSetting($pdo, 'cod_enabled', '1');
$walletEnabled = getSetting($pdo, 'wallet_enabled', '1');
$chatEnabled = getSetting($pdo, 'chat_enabled', '1');
$kycRequired = getSetting($pdo, 'kyc_required', '0');
$adminDarkMode = getSetting($pdo, 'admin_dark_mode', '0');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Site Settings - Listaria Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <style>
        :root { 
            --primary: #6B21A8; 
            --bg: #f8f9fa; 
            --sidebar-bg: #1a1a1a;
            --text-light: #a1a1aa;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); margin: 0; padding: 0; display:flex; color: #333; }
        
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 2rem 1.5rem; color: white; z-index: 100; display: flex; flex-direction: column; }
        .brand { font-size: 1.4rem; font-weight: 800; color: white; display:flex; align-items: center; gap: 10px; margin-bottom: 3rem; text-decoration:none; }
        .menu-item { display: flex; align-items: center; gap: 12px; padding: 1rem; color: var(--text-light); text-decoration: none; border-radius: 12px; margin-bottom: 0.5rem; transition: all 0.2s; font-weight: 500; }
        .menu-item:hover, .menu-item.active { background: #6B21A8; color: white; }
        .menu-item ion-icon { font-size: 1.2rem; }
        
        .main-content { margin-left: 260px; padding: 2.5rem 3rem; width: calc(100% - 260px); min-height: 100vh; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1a1a1a; }
        
        .msg-success {
            background: #f0fdf4;
            color: #22c55e;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .settings-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #f0f0f0;
        }
        
        .settings-card.full-width {
            grid-column: span 2;
        }
        
        .card-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-title ion-icon {
            color: #6B21A8;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
            font-size: 0.9rem;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6B21A8;
        }
        
        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: #999;
            font-size: 0.8rem;
        }
        
        .toggle-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .toggle-group:last-child {
            border-bottom: none;
        }
        
        .toggle-info {
            flex: 1;
        }
        
        .toggle-label {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        
        .toggle-desc {
            font-size: 0.8rem;
            color: #999;
            margin-top: 0.25rem;
        }
        
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 26px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e5e5e5;
            transition: 0.3s;
            border-radius: 26px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        input:checked + .toggle-slider {
            background-color: #6B21A8;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(22px);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-primary { background: #6B21A8; color: white; }
        .btn-primary:hover { background: #581c87; }
        
        .btn-dark { background: #1a1a1a; color: white; }
        .btn-dark:hover { background: #333; }
        
        .form-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }
        
        .maintenance-warning {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #92400e;
            font-weight: 500;
        }
        
        @media (max-width: 1024px) {
            .settings-grid { grid-template-columns: 1fr; }
            .settings-card.full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="main-content">
        <div class="header">
            <div>
                <h1>Site Settings</h1>
                <p style="color:#666; margin-top:0.5rem;">Configure your marketplace</p>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="msg-success">
                <ion-icon name="checkmark-circle-outline"></ion-icon>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <?php if($maintenanceMode === '1'): ?>
            <div class="maintenance-warning">
                <ion-icon name="warning-outline" style="font-size:1.5rem;"></ion-icon>
                <div>
                    <strong>Maintenance Mode is ON</strong><br>
                    <span style="font-weight:normal;">The site is currently not accessible to regular users.</span>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="settings-grid">
                
                <!-- General Settings -->
                <div class="settings-card">
                    <div class="card-title">
                        <ion-icon name="globe-outline"></ion-icon>
                        General Settings
                    </div>
                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($siteName); ?>">
                    </div>
                    <div class="form-group">
                        <label>Tagline</label>
                        <input type="text" name="site_tagline" value="<?php echo htmlspecialchars($siteTagline); ?>">
                    </div>
                    <div class="form-group">
                        <label>Currency Symbol</label>
                        <input type="text" name="currency_symbol" value="<?php echo htmlspecialchars($currencySymbol); ?>">
                    </div>
                </div>

                <!-- Commission & Pricing -->
                <div class="settings-card">
                    <div class="card-title">
                        <ion-icon name="cash-outline"></ion-icon>
                        Commission & Pricing
                    </div>
                    <div class="form-group">
                        <label>Platform Commission (%)</label>
                        <input type="number" name="commission_rate" value="<?php echo htmlspecialchars($commissionRate); ?>" min="0" max="100" step="0.1">
                        <small>Percentage taken from each sale</small>
                    </div>
                    <div class="form-group">
                        <label>Minimum Listing Price</label>
                        <input type="number" name="min_listing_price" value="<?php echo htmlspecialchars($minPrice); ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>Max Listing Images</label>
                        <input type="number" name="max_listing_images" value="<?php echo htmlspecialchars($maxImages); ?>" min="1" max="20">
                    </div>
                </div>

                <!-- Feature Toggles -->
                <div class="settings-card">
                    <div class="card-title">
                        <ion-icon name="toggle-outline"></ion-icon>
                        Feature Toggles
                    </div>
                    
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Maintenance Mode</div>
                            <div class="toggle-desc">Disable site access for regular users</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="maintenance_mode" <?php echo $maintenanceMode === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">User Registration</div>
                            <div class="toggle-desc">Allow new users to sign up</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="registration_enabled" <?php echo $registrationEnabled === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Listing Approval Required</div>
                            <div class="toggle-desc">Manually approve listings before publish</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="listing_approval_required" <?php echo $listingApproval === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">KYC Required for Selling</div>
                            <div class="toggle-desc">Users must verify identity to sell</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="kyc_required" <?php echo $kycRequired === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Chat System</div>
                            <div class="toggle-desc">Allow buyers and sellers to chat</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="chat_enabled" <?php echo $chatEnabled === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="settings-card">
                    <div class="card-title">
                        <ion-icon name="card-outline"></ion-icon>
                        Payment Methods
                    </div>
                    
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Razorpay</div>
                            <div class="toggle-desc">Online payment gateway</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="razorpay_enabled" <?php echo $razorpayEnabled === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Cash on Delivery</div>
                            <div class="toggle-desc">Pay when item is delivered</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="cod_enabled" <?php echo $codEnabled === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Wallet Payments</div>
                            <div class="toggle-desc">Use wallet balance for purchases</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="wallet_enabled" <?php echo $walletEnabled === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <!-- Email Settings -->
                <div class="settings-card full-width">
                    <div class="card-title">
                        <ion-icon name="mail-outline"></ion-icon>
                        Email Settings (SMTP)
                        <?php if ($smtpFromEnv): ?>
                            <span style="margin-left:auto; background:#f0fdf4; color:#22c55e; padding:3px 10px; border-radius:20px; font-size:0.7rem; font-weight:600;">FROM ENV</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($smtpFromEnv): ?>
                        <div style="background:#f8f4ff; border:1px solid #e9d5ff; border-radius:8px; padding:12px 16px; margin-bottom:1rem; font-size:0.82rem; color:#6B21A8; display:flex; align-items:center; gap:8px;">
                            <ion-icon name="shield-checkmark-outline" style="font-size:1.1rem;flex-shrink:0;"></ion-icon>
                            SMTP credentials are loaded from environment variables. Update them in the Secrets tab to change these values.
                        </div>
                    <?php endif; ?>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                        <div class="form-group">
                            <label>SMTP Host <?php if(defined('SMTP_HOST')): ?><span style="color:#22c55e;font-size:0.7rem;font-weight:600;">ENV</span><?php endif; ?></label>
                            <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($smtpHost); ?>" placeholder="smtp.gmail.com" <?php echo defined('SMTP_HOST') ? 'disabled style="background:#f5f5f5;cursor:not-allowed;"' : ''; ?>>
                            <?php if(defined('SMTP_HOST')): ?><input type="hidden" name="smtp_host" value="<?php echo htmlspecialchars($smtpHost); ?>"><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>SMTP Port <?php if(defined('SMTP_PORT')): ?><span style="color:#22c55e;font-size:0.7rem;font-weight:600;">ENV</span><?php endif; ?></label>
                            <input type="text" name="smtp_port" value="<?php echo htmlspecialchars($smtpPort); ?>" placeholder="587" <?php echo defined('SMTP_PORT') ? 'disabled style="background:#f5f5f5;cursor:not-allowed;"' : ''; ?>>
                            <?php if(defined('SMTP_PORT')): ?><input type="hidden" name="smtp_port" value="<?php echo htmlspecialchars($smtpPort); ?>"><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>SMTP Username <?php if(defined('SMTP_USER')): ?><span style="color:#22c55e;font-size:0.7rem;font-weight:600;">ENV</span><?php endif; ?></label>
                            <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($smtpUser); ?>" placeholder="your@email.com" <?php echo defined('SMTP_USER') ? 'disabled style="background:#f5f5f5;cursor:not-allowed;"' : ''; ?>>
                            <?php if(defined('SMTP_USER')): ?><input type="hidden" name="smtp_user" value="<?php echo htmlspecialchars($smtpUser); ?>"><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>SMTP Password <?php if(defined('SMTP_PASS')): ?><span style="color:#22c55e;font-size:0.7rem;font-weight:600;">ENV</span><?php endif; ?></label>
                            <input type="password" name="smtp_pass" value="<?php echo defined('SMTP_PASS') ? '••••••••' : htmlspecialchars($smtpPass); ?>" placeholder="App password" <?php echo defined('SMTP_PASS') ? 'disabled style="background:#f5f5f5;cursor:not-allowed;"' : ''; ?>>
                            <?php if(defined('SMTP_PASS')): ?><input type="hidden" name="smtp_pass" value=""><?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Admin Preferences -->
                <div class="settings-card full-width">
                    <div class="card-title">
                        <ion-icon name="color-palette-outline"></ion-icon>
                        Admin Preferences
                    </div>
                    
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Dark Mode</div>
                            <div class="toggle-desc">Enable dark theme for admin panel</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="admin_dark_mode" <?php echo $adminDarkMode === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <ion-icon name="save-outline"></ion-icon>
                    Save Settings
                </button>
                <button type="reset" class="btn btn-dark">
                    <ion-icon name="refresh-outline"></ion-icon>
                    Reset
                </button>
            </div>
        </form>
    </main>
</body>
</html>
