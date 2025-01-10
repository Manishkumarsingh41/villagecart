<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_user_logged_in()) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !isset($_SESSION['order_success'])) {
    header("Location: ../index.php");
    exit();
}

$order_id = intval($_GET['id']);
$order = null;
$order_items = [];

// Get order details
$sql = "SELECT o.*, u.username 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        
        // Get order items
        $sql = "SELECT oi.*, p.name, p.image_url, s.shop_name 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                JOIN shops s ON p.shop_id = s.id 
                WHERE oi.order_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// Clear the order success session
unset($_SESSION['order_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <?php if ($order): ?>
            <div class="text-center mb-4">
                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                <h2 class="mt-3">Thank You for Your Order!</h2>
                <p class="text-muted">Order #<?php echo $order_id; ?> has been placed successfully.</p>
            </div>
            
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Order Details</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Order Date:</strong></p>
                                    <p class="text-muted">
                                        <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Payment Method:</strong></p>
                                    <p class="text-muted">
                                        <?php echo ucfirst($order['payment_method']); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Shipping Address:</strong></p>
                                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Contact Number:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($order['contact_number']); ?></p>
                                </div>
                            </div>
                            
                            <h6 class="mt-4 mb-3">Order Items</h6>
                            <?php foreach ($order_items as $item): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div style="width: 60px; height: 60px;">
                                        <?php if ($item['image_url']): ?>
                                            <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="img-fluid rounded">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white p-2 text-center rounded">
                                                No Image
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ms-3 flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">
                                            Sold by: <?php echo htmlspecialchars($item['shop_name']); ?>
                                        </small>
                                    </div>
                                    <div class="text-end" style="min-width: 120px;">
                                        <p class="mb-0">₹<?php echo number_format($item['price'], 2); ?></p>
                                        <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <hr>
                            
                            <div class="text-end">
                                <p class="mb-1">Subtotal: ₹<?php echo number_format($order['total_amount'], 2); ?></p>
                                <p class="mb-1">Shipping: Free</p>
                                <h5 class="mb-0">Total: ₹<?php echo number_format($order['total_amount'], 2); ?></h5>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="../index.php" class="btn btn-primary">Continue Shopping</a>
                        <a href="order-tracking.php?id=<?php echo $order_id; ?>" class="btn btn-outline-primary">
                            Track Order
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                Order not found. <a href="../index.php" class="alert-link">Return to homepage</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
