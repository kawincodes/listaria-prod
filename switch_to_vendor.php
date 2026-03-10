<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$msgType = '';

// Check current user status
$stmt = $pdo->prepare("SELECT account_type, vendor_status, rejection_reason FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['account_type'] === 'vendor') {
    header("Location: profile.php?msg=already_vendor");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $business_name = trim($_POST['business_name']);
    $business_bio = trim($_POST['business_bio']);
    $whatsapp_number = trim($_POST['whatsapp_number']);
    $gst_number = trim($_POST['gst_number']);

    if(empty($business_name) || empty($business_bio) || empty($whatsapp_number)) {
        $message = "All starred fields are required.";
        $msgType = "error";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET vendor_status = 'pending', vendor_applied_at = CURRENT_TIMESTAMP, business_name = ?, business_bio = ?, whatsapp_number = ?, gst_number = ?, is_public = 1 WHERE id = ?");
        if ($stmt->execute([$business_name, $business_bio, $whatsapp_number, $gst_number, $user_id])) {
            $user['vendor_status'] = 'pending'; // Update local variable for UI
            $message = "Your application has been submitted and is pending review.";
            $msgType = "success";
        } else {
            $message = "Failed to submit application. Please try again.";
            $msgType = "error";
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-container" style="min-height: 80vh; display: flex; justify-content: center; align-items: center; background-color: #f9f9f9; padding: 20px;">
    <div class="auth-card" style="background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); width: 100%; max-width: 500px; text-align: center;">
        
        <?php if ($user['vendor_status'] === 'pending'): ?>
            <div style="font-size: 3rem; color: #f39c12; margin-bottom: 20px;"><ion-icon name="time-outline"></ion-icon></div>
            <h2 style="margin-bottom: 10px; color: #333;">Application Pending</h2>
            <p style="color: #666; margin-bottom: 30px; line-height: 1.5;">Your vendor application has been received and is currently under review by our team. We'll notify you once a decision is made.</p>
            <a href="profile.php" class="btn-primary" style="text-decoration: none; display: inline-block; padding: 10px 20px;">Return to Profile</a>
        
        <?php else: ?>
            <h2 style="margin-bottom: 10px; color: #333;">Become a Vendor</h2>
            <p style="color: #666; margin-bottom: 30px;">Apply to start selling professionally with bulk uploads, business analytics, and a public store.</p>

            <?php if($message): ?>
                <div class="alert <?php echo $msgType; ?>" style="padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; text-align: left; background-color: <?php echo $msgType === 'error' ? '#ffebee' : '#e8f5e9'; ?>; color: <?php echo $msgType === 'error' ? '#c62828' : '#2e7d32'; ?>;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($user['vendor_status'] === 'rejected'): ?>
                <div class="alert error" style="padding: 15px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; text-align: left; background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171;">
                    <strong>Previous Application Rejected:</strong><br>
                    <?php echo htmlspecialchars($user['rejection_reason'] ?: 'Did not meet criteria.'); ?><br>
                    <span style="font-size: 0.85rem; margin-top: 5px; display: block;">You may update your details and re-apply below.</span>
                </div>
            <?php endif; ?>

            <form method="POST" action="switch_to_vendor.php" style="text-align: left;">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #333;">Business Name <span style="color:red;">*</span></label>
                    <input type="text" name="business_name" required placeholder="Luxury Vintage Hub" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; box-sizing: border-box;">
                </div>
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #333;">Business Bio <span style="color:red;">*</span></label>
                    <textarea name="business_bio" required rows="3" placeholder="Tell us about what you sell..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #333;">WhatsApp Number <span style="color:red;">*</span></label>
                    <input type="tel" name="whatsapp_number" required placeholder="10-digit number" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; box-sizing: border-box;">
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: 500; margin-bottom: 8px; color: #333;">GST Number <span style="color:#888; font-size: 0.8rem; font-weight: 400;">(Optional)</span></label>
                    <input type="text" name="gst_number" placeholder="22AAAAA0000A1Z5" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; box-sizing: border-box;">
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; font-size: 16px; font-weight: 600; background: #6B21A8; color: white; border: none; border-radius: 8px; cursor: pointer;">Submit Application</button>
            </form>
            <div style="margin-top: 20px;">
                <a href="profile.php" style="color: #666; text-decoration: none; font-size: 0.9rem;">Cancel</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
