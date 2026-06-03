<?php
require_once 'db.php';
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $fullname = trim($_POST['fullname']);

    // Input Validations
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $account_number = '202' . rand(1000000, 9999999);

        // Default Profile Picture
        $profile_pic = 'default.png';

        try {
            $pdo->beginTransaction();

            // Insert User with 'pending' status so they require admin approval
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, profile_pic, status) VALUES (?, ?, ?, ?, 'user', ?, 'pending')");
            $stmt->execute([$username, $email, $hashed_password, $fullname, $profile_pic]);
            
            // Fetch last inserted ID safely for PostgreSQL
            $user_id = $pdo->lastInsertId();

            // Create Bank Account record for the user
            $stmt = $pdo->prepare("INSERT INTO accounts (user_id, account_number, balance) VALUES (?, ?, 0.00)");
            $stmt->execute([$user_id, $account_number]);

            $pdo->commit();
            $message = "Success! Your application has been submitted. Please wait for Admin Approval before signing in. <a href='login.php' style='color:#0f5c8c; font-weight:bold;'>Go to Login</a>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            // 23505 is the explicit PostgreSQL code for unique constraint violation
            if ($e->getCode() == '23505') {
                $error = "Registration failed: This Username or Email address is already registered.";
            } else {
                $error = "System Registration Error: " . $e->getMessage();
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
    <title>Register - Barclays Banking Demo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .register-container { background: rgba(255, 255, 255, 0.95); border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); width: 100%; max-width: 500px; padding: 40px; }
        .brand { text-align: center; margin-bottom: 30px; }
        .brand .logo { font-size: 2rem; font-weight: 800; color: #0f5c8c; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; }
        .brand h2 { font-size: 1.8rem; color: #333; margin-top: 10px; }
        .brand p { color: #666; margin-top: 5px; }
        .error-message { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; border-left: 4px solid #dc2626; }
        .success-message { background: #dcfce7; color: #16a34a; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; border-left: 4px solid #16a34a; }
        .form-group { margin-bottom: 20px; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem; }
        .input-group input { width: 100%; padding: 15px 15px 15px 45px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; }
        .input-group input:focus { outline: none; border-color: #0f5c8c; }
        .register-btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #0f5c8c, #0a3d5e); color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 10px; }
        .register-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(15, 92, 140, 0.3); }
        .login-link { text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
        .login-link a { color: #0f5c8c; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="brand">
            <a href="#" class="logo"><i class="fas fa-university"></i> BARCLAYS</a>
            <h2>Create Account</h2>
            <p>Project Demo Registration Portal</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <div class="input-group"><i class="fas fa-user"></i>
                    <input type="text" name="fullname" placeholder="Full Name" required>
                </div>
            </div>
            <div class="form-group">
                <div class="input-group"><i class="fas fa-at"></i>
                    <input type="text" name="username" placeholder="Desired Username" required>
                </div>
            </div>
            <div class="form-group">
                <div class="input-group"><i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
            </div>
            <div class="form-group">
                <div class="input-group"><i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password (Min 8 characters)" required>
                </div>
            </div>
            <button type="submit" class="register-btn"><i class="fas fa-user-plus"></i> Submit Application</button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>
</body>
</html>