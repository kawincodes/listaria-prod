<?php
require 'includes/db.php';
require_once __DIR__ . '/includes/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    $id_token = $_POST['credential'];
    
    // Verify the token using Google's tokeninfo endpoint
    // Using cURL instead of file_get_contents to handle SSL issues on localhost
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Disable SSL verification for local development (fixes 'SSL certificate problem')
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $curl_error = curl_error($ch);
        echo 'Failed to connect to Google: ' . $curl_error; // Detailed error for debugging
        $response = false;
    }
    curl_close($ch);
    
    if ($response) {
        $payload = json_decode($response, true);
        
        if (isset($payload['email'])) {
            $email = $payload['email'];
            $name = $payload['name'];
            $google_sub = $payload['sub']; // Unique Google ID

            // Check if user exists (Case insensitive check)
            $stmt = $pdo->prepare("SELECT id, full_name, email_verified, account_type FROM users WHERE LOWER(email) = LOWER(?)");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Login existing user
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['account_type'] = $user['account_type'] ?? 'customer';
                
                // Auto-verify email if logged in via Google
                if ($user['email_verified'] == 0) {
                     $update = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
                     $update->execute([$user['id']]);
                }
            } else {
                // Register new user
                // Generate a random secure password since they use Google
                $random_password = bin2hex(random_bytes(16));
                $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);

                // Insert as verified (email_verified = 1)
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, email_verified) VALUES (?, ?, ?, 1)");
                if ($stmt->execute([$name, $email, $hashed_password])) {
                    $new_user_id = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['full_name'] = $name;
                    $_SESSION['account_type'] = 'customer'; // Default for new Google signups
                } else {
                    die("Database error during Google Registration.");
                }
            }

            $login_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $login_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $logged_user_id = $_SESSION['user_id'] ?? null;
            $logStmt = $pdo->prepare("INSERT INTO login_logs (user_id, email, ip_address, user_agent, login_status) VALUES (?, ?, ?, ?, 'success')");
            $logStmt->execute([$logged_user_id, $email, $login_ip, $login_ua]);
            if ($logged_user_id) {
                $pdo->prepare("UPDATE users SET last_login_ip = ? WHERE id = ?")->execute([$login_ip, $logged_user_id]);
            }

            header("Location: index.php");
            exit;
        } else {
            echo "Invalid Google Token payload.";
        }
    } else {
        echo "Failed to verify Google Token.";
    }
} else {
    header("Location: login.php");
    exit;
}
?>
