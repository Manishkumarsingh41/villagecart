<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$message = '';
$type = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Find user with this token
    $sql = "SELECT id FROM users WHERE verification_token = ? AND verification_token_expires > NOW()";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Update user as verified
            $sql = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $user['id']);
                if ($stmt->execute()) {
                    $type = 'success';
                    $message = 'Your email has been verified! You can now login.';
                } else {
                    $type = 'danger';
                    $message = 'Error verifying email. Please try again.';
                }
            }
        } else {
            $type = 'danger';
            $message = 'Invalid or expired verification token.';
        }
    }
} else {
    $type = 'danger';
    $message = 'No verification token provided.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - VillageCart</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    
    <main class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header bg-gradient text-white text-center py-4" 
                         style="background: linear-gradient(45deg, var(--primary-color), var(--accent-color));">
                        <h2 class="mb-0">Email Verification</h2>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $type; ?> text-center">
                                <?php echo $message; ?>
                                <?php if ($type === 'success'): ?>
                                    <div class="mt-3">
                                        <a href="login.php" class="btn btn-primary">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login Now
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
