<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_user_logged_in()) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$cart_items = get_cart_items($_SESSION['user_id']);
$cart_total = get_cart_total($_SESSION['user_id']);

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = sanitize_input($_POST['shipping_address']);
    $contact_number = sanitize_input($_POST['contact_number']);
    $payment_method = sanitize_input($_POST['payment_method']);
    
    if (empty($shipping_address) || empty($contact_number)) {
        $error = "All fields are required";
    } else {
        if ($payment_method === 'cod') {
            // Create order for Cash on Delivery
            $order_id = create_order($_SESSION['user_id'], $shipping_address, $contact_number, 'cod');
            if ($order_id) {
                $_SESSION['order_success'] = true;
                header("Location: order-confirmation.php?id=" . $order_id);
                exit();
            } else {
                $error = "Error creating order. Please try again.";
            }
        } else {
            // For online payment (Razorpay integration example)
            require_once '../vendor/razorpay/razorpay-php/Razorpay.php';
            
            $api_key = 'YOUR_RAZORPAY_KEY_ID';
            $api_secret = 'YOUR_RAZORPAY_KEY_SECRET';
            
            $api = new Razorpay\Api\Api($api_key, $api_secret);
            
            $order = $api->order->create([
                'amount' => $cart_total * 100, // Amount in paise
                'currency' => 'INR',
                'payment_capture' => 1
            ]);
            
            $_SESSION['checkout_details'] = [
                'shipping_address' => $shipping_address,
                'contact_number' => $contact_number,
                'payment_method' => 'online',
                'razorpay_order_id' => $order['id']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Checkout</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Shipping Information</h5>
                        <form method="POST" id="checkout-form">
                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Shipping Address</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" 
                                          rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="cod" value="cod" checked>
                                    <label class="form-check-label" for="cod">
                                        Cash on Delivery
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="online" value="online">
                                    <label class="form-check-label" for="online">
                                        Online Payment (Credit/Debit Card, UPI)
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Place Order</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?></span>
                                <span>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>₹<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong>₹<?php echo number_format($cart_total, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    // Razorpay integration
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
        
        if (paymentMethod === 'online') {
            e.preventDefault();
            
            const options = {
                key: 'YOUR_RAZORPAY_KEY_ID',
                amount: <?php echo $cart_total * 100; ?>,
                currency: 'INR',
                name: 'VillageCart',
                description: 'Purchase from VillageCart',
                order_id: '<?php echo isset($order['id']) ? $order['id'] : ''; ?>',
                handler: function(response) {
                    // Add payment ID to form
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'razorpay_payment_id';
                    input.value = response.razorpay_payment_id;
                    document.getElementById('checkout-form').appendChild(input);
                    
                    // Submit form
                    document.getElementById('checkout-form').submit();
                },
                prefill: {
                    name: '<?php echo htmlspecialchars($_SESSION['username']); ?>',
                    contact: document.getElementById('contact_number').value
                }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
        }
    });
    </script>
</body>
</html>
