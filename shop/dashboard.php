<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect if not logged in or not a shop owner
if (!is_user_logged_in() || !is_shop_owner()) {
    header("Location: ../pages/login.php");
    exit();
}

// Get shop information
$user_id = $_SESSION['user_id'];
$shop = null;
$products = [];

$sql = "SELECT * FROM shops WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $shop = $result->fetch_assoc();
        
        // Get shop products
        $sql = "SELECT * FROM products WHERE shop_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $shop['id']);
            $stmt->execute();
            $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
    $stmt->close();
}

// Get recent orders
$orders = [];
if ($shop) {
    $sql = "SELECT o.*, oi.*, p.name as product_name 
            FROM orders o 
            JOIN order_items oi ON o.id = oi.order_id 
            JOIN products p ON oi.product_id = p.id 
            WHERE p.shop_id = ? 
            ORDER BY o.created_at DESC 
            LIMIT 5";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $shop['id']);
        $stmt->execute();
        $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Dashboard - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <?php if (!$shop): ?>
            <div class="alert alert-info">
                You haven't registered your shop yet. 
                <a href="../pages/shop-registration.php" class="alert-link">Register now</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($shop['shop_name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($shop['description']); ?></p>
                            <p class="mb-1"><strong>Status:</strong> <?php echo ucfirst($shop['status']); ?></p>
                            <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars($shop['address']); ?></p>
                            <p class="mb-0"><strong>Phone:</strong> <?php echo htmlspecialchars($shop['phone']); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Quick Stats</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="border rounded p-3 text-center">
                                        <h6>Total Products</h6>
                                        <h3><?php echo count($products); ?></h3>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 text-center">
                                        <h6>Recent Orders</h6>
                                        <h3><?php echo count($orders); ?></h3>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="border rounded p-3 text-center">
                                        <h6>Status</h6>
                                        <h3><?php echo ucfirst($shop['status']); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Recent Products</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach(array_slice($products, 0, 5) as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                            <td><?php echo $product['stock']; ?></td>
                                            <td><?php echo ucfirst($product['status']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="products.php" class="btn btn-primary btn-sm">Manage Products</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Recent Orders</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Product</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['order_id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                            <td>₹<?php echo number_format($order['price'], 2); ?></td>
                                            <td><?php echo ucfirst($order['status']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="orders.php" class="btn btn-primary btn-sm">View All Orders</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
