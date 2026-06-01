<?php
require 'db.php';
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $fullname = trim($_POST['fullname']);

    // Validations
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $account_number = '202' . rand(1000000, 9999999);

        // Handle Profile Picture Upload
        $profile_pic = 'default.png';
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed_types)) {
                $profile_pic = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'uploads/' . $profile_pic);
            }
        }

        try {
            $pdo->beginTransaction();

            // Insert User WITH Profile Pic
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, profile_pic) VALUES (?, ?, ?, ?, 'user', ?)");
            $stmt->execute([$username, $email, $hashed_password, $fullname, $profile_pic]);
            $user_id = $pdo->lastInsertId();

            // Create Account
            $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, balance) VALUES (?, ?, 0.00)");
            $stmt->execute([$user_id, $account_number]);

            $pdo->commit();
            $message = "Success! Account created. <a href='login.php'>Login here</a>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = "Username or Email already exists!";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Barclays Banking</title>
    
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
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 500px;
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

        /* Messages */
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

        .success-message {
            background: #dcfce7;
            color: #16a34a;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            border-left: 4px solid #16a34a;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
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

        /* Password Requirements */
        .password-requirements {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 5px;
            padding-left: 10px;
        }

        .password-requirements i {
            color: #10b981;
            margin-right: 5px;
        }

        /* File Upload */
        .file-upload {
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
        }

        .file-upload:hover {
            border-color: #0f5c8c;
            background: #f8fafc;
        }

        .file-upload i {
            font-size: 2rem;
            color: #0f5c8c;
            margin-bottom: 10px;
        }

        .file-upload p {
            color: #64748b;
            font-size: 0.9rem;
        }

        .file-upload small {
            color: #94a3b8;
            font-size: 0.8rem;
            display: block;
            margin-top: 5px;
        }

        #file-name {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #0f5c8c;
            display: none;
        }

        /* Register Button */
        .register-btn {
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
            margin-top: 20px;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15, 92, 140, 0.3);
        }

        .register-btn i {
            font-size: 1.1rem;
        }

        /* Login Link */
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .login-link p {
            color: #64748b;
            font-size: 0.95rem;
        }

        .login-link a {
            color: #0f5c8c;
            text-decoration: none;
            font-weight: 600;
            margin-left: 5px;
            transition: 0.3s;
        }

        .login-link a:hover {
            color: #d4af37;
        }

        /* Terms */
        .terms {
            margin-top: 20px;
            font-size: 0.8rem;
            color: #94a3b8;
            text-align: center;
        }

        .terms a {
            color: #0f5c8c;
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
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
            <h2>Create Account</h2>
            <p>Join over 1 million satisfied customers</p>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form method="POST" enctype="multipart/form-data" id="registerForm">
            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="fullname" placeholder="Full Name" required>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-at"></i>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Password (Min 8 chars)" required onkeyup="checkPassword()">
                </div>
                <div class="password-requirements" id="passwordRequirements">
                    <i class="fas fa-circle" id="lengthIcon"></i> At least 8 characters
                </div>
            </div>

            <div class="form-group">
                <label class="file-upload" for="profilePic">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Click to upload profile picture</p>
                    <small>Optional - JPG, PNG, GIF up to 5MB</small>
                </label>
                <input type="file" id="profilePic" name="profile_pic" accept="image/*" style="display: none;" onchange="updateFileName(this)">
                <div id="file-name"></div>
            </div>

            <button type="submit" class="register-btn" id="registerBtn">
                <i class="fas fa-user-plus"></i>
                Create Account
            </button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="login.php">Sign in</a></p>
        </div>

        <div class="terms">
            By creating an account, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
        </div>
    </div>

    <script>
        // Password validation
        function checkPassword() {
            const password = document.getElementById('password').value;
            const lengthIcon = document.getElementById('lengthIcon');
            
            if (password.length >= 8) {
                lengthIcon.className = 'fas fa-check-circle';
                lengthIcon.style.color = '#10b981';
            } else {
                lengthIcon.className = 'fas fa-circle';
                lengthIcon.style.color = '#64748b';
            }
        }

        // File upload display
        function updateFileName(input) {
            const fileName = document.getElementById('file-name');
            if (input.files && input.files[0]) {
                fileName.style.display = 'block';
                fileName.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Selected: ' + input.files[0].name;
            }
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
            }
        });
    </script>
</body>
</html>