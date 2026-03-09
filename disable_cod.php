<?php
require 'includes/db.php';

try {
    // Config: COD False, PhonePe True
    $config = [
        'cod' => false,
        'phonepe' => true,
        'online' => false,
        'paytm' => false
    ];
    $json = json_encode($config);
    
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO site_settings (setting_key, setting_value, updated_at) VALUES ('payment_config', ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$json]);
    
    echo "<h1>Configuration Updated</h1>";
    echo "<p>Cash on Delivery: <strong>Disabled</strong></p>";
    echo "<p>PhonePe: <strong>Enabled</strong></p>";
    echo "<p><a href='payment_method.php'>Check Payment Page</a></p>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
