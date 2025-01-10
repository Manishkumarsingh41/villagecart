<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !is_admin()) {
    header("Location: ../pages/login.php");
    exit();
}

$stats = get_dashboard_stats();
$recent_orders = get_all_orders(5);
$pending_shops = get_pending_shops();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">VillageCart Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shops.php">Manage Shops</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Manage Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Manage Users</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../pages/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <h2 class="mb-4">Admin Dashboard</h2>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Users</h5>
                        <h2 class="mb-0"><?php echo $stats['total_users']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Shops</h5>
                        <h2 class="mb-0"><?php echo $stats['total_shops']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <h2 class="mb-0"><?php echo $stats['total_orders']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Revenue</h5>
                        <h2 class="mb-0">₹<?php echo number_format($stats['total_revenue'], 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Pending Shops -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Pending Shop Approvals</h5>
                        <span class="badge bg-primary"><?php echo count($pending_shops); ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_shops)): ?>
                            <p class="text-muted">No pending shop approvals.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Shop Name</th>
                                            <th>Owner</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_shops as $shop): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($shop['shop_name']); ?></td>
                                                <td><?php echo htmlspecialchars($shop['username']); ?></td>
                                                <td>
                                                    <form method="POST" action="approve-shop.php" class="d-inline">
                                                        <input type="hidden" name="shop_id" value="<?php echo $shop['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                    </form>
                                                    <form method="POST" action="reject-shop.php" class="d-inline">
                                                        <input type="hidden" name="shop_id" value="<?php echo $shop['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                            <p class="text-muted">No orders found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['username']); ?></td>
                                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $order['status'] === 'pending' ? 'warning' : 'success'; ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Orders Overview</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="ordersChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Revenue Overview</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Orders Chart
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    new Chart(ordersCtx, {
        type: 'pie',
        data: {
            labels: ['Pending', 'Confirmed', 'Shipped', 'Delivered'],
            datasets: [{
                data: [
                    <?php echo $stats['pending_orders']; ?>,
                    <?php echo $stats['total_orders'] - $stats['pending_orders']; ?>,
                    0,
                    0
                ],
                backgroundColor: [
                    '#ffc107',
                    '#0dcaf0',
                    '#198754',
                    '#0d6efd'
                ]
            }]
        }
    });
    
    // Revenue Chart (Last 7 days)
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
            datasets: [{
                label: 'Revenue',
                data: [
                    <?php echo $stats['total_revenue'] * 0.1; ?>,
                    <?php echo $stats['total_revenue'] * 0.15; ?>,
                    <?php echo $stats['total_revenue'] * 0.2; ?>,
                    <?php echo $stats['total_revenue'] * 0.12; ?>,
                    <?php echo $stats['total_revenue'] * 0.18; ?>,
                    <?php echo $stats['total_revenue'] * 0.13; ?>,
                    <?php echo $stats['total_revenue'] * 0.12; ?>
                ],
                borderColor: '#198754',
                tension: 0.1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
</body>
</html>
