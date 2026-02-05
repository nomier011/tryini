<?php
session_start();
include 'config.php';

$error = '';
$username = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Fetch user from database
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $stored_password);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        // Assuming passwords are stored with password_hash()
        if (password_verify($password, $stored_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Username not found.";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SecureApp</title>
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

        /* Transparent Floating Login Form */
        .login-container {
            width: 100%;
            max-width: 420px;
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
        .login-container::before {
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

        .login-container::after {
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
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 2.5rem;
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
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 
                0 5px 15px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
            font-weight: 500;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        /* Form Title */
        h2 {
            color: white;
            margin-bottom: 30px;
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
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
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            font-size: 0.95rem;
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
            padding: 14px 15px 14px 45px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 1rem;
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
            font-size: 0.95rem;
        }

        /* Login Button */
        .btn-login {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 16px;
            border-radius: 10px;
            font-size: 1.1rem;
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

        .btn-login::before {
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

        .btn-login:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-3px);
            box-shadow: 
                0 8px 25px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        /* Error Message */
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
            font-size: 0.95rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        /* Register Link */
        .register-link {
            text-align: center;
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.95rem;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .register-link a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
            padding-bottom: 3px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .register-link a:hover {
            color: white;
        }

        .register-link a:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: rgba(255, 255, 255, 0.8);
            transition: width 0.3s;
        }

        .register-link a:hover:after {
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
            
            .login-container {
                padding: 30px 25px;
                max-width: 100%;
                backdrop-filter: blur(20px);
            }
            
            .logo h1 {
                font-size: 2rem;
            }
            
            .logo h1 i {
                width: 50px;
                height: 50px;
                font-size: 24px;
            }
            
            h2 {
                font-size: 1.6rem;
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

        .login-container {
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
    
    <!-- Transparent Floating Login Form -->
    <div class="login-container">
        <div class="logo">
            <h1>
                <i class="fas fa-shield-alt"></i>
                SecureApp
            </h1>
            <p>Secure access to your dashboard</p>
        </div>
        
        <h2>Sign In to Your Account</h2>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm">
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
                           placeholder="Enter your username">
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
                           placeholder="Enter your password">
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>
        
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
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
            Use your registered credentials to login
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('video-background');
            const featuresToggle = document.getElementById('featuresToggle');
            const featuresPanel = document.getElementById('featuresPanel');
            
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
            
            // Form submission handler
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                const submitBtn = document.querySelector('.btn-login');
                const originalHTML = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
                submitBtn.disabled = true;
                
                // Re-enable button if there's an error
                setTimeout(() => {
                    if (document.querySelector('.error-message')) {
                        submitBtn.innerHTML = originalHTML;
                        submitBtn.disabled = false;
                    }
                }, 2000);
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
            const loginContainer = document.querySelector('.login-container');
            let floating = true;
            
            // Pause floating animation on hover
            loginContainer.addEventListener('mouseenter', function() {
                floating = false;
                this.style.animation = 'none';
                this.style.transform = 'translateY(0)';
            });
            
            loginContainer.addEventListener('mouseleave', function() {
                floating = true;
                this.style.animation = 'floatIn 0.8s ease-out, float 6s ease-in-out infinite';
                this.style.animationDelay = '0s, 1s';
            });
            
            // Add subtle parallax effect to form on mouse move
            document.addEventListener('mousemove', function(e) {
                if (!floating) return;
                
                const xAxis = (window.innerWidth / 2 - e.pageX) / 25;
                const yAxis = (window.innerHeight / 2 - e.pageY) / 25;
                
                loginContainer.style.transform = `translateY(${yAxis}px) translateX(${xAxis}px)`;
            });
        });
    </script>
</body>
</html>