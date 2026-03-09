<?php
require 'includes/db.php';

try {
    // Fetch current content
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'founder_2_note'");
    $stmt->execute();
    $content = $stmt->fetchColumn();

    if ($content) {
        // Remove the signature div
        // The signature is: <div style="margin-top: 1rem;"><strong>Aryan Biswa</strong><br>Co-Founder & CFMO, Listaria</div>
        
        $signature = '<div style="margin-top: 1rem;"><strong>Aryan Biswa</strong><br>Co-Founder & CFMO, Listaria</div>';
        $newContent = str_replace($signature, '', $content);
        
        // Also remove any trailing newlines or spaces if needed
        $newContent = trim($newContent);

        $update = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'founder_2_note'");
        $update->execute([$newContent]);
        
        echo "Updated founder 2 note successfully.";
    } else {
        echo "Founder 2 note not found.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
