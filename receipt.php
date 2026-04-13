<?php
session_start();
require_once 'config.php';

requireLogin();

$order_id = $_GET['order_id'] ?? 0;

// Get order details
$order = $conn->query("
    SELECT o.*, u.username as cashier_name 
    FROM orders o
    JOIN users u ON o.cashier_id = u.id
    WHERE o.id = $order_id
")->fetch_assoc();

if (!$order) {
    header("Location: dashboard.php");
    exit();
}

// Get order items
$items = $conn->query("
    SELECT oi.*, p.name 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $order_id
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt | Coffee POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #000;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            font-family: 'Courier New', monospace;
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

        .receipt {
            background: white;
            max-width: 450px;
            width: 100%;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px dashed #000;
        }

        .receipt-title {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .shop-info {
            font-size: 0.9rem;
            color: #333;
        }

        .receipt-info {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 10px;
            font-size: 0.95rem;
        }

        .receipt-info div {
            margin-bottom: 5px;
        }

        .receipt-items {
            margin-bottom: 20px;
        }

        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.95rem;
            padding: 5px 0;
            border-bottom: 1px dotted #ccc;
        }

        .item-name {
            flex: 2;
        }

        .item-quantity {
            flex: 1;
            text-align: center;
        }

        .item-price {
            flex: 1;
            text-align: right;
        }

        .receipt-total {
            border-top: 2px dashed #000;
            padding-top: 15px;
            margin-top: 15px;
            font-weight: bold;
            font-size: 1.2rem;
            display: flex;
            justify-content: space-between;
        }

        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #000;
            font-size: 0.9rem;
        }

        /* Button container - FIXED ALIGNMENT */
        .button-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .print-btn, .new-order-btn, .dashboard-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .print-btn {
            background: #000;
            color: white;
        }

        .print-btn:hover {
            background: #333;
            transform: translateY(-2px);
        }

        .new-order-btn {
            background: #4CAF50;
            color: white;
        }

        .new-order-btn:hover {
            background: #45a049;
            transform: translateY(-2px);
        }

        .dashboard-btn {
            background: #666;
            color: white;
        }

        .dashboard-btn:hover {
            background: #888;
            transform: translateY(-2px);
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
            margin-left: 5px;
        }

        .status-pending {
            background: rgba(255,193,7,0.2);
            color: #856404;
        }

        .status-preparing {
            background: rgba(33,150,243,0.2);
            color: #0c5460;
        }

        .status-served {
            background: rgba(76,175,80,0.2);
            color: #155724;
        }

        .status-cancelled {
            background: rgba(244,67,54,0.2);
            color: #721c24;
        }

        @media print {
            .print-btn, .new-order-btn, .dashboard-btn, .button-container, #video-background, .video-overlay {
                display: none;
            }
            body {
                background: white;
                padding: 0;
            }
            .receipt {
                box-shadow: none;
                padding: 15px;
                max-width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .receipt {
                padding: 20px;
            }
            
            .receipt-title {
                font-size: 1.5rem;
            }
            
            .print-btn, .new-order-btn, .dashboard-btn {
                padding: 12px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="video-background">
        <source src="videos/coffee-bg2.mp4" type="video/mp4">
    </video>
    <div class="video-overlay"></div>
    
    <div class="receipt" id="receipt">
        <div class="receipt-header">
            <div class="receipt-title">☕ Coffee Shop</div>
            <div class="shop-info">123 Coffee Street</div>
            <div class="shop-info">Tel: (555) 123-4567</div>
            <div class="shop-info">VAT Reg TIN: 123-456-789</div>
        </div>
        
        <div class="receipt-info">
            <div><strong>Order #:</strong> <?php echo $order['order_number']; ?></div>
            <div><strong>Date:</strong> <?php echo date('F j, Y h:i A', strtotime($order['created_at'])); ?></div>
            <div><strong>Cashier:</strong> <?php echo htmlspecialchars($order['cashier_name']); ?></div>
            <div><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></div>
            <div><strong>Status:</strong> 
                <span class="status-badge status-<?php echo $order['order_status']; ?>">
                    <?php echo ucfirst($order['order_status']); ?>
                </span>
            </div>
        </div>
        
        <div class="receipt-items">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 1px solid #ccc; font-weight: bold;">
                <span style="flex: 2;">Item</span>
                <span style="flex: 1; text-align: center;">Qty</span>
                <span style="flex: 1; text-align: right;">Total</span>
            </div>
            <?php while($item = $items->fetch_assoc()): ?>
            <div class="receipt-item">
                <span class="item-name"><?php echo htmlspecialchars($item['name']); ?></span>
                <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                <span class="item-price">₱<?php echo number_format($item['subtotal'], 2); ?></span>
            </div>
            <?php if(!empty($item['notes'])): ?>
            <div style="font-size: 0.75rem; color: #666; margin-bottom: 5px; padding-left: 10px;">
                <i class="fas fa-comment"></i> <?php echo htmlspecialchars($item['notes']); ?>
            </div>
            <?php endif; ?>
            <?php endwhile; ?>
        </div>
        
        <div class="receipt-total">
            <span>TOTAL AMOUNT</span>
            <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
        
        <div style="margin-top: 15px; display: flex; justify-content: space-between; font-size: 0.95rem;">
            <span>Payment Method:</span>
            <span><strong><?php echo strtoupper($order['payment_method']); ?></strong></span>
        </div>
        
        <?php if($order['order_status'] == 'cancelled'): ?>
        <div style="margin-top: 15px; padding: 10px; background: rgba(244,67,54,0.1); border-radius: 5px; color: #721c24;">
            <i class="fas fa-ban"></i> This order has been cancelled
        </div>
        <?php endif; ?>
        
        <div class="receipt-footer">
            <p>Thank you for your business!</p>
            <p>Please come again</p>
            <p style="font-size: 0.8rem; margin-top: 10px;">This serves as an official receipt</p>
        </div>
        
        <!-- Button Container - Fixed alignment -->
        <div class="button-container">
            <button onclick="window.print()" class="print-btn">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            
            <?php if($_SESSION['role'] == 'cashier'): ?>
            <a href="new-order.php" class="new-order-btn">
                <i class="fas fa-plus-circle"></i> New Order
            </a>
            <?php else: ?>
            <a href="dashboard.php" class="dashboard-btn">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>