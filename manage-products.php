<?php
session_start();
require_once 'config.php';

requireRole('manager');

$message = '';
$error = '';

// Create images directory and subdirectories if they don't exist
function createImageDirectories() {
    $base_dir = 'images';
    $categories = ['drinks', 'pastry', 'foods'];
    
    // Create base images directory if it doesn't exist
    if (!file_exists($base_dir)) {
        if (!mkdir($base_dir, 0777, true)) {
            return false;
        }
    }
    
    // Create category subdirectories
    foreach ($categories as $category) {
        $category_dir = $base_dir . '/' . $category;
        if (!file_exists($category_dir)) {
            if (!mkdir($category_dir, 0777, true)) {
                return false;
            }
        }
    }
    
    return true;
}

// Handle product actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_product'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $main_category = $_POST['main_category'];
        $sub_category = trim($_POST['sub_category']);
        $stock = intval($_POST['stock']);
        
        // Create directories first
        if (!createImageDirectories()) {
            $error = "Failed to create image directories. Please check permissions.";
        } else {
            // Handle image upload
            $image = 'default.jpg'; // Default image
            
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $filename = $_FILES['product_image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $filesize = $_FILES['product_image']['size'];
                
                // Check file size (max 5MB)
                if ($filesize > 5 * 1024 * 1024) {
                    $error = "File size too large. Maximum 5MB allowed.";
                }
                // Check file extension
                elseif (in_array($ext, $allowed)) {
                    // Create category folder if it doesn't exist (double-check)
                    $upload_dir = "images/" . $main_category . "/";
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $new_filename = time() . '_' . uniqid() . '.' . $ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    // Get absolute path for better error handling
                    $absolute_path = realpath(dirname(__FILE__)) . '/' . $upload_dir;
                    
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                        $image = $main_category . "/" . $new_filename;
                    } else {
                        $error = "Error uploading file. Please check folder permissions.";
                        error_log("Upload failed. Temp: " . $_FILES['product_image']['tmp_name'] . " Destination: " . $upload_path);
                    }
                } else {
                    $error = "Invalid file type. Allowed: " . implode(', ', $allowed);
                }
            }
            
            if (empty($error)) {
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, main_category, sub_category, stock, image, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("ssdssis", $name, $description, $price, $main_category, $sub_category, $stock, $image);
                
                if ($stmt->execute()) {
                    $message = "Product added successfully!";
                } else {
                    $error = "Error adding product: " . $conn->error;
                }
                $stmt->close();
            }
        }
    }
    
    if (isset($_POST['update_product'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $main_category = $_POST['main_category'];
        $sub_category = trim($_POST['sub_category']);
        $stock = intval($_POST['stock']);
        $current_image = $_POST['current_image'];
        
        // Create directories first
        if (!createImageDirectories()) {
            $error = "Failed to create image directories. Please check permissions.";
        } else {
            $image = $current_image; // Keep current image by default
            
            // Handle new image upload
            if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $filename = $_FILES['product_image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $filesize = $_FILES['product_image']['size'];
                
                // Check file size (max 5MB)
                if ($filesize > 5 * 1024 * 1024) {
                    $error = "File size too large. Maximum 5MB allowed.";
                }
                // Check file extension
                elseif (in_array($ext, $allowed)) {
                    // Create category folder if it doesn't exist
                    $upload_dir = "images/" . $main_category . "/";
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $new_filename = time() . '_' . uniqid() . '.' . $ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                        // Delete old image if it's not default and exists
                        if ($current_image != 'default.jpg' && file_exists("images/" . $current_image)) {
                            unlink("images/" . $current_image);
                        }
                        $image = $main_category . "/" . $new_filename;
                    } else {
                        $error = "Error uploading file. Please check folder permissions.";
                    }
                } else {
                    $error = "Invalid file type. Allowed: " . implode(', ', $allowed);
                }
            }
            
            if (empty($error)) {
                $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, main_category=?, sub_category=?, stock=?, image=? WHERE id=?");
                $stmt->bind_param("ssdssisi", $name, $description, $price, $main_category, $sub_category, $stock, $image, $id);
                
                if ($stmt->execute()) {
                    $message = "Product updated successfully!";
                } else {
                    $error = "Error updating product: " . $conn->error;
                }
                $stmt->close();
            }
        }
    }
    
    if (isset($_POST['delete_product'])) {
        $id = intval($_POST['id']);
        $image_to_delete = $_POST['image_to_delete'];
        
        // Delete the image file if it exists and is not default
        if (!empty($image_to_delete) && $image_to_delete != 'default.jpg') {
            $image_path = "images/" . $image_to_delete;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Product deleted successfully!";
        } else {
            $error = "Error deleting product: " . $conn->error;
        }
        $stmt->close();
    }
    
    if (isset($_POST['toggle_availability'])) {
        $id = intval($_POST['id']);
        $current_status = intval($_POST['current_status']);
        $new_status = $current_status ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE products SET is_available = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_status, $id);
        
        if ($stmt->execute()) {
            $message = "Product availability updated!";
        } else {
            $error = "Error updating availability";
        }
        $stmt->close();
    }
}

// Get all products
$products = $conn->query("SELECT * FROM products ORDER BY main_category, sub_category, name");

// Create a default placeholder image if it doesn't exist
if (!file_exists('images/default.jpg')) {
    // You can create a simple default image or just use a placeholder
    // For now, we'll just note that it doesn't exist
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | Coffee POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            min-height: 100vh;
            padding: 20px;
            background: #000;
            position: relative;
            overflow-x: hidden;
        }

        #video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
        }

        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            z-index: -1;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            backdrop-filter: blur(15px);
            flex-wrap: wrap;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .brand a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            transition: all 0.3s;
        }

        .brand a:hover {
            background: rgba(255,255,255,0.2);
        }

        .logo {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
        }

        .brand-text h1 {
            color: white;
            font-size: clamp(1.5rem, 4vw, 2rem);
        }

        .btn-add {
            background: rgba(76,175,80,0.3);
            color: #4CAF50;
            border: 1px solid rgba(76,175,80,0.5);
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-add:hover {
            background: rgba(76,175,80,0.4);
            transform: translateY(-2px);
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-message {
            background: rgba(76,175,80,0.2);
            color: #4CAF50;
            border-left: 4px solid #4CAF50;
        }

        .error-message {
            background: rgba(244,67,54,0.2);
            color: #f44336;
            border-left: 4px solid #f44336;
        }

        .products-table {
            width: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(15px);
            overflow-x: auto;
        }

        .products-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }

        .products-table th {
            color: rgba(255,255,255,0.9);
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-weight: 600;
        }

        .products-table td {
            color: white;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
        }

        .product-thumb {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            background: rgba(255,255,255,0.1);
        }

        .product-thumb-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255,255,255,0.5);
            font-size: 20px;
        }

        .category-badge {
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .category-drinks {
            background: rgba(33,150,243,0.2);
            color: #2196F3;
        }

        .category-pastry {
            background: rgba(255,193,7,0.2);
            color: #ffc107;
        }

        .category-foods {
            background: rgba(76,175,80,0.2);
            color: #4CAF50;
        }

        .availability-badge {
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
        }

        .available {
            background: rgba(76,175,80,0.2);
            color: #4CAF50;
        }

        .unavailable {
            background: rgba(244,67,54,0.2);
            color: #f44336;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-edit, .btn-delete, .btn-toggle {
            padding: 6px 12px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.85rem;
        }

        .btn-edit {
            background: rgba(33,150,243,0.3);
            color: #2196F3;
        }

        .btn-edit:hover {
            background: rgba(33,150,243,0.4);
        }

        .btn-delete {
            background: rgba(244,67,54,0.3);
            color: #f44336;
        }

        .btn-delete:hover {
            background: rgba(244,67,54,0.4);
        }

        .btn-toggle {
            background: rgba(255,152,0,0.3);
            color: #ff9800;
        }

        .btn-toggle:hover {
            background: rgba(255,152,0,0.4);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 30px;
            width: 90%;
            max-width: 600px;
            border: 1px solid rgba(255,255,255,0.2);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h2 {
            color: white;
            margin-bottom: 20px;
        }

        .modal-content .form-group {
            margin-bottom: 15px;
        }

        .modal-content label {
            display: block;
            color: rgba(255,255,255,0.9);
            margin-bottom: 5px;
            font-weight: 600;
        }

        .modal-content input,
        .modal-content textarea,
        .modal-content select {
            width: 100%;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
        }

        .modal-content input[type="file"] {
            padding: 8px;
            background: rgba(255,255,255,0.05);
        }

        .modal-content textarea {
            resize: vertical;
            min-height: 80px;
        }

        .modal-content select option {
            background: #333;
        }

        .current-image-preview {
            margin: 10px 0;
            padding: 10px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .current-image-preview img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }

        .current-image-preview span {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .modal-buttons button:hover {
            transform: translateY(-2px);
        }

        .btn-submit {
            background: rgba(76,175,80,0.3);
            color: #4CAF50;
        }

        .btn-update {
            background: rgba(33,150,243,0.3);
            color: #2196F3;
        }

        .btn-cancel {
            background: rgba(244,67,54,0.3);
            color: #f44336;
        }

        .image-requirements {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.6);
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn-add {
                width: 100%;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="video-background">
        <source src="videos/coffee-bg2.mp4" type="video/mp4">
    </video>
    <div class="video-overlay"></div>
    
    <div class="container">
        <div class="header">
            <div class="brand">
                <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back</a>
                <div class="logo"><i class="fas fa-box"></i></div>
                <div class="brand-text">
                    <h1>Manage Products</h1>
                </div>
            </div>
            <button class="btn-add" onclick="openAddModal()"><i class="fas fa-plus"></i> Add Product</button>
        </div>
        
        <?php if($message): ?>
        <div class="message success-message"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
        <div class="message error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="products-table">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Main Category</th>
                        <th>Sub Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products && $products->num_rows > 0): ?>
                        <?php while($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php 
                                $image_path = "images/" . $product['image'];
                                if (!empty($product['image']) && file_exists($image_path)): 
                                ?>
                                    <img src="<?php echo $image_path; ?>" alt="<?php echo $product['name']; ?>" class="product-thumb">
                                <?php else: ?>
                                    <div class="product-thumb-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>#<?php echo $product['id']; ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>
                                <span class="category-badge category-<?php echo $product['main_category']; ?>">
                                    <?php echo ucfirst($product['main_category']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($product['sub_category'] ?? 'N/A'); ?></td>
                            <td>₱<?php echo number_format($product['price'],2); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td>
                                <span class="availability-badge <?php echo $product['is_available'] ? 'available' : 'unavailable'; ?>">
                                    <?php echo $product['is_available'] ? 'Available' : 'Unavailable'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $product['is_available']; ?>">
                                        <button type="submit" name="toggle_availability" class="btn-toggle" title="Toggle Availability">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                    <button class="btn-edit" onclick='openEditModal(<?php echo json_encode($product); ?>)'>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn-delete" onclick="openDeleteModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', '<?php echo $product['image']; ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: rgba(255,255,255,0.6); padding: 40px;">
                                No products found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Add New Product</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Main Category</label>
                    <select name="main_category" required>
                        <option value="">Select Category</option>
                        <option value="drinks">Drinks</option>
                        <option value="pastry">Pastry</option>
                        <option value="foods">Foods</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Sub Category</label>
                    <input type="text" name="sub_category" placeholder="e.g., Hot Coffee, Cold Drinks, Pastries" required>
                </div>
                
                <div class="form-group">
                    <label>Price (₱)</label>
                    <input type="number" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="product_image" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="image-requirements">
                        <i class="fas fa-info-circle"></i> Max file size: 5MB. Allowed: JPG, PNG, GIF, WEBP
                    </div>
                </div>
                
                <div class="modal-buttons">
                    <button type="submit" name="add_product" class="btn-submit">Add Product</button>
                    <button type="button" onclick="closeModal('addModal')" class="btn-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Product</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="current_image" id="edit_current_image">
                
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Main Category</label>
                    <select name="main_category" id="edit_main_category" required>
                        <option value="drinks">Drinks</option>
                        <option value="pastry">Pastry</option>
                        <option value="foods">Foods</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Sub Category</label>
                    <input type="text" name="sub_category" id="edit_sub_category" required>
                </div>
                
                <div class="form-group">
                    <label>Price (₱)</label>
                    <input type="number" name="price" id="edit_price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" id="edit_stock" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Current Image</label>
                    <div class="current-image-preview" id="current_image_preview">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Change Image (Optional)</label>
                    <input type="file" name="product_image" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="image-requirements">
                        <i class="fas fa-info-circle"></i> Leave empty to keep current image. Max 5MB. Allowed: JPG, PNG, GIF, WEBP
                    </div>
                </div>
                
                <div class="modal-buttons">
                    <button type="submit" name="update_product" class="btn-update">Update Product</button>
                    <button type="button" onclick="closeModal('editModal')" class="btn-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2>Delete Product</h2>
            <p style="color:white; margin-bottom: 20px;">Are you sure you want to delete <strong style="color:#ffd700;" id="delete_name"></strong>?</p>
            <p style="color:rgba(255,255,255,0.7); margin-bottom: 20px; font-size:0.9rem;">This action cannot be undone.</p>
            <form method="POST">
                <input type="hidden" name="id" id="delete_id">
                <input type="hidden" name="image_to_delete" id="delete_image">
                <div class="modal-buttons">
                    <button type="submit" name="delete_product" class="btn-cancel" style="background: rgba(244,67,54,0.3); color: #f44336;">Delete Permanently</button>
                    <button type="button" onclick="closeModal('deleteModal')" class="btn-cancel" style="background: rgba(255,255,255,0.2); color: white;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
        
        function openEditModal(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_description').value = product.description || '';
            document.getElementById('edit_main_category').value = product.main_category;
            document.getElementById('edit_sub_category').value = product.sub_category || '';
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_stock').value = product.stock;
            document.getElementById('edit_current_image').value = product.image || '';
            
            // Show current image preview
            const previewDiv = document.getElementById('current_image_preview');
            const imagePath = 'images/' + (product.image || '');
            
            // Check if image exists
            const img = new Image();
            img.onload = function() {
                previewDiv.innerHTML = `
                    <img src="${imagePath}" alt="${product.name}">
                    <span>${product.image}</span>
                `;
            };
            img.onerror = function() {
                previewDiv.innerHTML = `
                    <div style="width:60px;height:60px;border-radius:8px;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-image" style="color:rgba(255,255,255,0.5);"></i>
                    </div>
                    <span>No image uploaded</span>
                `;
            };
            img.src = imagePath;
            
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function openDeleteModal(id, name, image) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            document.getElementById('delete_image').value = image || '';
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>