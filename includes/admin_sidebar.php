<?php
$isSuperAdmin = ($_SESSION['role'] ?? '') === 'super_admin';
$_sidebarPendingVendors = 0;
$_sidebarPendingKYC = 0;
$_sidebarPendingPayments = 0;
try {
    $_sidebarPendingVendors = $pdo->query("SELECT COUNT(*) FROM users WHERE vendor_status = 'pending'")->fetchColumn();
    $_sidebarPendingKYC = $pdo->query("SELECT COUNT(*) FROM users WHERE kyc_status = 'pending'")->fetchColumn();
    $_sidebarPendingPayments = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('Pending', 'Verification Pending') AND LOWER(payment_method) != 'cod'")->fetchColumn();
} catch (Exception $e) {}
?>
<style>
    .sidebar {
        display: flex !important;
        flex-direction: column !important;
        overflow-y: auto !important;
        scrollbar-width: thin;
        scrollbar-color: rgba(107,33,168,0.3) transparent;
        padding-bottom: 1rem !important;
        background: linear-gradient(180deg, #0f0a1a 0%, #1a1025 50%, #0f0a1a 100%) !important;
    }
    .sidebar::-webkit-scrollbar { width: 4px; }
    .sidebar::-webkit-scrollbar-track { background: transparent; }
    .sidebar::-webkit-scrollbar-thumb { background: rgba(107,33,168,0.3); border-radius: 4px; }
    .sidebar::-webkit-scrollbar-thumb:hover { background: rgba(107,33,168,0.5); }

    .menu-item, .brand { flex-shrink: 0 !important; }

    .sidebar .brand {
        padding: 1.5rem 1.2rem 1rem !important;
        margin-bottom: 0.5rem !important;
        border-bottom: 1px solid rgba(107,33,168,0.15);
        position: relative;
    }
    .sidebar .brand::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 1.2rem;
        width: 40px;
        height: 2px;
        background: #6B21A8;
        border-radius: 1px;
    }
    .brand-icon-wrap {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #6B21A8, #9333EA);
        border-radius: 10px;
        font-size: 1.1rem;
        color: white;
        flex-shrink: 0;
    }
    .brand-text {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }
    .brand-text span:first-child {
        font-size: 1.1rem;
        font-weight: 700;
        letter-spacing: -0.3px;
    }
    .brand-text span:last-child {
        font-size: 0.65rem;
        color: #6B21A8;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    .sidebar .nav-section-label {
        color: rgba(255,255,255,0.3);
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        padding: 1.2rem 1.2rem 0.5rem;
    }

    .sidebar .menu-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0.7rem 1.2rem;
        margin: 2px 0.6rem;
        color: rgba(255,255,255,0.55);
        text-decoration: none;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 500;
        transition: all 0.2s ease;
        position: relative;
    }
    .sidebar .menu-item ion-icon {
        font-size: 1.15rem;
        flex-shrink: 0;
    }
    .sidebar .menu-item:hover {
        color: rgba(255,255,255,0.9);
        background: rgba(107,33,168,0.12);
    }
    .sidebar .menu-item.active {
        color: white;
        background: linear-gradient(135deg, rgba(107,33,168,0.35), rgba(147,51,234,0.2));
        font-weight: 600;
    }
    .sidebar .menu-item.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 60%;
        background: #9333EA;
        border-radius: 0 3px 3px 0;
    }
    .sidebar .menu-item.active ion-icon {
        color: #c084fc;
    }

    .sidebar .nav-section-label.super-admin-label {
        color: #9333EA;
    }

    .sidebar .nav-divider {
        height: 1px;
        background: rgba(255,255,255,0.06);
        margin: 0.5rem 1.2rem;
    }

    .sidebar .sidebar-footer {
        border-top: 1px solid rgba(255,255,255,0.06);
        margin-top: auto;
        padding-top: 0.5rem;
    }
    .sidebar .sidebar-footer .menu-item {
        font-size: 0.85rem;
    }
    .sidebar .menu-item.logout-btn {
        color: rgba(239,68,68,0.7);
    }
    .sidebar .menu-item.logout-btn:hover {
        color: #ef4444;
        background: rgba(239,68,68,0.08);
    }

    .sidebar .menu-badge {
        margin-left: auto;
        font-size: 0.65rem;
        padding: 2px 7px;
        border-radius: 10px;
        font-weight: 600;
        background: rgba(107,33,168,0.25);
        color: #c084fc;
    }
</style>
<nav class="sidebar">
    <a href="index.php" class="brand">
        <div class="brand-icon-wrap">
            <ion-icon name="shield-checkmark"></ion-icon>
        </div>
        <div class="brand-text">
            <span>Listaria</span>
            <span>Admin Panel</span>
        </div>
    </a>
    
    <div class="nav-section-label">Main</div>
    
    <a href="admin_dashboard.php" class="menu-item <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
        <ion-icon name="grid-outline"></ion-icon> Dashboard
    </a>
    
    <a href="admin_analytics.php" class="menu-item <?php echo ($activePage == 'analytics') ? 'active' : ''; ?>">
        <ion-icon name="analytics-outline"></ion-icon> Analytics
    </a>
    
    <div class="nav-divider"></div>
    <div class="nav-section-label">Management</div>
    
    <a href="admin_users.php" class="menu-item <?php echo ($activePage == 'users') ? 'active' : ''; ?>">
        <ion-icon name="people-outline"></ion-icon> Users
    </a>
    
    <a href="admin_listings.php" class="menu-item <?php echo ($activePage == 'listings') ? 'active' : ''; ?>">
        <ion-icon name="pricetags-outline"></ion-icon> Listings
    </a>
    
    <a href="admin_requests.php" class="menu-item <?php echo ($activePage == 'requests') ? 'active' : ''; ?>">
        <ion-icon name="megaphone-outline"></ion-icon> Product Requests
    </a>
    
    <a href="admin_transactions.php" class="menu-item <?php echo ($activePage == 'transactions') ? 'active' : ''; ?>">
        <ion-icon name="wallet-outline"></ion-icon> Transactions
    </a>
    
    <a href="admin_returns.php" class="menu-item <?php echo ($activePage == 'returns') ? 'active' : ''; ?>">
        <ion-icon name="return-down-back-outline"></ion-icon> Returns
    </a>
    
    <a href="admin_support.php" class="menu-item <?php echo ($activePage == 'support') ? 'active' : ''; ?>">
        <ion-icon name="ticket-outline"></ion-icon> Support Tickets
    </a>
    
    <a href="admin_chats.php" class="menu-item <?php echo ($activePage == 'chats') ? 'active' : ''; ?>">
        <ion-icon name="chatbubbles-outline"></ion-icon> Chats
    </a>
    
    <div class="nav-divider"></div>
    <div class="nav-section-label">Vendor</div>
    
    <a href="admin_users.php?filter=vendor_apps" class="menu-item <?php echo ($activePage == 'vendor_apps') ? 'active' : ''; ?>">
        <ion-icon name="storefront-outline"></ion-icon> Vendor Applications
        <?php if($_sidebarPendingVendors > 0): ?>
        <span class="menu-badge" style="background:rgba(239,68,68,0.2);color:#ef4444;"><?php echo $_sidebarPendingVendors; ?></span>
        <?php endif; ?>
    </a>
    
    <a href="admin_users.php?kyc=pending" class="menu-item <?php echo ($activePage == 'kyc_pending') ? 'active' : ''; ?>">
        <ion-icon name="document-attach-outline"></ion-icon> KYC Verification
        <?php if($_sidebarPendingKYC > 0): ?>
        <span class="menu-badge" style="background:rgba(239,68,68,0.2);color:#ef4444;"><?php echo $_sidebarPendingKYC; ?></span>
        <?php endif; ?>
    </a>
    
    <a href="admin_users.php?filter=verified_vendors" class="menu-item <?php echo ($activePage == 'verified_vendors') ? 'active' : ''; ?>">
        <ion-icon name="checkmark-done-circle-outline"></ion-icon> Verified Vendors
    </a>
    
    <a href="admin_listings.php?filter=vendor" class="menu-item <?php echo ($activePage == 'vendor_products') ? 'active' : ''; ?>">
        <ion-icon name="cube-outline"></ion-icon> Vendor Products
    </a>
    
    <a href="admin_payment_verify.php" class="menu-item <?php echo ($activePage == 'payment_verify') ? 'active' : ''; ?>">
        <ion-icon name="receipt-outline"></ion-icon> Payment Verification
        <?php if($_sidebarPendingPayments > 0): ?>
        <span class="menu-badge" style="background:rgba(239,68,68,0.2);color:#ef4444;"><?php echo $_sidebarPendingPayments; ?></span>
        <?php endif; ?>
    </a>
    
    <a href="admin_transactions.php?filter=vendor_sales" class="menu-item <?php echo ($activePage == 'vendor_sales') ? 'active' : ''; ?>">
        <ion-icon name="cash-outline"></ion-icon> Vendor Sales
    </a>
    
    <a href="vendor_bulk_upload.php" class="menu-item <?php echo ($activePage ?? '') == 'vendor_bulk_upload' ? 'active' : ''; ?>">
        <ion-icon name="cloud-upload-outline"></ion-icon> Bulk Upload
    </a>
    
    <a href="admin_returns.php?filter=vendor" class="menu-item <?php echo ($activePage == 'vendor_returns') ? 'active' : ''; ?>">
        <ion-icon name="arrow-undo-outline"></ion-icon> Vendor Returns
    </a>

    <div class="nav-divider"></div>
    <div class="nav-section-label">Marketing</div>

    <a href="admin_coupons.php" class="menu-item <?php echo ($activePage == 'coupons') ? 'active' : ''; ?>">
        <ion-icon name="pricetag-outline"></ion-icon> Coupons
    </a>

    <div class="nav-divider"></div>
    <div class="nav-section-label">Content</div>
    
    <a href="admin_blogs.php" class="menu-item <?php echo ($activePage == 'blogs') ? 'active' : ''; ?>">
        <ion-icon name="newspaper-outline"></ion-icon> Blogs
    </a>
    
    <a href="admin_pages.php" class="menu-item <?php echo ($activePage == 'pages') ? 'active' : ''; ?>">
        <ion-icon name="document-text-outline"></ion-icon> Pages
    </a>
    
    <a href="admin_banners.php" class="menu-item <?php echo ($activePage == 'banners') ? 'active' : ''; ?>">
        <ion-icon name="images-outline"></ion-icon> Banners
    </a>
    
    <?php if($isSuperAdmin): ?>
    <div class="nav-divider"></div>
    <div class="nav-section-label super-admin-label">
        <ion-icon name="shield-checkmark" style="vertical-align:middle;margin-right:4px;font-size:0.75rem;"></ion-icon> Super Admin
    </div>
    
    <a href="admin_roles.php" class="menu-item <?php echo ($activePage == 'roles') ? 'active' : ''; ?>">
        <ion-icon name="key-outline"></ion-icon> Roles & Permissions
    </a>
    
    <a href="admin_activity.php" class="menu-item <?php echo ($activePage == 'activity') ? 'active' : ''; ?>">
        <ion-icon name="time-outline"></ion-icon> Activity Logs
    </a>
    
    <a href="admin_security.php" class="menu-item <?php echo ($activePage == 'security') ? 'active' : ''; ?>">
        <ion-icon name="shield-outline"></ion-icon> Security
    </a>
    <?php endif; ?>
    
    <div class="nav-divider"></div>
    <div class="nav-section-label">Settings</div>
    
    <a href="admin_settings.php" class="menu-item <?php echo ($activePage == 'settings') ? 'active' : ''; ?>">
        <ion-icon name="settings-outline"></ion-icon> Site Settings
    </a>
    
    <a href="admin_email_templates.php" class="menu-item <?php echo ($activePage == 'email_templates') ? 'active' : ''; ?>">
        <ion-icon name="mail-outline"></ion-icon> Email Templates
    </a>

    <a href="admin_seo.php" class="menu-item <?php echo ($activePage == 'seo') ? 'active' : ''; ?>">
        <ion-icon name="search-outline"></ion-icon> SEO & Sitemap
    </a>

    <div class="nav-divider"></div>
    <div class="nav-section-label">Developer Tools</div>

    <a href="admin_database.php" class="menu-item <?php echo ($activePage == 'database') ? 'active' : ''; ?>">
        <ion-icon name="server-outline"></ion-icon> Database Editor
    </a>
    
    <div class="sidebar-footer">
        <a href="index.php" class="menu-item">
            <ion-icon name="arrow-back-outline"></ion-icon> Back to Site
        </a>
        <a href="logout.php" class="menu-item logout-btn">
            <ion-icon name="log-out-outline"></ion-icon> Logout
        </a>
    </div>
</nav>
