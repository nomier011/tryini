<?php
session_start(); // Start session FIRST
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$username = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!$conn) {
        $error = "Database connection failed. Please check config.php";
    } else {
        // Check if users table exists
        $check_table = $conn->query("SHOW TABLES LIKE 'users'");
        if (!$check_table || $check_table->num_rows == 0) {
            $error = "Database tables not found. Please run the SQL installation script.";
        } else {
            $stmt = $conn->prepare("SELECT id, username, password, role, is_approved FROM users WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    if (!$user['is_approved']) {
                        $error = "Your account is pending approval from a manager.";
                    } else {
                        // Debug: Check if password verification works
                        if (password_verify($password, $user['password'])) {
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['role'] = $user['role'];
                            
                            $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                            if ($update) {
                                $update->bind_param("i", $user['id']);
                                $update->execute();
                                $update->close();
                            }
                            
                            if ($user['role'] == 'cashier') {
                                // Check if function exists before calling
                                if (function_exists('logCashierLogin')) {
                                    logCashierLogin($conn, $user['id']);
                                }
                            }
                            
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $error = "Invalid password.";
                        }
                    }
                } else {
                    $error = "Username not found.";
                }
                $stmt->close();
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Login | Coffee POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; background: #000; position: relative; }
        #video-background { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -2; }
        .video-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: -1; }
        .login-container { width: 100%; max-width: 420px; background: rgba(255,255,255,0.1); border-radius: 20px; padding: clamp(20px, 5vw, 40px); backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.2); }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { font-size: clamp(1.8rem, 6vw, 2.5rem); color: white; display: flex; align-items: center; justify-content: center; gap: 15px; flex-wrap: wrap; }
        .logo h1 i { background: rgba(255,255,255,0.2); width: clamp(50px, 12vw, 60px); height: clamp(50px, 12vw, 60px); border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: clamp(24px, 6vw, 28px); }
        h2 { color: white; text-align: center; margin-bottom: 30px; font-size: clamp(1.4rem, 5vw, 1.8rem); padding-bottom: 10px; border-bottom: 2px solid rgba(255,215,0,0.3); display: inline-block; width: 100%; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; color: rgba(255,255,255,0.9); font-weight: 600; }
        .input-with-icon { position: relative; }
        .input-with-icon i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.8); }
        .form-control { width: 100%; padding: clamp(12px, 3vw, 14px) 15px clamp(12px, 3vw, 14px) 45px; border: 1px solid rgba(255,255,255,0.2); border-radius: 10px; background: rgba(255,255,255,0.1); color: white; font-size: clamp(0.9rem, 3vw, 1rem); }
        .form-control:focus { outline: none; border-color: rgba(255,255,255,0.5); background: rgba(255,255,255,0.15); }
        .btn-login { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: clamp(14px, 3.5vw, 16px); border-radius: 10px; font-size: clamp(1rem, 3.5vw, 1.1rem); font-weight: 600; cursor: pointer; width: 100%; transition: all 0.3s; }
        .btn-login:hover { background: rgba(255,255,255,0.25); transform: translateY(-2px); }
        .error-message { background: rgba(220,53,69,0.2); color: #ff6b6b; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #ff6b6b; display: flex; align-items: center; gap: 12px; }
        .demo-credentials { margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 10px; font-size: 0.85rem; color: rgba(255,255,255,0.7); }
        .demo-credentials p { margin: 5px 0; }
        .demo-credentials strong { color: #ffd700; }
    </style>
</head>
<body>
    <video autoplay muted loop id="video-background"><source src="videos/coffee-bg2.mp4" type="video/mp4"></video>
    <div class="video-overlay"></div>
    
    <div class="login-container">
        <div class="logo"><h1><i class="fas fa-coffee"></i> Coffee POS</h1><p style="color: rgba(255,255,255,0.8); margin-top: 5px;">Point of Sale System</p></div>
        <h2>Sign In</h2>
        
        <?php if ($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i><span><?php echo $error; ?></span></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <div class="input-with-icon"><i class="fas fa-user"></i><input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter username"></div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-with-icon"><i class="fas fa-lock"></i><input type="password" name="password" class="form-control" required placeholder="Enter password"></div>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Sign In</button>
        </form>

        
    </div>
</body>
</html>