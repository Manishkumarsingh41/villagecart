<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'vendor/autoload.php';

use RobThree\Auth\TwoFactorAuth;

if (!is_user_logged_in()) {
    header('Location: login.php');
    exit();
}

$tfa = new TwoFactorAuth('VillageCart');
$error = '';
$success = '';
$secret = '';
$qrcode = '';

// Get user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT two_factor_enabled, two_factor_secret FROM users WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user['two_factor_enabled']) {
        $secret = $user['two_factor_secret'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enable'])) {
        // Generate new secret
        $secret = $tfa->createSecret();
        
        // Verify the code
        $code = $_POST['code'];
        if ($tfa->verifyCode($secret, $code)) {
            // Save secret and enable 2FA
            $sql = "UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("si", $secret, $user_id);
                if ($stmt->execute()) {
                    $success = "Two-factor authentication has been enabled!";
                } else {
                    $error = "Error enabling two-factor authentication";
                }
            }
        } else {
            $error = "Invalid verification code";
        }
    } elseif (isset($_POST['disable'])) {
        // Verify current password
        $password = $_POST['password'];
        $sql = "SELECT password FROM users WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if (password_verify($password, $result['password'])) {
                // Disable 2FA
                $sql = "UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("i", $user_id);
                    if ($stmt->execute()) {
                        $success = "Two-factor authentication has been disabled";
                        $secret = '';
                    } else {
                        $error = "Error disabling two-factor authentication";
                    }
                }
            } else {
                $error = "Invalid password";
            }
        }
    }
}

// Generate QR code if secret exists
if ($secret) {
    $qrcode = $tfa->getQRCodeImageAsDataUri(
        $_SESSION['username'], 
        $secret
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Setup - VillageCart</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    
    <main class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header bg-gradient text-white text-center py-4" 
                         style="background: linear-gradient(45deg, var(--primary-color), var(--accent-color));">
                        <h2 class="mb-0">Two-Factor Authentication</h2>
                        <p class="mb-0">Enhance your account security</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if (!$user['two_factor_enabled']): ?>
                            <div class="text-center mb-4">
                                <i class="fas fa-shield-alt fa-3x text-primary"></i>
                                <h3 class="mt-3">Enable Two-Factor Authentication</h3>
                                <p class="text-muted">Protect your account with an additional layer of security</p>
                            </div>
                            
                            <div class="row align-items-center">
                                <div class="col-md-6 text-center">
                                    <?php if ($qrcode): ?>
                                        <img src="<?php echo $qrcode; ?>" alt="QR Code" class="img-fluid mb-3">
                                        <p class="mb-3">Scan this QR code with your authenticator app</p>
                                        <div class="d-flex justify-content-center align-items-center mb-3">
                                            <span class="me-2">Manual entry code:</span>
                                            <code class="bg-light p-2 rounded"><?php echo $secret; ?></code>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <form method="POST">
                                        <div class="mb-4">
                                            <label class="form-label">Verification Code</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                <input type="text" class="form-control" name="code" required 
                                                       placeholder="Enter 6-digit code">
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" name="enable" class="btn btn-primary btn-lg">
                                                <i class="fas fa-shield-alt me-2"></i>Enable 2FA
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center mb-4">
                                <i class="fas fa-check-circle fa-3x text-success"></i>
                                <h3 class="mt-3">Two-Factor Authentication is Enabled</h3>
                                <p class="text-muted">Your account is protected with an additional layer of security</p>
                            </div>
                            
                            <form method="POST" class="mt-4">
                                <div class="mb-4">
                                    <label class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <small class="text-muted">Enter your password to disable 2FA</small>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" name="disable" class="btn btn-danger btn-lg">
                                        <i class="fas fa-shield-alt me-2"></i>Disable 2FA
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h4>How to set up Two-Factor Authentication</h4>
                        <ol>
                            <li>Download an authenticator app:
                                <ul>
                                    <li><a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Google Authenticator (Android)</a></li>
                                    <li><a href="https://apps.apple.com/us/app/google-authenticator/id388497605" target="_blank">Google Authenticator (iOS)</a></li>
                                    <li><a href="https://authy.com/download/" target="_blank">Authy (All platforms)</a></li>
                                </ul>
                            </li>
                            <li>Open your authenticator app and scan the QR code shown above</li>
                            <li>Enter the 6-digit code shown in your authenticator app</li>
                            <li>Click "Enable 2FA" to complete the setup</li>
                        </ol>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Make sure to save your backup codes in a safe place. You'll need them if you lose access to your authenticator app.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
