<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is already logged in
if (is_user_logged_in()) {
    header('Location: ' . SITE_URL);
    exit();
}

$error = '';
$success = '';

// Verify token
$token = isset($_GET['token']) ? sanitize_input($_GET['token']) : '';
if (empty($token)) {
    header('Location: login.php');
    exit();
}

// Check if token exists and is valid
$stmt = $conn->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = 'Invalid or expired reset token. Please request a new password reset.';
} else {
    $user = $result->fetch_assoc();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($password) || empty($confirm_password)) {
            $error = 'Please fill in all fields';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } else {
            // Update password and clear reset token
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user['id']);
            
            if ($stmt->execute()) {
                $success = 'Your password has been successfully reset. You can now login with your new password.';
                // Redirect to login page after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $error = 'Error updating password. Please try again.';
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
    <title>Reset Password - VillageCart</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-key fa-3x text-muted mb-3"></i>
                            <h2>Reset Password</h2>
                            <p class="text-muted">Please enter your new password.</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (empty($success)): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Must be at least 6 characters long</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100">Reset Password</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Password Tips -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Password Tips</h5>
                        <p class="card-text small">For a strong password:</p>
                        <ul class="small mb-0">
                            <li>Use at least 8 characters</li>
                            <li>Include uppercase and lowercase letters</li>
                            <li>Include numbers and special characters</li>
                            <li>Avoid using personal information</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            const password = document.querySelector(button.getAttribute('data-target'));
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });
    </script>
</body>
</html>
