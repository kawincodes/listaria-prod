<?php
require 'includes/db.php';

try {
    $aboutContent = '
    <div style="font-family: \'Inter\', sans-serif; color: #333; line-height: 1.8;">
        <h1 style="text-align: center; font-size: 2.5rem; margin-bottom: 2rem; color: #1e293b;">About Listaria</h1>
        
        <div style="margin-bottom: 3rem;">
            <h2 style="color: #2c3e50; font-size: 1.8rem; margin-bottom: 1rem;">The Bridge to Better</h2>
            <p style="font-size: 1.1rem; color: #555;">At Listaria, we believe that high-quality lifestyle products should be accessible, reliable, and sustainable. We are more than just a marketplace; we are a community-driven platform dedicated to redefining the pre-loved category through trust, transparency, and innovation.</p>
            <p style="font-size: 1.1rem; color: #555;">In a world where digital transactions often carry risks of fraud or misinformation, Listaria stands as a shield for the consumer. We’ve built a system that ensures what you see is exactly what you get.</p>
        </div>

        <div style="background: #f8f9fa; padding: 2rem; border-radius: 12px; margin-bottom: 3rem; border-left: 5px solid #2ecc71;">
            <h2 style="color: #2c3e50; font-size: 1.8rem; margin-top: 0;">Our Vision</h2>
            <p style="font-size: 1.2rem; font-weight: 500; color: #444;">To become the most trusted ecosystem for premium pre-loved goods, where every transaction is backed by integrity and every customer feels empowered to make sustainable choices without compromising on quality.</p>
        </div>

        <div style="margin-bottom: 3rem;">
            <h2 style="color: #2c3e50; font-size: 1.8rem; margin-bottom: 1.5rem;">What Makes Us Different?</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div style="padding: 1.5rem; border: 1px solid #eee; border-radius: 8px;">
                    <h3 style="color: #3498db; margin-top: 0;">The LISTARIA Standard</h3>
                    <p>Listaria is powered by our proprietary, patent-pending approach to commerce, ensuring a seamless flow from listing to delivery.</p>
                </div>
                <div style="padding: 1.5rem; border: 1px solid #eee; border-radius: 8px;">
                    <h3 style="color: #3498db; margin-top: 0;">Verified Quality</h3>
                    <p>We offer an optional, expert-led product verification service. Items can be inspected at our dedicated warehouse to ensure authenticity.</p>
                </div>
                <div style="padding: 1.5rem; border: 1px solid #eee; border-radius: 8px;">
                    <h3 style="color: #3498db; margin-top: 0;">Secure Transactions</h3>
                    <p>We eliminate the "middleman anxiety." Our platform manages the coordination between buyers and sellers, ensuring sellers are paid promptly.</p>
                </div>
                <div style="padding: 1.5rem; border: 1px solid #eee; border-radius: 8px;">
                    <h3 style="color: #3498db; margin-top: 0;">Curated Experience</h3>
                    <p>From our "24-Hour Exclusive Drops" to our interactive "Bidding" and "Watching" features, we provide a engaging shopping experience.</p>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 3rem;">
            <h2 style="color: #2c3e50; font-size: 1.8rem; margin-bottom: 1rem;">Our Commitment to You</h2>
            <p style="font-size: 1.1rem; color: #555;">Whether you are a seller looking for a fair platform to rehome your items or a buyer seeking premium products at a better value, Listaria is built for you. We are committed to fostering a fraud-free environment where quality meets sustainability.</p>
            <p style="font-size: 1.2rem; font-weight: bold; margin-top: 1rem; color: #333;">Welcome to Listaria. Experience the future of pre-loved commerce.</p>
        </div>

        <div style="margin-top: 3rem; text-align: center; border-top: 1px solid #eee; padding-top: 2rem;">
            <h2 style="color: #2c3e50; font-size: 1.8rem;">The Leadership</h2>
            <p style="font-size: 1.1rem; color: #666; max-width: 800px; margin: 0 auto;">Founded by a team of ambitious entrepreneurs at the intersection of marketing and finance, Listaria was born out of a desire to solve the trust deficit in the modern marketplace. Led by our CEO <strong>Harsh Vardhan Jaiswal</strong> and our CFMO <strong>Aryan Biswa</strong>, we are dedicated to perfect execution and customer satisfaction.</p>
        </div>
    </div>';

    $stmt = $pdo->prepare("INSERT OR REPLACE INTO site_settings (setting_key, setting_value, updated_at) VALUES ('about_content', ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$aboutContent]);
    
    echo "<h1>About Us Content Initialized</h1>";
    echo "<p>Content saved to DB.</p>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
