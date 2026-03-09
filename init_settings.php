<?php
require 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT UNIQUE,
        setting_value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert Default TOS
    $default_tos = "Terms & Conditions (T&C) – Listaria
Effective Date: January 23, 2026

These Terms & Conditions (“Terms”) govern your access to and use of the Listaria platform, website, and mobile application (collectively referred to as the “Platform”).

By registering, accessing, or transacting on Listaria, you agree to be legally bound by these Terms.

1. User Responsibilities
Users of the Listaria platform agree to the following responsibilities:
• Users must provide accurate, truthful, and complete information in listings and profile details.
• Sellers must disclose product flaws and must not list counterfeit, illegal, stolen, or banned items.
• Buyers must inspect items upon delivery and raise concerns within the applicable return window.
Failure to comply may result in suspension or termination of account access.

2. Prohibited Use
The following activities are strictly prohibited on the Platform:
• Listing or selling illegal, pirated, counterfeit, stolen, or restricted goods.
• Posting offensive, abusive, misleading, or inappropriate language, images, or content.
• Attempting to bypass Listaria’s transaction system, including encouraging off-platform payments or communication for fee avoidance.
• Engaging in scams, fraud, or deceptive practices.
Violation of these rules may result in immediate account suspension and potential legal action.

3. Platform Rights
Listaria reserves the right to:
• Remove or suspend listings that violate policies.
• Deactivate accounts involved in fraud, spam, abuse, or misconduct.
• Temporarily hold seller payouts in cases of suspected fraud, disputes, or policy violations.
• Modify, suspend, or discontinue platform features or services at any time without prior notice.

4. Intellectual Property
• All content, trademarks, logos, designs, graphics, text, and code on the Platform are the exclusive property of Listaria.
• Unauthorized use, reproduction, distribution, or exploitation of Listaria’s intellectual property is strictly prohibited.

5. Legal Status & Liability
• Listaria operates as a marketplace facilitator and intermediary.
• Listaria does not own, manufacture, or directly verify third-party listed products.
• Sellers remain solely responsible for the quality, authenticity, legality, and accuracy of their listed products.
• In case of disputes, Listaria may mediate between users; however, final resolution may involve legal authorities if necessary.

6. Communication & Platform Monitoring
• All communications between buyers and sellers must occur within the Listaria Platform.
• Listaria may store and monitor in-app communications to ensure compliance, prevent fraud, and resolve disputes.
• Monitoring may be conducted using automated tools or manual review where necessary.
• Attempts to share personal contact information to bypass platform fees may result in account suspension or termination.
By using the Platform, users acknowledge and consent to such monitoring as a condition of usage.

7. Intermediary Status
• Listaria operates as an intermediary under applicable Indian laws, including the Information Technology Act, 2000 and relevant IT Rules.
• Listaria does not control or assume responsibility for user-generated listings.
• Upon receiving valid legal notice, Listaria may remove unlawful listings or restrict access as required by law.

8. Limitation of Liability
• Listaria shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from user transactions.
• Platform services are provided on an “as-is” and “as-available” basis.
• Listaria does not guarantee uninterrupted, error-free, or secure access to the Platform.

9. Governing Law & Jurisdiction
These Terms shall be governed by and construed in accordance with the laws of India.
Any disputes arising out of or relating to the use of the Listaria Platform shall be subject to the exclusive jurisdiction of the courts located in Bengaluru, Karnataka.

10. Eligibility
• Users must be at least 18 years of age to register or transact on the Listaria Platform.
• By using the Platform, you represent and warrant that you are legally competent to enter into binding contracts under applicable law.

11. Indemnification
Users agree to indemnify, defend, and hold harmless Listaria, its directors, officers, employees, affiliates, and partners from any claims, damages, losses, liabilities, or expenses (including legal fees) arising out of:
• Violation of these Terms
• Infringement of intellectual property rights
• Sale of counterfeit, illegal, or misrepresented goods
• Fraudulent, negligent, or unlawful conduct

12. Consent & Disclaimer for High-Value Items (Applicable to Items Above ₹10,000)
• Before completing the purchase of high-value items, users must acknowledge the following:
• By proceeding with this purchase, you confirm that you have thoroughly reviewed the product listing, description, and photos.
• Listaria provides logistical support and AI-driven authenticity tools; however, the seller remains solely responsible for the quality, authenticity, and accuracy of the listed product.
• Refunds for high-value goods are subject to manual review and verification.
• Listaria reserves the right to request additional documentation, verification, or inspection before approving refunds for high-value transactions.
• Users may be required to digitally confirm acceptance of this disclaimer via checkbox acknowledgment or OTP validation at checkout.

13. Product Inspection Checklist
For electronics, furniture, or high-demand items, sellers may optionally submit a product inspection checklist to increase buyer confidence. This may include:

Functionality Status:
• ✓ Turns on
• ✓ No physical damage
• ✓ All ports/buttons working
Accessories Included:
• ✓ Original charger
• ✓ Packaging
Condition Photos Uploaded:
• ✓ Front view
• ✓ Back view
• ✓ Side view
• ✓ Damage (if any)
This checklist is intended to enhance transparency and reduce disputes; however, sellers remain responsible for the accuracy of the information provided.

Final Acknowledgment
By accessing, registering, or transacting on the Listaria Platform, you confirm that you have read, understood, and agreed to these Terms & Conditions.";

    $default_tos = str_replace('•', '-', $default_tos);

    $stmt = $pdo->prepare("INSERT OR IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->execute(['terms_of_service', $default_tos]);
    
    // Also update if it exists
    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'terms_of_service'");
    $stmt->execute([$default_tos]);

    echo "Table site_settings checked and TOS updated.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
