<?php
require 'includes/db.php';
require_once __DIR__ . '/includes/session.php';

$settings = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$f1_note = $settings['founder_1_note'] ?? '';
$f1_img = $settings['founder_1_image'] ?? 'https://via.placeholder.com/300x300';
$f2_note = $settings['founder_2_note'] ?? '';
$f2_img = $settings['founder_2_image'] ?? 'https://via.placeholder.com/300x300';
function safeUrl($url) {
    $url = trim($url ?? '');
    if ($url === '' || $url === '#') return '';
    if (preg_match('/^https?:\/\//i', $url)) return $url;
    return '';
}
$f1_linkedin = safeUrl($settings['founder_1_linkedin'] ?? '');
$f1_instagram = safeUrl($settings['founder_1_instagram'] ?? '');
$f1_twitter = safeUrl($settings['founder_1_twitter'] ?? '');
$f2_linkedin = safeUrl($settings['founder_2_linkedin'] ?? '');
$f2_instagram = safeUrl($settings['founder_2_instagram'] ?? '');
$f2_twitter = safeUrl($settings['founder_2_twitter'] ?? '');

include 'includes/header.php';
?>

<style>
    .founder-img-col img {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    .founder-img-col img:hover {
        transform: translateY(-5px);
    }
    .founder-socials {
        display: flex;
        gap: 12px;
        justify-content: center;
        margin-top: 0.75rem;
    }
    .founder-socials a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #f3f0ff;
        color: #6B21A8;
        text-decoration: none;
        transition: all 0.2s ease;
        font-size: 1.1rem;
    }
    .founder-socials a:hover {
        background: #6B21A8;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(107, 33, 168, 0.3);
    }
    @media (max-width: 768px) {
        .founder-section { flex-direction: column; gap: 2rem !important; }
        .founder-section.reverse { flex-direction: column-reverse; }
        .founder-img-col { width: 100%; max-width: 300px; margin: 0 auto; }
    }
</style>

<div class="container" style="padding-top: 3rem; padding-bottom: 5rem;">
    
    <h1 style="text-align: center; margin-bottom: 4rem; font-size: 2.5rem; color: var(--primary-text);">The Leadership</h1>

    <!-- Founder 1 -->
    <div class="founder-section" style="display: flex; gap: 4rem; align-items: flex-start; margin-bottom: 5rem;">
        <div class="founder-img-col" style="flex: 0 0 350px; text-align: center;">
            <img src="<?php echo htmlspecialchars($f1_img); ?>" alt="Harsh Vardhan Jaiswal">
            <div style="margin-top: 1rem; font-family: 'Inter', sans-serif; color: var(--primary-text);">
                <h3 style="margin: 0; font-size: 1.2rem;">Harsh Vardhan Jaiswal</h3>
                <p style="margin: 0.2rem 0 0; font-size: 0.95rem; color: var(--secondary-text);">CEO & Co-Founder, Listaria</p>
                <div class="founder-socials">
                    <?php if ($f1_linkedin): ?><a href="<?php echo htmlspecialchars($f1_linkedin); ?>" target="_blank" rel="noopener noreferrer" title="LinkedIn"><ion-icon name="logo-linkedin"></ion-icon></a><?php endif; ?>
                    <?php if ($f1_instagram): ?><a href="<?php echo htmlspecialchars($f1_instagram); ?>" target="_blank" rel="noopener noreferrer" title="Instagram"><ion-icon name="logo-instagram"></ion-icon></a><?php endif; ?>
                    <?php if ($f1_twitter): ?><a href="<?php echo htmlspecialchars($f1_twitter); ?>" target="_blank" rel="noopener noreferrer" title="X (Twitter)"><ion-icon name="logo-twitter"></ion-icon></a><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="founder-content" style="flex: 1; font-family: 'Inter', sans-serif; line-height: 1.8; color: var(--primary-text);">
            <?php echo $f1_note; ?>
        </div>
    </div>

    <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 4rem 0;">

    <!-- Founder 2 -->
    <div class="founder-section reverse" style="display: flex; gap: 4rem; align-items: flex-start;">
        <div class="founder-content" style="flex: 1; font-family: 'Inter', sans-serif; line-height: 1.8; color: var(--primary-text);">
            <?php echo $f2_note; ?>
        </div>
        <div class="founder-img-col" style="flex: 0 0 350px; text-align: center;">
            <img src="<?php echo htmlspecialchars($f2_img); ?>" alt="Aryan Biswa">
            <div style="margin-top: 1rem; font-family: 'Inter', sans-serif; color: var(--primary-text);">
                <h3 style="margin: 0; font-size: 1.2rem;">Aryan Biswa</h3>
                <p style="margin: 0.2rem 0 0; font-size: 0.95rem; color: var(--secondary-text);">Co-Founder & CFMO, Listaria</p>
                <div class="founder-socials">
                    <?php if ($f2_linkedin): ?><a href="<?php echo htmlspecialchars($f2_linkedin); ?>" target="_blank" rel="noopener noreferrer" title="LinkedIn"><ion-icon name="logo-linkedin"></ion-icon></a><?php endif; ?>
                    <?php if ($f2_instagram): ?><a href="<?php echo htmlspecialchars($f2_instagram); ?>" target="_blank" rel="noopener noreferrer" title="Instagram"><ion-icon name="logo-instagram"></ion-icon></a><?php endif; ?>
                    <?php if ($f2_twitter): ?><a href="<?php echo htmlspecialchars($f2_twitter); ?>" target="_blank" rel="noopener noreferrer" title="X (Twitter)"><ion-icon name="logo-twitter"></ion-icon></a><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
