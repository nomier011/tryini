<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';
$username = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate passwords match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username or email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if ($check) {
            $check->bind_param("ss", $username, $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "Username or email already exists.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user (default role is cashier)
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'cashier')");
                if ($stmt) {
                    $stmt->bind_param("sss", $username, $email, $hashed_password);
                    
                    if ($stmt->execute()) {
                        $success = "Registration successful! You can now <a href='login.php'>login here</a>";
                        $username = '';
                        $email = '';
                    } else {
                        $error = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $error = "Database error: " . $conn->error;
                }
            }
            $check->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Coffee POS</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
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

        .register-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: floatIn 0.8s ease-out;
        }

        @keyframes floatIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo h1 {
            font-size: 2.3rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .logo h1 i {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            width: 55px;
            height: 55px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        h2 {
            color: white;
            margin-bottom: 25px;
            font-size: 1.7rem;
            text-align: center;
            position: relative;
            padding-bottom: 12px;
        }

        h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, rgba(255,255,255,0.8), rgba(255,255,255,0.4));
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.8);
        }

        .form-control {
            width: 100%;
            padding: 13px 15px 13px 45px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(5px);
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.15);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: all 0.3s;
        }

        .password-requirements {
            margin-top: 8px;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.7);
        }

        .requirement {
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .requirement.valid {
            color: #6bff8d;
        }

        .password-match {
            margin-top: 5px;
            font-size: 0.85rem;
            min-height: 20px;
        }

        .btn-register {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 15px;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            backdrop-filter: blur(5px);
        }

        .btn-register:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-3px);
        }

        .error-message, .success-message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border-left: 4px solid #ff6b6b;
        }

        .success-message {
            background: rgba(40, 167, 69, 0.2);
            color: #6bff8d;
            border-left: 4px solid #6bff8d;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.8);
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .login-link a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="video-background">
        <source src="videos/coffee-bg.mp4" type="video/mp4">
        <source src="https://assets.mixkit.co/videos/preview/mixkit-steaming-hot-coffee-in-a-cup-2902-large.mp4" type="video/mp4">
    </video>
    
    <div class="video-overlay"></div>
    
    <div class="register-container">
        <div class="logo">
            <h1>
                <i class="fas fa-coffee"></i>
                Coffee POS
            </h1>
        </div>
        
        <h2>Create Account</h2>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" class="form-control" required value="<?php echo htmlspecialchars($username); ?>" placeholder="Choose username">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter email">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Create password">
                </div>
                <div class="password-strength">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <div class="password-requirements">
                    <div class="requirement" id="reqLength">
                        <i class="far fa-circle"></i> At least 6 characters
                    </div>
                    <div class="requirement" id="reqUpper">
                        <i class="far fa-circle"></i> Contains uppercase
                    </div>
                    <div class="requirement" id="reqNumber">
                        <i class="far fa-circle"></i> Contains number
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Confirm password">
                </div>
                <div class="password-match" id="passwordMatch"></div>
            </div>
            
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const passwordMatch = document.getElementById('passwordMatch');
        
        passwordInput.addEventListener('input', checkStrength);
        confirmInput.addEventListener('input', checkMatch);
        
        function checkStrength() {
            const password = passwordInput.value;
            let strength = 0;
            
            if (password.length >= 6) {
                strength += 33;
                document.getElementById('reqLength').classList.add('valid');
                document.getElementById('reqLength').innerHTML = '<i class="fas fa-check-circle"></i> At least 6 characters';
            } else {
                document.getElementById('reqLength').classList.remove('valid');
                document.getElementById('reqLength').innerHTML = '<i class="far fa-circle"></i> At least 6 characters';
            }
            
            if (/[A-Z]/.test(password)) {
                strength += 33;
                document.getElementById('reqUpper').classList.add('valid');
                document.getElementById('reqUpper').innerHTML = '<i class="fas fa-check-circle"></i> Contains uppercase';
            } else {
                document.getElementById('reqUpper').classList.remove('valid');
                document.getElementById('reqUpper').innerHTML = '<i class="far fa-circle"></i> Contains uppercase';
            }
            
            if (/[0-9]/.test(password)) {
                strength += 34;
                document.getElementById('reqNumber').classList.add('valid');
                document.getElementById('reqNumber').innerHTML = '<i class="fas fa-check-circle"></i> Contains number';
            } else {
                document.getElementById('reqNumber').classList.remove('valid');
                document.getElementById('reqNumber').innerHTML = '<i class="far fa-circle"></i> Contains number';
            }
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 33) {
                strengthBar.style.background = '#ff4757';
            } else if (strength < 66) {
                strengthBar.style.background = '#ffa502';
            } else {
                strengthBar.style.background = '#2ed573';
            }
            
            checkMatch();
        }
        
        function checkMatch() {
            if (confirmInput.value === '') {
                passwordMatch.textContent = '';
            } else if (passwordInput.value === confirmInput.value) {
                passwordMatch.innerHTML = '<i class="fas fa-check-circle" style="color: #6bff8d;"></i> Passwords match';
            } else {
                passwordMatch.innerHTML = '<i class="fas fa-times-circle" style="color: #ff6b6b;"></i> Passwords do not match';
            }
        }
    </script>
</body>
</html>