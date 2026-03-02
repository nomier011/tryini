<?php
session_start();
require_once 'config.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Get statistics based on role
if ($role == 'cashier') {
    // Cashier sees only their own stats (excluding cancelled orders)
    $today_sales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE cashier_id = $user_id AND DATE(created_at) = CURDATE() AND order_status != 'cancelled'")->fetch_assoc()['total'];
    $today_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE cashier_id = $user_id AND DATE(created_at) = CURDATE() AND order_status != 'cancelled'")->fetch_assoc()['count'];
    $customers = $conn->query("SELECT COUNT(DISTINCT customer_name) as count FROM orders WHERE cashier_id = $user_id AND DATE(created_at) = CURDATE() AND order_status != 'cancelled'")->fetch_assoc()['count'];
    
    // Recent orders for cashier
    $recent_orders = $conn->query("SELECT o.*, u.username as cashier_name FROM orders o JOIN users u ON o.cashier_id = u.id WHERE o.cashier_id = $user_id ORDER BY o.created_at DESC LIMIT 5");
    
    // Get current session info
    $current_session = $conn->query("SELECT login_time FROM cashier_sessions WHERE user_id = $user_id AND logout_time IS NULL ORDER BY login_time DESC LIMIT 1")->fetch_assoc();
    
} else {
    // Manager sees all stats (excluding cancelled orders)
    $today_sales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'")->fetch_assoc()['total'];
    $today_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'")->fetch_assoc()['count'];
    $customers = $conn->query("SELECT COUNT(DISTINCT customer_name) as count FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'")->fetch_assoc()['count'];
    
    // Recent orders for manager
    $recent_orders = $conn->query("SELECT o.*, u.username as cashier_name FROM orders o JOIN users u ON o.cashier_id = u.id ORDER BY o.created_at DESC LIMIT 5");
    
    // Get cancelled orders count
    $cancelled_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE() AND order_status = 'cancelled'")->fetch_assoc()['count'];
}

// Get popular items (excluding cancelled orders)
$popular_items = $conn->query("
    SELECT p.name, SUM(oi.quantity) as total_sold 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) = CURDATE() AND o.order_status != 'cancelled'
    GROUP BY p.id, p.name 
    ORDER BY total_sold DESC 
    LIMIT 5
");

// Get monthly revenue (excluding cancelled orders)
$monthly_revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND order_status != 'cancelled'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Coffee POS</title>
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
            position: relative;
            overflow-x: hidden;
            background: #000;
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
            padding: 0 15px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            flex-wrap: wrap;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: clamp(15px, 3vw, 20px);
            flex-wrap: wrap;
        }

        .logo {
            background: rgba(255, 255, 255, 0.2);
            width: clamp(50px, 8vw, 60px);
            height: clamp(50px, 8vw, 60px);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: clamp(24px, 4vw, 28px);
        }

        .brand-text h1 {
            color: white;
            font-size: clamp(1.5rem, 4vw, 2rem);
            margin-bottom: 5px;
        }

        .brand-text p {
            color: rgba(255, 255, 255, 0.8);
            font-size: clamp(0.85rem, 2vw, 0.95rem);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: clamp(10px, 2vw, 15px);
            flex-wrap: wrap;
        }

        .user-avatar {
            background: rgba(255, 255, 255, 0.2);
            width: clamp(40px, 6vw, 50px);
            height: clamp(40px, 6vw, 50px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: clamp(16px, 3vw, 20px);
        }

        .user-details h3 {
            color: white;
            font-size: clamp(1rem, 2.5vw, 1.1rem);
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .role-badge {
            background: <?php echo $role == 'manager' ? 'rgba(255, 215, 0, 0.2)' : 'rgba(66, 135, 245, 0.2)'; ?>;
            color: <?php echo $role == 'manager' ? '#ffd700' : '#4287f5'; ?>;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .logout-btn {
            background: rgba(255, 107, 107, 0.2);
            border: 1px solid rgba(255, 107, 107, 0.3);
            color: #ff6b6b;
            padding: clamp(8px, 2vw, 10px) clamp(15px, 3vw, 20px);
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s;
            font-size: clamp(0.9rem, 2vw, 1rem);
            white-space: nowrap;
        }

        .logout-btn:hover {
            background: rgba(255, 107, 107, 0.3);
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: clamp(15px, 3vw, 25px);
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: clamp(20px, 4vw, 25px);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #ffd700, #daa520);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .stat-icon {
            width: clamp(40px, 6vw, 50px);
            height: clamp(40px, 6vw, 50px);
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: clamp(18px, 3vw, 22px);
        }

        .stat-value {
            color: white;
            font-size: clamp(1.8rem, 5vw, 2.2rem);
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-value::before {
            content: '₱';
            margin-right: 5px;
            font-size: clamp(1.4rem, 4vw, 1.8rem);
        }

        .stat-value.no-currency::before {
            content: none;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: clamp(0.85rem, 2vw, 0.95rem);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: clamp(20px, 3vw, 30px);
            margin-bottom: 40px;
        }

        .section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: clamp(20px, 4vw, 30px);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            color: white;
            flex-wrap: wrap;
            gap: 10px;
        }

        .section-header h2 {
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .view-all {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .view-all:hover {
            color: white;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th {
            color: rgba(255, 255, 255, 0.9);
            text-align: left;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .orders-table td {
            color: white;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.95rem;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .status-preparing {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
        }

        .status-served {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        .status-cancelled {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .action-card:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
        }

        .action-icon {
            width: clamp(50px, 8vw, 60px);
            height: clamp(50px, 8vw, 60px);
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: clamp(20px, 4vw, 24px);
            margin: 0 auto 15px;
        }

        .action-card h3 {
            color: white;
            font-size: 1rem;
            margin-bottom: 5px;
        }

        .action-card p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
        }

        .popular-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .popular-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            transition: all 0.3s;
        }

        .popular-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .item-rank {
            width: 35px;
            height: 35px;
            background: rgba(255, 215, 0, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffd700;
            font-weight: 700;
        }

        .item-name {
            color: white;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .item-sales {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
        }

        .time-display {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            backdrop-filter: blur(15px);
        }

        .current-time {
            color: white;
            font-size: clamp(2rem, 6vw, 2.5rem);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .current-date {
            color: rgba(255, 255, 255, 0.8);
            font-size: clamp(1rem, 3vw, 1.1rem);
            margin-bottom: 20px;
        }

        .system-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #6bff8d;
            font-weight: 500;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            background: #6bff8d;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .session-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }

        @keyframes pulse {
            0%,100%{opacity:1}
            50%{opacity:0.5}
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: rgba(255,255,255,0.6);
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 40px;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .brand {
                justify-content: center;
            }
            
            .user-info {
                justify-content: center;
            }
            
            .orders-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="video-background">
        <source src="videos/coffee-bg2.mp4" type="video/mp4">
        <source src="https://assets.mixkit.co/videos/preview/mixkit-steaming-hot-coffee-in-a-cup-2902-large.mp4" type="video/mp4">
    </video>
    
    <div class="video-overlay"></div>
    
    <div class="container">
        <div class="header">
            <div class="brand">
                <div class="logo">
                    <i class="fas fa-coffee"></i>
                </div>
                <div class="brand-text">
                    <h1>Coffee Shop POS</h1>
                    <p><?php echo ucfirst($role); ?> Dashboard</p>
                </div>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <h3>
                        Welcome, <?php echo htmlspecialchars($username); ?>!
                        <span class="role-badge"><?php echo ucfirst($role); ?></span>
                    </h3>
                    <p><?php echo date('l, F j, Y'); ?></p>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($today_sales, 2); ?></div>
                <div class="stat-label">Today's Sales</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                </div>
                <div class="stat-value no-currency"><?php echo $today_orders; ?></div>
                <div class="stat-label">Orders Today</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-value no-currency"><?php echo $customers; ?></div>
                <div class="stat-label">Customers Served</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($monthly_revenue, 2); ?></div>
                <div class="stat-label">Monthly Revenue</div>
            </div>
            
            <?php if($role == 'manager'): ?>
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-ban" style="color: #f44336;"></i></div>
                </div>
                <div class="stat-value no-currency" style="color: #f44336;"><?php echo $cancelled_count; ?></div>
                <div class="stat-label">Cancelled Orders</div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="content-grid">
            <div>
                <div class="section">
                    <div class="section-header">
                        <h2><i class="fas fa-history"></i> Recent Orders</h2>
                        <a href="<?php echo $role == 'cashier' ? 'my-sales.php' : 'orders.php'; ?>" class="view-all">View All</a>
                    </div>
                    
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <?php if($role == 'manager'): ?><th>Cashier</th><?php endif; ?>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                                <?php while($order = $recent_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $order['order_number']; ?></td>
                                    <?php if($role == 'manager'): ?>
                                    <td><?php echo htmlspecialchars($order['cashier_name']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo $role == 'manager' ? '6' : '5'; ?>" style="text-align: center; color: rgba(255,255,255,0.6); padding: 20px;">
                                        No recent orders
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="section">
                    <div class="section-header">
                        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                    </div>
                    
                    <div class="quick-actions">
                        <?php if($role == 'cashier'): ?>
                        <a href="new-order.php" class="action-card">
                            <div class="action-icon"><i class="fas fa-plus-circle"></i></div>
                            <h3>New Order</h3>
                            <p>Create new sale</p>
                        </a>
                        
                        <a href="products.php" class="action-card">
                            <div class="action-icon"><i class="fas fa-box"></i></div>
                            <h3>View Menu</h3>
                            <p>Browse items</p>
                        </a>
                        
                        <a href="my-sales.php" class="action-card">
                            <div class="action-icon"><i class="fas fa-chart-bar"></i></div>
                            <h3>My Sales</h3>
                            <p>View transactions</p>
                        </a>
                        <?php endif; ?>
                        
                        <?php if($role == 'manager'): ?>
                        <a href="manage-products.php" class="action-card">
                            <div class="action-icon"><i class="fas fa-edit"></i></div>
                            <h3>Manage Menu</h3>
                            <p>Add/Edit items</p>
                        </a>
                        
                        <a href="manage-users.php" class="action-card">
                            <div class="action-icon"><i class="fas fa-users-cog"></i></div>
                            <h3>Manage Users</h3>
                            <p>Create/Verify cashiers</p>
                        </a>
                        
                        <a href="cashier-activity.php" class="action-card">
                            <div class="action-icon"><i class="fas fa-clock"></i></div>
                            <h3>Cashier Activity</h3>
                            <p>Monitor logins/logouts</p>
                        </a>
                        
                        <a href="cancelled-orders.php" class="action-card">
                            <div class="action-icon"><i class="fas fa-ban" style="color: #f44336;"></i></div>
                            <h3>Cancelled Orders</h3>
                            <p>View cancelled sales</p>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="section">
                    <div class="section-header">
                        <h2><i class="fas fa-fire"></i> Popular Items</h2>
                    </div>
                    
                    <div class="popular-items">
                        <?php if ($popular_items && $popular_items->num_rows > 0): ?>
                            <?php $rank = 1; while($item = $popular_items->fetch_assoc()): ?>
                            <div class="popular-item">
                                <div class="item-rank"><?php echo $rank++; ?></div>
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-sales"><?php echo $item['total_sold']; ?> sold today</div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="color: rgba(255,255,255,0.6); text-align: center; padding: 20px;">
                                No sales yet today
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="time-display">
                    <div class="current-time" id="currentTime">--:--:--</div>
                    <div class="current-date" id="currentDate">-- --- ----</div>
                    <div class="system-status">
                        <div class="status-indicator"></div>
                        <span>System Online</span>
                    </div>
                    
                    <?php if($role == 'cashier' && isset($current_session)): ?>
                    <div class="session-info">
                        <i class="fas fa-clock"></i> 
                        Logged in since: <?php echo date('h:i A', strtotime($current_session['login_time'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>© <?php echo date('Y'); ?> Coffee Shop POS | Logged in as: <?php echo ucfirst($role); ?></p>
        </div>
    </div>

    <script>
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', { hour12: true });
            document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>