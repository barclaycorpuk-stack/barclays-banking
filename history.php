<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Force Currency to Euro explicitly for your presentation consistency
$currency_symbol = '€';
$currency_code = 'EUR';

try {
    // 1. Fetch account info with strict fallback bounds
    $stmt = $pdo->prepare("SELECT id, account_number, balance FROM accounts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $account = $stmt->fetch();
    
    if ($account) {
        $account_id = $account['id'];
        $account_number = $account['account_number'];
        $current_balance = (isset($account['balance']) && !empty($account['balance'])) ? floatval($account['balance']) : 0.00;
    } else {
        $account_id = 0;
        $account_number = '0000000000';
        $current_balance = 0.00;
    }
} catch (PDOException $e) {
    die("System Error: " . $e->getMessage());
}

// Handle Export Actions Safely
if (isset($_GET['export']) && $_GET['export'] == 'pdf' && $account_id !== 0) {
    exportToPDF($account_id, $current_balance, $currency_symbol, $pdo);
    exit;
} elseif (isset($_GET['export']) && $_GET['export'] == 'csv' && $account_id !== 0) {
    exportToCSV($account_id, $current_balance, $currency_symbol, $pdo);
    exit;
}

// Parse configuration filter elements
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$filter_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d');
$filter_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

$transactions = [];
$total_in = 0.00;
$total_out = 0.00;
$today_count = 0;

if ($account_id !== 0) {
    // Build filter statements for PostgreSQL
    $sql = "
        SELECT t.*, 
               sender_u.full_name AS sender_name, 
               receiver_u.full_name AS receiver_name,
               sender_a.account_number AS sender_account,
               receiver_a.account_number AS receiver_account,
               t.description
        FROM transactions t
        LEFT JOIN accounts sender_a ON t.sender_account_id = sender_a.id
        LEFT JOIN users sender_u ON sender_a.user_id = sender_u.id
        LEFT JOIN accounts receiver_a ON t.receiver_account_id = receiver_a.id
        LEFT JOIN users receiver_u ON receiver_a.user_id = receiver_u.id
        WHERE (t.sender_account_id = ? OR t.receiver_account_id = ?)
    ";

    $params = [$account_id, $account_id];

    if ($filter_type != 'all') {
        $sql .= " AND t.type = ?";
        $params[] = $filter_type;
    }
    if (!empty($filter_from)) {
        $sql .= " AND DATE(t.created_at) >= ?";
        $params[] = $filter_from;
    }
    if (!empty($filter_to)) {
        $sql .= " AND DATE(t.created_at) <= ?";
        $params[] = $filter_to;
    }

    $sql .= " ORDER BY t.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    // Calculate aggregated ledger boundaries with exact mapping fields
    foreach ($transactions as $t) {
        $amt = floatval($t['amount']);
        if ($t['receiver_account_id'] == $account_id) {
            $total_in += $amt;
        }
        if ($t['sender_account_id'] == $account_id) {
            $total_out += $amt;
        }
    }
    $today_count = count($transactions);
}

function getTransactionDisplay($transaction, $account_id) {
    $is_sent = ($transaction['sender_account_id'] == $account_id);
    if ($is_sent) {
        if (!empty($transaction['description']) && strpos($transaction['description'], 'QR Payment') !== false) {
            return $transaction['description'];
        } elseif (!empty($transaction['receiver_name'])) {
            return "Transfer to " . htmlspecialchars($transaction['receiver_name']);
        } else {
            return "Withdrawal Action";
        }
    } else {
        if (!empty($transaction['description']) && strpos($transaction['description'], 'QR Payment') !== false) {
            return "Received via QR scan";
        } elseif (!empty($transaction['sender_name'])) {
            return "Transfer from " . htmlspecialchars($transaction['sender_name']);
        } else {
            return "System Account Deposit";
        }
    }
}

// PDF Statement Export Module Function
function exportToPDF($account_id, $current_balance, $currency_symbol, $pdo) {
    require_once('fpdf/fpdf.php');
    
    $filter_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d');
    $filter_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
    $filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
    
    $sql = "
        SELECT t.*, sender_u.full_name AS sender_name, receiver_u.full_name AS receiver_name, t.description
        FROM transactions t
        LEFT JOIN accounts sender_a ON t.sender_account_id = sender_a.id
        LEFT JOIN users sender_u ON sender_a.user_id = sender_u.id
        LEFT JOIN accounts receiver_a ON t.receiver_account_id = receiver_a.id
        LEFT JOIN users receiver_u ON receiver_a.user_id = receiver_u.id
        WHERE (t.sender_account_id = ? OR t.receiver_account_id = ?)
    ";
    $params = [$account_id, $account_id];
    
    if ($filter_type != 'all') { $sql .= " AND t.type = ?"; $params[] = $filter_type; }
    if (!empty($filter_from)) { $sql .= " AND DATE(t.created_at) >= ?"; $params[] = $filter_from; }
    if (!empty($filter_to)) { $sql .= " AND DATE(t.created_at) <= ?"; $params[] = $filter_to; }
    $sql .= " ORDER BY t.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT u.full_name, a.account_number FROM users u JOIN accounts a ON u.id = a.user_id WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'BARCLAYS BANKING', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Transaction Statement', 0, 1, 'C');
    $pdf->Ln(10);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 8, 'Period: ' . date('M d, Y', strtotime($filter_from)) . ' - ' . date('M d, Y', strtotime($filter_to)), 0, 1, 'C');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(40, 8, 'Account Holder:', 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 8, $user['full_name'], 0, 1);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(40, 8, 'Account Number:', 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 8, '****' . substr($user['account_number'], -4), 0, 1);
    $pdf->Ln(10);
    
    if (count($transactions) == 0) {
        $pdf->Cell(0, 10, 'No transactions found for this period.', 0, 1, 'C');
    } else {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(56, 189, 248);
        $pdf->Cell(40, 10, 'Date', 1, 0, 'C', true);
        $pdf->Cell(80, 10, 'Description', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Amount', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Type', 1, 1, 'C', true);
        
        foreach ($transactions as $t) {
            $is_sent = ($t['sender_account_id'] == $account_id);
            $type = $is_sent ? 'DEBIT' : 'CREDIT';
            $sign = $is_sent ? '-' : '+';
            $description = getTransactionDisplay($t, $account_id);
            
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(40, 8, date('M d, Y', strtotime($t['created_at'])), 1, 0, 'L');
            $pdf->Cell(80, 8, substr($description, 0, 40), 1, 0, 'L');
            $pdf->Cell(30, 8, $sign . ' ' . $currency_symbol . number_format($t['amount'], 2), 1, 0, 'R');
            $pdf->Cell(30, 8, $type, 1, 1, 'C');
        }
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(50, 8, 'Current Account Balance: ', 0, 0);
        $pdf->Cell(30, 8, $currency_symbol . number_format($current_balance, 2), 0, 1);
    }
    $pdf->Output('D', 'statement_' . date('Y-m-d') . '.pdf');
    exit;
}

// CSV Export Module Function
function exportToCSV($account_id, $current_balance, $currency_symbol, $pdo) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Description', 'Amount', 'Type']);
    
    // Simple fetch query for csv mapping bounds
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE sender_account_id = ? OR receiver_account_id = ? ORDER BY created_at DESC");
    $stmt->execute([$account_id, $account_id]);
    $rows = $stmt->fetchAll();
    
    foreach ($rows as $t) {
        $is_sent = ($t['sender_account_id'] == $account_id);
        $description = getTransactionDisplay($t, $account_id);
        fputcsv($output, [
            date('Y-m-d H:i', strtotime($t['created_at'])),
            $description,
            ($is_sent ? '-' : '+') . $currency_symbol . number_format($t['amount'], 2),
            $is_sent ? 'DEBIT' : 'CREDIT'
        ]);
    }
    fputcsv($output, []);
    fputcsv($output, ['Current Balance Pool:', $currency_symbol . number_format($current_balance, 2)]);
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - Barclays Banking</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #1e293b;
            --accent: #38bdf8;
            --text: #f8fafc;
            --success: #10b981;
            --danger: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--primary);
            color: var(--text);
            min-height: 100vh;
            padding: 30px 20px;
        }
        .container { max-width: 1100px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .back-btn { width: 45px; height: 45px; background: var(--secondary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s; }
        .back-btn:hover { transform: translateX(-4px); background: rgba(255,255,255,0.1); }
        .header h1 { font-size: 1.8rem; color: white; display: flex; align-items: center; gap: 10px; }
        .account-badge { background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); padding: 8px 20px; border-radius: 50px; color: var(--accent); font-weight: 600; font-size: 0.9rem; }

        .date-info { background: var(--secondary); border: 1px solid rgba(255,255,255,0.05); border-radius: 15px; padding: 15px 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; border-left: 4px solid var(--accent); }
        
        /* Stats Panel Cards Row Layout */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--secondary); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 20px; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); border-color: var(--accent); }
        .stat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .stat-header h3 { color: #94a3b8; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .stat-icon { width: 35px; height: 35px; background: rgba(255,255,255,0.05); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--accent); }
        .stat-amount { font-size: 1.8rem; font-weight: 700; }
        .stat-amount.in { color: var(--success); }
        .stat-amount.out { color: var(--danger); }
        .stat-amount.net { color: var(--accent); }

        /* Filter Section controls */
        .filter-section { background: var(--secondary); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 25px; margin-bottom: 30px; }
        .filter-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 0.8rem; color: #cbd5e1; font-weight: 500; }
        .filter-group select, .filter-group input { padding: 10px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white; font-size: 0.9rem; }
        .filter-group select:focus, .filter-group input:focus { outline: none; border-color: var(--accent); }
        .filter-actions { display: flex; gap: 10px; align-items: flex-end; }
        .btn-filter { width: 100%; padding: 11px; background: var(--accent); color: var(--primary); border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-reset { width: 100%; padding: 10px; background: transparent; color: #94a3b8; border: 1px solid #334155; border-radius: 8px; text-decoration: none; text-align: center; font-size: 0.9rem; font-weight: 600; }

        .export-buttons { display: flex; gap: 10px; margin-bottom: 20px; justify-content: flex-end; }
        .btn-export { padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 8px; }
        .btn-export.csv { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .btn-export.pdf { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }

        /* Custom Transaction Records Layout Grid */
        .transactions-container { background: var(--secondary); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; overflow: hidden; }
        .transaction-header { display: grid; grid-template-columns: 2fr 1.2fr 1fr 1fr; padding: 15px 20px; background: #0f172a; border-bottom: 1px solid #334155; font-weight: 600; font-size: 0.85rem; color: #94a3b8; }
        .transaction-item { display: grid; grid-template-columns: 2fr 1.2fr 1fr 1fr; padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.92rem; align-items: center; }
        .transaction-item:last-child { border-bottom: none; }
        .transaction-badge { background: rgba(255,255,255,0.05); padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.5px; }
        .transaction-amount { font-weight: 600; text-align: right; padding-right: 15px; }
        .transaction-amount.plus { color: var(--success); }
        .transaction-amount.minus { color: var(--danger); }
        
        .empty-state { text-align: center; padding: 50px 20px; color: #64748b; }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; opacity: 0.4; }

        @media (max-width: 800px) {
            .stats-grid, .filter-grid { grid-template-columns: 1fr; }
            .transaction-header { display: none; }
            .transaction-item { grid-template-columns: 1fr; gap: 8px; border-bottom: 1px solid #334155; }
            .transaction-amount { text-align: left; font-size: 1.1rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-line-left"></i><i class="fas fa-arrow-left"></i></a>
                <h1><i class="fas fa-history"></i> Transaction History</h1>
            </div>
            <div class="account-badge">A/C: ****<?php echo substr($account_number, -4); ?></div>
        </div>

        <div class="date-info">
            <div><i class="fas fa-calendar-alt" style="color:var(--accent); margin-right:8px;"></i><strong>Statement Frame: <?php if ($filter_from == $filter_to) { echo date('F j, Y', strtotime($filter_from)); } else { echo date('M j, Y', strtotime($filter_from)) . ' - ' . date('M j, Y', strtotime($filter_to)); } ?></strong></div>
            <div class="transaction-badge" style="background:var(--accent); color:var(--primary);"><?php echo $today_count; ?> Record Found</div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-header"><h3>Total Inflow</h3><div class="stat-icon"><i class="fas fa-arrow-down"></i></div></div><div class="stat-amount in">+<?php echo $currency_symbol; ?><?php echo number_format($total_in, 2); ?></div></div>
            <div class="stat-card"><div class="stat-header"><h3>Total Outflow</h3><div class="stat-icon"><i class="fas fa-arrow-up"></i></div></div><div class="stat-amount out">-<?php echo $currency_symbol; ?><?php echo number_format($total_out, 2); ?></div></div>
            <div class="stat-card"><div class="stat-header"><h3>Net Ledger Pool</h3><div class="stat-icon"><i class="fas fa-wallet"></i></div></div><div class="stat-amount net"><?php echo $currency_symbol; ?><?php echo number_format($current_balance, 2); ?></div></div>
        </div>

        <div class="filter-section">
            <form method="GET" class="filter-grid">
                <div class="filter-group">
                    <label>Transaction Classification</label>
                    <select name="type">
                        <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <option value="deposit" <?php echo $filter_type == 'deposit' ? 'selected' : ''; ?>>Deposits</option>
                        <option value="transfer" <?php echo $filter_type == 'transfer' ? 'selected' : ''; ?>>Transfers</option>
                        <option value="withdrawal" <?php echo $filter_type == 'withdrawal' ? 'selected' : ''; ?>>Withdrawals</option>
                    </select>
                </div>
                <div class="filter-group"><label>From Date</label><input type="date" name="from" value="<?php echo $filter_from; ?>"></div>
                <div class="filter-group"><label>To Date</label><input type="date" name="to" value="<?php echo $filter_to; ?>"></div>
                <div class="filter-actions"><button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button><a href="history.php" class="btn-reset">Reset</a></div>
            </form>
        </div>

        <div class="export-buttons">
            <a href="?export=csv&type=<?php echo $filter_type; ?>&from=<?php echo $filter_from; ?>&to=<?php echo $filter_to; ?>" class="btn-export csv"><i class="fas fa-file-csv"></i> Save CSV</a>
            <a href="?export=pdf&type=<?php echo $filter_type; ?>&from=<?php echo $filter_from; ?>&to=<?php echo $filter_to; ?>" class="btn-export pdf"><i class="fas fa-file-pdf"></i> Save PDF Statement</a>
        </div>

        <div class="transactions-container">
            <?php if (empty($transactions)): ?>
                <div class="empty-state"><i class="fas fa-folder-open"></i><h3>No Logs Found</h3><p>No logged banking actions correspond to the set filtering boundaries.</p></div>
            <?php else: ?>
                <div class="transaction-header"><div>Description Description</div><div>Timestamp</div><div>Type</div><div style="text-align:right; padding-right:25px;">Amount</div></div>
                <?php foreach ($transactions as $t): 
                    $is_sent = ($t['sender_account_id'] == $account_id); 
                    $is_qr = (!empty($t['description']) && strpos($t['description'], 'QR Payment') !== false); 
                    $amount_class = $is_sent ? 'minus' : 'plus'; 
                    $amount_sign = $is_sent ? '-' : '+'; 
                    $display_text = getTransactionDisplay($t, $account_id);
                ?>
                <div class="transaction-item">
                    <div><strong><?php echo $display_text; ?></strong></div>
                    <div style="color: #cbd5e1;"><i class="far fa-clock" style="margin-right:6px; color:var(--accent);"></i><?php echo date('M d, Y h:i A', strtotime($t['created_at'])); ?></div>
                    <div><span class="transaction-badge"><?php echo strtoupper($t['type']); ?></span></div>
                    <div class="transaction-amount <?php echo $amount_class; ?>"><?php echo $amount_sign; ?><?php echo $currency_symbol; ?><?php echo number_format($t['amount'], 2); ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>