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
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;
$shops = get_all_shops($status_filter);

// Handle shop status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['shop_id']) && isset($_POST['status'])) {
        $shop_id = intval($_POST['shop_id']);
        $status = sanitize_input($_POST['status']);
        
        if (update_shop_status($shop_id, $status)) {
            $success = "Shop status updated successfully!";
            header("refresh:1;url=shops.php");
        } else {
            $error = "Error updating shop status.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shops - VillageCart Admin</title>
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
                        <a class="nav-link active" href="shops.php">Manage Shops</a>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Shops</h2>
            <div class="btn-group">
                <a href="shops.php" class="btn btn-outline-primary <?php echo !$status_filter ? 'active' : ''; ?>">
                    All
                </a>
                <a href="shops.php?status=pending" 
                   class="btn btn-outline-primary <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                    Pending
                </a>
                <a href="shops.php?status=active" 
                   class="btn btn-outline-primary <?php echo $status_filter === 'active' ? 'active' : ''; ?>">
                    Active
                </a>
                <a href="shops.php?status=inactive" 
                   class="btn btn-outline-primary <?php echo $status_filter === 'inactive' ? 'active' : ''; ?>">
                    Inactive
                </a>
            </div>
        </div>
        
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
                                <th>Shop Name</th>
                                <th>Owner</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shops as $shop): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($shop['shop_name']); ?></td>
                                    <td><?php echo htmlspecialchars($shop['username']); ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($shop['email']); ?></div>
                                        <small><?php echo htmlspecialchars($shop['phone']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $shop['status'] === 'active' ? 'success' : 
                                                ($shop['status'] === 'pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($shop['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($shop['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="viewShop(<?php echo htmlspecialchars(json_encode($shop)); ?>)">
                                            View
                                        </button>
                                        <button class="btn btn-sm btn-<?php 
                                            echo $shop['status'] === 'active' ? 'danger' : 'success'; 
                                        ?>" onclick="updateStatus(<?php echo $shop['id']; ?>, '<?php 
                                            echo $shop['status'] === 'active' ? 'inactive' : 'active'; 
                                        ?>')">
                                            <?php echo $shop['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
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
    
    <!-- View Shop Modal -->
    <div class="modal fade" id="viewShopModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Shop Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Shop Name:</strong>
                        <p id="modal_shop_name"></p>
                    </div>
                    <div class="mb-3">
                        <strong>Description:</strong>
                        <p id="modal_description"></p>
                    </div>
                    <div class="mb-3">
                        <strong>Address:</strong>
                        <p id="modal_address"></p>
                    </div>
                    <div class="mb-3">
                        <strong>Contact:</strong>
                        <p id="modal_contact"></p>
                    </div>
                    <div class="mb-3">
                        <strong>Owner:</strong>
                        <p id="modal_owner"></p>
                    </div>
                    <div class="mb-3">
                        <strong>Status:</strong>
                        <p id="modal_status"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Status Form -->
    <form id="updateStatusForm" method="POST" style="display: none;">
        <input type="hidden" name="shop_id" id="status_shop_id">
        <input type="hidden" name="status" id="status_value">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function viewShop(shop) {
        document.getElementById('modal_shop_name').textContent = shop.shop_name;
        document.getElementById('modal_description').textContent = shop.description;
        document.getElementById('modal_address').textContent = shop.address;
        document.getElementById('modal_contact').textContent = shop.phone;
        document.getElementById('modal_owner').textContent = shop.username;
        document.getElementById('modal_status').textContent = shop.status.charAt(0).toUpperCase() + shop.status.slice(1);
        
        new bootstrap.Modal(document.getElementById('viewShopModal')).show();
    }
    
    function updateStatus(shopId, status) {
        if (confirm('Are you sure you want to ' + (status === 'active' ? 'activate' : 'deactivate') + ' this shop?')) {
            document.getElementById('status_shop_id').value = shopId;
            document.getElementById('status_value').value = status;
            document.getElementById('updateStatusForm').submit();
        }
    }
    </script>
</body>
</html>
