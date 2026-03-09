<?php
require 'includes/db.php';

try {
    // Fetch current content
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'founder_1_note'");
    $stmt->execute();
    $content = $stmt->fetchColumn();

    if ($content) {
        // Remove the signature div
        // The signature is: <div style="margin-top: 1rem;"><strong>Harsh Vardhan Jaiswal</strong><br>CEO & Co-Founder, Listaria</div>
        // We will use regex to remove it cleanly, or string replace if exact match
        
        $signature = '<div style="margin-top: 1rem;"><strong>Harsh Vardhan Jaiswal</strong><br>CEO & Co-Founder, Listaria</div>';
        $newContent = str_replace($signature, '', $content);
        
        // Also remove any trailing newlines or spaces if needed
        $newContent = trim($newContent);

        $update = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'founder_1_note'");
        $update->execute([$newContent]);
        
        echo "Updated founder 1 note successfully.";
    } else {
        echo "Founder 1 note not found.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
