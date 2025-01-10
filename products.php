<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

try {
    $pdo = get_db_connection();
    
    // Get categories for filter
    $stmt = $pdo->query("SELECT * FROM categories WHERE active = 1 ORDER BY name");
    $categories = $stmt->fetchAll();
    
    // Get price ranges for filter
    $stmt = $pdo->query("
        SELECT 
            MIN(price) as min_price,
            MAX(price) as max_price
        FROM products 
        WHERE active = 1
    ");
    $price_range = $stmt->fetch();
    
    // Build query based on filters
    $where_clauses = ["p.active = 1"];
    $params = [];
    
    if (!empty($_GET['category'])) {
        $where_clauses[] = "p.category_id = ?";
        $params[] = $_GET['category'];
    }
    
    if (!empty($_GET['min_price'])) {
        $where_clauses[] = "p.price >= ?";
        $params[] = $_GET['min_price'];
    }
    
    if (!empty($_GET['max_price'])) {
        $where_clauses[] = "p.price <= ?";
        $params[] = $_GET['max_price'];
    }
    
    if (!empty($_GET['search'])) {
        $where_clauses[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $search_term = "%{$_GET['search']}%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Pagination
    $page = max(1, $_GET['page'] ?? 1);
    $per_page = 12;
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $count_sql = "
        SELECT COUNT(*) as count 
        FROM products p 
        WHERE " . implode(" AND ", $where_clauses);
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_products = $stmt->fetch()['count'];
    $total_pages = ceil($total_products / $per_page);
    
    // Get products
    $sql = "
        SELECT 
            p.*, 
            c.name as category_name,
            s.name as shop_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        JOIN shops s ON p.shop_id = s.id
        WHERE " . implode(" AND ", $where_clauses) . "
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $pdo->prepare($sql);
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Products page error: ' . $e->getMessage());
    $categories = [];
    $products = [];
    $price_range = ['min_price' => 0, 'max_price' => 1000];
    $total_pages = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | VillageCart</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nouislider@14.6.3/distribute/nouislider.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Filters</h5>
                        
                        <form id="filter-form" method="GET">
                            <!-- Search -->
                            <div class="mb-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            </div>
                            
                            <!-- Categories -->
                            <div class="mb-4">
                                <label class="form-label">Categories</label>
                                <?php foreach ($categories as $category): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" 
                                               name="category" value="<?php echo $category['id']; ?>" 
                                               id="category-<?php echo $category['id']; ?>"
                                               <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="category-<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Price Range -->
                            <div class="mb-4">
                                <label class="form-label">Price Range</label>
                                <div id="price-slider"></div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <input type="number" class="form-control form-control-sm" 
                                               id="min-price" name="min_price" 
                                               value="<?php echo htmlspecialchars($_GET['min_price'] ?? $price_range['min_price']); ?>">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control form-control-sm" 
                                               id="max-price" name="max_price" 
                                               value="<?php echo htmlspecialchars($_GET['max_price'] ?? $price_range['max_price']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">Apply Filters</button>
                            <?php if (!empty($_GET)): ?>
                                <a href="<?php echo SITE_URL; ?>/products" class="btn btn-outline-secondary w-100 mt-2">
                                    Clear Filters
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-lg-9">
                <?php if (!empty($products)): ?>
                    <div class="row">
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-4 mb-4">
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
                                            <a href="<?php echo SITE_URL; ?>/product/<?php echo $product['id']; ?>" 
                                               class="btn btn-success">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php
                                            $params = $_GET;
                                            $params['page'] = $i;
                                            echo http_build_query($params);
                                        ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open text-muted mb-4" style="font-size: 4rem;"></i>
                        <h3>No products found</h3>
                        <p class="text-muted">Try adjusting your filters or search terms.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/nouislider@14.6.3/distribute/nouislider.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize price slider
            const priceSlider = document.getElementById('price-slider');
            const minPrice = <?php echo $price_range['min_price']; ?>;
            const maxPrice = <?php echo $price_range['max_price']; ?>;
            const currentMinPrice = <?php echo $_GET['min_price'] ?? $price_range['min_price']; ?>;
            const currentMaxPrice = <?php echo $_GET['max_price'] ?? $price_range['max_price']; ?>;
            
            noUiSlider.create(priceSlider, {
                start: [currentMinPrice, currentMaxPrice],
                connect: true,
                range: {
                    'min': minPrice,
                    'max': maxPrice
                },
                step: 1
            });
            
            const minPriceInput = document.getElementById('min-price');
            const maxPriceInput = document.getElementById('max-price');
            
            priceSlider.noUiSlider.on('update', function(values, handle) {
                const value = Math.round(values[handle]);
                if (handle === 0) {
                    minPriceInput.value = value;
                } else {
                    maxPriceInput.value = value;
                }
            });
            
            minPriceInput.addEventListener('change', function() {
                priceSlider.noUiSlider.set([this.value, null]);
            });
            
            maxPriceInput.addEventListener('change', function() {
                priceSlider.noUiSlider.set([null, this.value]);
            });
        });
    </script>
</body>
</html>
