<?php
require 'includes/db.php';
session_start();

$message_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    $user_id = $_SESSION['user_id'] ?? null;

    if ($name && $email && $message) {
        try {
            $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, name, email, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $name, $email, $message]);
            $message_sent = true;
        } catch (Exception $e) {
            // Log error silently or show generic message
        }
    }
}

// Prefill if logged in
$pre_name = '';
$pre_email = '';
if (isset($_SESSION['user_id'])) {
    $stmtU = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $stmtU->execute([$_SESSION['user_id']]);
    $u = $stmtU->fetch();
    if ($u) {
        $pre_name = $u['full_name'];
        $pre_email = $u['email'];
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding-top: 4rem; padding-bottom: 4rem; max-width: 800px;">
    
    <div style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem;">Help & Support</h1>
        <p style="color: #666; font-size: 1.1rem;">Have a question or run into an issue? We're here to help.</p>
    </div>

    <?php if ($message_sent): ?>
        <div style="background: #e8f5e9; border: 1px solid #c8e6c9; color: #2e7d32; padding: 2rem; border-radius: 12px; text-align: center; margin-bottom: 2rem;">
            <ion-icon name="checkmark-circle" style="font-size: 3rem; margin-bottom: 1rem;"></ion-icon>
            <h3 style="margin-top: 0;">Message Sent!</h3>
            <p>We've received your message and will get back to you shortly.</p>
            <a href="index.php" class="btn-primary" style="display: inline-block; margin-top: 1rem; text-decoration: none; padding: 0.8rem 1.5rem; border-radius: 8px;">Back to Home</a>
        </div>
    <?php else: ?>

        <div style="background: white; padding: 3rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
            <form method="POST" action="help_support.php" id="support-form">
                <div class="form-group">
                    <label style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Your Name</label>
                    <input type="text" name="name" class="form-input" required value="<?php echo htmlspecialchars($pre_name); ?>" placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Email Address</label>
                    <input type="email" name="email" class="form-input" required value="<?php echo htmlspecialchars($pre_email); ?>" placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label style="font-weight: 600; margin-bottom: 0.5rem; display: block;">How can we help?</label>
                    <textarea name="message" class="form-input" rows="6" required placeholder="Describe your issue or question..."></textarea>
                </div>

                <button type="submit" class="submit-btn" style="background: #000; color: white;">Submit Request</button>
            </form>
        </div>
        <script>
            document.getElementById('support-form').addEventListener('submit', function() {
                const btn = this.querySelector('button[type="submit"]');
                btn.disabled = true;
                btn.innerHTML = 'Sending...';
                btn.style.opacity = '0.7';
            });
        </script>

    <?php endif; ?>

</div>

<style>
    .form-input {
        width: 100%;
        padding: 1rem;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        font-family: 'Inter', sans-serif;
        transition: all 0.2s;
        background: #f9f9f9;
        box-sizing: border-box;
    }
    .form-input:focus {
        border-color: #000;
        background: #fff;
        outline: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .submit-btn {
        width: 100%;
        padding: 1rem;
        border: none;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 1rem;
    }
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
</style>

<?php include 'includes/footer.php'; ?>
