<?php $userId = session()->get('user_id'); ?>

<?php if (!$userId): ?>
<div id="guestInterceptModal" class="guest-modal">
    <div class="guest-modal-content">
        <button class="guest-modal-close" onclick="closeGuestModal()"><ion-icon name="close-outline"></ion-icon></button>
        <div class="guest-modal-icon">
            <img src="/assets/logo.jpg" alt="Listaria Logo" style="width:100%; height:100%; object-fit:cover; border-radius:16px;">
        </div>
        <h2>Unlock the Full Experience</h2>
        <p>Sign in to unlock personalized wishlists, seamless order tracking, and exclusive vendor features.</p>
        <div class="guest-modal-actions">
            <a href="/login" class="btn-guest-primary">Sign In</a>
            <a href="/register" class="btn-guest-secondary">Create Account</a>
        </div>
        <button class="btn-guest-link" onclick="closeGuestModal()">Maybe later</button>
    </div>
</div>
<style>
    .guest-modal { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter:blur(4px); display:none; align-items:center; justify-content:center; z-index:9000; }
    .guest-modal-content { background:white; width:90%; max-width:400px; padding:2.5rem; border-radius:24px; text-align:center; position:relative; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
    .guest-modal-close { position:absolute; top:1.25rem; right:1.25rem; background:#f3f4f6; border:none; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; color:#64748b; }
    .guest-modal-icon { width:60px; height:60px; margin:0 auto 1rem; }
    .guest-modal-actions { display:flex; flex-direction:column; gap:0.75rem; margin-top:1.5rem; }
    .btn-guest-primary { background:#6B21A8; color:white; padding:12px; border-radius:12px; text-decoration:none; font-weight:600; }
    .btn-guest-secondary { background:#f3f4f6; color:#333; padding:12px; border-radius:12px; text-decoration:none; font-weight:600; }
    .btn-guest-link { background:none; border:none; color:#999; cursor:pointer; margin-top:0.5rem; }
</style>
<script>
function closeGuestModal() { document.getElementById('guestInterceptModal').style.display = 'none'; }
</script>
<?php endif; ?>

<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-grid">
            <div class="footer-col brand-col">
                <div class="footer-logo">listaria</div>
                <p class="footer-tagline">Elevating the luxury recommerce experience in India.</p>
                <div class="brand-subtext">Listaria is a brand of Listaria Pvt Ltd.</div>
            </div>
            <div class="footer-col">
                <h4 class="footer-col-title">Company</h4>
                <nav class="footer-nav-list">
                    <a href="/about">About Us</a>
                    <a href="/blogs">Blogs</a>
                    <a href="/founders">Founders</a>
                </nav>
            </div>
            <div class="footer-col">
                <h4 class="footer-col-title">Information</h4>
                <nav class="footer-nav-list">
                    <a href="/terms">Terms of Service</a>
                    <a href="/privacy">Privacy Policy</a>
                    <a href="/refund">Refund & Return</a>
                </nav>
            </div>
        </div>
        <div class="footer-bottom-bar">
            <div class="copyright">&copy; 2026 Listaria. All rights reserved.</div>
        </div>
    </div>
</footer>

<style>
    .site-footer { background:#fafafa; padding:5rem 2rem 3rem; border-top:1px solid #eee; }
    .footer-content { max-width:1200px; margin:0 auto; }
    .footer-grid { display:grid; grid-template-columns:2fr 1fr 1fr; gap:4rem; margin-bottom:4rem; }
    .footer-logo { font-size:2rem; font-weight:800; color:#333; letter-spacing:-1px; margin-bottom:1rem; }
    .footer-tagline { color:#666; font-size:0.95rem; line-height:1.7; margin-bottom:0.5rem; }
    .brand-subtext { color:#999; font-size:0.8rem; }
    .footer-col-title { font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; color:#999; margin-bottom:1.5rem; font-weight:600; }
    .footer-nav-list { display:flex; flex-direction:column; gap:0.75rem; }
    .footer-nav-list a { color:#555; text-decoration:none; font-size:0.95rem; transition:color 0.2s; }
    .footer-nav-list a:hover { color:#6B21A8; }
    .footer-bottom-bar { border-top:1px solid #eee; padding-top:2rem; display:flex; justify-content:space-between; align-items:center; }
    .copyright { font-size:0.85rem; color:#666; }
    @media (max-width:900px) { .footer-grid { grid-template-columns:1fr 1fr; gap:3rem; } .brand-col { grid-column:span 2; } }
    @media (max-width:600px) { .site-footer { padding:4rem 1.5rem 2.5rem; } .footer-grid { grid-template-columns:1fr; gap:2.5rem; } .brand-col { grid-column:span 1; } }
</style>
