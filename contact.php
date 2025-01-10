<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // In a real application, you would send an email here
        // For now, we'll just simulate success
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | VillageCart</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="text-center mb-4">Contact Us</h1>
                <p class="text-center text-muted mb-5">Have a question or feedback? We'd love to hear from you.</p>
                
                <?php if ($success): ?>
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle me-2"></i>
                        Thank you for your message! We'll get back to you soon.
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="row mt-5">
                    <div class="col-md-4 text-center mb-4 mb-md-0">
                        <i class="fas fa-map-marker-alt text-success mb-3" style="font-size: 2rem;"></i>
                        <h5>Address</h5>
                        <p class="text-muted">123 Village Street<br>Silicon Valley, CA 94025</p>
                    </div>
                    
                    <div class="col-md-4 text-center mb-4 mb-md-0">
                        <i class="fas fa-phone text-success mb-3" style="font-size: 2rem;"></i>
                        <h5>Phone</h5>
                        <p class="text-muted">+1 (555) 123-4567</p>
                    </div>
                    
                    <div class="col-md-4 text-center">
                        <i class="fas fa-envelope text-success mb-3" style="font-size: 2rem;"></i>
                        <h5>Email</h5>
                        <p class="text-muted">support@villagecart.com</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
