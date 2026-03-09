<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listaria - Luxury Recommerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.2">
    <link rel="stylesheet" href="assets/css/responsive.css?v=1.0.2">
    <!-- Ionicons for icons (Filter, Arrows, etc) -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
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
            // Ensure we have user name
            $dName = 'User';
            if(isset($user['full_name'])) {
                $dName = $user['full_name'];
            } else {
                // Quick fetch if not available
                try {
                    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $u = $stmt->fetch();
                    if($u) $dName = $u['full_name'];
                } catch(PDOException $e) {}
            }
         ?>
            <!-- New Profile Block -->
            <div style="padding-bottom:15px; margin-bottom:15px; border-bottom:1px solid var(--border-color);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3 style="margin:0; font-size:1.1rem; font-weight:700; color:var(--primary-text);"><?php echo htmlspecialchars($dName); ?></h3>
                </div>
                <a href="profile.php" style="display:block; text-align:center; background:var(--brand-color); color:white; padding:12px; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.95rem;">
                    My Profile
                </a>
            </div>
            
            <!-- Other Links -->
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
