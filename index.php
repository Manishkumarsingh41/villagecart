<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

try {
    $pdo = get_db_connection();
    
    // Get featured shops
    $stmt = $pdo->query("
        SELECT id, name, description, image_url 
        FROM shops 
        WHERE active = 1 
        ORDER BY created_at DESC 
        LIMIT 6
    ");
    $featured_shops = $stmt->fetchAll();
    
    // Get featured products
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.description, p.price, p.image_url, s.name as shop_name 
        FROM products p 
        JOIN shops s ON p.shop_id = s.id 
        WHERE p.active = 1 AND p.featured = 1 
        ORDER BY p.created_at DESC 
        LIMIT 8
    ");
    $featured_products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Index page error: ' . $e->getMessage());
    $featured_shops = [];
    $featured_products = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VillageCart - Shop Local, Connect Global</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero-section bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 mb-4">Support Local Businesses</h1>
                    <p class="lead mb-4">Discover unique products from local sellers and artisans in your community.</p>
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="<?php echo SITE_URL; ?>/products" class="btn btn-success btn-lg px-4 me-md-2">
                            Shop Now
                        </a>
                        <a href="<?php echo SITE_URL; ?>/seller/register" class="btn btn-outline-success btn-lg px-4">
                            Become a Seller
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="<?php echo SITE_URL; ?>/assets/images/hero-image.jpg" alt="VillageCart Hero" class="img-fluid rounded-3">
                </div>
            </div>
        </div>
    </section>
    
    <!-- Featured Shops Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Featured Shops</h2>
            <div class="row">
                <?php if (!empty($featured_shops)): ?>
                    <?php foreach ($featured_shops as $shop): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card shop-card h-100 shadow-sm">
                                <img src="<?php echo !empty($shop['image_url']) ? htmlspecialchars($shop['image_url']) : SITE_URL . '/assets/images/shop-placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($shop['name']); ?>" 
                                     class="card-img-top">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($shop['name']); ?></h5>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($shop['description']); ?></p>
                                    <a href="<?php echo SITE_URL; ?>/shop/<?php echo $shop['id']; ?>" class="btn btn-outline-success">
                                        Visit Shop
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">No featured shops available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Featured Products Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Featured Products</h2>
            <div class="row">
                <?php if (!empty($featured_products)): ?>
                    <?php foreach ($featured_products as $product): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card product-card h-100 shadow-sm">
                                <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : SITE_URL . '/assets/images/product-placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="card-img-top">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="card-text text-muted small mb-2">
                                        by <?php echo htmlspecialchars($product['shop_name']); ?>
                                    </p>
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h5 mb-0">$<?php echo number_format($product['price'], 2); ?></span>
                                        <a href="<?php echo SITE_URL; ?>/product/<?php echo $product['id']; ?>" class="btn btn-success">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">No featured products available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Why Choose Us Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose VillageCart?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="fas fa-handshake text-success mb-3" style="font-size: 2.5rem;"></i>
                        <h4>Support Local Communities</h4>
                        <p class="text-muted">Every purchase directly supports local artisans and small businesses in your community.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="fas fa-box text-success mb-3" style="font-size: 2.5rem;"></i>
                        <h4>Unique Products</h4>
                        <p class="text-muted">Discover handcrafted items and fresh local produce you won't find anywhere else.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="fas fa-shield-alt text-success mb-3" style="font-size: 2.5rem;"></i>
                        <h4>Secure Shopping</h4>
                        <p class="text-muted">Shop with confidence knowing your transactions are safe and secure.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>