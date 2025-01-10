<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !is_admin()) {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shop_id'])) {
    $shop_id = intval($_POST['shop_id']);
    
    if (update_shop_status($shop_id, 'rejected')) {
        $_SESSION['success'] = "Shop rejected successfully!";
    } else {
        $_SESSION['error'] = "Error rejecting shop.";
    }
}

header("Location: shops.php");
exit();
?>
