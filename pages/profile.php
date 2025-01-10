<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect if not logged in
if (!is_user_logged_in()) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}

// Get user's orders
$sql = "SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ? 
        GROUP BY o.id 
        ORDER BY o.created_at DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get user's shop if they are a shop owner
$shop = null;
if (is_shop_owner()) {
    $sql = "SELECT * FROM shops WHERE user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $shop = $stmt->get_result()->fetch_assoc();
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        
        // Verify current password
        if (!empty($current_password)) {
            $sql = "SELECT password FROM users WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if (!password_verify($current_password, $result['password'])) {
                    $error = "Current password is incorrect";
                } else {
                    // Update password if new password is provided
                    if (!empty($new_password)) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET password = ? WHERE id = ?";
                        if ($stmt = $conn->prepare($sql)) {
                            $stmt->bind_param("si", $hashed_password, $user_id);
                            $stmt->execute();
                        }
                    }
                    
                    // Update username and email
                    $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("ssi", $username, $email, $user_id);
                        if ($stmt->execute()) {
                            $success = "Profile updated successfully";
                            // Refresh user data
                            $sql = "SELECT * FROM users WHERE id = ?";
                            if ($stmt = $conn->prepare($sql)) {
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $user = $stmt->get_result()->fetch_assoc();
                            }
                        } else {
                            $error = "Error updating profile";
                        }
                    }
                }
            }
        } else {
            $error = "Please enter your current password to make changes";
        }
    } elseif (isset($_POST['update_profile_image'])) {
        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($filetype, $allowed)) {
                $new_filename = uniqid() . '.' . $filetype;
                $upload_path = '../assets/images/profiles/' . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Update database with new image path
                    $image_path = 'assets/images/profiles/' . $new_filename;
                    $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("si", $image_path, $user_id);
                        if ($stmt->execute()) {
                            $success = "Profile image updated successfully";
                            // Refresh user data
                            $sql = "SELECT * FROM users WHERE id = ?";
                            if ($stmt = $conn->prepare($sql)) {
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $user = $stmt->get_result()->fetch_assoc();
                            }
                        } else {
                            $error = "Error updating profile image";
                        }
                    }
                } else {
                    $error = "Error uploading image";
                }
            } else {
                $error = "Invalid file type. Allowed types: " . implode(', ', $allowed);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="container mt-4">
        <div class="row">
            <!-- Profile Sidebar -->
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="position-relative mb-3">
                            <?php if (!empty($user['profile_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                     class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-5x text-primary"></i>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-primary position-absolute bottom-0 end-0" 
                                    data-bs-toggle="modal" data-bs-target="#profileImageModal">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        <h5 class="card-title"><?php echo htmlspecialchars($user['username']); ?></h5>
                        <p class="card-text text-muted">
                            <?php echo ucfirst($user['role']); ?>
                            <?php if ($user['email_verified']): ?>
                                <span class="badge bg-success ms-1" title="Email Verified">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            <?php endif; ?>
                        </p>
                        <?php if ($user['two_factor_enabled']): ?>
                            <span class="badge bg-info" title="2FA Enabled">
                                <i class="fas fa-shield-alt"></i> 2FA Enabled
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="list-group mb-4">
                    <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="fas fa-user me-2"></i>Profile
                    </a>
                    <a href="#security" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-shield-alt me-2"></i>Security
                    </a>
                    <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-shopping-bag me-2"></i>Orders
                    </a>
                    <?php if (is_shop_owner()): ?>
                        <a href="#shop" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <i class="fas fa-store me-2"></i>My Shop
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Edit Profile</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($error)): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>
                                <?php if (!empty($success)): ?>
                                    <div class="alert alert-success"><?php echo $success; ?></div>
                                <?php endif; ?>
                                
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" name="username" 
                                               value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <div class="input-group">
                                            <input type="email" class="form-control" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            <?php if (!$user['email_verified']): ?>
                                                <button type="button" class="btn btn-warning" id="verifyEmail">
                                                    <i class="fas fa-envelope me-1"></i>Verify
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" name="new_password" 
                                               placeholder="Leave blank to keep current password">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" name="current_password" 
                                               placeholder="Enter current password to save changes" required>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Security Settings</h5>
                            </div>
                            <div class="card-body">
                                <!-- Two-Factor Authentication -->
                                <div class="mb-4">
                                    <h6>Two-Factor Authentication</h6>
                                    <p class="text-muted">Add an extra layer of security to your account</p>
                                    <?php if ($user['two_factor_enabled']): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-shield-alt me-2"></i>Two-factor authentication is enabled
                                        </div>
                                        <a href="../two-factor-setup.php" class="btn btn-danger">
                                            <i class="fas fa-times me-1"></i>Disable 2FA
                                        </a>
                                    <?php else: ?>
                                        <a href="../two-factor-setup.php" class="btn btn-success">
                                            <i class="fas fa-shield-alt me-1"></i>Enable 2FA
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Connected Accounts -->
                                <div class="mb-4">
                                    <h6>Connected Accounts</h6>
                                    <p class="text-muted">Link your accounts for easier login</p>
                                    
                                    <div class="d-grid gap-2">
                                        <?php if ($user['social_provider'] === 'google'): ?>
                                            <button class="btn btn-outline-danger" disabled>
                                                <i class="fab fa-google me-2"></i>Connected with Google
                                            </button>
                                        <?php else: ?>
                                            <a href="../auth/google.php" class="btn btn-outline-danger">
                                                <i class="fab fa-google me-2"></i>Connect with Google
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['social_provider'] === 'facebook'): ?>
                                            <button class="btn btn-outline-primary" disabled>
                                                <i class="fab fa-facebook me-2"></i>Connected with Facebook
                                            </button>
                                        <?php else: ?>
                                            <a href="../auth/facebook.php" class="btn btn-outline-primary">
                                                <i class="fab fa-facebook me-2"></i>Connect with Facebook
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Recent Activity -->
                                <div>
                                    <h6>Recent Activity</h6>
                                    <p class="text-muted">Recent login activity on your account</p>
                                    
                                    <div class="list-group">
                                        <?php
                                        $sql = "SELECT * FROM user_activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
                                        if ($stmt = $conn->prepare($sql)) {
                                            $stmt->bind_param("i", $user_id);
                                            $stmt->execute();
                                            $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                            
                                            foreach ($activities as $activity):
                                        ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['activity_type']); ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($activity['ip_address']); ?>
                                                </small>
                                            </div>
                                        <?php
                                            endforeach;
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Orders Tab -->
                    <div class="tab-pane fade" id="orders">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">My Orders</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($orders)): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Items</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td>#<?php echo $order['id']; ?></td>
                                                        <td><?php echo $order['item_count']; ?> items</td>
                                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo match($order['status']) {
                                                                    'pending' => 'warning',
                                                                    'confirmed' => 'info',
                                                                    'shipped' => 'primary',
                                                                    'delivered' => 'success',
                                                                    'cancelled' => 'danger',
                                                                    default => 'secondary'
                                                                };
                                                            ?>">
                                                                <?php echo ucfirst($order['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('d M Y', strtotime($order['created_at'])); ?></td>
                                                        <td>
                                                            <a href="order.php?id=<?php echo $order['id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                View Details
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center">No orders found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shop Tab -->
                    <?php if (is_shop_owner()): ?>
                        <div class="tab-pane fade" id="shop">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">My Shop</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($shop): ?>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Shop Details</h6>
                                                <p><strong>Name:</strong> <?php echo htmlspecialchars($shop['shop_name']); ?></p>
                                                <p><strong>Status:</strong> 
                                                    <span class="badge bg-<?php 
                                                        echo match($shop['status']) {
                                                            'active' => 'success',
                                                            'pending' => 'warning',
                                                            'inactive' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                    ?>">
                                                        <?php echo ucfirst($shop['status']); ?>
                                                    </span>
                                                </p>
                                                <p><strong>Address:</strong> <?php echo htmlspecialchars($shop['address']); ?></p>
                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($shop['phone']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-grid gap-2">
                                                    <a href="../admin/manage-products.php" class="btn btn-primary">
                                                        <i class="fas fa-box me-2"></i>Manage Products
                                                    </a>
                                                    <a href="../admin/manage-orders.php" class="btn btn-info">
                                                        <i class="fas fa-shopping-bag me-2"></i>Manage Orders
                                                    </a>
                                                    <a href="../admin/shop-settings.php" class="btn btn-secondary">
                                                        <i class="fas fa-cog me-2"></i>Shop Settings
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-center">No shop found. Please contact admin to set up your shop.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Profile Image Upload Modal -->
    <div class="modal fade" id="profileImageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data" id="profileImageForm">
                        <div class="mb-3">
                            <label class="form-label">Choose Image</label>
                            <input type="file" class="form-control" name="profile_image" accept="image/*" required>
                        </div>
                        <div id="imagePreview" class="text-center mb-3" style="display: none;">
                            <img src="" class="img-fluid">
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="update_profile_image" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i>Upload Image
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
    // Profile image preview
    document.querySelector('input[name="profile_image"]').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.querySelector('#imagePreview img');
                preview.src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Email verification
    document.getElementById('verifyEmail')?.addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...';
        
        fetch('../verify-email.php?send=1')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Verification email sent! Please check your inbox.');
                } else {
                    alert('Error sending verification email. Please try again.');
                }
            })
            .catch(error => {
                alert('Error sending verification email. Please try again.');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-envelope me-1"></i>Verify';
            });
    });
    </script>
</body>
</html>
