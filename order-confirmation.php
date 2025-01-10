<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Ensure user is logged in
if (!is_user_logged_in()) {
    header('Location: ' . SITE_URL . '/login');
    exit;
}

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$order_id) {
    header('Location: ' . SITE_URL . '/404');
    exit;
}

try {
    $pdo = get_db_connection();
    
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, a.*, u.email, u.full_name
        FROM orders o
        JOIN addresses a ON o.address_id = a.id
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('Location: ' . SITE_URL . '/404');
        exit;
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image_url, s.name as shop_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN shops s ON p.shop_id = s.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Order confirmation error: ' . $e->getMessage());
    header('Location: ' . SITE_URL . '/500');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | VillageCart</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="text-center mb-5">
            <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
            <h1 class="mt-3">Thank You for Your Order!</h1>
            <p class="lead text-muted">
                Order #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?> has been placed successfully.
            </p>
        </div>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Order Summary -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Shop</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo !empty($item['image_url']) ? htmlspecialchars($item['image_url']) : SITE_URL . '/assets/images/product-placeholder.jpg'; ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                         class="me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <div>
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['shop_name']); ?></td>
                                            <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                                            <td class="text-end">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Order Details -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Shipping Address</h5>
                                <address class="mb-0">
                                    <?php echo htmlspecialchars($order['full_name']); ?><br>
                                    <?php echo htmlspecialchars($order['address_line1']); ?><br>
                                    <?php if ($order['address_line2']): ?>
                                        <?php echo htmlspecialchars($order['address_line2']); ?><br>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($order['city']); ?>, 
                                    <?php echo htmlspecialchars($order['state']); ?> 
                                    <?php echo htmlspecialchars($order['postal_code']); ?>
                                </address>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Order Details</h5>
                                <ul class="list-unstyled mb-0">
                                    <li>
                                        <strong>Order ID:</strong> 
                                        #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?>
                                    </li>
                                    <li>
                                        <strong>Order Date:</strong> 
                                        <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                                    </li>
                                    <li>
                                        <strong>Payment Method:</strong> 
                                        <?php echo ucfirst($order['payment_method']); ?>
                                    </li>
                                    <li>
                                        <strong>Payment Status:</strong> 
                                        <span class="badge bg-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </li>
                                    <li>
                                        <strong>Order Status:</strong> 
                                        <span class="badge bg-info">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="<?php echo SITE_URL; ?>/orders" class="btn btn-success">
                        View All Orders
                    </a>
                    <a href="<?php echo SITE_URL; ?>/products" class="btn btn-outline-success ms-2">
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
