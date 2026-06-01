<?php
session_start();
require 'db.php';

// Security Check
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle Change Password
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $hash = $stmt->fetchColumn();
    
    if (!password_verify($current, $hash)) {
        $error = "Current password is incorrect!";
    } elseif ($new != $confirm) {
        $error = "New passwords do not match!";
    } elseif (strlen($new) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$new_hash, $user_id]);
        $message = "Password changed successfully!";
    }
}

// Handle Transaction PIN
if (isset($_POST['update_pin'])) {
    $pin = $_POST['transaction_pin'];
    $confirm_pin = $_POST['confirm_pin'];
    
    if (strlen($pin) != 4 || !ctype_digit($pin)) {
        $error = "PIN must be exactly 4 digits!";
    } elseif ($pin != $confirm_pin) {
        $error = "PINs do not match!";
    } else {
        $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET transaction_pin = ? WHERE id = ?");
        $stmt->execute([$hashed_pin, $user_id]);
        $message = "Transaction PIN updated successfully!";
    }
}

// Handle 2FA Toggle
if (isset($_POST['toggle_2fa'])) {
    $current_status = $_POST['current_2fa'] == '1' ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = ? WHERE id = ?");
    $stmt->execute([$current_status, $user_id]);
    $message = "2FA " . ($current_status ? "enabled" : "disabled") . " successfully!";
}

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $error = "Email already in use!";
    } else {
        // Update user details
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$full_name, $email, $phone, $user_id]);
        
        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        // Profile pic upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $profile_pic = uniqid() . '_profile.' . $ext;
                move_uploaded_file($_FILES['profile_pic']['tmp_name'], 'uploads/' . $profile_pic);
                $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?")->execute([$profile_pic, $user_id]);
            }
        }
        
        // Cover pic upload
        if (isset($_FILES['cover_pic']) && $_FILES['cover_pic']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['cover_pic']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $cover_pic = uniqid() . '_cover.' . $ext;
                move_uploaded_file($_FILES['cover_pic']['tmp_name'], 'uploads/' . $cover_pic);
                $pdo->prepare("UPDATE users SET cover_pic = ? WHERE id = ?")->execute([$cover_pic, $user_id]);
            }
        }
        
        $message = "Profile updated successfully!";
    }
}

// Fetch Latest User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get account balance
$stmt = $pdo->prepare("SELECT balance, account_number FROM accounts WHERE user_id = ?");
$stmt->execute([$user_id]);
$account = $stmt->fetch();

$profile_url = (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default.png' && file_exists('uploads/'.$user['profile_pic'])) 
    ? 'uploads/'.$user['profile_pic'] 
    : 'https://ui-avatars.com/api/?name='.urlencode($user['full_name']).'&background=38bdf8&color=0f172a&bold=true';

$cover_url = (!empty($user['cover_pic']) && $user['cover_pic'] !== 'default_cover.jpg' && file_exists('uploads/'.$user['cover_pic'])) 
    ? 'uploads/'.$user['cover_pic'] 
    : 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=1000&auto=format&fit=crop';

$has_pin = !empty($user['transaction_pin']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Profile - Barclays Banking</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --accent: #38bdf8;
        }
        
        body { font-family: 'Poppins', sans-serif; background: #0f172a; color: white; margin: 0; padding: 0; }
        
        .profile-header { position: relative; width: 100%; height: 250px; background: url('<?php echo $cover_url; ?>') center/cover no-repeat; }
        .overlay { position: absolute; top:0; left:0; width:100%; height:100%; background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(15,23,42,1)); }
        
        .container { max-width: 800px; margin: -80px auto 40px; position: relative; z-index: 10; }
        
        .profile-card {
            background: #1e293b;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.05);
        }
        
        .profile-img-wrapper { text-align: center; margin-bottom: 20px; margin-top: -70px; }
        .profile-img { width: 130px; height: 130px; border-radius: 50%; border: 5px solid #1e293b; object-fit: cover; }
        
        .account-badge {
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid var(--accent);
            color: var(--accent);
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            font-size: 0.8rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 30px 0;
            padding: 20px 0;
            border-top: 1px solid rgba(255,255,255,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-item h3 {
            color: var(--accent);
            font-size: 1.3rem;
            margin-bottom: 5px;
        }
        
        .stat-item p {
            color: #94a3b8;
            font-size: 0.8rem;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 8px;
            transition: 0.3s;
        }
        
        .tab.active {
            background: var(--accent);
            color: var(--primary);
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .msg { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid #10b981; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #f87171; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        
        form { display: flex; flex-direction: column; gap: 15px; }
        .input-group { display: flex; flex-direction: column; gap: 5px; }
        label { font-size: 0.85rem; color: #94a3b8; }
        input, select { 
            padding: 12px; 
            background: rgba(15,23,42,0.5); 
            border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 8px; 
            color: white;
            font-family: 'Poppins', sans-serif;
        }
        input:focus { outline: none; border-color: var(--accent); }
        
        button {
            padding: 12px;
            background: var(--accent);
            color: var(--primary);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover { background: #0284c7; color: white; }
        
        .twofa-toggle {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(15,23,42,0.5);
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--accent);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .pin-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .pin-status.set {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid #10b981;
        }
        
        .pin-status.not-set {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid #f87171;
        }
        
        .back-btn { 
            display: block; 
            text-align: center; 
            margin-top: 20px; 
            color: #94a3b8; 
            text-decoration: none;
            transition: 0.3s;
        }
        .back-btn:hover { color: var(--accent); }
        
        hr {
            border-color: rgba(255,255,255,0.1);
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; gap: 10px; }
            .tabs { flex-wrap: wrap; }
            .tab { flex: 1; text-align: center; }
        }
    </style>
</head>
<body>

<div class="profile-header">
    <div class="overlay"></div>
</div>

<div class="container">
    <div class="profile-card">
        <div class="profile-img-wrapper">
            <img src="<?php echo $profile_url; ?>" alt="Profile" class="profile-img">
        </div>

        <h2 style="text-align: center; margin: 0;"><?php echo htmlspecialchars($user['full_name']); ?></h2>
        <div style="text-align: center; margin-top: 10px;">
            <span class="account-badge"><i class="fas fa-shield-alt"></i> <?php echo ucfirst($user['role']); ?> Account</span>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-item">
                <h3>$<?php echo number_format($account['balance'], 2); ?></h3>
                <p>Account Balance</p>
            </div>
            <div class="stat-item">
                <h3>****<?php echo substr($account['account_number'], -4); ?></h3>
                <p>Account Number</p>
            </div>
            <div class="stat-item">
                <h3><?php echo date('M Y', strtotime($user['created_at'])); ?></h3>
                <p>Member Since</p>
            </div>
        </div>
        
        <?php if ($message): ?><div class="msg"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
        
        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="openTab('profile')">Edit Profile</div>
            <div class="tab" onclick="openTab('security')">Security</div>
            <div class="tab" onclick="openTab('pin')">Transaction PIN</div>
        </div>
        
        <!-- Tab 1: Edit Profile -->
        <div id="profile" class="tab-content active">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="input-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+1 234 567 8900">
                </div>
                
                <div class="input-group">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_pic" accept="image/*">
                    <small style="color: #64748b;">Recommended: Square image, max 2MB</small>
                </div>
                
                <div class="input-group">
                    <label>Cover Photo</label>
                    <input type="file" name="cover_pic" accept="image/*">
                    <small style="color: #64748b;">Recommended: 1200x300px image</small>
                </div>
                
                <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
        
        <!-- Tab 2: Security (Change Password + 2FA) -->
        <div id="security" class="tab-content">
            <h3 style="color: var(--accent); margin-bottom: 20px;">Change Password</h3>
            <form method="POST">
                <input type="hidden" name="change_password" value="1">
                
                <div class="input-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                
                <div class="input-group">
                    <label>New Password (min. 6 characters)</label>
                    <input type="password" name="new_password" required>
                </div>
                
                <div class="input-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <button type="submit"><i class="fas fa-key"></i> Update Password</button>
            </form>
            
            <hr>
            
            <h3 style="color: var(--accent); margin: 30px 0 20px;">Two-Factor Authentication</h3>
            <form method="POST">
                <input type="hidden" name="toggle_2fa" value="1">
                <input type="hidden" name="current_2fa" value="<?php echo $user['two_factor_enabled'] ?? 0; ?>">
                
                <div class="twofa-toggle">
                    <div>
                        <strong>Enable 2FA</strong>
                        <p style="color: #94a3b8; font-size: 0.8rem; margin-top: 5px;">Add an extra layer of security to your account</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="twofa" <?php echo ($user['two_factor_enabled'] ?? 0) ? 'checked' : ''; ?> onchange="this.form.submit()">
                        <span class="slider"></span>
                    </label>
                </div>
            </form>
        </div>
        
        <!-- Tab 3: Transaction PIN -->
        <div id="pin" class="tab-content">
            <h3 style="color: var(--accent); margin-bottom: 20px;">
                Transaction PIN 
                <span class="pin-status <?php echo $has_pin ? 'set' : 'not-set'; ?>">
                    <i class="fas <?php echo $has_pin ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $has_pin ? 'PIN Set' : 'PIN Not Set'; ?>
                </span>
            </h3>
            <p style="color: #94a3b8; margin-bottom: 20px;">Set a 4-digit PIN for secure money transfers. You'll need this PIN every time you send money.</p>
            
            <form method="POST">
                <input type="hidden" name="update_pin" value="1">
                
                <div class="input-group">
                    <label>4-Digit PIN</label>
                    <input type="password" name="transaction_pin" maxlength="4" pattern="[0-9]{4}" placeholder="Enter 4 digits" required>
                </div>
                
                <div class="input-group">
                    <label>Confirm PIN</label>
                    <input type="password" name="confirm_pin" maxlength="4" pattern="[0-9]{4}" placeholder="Confirm 4 digits" required>
                </div>
                
                <button type="submit"><i class="fas fa-lock"></i> <?php echo $has_pin ? 'Update' : 'Set'; ?> Transaction PIN</button>
            </form>
            
            <div style="margin-top: 20px; padding: 15px; background: rgba(56, 189, 248, 0.1); border-radius: 8px;">
                <i class="fas fa-info-circle" style="color: var(--accent); margin-right: 10px;"></i>
                <span style="color: #94a3b8;">Your transaction PIN is encrypted and required for all money transfers including QR payments and email transfers.</span>
            </div>
        </div>
        
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</div>

<script>
    function openTab(tabName) {
        // Hide all tabs
        var tabs = document.getElementsByClassName('tab-content');
        for (var i = 0; i < tabs.length; i++) {
            tabs[i].classList.remove('active');
        }
        
        // Remove active class from all tab buttons
        var tabButtons = document.getElementsByClassName('tab');
        for (var i = 0; i < tabButtons.length; i++) {
            tabButtons[i].classList.remove('active');
        }
        
        // Show selected tab
        document.getElementById(tabName).classList.add('active');
        event.target.classList.add('active');
    }
    
    // Auto-hide messages after 5 seconds
    setTimeout(function() {
        var msg = document.querySelector('.msg');
        var error = document.querySelector('.error');
        if (msg) setTimeout(function() { msg.style.display = 'none'; }, 5000);
        if (error) setTimeout(function() { error.style.display = 'none'; }, 5000);
    }, 5000);
</script>

</body>
</html>