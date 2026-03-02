<?php
session_start();
require_once 'config.php';

requireRole('manager');

// Handle cancellation approvals/rejections
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve_cancellation'])) {
        $order_id = $_POST['order_id'];
        if (approveCancellation($conn, $order_id, $_SESSION['user_id'])) {
            $success = "Order cancelled successfully";
        } else {
            $error = "Error cancelling order";
        }
    }
    
    if (isset($_POST['reject_cancellation'])) {
        $order_id = $_POST['order_id'];
        if (rejectCancellation($conn, $order_id)) {
            $success = "Cancellation request rejected";
        } else {
            $error = "Error rejecting request";
        }
    }
}

// Get pending cancellation requests
$cancellation_requests = $conn->query("
    SELECT o.*, u.username as cashier_name, u2.username as requested_by_name
    FROM orders o
    JOIN users u ON o.cashier_id = u.id
    JOIN users u2 ON o.cancellation_requested_by = u2.id
    WHERE o.cancellation_requested = 1 AND o.order_status != 'cancelled'
    ORDER BY o.cancellation_requested_at DESC
");

// Get cashier sessions
$sessions = $conn->query("
    SELECT 
        cs.*,
        u.username,
        u.email
    FROM cashier_sessions cs
    JOIN users u ON cs.user_id = u.id
    WHERE DATE(cs.login_time) = CURDATE()
    ORDER BY cs.login_time DESC
");

// Get today's cashiers with their stats
$cashiers = $conn->query("
    SELECT 
        u.id,
        u.username,
        u.email,
        u.last_login,
        u.last_logout,
        COUNT(DISTINCT o.id) as order_count,
        COALESCE(SUM(o.total_amount), 0) as total_sales
    FROM users u
    LEFT JOIN orders o ON u.id = o.cashier_id AND DATE(o.created_at) = CURDATE() AND o.order_status != 'cancelled'
    WHERE u.role = 'cashier'
    GROUP BY u.id
    ORDER BY total_sales DESC
");

// Get cancelled orders for today
$cancelled_orders = $conn->query("
    SELECT co.* 
    FROM cancelled_orders co
    WHERE DATE(co.cancelled_at) = CURDATE()
    ORDER BY co.cancelled_at DESC
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
            overflow-x: hidden;
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
            flex-wrap: wrap;
        }

        .brand a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            transition: all 0.3s;
        }

        .brand a:hover {
            background: rgba(255,255,255,0.2);
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

        .section-title {
            color: white;
            margin: 30px 0 20px;
            font-size: clamp(1.2rem, 3vw, 1.5rem);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #ffd700;
        }

        /* Cancellation Requests */
        .requests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .request-card {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(15px);
            border-left: 4px solid #ff9800;
            position: relative;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .request-order-number {
            color: #ffd700;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .request-badge {
            background: rgba(255,152,0,0.2);
            color: #ff9800;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
        }

        .request-details {
            margin: 15px 0;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
        }

        .request-reason {
            color: white;
            font-style: italic;
            margin: 10px 0;
            padding: 10px;
            background: rgba(255,255,255,0.05);
            border-radius: 5px;
        }

        .request-meta {
            display: flex;
            justify-content: space-between;
            color: rgba(255,255,255,0.6);
            font-size: 0.85rem;
            margin: 10px 0;
        }

        .request-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-approve {
            flex: 1;
            padding: 10px;
            background: rgba(76,175,80,0.3);
            color: #4CAF50;
            border: 1px solid #4CAF50;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-approve:hover {
            background: rgba(76,175,80,0.4);
            transform: translateY(-2px);
        }

        .btn-reject {
            flex: 1;
            padding: 10px;
            background: rgba(244,67,54,0.3);
            color: #f44336;
            border: 1px solid #f44336;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-reject:hover {
            background: rgba(244,67,54,0.4);
            transform: translateY(-2px);
        }

        /* Cashier Grid */
        .cashier-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .cashier-card {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s;
        }

        .cashier-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.15);
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

        .cashier-info h3 {
            color: white;
            margin-bottom: 3px;
        }

        .cashier-info p {
            color: rgba(255,255,255,0.6);
            font-size: 0.9rem;
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

        .time-info {
            margin: 10px 0;
            padding: 10px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
        }

        .time-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }

        .time-label {
            color: rgba(255,255,255,0.6);
        }

        .time-value {
            color: #ffd700;
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

        .stat-number::before {
            content: '₱';
            margin-right: 2px;
        }

        .stat-label {
            color: rgba(255,255,255,0.6);
            font-size: 0.8rem;
        }

        /* Sessions Table */
        .sessions-table {
            width: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(15px);
            overflow-x: auto;
            margin-bottom: 40px;
        }

        .sessions-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .sessions-table th {
            color: rgba(255,255,255,0.9);
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-weight: 600;
        }

        .sessions-table td {
            color: white;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .duration-badge {
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.85rem;
        }

        .duration-active {
            background: rgba(107,255,141,0.2);
            color: #6bff8d;
        }

        .duration-ended {
            background: rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.8);
        }

        /* Cancelled Orders */
        .cancelled-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .cancelled-card {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 15px;
            backdrop-filter: blur(15px);
            border-left: 4px solid #f44336;
        }

        .cancelled-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .cancelled-order-number {
            color: #ffd700;
            font-weight: 600;
        }

        .cancelled-amount {
            color: #f44336;
            font-weight: 600;
        }

        .cancelled-amount::before {
            content: '₱';
            margin-right: 2px;
        }

        .cancelled-details {
            margin: 10px 0;
            padding: 10px;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
        }

        .cancelled-reason {
            color: rgba(255,255,255,0.8);
            font-style: italic;
            margin: 5px 0;
        }

        .cancelled-meta {
            display: flex;
            justify-content: space-between;
            color: rgba(255,255,255,0.6);
            font-size: 0.85rem;
            margin-top: 10px;
        }

        .no-data {
            color: rgba(255,255,255,0.6);
            text-align: center;
            padding: 40px;
            font-size: 1.1rem;
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
        }

        @media (max-width: 768px) {
            .requests-grid,
            .cashier-grid,
            .cancelled-grid {
                grid-template-columns: 1fr;
            }
            
            .request-actions {
                flex-direction: column;
            }
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
                <div class="logo"><i class="fas fa-users"></i></div>
                <div class="brand-text">
                    <h1>Cashier Activity & Approvals</h1>
                </div>
            </div>
        </div>
        
        <?php if(isset($success)): ?>
        <div class="message success-message">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
        <div class="message error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <!-- Pending Cancellation Requests -->
        <h2 class="section-title"><i class="fas fa-clock" style="color: #ff9800;"></i> Pending Cancellation Requests</h2>
        <div class="requests-grid">
            <?php if ($cancellation_requests && $cancellation_requests->num_rows > 0): ?>
                <?php while($request = $cancellation_requests->fetch_assoc()): ?>
                <div class="request-card">
                    <div class="request-header">
                        <span class="request-order-number"><?php echo $request['order_number']; ?></span>
                        <span class="request-badge">Awaiting Approval</span>
                    </div>
                    
                    <div class="request-details">
                        <div style="color: rgba(255,255,255,0.8); margin-bottom: 5px;">
                            <strong>Cashier:</strong> <?php echo htmlspecialchars($request['cashier_name']); ?>
                        </div>
                        <div style="color: rgba(255,255,255,0.8); margin-bottom: 5px;">
                            <strong>Customer:</strong> <?php echo htmlspecialchars($request['customer_name'] ?? 'Walk-in'); ?>
                        </div>
                        <div style="color: #ffd700; margin-bottom: 5px;">
                            <strong>Amount:</strong> ₱<?php echo number_format($request['total_amount'], 2); ?>
                        </div>
                        
                        <div class="request-reason">
                            <strong>Reason:</strong> <?php echo htmlspecialchars($request['cancellation_reason']); ?>
                        </div>
                        
                        <div class="request-meta">
                            <span><i class="fas fa-user"></i> Requested by: <?php echo htmlspecialchars($request['requested_by_name']); ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($request['cancellation_requested_at'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="request-actions">
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="order_id" value="<?php echo $request['id']; ?>">
                            <button type="submit" name="approve_cancellation" class="btn-approve" onclick="return confirm('Approve this cancellation? Stock will be restored.')">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form>
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="order_id" value="<?php echo $request['id']; ?>">
                            <button type="submit" name="reject_cancellation" class="btn-reject" onclick="return confirm('Reject this cancellation request?')">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">No pending cancellation requests</div>
            <?php endif; ?>
        </div>
        
        <!-- Today's Cashiers -->
        <h2 class="section-title"><i class="fas fa-user-clock"></i> Today's Cashiers</h2>
        <div class="cashier-grid">
            <?php if ($cashiers && $cashiers->num_rows > 0): ?>
                <?php while($cashier = $cashiers->fetch_assoc()): ?>
                <div class="cashier-card">
                    <div class="cashier-header">
                        <div class="cashier-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="cashier-info">
                            <h3><?php echo htmlspecialchars($cashier['username']); ?></h3>
                            <p><?php echo htmlspecialchars($cashier['email']); ?></p>
                        </div>
                    </div>
                    
                    <div class="time-info">
                        <div class="time-item">
                            <span class="time-label"><i class="fas fa-sign-in-alt"></i> Login:</span>
                            <span class="time-value"><?php echo $cashier['last_login'] ? date('h:i A', strtotime($cashier['last_login'])) : 'Not logged in'; ?></span>
                        </div>
                        <div class="time-item">
                            <span class="time-label"><i class="fas fa-sign-out-alt"></i> Logout:</span>
                            <span class="time-value"><?php echo $cashier['last_logout'] ? date('h:i A', strtotime($cashier['last_logout'])) : 'Still active'; ?></span>
                        </div>
                    </div>
                    
                    <div class="cashier-stats">
                        <div class="stat-box">
                            <div class="stat-number" style="color: #ffd700;"><?php echo $cashier['order_count']; ?></div>
                            <div class="stat-label">Orders Today</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo number_format($cashier['total_sales'], 2); ?></div>
                            <div class="stat-label">Sales Today</div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">No cashier activity today</div>
            <?php endif; ?>
        </div>
        
        <!-- Detailed Sessions -->
        <h2 class="section-title"><i class="fas fa-history"></i> Login/Logout History</h2>
        <div class="sessions-table">
            <table>
                <thead>
                    <tr>
                        <th>Cashier</th>
                        <th>Email</th>
                        <th>Login Time</th>
                        <th>Logout Time</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($sessions && $sessions->num_rows > 0): ?>
                        <?php while($session = $sessions->fetch_assoc()): 
                            $login = new DateTime($session['login_time']);
                            $logout = $session['logout_time'] ? new DateTime($session['logout_time']) : null;
                            $duration = $logout ? $login->diff($logout) : null;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($session['username']); ?></td>
                            <td><?php echo htmlspecialchars($session['email']); ?></td>
                            <td><?php echo date('h:i:s A', strtotime($session['login_time'])); ?></td>
                            <td>
                                <?php if ($session['logout_time']): ?>
                                    <?php echo date('h:i:s A', strtotime($session['logout_time'])); ?>
                                <?php else: ?>
                                    <span class="duration-badge duration-active">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($duration): ?>
                                    <span class="duration-badge duration-ended">
                                        <?php echo $duration->h ?>h <?php echo $duration->i ?>m
                                    </span>
                                <?php elseif (!$session['logout_time']): ?>
                                    <span class="duration-badge duration-active">Ongoing</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-data">No sessions recorded today</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Cancelled Orders -->
        <h2 class="section-title"><i class="fas fa-ban" style="color: #f44336;"></i> Cancelled Orders Today</h2>
        <div class="cancelled-grid">
            <?php if ($cancelled_orders && $cancelled_orders->num_rows > 0): ?>
                <?php while($order = $cancelled_orders->fetch_assoc()): ?>
                <div class="cancelled-card">
                    <div class="cancelled-header">
                        <span class="cancelled-order-number"><?php echo $order['order_number']; ?></span>
                        <span class="cancelled-amount"><?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                    <div class="cancelled-details">
                        <div style="color: rgba(255,255,255,0.8); margin-bottom: 5px;">
                            <strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?>
                        </div>
                        <div style="color: rgba(255,255,255,0.8); margin-bottom: 5px;">
                            <strong>Cashier:</strong> <?php echo htmlspecialchars($order['cashier_name']); ?>
                        </div>
                        <div class="cancelled-reason">
                            <strong>Reason:</strong> <?php echo htmlspecialchars($order['cancellation_reason']); ?>
                        </div>
                    </div>
                    <div class="cancelled-meta">
                        <span><i class="fas fa-user"></i> Cancelled by: <?php echo htmlspecialchars($order['cancelled_by_name']); ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($order['cancelled_at'])); ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">No cancelled orders today</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>