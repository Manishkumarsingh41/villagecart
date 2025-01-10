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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'No account found with this email address';
        } else {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token in database
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
            $stmt->bind_param("ssi", $token, $expiry, $user['id']);
            
            if ($stmt->execute()) {
                // Send reset email
                $reset_link = SITE_URL . '/reset-password.php?token=' . $token;
                
                // TODO: Implement proper email sending
                // For now, just show the reset link
                $success = 'Password reset instructions have been sent to your email address.<br>
                           <small class="text-muted">(For demo purposes, here is your reset link: 
                           <a href="' . $reset_link . '">' . $reset_link . '</a>)</small>';
            } else {
                $error = 'Error generating reset token. Please try again.';
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
    <title>Forgot Password - VillageCart</title>
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
                            <i class="fas fa-lock fa-3x text-muted mb-3"></i>
                            <h2>Forgot Password?</h2>
                            <p class="text-muted">Enter your email address and we'll send you instructions to reset your password.</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" required
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 mb-3">Send Reset Link</button>
                            
                            <div class="text-center">
                                <a href="login.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Help Box -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Need Help?</h5>
                        <p class="card-text small">If you're having trouble accessing your account, you can:</p>
                        <ul class="small mb-0">
                            <li>Check your spam folder for the reset email</li>
                            <li>Make sure you're using the email address you registered with</li>
                            <li><a href="contact.php" class="text-decoration-none">Contact our support team</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
