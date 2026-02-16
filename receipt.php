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
        body {
            background: #000;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            font-family: 'Courier New', monospace;
        }

        .receipt {
            background: white;
            max-width: 400px;
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

        .receipt-info {
            margin: 20px 0;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 10px;
            font-size: 0.9rem;
        }

        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
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
        }

        .print-btn {
            background: #000;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            width: 100%;
            font-size: 1.1rem;
            cursor: pointer;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .back-btn {
            background: #666;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            width: 100%;
            margin-top: 10px;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        @media print {
            .print-btn, .back-btn { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <div class="receipt-title">☕ Coffee Shop</div>
            <div>123 Coffee Street</div>
            <div>Tel: (555) 123-4567</div>
        </div>
        
        <div class="receipt-info">
            <div><strong>Order #:</strong> <?php echo $order['order_number']; ?></div>
            <div><strong>Date:</strong> <?php echo date('m/d/Y h:i A', strtotime($order['created_at'])); ?></div>
            <div><strong>Cashier:</strong> <?php echo htmlspecialchars($order['cashier_name']); ?></div>
            <div><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></div>
        </div>
        
        <div class="receipt-items">
            <?php while($item = $items->fetch_assoc()): ?>
            <div class="receipt-item">
                <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['name']); ?></span>
                <span>$<?php echo number_format($item['subtotal'], 2); ?></span>
            </div>
            <?php endwhile; ?>
        </div>
        
        <div class="receipt-total">
            <span>Total</span>
            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
        
        <div style="margin-top: 10px; display: flex; justify-content: space-between;">
            <span>Payment:</span>
            <span><?php echo ucfirst($order['payment_method']); ?></span>
        </div>
        
        <div class="receipt-footer">
            <p>Thank you! Please come again</p>
        </div>
        
        <button onclick="window.print()" class="print-btn">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        
        <a href="<?php echo $_SESSION['role'] == 'cashier' ? 'new-order.php' : 'dashboard.php'; ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</body>
</html>