<?php $isSuperAdmin = ($_SESSION['role'] ?? '') === 'super_admin'; ?>
<style>
    /* Global Sidebar Scroll Fix */
    .sidebar {
        display: flex !important;
        flex-direction: column !important;
        overflow-y: auto !important;
        scrollbar-width: thin;
        scrollbar-color: #333 #1a1a1a;
        padding-bottom: 2rem !important; /* Ensure bottom content isn't cut off */
    }
    
    /* Professional Scrollbar */
    .sidebar::-webkit-scrollbar { width: 6px; }
    .sidebar::-webkit-scrollbar-track { background: #1a1a1a; }
    .sidebar::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
    .sidebar::-webkit-scrollbar-thumb:hover { background: #444; }

    /* Prevent item shrinking */
    .menu-item, .brand { flex-shrink: 0 !important; }
</style>
<nav class="sidebar">
    <a href="index.php" class="brand">
        <ion-icon name="shield-checkmark" style="color:#6B21A8;"></ion-icon>
        listaria admin
    </a>
    
    <div style="color:#666; font-size:0.7rem; text-transform:uppercase; padding:0 1rem; margin-bottom:0.5rem;">Main</div>
    
    <a href="admin_dashboard.php" class="menu-item <?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>">
        <ion-icon name="grid-outline"></ion-icon> Dashboard
    </a>
    
    <a href="admin_analytics.php" class="menu-item <?php echo ($activePage == 'analytics') ? 'active' : ''; ?>">
        <ion-icon name="analytics-outline"></ion-icon> Analytics
    </a>
    
    <div style="color:#666; font-size:0.7rem; text-transform:uppercase; padding:0 1rem; margin:1rem 0 0.5rem;">Management</div>
    
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
    
    <div style="color:#666; font-size:0.7rem; text-transform:uppercase; padding:0 1rem; margin:1rem 0 0.5rem;">Content</div>
    
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
    <div style="color:#6B21A8; font-size:0.7rem; text-transform:uppercase; padding:0 1rem; margin:1rem 0 0.5rem;">
        <ion-icon name="shield-checkmark" style="vertical-align:middle;"></ion-icon> Super Admin
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
    
    <div style="color:#666; font-size:0.7rem; text-transform:uppercase; padding:0 1rem; margin:1rem 0 0.5rem;">Settings</div>
    
    <a href="admin_settings.php" class="menu-item <?php echo ($activePage == 'settings') ? 'active' : ''; ?>">
        <ion-icon name="settings-outline"></ion-icon> Site Settings
    </a>
    
    <div style="flex:1;"></div>
    
    <a href="index.php" class="menu-item" style="border-top: 1px solid #333; padding-top: 1rem; margin-top: 1rem;">
        <ion-icon name="arrow-back-outline"></ion-icon> Back to Site
    </a>
    
    <a href="logout.php" class="menu-item" style="color: #ef4444;">
        <ion-icon name="log-out-outline"></ion-icon> Logout
    </a>
</nav>
