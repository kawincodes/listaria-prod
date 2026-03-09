<?php
session_start();
// Simulate a Google User Login
$_SESSION['user_id'] = 888; // dummy ID
$_SESSION['full_name'] = "Google Test User";
$_SESSION['email'] = "testuser@gmail.com";
$_SESSION['is_admin'] = 0; // Regular user

// Redirect to home
header("Location: index.php");
exit;
?>
