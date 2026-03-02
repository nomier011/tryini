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
$pending_requests = $conn->query("
    SELECT o.*, u.username as cashier_name, u2.username as requested_by_name
    FROM orders o
    JOIN users u ON o.cashier_id = u.id
    JOIN users u2 ON o.cancellation_requested_by = u2.id
    WHERE o.cancellation_requested = 1 AND o.order_status != 'cancelled'
    ORDER BY o.cancellation_requested_at DESC
");

// Get all cancelled orders (approved cancellations)
$cancelled_orders = $conn->query("
    SELECT co.* 
    FROM cancelled_orders co
    ORDER BY co.cancelled_at DESC
");

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_cancelled,
        COALESCE(SUM(total_amount), 0) as total_amount,
        COUNT(DISTINCT cashier_id) as cashiers_involved
    FROM cancelled_orders
")->fetch_assoc();

// Get pending requests count
$pending_count = $conn->query("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE cancellation_requested = 1 AND order_status != 'cancelled'
")->fetch_assoc()['count'];

// Get cancellation by reason
$reasons = $conn->query("
    SELECT 
        cancellation_reason,
        COUNT(*) as count,
        SUM(total_amount) as total
    FROM cancelled_orders
    GROUP BY cancellation_reason
    ORDER BY count DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancelled Orders | Coffee POS</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .stat-card.warning {
            border-left: 4px solid #ff9800;
        }

        .stat-card.danger {
            border-left: 4px solid #f44336;
        }

        .stat-card.info {
            border-left: 4px solid #2196F3;
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .stat-value {
            color: #ffd700;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-value::before {
            content: '₱';
            margin-right: 5px;
        }

        .stat-value.no-currency::before {
            content: none;
        }

        .stat-label {
            color: rgba(255,255,255,0.8);
        }

        .section-title {
            color: white;
            margin: 30px 0 20px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #ffd700;
        }

        .section-title .badge {
            background: rgba(255,152,0,0.3);
            color: #ff9800;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-left: 10px;
        }

        /* Pending Requests Grid */
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

        .reasons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .reason-card {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 15px;
            backdrop-filter: blur(15px);
        }

        .reason-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .reason-name {
            color: white;
            font-weight: 600;
        }

        .reason-count {
            color: #ff9800;
            font-weight: 600;
        }

        .reason-total {
            color: #ffd700;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .reason-total::before {
            content: '₱';
            margin-right: 2px;
        }

        .cancelled-table {
            width: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 20px;
            backdrop-filter: blur(15px);
            overflow-x: auto;
        }

        .cancelled-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .cancelled-table th {
            color: rgba(255,255,255,0.9);
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-weight: 600;
        }

        .cancelled-table td {
            color: white;
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .amount {
            color: #f44336;
            font-weight: 600;
        }

        .amount::before {
            content: '₱';
            margin-right: 2px;
        }

        .reason-text {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: rgba(255,255,255,0.9);
        }

        .badge {
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
            background: rgba(255,152,0,0.2);
            color: #ff9800;
        }

        .badge-approved {
            background: rgba(76,175,80,0.2);
            color: #4CAF50;
        }

        .no-data {
            color: rgba(255,255,255,0.6);
            text-align: center;
            padding: 40px;
            font-size: 1.1rem;
        }

        .filter-section {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(15px);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            color: rgba(255,255,255,0.8);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .filter-group input {
            width: 100%;
            padding: 8px 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 5px;
            color: white;
        }

        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 5px;
            color: white;
        }

        .filter-group select option {
            background: #333;
        }

        .btn-filter {
            padding: 8px 20px;
            background: rgba(255,215,0,0.3);
            border: 1px solid #ffd700;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-filter:hover {
            background: rgba(255,215,0,0.4);
        }

        .btn-reset {
            padding: 8px 20px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-reset:hover {
            background: rgba(255,255,255,0.2);
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

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .reasons-grid {
                grid-template-columns: 1fr;
            }
            
            .requests-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-section {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
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
                <div class="logo"><i class="fas fa-ban" style="color: #f44336;"></i></div>
                <div class="brand-text">
                    <h1>Cancelled Orders Management</h1>
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card danger">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-ban"></i></div>
                </div>
                <div class="stat-value no-currency"><?php echo $stats['total_cancelled'] ?? 0; ?></div>
                <div class="stat-label">Total Cancelled Orders</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_amount'] ?? 0, 2); ?></div>
                <div class="stat-label">Total Cancelled Amount</div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                </div>
                <div class="stat-value no-currency"><?php echo $pending_count; ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
                <div class="stat-value no-currency"><?php echo $stats['cashiers_involved'] ?? 0; ?></div>
                <div class="stat-label">Cashiers Involved</div>
            </div>
        </div>

        <!-- Pending Cancellation Requests -->
        <?php if ($pending_requests && $pending_requests->num_rows > 0): ?>
        <h2 class="section-title">
            <i class="fas fa-clock" style="color: #ff9800;"></i> 
            Pending Cancellation Requests
            <span class="badge"><?php echo $pending_requests->num_rows; ?> pending</span>
        </h2>
        
        <div class="requests-grid">
            <?php while($request = $pending_requests->fetch_assoc()): ?>
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
                        <span><i class="fas fa-clock"></i> <?php echo date('M d, h:i A', strtotime($request['cancellation_requested_at'])); ?></span>
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
        </div>
        <?php endif; ?>

        <!-- Cancellation Reasons Breakdown -->
        <h2 class="section-title"><i class="fas fa-chart-pie"></i> Cancellation Reasons</h2>
        <div class="reasons-grid">
            <?php if ($reasons && $reasons->num_rows > 0): ?>
                <?php while($reason = $reasons->fetch_assoc()): ?>
                <div class="reason-card">
                    <div class="reason-header">
                        <span class="reason-name"><?php echo htmlspecialchars($reason['cancellation_reason'] ?: 'No reason provided'); ?></span>
                        <span class="reason-count"><?php echo $reason['count']; ?>x</span>
                    </div>
                    <div class="reason-total"><?php echo number_format($reason['total'], 2); ?></div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">No cancellation data available</div>
            <?php endif; ?>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%; align-items: flex-end;">
                <div class="filter-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>">
                </div>
                <div class="filter-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?php echo $_GET['date_to'] ?? ''; ?>">
                </div>
                <div class="filter-group">
                    <label>Cashier</label>
                    <select name="cashier">
                        <option value="">All Cashiers</option>
                        <?php 
                        $cashiers = $conn->query("SELECT DISTINCT cashier_name FROM cancelled_orders ORDER BY cashier_name");
                        while($c = $cashiers->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $c['cashier_name']; ?>" <?php echo (isset($_GET['cashier']) && $_GET['cashier'] == $c['cashier_name']) ? 'selected' : ''; ?>>
                            <?php echo $c['cashier_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn-filter">Apply Filter</button>
                <a href="cancelled-orders.php" class="btn-reset">Reset</a>
            </form>
        </div>

        <!-- Cancelled Orders List -->
        <h2 class="section-title"><i class="fas fa-list"></i> Approved Cancellations</h2>
        <div class="cancelled-table">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date Cancelled</th>
                        <th>Original Date</th>
                        <th>Cashier</th>
                        <th>Cancelled By</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Apply filters if set
                    $filter_query = "";
                    $params = [];
                    $types = "";
                    
                    if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
                        $filter_query .= " AND DATE(cancelled_at) >= ?";
                        $params[] = $_GET['date_from'];
                        $types .= "s";
                    }
                    
                    if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
                        $filter_query .= " AND DATE(cancelled_at) <= ?";
                        $params[] = $_GET['date_to'];
                        $types .= "s";
                    }
                    
                    if (isset($_GET['cashier']) && !empty($_GET['cashier'])) {
                        $filter_query .= " AND cashier_name = ?";
                        $params[] = $_GET['cashier'];
                        $types .= "s";
                    }
                    
                    $sql = "SELECT * FROM cancelled_orders WHERE 1=1 $filter_query ORDER BY cancelled_at DESC";
                    
                    if (!empty($params)) {
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $filtered_orders = $stmt->get_result();
                    } else {
                        $filtered_orders = $conn->query($sql);
                    }
                    
                    if ($filtered_orders && $filtered_orders->num_rows > 0): 
                        while($order = $filtered_orders->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><strong><?php echo $order['order_number']; ?></strong></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($order['cancelled_at'])); ?></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($order['original_created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($order['cashier_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['cancelled_by_name']); ?> <span class="badge badge-approved">Approved</span></td>
                        <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></td>
                        <td class="amount"><?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><?php echo ucfirst($order['payment_method']); ?></td>
                        <td class="reason-text" title="<?php echo htmlspecialchars($order['cancellation_reason']); ?>">
                            <?php echo htmlspecialchars($order['cancellation_reason'] ?: 'No reason provided'); ?>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                    <tr>
                        <td colspan="9" class="no-data">No cancelled orders found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>