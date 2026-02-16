<?php
session_start();
require_once 'config.php';

requireRole('manager');

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_product'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category = trim($_POST['category']);
        $stock = intval($_POST['stock']);
        
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsi", $name, $description, $price, $category, $stock);
        
        if ($stmt->execute()) {
            $message = "Product added successfully!";
            logActivity($conn, $_SESSION['user_id'], 'add_product', "Added product: $name");
        } else {
            $error = "Error adding product: " . $conn->error;
        }
        $stmt->close();
    }
    
    if (isset($_POST['update_product'])) {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category = trim($_POST['category']);
        $stock = intval($_POST['stock']);
        
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category=?, stock=? WHERE id=?");
        $stmt->bind_param("ssdsii", $name, $description, $price, $category, $stock, $id);
        
        if ($stmt->execute()) {
            $message = "Product updated successfully!";
            logActivity($conn, $_SESSION['user_id'], 'update_product', "Updated product: $name");
        } else {
            $error = "Error updating product: " . $conn->error;
        }
        $stmt->close();
    }
    
    if (isset($_POST['delete_product'])) {
        $id = intval($_POST['id']);
        
        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "Product deleted successfully!";
            logActivity($conn, $_SESSION['user_id'], 'delete_product', "Deleted product ID: $id");
        } else {
            $error = "Error deleting product: " . $conn->error;
        }
        $stmt->close();
    }
}

$products = $conn->query("SELECT * FROM products ORDER BY category, name");
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
            max-width: 1200px;
            margin: 0 auto;
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
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .brand a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
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
            font-size: 2rem;
        }

        .btn-add {
            background: rgba(76,175,80,0.3);
            color: #4CAF50;
            border: 1px solid rgba(76,175,80,0.5);
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
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
            border-collapse: collapse;
        }

        .products-table th {
            color: rgba(255,255,255,0.9);
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .products-table td {
            color: white;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .btn-edit, .btn-delete {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            margin: 0 5px;
        }

        .btn-edit {
            background: rgba(33,150,243,0.3);
            color: #2196F3;
        }

        .btn-delete {
            background: rgba(244,67,54,0.3);
            color: #f44336;
        }

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
            max-width: 500px;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .modal-content h2 {
            color: white;
            margin-bottom: 20px;
        }

        .modal-content input,
        .modal-content textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="video-background">
        <source src="videos/coffee-bg.mp4" type="video/mp4">
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
        
        <table class="products-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($product = $products->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $product['id']; ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                    <td>$<?php echo number_format($product['price'],2); ?></td>
                    <td><?php echo $product['stock']; ?></td>
                    <td>
                        <button class="btn-edit" onclick='openEditModal(<?php echo json_encode($product); ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-delete" onclick="openDeleteModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Add Product</h2>
            <form method="POST">
                <input type="text" name="name" placeholder="Product Name" required>
                <textarea name="description" placeholder="Description" rows="3"></textarea>
                <input type="text" name="category" placeholder="Category" required>
                <input type="number" name="price" placeholder="Price" step="0.01" required>
                <input type="number" name="stock" placeholder="Stock" required>
                <div class="modal-buttons">
                    <button type="submit" name="add_product" style="background:#4CAF50;color:white;">Add</button>
                    <button type="button" onclick="closeModal('addModal')" style="background:#f44336;color:white;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Product</h2>
            <form method="POST">
                <input type="hidden" name="id" id="edit_id">
                <input type="text" name="name" id="edit_name" required>
                <textarea name="description" id="edit_description" rows="3"></textarea>
                <input type="text" name="category" id="edit_category" required>
                <input type="number" name="price" id="edit_price" step="0.01" required>
                <input type="number" name="stock" id="edit_stock" required>
                <div class="modal-buttons">
                    <button type="submit" name="update_product" style="background:#2196F3;color:white;">Update</button>
                    <button type="button" onclick="closeModal('editModal')" style="background:#f44336;color:white;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2>Delete Product</h2>
            <p style="color:white;">Delete <span id="delete_name" style="color:#ffd700;"></span>?</p>
            <form method="POST">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-buttons">
                    <button type="submit" name="delete_product" style="background:#f44336;color:white;">Delete</button>
                    <button type="button" onclick="closeModal('deleteModal')" style="background:#666;color:white;">Cancel</button>
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
            document.getElementById('edit_category').value = product.category;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_stock').value = product.stock;
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function openDeleteModal(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
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