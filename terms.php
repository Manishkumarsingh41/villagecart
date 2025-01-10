<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service | VillageCart</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="mb-4">Terms of Service</h1>
                <p class="text-muted mb-5">Last updated: <?php echo date('F d, Y'); ?></p>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">1. Acceptance of Terms</h2>
                        <p>By accessing and using VillageCart, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">2. User Accounts</h2>
                        <ul>
                            <li>You must be at least 18 years old to use our services</li>
                            <li>You are responsible for maintaining the security of your account</li>
                            <li>You must provide accurate and complete information</li>
                            <li>You may not use another person's account</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">3. Marketplace Rules</h2>
                        <ul>
                            <li>All products must comply with applicable laws</li>
                            <li>Sellers must accurately describe their products</li>
                            <li>Buyers must make timely payments</li>
                            <li>We reserve the right to remove any listing</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">4. Payment Terms</h2>
                        <ul>
                            <li>All prices are in USD unless otherwise stated</li>
                            <li>Payment must be made at the time of purchase</li>
                            <li>We use secure payment processors</li>
                            <li>Sellers receive payment after order confirmation</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">5. Shipping and Returns</h2>
                        <ul>
                            <li>Sellers are responsible for shipping items</li>
                            <li>Buyers must inspect items upon receipt</li>
                            <li>Return policies vary by seller</li>
                            <li>Disputes must be reported within 14 days</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">6. Intellectual Property</h2>
                        <p>All content on VillageCart is protected by copyright and other intellectual property rights. Users may not copy, reproduce, or distribute content without permission.</p>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">7. Limitation of Liability</h2>
                        <p>VillageCart is not liable for:</p>
                        <ul>
                            <li>Product quality or accuracy of listings</li>
                            <li>User conduct or disputes</li>
                            <li>Technical issues or service interruptions</li>
                            <li>Indirect or consequential damages</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">8. Changes to Terms</h2>
                        <p>We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting to the website. Your continued use of VillageCart constitutes acceptance of the modified terms.</p>
                    </div>
                </div>
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-3">9. Contact Information</h2>
                        <p>For questions about these Terms of Service, please contact us at:</p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-envelope me-2 text-success"></i> legal@villagecart.com</li>
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
