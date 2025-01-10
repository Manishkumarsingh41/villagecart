<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in to place orders']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    $pdo = get_db_connection();
    
    switch ($action) {
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            // Get JSON data
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (!isset($data['address_id']) || !isset($data['payment_method'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }
            
            // Verify address belongs to user
            $stmt = $pdo->prepare('SELECT id FROM addresses WHERE id = ? AND user_id = ?');
            $stmt->execute([$data['address_id'], $user_id]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid address']);
                exit;
            }
            
            // Get cart items
            $stmt = $pdo->prepare('
                SELECT c.*, p.price, p.stock_quantity
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
            ');
            $stmt->execute([$user_id]);
            $cart_items = $stmt->fetchAll();
            
            if (empty($cart_items)) {
                http_response_code(400);
                echo json_encode(['error' => 'Cart is empty']);
                exit;
            }
            
            // Calculate total and verify stock
            $total_amount = 0;
            foreach ($cart_items as $item) {
                if ($item['quantity'] > $item['stock_quantity']) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Some items are out of stock']);
                    exit;
                }
                $total_amount += $item['price'] * $item['quantity'];
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Create order
                $stmt = $pdo->prepare('
                    INSERT INTO orders (
                        user_id,
                        address_id,
                        total_amount,
                        payment_method,
                        payment_status,
                        status,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                ');
                $stmt->execute([
                    $user_id,
                    $data['address_id'],
                    $total_amount,
                    $data['payment_method'],
                    $data['payment_method'] === 'cash' ? 'pending' : 'paid',
                    'pending'
                ]);
                $order_id = $pdo->lastInsertId();
                
                // Add order items
                $stmt = $pdo->prepare('
                    INSERT INTO order_items (
                        order_id,
                        product_id,
                        quantity,
                        price,
                        created_at
                    ) VALUES (?, ?, ?, ?, NOW())
                ');
                
                foreach ($cart_items as $item) {
                    $stmt->execute([
                        $order_id,
                        $item['product_id'],
                        $item['quantity'],
                        $item['price']
                    ]);
                    
                    // Update stock
                    $update_stock = $pdo->prepare('
                        UPDATE products 
                        SET stock_quantity = stock_quantity - ? 
                        WHERE id = ?
                    ');
                    $update_stock->execute([$item['quantity'], $item['product_id']]);
                }
                
                // Add initial status
                $stmt = $pdo->prepare('
                    INSERT INTO order_status_history (
                        order_id,
                        status,
                        comment,
                        created_at
                    ) VALUES (?, ?, ?, NOW())
                ');
                $stmt->execute([
                    $order_id,
                    'pending',
                    'Order placed successfully'
                ]);
                
                // Clear cart
                $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ?');
                $stmt->execute([$user_id]);
                
                // Commit transaction
                $pdo->commit();
                
                echo json_encode([
                    'message' => 'Order placed successfully',
                    'order_id' => $order_id
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            
            if (!$order_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid order ID']);
                exit;
            }
            
            // Get order details
            $stmt = $pdo->prepare('
                SELECT o.*, a.*, u.email, u.full_name
                FROM orders o
                JOIN addresses a ON o.address_id = a.id
                JOIN users u ON o.user_id = u.id
                WHERE o.id = ? AND o.user_id = ?
            ');
            $stmt->execute([$order_id, $user_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'Order not found']);
                exit;
            }
            
            // Get order items
            $stmt = $pdo->prepare('
                SELECT oi.*, p.name, p.image_url, s.name as shop_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN shops s ON p.shop_id = s.id
                WHERE oi.order_id = ?
            ');
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll();
            
            // Get order status history
            $stmt = $pdo->prepare('
                SELECT * FROM order_status_history 
                WHERE order_id = ? 
                ORDER BY created_at DESC
            ');
            $stmt->execute([$order_id]);
            $status_history = $stmt->fetchAll();
            
            echo json_encode([
                'order' => $order,
                'items' => $order_items,
                'status_history' => $status_history
            ]);
            break;
            
        case 'list':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $page = max(1, $_GET['page'] ?? 1);
            $per_page = 10;
            $offset = ($page - 1) * $per_page;
            
            // Get total count
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM orders WHERE user_id = ?');
            $stmt->execute([$user_id]);
            $total_orders = $stmt->fetch()['count'];
            $total_pages = ceil($total_orders / $per_page);
            
            // Get orders
            $stmt = $pdo->prepare('
                SELECT o.*, COUNT(oi.id) as item_count
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = ?
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?
            ');
            $stmt->execute([$user_id, $per_page, $offset]);
            $orders = $stmt->fetchAll();
            
            echo json_encode([
                'orders' => $orders,
                'total_pages' => $total_pages,
                'current_page' => $page
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    error_log('Order API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing your request']);
}
