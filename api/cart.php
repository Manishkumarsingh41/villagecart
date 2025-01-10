define('DB_HOST', 'localhost');
define('DB_NAME', 'villagecart');
define('DB_USER', 'root');
define('DB_PASS', '');
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in to manage your cart']);
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
            
            $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?? 1;
            
            if (!$product_id || $quantity < 1) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid product ID or quantity']);
                exit;
            }
            
            // Check if product exists and is in stock
            $stmt = $pdo->prepare('SELECT id, stock_quantity, price FROM products WHERE id = ? AND active = 1');
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
                exit;
            }
            
            if ($product['stock_quantity'] < $quantity) {
                http_response_code(400);
                echo json_encode(['error' => 'Not enough stock available']);
                exit;
            }
            
            // Check if product is already in cart
            $stmt = $pdo->prepare('SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?');
            $stmt->execute([$user_id, $product_id]);
            $cart_item = $stmt->fetch();
            
            if ($cart_item) {
                // Update quantity
                $new_quantity = $cart_item['quantity'] + $quantity;
                if ($new_quantity > $product['stock_quantity']) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Cannot add more items than available in stock']);
                    exit;
                }
                
                $stmt = $pdo->prepare('UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?');
                $stmt->execute([$new_quantity, $user_id, $product_id]);
            } else {
                // Add new item to cart
                $stmt = $pdo->prepare('INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)');
                $stmt->execute([$user_id, $product_id, $quantity]);
            }
            
            echo json_encode([
                'message' => 'Product added to cart',
                'cart_count' => get_cart_count()
            ]);
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
            
            if (!$product_id || $quantity < 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid product ID or quantity']);
                exit;
            }
            
            if ($quantity === 0) {
                // Remove item from cart
                $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
                $stmt->execute([$user_id, $product_id]);
            } else {
                // Check stock quantity
                $stmt = $pdo->prepare('SELECT stock_quantity FROM products WHERE id = ? AND active = 1');
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                if (!$product || $product['stock_quantity'] < $quantity) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Not enough stock available']);
                    exit;
                }
                
                // Update quantity
                $stmt = $pdo->prepare('UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?');
                $stmt->execute([$quantity, $user_id, $product_id]);
            }
            
            // Get updated cart total
            $stmt = $pdo->prepare('
                SELECT SUM(c.quantity * p.price) as total
                FROM cart c
                JOIN products p ON p.id = c.product_id
                WHERE c.user_id = ?
            ');
            $stmt->execute([$user_id]);
            $total = $stmt->fetch()['total'] ?? 0;
            
            echo json_encode([
                'message' => 'Cart updated',
                'cart_count' => get_cart_count(),
                'total' => number_format($total, 2)
            ]);
            break;
            
        case 'remove':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            
            if (!$product_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid product ID']);
                exit;
            }
            
            $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
            $stmt->execute([$user_id, $product_id]);
            
            echo json_encode([
                'message' => 'Product removed from cart',
                'cart_count' => get_cart_count()
            ]);
            break;
            
        case 'get':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $stmt = $pdo->prepare('
                SELECT 
                    p.id,
                    p.name,
                    p.price,
                    p.stock_quantity,
                    c.quantity,
                    (p.price * c.quantity) as subtotal
                FROM cart c
                JOIN products p ON p.id = c.product_id
                WHERE c.user_id = ?
            ');
            $stmt->execute([$user_id]);
            $items = $stmt->fetchAll();
            
            $total = array_sum(array_column($items, 'subtotal'));
            
            echo json_encode([
                'items' => $items,
                'total' => number_format($total, 2),
                'cart_count' => count($items)
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    error_log('Cart API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing your request']);
}
