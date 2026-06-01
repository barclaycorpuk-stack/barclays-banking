<?php
session_start();
require 'db.php';

// Security Check: Only allow Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("<h2 style='color:red;text-align:center;margin-top:50px;'>ACCESS DENIED.<br>You are not an administrator.</h2><center><a href='login.php'>Go Back</a></center>");
}

$message = "";
$error = "";
$currency_symbol = CURRENCY_SYMBOL;
$currency_code = CURRENCY_CODE;

// --- Handle User Actions ---
if (isset($_GET['action']) && isset($_GET['id']) && !isset($_GET['deposit_id']) && !isset($_GET['loan_id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    switch($action) {
        case 'approve':
            $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
            $stmt->execute([$id]);
            $message = "User approved successfully!";
            break;
        case 'reject':
            $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$id]);
            $message = "User rejected!";
            break;
        case 'freeze':
            $stmt = $pdo->prepare("UPDATE users SET is_frozen = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Account frozen!";
            break;
        case 'unfreeze':
            $stmt = $pdo->prepare("UPDATE users SET is_frozen = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Account unfrozen!";
            break;
    }
    header("Location: admin.php");
    exit;
}

// --- Handle Deposit Approval/Rejection ---
if (isset($_GET['action']) && isset($_GET['deposit_id'])) {
    $action = $_GET['action'];
    $deposit_id = intval($_GET['deposit_id']);
    $admin_note = isset($_GET['note']) ? $_GET['note'] : '';
    
    if ($action == 'approve_deposit') {
        $stmt = $pdo->prepare("SELECT * FROM deposit_requests WHERE id = ?");
        $stmt->execute([$deposit_id]);
        $deposit = $stmt->fetch();
        
        if ($deposit && $deposit['status'] == 'pending') {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ?");
                $stmt->execute([$deposit['user_id']]);
                $account_id = $stmt->fetchColumn();
                
                $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?")->execute([$deposit['amount'], $account_id]);
                
                $stmt = $pdo->prepare("INSERT INTO transactions (sender_account_id, receiver_account_id, amount, type, description, created_at) VALUES (?, ?, ?, 'deposit', 'Admin approved deposit request', NOW())");
                $stmt->execute([$account_id, $account_id, $deposit['amount']]);
                
                $pdo->prepare("UPDATE deposit_requests SET status = 'approved', approved_date = NOW(), admin_notes = ? WHERE id = ?")->execute([$admin_note, $deposit_id]);
                
                $pdo->commit();
                
                // Add notification for user
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'deposit', 'Deposit Approved', CONCAT('Your deposit of ', ?, ' has been approved and added to your account!'), NOW())");
                $stmt->execute([$deposit['user_id'], $currency_symbol . number_format($deposit['amount'], 2)]);
                
                $message = "Deposit approved! Funds added to user's account.";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "Error: " . $e->getMessage();
            }
        }
    } elseif ($action == 'reject_deposit') {
        $pdo->prepare("UPDATE deposit_requests SET status = 'rejected', admin_notes = ? WHERE id = ?")->execute([$admin_note, $deposit_id]);
        
        // Add notification for user
        $stmt = $pdo->prepare("SELECT user_id FROM deposit_requests WHERE id = ?");
        $stmt->execute([$deposit_id]);
        $user_id_reject = $stmt->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'deposit', 'Deposit Rejected', CONCAT('Your deposit request has been rejected.'), NOW())");
        $stmt->execute([$user_id_reject]);
        
        $message = "Deposit request rejected.";
    }
    header("Location: admin.php");
    exit;
}

// --- Handle Loan Approval/Rejection ---
if (isset($_GET['action']) && isset($_GET['loan_id'])) {
    $action = $_GET['action'];
    $loan_id = intval($_GET['loan_id']);
    
    if ($action == 'approve_loan') {
        // Get user_id before updating
        $stmt = $pdo->prepare("SELECT user_id FROM loan_applications WHERE id = ?");
        $stmt->execute([$loan_id]);
        $loan_user_id = $stmt->fetchColumn();
        
        $pdo->prepare("UPDATE loan_applications SET status = 'approved', approved_date = NOW() WHERE id = ?")->execute([$loan_id]);
        
        // Add notification for user
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'loan', 'Loan Approved', 'Congratulations! Your loan application has been approved.', NOW())");
        $stmt->execute([$loan_user_id]);
        
        $message = "Loan approved successfully!";
        
    } elseif ($action == 'reject_loan') {
        // Get user_id before updating
        $stmt = $pdo->prepare("SELECT user_id FROM loan_applications WHERE id = ?");
        $stmt->execute([$loan_id]);
        $loan_user_id = $stmt->fetchColumn();
        
        $pdo->prepare("UPDATE loan_applications SET status = 'rejected', approved_date = NOW() WHERE id = ?")->execute([$loan_id]);
        
        // Add notification for user
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'loan', 'Loan Rejected', 'We regret to inform you that your loan application has been rejected.', NOW())");
        $stmt->execute([$loan_user_id]);
        
        $message = "Loan rejected.";
    }
    header("Location: admin.php");
    exit;
}

// Fetch statistics
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$pending_approvals = $pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$total_transactions = $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$frozen_accounts = $pdo->query("SELECT COUNT(*) FROM users WHERE is_frozen=1")->fetchColumn();
$pending_deposits_count = $pdo->query("SELECT COUNT(*) FROM deposit_requests WHERE status='pending'")->fetchColumn();
$pending_loans_count = $pdo->query("SELECT COUNT(*) FROM loan_applications WHERE status='pending'")->fetchColumn();

// Fetch users
$users = $pdo->query("
    SELECT u.*, a.balance, a.account_number 
    FROM users u 
    LEFT JOIN accounts a ON u.id = a.user_id 
    ORDER BY 
        CASE 
            WHEN u.status = 'pending' THEN 1
            ELSE 2
        END,
        u.id DESC
")->fetchAll();

// Fetch transactions
$transactions = $pdo->query("
    SELECT t.*, 
           sender_u.full_name AS sender_name,
           receiver_u.full_name AS receiver_name
    FROM transactions t
    LEFT JOIN accounts sender_a ON t.sender_account_id = sender_a.id
    LEFT JOIN users sender_u ON sender_a.user_id = sender_u.id
    LEFT JOIN accounts receiver_a ON t.receiver_account_id = receiver_a.id
    LEFT JOIN users receiver_u ON receiver_a.user_id = receiver_u.id
    ORDER BY t.created_at DESC
    LIMIT 30
")->fetchAll();

// Detect suspicious transactions (over €10,000)
$suspicious = $pdo->query("
    SELECT t.*, sender_u.full_name AS sender_name
    FROM transactions t
    JOIN accounts sender_a ON t.sender_account_id = sender_a.id
    JOIN users sender_u ON sender_a.user_id = sender_u.id
    WHERE t.amount > 10000
    ORDER BY t.amount DESC
")->fetchAll();

// Fetch deposit requests
$all_deposits = $pdo->query("
    SELECT dr.*, u.full_name, u.email, u.id as user_id 
    FROM deposit_requests dr 
    JOIN users u ON dr.user_id = u.id 
    ORDER BY 
        CASE WHEN dr.status = 'pending' THEN 1 ELSE 2 END,
        dr.requested_date DESC
")->fetchAll();

// Fetch loan applications
$all_loans = $pdo->query("
    SELECT la.*, u.full_name, u.email, a.balance 
    FROM loan_applications la 
    JOIN users u ON la.user_id = u.id 
    JOIN accounts a ON u.id = a.user_id 
    ORDER BY 
        CASE WHEN la.status = 'pending' THEN 1 ELSE 2 END,
        la.applied_date DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barclays Banking - Admin Panel (<?php echo $currency_code; ?>)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .admin-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .logout-btn {
            background: #ff4757;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: 0.3s;
            box-shadow: 0 4px 15px rgba(255, 71, 87, 0.3);
        }

        .logout-btn:hover {
            background: #ff6b81;
            transform: translateY(-2px);
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .welcome-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideDown 0.5s ease;
        }

        .welcome-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .welcome-section p {
            color: #718096;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 1.8rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: 0.3s;
            animation: fadeInUp 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.3rem;
        }

        .stat-info p {
            color: #718096;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .nav-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .nav-tab {
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .nav-tab:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .nav-tab.active {
            background: white;
            color: #2d3748;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .badge-count {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.2rem 0.7rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-count.warning {
            background: #ef4444;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeIn 0.5s ease;
            display: none;
        }

        .content-card.active {
            display: block;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1rem;
            color: #718096;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }

        tr:hover {
            background: #f7fafc;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-approved {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-pending {
            background: #feebc8;
            color: #744210;
        }

        .status-rejected {
            background: #fed7d7;
            color: #742a2a;
        }

        .status-frozen {
            background: #e9d8fd;
            color: #44337a;
        }

        .role-badge {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .role-admin {
            background: #fed7d7;
            color: #9b2c2c;
        }

        .role-user {
            background: #c6f6d5;
            color: #22543d;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            margin: 0 0.2rem;
            display: inline-block;
            transition: 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-approve {
            background: #48bb78;
            color: white;
        }

        .btn-reject {
            background: #f56565;
            color: white;
        }

        .btn-freeze {
            background: #ed8936;
            color: white;
        }

        .btn-unfreeze {
            background: #4299e1;
            color: white;
        }

        .btn-view {
            background: #667eea;
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .suspicious-row {
            background: linear-gradient(135deg, #fff5f5, #fed7d7);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { background: #fff5f5; }
            50% { background: #fed7d7; }
            100% { background: #fff5f5; }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .chart-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .chart-card h3 {
            color: #2d3748;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .quick-action {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-decoration: none;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            .nav-right {
                flex-direction: column;
                width: 100%;
            }
            .admin-badge, .logout-btn {
                width: 100%;
                justify-content: center;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .nav-tabs {
                flex-direction: column;
            }
            .nav-tab {
                width: 100%;
                justify-content: center;
            }
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-university"></i> Barclays Banking
        </div>
        <div class="nav-right">
            <div class="admin-badge">
                <i class="fas fa-shield-alt"></i> Administrator
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="welcome-section">
            <h1>Welcome back, Admin</h1>
            <p>Here's what's happening with your bank today (Currency: <?php echo $currency_code; ?> <?php echo $currency_symbol; ?>)</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $pending_approvals; ?></h3>
                    <p>Pending Approvals</p>
                </div>
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_transactions; ?></h3>
                    <p>Transactions</p>
                </div>
                <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $frozen_accounts; ?></h3>
                    <p>Frozen Accounts</p>
                </div>
                <div class="stat-icon"><i class="fas fa-snowflake"></i></div>
            </div>
        </div>

        <div class="nav-tabs">
            <div class="nav-tab active" onclick="openTab('users')"><i class="fas fa-users"></i> Users</div>
            <div class="nav-tab" onclick="openTab('transactions')"><i class="fas fa-exchange-alt"></i> Transactions</div>
            <div class="nav-tab" onclick="openTab('suspicious')"><i class="fas fa-exclamation-triangle"></i> Suspicious <?php if (count($suspicious) > 0): ?><span class="badge-count warning"><?php echo count($suspicious); ?></span><?php endif; ?></div>
            <div class="nav-tab" onclick="openTab('deposits')"><i class="fas fa-wallet"></i> Deposits <?php if ($pending_deposits_count > 0): ?><span class="badge-count warning"><?php echo $pending_deposits_count; ?></span><?php endif; ?></div>
            <div class="nav-tab" onclick="openTab('loans')"><i class="fas fa-hand-holding-usd"></i> Loans <?php if ($pending_loans_count > 0): ?><span class="badge-count warning"><?php echo $pending_loans_count; ?></span><?php endif; ?></div>
            <div class="nav-tab" onclick="openTab('analytics')"><i class="fas fa-chart-line"></i> Analytics</div>
        </div>

        <!-- Users Tab -->
        <div id="users" class="content-card active">
            <div class="card-header">
                <h2><i class="fas fa-user-cog"></i> User Management</h2>
                <div class="badge-count"><?php echo count($users); ?> users</div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Account</th>
                            <th>Balance (<?php echo $currency_symbol; ?>)</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">
                                        <?php echo substr($u['full_name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($u['full_name']); ?></strong>
                                        <br>
                                        <small><?php echo $u['email']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>****<?php echo substr($u['account_number'] ?? '0000', -4); ?></td>
                            <td><strong><?php echo $currency_symbol; ?><?php echo number_format($u['balance'] ?? 0, 2); ?></strong></td>
                            <td>
                                <span class="role-badge role-<?php echo $u['role']; ?>">
                                    <?php echo strtoupper($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['is_frozen'] ?? 0): ?>
                                    <span class="status-badge status-frozen">FROZEN</span>
                                <?php else: ?>
                                    <span class="status-badge status-<?php echo $u['status']; ?>">
                                        <?php echo $u['status']; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['status'] == 'pending'): ?>
                                    <a href="?action=approve&id=<?php echo $u['id']; ?>" class="action-btn btn-approve"><i class="fas fa-check"></i></a>
                                    <a href="?action=reject&id=<?php echo $u['id']; ?>" class="action-btn btn-reject"><i class="fas fa-times"></i></a>
                                <?php endif; ?>
                                <?php if (($u['is_frozen'] ?? 0) == 0 && $u['role'] != 'admin'): ?>
                                    <a href="?action=freeze&id=<?php echo $u['id']; ?>" class="action-btn btn-freeze"><i class="fas fa-snowflake"></i></a>
                                <?php elseif (($u['is_frozen'] ?? 0) == 1): ?>
                                    <a href="?action=unfreeze&id=<?php echo $u['id']; ?>" class="action-btn btn-unfreeze"><i class="fas fa-sun"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </div>
            </div>
        </div>

        <!-- Transactions Tab -->
        <div id="transactions" class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-history"></i> Recent Transactions</h2>
                <div class="badge-count">Last 30</div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Amount (<?php echo $currency_symbol; ?>)</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?php echo date('M d, Y h:i A', strtotime($t['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($t['sender_name'] ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars($t['receiver_name'] ?? 'System'); ?></td>
                            <td><strong><?php echo $currency_symbol; ?><?php echo number_format($t['amount'], 2); ?></strong></td>
                            <td><span class="role-badge"><?php echo ucfirst($t['type']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Suspicious Tab -->
        <div id="suspicious" class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Suspicious Transactions</h2>
                <div class="badge-count">High Value > <?php echo $currency_symbol; ?>10,000</div>
            </div>
            <?php if (empty($suspicious)): ?>
                <div style="text-align: center; padding: 4rem;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: #48bb78; margin-bottom: 1rem;"></i>
                    <h3>No Suspicious Transactions</h3>
                    <p>All transactions are within normal limits.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Amount (<?php echo $currency_symbol; ?>)</th>
                                <th>Type</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suspicious as $s): ?>
                            <tr class="suspicious-row">
                                <td><?php echo date('M d, Y h:i A', strtotime($s['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($s['sender_name']); ?></td>
                                <td><strong><?php echo $currency_symbol; ?><?php echo number_format($s['amount'], 2); ?></strong></td>
                                <td><?php echo ucfirst($s['type']); ?></td>
                                <td><a href="#" class="action-btn btn-view"><i class="fas fa-eye"></i> View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Deposits Tab -->
        <div id="deposits" class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-wallet"></i> Deposit Requests</h2>
                <div class="badge-count">Pending: <?php echo $pending_deposits_count; ?></div>
            </div>
            <?php if (empty($all_deposits)): ?>
                <div style="text-align: center; padding: 4rem;">
                    <i class="fas fa-inbox" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                    <h3>No Deposit Requests</h3>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Amount (<?php echo $currency_symbol; ?>)</th>
                                <th>Payment Method</th>
                                <th>Reason</th>
                                <th>Requested</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_deposits as $deposit): ?>
                            <tr>
                                <td>#<?php echo $deposit['id']; ?></div>
                                <td>
                                    <strong><?php echo htmlspecialchars($deposit['full_name']); ?></strong>
                                    <br>
                                    <small><?php echo $deposit['email']; ?></small>
                                </div>
                                <td><strong><?php echo $currency_symbol; ?><?php echo number_format($deposit['amount'], 2); ?></strong></div>
                                <td><?php echo str_replace('_', ' ', ucfirst($deposit['payment_method'])); ?></div>
                                <td><?php echo htmlspecialchars(substr($deposit['reason'], 0, 40)); ?>...</div>
                                <td><?php echo date('M d, Y', strtotime($deposit['requested_date'])); ?></div>
                                <td>
                                    <span class="status-badge status-<?php echo $deposit['status']; ?>">
                                        <?php echo ucfirst($deposit['status']); ?>
                                    </span>
                                </div>
                                <td>
                                    <?php if ($deposit['status'] == 'pending'): ?>
                                        <a href="?action=approve_deposit&deposit_id=<?php echo $deposit['id']; ?>&note=Approved" class="action-btn btn-approve" onclick="return confirm('Approve deposit?')"><i class="fas fa-check"></i> Approve</a>
                                        <a href="?action=reject_deposit&deposit_id=<?php echo $deposit['id']; ?>&note=Rejected" class="action-btn btn-reject" onclick="return confirm('Reject deposit?')"><i class="fas fa-times"></i> Reject</a>
                                    <?php else: ?>
                                        <span style="color: #64748b;">Processed</span>
                                    <?php endif; ?>
                                </div>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Loans Tab -->
        <div id="loans" class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-hand-holding-usd"></i> Loan Applications</h2>
                <div class="badge-count">Pending: <?php echo $pending_loans_count; ?></div>
            </div>
            <?php if (empty($all_loans)): ?>
                <div style="text-align: center; padding: 4rem;">
                    <i class="fas fa-inbox" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                    <h3>No Loan Applications</h3>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Applicant</th>
                                <th>Amount (<?php echo $currency_symbol; ?>)</th>
                                <th>Type</th>
                                <th>Tenure</th>
                                <th>EMI (<?php echo $currency_symbol; ?>)</th>
                                <th>Applied</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_loans as $loan): ?>
                            <tr>
                                <td>#<?php echo $loan['id']; ?></div>
                                <td>
                                    <strong><?php echo htmlspecialchars($loan['full_name']); ?></strong>
                                    <br>
                                    <small><?php echo $loan['email']; ?></small>
                                </div>
                                <td><strong><?php echo $currency_symbol; ?><?php echo number_format($loan['amount'], 2); ?></strong></div>
                                <td><?php echo ucfirst($loan['loan_type']); ?></div>
                                <td><?php echo $loan['tenure']; ?> months</div>
                                <td><?php echo $currency_symbol; ?><?php echo number_format($loan['emi'], 2); ?></div>
                                <td><?php echo date('M d, Y', strtotime($loan['applied_date'])); ?></div>
                                <td>
                                    <span class="status-badge status-<?php echo $loan['status']; ?>">
                                        <?php echo ucfirst($loan['status']); ?>
                                    </span>
                                </div>
                                <td>
                                    <?php if ($loan['status'] == 'pending'): ?>
                                        <a href="?action=approve_loan&loan_id=<?php echo $loan['id']; ?>" class="action-btn btn-approve" onclick="return confirm('Approve this loan?')"><i class="fas fa-check"></i> Approve</a>
                                        <a href="?action=reject_loan&loan_id=<?php echo $loan['id']; ?>" class="action-btn btn-reject" onclick="return confirm('Reject this loan?')"><i class="fas fa-times"></i> Reject</a>
                                    <?php else: ?>
                                        <span style="color: #64748b;">Processed</span>
                                    <?php endif; ?>
                                </div>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Analytics Tab -->
        <div id="analytics" class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-chart-line"></i> Analytics Dashboard</h2>
            </div>
            <div class="chart-container">
                <div class="chart-card">
                    <h3>User Status Distribution</h3>
                    <canvas id="userChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Transaction Types</h3>
                    <canvas id="transactionChart"></canvas>
                </div>
            </div>
            <div class="quick-actions">
                <a href="#" class="quick-action"><i class="fas fa-file-pdf fa-2x"></i><div><strong>Generate Report</strong><br><small>Download PDF</small></div></a>
                <a href="#" class="quick-action"><i class="fas fa-envelope fa-2x"></i><div><strong>Email All Users</strong><br><small>Send Notification</small></div></a>
                <a href="#" class="quick-action"><i class="fas fa-cog fa-2x"></i><div><strong>Settings</strong><br><small>Bank Configuration</small></div></a>
            </div>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            var tabs = document.getElementsByClassName('content-card');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            var navTabs = document.getElementsByClassName('nav-tab');
            for (var i = 0; i < navTabs.length; i++) {
                navTabs[i].classList.remove('active');
            }
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }

        window.onload = function() {
            new Chart(document.getElementById('userChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Pending', 'Frozen'],
                    datasets: [{
                        data: [<?php echo max(0, $total_users - $pending_approvals - $frozen_accounts); ?>, <?php echo $pending_approvals; ?>, <?php echo $frozen_accounts; ?>],
                        backgroundColor: ['#48bb78', '#fbbf24', '#f87171'],
                        borderWidth: 0
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });
            new Chart(document.getElementById('transactionChart'), {
                type: 'bar',
                data: {
                    labels: ['Deposits', 'Transfers', 'Withdrawals'],
                    datasets: [{
                        label: 'Transaction Count',
                        data: [12, 19, 3],
                        backgroundColor: ['#667eea', '#764ba2', '#f687b3'],
                        borderRadius: 8
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });
        };
    </script>
</body>
</html>