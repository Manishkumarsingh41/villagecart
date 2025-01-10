<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | VillageCart</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="mb-4">Privacy Policy</h1>
                <p class="text-muted mb-5">Last updated: <?php echo date('F d, Y'); ?></p>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">1. Information We Collect</h2>
                        <p>We collect information that you provide directly to us, including:</p>
                        <ul>
                            <li>Name and contact information</li>
                            <li>Account credentials</li>
                            <li>Payment information</li>
                            <li>Shopping preferences</li>
                            <li>Communication history</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">2. How We Use Your Information</h2>
                        <p>We use the information we collect to:</p>
                        <ul>
                            <li>Process your orders and payments</li>
                            <li>Communicate with you about your orders</li>
                            <li>Send you marketing communications (with your consent)</li>
                            <li>Improve our services</li>
                            <li>Prevent fraud and maintain security</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">3. Information Sharing</h2>
                        <p>We may share your information with:</p>
                        <ul>
                            <li>Sellers on our platform (to fulfill orders)</li>
                            <li>Payment processors</li>
                            <li>Service providers</li>
                            <li>Law enforcement (when required by law)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">4. Your Rights</h2>
                        <p>You have the right to:</p>
                        <ul>
                            <li>Access your personal information</li>
                            <li>Correct inaccurate information</li>
                            <li>Request deletion of your information</li>
                            <li>Opt-out of marketing communications</li>
                            <li>Object to processing of your information</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">5. Security</h2>
                        <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">6. Contact Us</h2>
                        <p>If you have any questions about this Privacy Policy, please contact us at:</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-envelope me-2 text-success"></i> privacy@villagecart.com</li>
                            <li><i class="fas fa-phone me-2 text-success"></i> +1 (555) 123-4567</li>
                            <li><i class="fas fa-map-marker-alt me-2 text-success"></i> 123 Village Street, Silicon Valley, CA 94025</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
