<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Force clean presentation variables for Euro
$currency_symbol = '€';
$currency_code = 'EUR';

try {
    // 1. Get clean user info
    $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // 2. Get account info with strict fallback to 0.00 if unassigned
    $stmt = $pdo->prepare("SELECT id, balance, account_number FROM accounts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $account = $stmt->fetch();
    
    if ($account) {
        $account_id = $account['id'];
        $account_number = $account['account_number'];
        $account_balance = (isset($account['balance']) && !empty($account['balance'])) ? floatval($account['balance']) : 0.00;
    } else {
        $account_id = 0;
        $account_number = 'N/A';
        $account_balance = 0.00;
    }

} catch (PDOException $e) {
    $error = "System Database Error: " . $e->getMessage();
}

// Handle deposit request submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_deposit'])) {
    $amount = floatval($_POST['amount']);
    $reason = trim($_POST['reason']);
    $payment_method = $_POST['payment_method'];
    
    $receipt_path = null;
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        $upload_dir = 'uploads/deposit_receipts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $receipt_path = $upload_dir . uniqid() . '_deposit.' . $ext;
            move_uploaded_file($_FILES['receipt']['tmp_name'], $receipt_path);
        }
    }
    
    if ($amount <= 0) {
        $error = "Please enter a valid amount.";
    } elseif ($amount < 10) {
        $error = "Minimum deposit amount is " . $currency_symbol . "10.";
    } elseif (strlen($reason) < 5) {
        $error = "Please provide a reason for deposit (minimum 5 characters).";
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO deposit_requests (user_id, amount, reason, payment_method, receipt_path, status, requested_date) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$user_id, $amount, $reason, $payment_method, $receipt_path]);
            
            // NOTIFICATION: Add notification for user
            $stmt2 = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'deposit', 'Deposit Request Submitted', CONCAT('Your deposit request of ', ?, ' has been submitted and is pending approval.'), NOW())");
            $stmt2->execute([$user_id, $currency_symbol . number_format($amount, 2)]);
            
            // NOTIFICATION: Add notification for admin (user_id = 1)
            $stmt3 = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (1, 'deposit', 'New Deposit Request', CONCAT('User ', ?, ' requested a deposit of ', ?), NOW())");
            $stmt3->execute([$user['full_name'], $currency_symbol . number_format($amount, 2)]);
            
            $pdo->commit();
            $message = "✅ Deposit request submitted! Please wait for admin approval. Funds will be added to your account once approved.";
            
            // Refresh local balance metric variables safely
            $account_balance = 0.00; 
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}

// Get deposit requests safely
$deposit_requests = [];
$pending_count = 0;
if ($account_id !== 0) {
    $stmt = $pdo->prepare("SELECT * FROM deposit_requests WHERE user_id = ? ORDER BY requested_date DESC");
    $stmt->execute([$user_id]);
    $deposit_requests = $stmt->fetchAll();
    foreach ($deposit_requests as $req) { 
        if ($req['status'] == 'pending') $pending_count++; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Funds - Barclays Banking</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            padding: 20px;
            color: #f8fafc;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        .header {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        .balance { background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); padding: 10px 20px; border-radius: 50px; }
        .balance span { font-size: 1.5rem; font-weight: 700; margin-left: 10px; color: #38bdf8; }
        .back-btn {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 30px;
            transition: 0.3s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.2); }
        .glass-card {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin-bottom: 30px;
        }
        .card-header { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; }
        .card-header .icon {
            width: 60px;
            height: 60px;
            background: rgba(56, 189, 248, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #38bdf8;
            font-size: 1.8rem;
        }
        .card-header h2 { font-size: 1.8rem; color: white; }
        .card-header p { color: #94a3b8; margin-top: 5px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #cbd5e1; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #334155;
            background: #0f172a;
            color: white;
            border-radius: 10px;
            font-size: 1rem;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #38bdf8;
        }
        .btn-primary {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #38bdf8, #0284c7);
            color: #0f172a;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(56, 189, 248, 0.2); }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #14532d; color: #4ade80; border: 1px solid #166534; }
        .alert-error { background: #7f1d1d; color: #fca5a5; border: 1px solid #991b1b; }
        .alert-info { background: #1e3a8a; color: #93c5fd; border: 1px solid #1e40af; }
        .requests-section {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 30px;
            padding: 30px;
        }
        .request-item {
            background: #0f172a;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #334155;
        }
        .request-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 10px; }
        .request-amount { font-size: 1.5rem; font-weight: 700; color: #38bdf8; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-pending { background: #854d0e; color: #fef08a; }
        .status-approved { background: #146534; color: #dcfce7; }
        .status-rejected { background: #7f1d1d; color: #fca5a5; }
        .upload-area {
            border: 2px dashed #475569;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 5px;
            background: #0f172a;
        }
        .request-details { margin: 10px 0; color: #94a3b8; font-size: 0.85rem; }
        .request-details span { margin-right: 15px; }
        .request-reason { background: #1e293b; padding: 10px; border-radius: 8px; margin: 10px 0; color: #cbd5e1; font-style: italic; }
        @media (max-width: 768px) {
            .glass-card, .requests-section { padding: 25px; }
            .request-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <div class="header">
            <h2><i class="fas fa-university"></i> Barclays Banking</h2>
            <div class="balance">Current Balance: <span><?php echo $currency_symbol; ?><?php echo number_format($account_balance, 2); ?></span></div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="glass-card">
            <div class="card-header">
                <div class="icon"><i class="fas fa-plus-circle"></i></div>
                <div><h2>Request Deposit</h2><p>Submit a deposit request for admin approval</p></div>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="request_deposit" value="1">
                <div class="form-group">
                    <label>Deposit Amount (<?php echo $currency_symbol; ?>)</label>
                    <input type="number" name="amount" step="0.01" min="10" placeholder="Enter amount" required>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method" required>
                        <option value="bank_transfer">🏦 Bank Transfer</option>
                        <option value="credit_card">💳 Credit/Debit Card</option>
                        <option value="mobile_banking">📱 Mobile Banking</option>
                        <option value="cash">💰 Cash Deposit at Branch</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reason for Deposit</label>
                    <textarea name="reason" rows="3" placeholder="e.g., Savings, Bill Payment, Investment, etc." required></textarea>
                </div>
                <div class="form-group">
                    <label>Upload Payment Receipt (Optional)</label>
                    <div class="upload-area" onclick="document.getElementById('receipt').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p style="color: #94a3b8;">Click to upload receipt (JPG, PNG, PDF)</p>
                        <small style="color: #64748b;">Max 5MB</small>
                    </div>
                    <input type="file" id="receipt" name="receipt" accept="image/*,.pdf" style="display: none;">
                </div>
                <div class="alert alert-info" style="margin-top: 15px;"><i class="fas fa-info-circle"></i> Your deposit request will be reviewed by admin. Funds will be added after approval.</div>
                <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Submit Deposit Request</button>
            </form>
        </div>

        <div class="requests-section">
            <h3 style="color: white; margin-bottom: 20px;"><i class="fas fa-history"></i> My Deposit Requests <?php if ($pending_count > 0): ?><span class="status-badge" style="background: #854d0e; color: #fef08a; margin-left: 10px;"><?php echo $pending_count; ?> pending</span><?php endif; ?></h3>
            <?php if (empty($deposit_requests)): ?>
                <div style="text-align: center; padding: 40px; color: #64748b;"><i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px;"></i><p>No deposit requests yet</p></div>
            <?php else: ?>
                <?php foreach ($deposit_requests as $req): ?>
                <div class="request-item">
                    <div class="request-header">
                        <div class="request-amount"><?php echo $currency_symbol; ?><?php echo number_format($req['amount'], 2); ?></div>
                        <div class="status-badge status-<?php echo $req['status']; ?>"><i class="fas <?php echo $req['status'] == 'pending' ? 'fa-clock' : ($req['status'] == 'approved' ? 'fa-check-circle' : 'fa-times-circle'); ?>"></i> <?php echo ucfirst($req['status']); ?></div>
                    </div>
                    <div class="request-details">
                        <span><i class="fas fa-credit-card"></i> <?php echo str_replace('_', ' ', ucfirst($req['payment_method'])); ?></span>
                        <span><i class="fas fa-calendar"></i> Requested: <?php echo date('M d, Y h:i A', strtotime($req['requested_date'])); ?></span>
                    </div>
                    <div class="request-reason"><i class="fas fa-quote-left"></i> <?php echo htmlspecialchars($req['reason']); ?></div>
                    <?php if ($req['status'] == 'approved'): ?>
                        <div style="margin-top: 15px; padding: 10px; background: #14532d; border-radius: 8px; color: #4ade80; border: 1px solid #166534;"><i class="fas fa-check-circle"></i> ✅ Amount has been added to your account balance!</div>
                    <?php elseif ($req['status'] == 'rejected'): ?>
                        <div style="margin-top: 15px; padding: 10px; background: #7f1d1d; border-radius: 8px; color: #fca5a5; border: 1px solid #991b1b;"><i class="fas fa-times-circle"></i> ❌ Deposit request was rejected.</div>
                    <?php else: ?>
                        <div style="margin-top: 15px; padding: 10px; background: #854d0e; border-radius: 8px; color: #fef08a; border: 1px solid #744210;"><i class="fas fa-clock"></i> ⏳ Awaiting admin approval.</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <script>
        document.getElementById('receipt').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                document.querySelector('.upload-area').innerHTML = '<i class="fas fa-check-circle" style="color: #38bdf8;"></i><p style="color: white; margin-top:5px;">File selected: ' + e.target.files[0].name + '</p><small style="color: #64748b;">Click to change</small>';
            }
        });
    </script>
</body>
</html>