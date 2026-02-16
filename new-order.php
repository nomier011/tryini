<?php
session_start();
require_once 'config.php';

requireRole('cashier');

$user_id = $_SESSION['user_id'];
$error = '';

// Get all products with stock
$products = $conn->query("SELECT * FROM products WHERE stock > 0 ORDER BY category, name");

// Process new order
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $customer_name = trim($_POST['customer_name'] ?? 'Walk-in');
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $items = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    
    if (empty($items)) {
        $error = "Please select at least one item.";
    } else {
        $conn->begin_transaction();
        
        try {
            $order_number = generateOrderNumber($conn);
            $total = 0;
            $order_items = [];
            
            foreach ($items as $index => $product_id) {
                if (isset($quantities[$index]) && $quantities[$index] > 0) {
                    $product = $conn->query("SELECT * FROM products WHERE id = $product_id")->fetch_assoc();
                    $quantity = (int)$quantities[$index];
                    $subtotal = $product['price'] * $quantity;
                    $total += $subtotal;
                    
                    $order_items[] = [
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'price' => $product['price'],
                        'subtotal' => $subtotal
                    ];
                }
            }
            
            // Insert order
            $stmt = $conn->prepare("INSERT INTO orders (order_number, cashier_id, customer_name, total_amount, payment_method) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sisds", $order_number, $user_id, $customer_name, $total, $payment_method);
            $stmt->execute();
            $order_id = $stmt->insert_id;
            $stmt->close();
            
            // Insert order items and update stock
            foreach ($order_items as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiidd", $order_id, $item['product_id'], $item['quantity'], $item['price'], $item['subtotal']);
                $stmt->execute();
                $stmt->close();
                
                $conn->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['product_id']}");
            }
            
            logActivity($conn, $user_id, 'create_order', "Created order: $order_number");
            
            $conn->commit();
            
            header("Location: receipt.php?order_id=$order_id");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error processing order: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order | Coffee POS</title>
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
            background: rgba(0, 0, 0, 0.4);
            z-index: -1;
        }

        .container {
            max-width: 1400px;
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
            margin-bottom: 5px;
        }

        .brand-text p {
            color: rgba(255,255,255,0.8);
        }

        .pos-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
            margin-top: 20px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(15px);
        }

        .product-card {
            background: rgba(255,255,255,0.15);
            border-radius: 15px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .product-card:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-3px);
        }

        .product-name {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .product-price {
            color: #ffd700;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .product-stock {
            color: rgba(255,255,255,0.6);
            font-size: 0.8rem;
        }

        .cart {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(15px);
            position: sticky;
            top: 20px;
        }

        .cart-header {
            color: white;
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .cart-items {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            margin-bottom: 10px;
        }

        .cart-item-info {
            flex: 1;
        }

        .cart-item-name {
            color: white;
            font-size: 0.95rem;
        }

        .cart-item-price {
            color: #ffd700;
            font-size: 0.9rem;
        }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .quantity-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 5px;
            cursor: pointer;
        }

        .cart-total {
            color: white;
            font-size: 1.4rem;
            font-weight: 700;
            text-align: right;
            margin: 20px 0;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .btn-checkout {
            background: rgba(255, 215, 0, 0.3);
            color: white;
            border: 1px solid #ffd700;
            width: 100%;
            padding: 15px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-checkout:hover {
            background: rgba(255, 215, 0, 0.5);
            transform: translateY(-2px);
        }

        .error-message {
            background: rgba(220,53,69,0.2);
            color: #ff6b6b;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        input, select {
            width: 100%;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="video-background">
        <source src="videos/coffee-bg.mp4" type="video/mp4">
        <source src="https://assets.mixkit.co/videos/preview/mixkit-steaming-hot-coffee-in-a-cup-2902-large.mp4" type="video/mp4">
    </video>
    <div class="video-overlay"></div>
    
    <div class="container">
        <div class="header">
            <div class="brand">
                <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back</a>
                <div class="logo"><i class="fas fa-coffee"></i></div>
                <div class="brand-text">
                    <h1>New Order</h1>
                    <p>Create customer order</p>
                </div>
            </div>
        </div>
        
        <?php if($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="orderForm">
            <div class="pos-container">
                <div class="products-grid">
                    <?php while($product = $products->fetch_assoc()): ?>
                    <div class="product-card" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>)">
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                        <div class="product-stock">Stock: <?php echo $product['stock']; ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="cart">
                    <div class="cart-header">
                        <i class="fas fa-shopping-cart"></i> Current Order
                    </div>
                    
                    <div class="cart-items" id="cartItems"></div>
                    <div class="cart-total" id="cartTotal">$0.00</div>
                    
                    <input type="text" name="customer_name" placeholder="Customer Name (optional)">
                    <select name="payment_method">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="online">Online</option>
                    </select>
                    
                    <button type="submit" name="place_order" class="btn-checkout">
                        <i class="fas fa-check"></i> Complete Order
                    </button>
                </div>
            </div>
            <div id="hiddenInputs"></div>
        </form>
    </div>

    <script>
        let cart = [];
        
        function addToCart(id, name, price) {
            const existingItem = cart.find(item => item.id === id);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({ id, name, price, quantity: 1 });
            }
            updateCartDisplay();
        }
        
        function updateQuantity(id, change) {
            const item = cart.find(item => item.id === id);
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    cart = cart.filter(i => i.id !== id);
                }
            }
            updateCartDisplay();
        }
        
        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            updateCartDisplay();
        }
        
        function updateCartDisplay() {
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            const hiddenInputs = document.getElementById('hiddenInputs');
            
            cartItems.innerHTML = '';
            hiddenInputs.innerHTML = '';
            
            let total = 0;
            
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                cartItems.innerHTML += `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                        </div>
                        <div class="cart-item-quantity">
                            <button type="button" class="quantity-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                            <span style="color: white; min-width: 30px; text-align: center;">${item.quantity}</span>
                            <button type="button" class="quantity-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                            <button type="button" class="quantity-btn" onclick="removeFromCart(${item.id})" style="background: rgba(255,107,107,0.3);">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                
                hiddenInputs.innerHTML += `
                    <input type="hidden" name="items[]" value="${item.id}">
                    <input type="hidden" name="quantities[]" value="${item.quantity}">
                `;
            });
            
            if (cart.length === 0) {
                cartItems.innerHTML = '<div style="color: rgba(255,255,255,0.6); text-align: center; padding: 20px;">No items in cart</div>';
            }
            
            cartTotal.textContent = '$' + total.toFixed(2);
        }
    </script>
</body>
</html>