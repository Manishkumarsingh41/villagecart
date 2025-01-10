<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

$search_query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Get all categories for filter
$sql = "SELECT * FROM categories ORDER BY name ASC";
$result = $conn->query($sql);
$categories = $result->fetch_all(MYSQLI_ASSOC);

// Search products
$products = [];
if (!empty($search_query)) {
    $sql = "SELECT p.*, s.shop_name, c.name as category_name 
            FROM products p 
            JOIN shops s ON p.shop_id = s.id 
            JOIN categories c ON p.category_id = c.id 
            WHERE p.status = 'active' 
            AND (
                p.name LIKE ? 
                OR p.description LIKE ? 
                OR s.shop_name LIKE ? 
                OR c.name LIKE ?
            )";
    
    if ($category_filter > 0) {
        $sql .= " AND p.category_id = ?";
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    if ($stmt = $conn->prepare($sql)) {
        $search_param = "%{$search_query}%";
        if ($category_filter > 0) {
            $stmt->bind_param("ssssi", $search_param, $search_param, $search_param, $search_param, $category_filter);
        } else {
            $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
        }
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="container mt-4">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Filters</h5>
                        <form action="" method="GET">
                            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Search Results -->
            <div class="col-md-9">
                <h1 class="mb-4">
                    Search Results
                    <?php if (!empty($search_query)): ?>
                        for "<?php echo htmlspecialchars($search_query); ?>"
                    <?php endif; ?>
                </h1>
                
                <?php if (!empty($search_query)): ?>
                    <?php if (!empty($products)): ?>
                        <p class="text-muted mb-4">Found <?php echo count($products); ?> products</p>
                        
                        <div class="row">
                            <?php foreach ($products as $product): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="product-card h-100">
                                        <img src="<?php echo htmlspecialchars($product['image_url'] ?: '../assets/images/products/placeholder.jpg'); ?>" 
                                             class="product-image" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <p class="card-text text-muted">
                                                <small>
                                                    <i class="fas fa-store me-1"></i>
                                                    <?php echo htmlspecialchars($product['shop_name']); ?>
                                                </small>
                                                <br>
                                                <small>
                                                    <i class="fas fa-tag me-1"></i>
                                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                                </small>
                                            </p>
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
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No products found matching your search criteria.
                            <a href="../index.php" class="alert-link">Return to homepage</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Please enter a search term to find products.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
