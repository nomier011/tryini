<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'cashier') {
    logCashierLogout($conn, $_SESSION['user_id']);
}

session_destroy();
header("Location: login.php");
exit();
?>