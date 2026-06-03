<?php
session_start();
require_once 'db.php';

// Security Check: Make sure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$currency_symbol = CURRENCY_SYMBOL; // €
$currency_code = CURRENCY_CODE; // EUR

// 1. Fetch user details including profile pic, account number, and balance
$stmt = $pdo->prepare("
    SELECT u.full_name, u.username, u.profile_pic, a.account_number, a.balance, a.account_type 
    FROM users u 
    JOIN accounts a ON u.id = a.user_id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// If account_type is not set in database, default to 'Savings'
if (!isset($user['account_type']) || empty($user['account_type'])) {
    $user['account_type'] = 'Savings';
}

// 2. Fetch Account ID
$stmt = $pdo->prepare("SELECT id FROM accounts WHERE user_id = ?");
$stmt->execute([$user_id]);
$account_id = $stmt->fetchColumn();

// 3. Get view mode from session or default to 'daily'
if (!isset($_SESSION['view_mode'])) {
    $_SESSION['view_mode'] = 'daily';
}
if (isset($_GET['view'])) {
    $_SESSION['view_mode'] = $_GET['view'];
}
$view_mode = $_SESSION['view_mode'];

// 4. Get transactions based on view mode (PostgreSQL Compatible Syntax)
if ($view_mode == 'daily') {
    // Today's transactions only (Changed CURDATE() to CURRENT_DATE)
    $stmt = $pdo->prepare("
        SELECT * FROM transactions 
        WHERE (sender_account_id = ? OR receiver_account_id = ?) 
        AND DATE(created_at) = CURRENT_DATE
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$account_id, $account_id]);
    $recent_transactions = $stmt->fetchAll();
    
    // Calculate today's stats (Changed CURDATE() to CURRENT_DATE)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN receiver_account_id = ? THEN amount ELSE 0 END) as today_received,
            SUM(CASE WHEN sender_account_id = ? THEN amount ELSE 0 END) as today_sent
        FROM transactions 
        WHERE (sender_account_id = ? OR receiver_account_id = ?) 
        AND DATE(created_at) = CURRENT_DATE
    ");
    $stmt->execute([$account_id, $account_id, $account_id, $account_id]);
    $today_stats = $stmt->fetch();
    
    $total_received = $today_stats['today_received'] ?? 0;
    $total_sent = $today_stats['today_sent'] ?? 0;
    
} else {
    // Monthly view (Changed DATE_SUB syntax to PostgreSQL interval calculation syntax)
    $stmt = $pdo->prepare("
        SELECT * FROM transactions 
        WHERE (sender_account_id = ? OR receiver_account_id = ?) 
        AND created_at >= NOW() - INTERVAL '30 days'
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$account_id, $account_id]);
    $recent_transactions = $stmt->fetchAll();
    
    // Calculate monthly stats (Changed DATE_SUB syntax to PostgreSQL interval calculation syntax)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN receiver_account_id = ? THEN amount ELSE 0 END) as monthly_received,
            SUM(CASE WHEN sender_account_id = ? THEN amount ELSE 0 END) as monthly_sent
        FROM transactions 
        WHERE (sender_account_id = ? OR receiver_account_id = ?) 
        AND created_at >= NOW() - INTERVAL '30 days'
    ");
    $stmt->execute([$account_id, $account_id, $account_id, $account_id]);
    $monthly_stats = $stmt->fetch();
    
    $total_received = $monthly_stats['monthly_received'] ?? 0;
    $total_sent = $monthly_stats['monthly_sent'] ?? 0;
}

// 5. Calculate quick stats (all time for comparison)
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE sender_account_id = ? OR receiver_account_id = ?");
$stmt->execute([$account_id, $account_id]);
$all_transactions = $stmt->fetchAll();

// 6. Time-based greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour < 17) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}

// 7. Account age (from first transaction or use default)
$account_age_days = 45; // Default
if (!empty($all_transactions)) {
    $first_tx = end($all_transactions);
    if (isset($first_tx['created_at'])) {
        $account_age_days = floor((time() - strtotime($first_tx['created_at'])) / (60 * 60 * 24));
    }
}

// 8. Get unread notifications count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_notifications = $stmt->fetchColumn();

// 9. Prepare Data for the QR Code Modal
$branch_name = "Main Branch"; 
$qr_text = "Bank: Barclays Banking\nName: " . $user['full_name'] . "\nAccount No: " . $user['account_number'] . "\nBranch: " . $branch_name;
$qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($qr_text);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Barclays Banking</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #1e293b;
            --accent: #38bdf8;
            --text: #f8fafc;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gold: #d4af37;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--primary);
            color: var(--text);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        header {
            background: var(--secondary);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .logo { font-size: 1.5rem; font-weight: 700; letter-spacing: 1px; }
        .logo span { color: var(--accent); }
        .nav-user { display: flex; align-items: center; gap: 15px; }
        .nav-user img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent); }
        .logout-btn {
            padding: 8px 15px; background: transparent; border: 1px solid var(--accent);
            color: var(--accent); border-radius: 20px; text-decoration: none; font-size: 0.9rem; transition: 0.3s;
        }
        .logout-btn:hover { background: var(--accent); color: var(--primary); }
        .notification-bell { position: relative; color: white; text-decoration: none; margin-right: 5px; }
        .notification-bell i { font-size: 1.3rem; }
        .notification-badge { position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 50%; }
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .welcome-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .welcome-text { font-size: 1.5rem; font-weight: 500; }
        .welcome-text span { color: var(--accent); font-weight: 600; }
        .view-toggle { display: flex; gap: 10px; background: var(--secondary); padding: 5px; border-radius: 40px; border: 1px solid rgba(255,255,255,0.1); }
        .view-btn { padding: 8px 25px; border-radius: 30px; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: 0.3s; color: #94a3b8; }
        .view-btn.active { background: var(--accent); color: var(--primary); }
        .view-btn:hover:not(.active) { background: rgba(56, 189, 248, 0.2); color: white; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: var(--secondary); border: 1px solid rgba(255,255,255,0.05); border-radius: 15px; padding: 15px; display: flex; align-items: center; gap: 15px; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); border-color: var(--accent); }
        .stat-icon { width: 45px; height: 45px; background: rgba(56, 189, 248, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.3rem; }
        .stat-info h3 { font-size: 1.1rem; font-weight: 600; margin-bottom: 3px; }
        .stat-info p { font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        .dashboard-top-row { display: flex; gap: 30px; margin-bottom: 30px; flex-wrap: wrap; }
        .atm-card { background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); width: 350px; height: 240px; border-radius: 20px; padding: 25px; color: white; position: relative; box-shadow: 0 15px 35px rgba(0,0,0,0.3); box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; flex-shrink: 0; }
        .contactless { position: absolute; top: 25px; right: 25px; font-size: 1.5rem; opacity: 0.8; }
        .chip { width: 45px; height: 30px; background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%); border-radius: 5px; margin-bottom: 15px; }
        .balance-label { font-size: 0.8rem; text-transform: uppercase; opacity: 0.9; display: flex; justify-content: space-between; }
        .account-type-badge { background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 12px; font-size: 0.6rem; text-transform: uppercase; }
        .balance-amount { font-size: 2.2rem; font-weight: 700; margin-bottom: 15px; }
        .account-num { font-family: monospace; font-size: 1.2rem; letter-spacing: 2px; }
        .card-footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 15px; }
        .expiry { font-size: 0.7rem; text-transform: uppercase; opacity: 0.8; }
        .expiry strong { font-size: 0.9rem; display: block; }
        .logo-type { font-size: 1.5rem; font-weight: bold; font-style: italic; opacity: 0.9; }
        .activity-panel { flex: 1; min-width: 300px; background: var(--secondary); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 25px; }
        .activity-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .activity-header h3 { margin: 0; font-size: 1.1rem; color: white; }
        .activity-header a { color: var(--accent); text-decoration: none; font-size: 0.85rem; }
        .activity-header a:hover { text-decoration: underline; }
        .period-badge { background: rgba(212, 175, 55, 0.2); color: var(--gold); padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 5px; }
        .activity-list { max-height: 350px; overflow-y: auto; padding-right: 5px; }
        .activity-list::-webkit-scrollbar { width: 5px; }
        .activity-list::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        .activity-list::-webkit-scrollbar-thumb { background: var(--accent); border-radius: 5px; }
        .tx-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .tx-item:last-child { border-bottom: none; }
        .tx-icon { width: 35px; height: 35px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 0.9rem; margin-right: 15px; }
        .tx-icon.in { background: rgba(52, 211, 153, 0.2); color: #34d399; }
        .tx-icon.out { background: rgba(248, 113, 113, 0.2); color: #f87171; }
        .tx-icon.deposit { background: rgba(56, 189, 248, 0.2); color: var(--accent); }
        .tx-details { flex: 1; }
        .tx-details p { margin: 0; font-size: 0.9rem; color: #e2e8f0; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .tx-details small { color: #94a3b8; font-size: 0.75rem; display: block; margin-top: 2px; }
        .tx-amount { font-weight: 600; font-size: 0.95rem; }
        .tx-amount.in { color: #34d399; }
        .tx-amount.out { color: #f87171; }
        .tx-amount.deposit { color: var(--accent); }
        .empty-state { text-align: center; color: #94a3b8; padding: 40px 20px; font-size: 0.9rem; }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; opacity: 0.5; }
        .section-title { font-size: 1.2rem; margin-bottom: 20px; font-weight: 600; text-align: center; }
        .actions-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; max-width: 950px; margin: 0 auto; }
        .action-card { background: var(--secondary); border: 1px solid rgba(255,255,255,0.05); border-radius: 15px; padding: 20px 10px; text-align: center; text-decoration: none; color: var(--text); transition: 0.3s; cursor: pointer; }
        .action-card:hover { transform: translateY(-5px); background: rgba(56, 189, 248, 0.1); border-color: rgba(56, 189, 248, 0.3); }
        .action-card i { font-size: 1.8rem; color: var(--accent); margin-bottom: 10px; }
        .action-card h3 { font-size: 0.85rem; margin: 0; font-weight: 500; }
        .qr-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); z-index: 2000; justify-content: center; align-items: center; }
        .qr-modal-card { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 25px; padding: 40px 30px; width: 320px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.5), 0 0 20px rgba(56, 189, 248, 0.1); position: relative; animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes popIn { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        .close-btn { position: absolute; top: 15px; right: 20px; font-size: 1.5rem; color: #64748b; cursor: pointer; transition: 0.2s; }
        .close-btn:hover { color: #f87171; transform: scale(1.1); }
        .qr-box { background: white; padding: 15px; border-radius: 15px; display: inline-block; margin: 20px 0; box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        .qr-modal-card h2 { color: white; margin: 0; font-size: 1.5rem; }
        .qr-modal-card p { color: #94a3b8; font-size: 0.9rem; margin-top: 5px; }
        .qr-details { margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; }
        .qr-details div { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.85rem; }
        .qr-details span { color: var(--accent); font-weight: bold; letter-spacing: 1px; }
        @media (max-width: 850px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .actions-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 480px) { .stats-grid { grid-template-columns: 1fr; } .actions-grid { grid-template-columns: repeat(2, 1fr); } .welcome-section { flex-direction: column; align-items: flex-start; } .view-toggle { align-self: flex-start; } }
    </style>
</head>
<body>

<header>
    <div class="logo">BARCLAYS <span>BANKING</span></div>
    <div class="nav-user">
        <a href="notifications.php" class="notification-bell">
            <i class="fas fa-bell"></i>
            <?php if ($unread_notifications > 0): ?>
            <span class="notification-badge"><?php echo min($unread_notifications, 99); ?></span>
            <?php endif; ?>
        </a>
        
        <?php 
        if (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default.png') {
            $avatar_url = 'uploads/' . htmlspecialchars($user['profile_pic']);
        } else {
            $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=38bdf8&color=0f172a&bold=true';
        }
        ?>
        <a href="profile.php" style="display: flex; align-items: center; gap: 15px; text-decoration: none; color: white;">
            <img src="<?php echo $avatar_url; ?>" alt="Profile Picture">
            <span style="font-weight: 500;">Hello, <?php echo htmlspecialchars($user['full_name']); ?></span>
        </a>
        <a href="logout.php" class="logout-btn">Log Out</a>
    </div>
</header>

<div class="container">
    <div class="welcome-section">
        <div class="welcome-text">
            <?php echo $greeting; ?>, <span><?php echo htmlspecialchars($user['full_name']); ?></span>
        </div>
        <div class="view-toggle">
            <a href="?view=daily" class="view-btn <?php echo $view_mode == 'daily' ? 'active' : ''; ?>">
                <i class="fas fa-sun"></i> Daily
            </a>
            <a href="?view=monthly" class="view-btn <?php echo $view_mode == 'monthly' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> Monthly
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <div class="stat-info">
                <h3><?php echo $currency_symbol; ?><?php echo number_format($user['balance'], 2); ?></h3>
                <p>Current Balance</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-arrow-down"></i></div>
            <div class="stat-info">
                <h3><?php echo $currency_symbol; ?><?php echo number_format($total_received, 2); ?></h3>
                <p><?php echo $view_mode == 'daily' ? 'Today\'s Received' : 'Monthly Received'; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
            <div class="stat-info">
                <h3><?php echo $currency_symbol; ?><?php echo number_format($total_sent, 2); ?></h3>
                <p><?php echo $view_mode == 'daily' ? 'Today\'s Sent' : 'Monthly Sent'; ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <h3><?php echo $currency_symbol; ?><?php echo number_format($total_received - $total_sent, 2); ?></h3>
                <p><?php echo $view_mode == 'daily' ? 'Today\'s Net' : 'Monthly Net'; ?></p>
            </div>
        </div>
    </div>

    <div class="dashboard-top-row">
        <div class="atm-card">
            <div class="contactless"><i class="fas fa-wifi" style="transform: rotate(90deg);"></i></div>
            <div class="chip"></div>
            <div>
                <div class="balance-label">
                    TOTAL BALANCE
                    <span class="account-type-badge"><?php echo $user['account_type']; ?></span>
                </div>
                <div class="balance-amount"><?php echo $currency_symbol; ?><?php echo number_format($user['balance'], 2); ?></div>
                <div class="account-num">**** **** **** <?php echo substr($user['account_number'], -4); ?></div>
            </div>
            <div class="card-footer">
                <div class="expiry">VALID THRU<strong>12/28</strong></div>
                <div class="logo-type">VISA</div>
            </div>
        </div>

        <div class="activity-panel">
            <div class="activity-header">
                <h3>
                    <?php echo $view_mode == 'daily' ? 'Today\'s Activity' : 'Last 30 Days Activity'; ?>
                    <span class="period-badge">
                        <i class="fas <?php echo $view_mode == 'daily' ? 'fa-sun' : 'fa-calendar-alt'; ?>"></i>
                        <?php echo $view_mode == 'daily' ? date('M d, Y') : date('M d', strtotime('-30 days')) . ' - ' . date('M d, Y'); ?>
                    </span>
                </h3>
                <a href="history.php">View All →</a>
            </div>
            
            <div class="activity-list">
                <?php if (empty($recent_transactions)): ?>
                    <div class="empty-state">
                        <i class="fas <?php echo $view_mode == 'daily' ? 'fa-coffee' : 'fa-chart-line'; ?>"></i>
                        <p>No transactions <?php echo $view_mode == 'daily' ? 'today' : 'in the last 30 days'; ?></p>
                        <small>Your activity will appear here</small>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_transactions as $tx): 
                        $is_in = (isset($tx['receiver_account_id']) && $tx['receiver_account_id'] == $account_id);
                        
                        if ($tx['type'] == 'deposit') {
                            $icon_class = 'deposit';
                            $icon = 'fa-circle-down';
                            $sign = '+';
                        } elseif ($is_in) {
                            $icon_class = 'in';
                            $icon = 'fa-arrow-down';
                            $sign = '+';
                        } else {
                            $icon_class = 'out';
                            $icon = 'fa-arrow-up';
                            $sign = '-';
                        }
                        
                        if ($tx['type'] == 'transfer') {
                            $title = $is_in ? 'Transfer Received' : 'Transfer Sent';
                        } elseif ($tx['type'] == 'deposit') {
                            $title = 'Deposit';
                        } elseif ($tx['type'] == 'withdrawal') {
                            $title = 'Withdrawal';
                        } else {
                            $title = ucfirst($tx['type']);
                        }
                        
                        $display_date = $view_mode == 'daily' 
                            ? date('h:i A', strtotime($tx['created_at']))
                            : date('M d, h:i A', strtotime($tx['created_at']));
                    ?>
                    <div class="tx-item">
                        <div class="tx-icon <?php echo $icon_class; ?>">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div class="tx-details">
                            <p>
                                <?php echo $title; ?>
                                <?php if (isset($tx['description'])): ?>
                                    <span class="tx-card-number"><?php echo htmlspecialchars($tx['description']); ?></span>
                                <?php endif; ?>
                            </p>
                            <small><i class="far fa-clock"></i> <?php echo $display_date; ?></small>
                        </div>
                        <div class="tx-amount <?php echo $icon_class; ?>">
                            <?php echo $sign; ?><?php echo $currency_symbol; ?><?php echo number_format($tx['amount'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div>
        <div class="section-title">Quick Actions</div>
        <div class="actions-grid">
            <a href="#" class="action-card" onclick="openQRModal(); return false;">
                <i class="fas fa-qrcode"></i>
                <h3>Receive</h3>
            </a>
            <a href="transfer.php" class="action-card">
                <i class="fas fa-paper-plane"></i>
                <h3>Send Money</h3>
            </a>
            <a href="deposit.php" class="action-card">
                <i class="fas fa-wallet"></i>
                <h3>Deposit</h3>
            </a>
            <a href="apply_loan.php" class="action-card">
                <i class="fas fa-hand-holding-usd"></i>
                <h3>Apply Loan</h3>
            </a>
            <a href="history.php" class="action-card">
                <i class="fas fa-history"></i>
                <h3>History</h3>
            </a>
            <a href="cards.php" class="action-card">
                <i class="fas fa-credit-card"></i>
                <h3>My Cards</h3>
            </a>
        </div>
    </div>
</div>

<div id="qrModal" class="qr-modal-overlay">
    <div class="qr-modal-card">
        <span class="close-btn" onclick="closeQRModal()"><i class="fas fa-times"></i></span>
        <h2>Receive Money</h2>
        <p>Scan to transfer instantly</p>
        
        <div class="qr-box">
            <img src="<?php echo $qr_image_url; ?>" alt="QR Code" width="180" height="180">
        </div>
        
        <div class="qr-details">
            <div>Account Name: <span style="color:white;"><?php echo htmlspecialchars($user['full_name']); ?></span></div>
            <div>A/C Number: <span><?php echo htmlspecialchars($user['account_number']); ?></span></div>
        </div>
    </div>
</div>

<script>
    function openQRModal() { document.getElementById('qrModal').style.display = 'flex'; }
    function closeQRModal() { document.getElementById('qrModal').style.display = 'none'; }
    window.onclick = function(event) {
        let modal = document.getElementById('qrModal');
        if (event.target == modal) { modal.style.display = 'none'; }
    }
</script>

</body>
</html>