<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect if not logged in or not an admin
if (!is_user_logged_in() || !is_admin()) {
    header("Location: ../pages/login.php");
    exit();
}

$error = '';
$success = '';
$orders = get_all_orders();

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['order_id']) && isset($_POST['status'])) {
        $order_id = intval($_POST['order_id']);
        $status = sanitize_input($_POST['status']);
        
        if (update_order_status($order_id, $status)) {
            $success = "Order status updated successfully!";
            header("refresh:1;url=orders.php");
        } else {
            $error = "Error updating order status.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - VillageCart Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shops.php">Manage Shops</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">Manage Orders</a>
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
        <h2 class="mb-4">Manage Orders</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($order['username']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                    </td>
                                    <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; 
                                        ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                        <div class="small text-muted">
                                            <?php echo ucfirst($order['payment_method']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status'] === 'delivered' ? 'success' : 
                                                ($order['status'] === 'pending' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="viewOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                            View
                                        </button>
                                        <button class="btn btn-sm btn-success" 
                                                onclick="updateStatus(<?php echo $order['id']; ?>)">
                                            Update Status
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p class="mb-1" id="modal_customer"></p>
                            <p class="mb-1" id="modal_email"></p>
                            <p class="mb-1" id="modal_contact"></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Shipping Address</h6>
                            <p id="modal_address"></p>
                        </div>
                    </div>
                    
                    <h6>Order Items</h6>
                    <div id="modal_items"></div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Payment Method:</strong> <span id="modal_payment_method"></span></p>
                            <p><strong>Payment Status:</strong> <span id="modal_payment_status"></span></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <p><strong>Total Amount:</strong> <span id="modal_total"></span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="status_order_id">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function viewOrder(order) {
        document.getElementById('modal_customer').textContent = order.username;
        document.getElementById('modal_email').textContent = order.email;
        document.getElementById('modal_contact').textContent = order.contact_number;
        document.getElementById('modal_address').textContent = order.shipping_address;
        document.getElementById('modal_payment_method').textContent = order.payment_method.toUpperCase();
        document.getElementById('modal_payment_status').textContent = order.payment_status;
        document.getElementById('modal_total').textContent = '₹' + parseFloat(order.total_amount).toFixed(2);
        
        // TODO: Add order items when available
        document.getElementById('modal_items').innerHTML = '<p class="text-muted">Loading order items...</p>';
        
        new bootstrap.Modal(document.getElementById('viewOrderModal')).show();
    }
    
    function updateStatus(orderId) {
        document.getElementById('status_order_id').value = orderId;
        new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
    }
    </script>
</body>
</html>
