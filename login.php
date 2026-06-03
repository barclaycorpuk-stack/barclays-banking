<?php
require_once 'db.php';
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login_input = trim($_POST['username']); // Can be username or email
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Check Account Status for Final Year Project logic
            if ($user['status'] === 'pending') {
                $error = "Your account registration is currently PENDING admin approval. Please contact the system evaluator.";
            } elseif ($user['status'] === 'rejected') {
                $error = "Access Denied: Your account registration has been rejected by administration.";
            } elseif (isset($user['is_frozen']) && $user['is_frozen'] == 1) {
                $error = "Access Denied: This account has been frozen due to suspicious activity.";
            } else {
                // Login is successful! Set up session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];

                // Redirect based on authority role
                if ($user['role'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: dashboard.php"); // Or whatever your normal user home file is called
                }
                exit;
            }
        } else {
            $error = "Invalid username or password!";
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barclays Banking Demo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-container { background: rgba(255, 255, 255, 0.95); border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); width: 100%; max-width: 450px; padding: 40px; }
        .brand { text-align: center; margin-bottom: 30px; }
        .brand .logo { font-size: 2rem; font-weight: 800; color: #0f5c8c; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; }
        .brand h2 { font-size: 1.8rem; color: #333; margin-top: 10px; }
        .error-message { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; border-left: 4px solid #dc2626; }
        .form-group { margin-bottom: 20px; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem; }
        .input-group input { width: 100%; padding: 15px 15px 15px 45px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; }
        .login-btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #0f5c8c, #0a3d5e); color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 10px; }
        .login-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(15, 92, 140, 0.3); }
        .register-link { text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
        .register-link a { color: #0f5c8c; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand">
            <a href="#" class="logo"><i class="fas fa-university"></i> BARCLAYS</a>
            <h2>Welcome Back</h2>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <div class="input-group"><i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Username or Email" required>
                </div>
            </div>
            <div class="form-group">
                <div class="input-group"><i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
            </div>
            <button type="submit" class="login-btn"><i class="fas fa-sign-in-alt"></i> Sign In</button>
        </form>

        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Create one now</a></p>
        </div>
    </div>
</body>
</html>