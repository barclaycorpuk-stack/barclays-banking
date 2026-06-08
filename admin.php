<?php
session_start();
require_once 'db.php';

// Security Check: Only allow Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("<h2 style='color:red;text-align:center;margin-top:50px;'>ACCESS DENIED.<br>You are not an administrator.</h2><center><a href='login.php'>Go Back</a></center>");
}

$message = "";
$error = "";
$currency_symbol = '€';
$currency_code = 'EUR';

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
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'deposit', 'Deposit Rejected', 'Your deposit request has been rejected.', NOW())");
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
        $stmt = $pdo->prepare("SELECT user_id FROM loan_applications WHERE id = ?");
        $stmt->execute([$loan_id]);
        $loan_user_id = $stmt->fetchColumn();
        
        $pdo->prepare("UPDATE loan_applications SET status = 'approved', approved_date = NOW() WHERE id = ?")->execute([$loan_id]);
        
        // Add notification for user
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'loan', 'Loan Approved', 'Congratulations! Your loan application has been approved.', NOW())");
        $stmt->execute([$loan_user_id]);
        
        $message = "Loan approved successfully!";
        
    } elseif ($action == 'reject_loan') {
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

// Fetch users with explicit fallback query overrides to clean out buffer leaks
$users = $pdo->query("
    SELECT u.*, 
           COALESCE(a.balance, 0.00) as balance, 
           a.account_number 
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

// Detect suspicious transactions (over 10,000)
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
    SELECT la.*, u.full_name, u.email, COALESCE(a.balance, 0.00) as balance 
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); min-height: 100vh; color: #f8fafc; }
        .navbar { background: #1e293b; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); padding: 1rem 2rem; position: sticky; top: 0; z-index: 1000; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .logo { font-size: 1.6rem; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; }
        .logo span { color: #38bdf8; }
        .nav-right { display: flex; align-items: center; gap: 2rem; }
        .admin-badge { background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); color: #38bdf8; padding: 0.5rem 1.5rem; border-radius: 50px; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; }
        .logout-btn { background: #ef4444; color: white; padding: 0.5rem 1.5rem; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; transition: 0.3s; }
        .logout-btn:hover { background: #dc2626; transform: translateY(-2px); }
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 2rem; }
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; background: #14532d; color: #4ade80; border: 1px solid #166534; }
        .welcome-section { background: #1e293b; border-radius: 20px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.05); }
        .welcome-section h1 { font-size: 2.2rem; font-weight: 700; color: white; margin-bottom: 0.5rem; }
        .welcome-section p { color: #94a3b8; font-size: 1.05rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: #1e293b; border-radius: 20px; padding: 1.8rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.05); display: flex; align-items: center; justify-content: space-between; }
        .stat-info h3 { font-size: 2rem; font-weight: 700; color: white; margin-bottom: 0.3rem; }
        .stat-info p { color: #94a3b8; font-size: 0.85rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-icon { width: 55px; height: 55px; background: rgba(56, 189, 248, 0.1); border-radius: 15px; display: flex; align-items: center; justify-content: center; color: #38bdf8; font-size: 1.6rem; }
        .nav-tabs { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .nav-tab { padding: 0.8rem 1.8rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 50px; color: #cbd5e1; font-weight: 600; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 0.8rem; }
        .nav-tab.active { background: #38bdf8; color: #0f172a; box-shadow: 0 10px 30px rgba(56, 189, 248, 0.2); }
        .badge-count { background: #334155; color: white; padding: 0.2rem 0.7rem; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
        .badge-count.warning { background: #ef4444; }
        .content-card { background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; padding: 2rem; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3); display: none; }
        .content-card.active { display: block; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .card-header h2 { font-size: 1.6rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 1rem; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; color: #94a3b8; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #334155; }
        td { padding: 1rem; border-bottom: 1px solid #334155; color: #e2e8f0; }
        tr:hover { background: #0f172a; }
        .user-cell { display: flex; align-items: center; gap: 1rem; }
        .user-avatar { width: 40px; height: 40px; border-radius: 10px; background: #38bdf8; display: flex; align-items: center; justify-content: center; color: #0f172a; font-weight: 700; font-size: 1.1rem; }
        .status-badge { padding: 0.3rem 0.9rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; display: inline-block; text-transform: uppercase; }
        .status-approved { background: #14532d; color: #4ade80; }
        .status-pending { background: #78350f; color: #fef08a; }
        .status-rejected { background: #7f1d1d; color: #fca5a5; }
        .status-frozen { background: #4c1d95; color: #e9d8fd; }
        .role-badge { padding: 0.3rem 0.9rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
        .role-admin { background: #7f1d1d; color: #fca5a5; }
        .role-user { background: #14532d; color: #4ade80; }
        .action-btn { padding: 0.5rem 0.8rem; border-radius: 8px; font-size: 0.8rem; font-weight: 600; text-decoration: none; margin: 0 0.2rem; display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; border: none; cursor: pointer; color: white; }
        .btn-approve { background: #10b981; }
        .btn-reject { background: #ef4444; }
        .btn-freeze { background: #f59e0b; }
        .btn-unfreeze { background: #38bdf8; color: #0f172a; }
        .btn-view { background: #6366f1; }
        .action-btn:hover { transform: translateY(-2px); filter: brightness(1.1); }
        .suspicious-row { background: rgba(239, 68, 68, 0.08); border-left: 4px solid #ef4444; }
        .chart-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem; }
        .chart-card { background: #1e293b; border: 1px solid rgba(255,255,255,0.05); border-radius: 15px; padding: 1.5rem; }
        .chart-card h3 { color: white; margin-bottom: 1rem; font-size: 1.1rem; }
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 2rem; }
        .quick-action { background: linear-gradient(135deg, #38bdf8, #0284c7); color: #0f172a; padding: 1.5rem; border-radius: 15px; text-decoration: none; transition: 0.3s; display: flex; align-items: center; gap: 1rem; }
        .quick-action:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(56, 189, 248, 0.3); }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-university" style="color:#38bdf8;"></i> BARCLAYS <span>ADMIN</span>
        </div>
        <div class="nav-right">
            <div class="admin-badge">
                <i class="fas fa-shield-alt"></i> Control Center
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container">
        <?php if (!empty($message)): ?>
        <div class="alert">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="welcome-section">
            <h1>System Overview Terminal</h1>
            <p>Evaluating active account clusters and system node verification pools (Currency: <?php echo $currency_code; ?> <?php echo $currency_symbol; ?>)</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Registered Users</p>
                </div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $pending_approvals; ?></h3>
                    <p>Awaiting Approval</p>
                </div>
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $total_transactions; ?></h3>
                    <p>Logged Transactions</p>
                </div>
                <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <h3><?php echo $frozen_accounts; ?></h3>
                    <p>Locked Core Nodes</p>
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

        <div id="users" class="content-card active">
            <div class="card-header">
                <h2><i class="fas fa-user-cog" style="color:#38bdf8;"></i> User Control Deck</h2>
                <div class="badge-count"><?php echo count($users); ?> Total Clusters</div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>User Profile Node</th>
                            <th>Account String</th>
                            <th>Balance Metric</th>
                            <th>Authority Role</th>
                            <th>Status State</th>
                            <th>Management Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): 
                            // Enforce clean decimal float check directly inside current parsing row context loop bounds
                            $sanitized_balance = (isset($u['balance']) && !empty($u['balance'])) ? floatval($u['balance']) : 0.00;
                        ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">
                                        <?php echo substr($u['full_name'] ?? 'U', 0, 1); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($u['full_name'] ?? 'N/A'); ?></strong>
                                        <br>
                                        <small style="color:#94a3b8;"><?php echo htmlspecialchars($u['email'] ?? ''); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td style="font-family:monospace; letter-spacing:0.5px;">****<?php echo substr($u['account_number'] ?? '0000', -4); ?></td>
                            <td><strong style="color:#38bdf8;"><?php echo $currency_symbol; ?><?php echo number_format($sanitized_balance, 2); ?></strong></td>
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
                </table>
            </div>
        </div>

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
                            <td><span class="role-badge" style="background:#334155; color:white;"><?php echo ucfirst($t['type']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="suspicious" class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> Suspicious Transactions</h2>
                <div class="badge-count" style="background:#ef4444;">High Value > <?php echo $currency_symbol; ?>10,000</div>
            </div>
            <?php if (empty($suspicious)): ?>
                <div style="text-align: center; padding: 4rem;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: #10b981; margin-bottom: 1rem;"></i>
                    <h3>No Suspicious Transactions</h3>
                    <p style="color:#94a3b8; margin-top:5px;">All transaction nodes operate inside default boundaries.</p>
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
                                <td style="color:#ef4444;"><strong><?php echo $currency_symbol; ?><?php echo number_format($s['amount'], 2); ?></strong></td>
                                <td><?php echo ucfirst($s['type']); ?></td>
                                <td><a href="#" class="action-btn btn-view"><i class="fas fa-eye"></i> View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div id="deposits" class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-wallet"></i> Deposit Requests</h2>
                <div class="badge-count">Pending: <?php echo $pending_deposits_count; ?></div>
            </div>
            <?php if (empty($all_deposits)): ?>
                <div style="text-align: center; padding: 4rem;">
                    <i class="fas fa-inbox" style="font-size: 4rem; color: #475569; margin-bottom: 1rem;"></i>
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
                                <td>#<?php echo $deposit['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($deposit['full_name']); ?></strong>
                                    <br>
                                    <small style="color:#94a3b8;"><?php echo $deposit['email']; ?></small>
                                </td>
                                <td><strong><?php echo $currency_symbol; ?><?php echo number_format($deposit['amount'], 2); ?></strong></td>
                                <td><?php echo str_replace('_', ' ', ucfirst($deposit['payment_method'])); ?></td>
                                <td><?php echo htmlspecialchars(substr($deposit['reason'], 0, 40)); ?>...</td>
                                <td><?php echo date('M d, Y', strtotime($deposit['requested_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $deposit['status']; ?>">
                                        <?php echo ucfirst($deposit['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($deposit['status'] == 'pending'): ?>
                                        <a href="?action=approve_deposit&deposit_id=<?php echo $deposit['id']; ?>&note=Approved" class="action-btn btn-approve" onclick="return confirm('Approve deposit?')"><i class="fas fa-check"></i> Approve</a>
                                        <a href="?action=reject_deposit&deposit_id=<?php echo $deposit['id']; ?>&note=Rejected" class="action-btn btn-reject" onclick="return confirm('Reject deposit?')"><i class="fas fa-times"></i> Reject</a>
                                    <?php else: ?>
                                        <span style="color: #64748b;">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div id="loans" class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-hand-holding-usd"></i> Loan Applications</h2>
                <div class="badge-count">Pending: <?php echo $pending_loans_count; ?></div>
            </div>
            <?php if (empty($all_loans)): ?>
                <div style="text-align: center; padding: 4rem;">
                    <i class="fas fa-inbox" style="font-size: 4rem; color: #475569; margin-bottom: 1rem;"></i>
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
                            <?php foreach ($all_loans as $loan): 
                                $sanitized_loan_balance = (isset($loan['balance']) && !empty($loan['balance'])) ? floatval($loan['balance']) : 0.00;
                            ?>
                            <tr>
                                <td>#<?php echo $loan['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($loan['full_name']); ?></strong>
                                    <br>
                                    <small style="color:#94a3b8;">A/C Liquidity: <?php echo $currency_symbol . number_format($sanitized_loan_balance, 2); ?></small>
                                </td>
                                <td><strong><?php echo $currency_symbol; ?><?php echo number_format($loan['amount'], 2); ?></strong></td>
                                <td><?php echo ucfirst($loan['loan_type']); ?></td>
                                <td><?php echo $loan['tenure']; ?> months</td>
                                <td><?php echo $currency_symbol; ?><?php echo number_format($loan['emi'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($loan['applied_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $loan['status']; ?>">
                                        <?php echo ucfirst($loan['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($loan['status'] == 'pending'): ?>
                                        <a href="?action=approve_loan&loan_id=<?php echo $loan['id']; ?>" class="action-btn btn-approve" onclick="return confirm('Approve this loan?')"><i class="fas fa-check"></i> Approve</a>
                                        <a href="?action=reject_loan&loan_id=<?php echo $loan['id']; ?>" class="action-btn btn-reject" onclick="return confirm('Reject this loan?')"><i class="fas fa-times"></i> Reject</a>
                                    <?php else: ?>
                                        <span style="color: #64748b;">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div id="analytics" class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-chart-line"></i> Analytics Dashboard</h2>
            </div>
            <div class="chart-container">
                <div class="chart-card">
                    <h3 style="color:#94a3b8;">User Status Distribution</h3>
                    <canvas id="userChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3 style="color:#94a3b8;">Transaction Types</h3>
                    <canvas id="transactionChart"></canvas>
                </div>
            </div>
            <div class="quick-actions">
                <a href="#" class="quick-action" style="color:#0f172a;"><i class="fas fa-file-pdf fa-2x"></i><div><strong>Generate Report</strong><br><small>Download PDF</small></div></a>
                <a href="#" class="quick-action" style="color:#0f172a;"><i class="fas fa-envelope fa-2x"></i><div><strong>Email All Users</strong><br><small>Send Notification</small></div></a>
                <a href="#" class="quick-action" style="color:#0f172a;"><i class="fas fa-cog fa-2x"></i><div><strong>Settings</strong><br><small>Bank Configuration</small></div></a>
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
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                        borderWidth: 0
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8' } } } }
            });
            new Chart(document.getElementById('transactionChart'), {
                type: 'bar',
                data: {
                    labels: ['Deposits', 'Transfers', 'Withdrawals'],
                    datasets: [{
                        label: 'Transaction Count',
                        data: [12, 19, 3],
                        backgroundColor: ['#38bdf8', '#6366f1', '#ec4899'],
                        borderRadius: 8
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { color: '#94a3b8' } }, x: { ticks: { color: '#94a3b8' } } } }
            });
        };
    </script>
</body>
</html>