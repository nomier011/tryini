<?php
session_start();
require_once 'config.php';

requireLogin();

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Get orders based on role
if ($role == 'cashier') {
    $orders = $conn->query("SELECT o.*, u.username as cashier_name 
                            FROM orders o 
                            JOIN users u ON o.cashier_id = u.id 
                            WHERE o.cashier_id = $user_id 
                            ORDER BY o.created_at DESC");
} else {
    $orders = $conn->query("SELECT o.*, u.username as cashier_name 
                            FROM orders o 
                            JOIN users u ON o.cashier_id = u.id 
                            ORDER BY o.created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders | Coffee POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #000;
            min-height: 100vh;
            padding: 20px;
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
        .orders-table {
            width: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(15px);
            overflow-x: auto;
        }
        .orders-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        .orders-table th {
            color: rgba(255,255,255,0.9);
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .orders-table td {
            color: white;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending {
            background: rgba(255,193,7,0.2);
            color: #ffc107;
        }
        .status-preparing {
            background: rgba(33,150,243,0.2);
            color: #2196F3;
        }
        .status-served {
            background: rgba(76,175,80,0.2);
            color: #4CAF50;
        }
        .status-cancelled {
            background: rgba(244,67,54,0.2);
            color: #f44336;
        }
        .view-btn {
            color: #ffd700;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            background: rgba(255,215,0,0.1);
        }
        .no-data {
            text-align: center;
            color: rgba(255,255,255,0.6);
            padding: 40px;
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
                <div class="logo"><i class="fas fa-receipt"></i></div>
                <div class="brand-text">
                    <h1>All Orders</h1>
                </div>
            </div>
        </div>
        
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <?php if($role == 'manager'): ?><th>Cashier</th><?php endif; ?>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date & Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders && $orders->num_rows > 0): ?>
                        <?php while($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $order['order_number']; ?></td>
                            <?php if($role == 'manager'): ?>
                            <td><?php echo htmlspecialchars($order['cashier_name']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></td>
                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo ucfirst($order['payment_method']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="receipt.php?order_id=<?php echo $order['id']; ?>" class="view-btn">
                                    <i class="fas fa-print"></i> Receipt
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $role == 'manager' ? '8' : '7'; ?>" class="no-data">
                                No orders found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>