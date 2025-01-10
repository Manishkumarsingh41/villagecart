<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error | VillageCart</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center text-center">
            <div class="col-md-8 col-lg-6">
                <div class="mb-4">
                    <i class="fas fa-cogs text-danger" style="font-size: 5rem;"></i>
                </div>
                <h1 class="display-4 mb-4">500</h1>
                <h2 class="mb-4">Internal Server Error</h2>
                <p class="text-muted mb-4">Something went wrong on our end. We're working to fix it as soon as possible.</p>
                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                    <a href="<?php echo SITE_URL; ?>/" class="btn btn-success px-4 me-sm-3">Go Home</a>
                    <a href="<?php echo SITE_URL; ?>/contact" class="btn btn-outline-secondary px-4">Contact Support</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
