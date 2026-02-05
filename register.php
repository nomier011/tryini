<?php
session_start();
include 'config.php';

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
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            // Hash password before storing
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success = "Registration successful! You can now <a href='login.php' style='color: white; text-decoration: underline;'>login here</a>";
                $username = '';
                $email = '';
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | SecureApp</title>
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

        /* Video Background - Full Screen */
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
            background: rgba(0, 0, 0, 0.3);
            z-index: -1;
        }

        /* Transparent Floating Register Form */
        .register-container {
            width: 100%;
            max-width: 450px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.2),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            animation: floatIn 0.8s ease-out;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        /* Glass effect overlay */
        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.4), 
                transparent);
        }

        .register-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                135deg,
                rgba(255, 255, 255, 0.05) 0%,
                rgba(255, 255, 255, 0.02) 100%
            );
            z-index: -1;
            pointer-events: none;
        }

        @keyframes floatIn {
            from { 
                opacity: 0; 
                transform: translateY(30px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }

        /* Logo */
        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo h1 {
            font-size: 2.3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
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
            box-shadow: 
                0 5px 15px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            font-weight: 500;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        /* Form Title */
        h2 {
            color: white;
            margin-bottom: 25px;
            font-size: 1.7rem;
            font-weight: 700;
            text-align: center;
            position: relative;
            padding-bottom: 12px;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, 
                rgba(255, 255, 255, 0.8), 
                rgba(255, 255, 255, 0.4));
            border-radius: 2px;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            font-size: 0.9rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
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
            font-size: 1.1rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .form-control {
            width: 100%;
            padding: 13px 15px 13px 45px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(5px);
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 
                0 0 0 3px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .password-requirements {
            margin-top: 8px;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .requirement {
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .requirement i {
            font-size: 0.8rem;
        }

        .requirement.valid {
            color: rgba(100, 255, 100, 0.9);
        }

        /* Password match indicator */
        .password-match {
            margin-top: 5px;
            font-size: 0.85rem;
            min-height: 20px;
        }

        .password-match i {
            margin-right: 5px;
        }

        /* Register Button */
        .btn-register {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 15px;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
            letter-spacing: 0.5px;
            box-shadow: 
                0 4px 15px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            backdrop-filter: blur(5px);
            position: relative;
            overflow: hidden;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: left 0.5s;
        }

        .btn-register:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-3px);
            box-shadow: 
                0 8px 25px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:active {
            transform: translateY(-1px);
        }

        /* Messages */
        .error-message {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #ff6b6b;
            display: <?php echo $error ? 'flex' : 'none'; ?>;
            align-items: center;
            gap: 12px;
            animation: shake 0.5s;
            font-weight: 500;
            font-size: 0.9rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        .success-message {
            background: rgba(40, 167, 69, 0.2);
            color: #6bff8d;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #6bff8d;
            display: <?php echo $success ? 'flex' : 'none'; ?>;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.5s;
            font-weight: 500;
            font-size: 0.9rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(107, 255, 141, 0.3);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Login Link */
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .login-link a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
            padding-bottom: 3px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .login-link a:hover {
            color: white;
        }

        .login-link a:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: rgba(255, 255, 255, 0.8);
            transition: width 0.3s;
        }

        .login-link a:hover:after {
            width: 100%;
        }

        /* Features Toggle */
        .features-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 
                0 5px 15px rgba(0, 0, 0, 0.2),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .features-toggle:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.1);
        }

        /* Features Panel */
        .features-panel {
            position: fixed;
            bottom: 90px;
            right: 30px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            padding: 25px;
            width: 320px;
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.2),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s;
            pointer-events: none;
            z-index: 99;
        }

        .features-panel.show {
            opacity: 1;
            transform: translateY(0);
            pointer-events: all;
        }

        .features-panel h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.3rem;
            text-align: center;
            font-weight: 700;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 15px;
        }

        .feature-item i {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            width: 35px;
            height: 35px;
            min-width: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .feature-text h4 {
            font-size: 1rem;
            color: white;
            margin-bottom: 3px;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .feature-text p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.4;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .features-note {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            body {
                padding: 15px;
                background: #000;
            }
            
            .register-container {
                padding: 30px 20px;
                max-width: 100%;
                backdrop-filter: blur(20px);
            }
            
            .logo h1 {
                font-size: 2rem;
            }
            
            .logo h1 i {
                width: 50px;
                height: 50px;
                font-size: 22px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .features-panel {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                border-radius: 20px 20px 0 0;
                padding: 20px;
                transform: translateY(100%);
                backdrop-filter: blur(20px);
            }
            
            .features-panel.show {
                transform: translateY(0);
            }
            
            .features-toggle {
                bottom: 20px;
                right: 20px;
            }
        }

        /* Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .register-container {
            animation: floatIn 0.8s ease-out, float 6s ease-in-out infinite;
            animation-delay: 0s, 1s;
        }

        /* Subtle glow effect */
        .glow-effect {
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(
                45deg,
                rgba(255, 255, 255, 0.1),
                rgba(255, 255, 255, 0.05),
                rgba(255, 255, 255, 0.1)
            );
            border-radius: 22px;
            z-index: -1;
            filter: blur(10px);
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Video Background -->
    <video autoplay muted loop id="video-background">
        <source src="videos/coffee-bg.mp4" type="video/mp4">
        <source src="coffee-bg.mp4" type="video/mp4">
        <!-- Fallback online video -->
        <source src="https://assets.mixkit.co/videos/preview/mixkit-steaming-hot-coffee-in-a-cup-2902-large.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    
    <!-- Subtle overlay for contrast -->
    <div class="video-overlay"></div>
    
    <!-- Glow effect for form -->
    <div class="glow-effect"></div>
    
    <!-- Transparent Floating Register Form -->
    <div class="register-container">
        <div class="logo">
            <h1>
                <i class="fas fa-shield-alt"></i>
                SecureApp
            </h1>
            <p>Create your secure account</p>
        </div>
        
        <h2>Create Your Account</h2>
        
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
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           required 
                           value="<?php echo htmlspecialchars($username); ?>"
                           placeholder="Choose a username"
                           minlength="3"
                           maxlength="20">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           required 
                           value="<?php echo htmlspecialchars($email); ?>"
                           placeholder="Enter your email">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required 
                           placeholder="Create a password"
                           minlength="6">
                </div>
                <div class="password-strength">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <div class="password-requirements">
                    <div class="requirement" id="reqLength">
                        <i class="far fa-circle"></i> At least 6 characters
                    </div>
                    <div class="requirement" id="reqUpper">
                        <i class="far fa-circle"></i> Contains uppercase letter
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
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           class="form-control" 
                           required 
                           placeholder="Confirm your password">
                </div>
                <div class="password-match" id="passwordMatch"></div>
            </div>
            
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
    
    <!-- Features Toggle Button -->
    <div class="features-toggle" id="featuresToggle">
        <i class="fas fa-info"></i>
    </div>
    
    <!-- Features Panel (Hidden by default) -->
    <div class="features-panel" id="featuresPanel">
        <h3>SecureApp Features</h3>
        
        <div class="feature-item">
            <i class="fas fa-check-circle"></i>
            <div class="feature-text">
                <h4>Secure Access</h4>
                <p>Protected user authentication with advanced security</p>
            </div>
        </div>
        
        <div class="feature-item">
            <i class="fas fa-bolt"></i>
            <div class="feature-text">
                <h4>Fast Dashboard</h4>
                <p>Quick access to your data and analytics</p>
            </div>
        </div>
        
        <div class="feature-item">
            <i class="fas fa-mobile-alt"></i>
            <div class="feature-text">
                <h4>Mobile Friendly</h4>
                <p>Access from any device, anywhere</p>
            </div>
        </div>
        
        <div class="feature-item">
            <i class="fas fa-headset"></i>
            <div class="feature-text">
                <h4>24/7 Support</h4>
                <p>Always here to help you with any issues</p>
            </div>
        </div>
        
        <div class="features-note">
            <i class="fas fa-info-circle"></i>
            Create your account to access all features
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('video-background');
            const featuresToggle = document.getElementById('featuresToggle');
            const featuresPanel = document.getElementById('featuresPanel');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthBar = document.getElementById('strengthBar');
            const passwordMatch = document.getElementById('passwordMatch');
            
            // Video fallback handling
            video.addEventListener('error', function() {
                console.log('Video failed to load');
                document.body.style.background = 'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)';
                document.querySelector('.video-overlay').style.display = 'none';
            });
            
            // Toggle features panel
            featuresToggle.addEventListener('click', function() {
                featuresPanel.classList.toggle('show');
                this.innerHTML = featuresPanel.classList.contains('show') 
                    ? '<i class="fas fa-times"></i>' 
                    : '<i class="fas fa-info"></i>';
            });
            
            // Close features panel when clicking outside
            document.addEventListener('click', function(event) {
                if (!featuresPanel.contains(event.target) && 
                    !featuresToggle.contains(event.target) && 
                    featuresPanel.classList.contains('show')) {
                    featuresPanel.classList.remove('show');
                    featuresToggle.innerHTML = '<i class="fas fa-info"></i>';
                }
            });
            
            // Password strength checker
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Check length
                if (password.length >= 6) {
                    strength += 33;
                    document.getElementById('reqLength').classList.add('valid');
                    document.getElementById('reqLength').innerHTML = '<i class="fas fa-check-circle"></i> At least 6 characters';
                } else {
                    document.getElementById('reqLength').classList.remove('valid');
                    document.getElementById('reqLength').innerHTML = '<i class="far fa-circle"></i> At least 6 characters';
                }
                
                // Check uppercase
                if (/[A-Z]/.test(password)) {
                    strength += 33;
                    document.getElementById('reqUpper').classList.add('valid');
                    document.getElementById('reqUpper').innerHTML = '<i class="fas fa-check-circle"></i> Contains uppercase letter';
                } else {
                    document.getElementById('reqUpper').classList.remove('valid');
                    document.getElementById('reqUpper').innerHTML = '<i class="far fa-circle"></i> Contains uppercase letter';
                }
                
                // Check number
                if (/[0-9]/.test(password)) {
                    strength += 34;
                    document.getElementById('reqNumber').classList.add('valid');
                    document.getElementById('reqNumber').innerHTML = '<i class="fas fa-check-circle"></i> Contains number';
                } else {
                    document.getElementById('reqNumber').classList.remove('valid');
                    document.getElementById('reqNumber').innerHTML = '<i class="far fa-circle"></i> Contains number';
                }
                
                // Update strength bar
                strengthBar.style.width = strength + '%';
                
                // Update color based on strength
                if (strength < 33) {
                    strengthBar.style.background = 'linear-gradient(to right, #ff4757, #ff6b81)';
                } else if (strength < 66) {
                    strengthBar.style.background = 'linear-gradient(to right, #ffa502, #ffbe76)';
                } else {
                    strengthBar.style.background = 'linear-gradient(to right, #2ed573, #7bed9f)';
                }
                
                // Check password match
                checkPasswordMatch();
            });
            
            // Password confirmation check
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword === '') {
                    passwordMatch.textContent = '';
                    passwordMatch.style.color = '';
                } else if (password === confirmPassword) {
                    passwordMatch.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                    passwordMatch.style.color = '#6bff8d';
                } else {
                    passwordMatch.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
                    passwordMatch.style.color = '#ff6b6b';
                }
            }
            
            // Form submission handler
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                // Check if passwords match
                if (password !== confirmPassword) {
                    e.preventDefault();
                    passwordMatch.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please fix password mismatch before submitting';
                    passwordMatch.style.color = '#ff6b6b';
                    confirmPasswordInput.focus();
                    return false;
                }
                
                const submitBtn = document.querySelector('.btn-register');
                const originalHTML = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
                submitBtn.disabled = true;
            });
            
            // Input focus effects with glow
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.querySelector('i').style.color = 'rgba(255, 255, 255, 1)';
                    this.style.boxShadow = 
                        '0 0 0 3px rgba(255, 255, 255, 0.15), ' +
                        'inset 0 1px 0 rgba(255, 255, 255, 0.1)';
                    this.style.borderColor = 'rgba(255, 255, 255, 0.6)';
                    this.style.background = 'rgba(255, 255, 255, 0.2)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.querySelector('i').style.color = 'rgba(255, 255, 255, 0.8)';
                    this.style.boxShadow = 'none';
                    this.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                    this.style.background = 'rgba(255, 255, 255, 0.1)';
                });
            });
            
            // Add floating animation control
            const registerContainer = document.querySelector('.register-container');
            let floating = true;
            
            // Pause floating animation on hover
            registerContainer.addEventListener('mouseenter', function() {
                floating = false;
                this.style.animation = 'none';
                this.style.transform = 'translateY(0)';
            });
            
            registerContainer.addEventListener('mouseleave', function() {
                floating = true;
                this.style.animation = 'floatIn 0.8s ease-out, float 6s ease-in-out infinite';
                this.style.animationDelay = '0s, 1s';
            });
        });
    </script>
</body>
</html>