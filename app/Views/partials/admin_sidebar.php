<?php $isSuperAdmin = (session()->get('role') ?? '') === 'super_admin'; ?>
<nav class="sidebar">
    <a href="/" class="brand">
        <ion-icon name="shield-checkmark" style="color:#6B21A8;"></ion-icon>
        listaria admin
    </a>

    <div style="color:#666; font-size:0.7rem; text-transform:uppercase; padding:0 1rem; margin-bottom:0.5rem;">Main</div>

    <a href="/admin/dashboard" class="menu-item <?= ($activePage ?? '') == 'dashboard' ? 'active' : '' ?>">
        <ion-icon name="grid-outline"></ion-icon> Dashboard
    </a>
    <a href="/admin/analytics" class="menu-item <?= ($activePage ?? '') == 'analytics' ? 'active' : '' ?>">
        <ion-icon name="analytics-outline"></ion-icon> Analytics
    </a>

    <div style="color:#666; font-size:0.7rem; text-transform:uppercase; padding:0 1rem; margin:1rem 0 0.5rem;">Management</div>

    <a href="/admin/users" class="menu-item <?= ($activePage ?? '') == 'users' ? 'active' : '' ?>">
        <ion-icon name="people-outline"></ion-icon> Users
    </a>
    <a href="/admin/listings" class="menu-item <?= ($activePage ?? '') == 'listings' ? 'active' : '' ?>">
        <ion-icon name="pricetags-outline"></ion-icon> Listings
    </a>
    <a href="/admin/requests" class="menu-item <?= ($activePage ?? '') == 'requests' ? 'active' : '' ?>">
        <ion-icon name="megaphone-outline"></ion-icon> Product Requests
    </a>
    <a href="/admin/transactions" class="menu-item <?= ($activePage ?? '') == 'transactions' ? 'active' : '' ?>">
        <ion-icon name="wallet-outline"></ion-icon> Transactions
    </a>
    <a href="/admin/returns" class="menu-item <?= ($activePage ?? '') == 'returns' ? 'active' : '' ?>">
        <ion-icon name="return-down-back-outline"></ion-icon> Returns
    </a>
    <a href="/admin/support" class="menu-item <?= ($activePage ?? '') == 'support' ? 'active' : '' ?>">
        <ion-icon name="ticket-outline"></ion-icon> Support Tickets
    </a>
    <a href="/admin/chats" class="menu-item <?= ($activePage ?? '') == 'chats' ? 'active' : '' ?>">
        <ion-icon name="chatbubbles-outline"></ion-icon> Chats
    </a>

    <div style="color:#666; font-size:0.7rem; text-transform:uppercase; padding:0 1rem; margin:1rem 0 0.5rem;">Content</div>

    <a href="/admin/blogs" class="menu-item <?= ($activePage ?? '') == 'blogs' ? 'active' : '' ?>">
        <ion-icon name="newspaper-outline"></ion-icon> Blogs
    </a>
    <a href="/admin/pages" class="menu-item <?= ($activePage ?? '') == 'pages' ? 'active' : '' ?>">
        <ion-icon name="document-text-outline"></ion-icon> Pages
    </a>
    <a href="/admin/banners" class="menu-item <?= ($activePage ?? '') == 'banners' ? 'active' : '' ?>">
        <ion-icon name="images-outline"></ion-icon> Banners
    </a>

    <?php if ($isSuperAdmin): ?>
    <div style="color:#6B21A8; font-size:0.7rem; text-transform:uppercase; padding:0 1rem; margin:1rem 0 0.5rem;">
        <ion-icon name="shield-checkmark" style="vertical-align:middle;"></ion-icon> Super Admin
    </div>
    <a href="/admin/roles" class="menu-item <?= ($activePage ?? '') == 'roles' ? 'active' : '' ?>">
        <ion-icon name="key-outline"></ion-icon> Roles & Permissions
    </a>
    <a href="/admin/activity" class="menu-item <?= ($activePage ?? '') == 'activity' ? 'active' : '' ?>">
        <ion-icon name="time-outline"></ion-icon> Activity Logs
    </a>
    <a href="/admin/security" class="menu-item <?= ($activePage ?? '') == 'security' ? 'active' : '' ?>">
        <ion-icon name="shield-outline"></ion-icon> Security
    </a>
    <?php endif; ?>

    <div style="color:#666; font-size:0.7rem; text-transform:uppercase; padding:0 1rem; margin:1rem 0 0.5rem;">Settings</div>

    <a href="/admin/settings" class="menu-item <?= ($activePage ?? '') == 'settings' ? 'active' : '' ?>">
        <ion-icon name="settings-outline"></ion-icon> Site Settings
    </a>
    <a href="/admin/email-templates" class="menu-item <?= ($activePage ?? '') == 'email_templates' ? 'active' : '' ?>">
        <ion-icon name="mail-outline"></ion-icon> Email Templates
    </a>

    <div style="flex:1;"></div>

    <a href="/" class="menu-item" style="border-top:1px solid #333; padding-top:1rem; margin-top:1rem;">
        <ion-icon name="arrow-back-outline"></ion-icon> Back to Site
    </a>
    <a href="/logout" class="menu-item" style="color:#ef4444;">
        <ion-icon name="log-out-outline"></ion-icon> Logout
    </a>
</nav>
