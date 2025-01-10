<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in to manage addresses']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    $pdo = get_db_connection();
    
    switch ($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $address1 = trim($_POST['address1'] ?? '');
            $address2 = trim($_POST['address2'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $make_default = isset($_POST['make_default']) && $_POST['make_default'] === 'on';
            
            if (empty($address1) || empty($city) || empty($state) || empty($postal_code)) {
                http_response_code(400);
                echo json_encode(['error' => 'Please fill in all required fields']);
                exit;
            }
            
            // If this is the first address or make_default is true, update all other addresses
            if ($make_default) {
                $stmt = $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?');
                $stmt->execute([$user_id]);
            }
            
            // Check if this is the first address
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM addresses WHERE user_id = ?');
            $stmt->execute([$user_id]);
            $is_first = $stmt->fetch()['count'] === 0;
            
            // Add new address
            $stmt = $pdo->prepare('
                INSERT INTO addresses (
                    user_id, 
                    address_line1, 
                    address_line2, 
                    city, 
                    state, 
                    postal_code, 
                    is_default,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ');
            $stmt->execute([
                $user_id,
                $address1,
                $address2,
                $city,
                $state,
                $postal_code,
                $make_default || $is_first ? 1 : 0
            ]);
            
            echo json_encode([
                'message' => 'Address added successfully',
                'address_id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $address_id = filter_input(INPUT_POST, 'address_id', FILTER_VALIDATE_INT);
            $address1 = trim($_POST['address1'] ?? '');
            $address2 = trim($_POST['address2'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $postal_code = trim($_POST['postal_code'] ?? '');
            $make_default = isset($_POST['make_default']) && $_POST['make_default'] === 'on';
            
            if (!$address_id || empty($address1) || empty($city) || empty($state) || empty($postal_code)) {
                http_response_code(400);
                echo json_encode(['error' => 'Please fill in all required fields']);
                exit;
            }
            
            // Verify address belongs to user
            $stmt = $pdo->prepare('SELECT id FROM addresses WHERE id = ? AND user_id = ?');
            $stmt->execute([$address_id, $user_id]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Address not found']);
                exit;
            }
            
            // If make_default is true, update all other addresses
            if ($make_default) {
                $stmt = $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?');
                $stmt->execute([$user_id]);
            }
            
            // Update address
            $stmt = $pdo->prepare('
                UPDATE addresses SET 
                    address_line1 = ?,
                    address_line2 = ?,
                    city = ?,
                    state = ?,
                    postal_code = ?,
                    is_default = ?
                WHERE id = ? AND user_id = ?
            ');
            $stmt->execute([
                $address1,
                $address2,
                $city,
                $state,
                $postal_code,
                $make_default ? 1 : 0,
                $address_id,
                $user_id
            ]);
            
            echo json_encode(['message' => 'Address updated successfully']);
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $address_id = filter_input(INPUT_POST, 'address_id', FILTER_VALIDATE_INT);
            
            if (!$address_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid address ID']);
                exit;
            }
            
            // Verify address belongs to user and is not the only address
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM addresses WHERE user_id = ?');
            $stmt->execute([$user_id]);
            if ($stmt->fetch()['count'] === 1) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete the only address']);
                exit;
            }
            
            // Delete address
            $stmt = $pdo->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?');
            $stmt->execute([$address_id, $user_id]);
            
            // If deleted address was default, make the most recent address default
            $stmt = $pdo->prepare('
                SELECT id FROM addresses 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ');
            $stmt->execute([$user_id]);
            $new_default = $stmt->fetch();
            
            if ($new_default) {
                $stmt = $pdo->prepare('UPDATE addresses SET is_default = 1 WHERE id = ?');
                $stmt->execute([$new_default['id']]);
            }
            
            echo json_encode(['message' => 'Address deleted successfully']);
            break;
            
        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $stmt = $pdo->prepare('
                SELECT * FROM addresses 
                WHERE user_id = ? 
                ORDER BY is_default DESC, created_at DESC
            ');
            $stmt->execute([$user_id]);
            $addresses = $stmt->fetchAll();
            
            echo json_encode(['addresses' => $addresses]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    error_log('Address API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing your request']);
}
