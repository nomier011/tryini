<?php
session_start();
require_once 'config.php';

requireRole('manager');

// Get date filter
$filter = $_GET['filter'] ?? 'today';
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Build query based on filter
switch($filter) {
    case 'today':
        $date_condition = "DATE(o.created_at) = CURDATE()";
        break;
    case 'yesterday':
        $date_condition = "DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'week':
        $date_condition = "YEARWEEK(o.created_at) = YEARWEEK(CURDATE())";
        break;
    case 'month':
        $date_condition = "MONTH(o.created_at) = MONTH(CURDATE()) AND YEAR(o.created_at) = YEAR(CURDATE())";
        break;
    case 'custom':
        $date_condition = "DATE(o.created_at) BETWEEN '$date_from' AND '$date_to'";
        break;
    default:
        $date_condition = "DATE(o.created_at) = CURDATE()";
}

// Get sales data
$sales_data = $conn->query("
    SELECT 
        DATE(o.created_at) as sale_date,
        COUNT(DISTINCT o.id) as order_count,
        COUNT(DISTINCT o.customer_name) as customer_count,
        SUM(o.total_amount) as total_sales,
        AVG(o.total_amount) as average_order
    FROM orders o
    WHERE $date_condition AND o.order_status != 'cancelled'
    GROUP BY DATE(o.created_at)
");

// Get payment method breakdown
$payment_methods = $conn->query("
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(total_amount) as total
    FROM orders o
    WHERE $date_condition AND o.order_status != 'cancelled'
    GROUP BY payment_method
");

// Get top selling products
$top_products = $conn->query("
    SELECT 
        p.name,
        p.main_category,
        SUM(oi.quantity) as quantity_sold,
        SUM(oi.subtotal) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE $date_condition AND o.order_status != 'cancelled'
    GROUP BY p.id, p.name, p.main_category
    ORDER BY quantity_sold DESC
    LIMIT 10
");

// Get cancelled orders count
$cancelled = $conn->query("
    SELECT COUNT(*) as count, SUM(total_amount) as total
    FROM orders o
    WHERE $date_condition AND o.order_status = 'cancelled'
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report | Coffee POS</title>
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
            flex-wrap: wrap;
            gap: 20px;
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
            font-size: clamp(1.5rem, 4vw, 2rem);
        }
        .filter-section {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(15px);
        }
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        .filter-tab:hover {
            background: rgba(255,255,255,0.2);
        }
        .filter-tab.active {
            background: rgba(255,215,0,0.3);
            color: #ffd700;
        }
        .custom-date-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .date-input {
            flex: 1;
            min-width: 150px;
        }
        .date-input label {
            display: block;
            color: rgba(255,255,255,0.8);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .date-input input {
            width: 100%;
            padding: 8px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 5px;
            color: white;
        }
        .btn-filter {
            padding: 8px 20px;
            background: rgba(255,215,0,0.3);
            border: 1px solid #ffd700;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(15px);
        }
        .summary-label {
            color: rgba(255,255,255,0.7);
            margin-bottom: 10px;
        }
        .summary-value {
            color: #ffd700;
            font-size: 2rem;
            font-weight: 700;
        }
        .summary-value::before {
            content: '₱';
            margin-right: 5px;
        }
        .summary-value.no-currency::before {
            content: none;
        }
        .report-section {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(15px);
        }
        .section-title {
            color: white;
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }
        .report-table th {
            color: rgba(255,255,255,0.9);
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .report-table td {
            color: white;
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .badge {
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
        }
        .badge-drinks {
            background: rgba(33,150,243,0.2);
            color: #2196F3;
        }
        .badge-pastry {
            background: rgba(255,193,7,0.2);
            color: #ffc107;
        }
        .badge-foods {
            background: rgba(76,175,80,0.2);
            color: #4CAF50;
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
                <div class="logo"><i class="fas fa-chart-pie"></i></div>
                <div class="brand-text">
                    <h1>Sales Report</h1>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-tabs">
                <a href="?filter=today" class="filter-tab <?php echo $filter == 'today' ? 'active' : ''; ?>">Today</a>
                <a href="?filter=yesterday" class="filter-tab <?php echo $filter == 'yesterday' ? 'active' : ''; ?>">Yesterday</a>
                <a href="?filter=week" class="filter-tab <?php echo $filter == 'week' ? 'active' : ''; ?>">This Week</a>
                <a href="?filter=month" class="filter-tab <?php echo $filter == 'month' ? 'active' : ''; ?>">This Month</a>
                <a href="?filter=custom" class="filter-tab <?php echo $filter == 'custom' ? 'active' : ''; ?>">Custom Range</a>
            </div>
            
            <?php if($filter == 'custom'): ?>
            <form method="GET" class="custom-date-form">
                <input type="hidden" name="filter" value="custom">
                <div class="date-input">
                    <label>From</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>" required>
                </div>
                <div class="date-input">
                    <label>To</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>" required>
                </div>
                <button type="submit" class="btn-filter">Apply Filter</button>
            </form>
            <?php endif; ?>
        </div>

        <?php if($sales_data && $sales_data->num_rows > 0): 
            $row = $sales_data->fetch_assoc();
        ?>
        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Total Orders</div>
                <div class="summary-value no-currency"><?php echo $row['order_count']; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Customers</div>
                <div class="summary-value no-currency"><?php echo $row['customer_count']; ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Sales</div>
                <div class="summary-value"><?php echo number_format($row['total_sales'], 2); ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Average Order</div>
                <div class="summary-value"><?php echo number_format($row['average_order'], 2); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Payment Methods -->
        <div class="report-section">
            <h2 class="section-title">Payment Methods</h2>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Payment Method</th>
                        <th>Orders</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payment_methods && $payment_methods->num_rows > 0): ?>
                        <?php while($pm = $payment_methods->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo ucfirst($pm['payment_method']); ?></td>
                            <td><?php echo $pm['count']; ?></td>
                            <td>₱<?php echo number_format($pm['total'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: rgba(255,255,255,0.6);">No payment data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Products -->
        <div class="report-section">
            <h2 class="section-title">Top Selling Products</h2>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Quantity Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($top_products && $top_products->num_rows > 0): ?>
                        <?php while($product = $top_products->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $product['main_category']; ?>">
                                    <?php echo ucfirst($product['main_category']); ?>
                                </span>
                            </td>
                            <td><?php echo $product['quantity_sold']; ?></td>
                            <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: rgba(255,255,255,0.6);">No product sales data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Cancelled Orders -->
        <div class="report-section">
            <h2 class="section-title">Cancelled Orders</h2>
            <div style="display: flex; gap: 20px;">
                <div style="flex: 1; background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px;">
                    <div style="color: rgba(255,255,255,0.7); margin-bottom: 5px;">Cancelled Orders</div>
                    <div style="color: #f44336; font-size: 2rem; font-weight: 700;"><?php echo $cancelled['count'] ?? 0; ?></div>
                </div>
                <div style="flex: 1; background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px;">
                    <div style="color: rgba(255,255,255,0.7); margin-bottom: 5px;">Cancelled Amount</div>
                    <div style="color: #f44336; font-size: 2rem; font-weight: 700;">₱<?php echo number_format($cancelled['total'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>