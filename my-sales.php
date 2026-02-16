<?php
session_start();
require_once 'config.php';

requireRole('cashier');

$user_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'today';

$query = "SELECT o.*, COUNT(oi.id) as item_count 
          FROM orders o 
          LEFT JOIN order_items oi ON o.id = oi.order_id 
          WHERE o.cashier_id = $user_id";

switch($filter) {
    case 'today': $query .= " AND DATE(o.created_at) = CURDATE()"; break;
    case 'yesterday': $query .= " AND DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"; break;
    case 'week': $query .= " AND WEEK(o.created_at) = WEEK(CURDATE())"; break;
    case 'month': $query .= " AND MONTH(o.created_at) = MONTH(CURDATE())"; break;
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";
$orders = $conn->query($query);

$total_sales = 0;
$order_count = 0;
if ($orders->num_rows > 0) {
    $orders->data_seek(0);
    while($order = $orders->fetch_assoc()) {
        $total_sales += $order['total_amount'];
        $order_count++;
    }
    $orders->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sales | Coffee POS</title>
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
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-tab {
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            color: white;
            text-decoration: none;
        }
        .filter-tab.active {
            background: rgba(255,215,0,0.3);
            border: 1px solid #ffd700;
        }
        .summary-card {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            backdrop-filter: blur(15px);
        }
        .summary-item {
            text-align: center;
        }
        .summary-label {
            color: rgba(255,255,255,0.8);
            margin-bottom: 5px;
        }
        .summary-value {
            color: #ffd700;
            font-size: 2rem;
            font-weight: 700;
        }
        .orders-table {
            width: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(15px);
        }
        .orders-table th, .orders-table td {
            color: white;
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
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
                <div class="logo"><i class="fas fa-chart-bar"></i></div>
                <div class="brand-text"><h1>My Sales</h1></div>
            </div>
        </div>
        
        <div class="filter-tabs">
            <a href="?filter=today" class="filter-tab <?php echo $filter=='today'?'active':''; ?>">Today</a>
            <a href="?filter=yesterday" class="filter-tab <?php echo $filter=='yesterday'?'active':''; ?>">Yesterday</a>
            <a href="?filter=week" class="filter-tab <?php echo $filter=='week'?'active':''; ?>">This Week</a>
            <a href="?filter=month" class="filter-tab <?php echo $filter=='month'?'active':''; ?>">This Month</a>
        </div>
        
        <div class="summary-card">
            <div class="summary-item">
                <div class="summary-label">Orders</div>
                <div class="summary-value"><?php echo $order_count; ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Sales</div>
                <div class="summary-value">$<?php echo number_format($total_sales,2); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Average</div>
                <div class="summary-value">$<?php echo $order_count>0?number_format($total_sales/$order_count,2):'0.00'; ?></div>
            </div>
        </div>
        
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($order = $orders->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $order['order_number']; ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']??'Walk-in'); ?></td>
                    <td><?php echo $order['item_count']; ?> items</td>
                    <td>$<?php echo number_format($order['total_amount'],2); ?></td>
                    <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                    <td><a href="receipt.php?order_id=<?php echo $order['id']; ?>" style="color:#ffd700;">Receipt</a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>