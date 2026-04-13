<?php
session_start();
require_once 'config.php';
requireRole('cashier');

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$drinks = $conn->query("SELECT * FROM products WHERE main_category = 'drinks' AND stock > 0 AND is_available = 1 ORDER BY sub_category, name");
$pastry = $conn->query("SELECT * FROM products WHERE main_category = 'pastry' AND stock > 0 AND is_available = 1 ORDER BY sub_category, name");
$foods = $conn->query("SELECT * FROM products WHERE main_category = 'foods' AND stock > 0 AND is_available = 1 ORDER BY sub_category, name");
$pending_orders = $conn->query("SELECT o.*, u.username as cashier_name FROM orders o JOIN users u ON o.cashier_id = u.id WHERE o.order_status IN ('pending', 'preparing') ORDER BY o.created_at DESC");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    $customer_name = trim($_POST['customer_name'] ?? 'Walk-in');
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $items = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $item_notes = $_POST['item_notes'] ?? [];
    
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
                    if ($product['stock'] < $quantity) throw new Exception("Insufficient stock for " . $product['name']);
                    $subtotal = $product['price'] * $quantity;
                    $total += $subtotal;
                    $order_items[] = ['product_id' => $product_id, 'quantity' => $quantity, 'price' => $product['price'], 'subtotal' => $subtotal, 'notes' => $item_notes[$index] ?? ''];
                }
            }
            
            $order_status = 'pending';
            $stmt = $conn->prepare("INSERT INTO orders (order_number, cashier_id, customer_name, total_amount, payment_method, order_status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisdss", $order_number, $user_id, $customer_name, $total, $payment_method, $order_status);
            $stmt->execute();
            $order_id = $stmt->insert_id;
            $stmt->close();
            
            foreach ($order_items as $item) {
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiidds", $order_id, $item['product_id'], $item['quantity'], $item['price'], $item['subtotal'], $item['notes']);
                $stmt->execute();
                $stmt->close();
                $conn->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['product_id']}");
            }
            
            $conn->commit();
            
            header("Location: receipt.php?order_id=$order_id");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['order_status'];
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    if ($stmt->execute() && $new_status == 'served') {
        header("Location: receipt.php?order_id=$order_id");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_cancellation'])) {
    $order_id = $_POST['order_id'];
    $reason = trim($_POST['cancellation_reason']);
    if (empty($reason)) $error = "Please provide a reason.";
    elseif (requestCancellation($conn, $order_id, $user_id, $reason)) $success = "Cancellation request sent.";
    else $error = "Error requesting cancellation.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>New Order | Coffee POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { min-height: 100vh; padding: 20px; background: #000; position: relative; }
        #video-background { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -2; }
        .video-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: -1; }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 15px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 20px; background: rgba(255,255,255,0.1); border-radius: 20px; backdrop-filter: blur(15px); flex-wrap: wrap; gap: 20px; }
        .brand { display: flex; align-items: center; gap: 20px; }
        .brand a { color: white; text-decoration: none; padding: 10px 20px; background: rgba(255,255,255,0.1); border-radius: 10px; }
        .logo { width: clamp(45px, 8vw, 60px); height: clamp(45px, 8vw, 60px); background: rgba(255,255,255,0.2); border-radius: 15px; display: flex; align-items: center; justify-content: center; color: white; font-size: clamp(22px, 5vw, 28px); }
        .brand-text h1 { color: white; font-size: clamp(1.3rem, 4vw, 2rem); }
        .message { padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .success-message { background: rgba(76,175,80,0.2); color: #4CAF50; border-left: 4px solid #4CAF50; }
        .error-message { background: rgba(244,67,54,0.2); color: #f44336; border-left: 4px solid #f44336; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .tab { padding: clamp(10px, 3vw, 12px) clamp(20px, 4vw, 25px); background: rgba(255,255,255,0.1); border-radius: 30px; color: white; cursor: pointer; border: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center; gap: 8px; }
        .tab.active { background: rgba(255,215,0,0.3); border-color: #ffd700; color: #ffd700; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .pos-container { display: grid; grid-template-columns: 1fr 350px; gap: 20px; align-items: start; }
        .products-section { background: rgba(255,255,255,0.1); border-radius: 20px; padding: 20px; backdrop-filter: blur(15px); max-height: 600px; overflow-y: auto; }
        .category-title { color: white; font-size: clamp(1.1rem, 3.5vw, 1.3rem); margin: 20px 0 15px; padding-bottom: 5px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .category-title:first-child { margin-top: 0; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .product-card { background: rgba(255,255,255,0.15); border-radius: 12px; padding: 12px; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; }
        .product-card:hover { background: rgba(255,255,255,0.25); transform: translateY(-3px); }
        .product-name { color: white; font-weight: 600; margin-bottom: 5px; }
        .product-price { color: #ffd700; font-weight: 700; }
        .product-price::before { content: '₱'; margin-right: 2px; }
        .product-stock { color: rgba(255,255,255,0.6); font-size: 0.75rem; margin-top: 5px; }
        .cart { background: rgba(255,255,255,0.1); border-radius: 20px; padding: 20px; backdrop-filter: blur(15px); position: sticky; top: 20px; display: flex; flex-direction: column; max-height: calc(100vh - 150px); overflow-y: auto; }
        .cart-header { color: white; font-size: 1.2rem; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; }
        .cart-items { flex: 1; overflow-y: auto; margin-bottom: 20px; min-height: 200px; max-height: 350px; }
        .cart-item { background: rgba(255,255,255,0.05); border-radius: 10px; padding: 10px; margin-bottom: 10px; }
        .cart-item-header { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .cart-item-name { color: white; font-weight: 600; }
        .cart-item-price { color: #ffd700; }
        .cart-item-notes { width: 100%; padding: 5px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 5px; color: white; margin: 5px 0; }
        .cart-item-notes::placeholder { color: rgba(255,255,255,0.5); }
        .cart-item-controls { display: flex; align-items: center; justify-content: flex-end; gap: 10px; }
        .quantity-btn { background: rgba(255,255,255,0.1); border: none; color: white; width: 25px; height: 25px; border-radius: 5px; cursor: pointer; }
        .remove-btn { background: rgba(244,67,54,0.3); color: #f44336; border: none; width: 25px; height: 25px; border-radius: 5px; cursor: pointer; }
        .cart-footer { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; margin-top: auto; }
        .cart-total { color: white; font-size: 1.3rem; font-weight: 700; text-align: right; margin-bottom: 20px; }
        .cart-total::before { content: '₱'; margin-right: 5px; }
        .cart-inputs input, .cart-inputs select {
            width: 100%;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
            margin-bottom: 10px;
            font-size: 0.95rem;
            cursor: pointer;
        }
        .cart-inputs input:focus, .cart-inputs select:focus {
            outline: none;
            border-color: #ffd700;
            background: rgba(255,255,255,0.15);
        }
        .cart-inputs select option {
            background: #2a2a2a;
            color: white;
            padding: 10px;
        }
        .cart-inputs input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        .btn-checkout { background: rgba(255,215,0,0.3); color: white; border: 1px solid #ffd700; width: 100%; padding: 15px; border-radius: 10px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-checkout:hover { background: rgba(255,215,0,0.5); transform: translateY(-2px); }
        .empty-cart { text-align: center; padding: 30px; color: rgba(255,255,255,0.6); }
        .order-card { background: rgba(255,255,255,0.1); border-radius: 15px; padding: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .order-number { color: #ffd700; font-weight: 600; }
        .order-status { padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; display: inline-block; margin-top: 5px; }
        .status-pending { background: rgba(255,193,7,0.2); color: #ffc107; }
        .status-preparing { background: rgba(33,150,243,0.2); color: #2196F3; }
        .btn-status { padding: 8px 15px; border-radius: 8px; border: none; cursor: pointer; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: rgba(255,255,255,0.15); backdrop-filter: blur(15px); border-radius: 20px; padding: 30px; width: 90%; max-width: 400px; }
        .modal-content h3 { color: white; margin-bottom: 15px; }
        .modal-content textarea { width: 100%; padding: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white; margin-bottom: 15px; }
        .modal-content textarea::placeholder { color: rgba(255,255,255,0.5); }
        .modal-buttons { display: flex; gap: 10px; }
        .modal-buttons button { flex: 1; padding: 12px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .modal-buttons button:hover { transform: translateY(-2px); }
        @media (max-width: 1024px) { .pos-container { grid-template-columns: 1fr; } .cart { position: static; max-height: none; } }
        @media (max-width: 768px) { .products-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); } }
        @media (max-width: 480px) { .products-grid { grid-template-columns: 1fr; } .tabs { flex-direction: column; } .tab { justify-content: center; } }
    </style>
</head>
<body>
    <video autoplay muted loop id="video-background"><source src="videos/coffee-bg2.mp4" type="video/mp4"></video>
    <div class="video-overlay"></div>
    
    <div class="container">
        <div class="header">
            <div class="brand">
                <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Back</a>
                <div class="logo"><i class="fas fa-coffee"></i></div>
                <div class="brand-text"><h1>Point of Sale</h1></div>
            </div>
        </div>
        
        <?php if($success): ?><div class="message success-message"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="message error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('new-order')"><i class="fas fa-plus-circle"></i> New Order</div>
            <div class="tab" onclick="showTab('pending-orders')"><i class="fas fa-clock"></i> Pending Orders</div>
        </div>
        
        <div id="new-order-tab" class="tab-content active">
            <form method="POST" id="orderForm">
                <div class="pos-container">
                    <div class="products-section">
                        <h3 class="category-title"><i class="fas fa-coffee"></i> Drinks</h3>
                        <div class="products-grid">
                            <?php while($p = $drinks->fetch_assoc()): ?>
                            <div class="product-card" onclick="addToCart(<?php echo $p['id']; ?>, '<?php echo addslashes($p['name']); ?>', <?php echo $p['price']; ?>)">
                                <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div class="product-price"><?php echo number_format($p['price'], 2); ?></div>
                                <div class="product-stock">Stock: <?php echo $p['stock']; ?></div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <h3 class="category-title"><i class="fas fa-bread-slice"></i> Pastry</h3>
                        <div class="products-grid">
                            <?php while($p = $pastry->fetch_assoc()): ?>
                            <div class="product-card" onclick="addToCart(<?php echo $p['id']; ?>, '<?php echo addslashes($p['name']); ?>', <?php echo $p['price']; ?>)">
                                <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div class="product-price"><?php echo number_format($p['price'], 2); ?></div>
                                <div class="product-stock">Stock: <?php echo $p['stock']; ?></div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <h3 class="category-title"><i class="fas fa-utensils"></i> Foods</h3>
                        <div class="products-grid">
                            <?php while($p = $foods->fetch_assoc()): ?>
                            <div class="product-card" onclick="addToCart(<?php echo $p['id']; ?>, '<?php echo addslashes($p['name']); ?>', <?php echo $p['price']; ?>)">
                                <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div class="product-price"><?php echo number_format($p['price'], 2); ?></div>
                                <div class="product-stock">Stock: <?php echo $p['stock']; ?></div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    
                    <div class="cart">
                        <div class="cart-header">
                            <span><i class="fas fa-shopping-cart"></i> Current Order</span>
                            <span id="itemCount">0 items</span>
                        </div>
                        <div class="cart-items" id="cartItems">
                            <div class="empty-cart"><i class="fas fa-shopping-cart"></i><p>No items in cart</p></div>
                        </div>
                        <div class="cart-footer">
                            <div class="cart-total" id="cartTotal">0.00</div>
                            <div class="cart-inputs">
                                <input type="text" name="customer_name" placeholder="Customer Name (optional)">
                                <select name="payment_method" id="payment_method" required>
                                    <option value="">-- Select Payment Method --</option>
                                    <option value="cash">💵 Cash</option>
                                    <option value="card">💳 Card</option>
                                    <option value="gcash">📱 GCash</option>
                                    <option value="maya">🏦 Maya</option>
                                </select>
                            </div>
                            <button type="submit" name="place_order" class="btn-checkout" onclick="return validateOrder()">
                                <i class="fas fa-check"></i> Place Order
                            </button>
                        </div>
                    </div>
                </div>
                <div id="hiddenInputs"></div>
            </form>
        </div>
        
        <div id="pending-orders-tab" class="tab-content">
            <h3 style="color: white; margin-bottom: 20px;">Active Orders</h3>
            <div class="orders-list">
                <?php if ($pending_orders && $pending_orders->num_rows > 0): ?>
                    <?php while($order = $pending_orders->fetch_assoc()): ?>
                    <div class="order-card">
                        <div>
                            <div class="order-number"><?php echo $order['order_number']; ?></div>
                            <div>Customer: <?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></div>
                            <div style="color:#ffd700;">Total: ₱<?php echo number_format($order['total_amount'], 2); ?></div>
                        </div>
                        <div class="order-actions">
                            <?php if($order['order_status'] == 'pending' && !$order['cancellation_requested']): ?>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="order_status" value="preparing">
                                <button type="submit" name="update_status" class="btn-status" style="background:rgba(33,150,243,0.3);color:#2196F3;">
                                    <i class="fas fa-clock"></i> Start
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if($order['order_status'] == 'preparing' && !$order['cancellation_requested']): ?>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="order_status" value="served">
                                <button type="submit" name="update_status" class="btn-status" style="background:rgba(76,175,80,0.3);color:#4CAF50;">
                                    <i class="fas fa-check"></i> Served
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if($order['order_status'] != 'served' && $order['order_status'] != 'cancelled' && !$order['cancellation_requested']): ?>
                            <button class="btn-status" style="background:rgba(255,152,0,0.3);color:#ff9800;" onclick="openCancelRequestModal(<?php echo $order['id']; ?>)">
                                <i class="fas fa-ban"></i> Cancel
                            </button>
                            <?php endif; ?>
                            
                            <a href="receipt.php?order_id=<?php echo $order['id']; ?>" class="btn-status" style="background:rgba(255,255,255,0.2);color:white;text-decoration:none;display:inline-block;">
                                <i class="fas fa-print"></i> Receipt
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align:center; padding:40px; color:rgba(255,255,255,0.6);">No pending orders</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div id="cancelRequestModal" class="modal">
        <div class="modal-content">
            <h3 style="color:white;">Request Cancellation</h3>
            <form method="POST">
                <input type="hidden" name="order_id" id="cancel_order_id">
                <textarea name="cancellation_reason" rows="4" placeholder="Reason for cancellation" required></textarea>
                <div class="modal-buttons">
                    <button type="submit" name="request_cancellation" style="background:rgba(255,152,0,0.3);color:#ff9800;">Send Request</button>
                    <button type="button" onclick="closeCancelModal()" style="background:rgba(244,67,54,0.3);color:#f44336;">Close</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let cart = [];
        
        function addToCart(id, name, price) { 
            let item = cart.find(i => i.id === id); 
            if (item) {
                item.quantity++; 
            } else { 
                cart.push({ id, name, price, quantity: 1, notes: '' }); 
            } 
            updateCartDisplay(); 
        }
        
        function updateQuantity(id, change) { 
            let item = cart.find(i => i.id === id); 
            if (item) { 
                item.quantity += change; 
                if (item.quantity <= 0) cart = cart.filter(i => i.id !== id); 
            } 
            updateCartDisplay(); 
        }
        
        function removeFromCart(id) { 
            cart = cart.filter(i => i.id !== id); 
            updateCartDisplay(); 
        }
        
        function updateNotes(id, notes) { 
            let item = cart.find(i => i.id === id); 
            if (item) item.notes = notes; 
        }
        
        function updateCartDisplay() {
            const cartDiv = document.getElementById('cartItems'); 
            const totalSpan = document.getElementById('cartTotal'); 
            const countSpan = document.getElementById('itemCount'); 
            const hiddenDiv = document.getElementById('hiddenInputs');
            
            cartDiv.innerHTML = ''; 
            hiddenDiv.innerHTML = ''; 
            let total = 0, count = 0;
            
            if (cart.length === 0) {
                cartDiv.innerHTML = '<div class="empty-cart"><i class="fas fa-shopping-cart"></i><p>No items in cart</p></div>';
            } else {
                cart.forEach(item => { 
                    const itemTotal = item.price * item.quantity; 
                    total += itemTotal; 
                    count += item.quantity; 
                    cartDiv.innerHTML += `
                        <div class="cart-item">
                            <div class="cart-item-header">
                                <span class="cart-item-name">${item.name}</span>
                                <span class="cart-item-price">₱${itemTotal.toFixed(2)}</span>
                            </div>
                            <input type="text" class="cart-item-notes" placeholder="Special instructions..." value="${item.notes.replace(/"/g, '&quot;')}" onchange="updateNotes(${item.id}, this.value)">
                            <div class="cart-item-controls">
                                <button type="button" class="quantity-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                                <span style="color:white;">${item.quantity}</span>
                                <button type="button" class="quantity-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                                <button type="button" class="remove-btn" onclick="removeFromCart(${item.id})"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    `; 
                    hiddenDiv.innerHTML += `
                        <input type="hidden" name="items[]" value="${item.id}">
                        <input type="hidden" name="quantities[]" value="${item.quantity}">
                        <input type="hidden" name="item_notes[]" value="${item.notes.replace(/"/g, '&quot;')}">
                    `; 
                });
            }
            
            totalSpan.textContent = total.toFixed(2); 
            countSpan.textContent = count + ' item' + (count !== 1 ? 's' : '');
        }
        
        function validateOrder() { 
            if (cart.length === 0) { 
                alert('Please add items to the cart'); 
                return false; 
            } 
            const pm = document.querySelector('select[name="payment_method"]').value; 
            if (!pm) { 
                alert('Please select a payment method'); 
                return false; 
            } 
            return true; 
        }
        
        function showTab(tabName) { 
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active')); 
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active')); 
            document.getElementById(tabName + '-tab').classList.add('active'); 
            event.target.classList.add('active'); 
        }
        
        function openCancelRequestModal(orderId) { 
            document.getElementById('cancel_order_id').value = orderId; 
            document.getElementById('cancelRequestModal').style.display = 'flex'; 
        }
        
        function closeCancelModal() { 
            document.getElementById('cancelRequestModal').style.display = 'none'; 
        }
        
        window.onclick = function(e) { 
            if (e.target.classList.contains('modal')) e.target.style.display = 'none'; 
        }
    </script>
</body>
</html>