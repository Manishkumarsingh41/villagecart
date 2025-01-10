<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id) {
    header('Location: ' . SITE_URL . '/404');
    exit;
}

try {
    $pdo = get_db_connection();
    
    // Get product details
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            s.name as shop_name,
            s.description as shop_description,
            c.name as category_name
        FROM products p
        JOIN shops s ON p.shop_id = s.id
        JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.active = 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: ' . SITE_URL . '/404');
        exit;
    }
    
    // Get related products
    $stmt = $pdo->prepare("
        SELECT p.*, s.name as shop_name
        FROM products p
        JOIN shops s ON p.shop_id = s.id
        WHERE p.category_id = ? 
        AND p.id != ? 
        AND p.active = 1
        ORDER BY p.created_at DESC
        LIMIT 4
    ");
    $stmt->execute([$product['category_id'], $product_id]);
    $related_products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Product page error: ' . $e->getMessage());
    header('Location: ' . SITE_URL . '/500');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | VillageCart</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>" class="text-decoration-none">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo SITE_URL; ?>/products" class="text-decoration-none">Products</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($product['name']); ?>
                </li>
            </ol>
        </nav>
        
        <div class="row">
            <!-- Product Images -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <img src="<?php echo !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : SITE_URL . '/assets/images/product-placeholder.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="card-img-top">
                </div>
            </div>
            
            <!-- Product Details -->
            <div class="col-lg-6">
                <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="mb-3">
                    <span class="h3 text-success">$<?php echo number_format($product['price'], 2); ?></span>
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <span class="badge bg-success ms-2">In Stock</span>
                    <?php else: ?>
                        <span class="badge bg-danger ms-2">Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-4">
                    <h5>Description</h5>
                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                
                <div class="mb-4">
                    <h5>Shop Information</h5>
                    <p>
                        <strong>Sold by:</strong> 
                        <a href="<?php echo SITE_URL; ?>/shop/<?php echo $product['shop_id']; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($product['shop_name']); ?>
                        </a>
                    </p>
                    <p class="text-muted"><?php echo htmlspecialchars($product['shop_description']); ?></p>
                </div>
                
                <?php if ($product['stock_quantity'] > 0): ?>
                    <div class="mb-4">
                        <div class="input-group mb-3" style="max-width: 200px;">
                            <button class="btn btn-outline-secondary" type="button" id="decrease-quantity">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="form-control text-center" id="quantity" value="1" min="1" 
                                   max="<?php echo $product['stock_quantity']; ?>">
                            <button class="btn btn-outline-secondary" type="button" id="increase-quantity">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-success btn-lg w-100" id="add-to-cart">
                            <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                        </button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Product Details</h5>
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width: 150px;">Category:</td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Stock:</td>
                                <td><?php echo $product['stock_quantity']; ?> units</td>
                            </tr>
                            <tr>
                                <td class="text-muted">SKU:</td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <section class="mt-5">
                <h3 class="mb-4">Related Products</h3>
                <div class="row">
                    <?php foreach ($related_products as $related): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card product-card h-100 shadow-sm">
                                <img src="<?php echo !empty($related['image_url']) ? htmlspecialchars($related['image_url']) : SITE_URL . '/assets/images/product-placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>" 
                                     class="card-img-top">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h5>
                                    <p class="card-text text-muted small mb-2">
                                        by <?php echo htmlspecialchars($related['shop_name']); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h5 mb-0">$<?php echo number_format($related['price'], 2); ?></span>
                                        <a href="<?php echo SITE_URL; ?>/product/<?php echo $related['id']; ?>" 
                                           class="btn btn-success">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInput = document.getElementById('quantity');
            const decreaseBtn = document.getElementById('decrease-quantity');
            const increaseBtn = document.getElementById('increase-quantity');
            const addToCartBtn = document.getElementById('add-to-cart');
            const maxStock = <?php echo $product['stock_quantity']; ?>;
            
            function updateQuantity(value) {
                value = Math.max(1, Math.min(maxStock, value));
                quantityInput.value = value;
            }
            
            decreaseBtn?.addEventListener('click', function() {
                updateQuantity(parseInt(quantityInput.value) - 1);
            });
            
            increaseBtn?.addEventListener('click', function() {
                updateQuantity(parseInt(quantityInput.value) + 1);
            });
            
            quantityInput?.addEventListener('change', function() {
                updateQuantity(parseInt(this.value));
            });
            
            addToCartBtn?.addEventListener('click', function() {
                const quantity = parseInt(quantityInput.value);
                
                fetch('<?php echo SITE_URL; ?>/api/cart.php?action=add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: <?php echo $product_id; ?>,
                        quantity: quantity
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            showAlert(data.error, 'danger');
                        } else {
                            showAlert('Product added to cart!', 'success');
                            // Update cart count in header if exists
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = data.cart_count;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error adding to cart:', error);
                        showAlert('An error occurred while adding the product to cart', 'danger');
                    });
            });
            
            function showAlert(message, type = 'success') {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
                alertDiv.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alertDiv);
                
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            }
        });
    </script>
</body>
</html>
