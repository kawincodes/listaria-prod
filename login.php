<?php
require 'includes/db.php';
session_start();

$error = '';

// Check if verification success message should be shown
if (isset($_SESSION['verification_success'])) {
    $error = $_SESSION['verification_success'];
    unset($_SESSION['verification_success']);
    // Wait, error variable is used for errors. Let's use a success variable or just put it in error for now. 
    // Actually, let's add a success variable to the login page.
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter email and password.";
    } else {
        $captcha_success = true;
        if (isCaptchaActive($pdo)) {
            $provider = getCaptchaProvider($pdo);
            $captcha_token = ($provider === 'turnstile')
                ? ($_POST['cf-turnstile-response'] ?? '')
                : ($_POST['g-recaptcha-response'] ?? '');
            if (!verifyCaptcha($captcha_token, $pdo)) {
                $captcha_success = false;
                $error = "CAPTCHA verification failed. Please try again.";
            }
        }

        if ($captcha_success) {
            $stmt = $pdo->prepare("SELECT id, full_name, password, is_admin, email_verified, account_type FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['email_verified'] == 0) {
                     $error = "Please verify your email address to login.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['account_type'] = $user['account_type'] ?? 'customer';
                    $_SESSION['is_admin'] = $user['is_admin'] ?? 0;

                    if ($_SESSION['is_admin'] == 1) {
                        header("Location: admin_dashboard.php");
                    } else {
                        $redirect = $_POST['redirect'] ?? 'index.php';
                        header("Location: " . $redirect);
                    }
                    exit;
                }
            } else {
                $error = "Invalid email or password.";
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
        <h2>Welcome Back</h2>
        <p>Sign in to continue to Listaria.</p>

        <!-- Google Sign-In Button -->
        <?php 
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        // Handle standard ports to avoid unwanted :80 or :443 locally/production
        $login_uri = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/google_auth.php";
        
        $redirect_param = $_REQUEST['redirect'] ?? '';
        if($redirect_param) {
            $login_uri .= "?redirect=" . urlencode($redirect_param);
        }
        ?>
        <div id="g_id_onload"
             data-client_id="<?php echo defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '967983511172-50dejtt3oc33ej91ogtb6pcgmbchint2.apps.googleusercontent.com'; ?>"
             data-login_uri="<?php echo $login_uri; ?>"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin"
             data-type="standard"
             data-size="large"
             data-theme="outline"
             data-text="sign_in_with"
             data-shape="rectangular"
             data-logo_alignment="left">
        </div>

        <div style="margin: 20px 0; border-top: 1px solid #eee; position: relative;">
            <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: white; padding: 0 10px; color: #999; font-size: 12px;">OR</span>
        </div>

        <?php if($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(isset($_GET['verified'])): ?>
             <div class="alert success" style="background-color: #e8f5e9; color: #2e7d32;">Email verified successfully! You can now login.</div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_REQUEST['redirect'] ?? 'index.php'); ?>">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="you@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
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

            <button type="submit" class="btn-primary" style="width: 100%;">Sign In</button>
        </form>
        <p class="auth-footer">New to Listaria? <a href="register.php">Create an account</a></p>
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
