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
$role_filter = isset($_GET['role']) ? $_GET['role'] : null;
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Get users based on search or filter
$users = $search_query ? search_users($search_query) : get_all_users($role_filter);

// Handle user updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        
        // Update status
        if (isset($_POST['status'])) {
            $status = sanitize_input($_POST['status']);
            if (update_user_status($user_id, $status)) {
                log_user_activity($user_id, 'status_update', "Status updated to: $status");
                $success = "User status updated successfully!";
            } else {
                $error = "Error updating user status.";
            }
        }
        
        // Update role
        if (isset($_POST['role'])) {
            $role = sanitize_input($_POST['role']);
            if (update_user_role($user_id, $role)) {
                log_user_activity($user_id, 'role_update', "Role updated to: $role");
                $success = "User role updated successfully!";
            } else {
                $error = "Error updating user role.";
            }
        }
        
        // Send verification email
        if (isset($_POST['send_verification'])) {
            $user = get_user_details($user_id);
            if ($user && send_verification_email($user_id, $user['email'])) {
                log_user_activity($user_id, 'verification_sent', "Verification email sent");
                $success = "Verification email sent successfully!";
            } else {
                $error = "Error sending verification email.";
            }
        }
        
        if ($success) {
            header("refresh:1;url=users.php");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - VillageCart Admin</title>
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
                        <a class="nav-link" href="orders.php">Manage Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">Manage Users</a>
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
            <h2>Manage Users</h2>
            <div>
                <a href="export-users.php" class="btn btn-success">
                    <i class="fas fa-download"></i> Export Users
                </a>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" 
                                   placeholder="Search users..." value="<?php echo htmlspecialchars($search_query); ?>">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if ($search_query): ?>
                                <a href="users.php" class="btn btn-outline-secondary ms-2">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="btn-group float-end">
                            <a href="users.php" class="btn btn-outline-primary <?php echo !$role_filter ? 'active' : ''; ?>">
                                All Users
                            </a>
                            <a href="users.php?role=customer" 
                               class="btn btn-outline-primary <?php echo $role_filter === 'customer' ? 'active' : ''; ?>">
                                Customers
                            </a>
                            <a href="users.php?role=shop_owner" 
                               class="btn btn-outline-primary <?php echo $role_filter === 'shop_owner' ? 'active' : ''; ?>">
                                Shop Owners
                            </a>
                            <a href="users.php?role=admin" 
                               class="btn btn-outline-primary <?php echo $role_filter === 'admin' ? 'active' : ''; ?>">
                                Admins
                            </a>
                        </div>
                    </div>
                </div>
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
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $user['role'] === 'admin' ? 'danger' : 
                                                ($user['role'] === 'shop_owner' ? 'success' : 'primary'); 
                                        ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $user['status'] === 'active' ? 'success' : 'danger'; 
                                        ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="viewUser(<?php echo $user['id']; ?>)">
                                            View
                                        </button>
                                        <?php if ($user['role'] !== 'admin'): ?>
                                            <button class="btn btn-sm btn-<?php 
                                                echo $user['status'] === 'active' ? 'danger' : 'success'; 
                                            ?>" onclick="updateStatus(<?php echo $user['id']; ?>, '<?php 
                                                echo $user['status'] === 'active' ? 'inactive' : 'active'; 
                                            ?>')">
                                                <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="updateRole(<?php echo $user['id']; ?>)">
                                                Change Role
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="viewUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" id="userTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="details-tab" data-bs-toggle="tab" href="#details">
                                Basic Details
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="activity-tab" data-bs-toggle="tab" href="#activity">
                                Activity Log
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="orders-tab" data-bs-toggle="tab" href="#orders">
                                Orders
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="details">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Basic Information</h6>
                                    <p class="mb-1"><strong>Username:</strong> <span id="modal_username"></span></p>
                                    <p class="mb-1"><strong>Email:</strong> <span id="modal_email"></span></p>
                                    <p class="mb-1"><strong>Role:</strong> <span id="modal_role"></span></p>
                                    <p class="mb-1"><strong>Status:</strong> <span id="modal_status"></span></p>
                                    <p class="mb-1"><strong>Verified:</strong> <span id="modal_verified"></span></p>
                                    <p class="mb-1"><strong>Registered:</strong> <span id="modal_registered"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Activity Information</h6>
                                    <p class="mb-1"><strong>Total Orders:</strong> <span id="modal_orders"></span></p>
                                    <p class="mb-1"><strong>Total Spent:</strong> <span id="modal_spent"></span></p>
                                    <p class="mb-1" id="modal_shop_container" style="display: none;">
                                        <strong>Shop Name:</strong> <span id="modal_shop"></span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary" onclick="sendVerificationEmail()">
                                    Send Verification Email
                                </button>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="activity">
                            <div id="activity_log">
                                Loading activity log...
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="orders">
                            <div id="user_orders">
                                Loading orders...
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <form id="updateStatusForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="status_user_id">
        <input type="hidden" name="status" id="status_value">
    </form>
    
    <div class="modal fade" id="updateRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update User Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="role_user_id">
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="customer">Customer</option>
                                <option value="shop_owner">Shop Owner</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentUserId = null;
    
    async function viewUser(userId) {
        currentUserId = userId;
        try {
            const response = await fetch(`get-user-details.php?id=${userId}`);
            const user = await response.json();
            
            document.getElementById('modal_username').textContent = user.username;
            document.getElementById('modal_email').textContent = user.email;
            document.getElementById('modal_role').textContent = user.role.replace('_', ' ').toUpperCase();
            document.getElementById('modal_status').textContent = user.status.toUpperCase();
            document.getElementById('modal_verified').textContent = user.is_verified ? 'Yes' : 'No';
            document.getElementById('modal_registered').textContent = new Date(user.created_at).toLocaleDateString();
            document.getElementById('modal_orders').textContent = user.total_orders;
            document.getElementById('modal_spent').textContent = '₹' + parseFloat(user.total_spent || 0).toFixed(2);
            
            const shopContainer = document.getElementById('modal_shop_container');
            if (user.shop_name) {
                document.getElementById('modal_shop').textContent = user.shop_name;
                shopContainer.style.display = 'block';
            } else {
                shopContainer.style.display = 'none';
            }
            
            new bootstrap.Modal(document.getElementById('viewUserModal')).show();
            
            document.getElementById('activity-tab').addEventListener('click', loadActivityLog);
            document.getElementById('orders-tab').addEventListener('click', loadUserOrders);
        } catch (error) {
            console.error('Error fetching user details:', error);
            alert('Error loading user details. Please try again.');
        }
    }
    
    async function loadActivityLog() {
        if (!currentUserId) return;
        
        try {
            const response = await fetch(`get-user-activity.php?id=${currentUserId}`);
            const activities = await response.json();
            
            const activityLog = document.getElementById('activity_log');
            if (activities.length === 0) {
                activityLog.innerHTML = '<p class="text-muted">No activity recorded.</p>';
                return;
            }
            
            let html = '<div class="list-group">';
            activities.forEach(activity => {
                html += `
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${activity.activity_type}</h6>
                            <small>${new Date(activity.created_at).toLocaleString()}</small>
                        </div>
                        <p class="mb-1">${activity.description}</p>
                    </div>
                `;
            });
            html += '</div>';
            
            activityLog.innerHTML = html;
        } catch (error) {
            console.error('Error loading activity log:', error);
            document.getElementById('activity_log').innerHTML = 
                '<div class="alert alert-danger">Error loading activity log</div>';
        }
    }
    
    async function loadUserOrders() {
        if (!currentUserId) return;
        
        try {
            const response = await fetch(`get-user-orders.php?id=${currentUserId}`);
            const orders = await response.json();
            
            const ordersDiv = document.getElementById('user_orders');
            if (orders.length === 0) {
                ordersDiv.innerHTML = '<p class="text-muted">No orders found.</p>';
                return;
            }
            
            let html = '<div class="table-responsive"><table class="table">';
            html += `
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
            `;
            
            orders.forEach(order => {
                html += `
                    <tr>
                        <td>#${order.id}</td>
                        <td>${new Date(order.created_at).toLocaleDateString()}</td>
                        <td>₹${parseFloat(order.total_amount).toFixed(2)}</td>
                        <td>
                            <span class="badge bg-${
                                order.status === 'delivered' ? 'success' : 
                                (order.status === 'pending' ? 'warning' : 'info')
                            }">
                                ${order.status.toUpperCase()}
                            </span>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            ordersDiv.innerHTML = html;
        } catch (error) {
            console.error('Error loading orders:', error);
            document.getElementById('user_orders').innerHTML = 
                '<div class="alert alert-danger">Error loading orders</div>';
        }
    }
    
    async function sendVerificationEmail() {
        if (!currentUserId) return;
        
        try {
            const response = await fetch('send-verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_id=${currentUserId}&send_verification=1`
            });
            
            const result = await response.json();
            if (result.success) {
                alert('Verification email sent successfully!');
            } else {
                alert('Error sending verification email: ' + result.error);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error sending verification email. Please try again.');
        }
    }
    
    function updateStatus(userId, status) {
        if (confirm('Are you sure you want to ' + (status === 'active' ? 'activate' : 'deactivate') + ' this user?')) {
            document.getElementById('status_user_id').value = userId;
            document.getElementById('status_value').value = status;
            document.getElementById('updateStatusForm').submit();
        }
    }
    
    function updateRole(userId) {
        document.getElementById('role_user_id').value = userId;
        new bootstrap.Modal(document.getElementById('updateRoleModal')).show();
    }
    </script>
</body>
</html>
