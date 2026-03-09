<?php
function getDefaultEmailTemplates() {
    return [
        'order_confirmation' => [
            'name' => 'Order Confirmation',
            'subject' => 'Order Confirmed - {{order_id}} | Listaria',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="color: #6B21A8; margin: 0;">Listaria</h1>
    </div>
    <h2 style="color: #333;">Order Confirmed!</h2>
    <p>Hi {{customer_name}},</p>
    <p>Thank you for your order! Your order <strong>#{{order_id}}</strong> has been confirmed and is being processed.</p>
    <div style="background: #f8f4ff; padding: 15px; border-radius: 8px; margin: 15px 0;">
        <p style="margin: 5px 0;"><strong>Product:</strong> {{product_title}}</p>
        <p style="margin: 5px 0;"><strong>Amount:</strong> {{order_amount}}</p>
        <p style="margin: 5px 0;"><strong>Date:</strong> {{order_date}}</p>
    </div>
    <p>We will notify you once your order is shipped.</p>
    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'customer_name, order_id, product_title, order_amount, order_date',
            'description' => 'Sent to the buyer when an order is placed successfully.'
        ],
        'shipping_update' => [
            'name' => 'Shipping Update',
            'subject' => 'Your Order #{{order_id}} Has Been Shipped! | Listaria',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="color: #6B21A8; margin: 0;">Listaria</h1>
    </div>
    <h2 style="color: #333;">Your Order is On Its Way!</h2>
    <p>Hi {{customer_name}},</p>
    <p>Great news! Your order <strong>#{{order_id}}</strong> has been shipped.</p>
    <div style="background: #f0fdf4; padding: 15px; border-radius: 8px; margin: 15px 0;">
        <p style="margin: 5px 0;"><strong>Product:</strong> {{product_title}}</p>
        <p style="margin: 5px 0;"><strong>Estimated Delivery:</strong> {{delivery_date}}</p>
        <p style="margin: 5px 0;"><strong>Status:</strong> {{shipping_status}}</p>
    </div>
    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'customer_name, order_id, product_title, delivery_date, shipping_status',
            'description' => 'Sent to the buyer when the order status is updated to shipped.'
        ],
        'listing_approved' => [
            'name' => 'Listing Approved',
            'subject' => 'Your Listing Has Been Approved! | Listaria',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="color: #6B21A8; margin: 0;">Listaria</h1>
    </div>
    <h2 style="color: #6B21A8;">Congratulations {{seller_name}}!</h2>
    <p>We are happy to inform you that your product listing <strong>"{{product_title}}"</strong> has been approved by our team.</p>
    <p>It is now live on the platform and visible to potential buyers.</p>
    <p>You can manage your listings from your <a href="{{dashboard_url}}" style="color: #6B21A8; text-decoration: none; font-weight: bold;">Dashboard</a>.</p>
    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'seller_name, product_title, dashboard_url',
            'description' => 'Sent to the seller when their product listing is approved by admin.'
        ],
        'listing_rejected' => [
            'name' => 'Listing Rejected',
            'subject' => 'Listing Update - Action Required | Listaria',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="color: #6B21A8; margin: 0;">Listaria</h1>
    </div>
    <h2 style="color: #e53e3e;">Listing Not Approved</h2>
    <p>Hi {{seller_name}},</p>
    <p>Unfortunately, your product listing <strong>"{{product_title}}"</strong> was not approved.</p>
    <div style="background: #fff5f5; padding: 15px; border-radius: 8px; margin: 15px 0;">
        <p style="margin: 5px 0;"><strong>Reason:</strong> {{rejection_reason}}</p>
    </div>
    <p>Please update your listing and resubmit it for review.</p>
    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'seller_name, product_title, rejection_reason',
            'description' => 'Sent to the seller when their product listing is rejected.'
        ],
        'welcome_email' => [
            'name' => 'Welcome Email',
            'subject' => 'Welcome to Listaria! Verify Your Email',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="color: #6B21A8; margin: 0;">Listaria</h1>
    </div>
    <h2 style="color: #333;">Welcome, {{user_name}}!</h2>
    <p>Thank you for joining Listaria. Please verify your email address to get started.</p>
    <div style="text-align: center; margin: 25px 0;">
        <a href="{{verification_link}}" style="background: #6B21A8; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: bold;">Verify Email</a>
    </div>
    <p>If you did not create an account, please ignore this email.</p>
    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'user_name, verification_link',
            'description' => 'Sent to new users after registration with a verification link.'
        ],
        'order_delivered' => [
            'name' => 'Order Delivered',
            'subject' => 'Your Order #{{order_id}} Has Been Delivered! | Listaria',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="color: #6B21A8; margin: 0;">Listaria</h1>
    </div>
    <h2 style="color: #22c55e;">Order Delivered!</h2>
    <p>Hi {{customer_name}},</p>
    <p>Your order <strong>#{{order_id}}</strong> for <strong>"{{product_title}}"</strong> has been delivered successfully.</p>
    <p>We hope you love your purchase! If you have any issues, please contact our support team.</p>
    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'customer_name, order_id, product_title',
            'description' => 'Sent to the buyer when their order is marked as delivered.'
        ],
        'support_reply' => [
            'name' => 'Support Ticket Reply',
            'subject' => 'Re: Support Ticket #{{ticket_id}} | Listaria',
            'body' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="color: #6B21A8; margin: 0;">Listaria</h1>
    </div>
    <h2 style="color: #333;">Support Update</h2>
    <p>Hi {{user_name}},</p>
    <p>Our team has replied to your support ticket <strong>#{{ticket_id}}</strong>:</p>
    <div style="background: #f8f4ff; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #6B21A8;">
        <p style="margin: 0;">{{reply_message}}</p>
    </div>
    <p>If you need further assistance, please reply to your ticket from the support page.</p>
    <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
    <p style="font-size: 12px; color: #666;">This is an automated message from Listaria. Please do not reply to this email.</p>
</div>',
            'variables' => 'user_name, ticket_id, reply_message',
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
        $subject = str_replace('{{' . $key . '}}', htmlspecialchars($value), $subject);
        $body = str_replace('{{' . $key . '}}', htmlspecialchars($value), $body);
    }

    return [
        'subject' => $subject,
        'body' => $body
    ];
}
