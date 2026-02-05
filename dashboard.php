<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
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

        /* Video Background */
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

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
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
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            background: rgba(255, 255, 255, 0.2);
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .brand-text h1 {
            color: white;
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .brand-text p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            background: rgba(255, 255, 255, 0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-details h3 {
            color: white;
            font-size: 1.1rem;
            margin-bottom: 3px;
        }

        .user-details p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        .logout-btn {
            background: rgba(255, 107, 107, 0.2);
            border: 1px solid rgba(255, 107, 107, 0.3);
            color: #ff6b6b;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s;
            backdrop-filter: blur(5px);
        }

        .logout-btn:hover {
            background: rgba(255, 107, 107, 0.3);
            transform: translateY(-2px);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, 
                rgba(255, 215, 0, 0.8), 
                rgba(218, 165, 32, 0.8));
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-change {
            color: #6bff8d;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stat-change.negative {
            color: #ff6b6b;
        }

        .stat-value {
            color: white;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
        }

        /* Content Sections */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            color: white;
        }

        .section-header h2 {
            font-size: 1.5rem;
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

        /* Orders Table */
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
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-completed {
            background: rgba(107, 255, 141, 0.2);
            color: #6bff8d;
        }

        .status-pending {
            background: rgba(255, 215, 0, 0.2);
            color: #ffd700;
        }

        .status-preparing {
            background: rgba(66, 135, 245, 0.2);
            color: #4287f5;
        }

        /* Popular Items */
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
            font-size: 0.9rem;
        }

        .item-details {
            flex: 1;
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

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
            backdrop-filter: blur(10px);
        }

        .action-card:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .action-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
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

        /* Time Display */
        .time-display {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .current-time {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .current-date {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
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

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 40px;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .orders-table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }
            
            .section {
                padding: 20px;
            }
            
            .stat-value {
                font-size: 1.8rem;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }
    </style>
</head>
<body>
    <!-- Video Background -->
    <video autoplay muted loop id="video-background">
        <source src="videos/coffee-bg.mp4" type="video/mp4">
        <source src="coffee-bg.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    
    <!-- Dark Overlay -->
    <div class="video-overlay"></div>
    
    <!-- Main Container -->
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="brand">
                <div class="logo">
                    <i class="fas fa-coffee"></i>
                </div>
                <div class="brand-text">
                    <h1>Coffee Shop POS</h1>
                    <p>Point of Sale Dashboard</p>
                </div>
            </div>
            
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <h3>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
                    <p><?php echo date('l, F j, Y'); ?></p>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> 12.5%
                    </div>
                </div>
                <div class="stat-value">$1,248.50</div>
                <div class="stat-label">Today's Sales</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> 8.3%
                    </div>
                </div>
                <div class="stat-value">42</div>
                <div class="stat-label">Orders Today</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-down"></i> 2.1%
                    </div>
                </div>
                <div class="stat-value">156</div>
                <div class="stat-label">Customers Served</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-change">
                        <i class="fas fa-arrow-up"></i> 15.7%
                    </div>
                </div>
                <div class="stat-value">$8,452.75</div>
                <div class="stat-label">Monthly Revenue</div>
            </div>
        </div>
        
        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Left Column -->
            <div>
                <!-- Recent Orders -->
                <div class="section" style="margin-bottom: 30px;">
                    <div class="section-header">
                        <h2><i class="fas fa-history"></i> Recent Orders</h2>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#CB-1001</td>
                                <td>John Smith</td>
                                <td>Latte ×2, Croissant</td>
                                <td>$14.50</td>
                                <td><span class="status-badge status-completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>#CB-1002</td>
                                <td>Emma Johnson</td>
                                <td>Espresso, Blueberry Muffin</td>
                                <td>$9.25</td>
                                <td><span class="status-badge status-preparing">Preparing</span></td>
                            </tr>
                            <tr>
                                <td>#CB-1003</td>
                                <td>Michael Brown</td>
                                <td>Cappuccino, Bagel, Iced Coffee</td>
                                <td>$18.75</td>
                                <td><span class="status-badge status-pending">Pending</span></td>
                            </tr>
                            <tr>
                                <td>#CB-1004</td>
                                <td>Sarah Wilson</td>
                                <td>Americano, Chocolate Cake</td>
                                <td>$12.50</td>
                                <td><span class="status-badge status-completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>#CB-1005</td>
                                <td>David Lee</td>
                                <td>Mocha, Sandwich</td>
                                <td>$16.25</td>
                                <td><span class="status-badge status-preparing">Preparing</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Quick Actions -->
                <div class="section">
                    <div class="section-header">
                        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                    </div>
                    
                    <div class="quick-actions">
                        <a href="new-order.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <h3>New Order</h3>
                            <p>Start a new transaction</p>
                        </a>
                        
                        <a href="print-receipt.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-print"></i>
                            </div>
                            <h3>Print Receipt</h3>
                            <p>Print order receipts</p>
                        </a>
                        
                        <a href="manage-menu.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <h3>Manage Menu</h3>
                            <p>Edit menu items</p>
                        </a>
                        
                        <a href="view-reports.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h3>View Reports</h3>
                            <p>Sales analytics</p>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Popular Items -->
                <div class="section" style="margin-bottom: 30px;">
                    <div class="section-header">
                        <h2><i class="fas fa-fire"></i> Popular Items</h2>
                    </div>
                    
                    <div class="popular-items">
                        <div class="popular-item">
                            <div class="item-rank">1</div>
                            <div class="item-details">
                                <div class="item-name">Caramel Latte</div>
                                <div class="item-sales">42 sales today</div>
                            </div>
                        </div>
                        
                        <div class="popular-item">
                            <div class="item-rank">2</div>
                            <div class="item-details">
                                <div class="item-name">Iced Americano</div>
                                <div class="item-sales">38 sales today</div>
                            </div>
                        </div>
                        
                        <div class="popular-item">
                            <div class="item-rank">3</div>
                            <div class="item-details">
                                <div class="item-name">Chocolate Croissant</div>
                                <div class="item-sales">32 sales today</div>
                            </div>
                        </div>
                        
                        <div class="popular-item">
                            <div class="item-rank">4</div>
                            <div class="item-details">
                                <div class="item-name">Frappuccino</div>
                                <div class="item-sales">28 sales today</div>
                            </div>
                        </div>
                        
                        <div class="popular-item">
                            <div class="item-rank">5</div>
                            <div class="item-details">
                                <div class="item-name">Blueberry Muffin</div>
                                <div class="item-sales">25 sales today</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Time Display -->
                <div class="time-display">
                    <div class="current-time" id="currentTime">--:--:--</div>
                    <div class="current-date" id="currentDate">-- --- ----</div>
                    <div class="system-status">
                        <div class="status-indicator"></div>
                        <span>System Status: Online</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>© <?php echo date('Y'); ?> Coffee Shop POS System | All rights reserved</p>
            <p style="margin-top: 5px;">Last updated: Today at <?php echo date('g:i A'); ?></p>
        </div>
    </div>

    <script>
        // Update time display
        function updateTime() {
            const now = new Date();
            
            // Format time
            const timeString = now.toLocaleTimeString('en-US', { 
                hour12: true, 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            });
            
            // Format date
            const dateString = now.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            document.getElementById('currentTime').textContent = timeString;
            document.getElementById('currentDate').textContent = dateString;
        }
        
        // Update immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);
        
        // Animate stats counters
        function animateCounter(element, target, duration = 2000) {
            const start = 0;
            const increment = target / (duration / 16);
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    if (element.textContent.includes('$')) {
                        element.textContent = '$' + target.toFixed(2);
                    } else {
                        element.textContent = Math.floor(target);
                    }
                    clearInterval(timer);
                } else {
                    if (element.textContent.includes('$')) {
                        element.textContent = '$' + current.toFixed(2);
                    } else {
                        element.textContent = Math.floor(current);
                    }
                }
            }, 16);
        }
        
        // Start animations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const statValues = document.querySelectorAll('.stat-value');
                const targets = [1248.50, 42, 156, 8452.75];
                
                statValues.forEach((el, index) => {
                    if (el.textContent.includes('$')) {
                        el.textContent = '$' + targets[index].toFixed(2);
                    } else {
                        animateCounter(el, targets[index]);
                    }
                });
            }, 1000);
            
            // Add hover effects to cards
            const cards = document.querySelectorAll('.stat-card, .action-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Make status badges clickable
            document.querySelectorAll('.status-badge').forEach(badge => {
                badge.addEventListener('click', function() {
                    const status = this.textContent;
                    const orderId = this.closest('tr').querySelector('td').textContent;
                    alert(`Order ${orderId} status: ${status}\nClick to update status.`);
                });
            });
        });
        
        // Video fallback
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('video-background');
            
            video.addEventListener('error', function() {
                console.log('Video failed to load');
                document.body.style.background = 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)';
                document.querySelector('.video-overlay').style.display = 'none';
            });
        });
    </script>
</body>
</html>