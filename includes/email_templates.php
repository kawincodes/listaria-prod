<?php
function getDefaultEmailTemplates() {
    return [
        'order_confirmation' => [
            'name' => 'Order Confirmation',
            'subject' => 'Order Confirmed - #{{order_id}} | Listaria',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#1e293b;margin-top:0;">Order Confirmed!</h2>
  <p style="color:#475569;">Hi {{customer_name}},</p>
  <p style="color:#475569;">Thank you for your purchase! Your order <strong>#{{order_id}}</strong> has been confirmed and is being processed.</p>
  <div style="background:#f8f4ff;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #6B21A8;">
    <p style="margin:4px 0;color:#1e293b;"><strong>Product:</strong> {{product_title}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Amount Paid:</strong> {{order_amount}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Order Date:</strong> {{order_date}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Payment Method:</strong> {{payment_method}}</p>
  </div>
  <p style="color:#475569;">We will notify you once your order is shipped. You can track your order from your <a href="{{profile_url}}" style="color:#6B21A8;font-weight:bold;">Profile</a>.</p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'customer_name, order_id, product_title, order_amount, order_date, payment_method, profile_url',
            'description' => 'Sent to the buyer when an order is placed successfully.'
        ],
        'new_sale_notification' => [
            'name' => 'New Sale Notification (Seller)',
            'subject' => 'You Have a New Sale! Order #{{order_id}} | Listaria',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#22c55e;margin-top:0;">You Made a Sale!</h2>
  <p style="color:#475569;">Hi {{seller_name}},</p>
  <p style="color:#475569;">Great news! Someone just purchased your item. Here are the details:</p>
  <div style="background:#f0fdf4;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #22c55e;">
    <p style="margin:4px 0;color:#1e293b;"><strong>Order ID:</strong> #{{order_id}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Product:</strong> {{product_title}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Sale Amount:</strong> {{order_amount}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Buyer:</strong> {{buyer_name}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Date:</strong> {{order_date}}</p>
  </div>
  <p style="color:#475569;">Please ship the item promptly. You can view and manage your sales from your <a href="{{profile_url}}" style="color:#6B21A8;font-weight:bold;">Seller Dashboard</a>.</p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'seller_name, order_id, product_title, order_amount, buyer_name, order_date, profile_url',
            'description' => 'Sent to the seller when someone purchases their product.'
        ],
        'shipping_update' => [
            'name' => 'Shipping Update',
            'subject' => 'Your Order #{{order_id}} Has Been Shipped! | Listaria',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#1e293b;margin-top:0;">Your Order is On Its Way!</h2>
  <p style="color:#475569;">Hi {{customer_name}},</p>
  <p style="color:#475569;">Great news! Your order <strong>#{{order_id}}</strong> has been shipped.</p>
  <div style="background:#f0fdf4;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #22c55e;">
    <p style="margin:4px 0;color:#1e293b;"><strong>Product:</strong> {{product_title}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Estimated Delivery:</strong> {{delivery_date}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Status:</strong> {{shipping_status}}</p>
  </div>
  <p style="color:#475569;">Track your order from your <a href="{{profile_url}}" style="color:#6B21A8;font-weight:bold;">Profile</a>.</p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'customer_name, order_id, product_title, delivery_date, shipping_status, profile_url',
            'description' => 'Sent to the buyer when the order status is updated to Shipped.'
        ],
        'order_delivered' => [
            'name' => 'Order Delivered',
            'subject' => 'Your Order #{{order_id}} Has Been Delivered! | Listaria',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#22c55e;margin-top:0;">Order Delivered!</h2>
  <p style="color:#475569;">Hi {{customer_name}},</p>
  <p style="color:#475569;">Your order <strong>#{{order_id}}</strong> for <strong>"{{product_title}}"</strong> has been delivered successfully.</p>
  <p style="color:#475569;">We hope you love your purchase! If you have any issues, you can raise a return request from your <a href="{{profile_url}}" style="color:#6B21A8;font-weight:bold;">Profile</a>.</p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'customer_name, order_id, product_title, profile_url',
            'description' => 'Sent to the buyer when their order is marked as delivered.'
        ],
        'order_status_update' => [
            'name' => 'Order Status Update',
            'subject' => 'Order #{{order_id}} Update: {{new_status}} | Listaria',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#1e293b;margin-top:0;">Order Update</h2>
  <p style="color:#475569;">Hi {{customer_name}},</p>
  <p style="color:#475569;">There has been an update to your order <strong>#{{order_id}}</strong>.</p>
  <div style="background:#f8f4ff;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #6B21A8;">
    <p style="margin:4px 0;color:#1e293b;"><strong>Product:</strong> {{product_title}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>New Status:</strong> <span style="font-weight:bold;color:#6B21A8;">{{new_status}}</span></p>
  </div>
  <p style="color:#475569;">You can view your order details from your <a href="{{profile_url}}" style="color:#6B21A8;font-weight:bold;">Profile</a>.</p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'customer_name, order_id, product_title, new_status, profile_url',
            'description' => 'Sent to the buyer whenever the order status is updated by admin.'
        ],
        'listing_approved' => [
            'name' => 'Listing Approved',
            'subject' => 'Your Listing Has Been Approved! | Listaria',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#6B21A8;margin-top:0;">Listing Approved!</h2>
  <p style="color:#475569;">Hi {{seller_name}},</p>
  <p style="color:#475569;">We are happy to inform you that your product listing <strong>"{{product_title}}"</strong> has been approved by our team and is now <strong>live</strong> on the platform.</p>
  <p style="color:#475569;">Manage your listings from your <a href="{{dashboard_url}}" style="color:#6B21A8;font-weight:bold;">Dashboard</a>.</p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'seller_name, product_title, dashboard_url',
            'description' => 'Sent to the seller when their product listing is approved by admin.'
        ],
        'listing_rejected' => [
            'name' => 'Listing Rejected',
            'subject' => 'Your Listing Needs Attention | Listaria',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#ef4444;margin-top:0;">Listing Not Approved</h2>
  <p style="color:#475569;">Hi {{seller_name}},</p>
  <p style="color:#475569;">Unfortunately, your product listing <strong>"{{product_title}}"</strong> could not be approved at this time.</p>
  <div style="background:#fff5f5;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #ef4444;">
    <p style="margin:4px 0;color:#1e293b;"><strong>Reason:</strong> {{rejection_reason}}</p>
  </div>
  <p style="color:#475569;">Please update your listing based on the feedback above and resubmit it for review from your <a href="{{dashboard_url}}" style="color:#6B21A8;font-weight:bold;">Dashboard</a>.</p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'seller_name, product_title, rejection_reason, dashboard_url',
            'description' => 'Sent to the seller when their product listing is rejected.'
        ],
        'vendor_approved' => [
            'name' => 'Vendor Application Approved',
            'subject' => 'Welcome to the Listaria Vendor Family! | Listaria',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#6B21A8;margin-top:0;">You are now a Verified Vendor!</h2>
  <p style="color:#475569;">Hi {{user_name}},</p>
  <p style="color:#475569;">Congratulations! Your vendor application on Listaria has been <strong>approved</strong>. You can now list products for sale on our platform.</p>
  <div style="background:#f8f4ff;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #6B21A8;">
    <p style="margin:4px 0;color:#1e293b;">You have access to all vendor features including listing management, order tracking, and earnings dashboard.</p>
  </div>
  <p style="text-align:center;margin:20px 0;">
    <a href="{{dashboard_url}}" style="background:#6B21A8;color:white;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;">Go to Dashboard</a>
  </p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'user_name, dashboard_url',
            'description' => 'Sent to the user when their vendor application is approved.'
        ],
        'vendor_rejected' => [
            'name' => 'Vendor Application Rejected',
            'subject' => 'Your Vendor Application Status | Listaria',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#ef4444;margin-top:0;">Application Not Approved</h2>
  <p style="color:#475569;">Hi {{user_name}},</p>
  <p style="color:#475569;">After careful review, we regret to inform you that your vendor application could not be approved at this time.</p>
  <div style="background:#fff5f5;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #ef4444;">
    <p style="margin:4px 0;color:#1e293b;"><strong>Reason:</strong> {{rejection_reason}}</p>
  </div>
  <p style="color:#475569;">You are welcome to reapply after addressing the above. If you believe this was an error, please contact our <a href="{{support_url}}" style="color:#6B21A8;font-weight:bold;">support team</a>.</p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'user_name, rejection_reason, support_url',
            'description' => 'Sent to the user when their vendor application is rejected.'
        ],
        'return_submitted' => [
            'name' => 'Return Request Received',
            'subject' => 'Return Request Received for Order #{{order_id}} | Listaria',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#f59e0b;margin-top:0;">Return Request Submitted</h2>
  <p style="color:#475569;">Hi {{customer_name}},</p>
  <p style="color:#475569;">We have received your return request for the following order:</p>
  <div style="background:#fffbeb;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #f59e0b;">
    <p style="margin:4px 0;color:#1e293b;"><strong>Order ID:</strong> #{{order_id}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Product:</strong> {{product_title}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Return Reason:</strong> {{return_reason}}</p>
  </div>
  <p style="color:#475569;">Our team will review your request and get back to you within 2-3 business days. You can track your return from your <a href="{{profile_url}}" style="color:#6B21A8;font-weight:bold;">Profile</a>.</p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'customer_name, order_id, product_title, return_reason, profile_url',
            'description' => 'Sent to the buyer when they submit a return request.'
        ],
        'return_status_update' => [
            'name' => 'Return Status Update',
            'subject' => 'Return Update for Order #{{order_id}} | Listaria',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#1e293b;margin-top:0;">Return Status Updated</h2>
  <p style="color:#475569;">Hi {{customer_name}},</p>
  <p style="color:#475569;">There is an update on your return request for order <strong>#{{order_id}}</strong>.</p>
  <div style="background:#f8f4ff;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #6B21A8;">
    <p style="margin:4px 0;color:#1e293b;"><strong>Product:</strong> {{product_title}}</p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Return Status:</strong> <span style="font-weight:bold;color:#6B21A8;">{{return_status}}</span></p>
    <p style="margin:4px 0;color:#1e293b;"><strong>Message:</strong> {{status_message}}</p>
  </div>
  <p style="color:#475569;">View your return details from your <a href="{{profile_url}}" style="color:#6B21A8;font-weight:bold;">Profile</a>.</p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'customer_name, order_id, product_title, return_status, status_message, profile_url',
            'description' => 'Sent to the buyer when admin updates the return request status.'
        ],
        'welcome_email' => [
            'name' => 'Welcome / Email Verification',
            'subject' => 'Welcome to Listaria — Please Verify Your Email',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#1e293b;margin-top:0;">Welcome, {{user_name}}!</h2>
  <p style="color:#475569;">Thank you for joining Listaria — India\'s luxury recommerce platform. Please verify your email address to activate your account.</p>
  <div style="text-align:center;margin:28px 0;">
    <a href="{{verification_link}}" style="background:#6B21A8;color:white;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:1rem;">Verify My Email</a>
  </div>
  <p style="color:#94a3b8;font-size:0.9rem;">If you did not create an account on Listaria, please ignore this email.</p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'user_name, verification_link',
            'description' => 'Sent to new users after registration with a verification link.'
        ],
        'support_reply' => [
            'name' => 'Support Ticket Reply',
            'subject' => 'Re: Your Support Request #{{ticket_id}} | Listaria',
            'body' => '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:24px;border:1px solid #eee;border-radius:12px;">
  <div style="text-align:center;margin-bottom:20px;">
    <h1 style="color:#6B21A8;margin:0;font-size:2rem;letter-spacing:-1px;">listaria</h1>
  </div>
  <h2 style="color:#1e293b;margin-top:0;">Support Team Reply</h2>
  <p style="color:#475569;">Hi {{user_name}},</p>
  <p style="color:#475569;">Our support team has replied to your request <strong>#{{ticket_id}}</strong>:</p>
  <div style="background:#f8f4ff;padding:16px;border-radius:8px;margin:16px 0;border-left:4px solid #6B21A8;">
    <p style="margin:0;color:#1e293b;">{{reply_message}}</p>
  </div>
  <p style="color:#475569;">If you need further assistance, visit our <a href="{{support_url}}" style="color:#6B21A8;font-weight:bold;">Help Centre</a>.</p>
  <hr style="border:0;border-top:1px solid #eee;margin:20px 0;">
  <p style="font-size:12px;color:#94a3b8;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'user_name, ticket_id, reply_message, support_url',
            'description' => 'Sent to the user when an admin replies to their support ticket.'
        ],
    ];
}

function getEmailTemplate($pdo, $templateKey) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE template_key = ?");
        $stmt->execute([$templateKey]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($template) {
            return $template;
        }
    } catch (Exception $e) {}

    $defaults = getDefaultEmailTemplates();
    if (isset($defaults[$templateKey])) {
        return [
            'template_key' => $templateKey,
            'name' => $defaults[$templateKey]['name'],
            'subject' => $defaults[$templateKey]['subject'],
            'body' => $defaults[$templateKey]['body'],
            'variables' => $defaults[$templateKey]['variables'],
            'is_active' => 1
        ];
    }
    return null;
}

function renderEmailTemplate($pdo, $templateKey, $data = []) {
    $template = getEmailTemplate($pdo, $templateKey);
    if (!$template || !$template['is_active']) {
        return null;
    }

    $subject = $template['subject'];
    $body = $template['body'];

    foreach ($data as $key => $value) {
        $subject = str_replace('{{' . $key . '}}', htmlspecialchars((string)$value), $subject);
        $body = str_replace('{{' . $key . '}}', htmlspecialchars((string)$value), $body);
    }

    return [
        'subject' => $subject,
        'body' => $body
    ];
}

/**
 * Silently send a templated email. Returns true on success, false on failure.
 * Never throws — all errors are logged via error_log().
 */
function sendTemplateMail($pdo, $templateKey, $toEmail, $data = [], $toName = '') {
    if (!$toEmail) return false;
    try {
        require_once __DIR__ . '/config.php';
        $rendered = renderEmailTemplate($pdo, $templateKey, $data);
        if (!$rendered) return false;
        $smtp = createSmtp($pdo);
        $smtp->send($toEmail, $rendered['subject'], $rendered['body'], $toName ?: 'Listaria');
        return true;
    } catch (Exception $e) {
        error_log("Listaria mailer [{$templateKey}] to {$toEmail}: " . $e->getMessage());
        return false;
    }
}
