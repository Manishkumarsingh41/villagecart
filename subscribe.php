<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

try {
    $pdo = get_db_connection();
    
    // Check if email already exists
    $stmt = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE email = ?');
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['message' => 'You are already subscribed to our newsletter!']);
        exit;
    }
    
    // Add new subscriber
    $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (email, created_at) VALUES (?, NOW())');
    $stmt->execute([$email]);
    
    echo json_encode(['message' => 'Thank you for subscribing to our newsletter!']);
} catch (PDOException $e) {
    error_log('Newsletter subscription error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing your request']);
}
