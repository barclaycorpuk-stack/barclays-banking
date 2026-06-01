<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$currency_symbol = CURRENCY_SYMBOL;

// Fetch account ID
$stmt = $pdo->prepare("SELECT id, account_number, balance FROM accounts WHERE user_id = ?");
$stmt->execute([$user_id]);
$account = $stmt->fetch();
$account_id = $account['id'];
$account_number = $account['account_number'];
$current_balance = $account['balance'];

// Handle Export
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    exportToPDF($account_id, $pdo);
    exit;
} elseif (isset($_GET['export']) && $_GET['export'] == 'csv') {
    exportToCSV($account_id, $pdo);
    exit;
}

// Get filter values
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$filter_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d');
$filter_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

// Build query with filters
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

// Calculate summary
$total_in = 0;
$total_out = 0;
foreach ($transactions as $t) {
    if ($t['receiver_account_id'] == $account_id) {
        $total_in += $t['amount'];
    }
    if ($t['sender_account_id'] == $account_id) {
        $total_out += $t['amount'];
    }
}

$today_count = count($transactions);

function getTransactionDisplay($transaction, $account_id) {
    $is_sent = ($transaction['sender_account_id'] == $account_id);
    
    if ($is_sent) {
        if (!empty($transaction['description']) && strpos($transaction['description'], 'QR Payment') !== false) {
            return $transaction['description'];
        } elseif (!empty($transaction['receiver_name'])) {
            return "To: " . $transaction['receiver_name'];
        } else {
            return "Withdrawal";
        }
    } else {
        if (!empty($transaction['description']) && strpos($transaction['description'], 'QR Payment') !== false) {
            return "Received via QR";
        } elseif (!empty($transaction['sender_name'])) {
            return "From: " . $transaction['sender_name'];
        } else {
            return "Deposit";
        }
    }
}

// PDF Export Function
function exportToPDF($account_id, $pdo) {
    require_once('fpdf/fpdf.php');
    
    $filter_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d');
    $filter_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
    $filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
    
    $sql = "
        SELECT t.*, 
               sender_u.full_name AS sender_name, 
               receiver_u.full_name AS receiver_name,
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
    
    $stmt = $pdo->prepare("SELECT u.full_name, a.account_number, a.balance FROM users u JOIN accounts a ON u.id = a.user_id WHERE u.id = ?");
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
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'No transactions found for this period.', 0, 1, 'C');
    } else {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(56, 189, 248);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(40, 10, 'Date', 1, 0, 'C', true);
        $pdf->Cell(80, 10, 'Description', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Amount', 1, 0, 'C', true);
        $pdf->Cell(30, 10, 'Type', 1, 1, 'C', true);
        
        $total_in = 0;
        $total_out = 0;
        
        foreach ($transactions as $t) {
            $is_sent = ($t['sender_account_id'] == $account_id);
            
            if ($is_sent) {
                $total_out += $t['amount'];
                $type = 'DEBIT';
                $amount_sign = '-';
            } else {
                $total_in += $t['amount'];
                $type = 'CREDIT';
                $amount_sign = '+';
            }
            
            $description = getTransactionDisplay($t, $account_id);
            
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(40, 8, date('M d, Y', strtotime($t['created_at'])), 1, 0, 'L');
            $pdf->Cell(80, 8, substr($description, 0, 40), 1, 0, 'L');
            $pdf->Cell(30, 8, $amount_sign . CURRENCY_SYMBOL . number_format($t['amount'], 2), 1, 0, 'R');
            $pdf->Cell(30, 8, $type, 1, 1, 'C');
        }
        
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, 'Summary for Period', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        
        $pdf->Cell(50, 8, 'Total Credits (In):', 0, 0);
        $pdf->Cell(30, 8, CURRENCY_SYMBOL . number_format($total_in, 2), 0, 1);
        
        $pdf->Cell(50, 8, 'Total Debits (Out):', 0, 0);
        $pdf->Cell(30, 8, CURRENCY_SYMBOL . number_format($total_out, 2), 0, 1);
        
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(50, 8, 'Current Balance:', 0, 0);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(30, 8, CURRENCY_SYMBOL . number_format($user['balance'], 2), 0, 1);
    }
    
    $pdf->Output('D', 'statement_' . date('Y-m-d') . '.pdf');
    exit;
}

// CSV Export Function
function exportToCSV($account_id, $pdo) {
    $filter_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d');
    $filter_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');
    $filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
    
    $sql = "
        SELECT t.*, 
               sender_u.full_name AS sender_name, 
               receiver_u.full_name AS receiver_name,
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
    
    $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ?");
    $stmt->execute([$account_id]);
    $current_balance = $stmt->fetchColumn();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Description', 'Amount', 'Type']);
    
    foreach ($transactions as $t) {
        $is_sent = ($t['sender_account_id'] == $account_id);
        $description = getTransactionDisplay($t, $account_id);
        $type = $is_sent ? 'DEBIT' : 'CREDIT';
        $amount = ($is_sent ? '-' : '+') . CURRENCY_SYMBOL . number_format($t['amount'], 2);
        
        fputcsv($output, [
            date('Y-m-d H:i', strtotime($t['created_at'])),
            $description,
            $amount,
            $type
        ]);
    }
    
    fputcsv($output, []);
    fputcsv($output, ['Current Balance:', CURRENCY_SYMBOL . number_format($current_balance, 2)]);
    
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
        /* Keep your existing styles - same as before */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 30px 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 30px; padding: 30px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); animation: slideUp 0.5s ease; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .back-btn { width: 45px; height: 45px; background: #0f5c8c; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 1.2rem; transition: 0.3s; box-shadow: 0 5px 15px rgba(15, 92, 140, 0.3); }
        .back-btn:hover { transform: translateX(-5px); background: #0a3d5e; }
        .header h1 { font-size: 2rem; color: #333; display: flex; align-items: center; gap: 10px; }
        .header h1 i { color: #d4af37; }
        .account-badge { background: #e6f0f7; padding: 8px 20px; border-radius: 50px; color: #0f5c8c; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
        .date-info { background: linear-gradient(135deg, #e6f0f7, #dbeafe); border-radius: 15px; padding: 15px 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; border-left: 4px solid #d4af37; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px; }
        .stat-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); transition: 0.3s; border: 1px solid #e2e8f0; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(15, 92, 140, 0.15); }
        .stat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .stat-header h3 { color: #64748b; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; }
        .stat-icon { width: 40px; height: 40px; background: #e6f0f7; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #0f5c8c; }
        .stat-amount { font-size: 2.2rem; font-weight: 700; }
        .stat-amount.in { color: #10b981; }
        .stat-amount.out { color: #f87171; }
        .stat-amount.net { color: #0f5c8c; }
        .stat-sub { color: #94a3b8; font-size: 0.8rem; margin-top: 5px; }
        .filter-section { background: #f8fafc; border-radius: 20px; padding: 25px; margin-bottom: 30px; }
        .filter-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: 600; color: #333; font-size: 0.9rem; display: flex; align-items: center; gap: 5px; }
        .filter-group select, .filter-group input { padding: 12px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 0.95rem; background: white; }
        .filter-actions { display: flex; gap: 10px; align-items: flex-end; }
        .btn-filter { padding: 12px 25px; background: #0f5c8c; color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .btn-reset { padding: 12px 25px; background: white; color: #64748b; border: 2px solid #e2e8f0; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .export-buttons { display: flex; gap: 10px; margin-bottom: 30px; justify-content: flex-end; }
        .btn-export { padding: 12px 25px; border-radius: 12px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-export.csv { background: #10b981; color: white; }
        .btn-export.pdf { background: #f59e0b; color: white; }
        .transactions-container { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .transaction-header { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; padding: 20px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; font-weight: 600; color: #64748b; }
        .transaction-item { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; padding: 20px; border-bottom: 1px solid #e2e8f0; transition: 0.3s; }
        .transaction-amount.plus { color: #10b981; }
        .transaction-amount.minus { color: #f87171; }
        .empty-state { text-align: center; padding: 60px 20px; }
        @media (max-width: 768px) { .stats-grid, .filter-grid { grid-template-columns: 1fr; } .transaction-header { display: none; } .transaction-item { grid-template-columns: 1fr; gap: 10px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
                <h1><i class="fas fa-history"></i> Transaction History</h1>
            </div>
            <div class="account-badge"><i class="fas fa-credit-card"></i> A/C: ****<?php echo substr($account_number, -4); ?></div>
        </div>

        <div class="date-info">
            <div class="date-info-text"><i class="fas fa-calendar-day"></i><strong>Showing transactions for: <?php if ($filter_from == $filter_to) { echo date('F j, Y', strtotime($filter_from)); } else { echo date('M j, Y', strtotime($filter_from)) . ' - ' . date('M j, Y', strtotime($filter_to)); } ?></strong></div>
            <div class="transaction-count"><i class="fas fa-receipt"></i> <?php echo $today_count; ?> transaction<?php echo $today_count != 1 ? 's' : ''; ?></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-header"><h3>Total Received</h3><div class="stat-icon"><i class="fas fa-arrow-down"></i></div></div><div class="stat-amount in">+<?php echo $currency_symbol; ?><?php echo number_format($total_in, 2); ?></div><div class="stat-sub">Money received into account (filtered period)</div></div>
            <div class="stat-card"><div class="stat-header"><h3>Total Sent</h3><div class="stat-icon"><i class="fas fa-arrow-up"></i></div></div><div class="stat-amount out">-<?php echo $currency_symbol; ?><?php echo number_format($total_out, 2); ?></div><div class="stat-sub">Money sent from account (filtered period)</div></div>
            <div class="stat-card"><div class="stat-header"><h3>Net Balance</h3><div class="stat-icon"><i class="fas fa-wallet"></i></div></div><div class="stat-amount net"><?php echo $currency_symbol; ?><?php echo number_format($current_balance, 2); ?></div><div class="stat-sub">Current account balance</div></div>
        </div>

        <div class="filter-section">
            <form method="GET" class="filter-grid">
                <div class="filter-group"><label><i class="fas fa-filter"></i> Transaction Type</label><select name="type"><option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>All Transactions</option><option value="deposit" <?php echo $filter_type == 'deposit' ? 'selected' : ''; ?>>Deposits</option><option value="transfer" <?php echo $filter_type == 'transfer' ? 'selected' : ''; ?>>Transfers</option><option value="withdrawal" <?php echo $filter_type == 'withdrawal' ? 'selected' : ''; ?>>Withdrawals</option></select></div>
                <div class="filter-group"><label><i class="fas fa-calendar"></i> From Date</label><input type="date" name="from" value="<?php echo $filter_from; ?>"></div>
                <div class="filter-group"><label><i class="fas fa-calendar"></i> To Date</label><input type="date" name="to" value="<?php echo $filter_to; ?>"></div>
                <div class="filter-actions"><button type="submit" class="btn-filter"><i class="fas fa-search"></i> Apply Filters</button><a href="history.php" class="btn-reset"><i class="fas fa-sync-alt"></i> Reset to Today</a></div>
            </form>
        </div>

        <div class="export-buttons"><a href="?export=csv&type=<?php echo $filter_type; ?>&from=<?php echo $filter_from; ?>&to=<?php echo $filter_to; ?>" class="btn-export csv"><i class="fas fa-file-csv"></i> Export as CSV</a><a href="?export=pdf&type=<?php echo $filter_type; ?>&from=<?php echo $filter_from; ?>&to=<?php echo $filter_to; ?>" class="btn-export pdf"><i class="fas fa-file-pdf"></i> Export as PDF</a></div>

        <div class="transactions-container">
            <?php if (empty($transactions)): ?>
                <div class="empty-state"><i class="fas fa-search"></i><h3>No Transactions Found</h3><p>No transactions were found for the selected period.</p></div>
            <?php else: ?>
                <div class="transaction-header"><div>Description</div><div>Date & Time</div><div>Type</div><div>Amount</div></div>
                <?php foreach ($transactions as $t): $is_sent = ($t['sender_account_id'] == $account_id); $is_qr = (!empty($t['description']) && strpos($t['description'], 'QR Payment') !== false); $amount_class = $is_sent ? 'minus' : 'plus'; $amount_sign = $is_sent ? '-' : '+'; if ($is_qr) { $display_text = $t['description'] ?? 'QR Payment'; } elseif ($is_sent) { $display_text = "Transfer to " . ($t['receiver_name'] ?? 'Unknown'); } else { $display_text = "Transfer from " . ($t['sender_name'] ?? 'Unknown'); } ?>
                <div class="transaction-item <?php echo $is_qr ? 'qr-payment' : ''; ?>"><div class="transaction-info"><h4><?php echo htmlspecialchars($display_text); ?></h4></div><div class="transaction-date"><i class="far fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($t['created_at'])); ?></div><div class="transaction-type"><span class="transaction-badge"><?php echo strtoupper($t['type']); ?></span></div><div class="transaction-amount <?php echo $amount_class; ?>"><?php echo $amount_sign; ?><?php echo $currency_symbol; ?><?php echo number_format($t['amount'], 2); ?></div></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>