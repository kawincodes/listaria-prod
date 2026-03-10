<?php
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/session.php';
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    if (!function_exists('_getSeoSetting')) {
        function _getSeoSetting($pdo, $key, $default = '') {
            try {
                $s = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
                $s->execute([$key]);
                $v = $s->fetchColumn();
                return $v !== false ? $v : $default;
            } catch(Exception $e) { return $default; }
        }
    }
    $__siteName    = _getSeoSetting($pdo, 'site_name', 'Listaria');
    $__seoTitle    = _getSeoSetting($pdo, 'seo_meta_title', $__siteName . ' — Luxury Marketplace');
    $__seoDesc     = _getSeoSetting($pdo, 'seo_meta_description', '');
    $__seoKw       = _getSeoSetting($pdo, 'seo_meta_keywords', '');
    $__seoRobots   = _getSeoSetting($pdo, 'seo_robots_default', 'index, follow');
    $__canonical   = rtrim(_getSeoSetting($pdo, 'seo_canonical_base', 'https://listaria.in'), '/');
    $__ogTitle     = _getSeoSetting($pdo, 'seo_og_title', '');
    $__ogDesc      = _getSeoSetting($pdo, 'seo_og_description', '');
    $__ogImage     = _getSeoSetting($pdo, 'seo_og_image', '');
    $__gaId        = _getSeoSetting($pdo, 'seo_google_analytics_id', '');
    $__gtmId       = _getSeoSetting($pdo, 'seo_gtm_id', '');
    $__gsc         = _getSeoSetting($pdo, 'seo_search_console', '');

    $__finalTitle = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' . htmlspecialchars($__siteName) : htmlspecialchars($__seoTitle);
    $__finalDesc  = !empty($metaDesc) ? $metaDesc : $__seoDesc;
    $__finalOgTitle = !empty($__ogTitle) ? $__ogTitle : (isset($pageTitle) ? $pageTitle . ' — ' . $__siteName : $__seoTitle);
    $__finalOgDesc  = !empty($__ogDesc) ? $__ogDesc : $__finalDesc;
    $__pageUrl = $__canonical . '/' . basename($_SERVER['PHP_SELF']) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    ?>
    <title><?php echo $__finalTitle; ?></title>
    <?php if (!empty($__finalDesc)): ?>
    <meta name="description" content="<?php echo htmlspecialchars($__finalDesc); ?>">
    <?php endif; ?>
    <?php if (!empty($__seoKw)): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($__seoKw); ?>">
    <?php endif; ?>
    <meta name="robots" content="<?php echo htmlspecialchars($__seoRobots); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($__pageUrl); ?>">
    <?php if (!empty($__gsc)): ?>
    <meta name="google-site-verification" content="<?php echo htmlspecialchars($__gsc); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($__pageUrl); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($__finalOgTitle); ?>">
    <?php if (!empty($__finalOgDesc)): ?>
    <meta property="og:description" content="<?php echo htmlspecialchars($__finalOgDesc); ?>">
    <?php endif; ?>
    <?php if (!empty($__ogImage)): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($__ogImage); ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($__finalOgTitle); ?>">
    <?php if (!empty($__finalOgDesc)): ?>
    <meta name="twitter:description" content="<?php echo htmlspecialchars($__finalOgDesc); ?>">
    <?php endif; ?>
    <?php if (!empty($__ogImage)): ?>
    <meta name="twitter:image" content="<?php echo htmlspecialchars($__ogImage); ?>">
    <?php endif; ?>
    <?php if (!empty($__gtmId)): ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo htmlspecialchars($__gtmId); ?>');</script>
    <?php elseif (!empty($__gaId)): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo htmlspecialchars($__gaId); ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?php echo htmlspecialchars($__gaId); ?>');</script>
    <?php endif; ?>
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6B21A8">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Listaria">
    <link rel="apple-touch-icon" href="/assets/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/assets/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/assets/icons/icon-144x144.png">
    <link rel="apple-touch-icon" sizes="128x128" href="/assets/icons/icon-128x128.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/assets/icons/icon-96x96.png">
    <!-- /PWA -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.3">
    <link rel="stylesheet" href="assets/css/responsive.css?v=1.0.3">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet"></noscript>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js" defer></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js" defer></script>
    <style>
        .nav-badge {
            background-color: #ff4d4f;
            color: white;
            font-size: 0.7rem; /* Small but readable */
            padding: 2px 6px;
            border-radius: 10px; /* Pill shape */
            margin-left: 5px;
            font-weight: bold;
            vertical-align: middle;
            display: inline-block;
            line-height: normal;
        }

        .nav-dot {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 10px;
            height: 10px;
            background-color: #ff4d4f;
            border-radius: 50%;
            border: 2px solid white;
            z-index: 10;
        }
        
        /* Pulse animation for attention */
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(255, 77, 79, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(255, 77, 79, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 77, 79, 0); }
        }
        .nav-badge, .nav-dot {
            animation: pulse-red 2s infinite;
        }

        /* Mobile Header Compact Styles */
        @media (max-width: 480px) {
            .navbar {
                padding: 0.5rem !important;
                gap: 5px !important;
            }
            .brand-wrapper img {
                height: 28px !important;
            }
            .brand {
                font-size: 1.1rem !important;
            }
            .brand-location {
                display: none !important;
            }
            .nav-actions {
                gap: 4px !important;
            }
            .btn-thrift {
                padding: 0 0.6rem !important;
                font-size: 0.75rem !important;
                height: 30px !important;
                margin-left: 5px !important; /* Override large margin on small screens */
            }
            .btn-sell {
                padding: 0 0.6rem !important;
                font-size: 0.75rem !important;
                height: 30px !important;
            }
            .hamburger {
                margin-right: 0 !important;
                padding: 4px !important;
            }
            .hamburger ion-icon {
                font-size: 1.4rem !important;
            }
        }

        /* Hide Sell button on mobile for pages with bottom navigation (Home, Wishlist, Profile) */
        <?php if (in_array($current_page, ['index.php', 'wishlist.php', 'profile.php'])): ?>
        @media (max-width: 768px) {
            .btn-sell { display: none !important; }
        }
        <?php endif; ?>
    </style>
</head>
<body>

<?php
$__marqueeEnabled = _getSeoSetting($pdo, 'marquee_enabled', '0');
$__marqueeText = _getSeoSetting($pdo, 'marquee_text', '');
if ($__marqueeEnabled === '1' && !empty($__marqueeText) && empty($_SESSION['marquee_dismissed'])):
    $__marqueeBg = _getSeoSetting($pdo, 'marquee_bg_color', '#6B21A8');
    $__marqueeColor = _getSeoSetting($pdo, 'marquee_text_color', '#ffffff');
    $__marqueeSpeed = _getSeoSetting($pdo, 'marquee_speed', 'medium');
    $__marqueeLink = _getSeoSetting($pdo, 'marquee_link', '');
    $__marqueeIcon = _getSeoSetting($pdo, 'marquee_icon', '');
    $__speedDuration = ['slow' => '30s', 'medium' => '18s', 'fast' => '10s'][$__marqueeSpeed] ?? '18s';
?>
<style>
    @keyframes marquee-scroll { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }
    .announcement-bar { background: <?php echo htmlspecialchars($__marqueeBg); ?>; color: <?php echo htmlspecialchars($__marqueeColor); ?>; overflow: hidden; position: relative; white-space: nowrap; font-size: 0.82rem; font-family: 'Inter', sans-serif; }
    .announcement-bar .marquee-content { display: inline-block; padding: 7px 0; animation: marquee-scroll <?php echo $__speedDuration; ?> linear infinite; }
    .announcement-bar .marquee-content:hover { animation-play-state: paused; }
    .announcement-bar .marquee-close { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: <?php echo htmlspecialchars($__marqueeColor); ?>; cursor: pointer; font-size: 1rem; opacity: 0.7; z-index: 2; padding: 4px; }
    .announcement-bar .marquee-close:hover { opacity: 1; }
    .announcement-bar a { color: inherit; text-decoration: underline; }
</style>
<div class="announcement-bar" id="announcementBar">
    <div class="marquee-content">
        <?php if (!empty($__marqueeIcon)): ?><ion-icon name="<?php echo htmlspecialchars($__marqueeIcon); ?>" style="vertical-align:middle;margin-right:6px;"></ion-icon><?php endif; ?>
        <?php if (!empty($__marqueeLink)): ?><a href="<?php echo htmlspecialchars($__marqueeLink); ?>"><?php echo htmlspecialchars($__marqueeText); ?></a><?php else: echo htmlspecialchars($__marqueeText); endif; ?>
    </div>
    <button class="marquee-close" onclick="document.getElementById('announcementBar').style.display='none';fetch('marquee_dismiss.php',{method:'POST'});" title="Dismiss">&times;</button>
</div>
<?php endif; ?>

    <nav class="navbar">
        <!-- Logo -->
        <a href="index.php" class="brand-wrapper" style="display:flex; flex-direction:row; align-items:center; gap:8px;">
            <img src="assets/logo.jpg" alt="Listaria Logo" style="height:40px; width:auto;">
            <div>
                <span class="brand" style="display:block; line-height:1;">listaria</span>
                <span class="brand-location" style="font-size:0.7rem; color:#666;">Live in BLR, India</span>
            </div>
        </a>
        
        <!-- Search Bar -->
        <div class="search-container">
            <form action="index.php" method="GET" style="display:flex; width:100%; align-items:center;">
                <input type="text" name="search" class="search-input" placeholder="Search for luxury items..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" style="display:none;"></button>
                <ion-icon name="options-outline" class="search-filter-icon"></ion-icon>
            </form>
        </div>

        <!-- Desktop Text Links (Hidden on mobile usually, or shown in drawer) -->
        <div class="nav-links">
            <a href="blogs.php" class="nav-link">Blogs</a>
            <a href="stores.php" class="nav-link">Stores</a>
            <a href="about.php" class="nav-link">About Us</a>
            <a href="founders.php" class="nav-link">Founders</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="nav-link" style="color:red;">Logout</a>
            <?php endif; ?>
        </div>

        <!-- Action Buttons (Sign In, Sell, Profile) -->
        <div class="nav-actions">
            <?php 
            // Notification Logic
            $notif_count = 0;
            if(isset($_SESSION['user_id']) && isset($pdo)) {
                try {
                    // Count unread as SELLER
                    $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM negotiations WHERE seller_id = ? AND is_read = 0");
                    $stmt1->execute([$_SESSION['user_id']]);
                    $seller_notifs = $stmt1->fetchColumn();

                    // Count unread as BUYER
                    // Note: 'is_buyer_read' column might not exist yet if self-healing hasn't run.
                    // We wrap in try-catch or just try query.
                    $buyer_notifs = 0;
                    try {
                        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM negotiations WHERE buyer_id = ? AND is_buyer_read = 0");
                        $stmt2->execute([$_SESSION['user_id']]);
                        $buyer_notifs = $stmt2->fetchColumn();
                    } catch (Exception $e) { /* Ignore if column missing */ }

                    $notif_count = $seller_notifs + $buyer_notifs;
                } catch(Exception $e) {
                    // Ignore errors
                }
            }
            ?>

            <?php if(isset($_SESSION['user_id'])): ?>
                <!-- Profile Link / Avatar -->
                 <!-- Shorten for mobile if needed, or just icon -->

                <a href="profile.php" class="nav-link profile-link" style="position: relative;">
                    My Dashboard
                    <?php if($notif_count > 0): ?>
                        <span class="nav-badge"><?php echo $notif_count; ?></span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn-signin">Sign in</a>
            <?php endif; ?>

            <?php if(isset($_SESSION['user_id'])): ?>
            <a href="thrift.php" class="btn-thrift" style="background: #6B21A8; color: #fff; height: 34px; padding: 0 1.2rem; border-radius: 40px; text-decoration: none; font-weight: 600; font-size: 0.85rem; margin-right: 5px; margin-left: 15px; transition: all 0.2s; display:inline-flex; align-items:center; justify-content:center; gap:5px; border: 1px solid #7c3aed; box-shadow: 0 4px 14px rgba(107, 33, 168, 0.4);">
                <ion-icon name="sparkles" style="color:#fff; font-size:1rem;"></ion-icon> Thrift+
            </a>
            <?php endif; ?>


            
            <?php 
            $current_page = basename($_SERVER['PHP_SELF']);
            $sell_link = ($current_page == 'thrift.php') ? 'sell.php?source=thrift' : 'sell.php';
            ?>

            <a href="<?php echo $sell_link; ?>" class="btn-sell">
                Sell <ion-icon name="arrow-forward-outline"></ion-icon>
            </a>

            <div class="hamburger" onclick="toggleMenu()" style="margin-right: 8px;">
                <div style="position:relative;">
                    <ion-icon name="menu-outline"></ion-icon>
                    <?php if($notif_count > 0): ?>
                        <span class="nav-dot"></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Menu Drawer (Initially hidden) -->
    <div class="mobile-menu-drawer">
        <div class="drawer-header">
            <span class="brand" style="font-size:1.5rem;">listaria</span>
            <div class="close-btn" onclick="toggleMenu()">
                <ion-icon name="close-outline" style="font-size:1.8rem;"></ion-icon>
            </div>
        </div>
        <div class="drawer-links">
         <?php if(isset($_SESSION['user_id'])): 
            // Fetch user name and account_type fresh from DB for accuracy
            $dName = 'User';
            $menuAccountType = $_SESSION['account_type'] ?? 'customer';
            $menuIsAdmin = !empty($_SESSION['is_admin']);
            $menuIsVendor = false;
            if(isset($user['full_name'])) {
                $dName = $user['full_name'];
                $menuAccountType = $user['account_type'] ?? $menuAccountType;
                $menuIsAdmin = !empty($user['is_admin']) || $menuIsAdmin;
                $menuIsVendor = ($menuAccountType === 'vendor') || !empty($user['is_verified_vendor']);
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT full_name, account_type, is_admin, is_verified_vendor FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $u = $stmt->fetch();
                    if($u) {
                        $dName = $u['full_name'];
                        $menuAccountType = $u['account_type'] ?? $menuAccountType;
                        $menuIsAdmin = !empty($u['is_admin']);
                        $menuIsVendor = ($menuAccountType === 'vendor') || !empty($u['is_verified_vendor']);
                        if(($_SESSION['account_type'] ?? '') !== $menuAccountType) {
                            $_SESSION['account_type'] = $menuAccountType;
                        }
                    }
                } catch(PDOException $e) {}
            }
         ?>
            <!-- Profile Block -->
            <div style="padding-bottom:15px; margin-bottom:15px; border-bottom:1px solid var(--border-color);">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                    <div style="width:38px; height:38px; border-radius:50%; background:var(--brand-color); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1rem; flex-shrink:0;">
                        <?php echo strtoupper(substr($dName, 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight:700; color:var(--primary-text); font-size:0.95rem;"><?php echo htmlspecialchars($dName); ?></div>
                        <?php if($menuIsAdmin): ?>
                        <div style="font-size:0.75rem; color:#dc2626; font-weight:600;">Admin</div>
                        <?php elseif($menuIsVendor): ?>
                        <div style="font-size:0.75rem; color:#7c3aed; font-weight:600;">Vendor</div>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="profile.php" style="display:block; text-align:center; background:var(--brand-color); color:white; padding:10px; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.9rem;">
                    My Profile
                </a>
            </div>

            <?php if($menuIsVendor): ?>
            <div style="margin-bottom:15px; padding-bottom:15px; border-bottom:1px solid var(--border-color);">
                <div style="font-size:0.7rem; font-weight:700; color:#7c3aed; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:8px; padding-left:4px;">Vendor</div>
                <a href="vendor_settings.php" class="mobile-link" style="display:flex; align-items:center; gap:10px;">
                    <ion-icon name="settings-outline" style="font-size:1.2rem; color:#7c3aed;"></ion-icon> Vendor Settings
                </a>
                <a href="sell.php" class="mobile-link" style="display:flex; align-items:center; gap:10px;">
                    <ion-icon name="add-circle-outline" style="font-size:1.2rem; color:#7c3aed;"></ion-icon> New Listing
                </a>
                <a href="sell.php?source=thrift" class="mobile-link" style="display:flex; align-items:center; gap:10px;">
                    <ion-icon name="leaf-outline" style="font-size:1.2rem; color:#27ae60;"></ion-icon> Sell on Thrift+
                </a>
                <a href="vendor_bulk_upload.php" class="mobile-link" style="display:flex; align-items:center; gap:10px;">
                    <ion-icon name="cloud-upload-outline" style="font-size:1.2rem; color:#7c3aed;"></ion-icon> Bulk Upload
                </a>
                <a href="profile.php?tab=orders#my-sales" class="mobile-link" style="display:flex; align-items:center; gap:10px;">
                    <ion-icon name="receipt-outline" style="font-size:1.2rem; color:#7c3aed;"></ion-icon> My Sales
                </a>
            </div>
            <?php endif; ?>

            <?php if($menuIsAdmin): ?>
            <div style="margin-bottom:15px; padding-bottom:15px; border-bottom:1px solid var(--border-color);">
                <div style="font-size:0.7rem; font-weight:700; color:#dc2626; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:8px; padding-left:4px;">Admin</div>
                <a href="admin_dashboard.php" class="mobile-link" style="display:flex; align-items:center; gap:10px;">
                    <ion-icon name="shield-outline" style="font-size:1.2rem; color:#dc2626;"></ion-icon> Admin Panel
                </a>
            </div>
            <?php endif; ?>
            
            <!-- General Links -->
            <a href="requests.php" class="mobile-link">Requests</a>
            <a href="blogs.php" class="mobile-link">Blogs</a>
            <a href="about.php" class="mobile-link">About Us</a>
            <a href="founders.php" class="mobile-link">Founders</a>
            <a href="wishlist.php" class="mobile-link">Wishlist</a>
            <a href="stores.php" class="mobile-link">Stores</a>
            <a href="logout.php" class="mobile-link" style="color:red;">Logout</a>
         <?php else: ?>
            <a href="login.php" class="mobile-link" style="font-weight:600; color:var(--brand-color);">Sign In</a>
            <a href="requests.php" class="mobile-link">Requests</a>
            <a href="blogs.php" class="mobile-link">Blogs</a>
            <a href="about.php" class="mobile-link">About Us</a>
            <a href="founders.php" class="mobile-link">Founders</a>
         <?php endif; ?>
            <div style="margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                <button id="theme-toggle" class="mobile-link" style="background:none; border:none; padding:0; cursor:pointer; display:flex; align-items:center; gap:10px; width:100%; text-align:left;">
                    <ion-icon name="moon-outline"></ion-icon> Switch Theme
                </button>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const drawer = document.querySelector('.mobile-menu-drawer');
            const bottomNav = document.querySelector('.mobile-bottom-nav');
            
            if (drawer) {
                drawer.classList.toggle('active');
                
                // Hide bottom nav when drawer is active
                if (bottomNav) {
                    if (drawer.classList.contains('active')) {
                        bottomNav.style.display = 'none';
                    } else {
                        bottomNav.style.display = 'flex';
                    }
                }
            }
        }
    </script>
