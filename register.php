<?php
require 'includes/db.php';
require 'includes/SimpleSMTP.php';
session_start();

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        if (isCaptchaActive($pdo)) {
            $provider = getCaptchaProvider($pdo);
            $captcha_token = ($provider === 'turnstile')
                ? ($_POST['cf-turnstile-response'] ?? '')
                : ($_POST['g-recaptcha-response'] ?? '');
            if (!verifyCaptcha($captcha_token, $pdo)) {
                $error = "CAPTCHA verification failed. Please try again.";
            }
        }

        if (!$error) {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "Email already registered.";
            } else {
                // Hash password and insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $verification_token = bin2hex(random_bytes(32));
                
                $account_type = $_POST['account_type'] ?? 'customer';
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, email_verified, verification_token, account_type) VALUES (?, ?, ?, 0, ?, ?)");
                
                try {
                    if ($stmt->execute([$full_name, $email, $hashed_password, $verification_token, $account_type])) {
                        
                        // Send verification email
                        // Hardcoded credentials as per user request
                        $smtp = createSmtp($pdo);

                        $verifyLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/verify.php?token=$verification_token";
                        
                        $subject = "Verify your Listaria Account";
                        $body = "
                            <h2>Welcome to Listaria, $full_name!</h2>
                            <p>Please click the link below to verify your email address and activate your account:</p>
                            <p><a href='$verifyLink' style='background:#6B21A8; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Verify Email</a></p>
                            <p>Or copy this link: $verifyLink</p>
                        ";

                        if ($smtp->send($email, $subject, $body)) {
                            $success = "Registration successful! Please check your email ($email) to verify your account.";
                        } else {
                             // Fallback if email fails? ideally we should warn them.
                             $success = "Registration successful, but failed to send verification email. Please contact support.";
                        }
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) { // Integrity constraint violation
                       $error = "Email already registered.";
                    } else {
                       $error = "Something went wrong: " . $e->getMessage();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include 'includes/header.php'; ?>
<!-- Load Google Identity Services -->
<script src="https://accounts.google.com/gsi/client" async defer></script>
<?php if (isCaptchaActive($pdo)):
    $captchaProvider = getCaptchaProvider($pdo);
    if ($captchaProvider === 'turnstile'): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php else: ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; endif; ?>
<body>

<div class="auth-container">
    <div class="auth-card">
        <h2>Join Listaria</h2>
        <p>Create an account to start selling and buying luxury.</p>

        <!-- Google Sign-In Button -->
        <?php 
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $login_uri = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/google_auth.php";
        
        $redirect_param = $_REQUEST['redirect'] ?? '';
        if($redirect_param) {
            $login_uri .= "?redirect=" . urlencode($redirect_param);
        }
        ?>
        <div id="g_id_onload"
             data-client_id="<?php echo defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '468231782277-fnu8fbidhnugu8hjcdcsunemuuusd4u6.apps.googleusercontent.com'; ?>"
             data-login_uri="<?php echo $login_uri; ?>"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin"
             data-type="standard"
             data-size="large"
             data-theme="outline"
             data-text="sign_up_with"
             data-shape="rectangular"
             data-logo_alignment="left">
        </div>

        <div style="margin: 20px 0; border-top: 1px solid #eee; position: relative;">
            <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: white; padding: 0 10px; color: #999; font-size: 12px;">OR</span>
        </div>
        
        <?php if($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php else: ?>

        <form method="POST" action="register.php">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_REQUEST['redirect'] ?? 'index.php'); ?>">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required placeholder="John Doe">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="you@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="••••••••">
            </div>

            <div class="form-group">
                <label>Account Type</label>
                <div style="display:flex; gap:20px; margin-top:10px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:normal;">
                        <input type="radio" name="account_type" value="customer" checked style="width:auto;"> Customer
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:normal;">
                        <input type="radio" name="account_type" value="vendor" style="width:auto;"> Vendor
                    </label>
                </div>
            </div>
            
            <?php if(isCaptchaActive($pdo)):
                $cProvider = getCaptchaProvider($pdo);
                $cSiteKey = getCaptchaSiteKey($pdo);
                if ($cProvider === 'turnstile'): ?>
            <div class="form-group">
                <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($cSiteKey); ?>" data-theme="light"></div>
            </div>
            <?php else: ?>
            <div class="form-group">
                <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($cSiteKey); ?>"></div>
            </div>
            <?php endif; endif; ?>

            <button type="submit" class="btn-primary" style="width: 100%;">Create Account</button>
        </form>
        <?php endif; ?>
        <p class="auth-footer">Already have an account? <a href="login.php">Sign in</a></p>
    </div>
</div>

<style>
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 80vh;
        background-color: #f9f9f9;
    }
    .auth-card {
        background: white;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        width: 100%;
        max-width: 400px;
        text-align: center;
    }
    .auth-card h2 {
        margin-bottom: 10px;
        color: #333;
    }
    .auth-card p {
        color: #666;
        margin-bottom: 30px;
    }
    .auth-card .form-group {
        text-align: left;
        margin-bottom: 20px;
    }
    .auth-card label {
        display: block;
        font-weight: 500;
        margin-bottom: 8px;
        color: #333;
    }
    .auth-card input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s;
    }
    .auth-card input:focus {
        border-color: var(--primary-color);
        outline: none;
    }
    .alert {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
    }
    .alert.error {
        background-color: #ffebee;
        color: #c62828;
    }
    .alert.success {
        background-color: #e8f5e9;
        color: #2e7d32;
    }
    .auth-footer {
        margin-top: 20px;
        font-size: 14px;
    }
    .auth-footer a {
        color: var(--primary-color);
        font-weight: 600;
        text-decoration: none;
    }
</style>

<?php include 'includes/footer.php'; ?>
</body>
</html>
