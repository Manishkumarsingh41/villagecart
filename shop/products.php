<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect if not logged in or not a shop owner
if (!is_user_logged_in() || !is_shop_owner()) {
    header("Location: ../pages/login.php");
    exit();
}

$error = '';
$success = '';
$shop = null;
$products = [];

// Get shop information
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM shops WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $shop = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$shop) {
    header("Location: dashboard.php");
    exit();
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = sanitize_input($_POST['name']);
                $description = sanitize_input($_POST['description']);
                $price = floatval($_POST['price']);
                $stock = intval($_POST['stock']);
                $category_id = intval($_POST['category_id']);
                
                // Handle image upload
                $image_url = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $target_dir = "../assets/images/products/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
                    $new_filename = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $image_url = 'assets/images/products/' . $new_filename;
                    }
                }
                
                $sql = "INSERT INTO products (shop_id, category_id, name, description, price, stock, image_url) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("iissdis", $shop['id'], $category_id, $name, $description, $price, $stock, $image_url);
                    if ($stmt->execute()) {
                        $success = "Product added successfully!";
                    } else {
                        $error = "Error adding product.";
                    }
                }
                break;
                
            case 'update':
                $product_id = intval($_POST['product_id']);
                $name = sanitize_input($_POST['name']);
                $description = sanitize_input($_POST['description']);
                $price = floatval($_POST['price']);
                $stock = intval($_POST['stock']);
                $status = sanitize_input($_POST['status']);
                
                $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, status = ? 
                        WHERE id = ? AND shop_id = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("ssdiiii", $name, $description, $price, $stock, $status, $product_id, $shop['id']);
                    if ($stmt->execute()) {
                        $success = "Product updated successfully!";
                    } else {
                        $error = "Error updating product.";
                    }
                }
                break;
                
            case 'delete':
                $product_id = intval($_POST['product_id']);
                $sql = "DELETE FROM products WHERE id = ? AND shop_id = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("ii", $product_id, $shop['id']);
                    if ($stmt->execute()) {
                        $success = "Product deleted successfully!";
                    } else {
                        $error = "Error deleting product.";
                    }
                }
                break;
        }
    }
}

// Get categories
$categories = [];
$sql = "SELECT * FROM categories";
$result = $conn->query($sql);
if ($result) {
    $categories = $result->fetch_all(MYSQLI_ASSOC);
}

// Get products
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.shop_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $shop['id']);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - VillageCart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Products</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                Add New Product
            </button>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($products as $product): ?>
                    <tr>
                        <td>
                            <?php if ($product['image_url']): ?>
                                <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    No Image
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td>₹<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['stock']; ?></td>
                        <td><?php echo ucfirst($product['status']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" 
                                    onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                Edit
                            </button>
                            <button class="btn btn-sm btn-danger" 
                                    onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" step="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="stock" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="product_id" id="edit_product_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="stock" id="edit_stock" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" id="delete_product_id">
                        <p>Are you sure you want to delete this product? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function editProduct(product) {
        document.getElementById('edit_product_id').value = product.id;
        document.getElementById('edit_name').value = product.name;
        document.getElementById('edit_description').value = product.description;
        document.getElementById('edit_price').value = product.price;
        document.getElementById('edit_stock').value = product.stock;
        document.getElementById('edit_status').value = product.status;
        
        new bootstrap.Modal(document.getElementById('editProductModal')).show();
    }
    
    function deleteProduct(productId) {
        document.getElementById('delete_product_id').value = productId;
        new bootstrap.Modal(document.getElementById('deleteProductModal')).show();
    }
    </script>
</body>
</html>
