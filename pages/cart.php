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

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        switch ($_POST['action']) {
            case 'update':
                $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
                if (update_cart_quantity($_SESSION['user_id'], $product_id, $quantity)) {
                    $success = "Cart updated successfully!";
                    header("refresh:1;url=cart.php");
                } else {
                    $error = "Error updating cart.";
                }
                break;
                
            case 'remove':
                if (remove_from_cart($_SESSION['user_id'], $product_id)) {
                    $success = "Item removed from cart!";
                    header("refresh:1;url=cart.php");
                } else {
                    $error = "Error removing item from cart.";
                }
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <h2 class="mb-4">Shopping Cart</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="../index.php" class="alert-link">Continue shopping</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="row mb-4 align-items-center">
                                    <div class="col-md-2">
                                        <?php if ($item['image_url']): ?>
                                            <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="img-fluid rounded">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white p-3 text-center rounded">
                                                No Image
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="text-muted mb-0">
                                            Sold by: <?php echo htmlspecialchars($item['shop_name']); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <span class="text-primary">₹<?php echo number_format($item['price'], 2); ?></span>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <form method="POST" class="d-flex align-items-center">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['stock']; ?>" 
                                                   class="form-control form-control-sm" 
                                                   onchange="this.form.submit()">
                                        </form>
                                    </div>
                                    
                                    <div class="col-md-2 text-end">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                        </form>
                                    </div>
                                </div>
                                <?php if (!$loop->last): ?>
                                    <hr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Summary</h5>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal</span>
                                <span>₹<?php echo number_format($cart_total, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Shipping</span>
                                <span>Free</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total</strong>
                                <strong>₹<?php echo number_format($cart_total, 2); ?></strong>
                            </div>
                            <a href="checkout.php" class="btn btn-primary w-100">
                                Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
