<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !is_admin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User ID not provided']);
    exit();
}

$user_id = intval($_GET['id']);

// Get user's orders
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($orders);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error fetching orders']);
}
?>
