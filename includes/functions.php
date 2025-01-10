<?php
// User Authentication Functions
function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_seller() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'seller';
}

function get_user_by_id($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function get_user_by_email($conn, $email) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Shop Functions
function get_shop_by_user($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM shops WHERE user_id = ? AND status = 'active'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function get_shop_by_id($conn, $shop_id) {
    $stmt = $conn->prepare("SELECT * FROM shops WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Product Functions
function get_products_by_shop($conn, $shop_id, $limit = 12) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE shop_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $shop_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

function get_featured_products($conn, $limit = 8) {
    $sql = "SELECT p.*, s.name as shop_name 
            FROM products p 
            JOIN shops s ON p.shop_id = s.id 
            WHERE p.status = 'active' 
            ORDER BY p.created_at DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Category Functions
function get_all_categories($conn) {
    $sql = "SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC";
    $result = $conn->query($sql);
    return $result;
}

function get_category_by_id($conn, $category_id) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Utility Functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_random_string($length = 10) {
    return bin2hex(random_bytes($length));
}

function format_price($price) {
    return number_format($price, 2);
}

function upload_image($file, $target_dir) {
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ["error" => "File is not an image."];
    }
    
    // Check file size
    if ($file["size"] > 5000000) {
        return ["error" => "Sorry, your file is too large."];
    }
    
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        return ["error" => "Sorry, only JPG, JPEG, PNG & GIF files are allowed."];
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ["success" => true, "filename" => $new_filename];
    } else {
        return ["error" => "Sorry, there was an error uploading your file."];
    }
}

// Error and Success Messages
function set_message($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function display_message() {
    if(isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'success';
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                    {$message}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
    return '';
}

// Cart Functions
function add_to_cart($product_id, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
}

function update_cart($product_id, $quantity) {
    if ($quantity > 0) {
        $_SESSION['cart'][$product_id] = $quantity;
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
}

function get_cart_count() {
    return isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
}

function get_cart_total($conn) {
    $total = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $stmt = $conn->prepare("SELECT price, discount_price FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($product = $result->fetch_assoc()) {
                $price = $product['discount_price'] ?? $product['price'];
                $total += $price * $quantity;
            }
        }
    }
    return $total;
}

// Order Functions
function create_order($user_id, $shipping_address, $contact_number, $payment_method) {
    global $conn;
    
    $conn->begin_transaction();
    try {
        // Get cart total
        $total_amount = get_cart_total($conn);
        
        // Create order
        $sql = "INSERT INTO orders (user_id, total_amount, shipping_address, contact_number, payment_method) 
                VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("idsss", $user_id, $total_amount, $shipping_address, $contact_number, $payment_method);
            $stmt->execute();
            $order_id = $conn->insert_id;
            
            // Get cart items
            $cart_items = $_SESSION['cart'];
            
            // Create order items
            $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            
            foreach ($cart_items as $product_id => $quantity) {
                $stmt->bind_param("iiid", $order_id, $product_id, $quantity, get_product_price($conn, $product_id));
                $stmt->execute();
                
                // Update product stock
                $new_stock = get_product_stock($conn, $product_id) - $quantity;
                $sql_update = "UPDATE products SET stock = ? WHERE id = ?";
                if ($stmt_update = $conn->prepare($sql_update)) {
                    $stmt_update->bind_param("ii", $new_stock, $product_id);
                    $stmt_update->execute();
                }
            }
            
            // Clear cart
            unset($_SESSION['cart']);
            
            $conn->commit();
            return $order_id;
        }
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
    return false;
}

function get_product_price($conn, $product_id) {
    $stmt = $conn->prepare("SELECT price, discount_price FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($product = $result->fetch_assoc()) {
        return $product['discount_price'] ?? $product['price'];
    }
    return 0;
}

function get_product_stock($conn, $product_id) {
    $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($product = $result->fetch_assoc()) {
        return $product['stock'];
    }
    return 0;
}

// Admin Functions
function get_pending_shops($conn) {
    $sql = "SELECT s.*, u.username, u.email 
            FROM shops s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.status = 'pending'";
    
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function get_all_shops($conn, $status = null) {
    $sql = "SELECT s.*, u.username, u.email 
            FROM shops s 
            JOIN users u ON s.user_id = u.id";
    
    if ($status) {
        $sql .= " WHERE s.status = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $status);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    } else {
        $result = $conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    return [];
}

function update_shop_status($conn, $shop_id, $status) {
    $sql = "UPDATE shops SET status = ? WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $status, $shop_id);
        return $stmt->execute();
    }
    return false;
}

function get_all_orders($conn, $limit = null) {
    $sql = "SELECT o.*, u.username, u.email 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    } else {
        $result = $conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    return [];
}

function get_dashboard_stats($conn) {
    $stats = [
        'total_users' => 0,
        'total_shops' => 0,
        'total_orders' => 0,
        'total_revenue' => 0,
        'pending_shops' => 0,
        'pending_orders' => 0
    ];
    
    // Get total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result) {
        $stats['total_users'] = $result->fetch_assoc()['count'];
    }
    
    // Get total shops
    $result = $conn->query("SELECT COUNT(*) as count FROM shops");
    if ($result) {
        $stats['total_shops'] = $result->fetch_assoc()['count'];
    }
    
    // Get pending shops
    $result = $conn->query("SELECT COUNT(*) as count FROM shops WHERE status = 'pending'");
    if ($result) {
        $stats['pending_shops'] = $result->fetch_assoc()['count'];
    }
    
    // Get total orders and revenue
    $result = $conn->query("SELECT COUNT(*) as count, SUM(total_amount) as revenue FROM orders");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_orders'] = $row['count'];
        $stats['total_revenue'] = $row['revenue'];
    }
    
    // Get pending orders
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    if ($result) {
        $stats['pending_orders'] = $result->fetch_assoc()['count'];
    }
    
    return $stats;
}

function update_order_status($conn, $order_id, $status) {
    $sql = "UPDATE orders SET status = ? WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $status, $order_id);
        return $stmt->execute();
    }
    return false;
}

// User Management Functions
function get_all_users($conn, $role = null) {
    $sql = "SELECT * FROM users";
    if ($role) {
        $sql .= " WHERE role = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $role);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    } else {
        $result = $conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    return [];
}

function update_user_status($conn, $user_id, $status) {
    $sql = "UPDATE users SET status = ? WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $status, $user_id);
        return $stmt->execute();
    }
    return false;
}

function update_user_role($conn, $user_id, $role) {
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $role, $user_id);
        return $stmt->execute();
    }
    return false;
}

function get_user_details($conn, $user_id) {
    $sql = "SELECT u.*, 
            (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as total_orders,
            (SELECT SUM(total_amount) FROM orders WHERE user_id = u.id) as total_spent,
            (SELECT shop_name FROM shops WHERE user_id = u.id LIMIT 1) as shop_name
            FROM users u WHERE u.id = ?";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return null;
}

// User Activity and Search Functions
function search_users($conn, $query) {
    $search = "%$query%";
    $sql = "SELECT * FROM users 
            WHERE username LIKE ? 
            OR email LIKE ? 
            OR phone LIKE ?";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sss", $search, $search, $search);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

function log_user_activity($conn, $user_id, $activity_type, $description) {
    $sql = "INSERT INTO user_activity_logs (user_id, activity_type, description) 
            VALUES (?, ?, ?)";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iss", $user_id, $activity_type, $description);
        return $stmt->execute();
    }
    return false;
}

function get_user_activity($conn, $user_id, $limit = 10) {
    $sql = "SELECT * FROM user_activity_logs 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

function verify_user($conn, $user_id) {
    $sql = "UPDATE users SET is_verified = 1 WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }
    return false;
}

function send_verification_email($conn, $user_id, $email) {
    $verification_token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $sql = "INSERT INTO verification_tokens (user_id, token, expires_at) 
            VALUES (?, ?, ?)";
            
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iss", $user_id, $verification_token, $expiry);
        if ($stmt->execute()) {
            $verification_link = "http://{$_SERVER['HTTP_HOST']}/verify.php?token=" . $verification_token;
            
            $subject = "Verify Your VillageCart Account";
            $message = "Hello,\n\n";
            $message .= "Please click the following link to verify your VillageCart account:\n";
            $message .= $verification_link . "\n\n";
            $message .= "This link will expire in 24 hours.\n\n";
            $message .= "If you didn't create a VillageCart account, please ignore this email.\n\n";
            $message .= "Best regards,\nVillageCart Team";
            
            $headers = "From: noreply@villagecart.com";
            
            return mail($email, $subject, $message, $headers);
        }
    }
    return false;
}

function export_users_csv($conn) {
    $sql = "SELECT u.id, u.username, u.email, u.phone, u.role, u.status, u.created_at,
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as total_spent,
            s.shop_name
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id
            LEFT JOIN shops s ON u.id = s.user_id
            GROUP BY u.id";
            
    $result = $conn->query($sql);
    if (!$result) {
        return false;
    }
    
    $filename = "users_export_" . date('Y-m-d_His') . ".csv";
    $file = fopen('php://temp', 'w');
    
    // Add headers
    fputcsv($file, [
        'ID', 'Username', 'Email', 'Phone', 'Role', 'Status', 
        'Registration Date', 'Total Orders', 'Total Spent', 'Shop Name'
    ]);
    
    // Add data
    while ($row = $result->fetch_assoc()) {
        fputcsv($file, [
            $row['id'],
            $row['username'],
            $row['email'],
            $row['phone'],
            $row['role'],
            $row['status'],
            $row['created_at'],
            $row['total_orders'],
            $row['total_spent'],
            $row['shop_name']
        ]);
    }
    
    rewind($file);
    $csv = stream_get_contents($file);
    fclose($file);
    
    return [
        'filename' => $filename,
        'content' => $csv
    ];
}

// Database connection function
function get_db_connection() {
    global $pdo;
    return $pdo;
}
?>