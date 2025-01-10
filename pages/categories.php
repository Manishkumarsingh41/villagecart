<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Get all categories with product count
$sql = "SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
        GROUP BY c.id 
        ORDER BY c.name ASC";
$result = $conn->query($sql);
$categories = $result->fetch_all(MYSQLI_ASSOC);

// Get featured products for each category
$featured_products = [];
foreach ($categories as $category) {
    $sql = "SELECT p.*, s.shop_name 
            FROM products p 
            JOIN shops s ON p.shop_id = s.id 
            WHERE p.category_id = ? AND p.status = 'active' 
            ORDER BY p.created_at DESC 
            LIMIT 4";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $category['id']);
        $stmt->execute();
        $featured_products[$category['id']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="container mt-4">
        <h1 class="mb-4">Shop by Category</h1>
        
        <?php foreach ($categories as $category): ?>
            <section class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h3 mb-0"><?php echo htmlspecialchars($category['name']); ?></h2>
                    <a href="category.php?id=<?php echo $category['id']; ?>" class="btn btn-outline-primary">
                        View All <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
                
                <?php if (!empty($category['description'])): ?>
                    <p class="text-muted mb-4"><?php echo htmlspecialchars($category['description']); ?></p>
                <?php endif; ?>
                
                <div class="row">
                    <?php if (!empty($featured_products[$category['id']])): ?>
                        <?php foreach ($featured_products[$category['id']] as $product): ?>
                            <div class="col-md-3 mb-4">
                                <div class="product-card h-100">
                                    <img src="<?php echo htmlspecialchars($product['image_url'] ?: '../assets/images/products/placeholder.jpg'); ?>" 
                                         class="product-image" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($product['shop_name']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <?php if ($product['discount_price']): ?>
                                                    <span class="product-price">₹<?php echo number_format($product['discount_price'], 2); ?></span>
                                                    <small class="text-muted text-decoration-line-through">
                                                        ₹<?php echo number_format($product['price'], 2); ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="product-price">₹<?php echo number_format($product['price'], 2); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent border-0">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-primary w-100">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <p class="text-center">No products available in this category.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
