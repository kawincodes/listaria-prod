<?php
require_once __DIR__ . '/includes/session.php';
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['account_type'] ?? 'customer') !== 'vendor') {
    header("Location: login.php");
    exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $business_name = $_POST['business_name'] ?? '';
    $business_bio = $_POST['business_bio'] ?? '';
    $whatsapp_number = $_POST['whatsapp_number'] ?? '';
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    $profile_image_path = null;
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        
        $tmp_name = $_FILES['profile_image']['tmp_name'];
        $name = basename($_FILES['profile_image']['name']);
        $target_file = $upload_dir . uniqid() . '_' . $name;
        
        if (move_uploaded_file($tmp_name, $target_file)) {
            $profile_image_path = $target_file;
        }
    }

    if ($profile_image_path) {
        $stmt = $pdo->prepare("UPDATE users SET business_name = ?, business_bio = ?, whatsapp_number = ?, is_public = ?, profile_image = ? WHERE id = ?");
        $result = $stmt->execute([$business_name, $business_bio, $whatsapp_number, $is_public, $profile_image_path, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET business_name = ?, business_bio = ?, whatsapp_number = ?, is_public = ? WHERE id = ?");
        $result = $stmt->execute([$business_name, $business_bio, $whatsapp_number, $is_public, $user_id]);
    }
    
    if ($result) {
        header("Location: profile.php?msg=business_updated");
    } else {
        header("Location: profile.php?msg=update_failed");
    }
    exit;
}
