<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

$shop_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get shop details
$sql = "SELECT * FROM shops WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $shop = $stmt->get_result()->fetch_assoc();
}

// Get shop categories
$sql = "SELECT DISTINCT c.* FROM categories c 
        JOIN products p ON c.id = p.category_id 
        WHERE p.shop_id = ? AND p.status = 'active'";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get shop products
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.shop_id = ? AND p.status = 'active'";
if ($category_filter > 0) {
    $sql .= " AND p.category_id = ?";
}
$sql .= " ORDER BY p.created_at DESC";

if ($stmt = $conn->prepare($sql)) {
    if ($category_filter > 0) {
        $stmt->bind_param("ii", $shop_id, $category_filter);
    } else {
        $stmt->bind_param("i", $shop_id);
    }
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $shop ? htmlspecialchars($shop['shop_name']) : 'Shop Not Found'; ?> - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="container mt-4">
        <?php if ($shop): ?>
            <!-- Shop Header -->
            <div class="shop-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-3"><?php echo htmlspecialchars($shop['shop_name']); ?></h1>
                        <p class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?php echo htmlspecialchars($shop['address']); ?>
                        </p>
                        <p class="text-muted">
                            <i class="fas fa-phone me-2"></i>
                            <?php echo htmlspecialchars($shop['phone']); ?>
                        </p>
                        <?php if (!empty($shop['description'])): ?>
                            <p class="mt-3"><?php echo nl2br(htmlspecialchars($shop['description'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-<?php 
                            echo match($shop['status']) {
                                'active' => 'success',
                                'pending' => 'warning',
                                'inactive' => 'danger',
                                default => 'secondary'
                            };
                        ?> mb-2">
                            <?php echo ucfirst($shop['status']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Categories Filter -->
            <?php if (!empty($categories)): ?>
                <div class="mb-4">
                    <h5 class="mb-3">Categories</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="?id=<?php echo $shop_id; ?>" 
                           class="btn <?php echo !$category_filter ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            All
                        </a>
                        <?php foreach ($categories as $category): ?>
                            <a href="?id=<?php echo $shop_id; ?>&category=<?php echo $category['id']; ?>" 
                               class="btn <?php echo $category_filter == $category['id'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Products Grid -->
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
                                    <p class="card-text text-muted"><?php echo htmlspecialchars($product['category_name']); ?></p>
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
                                        <span class="badge bg-<?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                        </span>
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
                        <p class="text-center">No products found.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                Shop not found. <a href="../index.php" class="alert-link">Return to homepage</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
