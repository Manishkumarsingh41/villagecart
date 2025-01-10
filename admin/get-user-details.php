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
$user = get_user_details($user_id);

header('Content-Type: application/json');
echo json_encode($user ?: ['error' => 'User not found']);
?>
