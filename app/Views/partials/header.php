<?php
$session = session();
$currentPath = uri_string();
$userId = $session->get('user_id');
$notifCount = 0;
if ($userId) {
    try {
        $db = \Config\Database::connect();
        $sellerNotifs = $db->query("SELECT COUNT(*) as cnt FROM negotiations WHERE seller_id = ? AND is_read = 0", [$userId])->getRow()->cnt ?? 0;
        $buyerNotifs = 0;
        try {
            $buyerNotifs = $db->query("SELECT COUNT(*) as cnt FROM negotiations WHERE buyer_id = ? AND is_buyer_read = 0", [$userId])->getRow()->cnt ?? 0;
        } catch (\Exception $e) {}
        $notifCount = $sellerNotifs + $buyerNotifs;
    } catch (\Exception $e) {}
}
?>
<style>
    .nav-badge {
        background-color: #ff4d4f; color: white; font-size: 0.7rem; padding: 2px 6px;
        border-radius: 10px; margin-left: 5px; font-weight: bold; vertical-align: middle;
        display: inline-block; line-height: normal;
    }
    .nav-dot {
        position: absolute; top: -2px; right: -2px; width: 10px; height: 10px;
        background-color: #ff4d4f; border-radius: 50%; border: 2px solid white; z-index: 10;
    }
    @keyframes pulse-red {
        0% { box-shadow: 0 0 0 0 rgba(255, 77, 79, 0.4); }
        70% { box-shadow: 0 0 0 6px rgba(255, 77, 79, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 77, 79, 0); }
    }
    .nav-badge, .nav-dot { animation: pulse-red 2s infinite; }
    @media (max-width: 480px) {
        .navbar { padding: 0.5rem !important; gap: 5px !important; }
        .brand-wrapper img { height: 28px !important; }
        .brand { font-size: 1.1rem !important; }
        .brand-location { display: none !important; }
        .nav-actions { gap: 4px !important; }
        .btn-thrift, .btn-sell { padding: 0 0.6rem !important; font-size: 0.75rem !important; height: 30px !important; }
        .hamburger { margin-right: 0 !important; padding: 4px !important; }
        .hamburger ion-icon { font-size: 1.4rem !important; }
    }
</style>

<nav class="navbar">
    <a href="/" class="brand-wrapper" style="display:flex; flex-direction:row; align-items:center; gap:8px;">
        <img src="/assets/logo.jpg" alt="Listaria Logo" style="height:40px; width:auto;">
        <div>
            <span class="brand" style="display:block; line-height:1;">listaria</span>
            <span class="brand-location" style="font-size:0.7rem; color:#666;">Live in BLR, India</span>
        </div>
    </a>

    <div class="search-container">
        <form action="/" method="GET" style="display:flex; width:100%; align-items:center;">
            <input type="text" name="search" class="search-input" placeholder="Search for luxury items..." value="<?= esc($search ?? '') ?>">
            <button type="submit" style="display:none;"></button>
            <ion-icon name="options-outline" class="search-filter-icon"></ion-icon>
        </form>
    </div>

    <div class="nav-links">
        <a href="/blogs" class="nav-link">Blogs</a>
        <a href="/stores" class="nav-link">Stores</a>
        <a href="/about" class="nav-link">About Us</a>
        <a href="/founders" class="nav-link">Founders</a>
        <?php if ($userId): ?>
            <a href="/logout" class="nav-link" style="color:red;">Logout</a>
        <?php endif; ?>
    </div>

    <div class="nav-actions">
        <?php if ($userId): ?>
            <a href="/profile" class="nav-link profile-link" style="position: relative;">
                My Dashboard
                <?php if ($notifCount > 0): ?>
                    <span class="nav-badge"><?= $notifCount ?></span>
                <?php endif; ?>
            </a>
        <?php else: ?>
            <a href="/login" class="btn-signin">Sign in</a>
        <?php endif; ?>

        <?php if ($userId): ?>
            <a href="/sell" class="btn-thrift" style="background: #6B21A8; color: #fff; height: 34px; padding: 0 1.2rem; border-radius: 40px; text-decoration: none; font-weight: 600; font-size: 0.85rem; margin-right: 5px; margin-left: 15px; transition: all 0.2s; display:inline-flex; align-items:center; justify-content:center; gap:5px; border: 1px solid #7c3aed; box-shadow: 0 4px 14px rgba(107, 33, 168, 0.4);">
                <ion-icon name="sparkles" style="color:#fff; font-size:1rem;"></ion-icon> Thrift+
            </a>
        <?php endif; ?>

        <a href="/sell" class="btn-sell">
            Sell <ion-icon name="arrow-forward-outline"></ion-icon>
        </a>

        <div class="hamburger" onclick="toggleMenu()" style="margin-right: 8px;">
            <div style="position:relative;">
                <ion-icon name="menu-outline"></ion-icon>
                <?php if ($notifCount > 0): ?><span class="nav-dot"></span><?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<div class="mobile-menu-drawer">
    <div class="drawer-header">
        <span class="brand" style="font-size:1.5rem;">listaria</span>
        <div class="close-btn" onclick="toggleMenu()">
            <ion-icon name="close-outline" style="font-size:1.8rem;"></ion-icon>
        </div>
    </div>
    <div class="drawer-links">
        <?php if ($userId): ?>
            <div style="padding-bottom:15px; margin-bottom:15px; border-bottom:1px solid var(--border-color);">
                <h3 style="margin:0; font-size:1.1rem; font-weight:700; color:var(--primary-text);"><?= esc($session->get('full_name') ?? 'User') ?></h3>
                <a href="/profile" style="display:block; text-align:center; background:var(--brand-color); color:white; padding:12px; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.95rem; margin-top:10px;">My Profile</a>
            </div>
            <a href="/requests" class="mobile-link">Requests</a>
            <a href="/blogs" class="mobile-link">Blogs</a>
            <a href="/about" class="mobile-link">About Us</a>
            <a href="/founders" class="mobile-link">Founders</a>
            <a href="/wishlist" class="mobile-link">Wishlist</a>
            <a href="/stores" class="mobile-link">Stores</a>
            <a href="/logout" class="mobile-link" style="color:red;">Logout</a>
        <?php else: ?>
            <a href="/login" class="mobile-link" style="font-weight:600; color:var(--brand-color);">Sign In</a>
            <a href="/requests" class="mobile-link">Requests</a>
            <a href="/blogs" class="mobile-link">Blogs</a>
            <a href="/about" class="mobile-link">About Us</a>
            <a href="/founders" class="mobile-link">Founders</a>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleMenu() {
    const drawer = document.querySelector('.mobile-menu-drawer');
    const bottomNav = document.querySelector('.mobile-bottom-nav');
    if (drawer) {
        drawer.classList.toggle('active');
        if (bottomNav) {
            bottomNav.style.display = drawer.classList.contains('active') ? 'none' : 'flex';
        }
    }
}
</script>
