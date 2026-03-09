<?php
require 'includes/db.php';
session_start();

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Find user with this token
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Verify user
        $update = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
        $update->execute([$user['id']]);

        // Redirect to login with success message
        header("Location: login.php?verified=1");
        exit;
    } else {
        $error = "Invalid or expired verification token.";
    }
} else {
    $error = "No token provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/header.php'; ?>
<body>

<div class="container" style="padding: 50px 20px; text-align: center;">
    <div style="max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <?php if(isset($error)): ?>
            <div style="color: #c62828; font-size: 1.2rem; margin-bottom: 20px;">
                <ion-icon name="alert-circle-outline" style="font-size: 3rem; margin-bottom: 10px; display: block; margin: 0 auto 10px;"></ion-icon>
                Verification Failed
            </div>
            <p><?php echo $error; ?></p>
            <a href="index.php" class="btn-primary" style="margin-top: 20px; display: inline-block;">Go Home</a>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
