<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Redirect to login if not logged in
if (!is_user_logged_in()) {
    $_SESSION['redirect_after_login'] = '/checkout';
    header('Location: ' . SITE_URL . '/login');
    exit;
}

// Get cart items
try {
    $pdo = get_db_connection();
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare('
        SELECT 
            p.id,
            p.name,
            p.price,
            p.stock_quantity,
            c.quantity,
            (p.price * c.quantity) as subtotal
        FROM cart c
        JOIN products p ON p.id = c.product_id
        WHERE c.user_id = ?
    ');
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();
    
    if (empty($cart_items)) {
        header('Location: ' . SITE_URL . '/cart');
        exit;
    }
    
    $cart_total = array_sum(array_column($cart_items, 'subtotal'));
    
    // Get user's addresses
    $stmt = $pdo->prepare('
        SELECT * FROM addresses 
        WHERE user_id = ? 
        ORDER BY is_default DESC, created_at DESC
    ');
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Checkout error: ' . $e->getMessage());
    set_message('An error occurred while loading the checkout page', 'danger');
    header('Location: ' . SITE_URL . '/cart');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | VillageCart</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <h1 class="mb-4">Checkout</h1>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Shipping Address -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Shipping Address</h5>
                        
                        <?php if (!empty($addresses)): ?>
                            <div class="mb-3">
                                <select class="form-select" id="address-select">
                                    <?php foreach ($addresses as $address): ?>
                                        <option value="<?php echo $address['id']; ?>">
                                            <?php echo htmlspecialchars($address['address_line1']); ?>
                                            <?php if ($address['address_line2']): ?>
                                                , <?php echo htmlspecialchars($address['address_line2']); ?>
                                            <?php endif; ?>
                                            , <?php echo htmlspecialchars($address['city']); ?>
                                            , <?php echo htmlspecialchars($address['state']); ?>
                                            <?php echo htmlspecialchars($address['postal_code']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#newAddressModal">
                                Add New Address
                            </button>
                        <?php else: ?>
                            <form id="address-form" class="needs-validation" novalidate>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="address1" class="form-label">Address Line 1</label>
                                        <input type="text" class="form-control" id="address1" name="address1" required>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="address2" class="form-label">Address Line 2 (Optional)</label>
                                        <input type="text" class="form-control" id="address2" name="address2">
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" required>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="state" class="form-label">State</label>
                                        <input type="text" class="form-control" id="state" name="state" required>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label for="postal_code" class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                                    </div>
                                    
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="save-address" name="save_address" checked>
                                            <label class="form-check-label" for="save-address">Save this address for future use</label>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Payment Method</h5>
                        
                        <div class="form-check mb-2">
                            <input type="radio" class="form-check-input" name="payment_method" id="cash" value="cash" checked>
                            <label class="form-check-label" for="cash">Cash on Delivery</label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input type="radio" class="form-check-input" name="payment_method" id="card" value="card">
                            <label class="form-check-label" for="card">Credit/Debit Card</label>
                        </div>
                        
                        <div id="card-details" class="mt-3" style="display: none;">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="card-number" class="form-label">Card Number</label>
                                    <input type="text" class="form-control" id="card-number" placeholder="1234 5678 9012 3456">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="expiry" class="form-label">Expiry Date</label>
                                    <input type="text" class="form-control" id="expiry" placeholder="MM/YY">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cvv" placeholder="123">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>
                                    <?php echo htmlspecialchars($item['name']); ?>
                                    <small class="text-muted">× <?php echo $item['quantity']; ?></small>
                                </span>
                                <span>$<?php echo number_format($item['subtotal'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>$<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong>$<?php echo number_format($cart_total, 2); ?></strong>
                        </div>
                        
                        <button type="button" class="btn btn-success w-100" id="place-order">
                            Place Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Address Modal -->
    <div class="modal fade" id="newAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="new-address-form" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="new-address1" class="form-label">Address Line 1</label>
                            <input type="text" class="form-control" id="new-address1" name="address1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new-address2" class="form-label">Address Line 2 (Optional)</label>
                            <input type="text" class="form-control" id="new-address2" name="address2">
                        </div>
                        
                        <div class="mb-3">
                            <label for="new-city" class="form-label">City</label>
                            <input type="text" class="form-control" id="new-city" name="city" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new-state" class="form-label">State</label>
                            <input type="text" class="form-control" id="new-state" name="state" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new-postal-code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="new-postal-code" name="postal_code" required>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="make-default" name="make_default">
                            <label class="form-check-label" for="make-default">Make this my default address</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="save-address">Save Address</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle card details
            const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
            const cardDetails = document.getElementById('card-details');
            
            paymentMethods.forEach(method => {
                method.addEventListener('change', function() {
                    cardDetails.style.display = this.value === 'card' ? 'block' : 'none';
                });
            });
            
            // Handle new address form submission
            const saveAddressBtn = document.getElementById('save-address');
            if (saveAddressBtn) {
                saveAddressBtn.addEventListener('click', function() {
                    const form = document.getElementById('new-address-form');
                    
                    if (!form.checkValidity()) {
                        form.classList.add('was-validated');
                        return;
                    }
                    
                    const formData = new FormData(form);
                    
                    fetch('<?php echo SITE_URL; ?>/api/address.php?action=add', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                showAlert(data.error, 'danger');
                            } else {
                                location.reload();
                            }
                        })
                        .catch(error => {
                            console.error('Error adding address:', error);
                            showAlert('An error occurred while adding the address', 'danger');
                        });
                });
            }
            
            // Handle order placement
            const placeOrderBtn = document.getElementById('place-order');
            placeOrderBtn.addEventListener('click', function() {
                const addressId = document.getElementById('address-select')?.value;
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
                
                if (paymentMethod === 'card') {
                    // Validate card details
                    const cardNumber = document.getElementById('card-number').value;
                    const expiry = document.getElementById('expiry').value;
                    const cvv = document.getElementById('cvv').value;
                    
                    if (!cardNumber || !expiry || !cvv) {
                        showAlert('Please fill in all card details', 'danger');
                        return;
                    }
                }
                
                const orderData = {
                    address_id: addressId,
                    payment_method: paymentMethod
                };
                
                if (paymentMethod === 'card') {
                    // In a real application, you would tokenize card details here
                    orderData.card = {
                        number: document.getElementById('card-number').value,
                        expiry: document.getElementById('expiry').value,
                        cvv: document.getElementById('cvv').value
                    };
                }
                
                fetch('<?php echo SITE_URL; ?>/api/order.php?action=create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            showAlert(data.error, 'danger');
                        } else {
                            window.location.href = `<?php echo SITE_URL; ?>/order-confirmation?id=${data.order_id}`;
                        }
                    })
                    .catch(error => {
                        console.error('Error placing order:', error);
                        showAlert('An error occurred while placing your order', 'danger');
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
