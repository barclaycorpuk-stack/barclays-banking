<?php
session_start();
require 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST['username']); 
    $password = $_POST['password'];

    // Fetch user by username or email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$login_input, $login_input]);
    $user = $stmt->fetch();

    // Verify password
    if ($user && password_verify($password, $user['password'])) {
        
        if ($user['status'] === 'pending') {
            $error = "Your account is pending Admin approval. Please wait.";
        } elseif ($user['status'] === 'rejected') {
            $error = "Your account application was declined.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] == 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        }
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barclays Banking</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Glassmorphism Container */
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header */
        .brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand .logo {
            font-size: 2rem;
            font-weight: 800;
            color: #0f5c8c;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .brand .logo i {
            color: #d4af37;
        }

        .brand h2 {
            font-size: 1.8rem;
            color: #333;
            margin-top: 10px;
        }

        .brand p {
            color: #666;
            margin-top: 5px;
        }

        /* Back to Home Button */
        .back-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 20px;
            transition: 0.3s;
        }

        .back-home:hover {
            color: #0f5c8c;
            transform: translateX(-5px);
        }

        /* Error Message */
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            border-left: 4px solid #dc2626;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group i:first-child {
            position: absolute;
            left: 15px;
            color: #94a3b8;
            font-size: 1.1rem;
            z-index: 1;
        }

        .input-group input {
            width: 100%;
            padding: 15px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .input-group input:focus {
            outline: none;
            border-color: #0f5c8c;
            box-shadow: 0 0 0 3px rgba(15, 92, 140, 0.1);
        }

        .input-group input::placeholder {
            color: #94a3b8;
            font-weight: 300;
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 15px;
            color: #94a3b8;
            cursor: pointer;
            z-index: 2;
        }

        .password-toggle:hover {
            color: #0f5c8c;
        }

        /* Options */
        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0 25px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #0f5c8c;
        }

        .forgot-password {
            color: #0f5c8c;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .forgot-password:hover {
            color: #d4af37;
        }

        /* Login Button */
        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #0f5c8c, #0a3d5e);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15, 92, 140, 0.3);
        }

        .login-btn i {
            font-size: 1.1rem;
        }

        /* Sign Up Link */
        .signup-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .signup-link p {
            color: #64748b;
            font-size: 0.95rem;
        }

        .signup-link a {
            color: #0f5c8c;
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
            transition: 0.3s;
        }

        .signup-link a:hover {
            color: #d4af37;
        }

        /* Features List */
        .features {
            margin-top: 30px;
            display: flex;
            justify-content: space-around;
            padding: 15px 0;
            border-top: 1px solid #e2e8f0;
        }

        .feature {
            text-align: center;
        }

        .feature i {
            color: #d4af37;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .feature span {
            display: block;
            font-size: 0.8rem;
            color: #64748b;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }

            .options {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .features {
                flex-wrap: wrap;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Back to Home -->
        <a href="index.php" class="back-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <!-- Brand -->
        <div class="brand">
            <a href="index.php" class="logo">
                <i class="fas fa-university"></i>
                BARCLAYS
            </a>
            <h2>Welcome Back</h2>
            <p>Please enter your details to sign in</p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST">
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Username or Email" required>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <i class="fas fa-eye password-toggle" id="togglePassword" onclick="togglePassword()"></i>
                </div>
            </div>

            <div class="options">
                <label class="remember-me">
                    <input type="checkbox"> Remember me
                </label>
                <a href="#" class="forgot-password">Forgot password?</a>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>

        <div class="signup-link">
            <p>Don't have an account? <a href="register.php">Create one now</a></p>
        </div>

        <!-- Features -->
        <div class="features">
            <div class="feature">
                <i class="fas fa-shield-alt"></i>
                <span>Secure</span>
            </div>
            <div class="feature">
                <i class="fas fa-bolt"></i>
                <span>Fast</span>
            </div>
            <div class="feature">
                <i class="fas fa-headset"></i>
                <span>24/7 Support</span>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePassword');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>