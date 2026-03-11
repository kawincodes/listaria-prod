<?php
require_once __DIR__ . '/includes/session.php';
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
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $msg = "Invalid request. Please try again.";
    } else {
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
        'captcha_enabled' => isset($_POST['captcha_enabled']) ? '1' : '0',
        'marquee_enabled' => isset($_POST['marquee_enabled']) ? '1' : '0',
        'marquee_text' => trim($_POST['marquee_text'] ?? ''),
        'marquee_bg_color' => trim($_POST['marquee_bg_color'] ?? '#6B21A8'),
        'marquee_text_color' => trim($_POST['marquee_text_color'] ?? '#ffffff'),
        'marquee_speed' => in_array($_POST['marquee_speed'] ?? '', ['slow', 'medium', 'fast']) ? $_POST['marquee_speed'] : 'medium',
        'marquee_link' => trim($_POST['marquee_link'] ?? ''),
        'marquee_icon' => trim($_POST['marquee_icon'] ?? ''),
        'founder_socials_visible' => isset($_POST['founder_socials_visible']) ? '1' : '0',
        'thrift_theme' => in_array($_POST['thrift_theme'] ?? '', ['current', 'og']) ? $_POST['thrift_theme'] : 'current',
        'captcha_provider' => in_array($_POST['captcha_provider'] ?? '', ['turnstile', 'recaptcha']) ? $_POST['captcha_provider'] : 'turnstile',
        'turnstile_site_key' => trim($_POST['turnstile_site_key'] ?? ''),
        'turnstile_secret_key' => trim($_POST['turnstile_secret_key'] ?? ''),
        'recaptcha_site_key' => trim($_POST['recaptcha_site_key'] ?? ''),
        'recaptcha_secret_key' => trim($_POST['recaptcha_secret_key'] ?? ''),
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$key, $value]);
    }
    
    $msg = "Settings saved successfully!";
    
    // Log activity
    try {
        $pdo->prepare("INSERT INTO admin_activity_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)")
            ->execute([$_SESSION['user_id'], "Settings updated", "Site settings modified", $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch(Exception $e) {}
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
$marqueeEnabled = getSetting($pdo, 'marquee_enabled', '0');
$marqueeText = getSetting($pdo, 'marquee_text', '');
$marqueeBgColor = getSetting($pdo, 'marquee_bg_color', '#6B21A8');
$marqueeTextColor = getSetting($pdo, 'marquee_text_color', '#ffffff');
$marqueeSpeed = getSetting($pdo, 'marquee_speed', 'medium');
$marqueeLink = getSetting($pdo, 'marquee_link', '');
$marqueeIcon = getSetting($pdo, 'marquee_icon', '');
$founderSocialsVisible = getSetting($pdo, 'founder_socials_visible', '1');
$thriftTheme = getSetting($pdo, 'thrift_theme', 'current');

$captchaCfg = getCaptchaConfig($pdo);
$captchaEnabledSetting = $captchaCfg['enabled'] ? '1' : '0';
$captchaProvider = $captchaCfg['provider'];
$turnstileSiteKey = $captchaCfg['turnstile_site_key'];
$turnstileSecretKey = $captchaCfg['turnstile_secret_key'];
$recaptchaSiteKey = $captchaCfg['recaptcha_site_key'];
$recaptchaSecretKey = $captchaCfg['recaptcha_secret_key'];

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
        
        .sidebar { width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed; padding: 0.5rem 0; color: white; z-index: 100; }
        .brand { font-size: 1.2rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; text-decoration: none; }
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
            display: inline-block;
            width: 48px;
            min-width: 48px;
            height: 26px;
            flex-shrink: 0;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
            position: absolute;
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
            z-index: 1;
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
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
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

                <div class="settings-card">
                    <div class="card-title">
                        <ion-icon name="shield-checkmark-outline"></ion-icon>
                        CAPTCHA Protection
                    </div>

                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Enable CAPTCHA</div>
                            <div class="toggle-desc">Protect login &amp; registration forms from bots</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="captcha_enabled" id="captcha_enabled_toggle" <?php echo $captchaEnabledSetting === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div id="captcha_settings_panel" style="margin-top: 1rem; <?php echo $captchaEnabledSetting !== '1' ? 'display:none;' : ''; ?>">
                        <div class="form-group" style="margin-bottom: 1.2rem;">
                            <label style="font-weight: 600; margin-bottom: 0.5rem; display: block;">CAPTCHA Provider</label>
                            <div style="display: flex; gap: 1rem;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 0.75rem 1.2rem; border-radius: 8px; border: 2px solid <?php echo $captchaProvider === 'turnstile' ? '#6B21A8' : '#ddd'; ?>; background: <?php echo $captchaProvider === 'turnstile' ? '#f3f0ff' : '#fff'; ?>; flex:1;">
                                    <input type="radio" name="captcha_provider" value="turnstile" <?php echo $captchaProvider === 'turnstile' ? 'checked' : ''; ?> onchange="toggleCaptchaProvider()" style="width: auto;">
                                    <div>
                                        <div style="font-weight: 600; font-size: 0.9rem;">Cloudflare Turnstile</div>
                                        <div style="font-size: 0.75rem; color: #888;">Privacy-friendly, no puzzles</div>
                                    </div>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 0.75rem 1.2rem; border-radius: 8px; border: 2px solid <?php echo $captchaProvider === 'recaptcha' ? '#6B21A8' : '#ddd'; ?>; background: <?php echo $captchaProvider === 'recaptcha' ? '#f3f0ff' : '#fff'; ?>; flex:1;">
                                    <input type="radio" name="captcha_provider" value="recaptcha" <?php echo $captchaProvider === 'recaptcha' ? 'checked' : ''; ?> onchange="toggleCaptchaProvider()" style="width: auto;">
                                    <div>
                                        <div style="font-weight: 600; font-size: 0.9rem;">Google reCAPTCHA</div>
                                        <div style="font-size: 0.75rem; color: #888;">v2 checkbox challenge</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div id="turnstile_keys" style="padding: 1rem; background: #f3f0ff; border-radius: 8px; border: 1px solid #e9e0ff; margin-bottom: 1rem; <?php echo $captchaProvider !== 'turnstile' ? 'display:none;' : ''; ?>">
                            <div style="font-weight: 600; margin-bottom: 0.75rem; font-size: 0.9rem; color: #6B21A8;">
                                <ion-icon name="shield-outline" style="vertical-align: middle;"></ion-icon> Cloudflare Turnstile Keys
                            </div>
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label style="font-size: 0.85rem;">Site Key</label>
                                <input type="text" name="turnstile_site_key" value="<?php echo htmlspecialchars($turnstileSiteKey); ?>" placeholder="0x4AAAAAAC..." style="font-family: monospace; font-size: 0.85rem;">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 0.85rem;">Secret Key</label>
                                <input type="text" name="turnstile_secret_key" value="<?php echo htmlspecialchars($turnstileSecretKey); ?>" placeholder="0x4AAAAAAC..." style="font-family: monospace; font-size: 0.85rem;">
                            </div>
                        </div>

                        <div id="recaptcha_keys" style="padding: 1rem; background: #e8f0fe; border-radius: 8px; border: 1px solid #c4d7f5; margin-bottom: 1rem; <?php echo $captchaProvider !== 'recaptcha' ? 'display:none;' : ''; ?>">
                            <div style="font-weight: 600; margin-bottom: 0.75rem; font-size: 0.9rem; color: #1a73e8;">
                                <ion-icon name="logo-google" style="vertical-align: middle;"></ion-icon> Google reCAPTCHA v2 Keys
                            </div>
                            <div class="form-group" style="margin-bottom: 0.75rem;">
                                <label style="font-size: 0.85rem;">Site Key</label>
                                <input type="text" name="recaptcha_site_key" value="<?php echo htmlspecialchars($recaptchaSiteKey); ?>" placeholder="6Lc..." style="font-family: monospace; font-size: 0.85rem;">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 0.85rem;">Secret Key</label>
                                <input type="text" name="recaptcha_secret_key" value="<?php echo htmlspecialchars($recaptchaSecretKey); ?>" placeholder="6Lc..." style="font-family: monospace; font-size: 0.85rem;">
                            </div>
                        </div>

                        <p style="font-size: 0.75rem; color: #888; margin: 0;">Keys entered here are saved to the database. Environment variables (if set) are used as defaults.</p>
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

                <div class="settings-card full-width">
                    <div class="card-title">
                        <ion-icon name="megaphone-outline"></ion-icon>
                        Announcement Bar (Marquee)
                    </div>
                    
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Enable Announcement Bar</div>
                            <div class="toggle-desc">Show a scrolling announcement bar above the header</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="marquee_enabled" id="marquee_toggle" <?php echo $marqueeEnabled === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div id="marquee_settings" style="margin-top: 1rem; <?php echo $marqueeEnabled !== '1' ? 'display:none;' : ''; ?>">
                        <div class="form-group">
                            <label>Announcement Text</label>
                            <input type="text" name="marquee_text" value="<?php echo htmlspecialchars($marqueeText); ?>" placeholder="Welcome to Listaria! Free shipping on orders above ₹999">
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                            <div class="form-group">
                                <label>Background Color</label>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <input type="color" name="marquee_bg_color" value="<?php echo htmlspecialchars($marqueeBgColor); ?>" style="width:50px;height:36px;padding:2px;border-radius:6px;border:1px solid #ddd;cursor:pointer;">
                                    <input type="text" value="<?php echo htmlspecialchars($marqueeBgColor); ?>" style="flex:1;font-family:monospace;" onchange="this.previousElementSibling.value=this.value" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Text Color</label>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <input type="color" name="marquee_text_color" value="<?php echo htmlspecialchars($marqueeTextColor); ?>" style="width:50px;height:36px;padding:2px;border-radius:6px;border:1px solid #ddd;cursor:pointer;">
                                    <input type="text" value="<?php echo htmlspecialchars($marqueeTextColor); ?>" style="flex:1;font-family:monospace;" onchange="this.previousElementSibling.value=this.value" readonly>
                                </div>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                            <div class="form-group">
                                <label>Scroll Speed</label>
                                <select name="marquee_speed" style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.9rem;">
                                    <option value="slow" <?php echo $marqueeSpeed === 'slow' ? 'selected' : ''; ?>>Slow</option>
                                    <option value="medium" <?php echo $marqueeSpeed === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="fast" <?php echo $marqueeSpeed === 'fast' ? 'selected' : ''; ?>>Fast</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Link URL (optional)</label>
                                <input type="url" name="marquee_link" value="<?php echo htmlspecialchars($marqueeLink); ?>" placeholder="https://listaria.in/sale">
                            </div>
                            <div class="form-group">
                                <label>Icon Name (optional)</label>
                                <input type="text" name="marquee_icon" value="<?php echo htmlspecialchars($marqueeIcon); ?>" placeholder="e.g. gift-outline">
                                <small>Ionicon name, e.g. megaphone-outline</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-card full-width">
                    <div class="card-title">
                        <ion-icon name="people-outline"></ion-icon>
                        Founders Page
                    </div>
                    
                    <div class="toggle-group">
                        <div class="toggle-info">
                            <div class="toggle-label">Show Social Links</div>
                            <div class="toggle-desc">Display LinkedIn, Instagram, and X icons on the founders page</div>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="founder_socials_visible" <?php echo $founderSocialsVisible === '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="settings-card full-width">
                    <div class="card-title">
                        <ion-icon name="leaf-outline"></ion-icon>
                        Thrift+ Page Theme
                    </div>
                    <div class="toggle-desc" style="margin-bottom: 1rem;">Choose the visual theme for the Thrift+ marketplace page</div>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <label style="flex:1; min-width:200px; cursor:pointer; border:2px solid <?php echo $thriftTheme === 'current' ? '#6B21A8' : '#e2e8f0'; ?>; border-radius:12px; padding:1.2rem; background:<?php echo $thriftTheme === 'current' ? '#f3f0ff' : '#fff'; ?>; transition:all 0.2s;" onclick="selectThrift('current')">
                            <input type="radio" name="thrift_theme" value="current" <?php echo $thriftTheme === 'current' ? 'checked' : ''; ?> style="display:none;">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.8rem;">
                                <div style="width:36px; height:36px; border-radius:8px; background:linear-gradient(135deg, #8B6242, #5C3D2E); display:flex; align-items:center; justify-content:center;">
                                    <ion-icon name="checkmark-outline" style="color:#f5ede0; font-size:1.2rem;"></ion-icon>
                                </div>
                                <strong style="font-size:1rem;">Current Theme</strong>
                            </div>
                            <div style="font-size:0.82rem; color:#666; line-height:1.4;">Warm wood aesthetic with organic gradients, cream background, green accents, and modern card layout.</div>
                            <div style="display:flex; gap:4px; margin-top:0.8rem;">
                                <span style="width:24px; height:24px; border-radius:50%; background:#f3ebdc; border:1px solid #ccc;"></span>
                                <span style="width:24px; height:24px; border-radius:50%; background:#5C3D2E;"></span>
                                <span style="width:24px; height:24px; border-radius:50%; background:#8B6242;"></span>
                                <span style="width:24px; height:24px; border-radius:50%; background:#294631;"></span>
                                <span style="width:24px; height:24px; border-radius:50%; background:#C9A87C;"></span>
                            </div>
                        </label>
                        <label style="flex:1; min-width:200px; cursor:pointer; border:2px solid <?php echo $thriftTheme === 'og' ? '#6B21A8' : '#e2e8f0'; ?>; border-radius:12px; padding:1.2rem; background:<?php echo $thriftTheme === 'og' ? '#f3f0ff' : '#fff'; ?>; transition:all 0.2s;" onclick="selectThrift('og')">
                            <input type="radio" name="thrift_theme" value="og" <?php echo $thriftTheme === 'og' ? 'checked' : ''; ?> style="display:none;">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.8rem;">
                                <div style="width:36px; height:36px; border-radius:8px; background:#1a1a1a; display:flex; align-items:center; justify-content:center;">
                                    <ion-icon name="newspaper-outline" style="color:#eae4cc; font-size:1.2rem;"></ion-icon>
                                </div>
                                <strong style="font-size:1rem;">OG Theme</strong>
                            </div>
                            <div style="font-size:0.82rem; color:#666; line-height:1.4;">Classic retro newspaper look with parchment background, bold black borders, serif fonts, and vintage card style.</div>
                            <div style="display:flex; gap:4px; margin-top:0.8rem;">
                                <span style="width:24px; height:24px; border-radius:50%; background:#eae4cc; border:1px solid #ccc;"></span>
                                <span style="width:24px; height:24px; border-radius:50%; background:#1a1a1a;"></span>
                                <span style="width:24px; height:24px; border-radius:50%; background:#fdfcf8; border:1px solid #ccc;"></span>
                                <span style="width:24px; height:24px; border-radius:50%; background:#555555;"></span>
                                <span style="width:24px; height:24px; border-radius:50%; background:#333333;"></span>
                            </div>
                        </label>
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
<script>
document.getElementById('marquee_toggle').addEventListener('change', function() {
    document.getElementById('marquee_settings').style.display = this.checked ? '' : 'none';
});

document.getElementById('captcha_enabled_toggle').addEventListener('change', function() {
    document.getElementById('captcha_settings_panel').style.display = this.checked ? '' : 'none';
});

function selectThrift(val) {
    document.querySelectorAll('input[name="thrift_theme"]').forEach(function(r) {
        var lbl = r.closest('label');
        if (r.value === val) {
            r.checked = true;
            lbl.style.borderColor = '#6B21A8';
            lbl.style.background = '#f3f0ff';
        } else {
            r.checked = false;
            lbl.style.borderColor = '#e2e8f0';
            lbl.style.background = '#fff';
        }
    });
}

function toggleCaptchaProvider() {
    var provider = document.querySelector('input[name="captcha_provider"]:checked').value;
    document.getElementById('turnstile_keys').style.display = provider === 'turnstile' ? '' : 'none';
    document.getElementById('recaptcha_keys').style.display = provider === 'recaptcha' ? '' : 'none';

    document.querySelectorAll('input[name="captcha_provider"]').forEach(function(radio) {
        var lbl = radio.closest('label');
        if (radio.value === provider) {
            lbl.style.borderColor = '#6B21A8';
            lbl.style.background = '#f3f0ff';
        } else {
            lbl.style.borderColor = '#ddd';
            lbl.style.background = '#fff';
        }
    });
}
</script>
</body>
</html>
