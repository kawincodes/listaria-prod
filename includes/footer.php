
<?php 
$allowed_nav_pages = ['index.php', 'wishlist.php', 'profile.php'];
$current_page = basename($_SERVER['PHP_SELF']);
if (in_array($current_page, $allowed_nav_pages)) {
    include 'includes/mobile_bottom_nav.php'; 
}
?>

<footer class="site-footer <?php echo in_array($current_page, $allowed_nav_pages) ? 'footer-with-nav' : ''; ?>">
    <div class="footer-content">
        <div class="footer-grid">
            <!-- Brand Column -->
            <div class="footer-col brand-col">
                <div class="footer-logo">listaria</div>
                <p class="footer-tagline">Elevating the luxury recommerce experience in India.</p>
                <div class="brand-subtext">Listaria is a brand of Listaria Pvt Ltd.</div>
            </div>

            <!-- Links Column -->
            <div class="footer-col">
                <h4 class="footer-col-title">Company</h4>
                <nav class="footer-nav-list">
                    <a href="about.php">About Us</a>
                    <a href="blogs.php">Blogs</a>
                    <a href="founders.php">Founders</a>
                    <a href="help_support.php">Help & Support</a>
                </nav>
            </div>

            <!-- Policies Column -->
            <div class="footer-col">
                <h4 class="footer-col-title">Information</h4>
                <nav class="footer-nav-list">
                    <a href="terms.php">Terms of Service</a>
                    <a href="privacy.php">Privacy Policy</a>
                    <a href="refund.php">Refund & Return</a>
                    <a href="switch_to_vendor.php" class="vendor-link">Wish to be a Vendor?</a>
                </nav>
            </div>
        </div>
        
        <div class="footer-bottom-bar">
            <div class="copyright">© 2026 Listaria. All rights reserved.</div>
        </div>
    </div>
</footer>

<!-- Guest Interception Modal -->
<?php if (!isset($_SESSION['user_id'])): ?>
<div id="guestInterceptModal" class="guest-modal">
    <div class="guest-modal-content">
        <button class="guest-modal-close" onclick="closeGuestModal()">
            <ion-icon name="close-outline"></ion-icon>
        </button>
        <div class="guest-modal-icon">
            <img src="assets/logo.jpg" alt="Listaria Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 16px;">
        </div>
        <h2>Unlock the Full Experience</h2>
        <p>Sign in to unlock personalized wishlists, seamless order tracking, and exclusive vendor features.</p>
        <div class="guest-modal-actions">
            <a href="login.php" class="btn-guest-primary">Sign In</a>
            <a href="register.php" class="btn-guest-secondary">Create Account</a>
        </div>
        <button class="btn-guest-link" onclick="closeGuestModal()">Maybe later</button>
    </div>
</div>

<style>
    .guest-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
        display: none; /* Hidden by default */
        align-items: center;
        justify-content: center;
        z-index: 9000;
        animation: guestFadeIn 0.3s ease;
    }

    .guest-modal-content {
        background: white;
        width: 90%;
        max-width: 400px;
        padding: 2.5rem;
        border-radius: 24px;
        text-align: center;
        position: relative;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        animation: guestSlideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .guest-modal-close {
        position: absolute;
        top: 1.25rem;
        right: 1.25rem;
        background: #f3f4f6;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #64748b;
        transition: all 0.2s;
    }

    .guest-modal-close:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    .guest-modal-icon {
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 20px;
        margin-left: auto;
        margin-right: auto;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .guest-modal-content h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1e293b;
        margin-bottom: 0.75rem;
        letter-spacing: -0.5px;
    }

    .guest-modal-content p {
        color: #64748b;
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 2rem;
    }

    .guest-modal-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }

    .btn-guest-primary {
        background: #6B21A8;
        color: white;
        text-decoration: none;
        padding: 0.85rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        transition: all 0.3s;
    }

    .btn-guest-primary:hover {
        background: #581c87;
        transform: translateY(-2px);
    }

    .btn-guest-secondary {
        background: white;
        color: #1e293b;
        text-decoration: none;
        padding: 0.85rem;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        border: 1px solid #e2e8f0;
        transition: all 0.3s;
    }

    .btn-guest-secondary:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .btn-guest-link {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        padding: 5px;
    }

    .btn-guest-link:hover {
        color: #64748b;
        text-decoration: underline;
    }

    @keyframes guestFadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes guestSlideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<script>
    function showGuestModal() {
        if (!sessionStorage.getItem('guest_popup_dismissed')) {
            const modal = document.getElementById('guestInterceptModal');
            if (modal) modal.style.display = 'flex';
        }
    }

    function closeGuestModal() {
        const modal = document.getElementById('guestInterceptModal');
        if (modal) modal.style.display = 'none';
        sessionStorage.setItem('guest_popup_dismissed', 'true');
    }

    document.addEventListener('DOMContentLoaded', () => {
        // 1. Scroll Trigger (past 400px)
        window.addEventListener('scroll', () => {
            if (window.scrollY > 400) {
                showGuestModal();
            }
        });

        // 2. Click Interception for listings
        document.addEventListener('click', (e) => {
            // Check if clicking a product card or a link to product_details.php
            const target = e.target.closest('a');
            if (target && (target.classList.contains('product-card') || target.href.includes('product_details.php'))) {
                if (!sessionStorage.getItem('guest_popup_dismissed')) {
                    e.preventDefault();
                    showGuestModal();
                }
            }
        });
    });
</script>
<?php endif; ?>

<style>
    .site-footer {
        background-color: #000000;
        color: #ffffff;
        padding: 5rem 2rem 3rem;
        margin-top: 5rem;
        font-family: 'Inter', sans-serif;
        border-top: 1px solid #1a1a1a;
        position: relative;
        z-index: 100;
    }

    /* Fix for mobile bottom nav overlap */
    .footer-with-nav {
        padding-bottom: 120px !important;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 4rem;
        margin-bottom: 4rem;
    }

    .footer-logo {
        font-size: 2.2rem;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -1.5px;
        margin-bottom: 1.5rem;
    }

    .footer-tagline {
        color: #888;
        font-size: 1rem;
        line-height: 1.6;
        margin-bottom: 1rem;
        max-width: 300px;
    }

    .brand-subtext {
        font-size: 0.75rem;
        color: #444;
        letter-spacing: 0.5px;
        margin-top: 2rem;
    }

    .footer-col-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: #fff;
        font-weight: 700;
        margin-bottom: 1.5rem;
        opacity: 0.9;
    }

    .footer-nav-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .footer-nav-list a {
        color: #888;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 400;
        transition: all 0.2s ease;
    }

    .footer-nav-list a:hover {
        color: #6B21A8;
        transform: translateX(3px);
    }

    .vendor-link {
        color: #a855f7 !important; /* Brighter purple for CTA */
        font-weight: 600 !important;
    }

    .footer-bottom-bar {
        padding-top: 2rem;
        border-top: 1px solid #111;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .copyright {
        font-size: 0.85rem;
        color: #666;
    }

    @media (max-width: 900px) {
        .footer-grid {
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }
        .brand-col {
            grid-column: span 2;
        }
    }

    @media (max-width: 600px) {
        .site-footer {
            padding: 4rem 1.5rem 2.5rem;
            text-align: left;
        }
        .footer-grid {
            grid-template-columns: 1fr;
            gap: 2.5rem;
            margin-bottom: 3rem;
        }
        .brand-col {
            grid-column: span 1;
        }
        .footer-bottom-bar {
            flex-direction: column-reverse;
            gap: 2rem;
            align-items: flex-start;
        }
        .footer-logo {
            font-size: 1.8rem;
        }
        
        .footer-with-nav {
            padding-bottom: 110px !important;
        }
    }
</style>

<script src="assets/js/script.js"></script>
</body>
</html>
