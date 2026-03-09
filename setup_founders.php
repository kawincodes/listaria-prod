<?php
require 'includes/db.php';

try {
    $founder1Note = '
    <h3 style="color: #2c3e50; font-size: 1.5rem; margin-top: 0;">A Message from Our CEO</h3>
    <h2 style="font-size: 1.8rem; margin: 0 0 1rem; color: #1e293b;">Building a Future Grounded in Integrity</h2>
    <p>The core of every great venture is a simple question: How can we make things better? When we started Listaria, the answer was clear. We saw a marketplace landscape filled with potential but held back by a lack of accountability. Buyers were hesitant, and sellers lacked a professional platform that truly valued their items. As CEO, my mission has been to transform that landscape into an ecosystem where integrity is the default setting, not an after-thought.</p>
    <p>At Listaria, we are obsessed with the details. From the initial concept of our patent-pending LAVPLES system to the final delivery at your doorstep, every step is engineered to eliminate friction and build confidence. We believe that the "pre-loved" market should offer the same level of sophistication and security as a luxury retail experience.</p>
    <p>Our goal is to lead the shift toward a more circular and sustainable economy, but we know that sustainability only works when it is backed by reliability. By implementing expert-led verification and coordinated logistics, we are setting a new gold standard for how goods change hands.</p>
    <p>We are not just building a platform; we are setting a movement in motion—one where every transaction is a bridge to a better, more transparent future.</p>
    <p>Thank you for trusting us with your lifestyle and your journey.</p>
    <div style="margin-top: 1rem;"><strong>Harsh Vardhan Jaiswal</strong><br>CEO & Co-Founder, Listaria</div>';

    $founder2Note = '
    <h3 style="color: #2c3e50; font-size: 1.5rem; margin-top: 0;">A Note from Our CMFO</h3>
    <h2 style="font-size: 1.8rem; margin: 0 0 1rem; color: #1e293b;">Redefining Value in a Modern World</h2>
    <p>When we first envisioned Listaria, we didn\'t just see a marketplace; we saw an opportunity to fix a broken bridge. For too long, the transition of premium goods from one person to another has been clouded by scepticism, the risk of fraud, and a lack of transparency.</p>
    <p>As the Chief Financial and Marketing Officer, my focus has always been on two things: Value and Trust.</p>
    <p>To me, value isn’t just about the price tag—it’s about the security of your investment. Whether you are buying or selling, you deserve a platform that respects your time and your money. That is why we developed Listaria. We wanted to move away from the traditional "second-hand" mindset and create a premium, "pre-loved" category that prioritizes perfect execution and consumer safety.</p>
    <p>Through our patent-pending LAVPLES framework and our rigorous verification processes, we are ensuring that quality is never a guessing game. We are building a community where sustainability meets reliability, allowing you to access the lifestyle you want with the peace of mind you deserve.</p>
    <p>Listaria is our commitment to a better, more transparent way of doing business. We aren\'t just selling products; we are building the foundation for the future of commerce.</p>
    <p>Thank you for being part of this journey.</p>
    <div style="margin-top: 1rem;"><strong>Aryan Biswa</strong><br>Co-Founder & CFMO, Listaria</div>';

    // Insert Founder 1
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO site_settings (setting_key, setting_value, updated_at) VALUES ('founder_1_note', ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$founder1Note]);

    $stmt = $pdo->prepare("INSERT OR REPLACE INTO site_settings (setting_key, setting_value, updated_at) VALUES ('founder_1_image', ?, CURRENT_TIMESTAMP)");
    $stmt->execute(['https://via.placeholder.com/300x300?text=CEO']); 

    // Insert Founder 2
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO site_settings (setting_key, setting_value, updated_at) VALUES ('founder_2_note', ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$founder2Note]);

    $stmt = $pdo->prepare("INSERT OR REPLACE INTO site_settings (setting_key, setting_value, updated_at) VALUES ('founder_2_image', ?, CURRENT_TIMESTAMP)");
    $stmt->execute(['https://via.placeholder.com/300x300?text=CFMO']);

    echo "<h1>Founders Content Initialized</h1>";
    echo "<p>Content saved to DB.</p>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
