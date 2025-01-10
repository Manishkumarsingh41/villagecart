<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

$category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get category details
$sql = "SELECT * FROM categories WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $category = $stmt->get_result()->fetch_assoc();
}

// Get category products
$sql = "SELECT p.*, s.shop_name 
        FROM products p 
        JOIN shops s ON p.shop_id = s.id 
        WHERE p.category_id = ? AND p.status = 'active' 
        ORDER BY p.created_at DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category ? htmlspecialchars($category['name']) : 'Category Not Found'; ?> - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="container mt-4">
        <?php if ($category): ?>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="categories.php">Categories</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </li>
                </ol>
            </nav>

            <div class="mb-4">
                <h1><?php echo htmlspecialchars($category['name']); ?></h1>
                <?php if (!empty($category['description'])): ?>
                    <p class="text-muted"><?php echo htmlspecialchars($category['description']); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="row">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
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
        <?php else: ?>
            <div class="alert alert-danger">
                Category not found. <a href="../index.php" class="alert-link">Return to homepage</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
