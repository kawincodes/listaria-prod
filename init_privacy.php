<?php
require 'includes/db.php';

try {
    // Privacy Policy Content
    $privacy_content = "Listaria Policies
Last Updated: January 23, 2026

📦 Shipping Policy
At Listaria, we are committed to delivering a smooth and transparent shipping experience. We have partnered with trusted logistics providers to ensure safe, timely, and eco-conscious deliveries across India.

1. Delivery Timelines
- Orders are typically delivered within 3–7 business days.
- Delivery times may vary based on the buyer's location, product category, and availability.
- Buyers will receive real-time tracking updates after the item is picked up.

2. Shipping Charges
- Free delivery on orders above ₹499.
- For orders below this threshold, a nominal shipping charge is applied based on category and location.
- The delivery charge (if any) will be shown during checkout.

3. Pickup & Delivery Handling
- For sellers, we provide doorstep pickup of listed products.
- For buyers, we offer doorstep delivery, ensuring convenience and accountability at both ends.
- All logistics are handled by Listaria’s verified courier partners.

4. Order Tracking
- Every shipment will have a tracking ID.
- Users can track real-time updates through their dashboard or order confirmation email/SMS.

5. Damaged or Missing Packages
If an item is damaged during transit or missing from the package, users must report the issue within 24 hours of delivery, with photographic proof, via the app or support email. We will initiate an investigation and resolve the issue promptly.

🔄 Refund & Return Policy
Effective Date: January 23, 2026
Listaria is built on trust. As a curated marketplace platform, we aim to ensure fairness and transparency for both buyers and sellers.
Because many products sold on Listaria are pre-owned or thrifted, return eligibility may vary based on product category.

1. Eligible Returns
A return and refund may be approved if:
• The product received is significantly different from the listing description.
• The product is damaged or non-functional and such condition was not disclosed in the listing.
• The wrong item was delivered.
All claims must be supported with clear images and submitted within the return window.

2. Non-Returnable Items
The following items are not eligible for return:
• Products sold “as-is” with flaws clearly disclosed in the listing.
• Items marked “Final Sale” or personalized/altered items.
• Undergarments, cosmetics, hygiene-sensitive products, or perishables.
• Items returned in used, altered, or damaged condition after delivery.

3. Special Policy for Thrift & Pre-Owned Items
Due to the nature of second-hand and thrift products:
• Returns will not be accepted for sizing issues, fit concerns, or minor wear consistent with normal pre-owned use.
• Buyers are encouraged to use the in-app chat feature to request precise measurements, condition details, and additional photos before purchase.
• Sellers are encouraged to provide accurate measurements and transparent condition disclosures in both listing description and chat communication.
A return for thrift items will only be approved if the product is materially different from the description or significantly misrepresented.

4. Return Window
• Buyers must raise a return request within 48 hours of delivery.
• Requests must be submitted via the “My Orders” section or by contacting official support.
• Requests submitted after the window may not be considered.

5. Return Process
- Buyer initiates return request with images and detailed reason.
- Team Listaria evaluates the claim within 2 working days.
- If approved, pickup will be arranged and the item returned to the seller.
Listaria reserves the right to request additional documentation or verification before approving a return.

6. Condition Verification
Returned items must:
• Be in the same condition as delivered
• Include original packaging, tags, and accessories (if applicable)
• Show no additional damage or usage after delivery
If an item fails verification upon return, the refund may be rejected.

7. Refund Method
• Approved refunds will be processed via PhonePe or to the original payment source.
• Refunds are typically processed within 5–7 working days after approval and successful return verification.

8. Return Shipping Charges
In case of an approved return, the buyer shall bear the pickup and return shipping charges unless the return is approved due to clear seller misrepresentation, delivery of the wrong item, or undisclosed major damage.
The applicable pickup charges will be deducted from the refund amount at the time of processing.
If a return request is rejected after review, the product may be sent back to the buyer and additional shipping charges may apply.

9. Fraud Protection & Misuse
To maintain platform integrity:
• Repeated, suspicious, or abusive refund requests may result in account review.
• Listaria reserves the right to suspend accounts, withhold refunds, or restrict platform access in cases of suspected misuse, fraud, or policy manipulation.

Final Note
By placing an order on Listaria, users acknowledge that they have reviewed the product listing carefully and agree to this Refund & Return Policy.

🔐 Data Privacy & Chat Monitoring Policy
At Listaria, user safety, fraud prevention, and dispute resolution are core priorities. As a digital marketplace platform operating in India, Listaria may collect, store, and process certain user data in compliance with applicable Indian laws, including the Information Technology Act, 2000, IT Rules 2021, and the Digital Personal Data Protection Act, 2023.

1. Data We Collect
We may collect the following information:
• Name, contact details, and profile information
• Transaction and payment details
• Shipping and delivery information
• Device and log data
• Chat communications between buyers and sellers conducted within the Listaria platform

2. Chat Monitoring & Usage
To ensure platform integrity and user safety:
• Chats conducted within the Listaria platform may be stored and reviewed.
• Monitoring may be performed through automated systems or manual review when required.
• Chat data may be accessed strictly for:
o Fraud prevention
o Dispute resolution
o Prevention of illegal activities
o Ensuring compliance with platform rules
Listaria does not use private chat data for advertising, selling insights, or commercial exploitation.

3. Legal Basis & Consent
By creating an account and using Listaria, users provide informed consent for:
• Collection and processing of personal data
• Storage of transaction-related communication
• Monitoring for safety and compliance purposes
Users may withdraw consent by deleting their account, subject to legal data retention requirements.

4. Data Storage & Security
• All data is stored securely using industry-standard encryption and safeguards.
• Access to sensitive information is restricted to authorized personnel only.
• Listaria implements technical and organizational measures to prevent unauthorized access, misuse, or data breaches.

5. Data Retention
• User data is retained only as long as necessary for operational, legal, or compliance purposes.
• Chat logs related to disputes may be retained for evidentiary purposes as required under law.

6. User Rights
Under applicable Indian data protection laws, users have the right to:
• Request access to their personal data
• Request correction of inaccurate information
• Request deletion of personal data (subject to legal obligations)
• File grievances regarding data misuse
Requests may be submitted through the Help & Support section or by emailing support@listaria.in

💬 Still Need Help?
Contact our support team at support@listaria.in or through the Help & Support section of the app. We're here to make your buying and selling journey safe.";

    $privacy_content = str_replace('•', '-', $privacy_content);
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute(['privacy_policy', $privacy_content]);
    
    // Also update if it exists (in case we re-run)
    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'privacy_policy'");
    $stmt->execute([$privacy_content]);

    echo "Privacy Policy initialized successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
