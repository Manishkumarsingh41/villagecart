<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect if not logged in or not a shop owner
if (!is_user_logged_in() || !is_shop_owner()) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = sanitize_input($_POST['shop_name']);
    $description = sanitize_input($_POST['description']);
    $address = sanitize_input($_POST['address']);
    $phone = sanitize_input($_POST['phone']);
    
    if (empty($shop_name) || empty($description) || empty($address) || empty($phone)) {
        $error = "All fields are required";
    } else {
        // Check if shop name already exists
        $sql = "SELECT id FROM shops WHERE shop_name = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $shop_name);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Shop name already exists";
            } else {
                // Insert new shop
                $sql = "INSERT INTO shops (user_id, shop_name, description, address, phone) VALUES (?, ?, ?, ?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    $user_id = $_SESSION['user_id'];
                    $stmt->bind_param("issss", $user_id, $shop_name, $description, $address, $phone);
                    
                    if ($stmt->execute()) {
                        $success = "Shop registration successful! Waiting for admin approval.";
                        // Redirect to shop dashboard after 2 seconds
                        header("refresh:2;url=../shop/dashboard.php");
                    } else {
                        $error = "Something went wrong. Please try again.";
                    }
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Shop - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="auth-form" style="max-width: 600px;">
            <h2>Register Your Shop</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="shop_name" class="form-label">Shop Name</label>
                    <input type="text" class="form-control" id="shop_name" name="shop_name" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Shop Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Shop Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Contact Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Register Shop</button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
