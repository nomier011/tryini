<?php
session_start();
require_once 'config.php';

requireRole('manager');

$cashiers = $conn->query("
    SELECT 
        u.id, u.username, u.email, u.last_login,
        COUNT(DISTINCT o.id) as order_count,
        COALESCE(SUM(o.total_amount), 0) as total_sales,
        MAX(o.created_at) as last_order
    FROM users u
    LEFT JOIN orders o ON u.id = o.cashier_id AND DATE(o.created_at) = CURDATE()
    WHERE u.role = 'cashier'
    GROUP BY u.id
    ORDER BY total_sales DESC
");

$logs = $conn->query("
    SELECT l.*, u.username 
    FROM activity_logs l
    JOIN users u ON l.user_id = u.id
    WHERE DATE(l.created_at) = CURDATE()
    ORDER BY l.created_at DESC
    LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Activity | Coffee POS</title>
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
        .section-title {
            color: white;
            margin: 30px 0 20px;
            font-size: 1.5rem;
        }
        .cashier-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .cashier-card {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(15px);
        }
        .cashier-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .cashier-avatar {
            width: 50px;
            height: 50px;
            background: rgba(255,215,0,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffd700;
            font-size: 1.2rem;
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-online {
            background: #6bff8d;
            box-shadow: 0 0 10px #6bff8d;
        }
        .status-offline {
            background: #ff6b6b;
        }
        .cashier-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }
        .stat-box {
            text-align: center;
            background: rgba(255,255,255,0.05);
            padding: 10px;
            border-radius: 10px;
        }
        .stat-number {
            color: #ffd700;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .logs-table {
            width: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(15px);
        }
        .logs-table th, .logs-table td {
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
                <div class="logo"><i class="fas fa-users"></i></div>
                <div class="brand-text">
                    <h1>Cashier Activity</h1>
                </div>
            </div>
        </div>
        
        <h2 class="section-title">Today's Cashiers</h2>
        <div class="cashier-grid">
            <?php while($cashier = $cashiers->fetch_assoc()): ?>
            <div class="cashier-card">
                <div class="cashier-header">
                    <div class="cashier-avatar"><i class="fas fa-user"></i></div>
                    <div>
                        <h3 style="color:white;"><?php echo htmlspecialchars($cashier['username']); ?></h3>
                        <p style="color:rgba(255,255,255,0.6);"><?php echo htmlspecialchars($cashier['email']); ?></p>
                    </div>
                </div>
                
                <div style="margin-bottom:10px;">
                    <span class="status-indicator <?php echo $cashier['last_login'] && date('Y-m-d',strtotime($cashier['last_login']))==date('Y-m-d') ? 'status-online' : 'status-offline'; ?>"></span>
                    <span style="color:rgba(255,255,255,0.8);">
                        <?php echo $cashier['last_login'] ? 'Last login: '.date('h:i A',strtotime($cashier['last_login'])) : 'Not logged in'; ?>
                    </span>
                </div>
                
                <div class="cashier-stats">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $cashier['order_count']; ?></div>
                        <div style="color:rgba(255,255,255,0.6);">Orders</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">$<?php echo number_format($cashier['total_sales'],2); ?></div>
                        <div style="color:rgba(255,255,255,0.6);">Sales</div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <h2 class="section-title">Today's Activity Log</h2>
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Cashier</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php while($log = $logs->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('h:i:s A', strtotime($log['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                    <td><?php echo ucwords(str_replace('_',' ',$log['action'])); ?></td>
                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                    <td><?php echo $log['ip_address']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>