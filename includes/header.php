<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VillageCart - Your Local Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>/">
                <i class="fas fa-shopping-cart text-success me-2"></i>
                <span class="fw-bold">VillageCart</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/shops">Shops</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/products">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/contact">Contact</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo SITE_URL; ?>/cart" class="btn btn-outline-success me-2 position-relative">
                            <i class="fas fa-shopping-cart"></i>
                            <?php $cart_count = get_cart_count(); ?>
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (is_admin()): ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin">Admin Dashboard</a></li>
                                <?php elseif (is_seller()): ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/seller">Seller Dashboard</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile">My Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/orders">My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout">Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login" class="btn btn-outline-success me-2">Login</a>
                        <a href="<?php echo SITE_URL; ?>/register" class="btn btn-success">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- Display any flash messages -->
<?php display_message(); ?>