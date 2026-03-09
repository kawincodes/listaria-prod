<?php
require 'includes/db.php';

try {
    $refund_content = "Refund & Return Policy – Listaria
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
1. Buyer initiates return request with images and detailed reason.
2. Team Listaria evaluates the claim within 2 working days.
3. If approved, pickup will be arranged and the item returned to the seller.
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

By placing an order on Listaria, users acknowledge that they have reviewed the product listing carefully and agree to this Refund & Return Policy.";

    $refund_content = str_replace('•', '-', $refund_content);
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute(['refund_policy', $refund_content]);

    echo "Refund Policy initialized successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
