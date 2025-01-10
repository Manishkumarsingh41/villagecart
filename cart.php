<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect to login if not logged in
if (!is_user_logged_in()) {
    $_SESSION['redirect_after_login'] = '/cart';
    header('Location: ' . SITE_URL . '/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | VillageCart</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <h1 class="mb-4">Shopping Cart</h1>
        
        <div id="cart-items">
            <!-- Cart items will be loaded here -->
            <div class="text-center">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
        
        <template id="cart-template">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <img src="" alt="" class="img-fluid rounded product-image">
                        </div>
                        <div class="col-md-4">
                            <h5 class="product-name"></h5>
                            <p class="text-muted mb-0">Price: $<span class="product-price"></span></p>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <button class="btn btn-outline-secondary decrease-quantity" type="button">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control text-center quantity-input" min="1">
                                <button class="btn btn-outline-secondary increase-quantity" type="button">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <p class="h5 mb-0">$<span class="subtotal"></span></p>
                        </div>
                        <div class="col-md-1 text-end">
                            <button class="btn btn-link text-danger remove-item">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        
        <template id="empty-cart-template">
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart text-muted mb-4" style="font-size: 4rem;"></i>
                <h3>Your cart is empty</h3>
                <p class="text-muted">Start shopping to add items to your cart!</p>
                <a href="<?php echo SITE_URL; ?>/products" class="btn btn-success mt-3">
                    Browse Products
                </a>
            </div>
        </template>
        
        <div id="cart-summary" class="card shadow-sm mt-4" style="display: none;">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-0">Total: $<span id="cart-total">0.00</span></h4>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="<?php echo SITE_URL; ?>/products" class="btn btn-outline-secondary me-2">
                            Continue Shopping
                        </a>
                        <a href="<?php echo SITE_URL; ?>/checkout" class="btn btn-success">
                            Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadCart();
            
            function loadCart() {
                fetch('<?php echo SITE_URL; ?>/api/cart.php?action=get')
                    .then(response => response.json())
                    .then(data => {
                        const cartItems = document.getElementById('cart-items');
                        const cartSummary = document.getElementById('cart-summary');
                        const cartTotal = document.getElementById('cart-total');
                        
                        cartItems.innerHTML = '';
                        
                        if (data.items && data.items.length > 0) {
                            data.items.forEach(item => {
                                const template = document.getElementById('cart-template');
                                const clone = template.content.cloneNode(true);
                                
                                clone.querySelector('.product-name').textContent = item.name;
                                clone.querySelector('.product-price').textContent = parseFloat(item.price).toFixed(2);
                                clone.querySelector('.quantity-input').value = item.quantity;
                                clone.querySelector('.subtotal').textContent = parseFloat(item.subtotal).toFixed(2);
                                
                                // Add data attributes for item management
                                const card = clone.querySelector('.card');
                                card.dataset.productId = item.id;
                                card.dataset.stockQuantity = item.stock_quantity;
                                
                                // Add event listeners
                                const quantityInput = clone.querySelector('.quantity-input');
                                const decreaseBtn = clone.querySelector('.decrease-quantity');
                                const increaseBtn = clone.querySelector('.increase-quantity');
                                const removeBtn = clone.querySelector('.remove-item');
                                
                                quantityInput.addEventListener('change', () => updateQuantity(item.id, quantityInput));
                                decreaseBtn.addEventListener('click', () => {
                                    if (quantityInput.value > 1) {
                                        quantityInput.value--;
                                        updateQuantity(item.id, quantityInput);
                                    }
                                });
                                increaseBtn.addEventListener('click', () => {
                                    if (quantityInput.value < item.stock_quantity) {
                                        quantityInput.value++;
                                        updateQuantity(item.id, quantityInput);
                                    }
                                });
                                removeBtn.addEventListener('click', () => removeItem(item.id));
                                
                                cartItems.appendChild(clone);
                            });
                            
                            cartTotal.textContent = data.total;
                            cartSummary.style.display = 'block';
                        } else {
                            const template = document.getElementById('empty-cart-template');
                            cartItems.appendChild(template.content.cloneNode(true));
                            cartSummary.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading cart:', error);
                        showAlert('An error occurred while loading your cart', 'danger');
                    });
            }
            
            function updateQuantity(productId, input) {
                const quantity = parseInt(input.value);
                const card = input.closest('.card');
                const stockQuantity = parseInt(card.dataset.stockQuantity);
                
                if (quantity < 1 || quantity > stockQuantity) {
                    showAlert('Invalid quantity', 'danger');
                    input.value = Math.max(1, Math.min(quantity, stockQuantity));
                    return;
                }
                
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('quantity', quantity);
                
                fetch('<?php echo SITE_URL; ?>/api/cart.php?action=update', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            showAlert(data.error, 'danger');
                            loadCart();
                        } else {
                            const subtotal = card.querySelector('.subtotal');
                            const price = parseFloat(card.querySelector('.product-price').textContent);
                            subtotal.textContent = (price * quantity).toFixed(2);
                            
                            document.getElementById('cart-total').textContent = data.total;
                            updateCartCount(data.cart_count);
                        }
                    })
                    .catch(error => {
                        console.error('Error updating cart:', error);
                        showAlert('An error occurred while updating your cart', 'danger');
                    });
            }
            
            function removeItem(productId) {
                if (!confirm('Are you sure you want to remove this item from your cart?')) {
                    return;
                }
                
                const formData = new FormData();
                formData.append('product_id', productId);
                
                fetch('<?php echo SITE_URL; ?>/api/cart.php?action=remove', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            showAlert(data.error, 'danger');
                        } else {
                            loadCart();
                            updateCartCount(data.cart_count);
                            showAlert('Item removed from cart', 'success');
                        }
                    })
                    .catch(error => {
                        console.error('Error removing item:', error);
                        showAlert('An error occurred while removing the item', 'danger');
                    });
            }
            
            function updateCartCount(count) {
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = count;
                    cartCount.style.display = count > 0 ? 'block' : 'none';
                }
            }
            
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
