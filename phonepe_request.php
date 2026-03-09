<?php
require 'includes/db.php';
session_start();

if (!isset($_GET['order_id'])) {
    die("Order ID is required");
}

$order_id = $_GET['order_id'];

// Fetch Order Details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Invalid Order ID");
}

$amount = $order['amount'];
$merchantTransactionId = "MT" . $order_id . "_" . time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment | PhonePe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5f259f;
            --primary-dark: #4a1c7c;
            --text-dark: #1a1a1a;
            --text-gray: #666666;
            --bg-light: #f8f9fa;
            --white: #ffffff;
            --border: #e0e0e0;
            --success: #00b907;
            --radius-lg: 16px;
            --radius-md: 12px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        * {
            box-sizing: border-box;
            outline: none;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-dark);
            padding: 20px;
        }

        .payment-card {
            background: var(--white);
            width: 100%;
            max-width: 420px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
        }

        .merchant-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .merchant-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #FF9933, #FFB74D);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 2px 8px rgba(255, 153, 51, 0.3);
        }

        .merchant-details {
            display: flex;
            flex-direction: column;
        }

        .merchant-label {
            font-size: 0.75rem;
            color: var(--text-gray);
            font-weight: 500;
        }

        .merchant-name {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-dark);
        }

        .amount-badge {
            background: #f3f0f9;
            color: var(--primary);
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .content {
            padding: 32px 24px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .qr-container {
            position: relative;
            padding: 16px;
            border: 2px solid #eee;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
            background: white;
        }

        .qr-container::after {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            border-radius: var(--radius-md);
            border: 2px solid transparent;
            background: linear-gradient(45deg, var(--primary), #9c27b0) border-box;
            -webkit-mask:
                linear-gradient(#fff 0 0) padding-box,
                linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0.5;
        }

        .qr-img {
            width: 220px;
            height: 220px;
            display: block;
        }

        .scan-instruction {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .scan-subtext {
            color: var(--text-gray);
            font-size: 0.9rem;
            margin-bottom: 24px;
        }

        .timer-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 32px;
        }

        .form-group {
            width: 100%;
            text-align: left;
            margin-bottom: 16px;
        }

        .input-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .utr-input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s ease;
            color: var(--text-dark);
        }

        .utr-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(95, 37, 159, 0.1);
        }

        .helper-text {
            font-size: 0.8rem;
            color: var(--text-gray);
            margin-top: 6px;
        }

        .footer {
            padding: 20px 24px;
            border-top: 1px solid var(--border);
            background: #fff;
        }

        .pay-btn {
            width: 100%;
            background: var(--primary);
            color: white;
            padding: 16px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
            box-shadow: 0 4px 12px rgba(95, 37, 159, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pay-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .pay-btn:active {
            transform: translateY(0);
        }

        .secured-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 16px;
            font-size: 0.75rem;
            color: #888;
        }

        /* Mobile specific adjustments */
        @media (max-width: 480px) {
            body {
                padding: 0;
                align-items: flex-start;
                background: white;
            }
            .payment-card {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
                min-height: 100vh;
            }
            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding-bottom: 24px; /* Safety for iPhone home bar */
            }
            .content {
                padding-bottom: 100px; /* Space for fixed footer */
            }
        }

        /* Success Modal Styles */
        #orderSuccessModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease-out;
        }

        .success-card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            transform: scale(0.9);
            animation: scaleIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            max-width: 90%;
            width: 340px;
        }

        .checkmark-wrapper {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3);
        }

        .checkmark {
            width: 40px;
            height: 24px;
            border-left: 4px solid white;
            border-bottom: 4px solid white;
            transform: rotate(-45deg) translate(2px, -2px);
            animation: checkDraw 0.6s ease-in-out forwards;
            opacity: 0;
        }

        @keyframes checkDraw {
            0% { width: 0; height: 0; opacity: 0; }
            40% { width: 0; height: 24px; opacity: 1; }
            100% { width: 40px; height: 24px; opacity: 1; }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes scaleIn {
            to { transform: scale(1); }
        }

        .success-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0 0 8px 0;
        }

        .success-desc {
            color: var(--text-gray);
            font-size: 0.95rem;
            line-height: 1.5;
        }
    </style>
</head>
<body>

<form action="phonepe_response.php" method="POST" id="payForm">
    <input type="hidden" name="transactionId" value="<?php echo htmlspecialchars($merchantTransactionId); ?>">
    <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
    
    <div class="payment-card">
        <!-- Header -->
        <div class="header">
            <div class="merchant-wrapper">
                <div class="merchant-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                </div>
                <div class="merchant-details">
                    <span class="merchant-label">Paying to</span>
                    <span class="merchant-name">Listaria</span>
                </div>
            </div>
            <div class="amount-badge">₹<?php echo number_format($amount, 2); ?></div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="scan-instruction">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h6v6H4z"/><path d="M14 4h6v6h-6z"/><path d="M4 14h6v6H4z"/><path d="M14 14h6v6h-6z"/>
                    <path d="M7 17l0 .01"/><path d="M17 7l0 .01"/><path d="M7 7l0 .01"/>
                </svg>
                Scan QR to Pay
            </div>
            <p class="scan-subtext">Use any UPI app on your phone</p>

            <div class="qr-container">
                <img src="assets/phonepe_qr_v2.png" alt="Payment QR Code" class="qr-img">
            </div>

            <div class="timer-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Time remaining: <span id="countdown">04:59</span></span>
            </div>

            <div class="form-group">
                <label class="input-label">UTR / Reference Number</label>
                <div class="input-wrapper">
                    <input type="text" 
                           name="utr_number" 
                           required 
                           placeholder="Enter 12-digit UTR number" 
                           class="utr-input"
                           maxlength="12"
                           pattern="\d{12}"
                           title="Please enter exactly 12 digits">
                </div>
                <p class="helper-text">You'll find this in your UPI app after payment.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <button type="submit" class="pay-btn">
                Submit for Verification
            </button>
            <div class="secured-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Secured by PhonePe Gateway
            </div>
        </div>
    </div>
</form>

<div id="orderSuccessModal">
    <div class="success-card">
        <div class="checkmark-wrapper">
            <div class="checkmark"></div>
        </div>
        <h3 class="success-title">Order Placed!</h3>
        <p class="success-desc">Verifying your payment details.<br>Please wait...</p>
    </div>
</div>

<script>
    // Timer Logic
    let timeLeft = 299; // 5 minutes
    const timerEl = document.getElementById('countdown');
    
    const timer = setInterval(() => {
        if(timeLeft <= 0) {
            clearInterval(timer);
            timerEl.innerText = "Expired";
            timerEl.style.color = "#d32f2f";
            return;
        }
        
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        
        timerEl.innerText = 
            (minutes < 10 ? "0" + minutes : minutes) + ":" + 
            (seconds < 10 ? "0" + seconds : seconds);
            
        timeLeft--;
    }, 1000);

    // Form Submission Handler
    document.getElementById('payForm').addEventListener('submit', function(e) {
        // e.preventDefault(); // Stop immediate submission
        // The submit event fires only if valid? 
        // We want to hijack it.
        
        e.preventDefault();
        
        // Show Modal
        const modal = document.getElementById('orderSuccessModal');
        modal.style.display = 'flex';
        
        // Wait 2.5 seconds and submit
        setTimeout(() => {
            this.submit();
        }, 2500);
    });
</script>

</body>
</html>
