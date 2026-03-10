<?php
require 'includes/db.php';
require_once __DIR__ . '/includes/session.php';

$settings = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$f1_note = $settings['founder_1_note'] ?? '';
$f1_img = $settings['founder_1_image'] ?? 'https://via.placeholder.com/300x300';
$f2_note = $settings['founder_2_note'] ?? '';
$f2_img = $settings['founder_2_image'] ?? 'https://via.placeholder.com/300x300';

include 'includes/header.php';
?>

<div class="container" style="padding-top: 3rem; padding-bottom: 5rem;">
    
    <h1 style="text-align: center; margin-bottom: 4rem; font-size: 2.5rem; color: var(--primary-text);">The Leadership</h1>

    <style>
        .founder-img-col img {
            width: 100%;
            height: 400px; /* Fixed height for consistency */
            object-fit: cover; /* Crop nicely */
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .founder-img-col img:hover {
            transform: translateY(-5px);
        }
    </style>

    <!-- Founder 1 -->
    <div class="founder-section" style="display: flex; gap: 4rem; align-items: flex-start; margin-bottom: 5rem;">
        <div class="founder-img-col" style="flex: 0 0 350px; text-align: center;">
            <img src="<?php echo htmlspecialchars($f1_img); ?>" alt="Harsh Vardhan Jaiswal">
            <div style="margin-top: 1rem; font-family: 'Inter', sans-serif; color: var(--primary-text);">
                <h3 style="margin: 0; font-size: 1.2rem;">Harsh Vardhan Jaiswal</h3>
                <p style="margin: 0.2rem 0 0; font-size: 0.95rem; color: var(--secondary-text);">CEO & Co-Founder, Listaria</p>
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
            </div>
        </div>
    </div>

</div>

<style>
    @media (max-width: 768px) {
        .founder-section { flex-direction: column; gap: 2rem !important; }
        .founder-section.reverse { flex-direction: column-reverse; }
        .founder-img-col { width: 100%; max-width: 300px; margin: 0 auto; }
    }
</style>

<?php include 'includes/footer.php'; ?>
