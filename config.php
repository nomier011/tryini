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

// Function to log user activity
function logActivity($conn, $user_id, $action, $details = '') {
    if (!$conn) return false;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
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