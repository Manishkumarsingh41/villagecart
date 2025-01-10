<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get product details with shop information
$sql = "SELECT p.*, s.shop_name, s.address as shop_address, c.name as category_name 
        FROM products p 
        JOIN shops s ON p.shop_id = s.id 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ? AND p.status = 'active'";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['name']) : 'Product Not Found'; ?> - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Add PWA manifest -->
    <link rel="manifest" href="../manifest.json">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="container mt-4">
        <?php if ($product): ?>
            <div class="row">
                <!-- Product Image -->
                <div class="col-md-6 mb-4">
                    <div class="product-image-container">
                        <img src="<?php echo htmlspecialchars($product['image_url'] ?: '../assets/images/products/placeholder.jpg'); ?>" 
                             class="img-fluid rounded" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                </div>
                
                <!-- Product Details -->
                <div class="col-md-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                            <li class="breadcrumb-item">
                                <a href="category.php?id=<?php echo $product['category_id']; ?>">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </li>
                        </ol>
                    </nav>

                    <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="mb-4">
                        <?php if ($product['discount_price']): ?>
                            <h2 class="text-primary mb-2">
                                ₹<?php echo number_format($product['discount_price'], 2); ?>
                                <small class="text-muted text-decoration-line-through">
                                    ₹<?php echo number_format($product['price'], 2); ?>
                                </small>
                                <span class="badge bg-danger">
                                    <?php 
                                    $discount = (($product['price'] - $product['discount_price']) / $product['price']) * 100;
                                    echo round($discount) . '% OFF';
                                    ?>
                                </span>
                            </h2>
                        <?php else: ?>
                            <h2 class="text-primary mb-2">₹<?php echo number_format($product['price'], 2); ?></h2>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>

                    <div class="mb-4">
                        <h5>Stock Status</h5>
                        <?php if ($product['stock'] > 0): ?>
                            <span class="badge bg-success">In Stock (<?php echo $product['stock']; ?> available)</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($product['stock'] > 0): ?>
                        <div class="mb-4">
                            <div class="d-flex align-items-center">
                                <div class="input-group me-3" style="width: 150px;">
                                    <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(-1)">-</button>
                                    <input type="number" class="form-control text-center" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                    <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(1)">+</button>
                                </div>
                                <button class="btn btn-primary flex-grow-1" onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Shop Information -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title">Sold by</h5>
                            <h6 class="mb-2"><?php echo htmlspecialchars($product['shop_name']); ?></h6>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                <?php echo htmlspecialchars($product['shop_address']); ?>
                            </p>
                            <a href="shop.php?id=<?php echo $product['shop_id']; ?>" class="btn btn-outline-primary">
                                Visit Shop
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                Product not found. <a href="../index.php" class="alert-link">Return to homepage</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateQuantity(change) {
        const input = document.getElementById('quantity');
        const newValue = parseInt(input.value) + change;
        if (newValue >= 1 && newValue <= <?php echo $product['stock']; ?>) {
            input.value = newValue;
        }
    }

    async function addToCart(productId) {
        const quantity = parseInt(document.getElementById('quantity').value);
        try {
            const response = await fetch('../api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    product_id: productId,
                    quantity: quantity
                })
            });
            
            const result = await response.json();
            if (result.success) {
                alert('Product added to cart!');
                // Update cart count in header if exists
                const cartCount = document.getElementById('cart-count');
                if (cartCount) {
                    cartCount.textContent = result.cart_count;
                }
            } else {
                alert(result.error || 'Error adding product to cart');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error adding product to cart');
        }
    }

    // Register Service Worker for PWA
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('../sw.js')
                .then(registration => {
                    console.log('ServiceWorker registration successful');
                })
                .catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
        });
    }
    </script>
</body>
</html>
