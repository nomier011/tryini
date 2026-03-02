<?php
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "pos_db";

// Create connection with error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Set PHP to use Philippine Peso
setlocale(LC_MONETARY, 'en_PH');

// Function to format currency in PHP
function formatMoney($amount) {
    return '₱' . number_format($amount, 2);
}

// Function to log cashier login
function logCashierLogin($conn, $user_id) {
    if (!$conn) return false;
    
    $stmt = $conn->prepare("INSERT INTO cashier_sessions (user_id, login_time) VALUES (?, NOW())");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $session_id = $stmt->insert_id;
        $stmt->close();
        
        // Update last login in users table
        $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        if ($update) {
            $update->bind_param("i", $user_id);
            $update->execute();
            $update->close();
        }
        
        return $session_id;
    }
    return false;
}

// Function to log cashier logout
function logCashierLogout($conn, $user_id) {
    if (!$conn) return false;
    
    $stmt = $conn->prepare("UPDATE cashier_sessions SET logout_time = NOW() WHERE user_id = ? AND logout_time IS NULL ORDER BY login_time DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Update last logout in users table
        $update = $conn->prepare("UPDATE users SET last_logout = NOW() WHERE id = ?");
        if ($update) {
            $update->bind_param("i", $user_id);
            $update->execute();
            $update->close();
        }
        return true;
    }
    return false;
}

// Function to generate order number
function generateOrderNumber($conn) {
    if (!$conn) return 'ORD-' . date('Ymd') . '-0001';
    $date = date('Ymd');
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
    if ($result) {
        $row = $result->fetch_assoc();
        $count = $row['count'] + 1;
        return "ORD-" . $date . "-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
    return "ORD-" . $date . "-0001";
}

// Function to request cancellation (cashier requests, manager approves)
function requestCancellation($conn, $order_id, $cashier_id, $reason) {
    $stmt = $conn->prepare("UPDATE orders SET cancellation_requested = 1, cancellation_reason = ?, cancellation_requested_by = ?, cancellation_requested_at = NOW() WHERE id = ? AND order_status != 'cancelled' AND order_status != 'served'");
    $stmt->bind_param("sii", $reason, $cashier_id, $order_id);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected > 0;
}

// Function to approve cancellation (manager only)
function approveCancellation($conn, $order_id, $manager_id) {
    $conn->begin_transaction();
    
    try {
        // Get order details
        $order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch_assoc();
        
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET order_status = 'cancelled', cancelled_by = ?, cancelled_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $manager_id, $order_id);
        $stmt->execute();
        
        // Restore stock
        $items = $conn->query("SELECT * FROM order_items WHERE order_id = $order_id");
        while ($item = $items->fetch_assoc()) {
            $conn->query("UPDATE products SET stock = stock + {$item['quantity']} WHERE id = {$item['product_id']}");
        }
        
        // Get cashier name
        $cashier = $conn->query("SELECT username FROM users WHERE id = {$order['cashier_id']}")->fetch_assoc();
        $manager = $conn->query("SELECT username FROM users WHERE id = $manager_id")->fetch_assoc();
        
        // Record in cancelled orders
        $stmt = $conn->prepare("INSERT INTO cancelled_orders 
                                (order_id, order_number, cashier_id, cashier_name, customer_name, total_amount, payment_method, cancellation_reason, cancelled_by, cancelled_by_name, original_created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isissdssiss", 
            $order['id'], 
            $order['order_number'], 
            $order['cashier_id'], 
            $cashier['username'], 
            $order['customer_name'], 
            $order['total_amount'], 
            $order['payment_method'], 
            $order['cancellation_reason'], 
            $manager_id, 
            $manager['username'], 
            $order['created_at']
        );
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Function to reject cancellation
function rejectCancellation($conn, $order_id) {
    $stmt = $conn->prepare("UPDATE orders SET cancellation_requested = 0, cancellation_reason = NULL, cancellation_requested_by = NULL, cancellation_requested_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();
    return true;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

// Function to redirect if not authorized
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header("Location: dashboard.php");
        exit();
    }
}
?>