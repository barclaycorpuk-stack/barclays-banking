<?php
// transfer.php - Complete Payment System with EUR Currency
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once 'fpdf/fpdf.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";
$show_popup = false;
$popup_data = [];

// Force clean presentation variables for Euro
$currency_symbol = '€';
$currency_code = 'EUR';

try {
    // Get user's account info AND transaction PIN with safe metrics mapping
    $stmt = $pdo->prepare("SELECT a.id, a.balance, a.account_number, a.account_type, u.full_name, u.email, u.phone, u.transaction_pin FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.user_id = ?");
    $stmt->execute([$user_id]);
    $account = $stmt->fetch();
    
    $account_id = $account['id'] ?? null;
    $account_balance = (isset($account['balance']) && !empty($account['balance'])) ? floatval($account['balance']) : 0.00;
    $sender_account = $account['account_number'] ?? '2024567890123456';
    $sender_name = $account['full_name'] ?? 'User';
    $sender_email = $account['email'] ?? '';
    $sender_phone = $account['phone'] ?? '';
    $account_type = $account['account_type'] ?? 'Savings';
    $user_transaction_pin_hash = $account['transaction_pin'] ?? null;
    $pin_not_set = empty($user_transaction_pin_hash);

    // Fetch user's linked bank accounts safely
    $stmt = $pdo->prepare("SELECT * FROM linked_bank_accounts WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$user_id]);
    $bank_accounts = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Database Connection Error: " . $e->getMessage();
}

// Format currency function
function formatCurrency($amount) {
    return '€' . number_format($amount, 2);
}

// Handle Manual Bank Transfer Action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['manual_transfer'])) {
    $amount = floatval($_POST['manual_amount']);
    $pin = trim($_POST['manual_pin']);
    $transfer_option = $_POST['transfer_option'] ?? 'manual';
    $transfer_note = trim($_POST['transfer_note'] ?? '');
    
    $receiver_name = '';
    $receiver_account = '';
    $receiver_bank = '';
    $receiver_ifsc = '';
    
    if ($transfer_option == 'linked_bank') {
        if (isset($_POST['bank_account_id']) && !empty($_POST['bank_account_id'])) {
            $bank_id = intval($_POST['bank_account_id']);
            $stmt = $pdo->prepare("SELECT * FROM linked_bank_accounts WHERE id = ? AND user_id = ?");
            $stmt->execute([$bank_id, $user_id]);
            $bank = $stmt->fetch();
            
            if ($bank) {
                $receiver_name = $bank['account_holder'];
                $receiver_account = $bank['account_number'];
                $receiver_bank = $bank['bank_name'];
                $receiver_ifsc = $bank['ifsc_code'] ?? '';
            } else {
                $error = "Selected bank account not found!";
            }
        } else {
            $error = "Please select a bank account!";
        }
    } else {
        $receiver_name = trim($_POST['receiver_name'] ?? '');
        $receiver_account = trim($_POST['receiver_account_number'] ?? '');
        $receiver_bank = trim($_POST['receiver_bank'] ?? '');
        $receiver_ifsc = trim($_POST['receiver_ifsc'] ?? '');
        
        if (empty($receiver_name) || empty($receiver_account) || empty($receiver_bank)) {
            $error = "Please fill all receiver details!";
        }
    }
    
    if (empty($error)) {
        if ($pin_not_set) {
            $error = "You haven't set a transaction PIN. Please set one in your Profile first.";
        } elseif (strlen($pin) != 4 || !ctype_digit($pin)) {
            $error = "PIN must be 4 digits!";
        } elseif (!password_verify($pin, $user_transaction_pin_hash)) {
            $error = "Invalid Transaction PIN!";
        } elseif ($amount <= 0) {
            $error = "Please enter a valid amount.";
        } elseif ($amount > $account_balance) {
            $error = "Insufficient funds! Your balance is " . formatCurrency($account_balance);
        } else {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$amount, $account_id]);
                
                $description = "Bank Transfer to $receiver_name ($receiver_bank - A/C: " . substr($receiver_account, -4) . ")";
                if (!empty($receiver_ifsc)) { $description .= " IFSC: $receiver_ifsc"; }
                if (!empty($transfer_note)) { $description .= " | Note: $transfer_note"; }
                
                $stmt = $pdo->prepare("INSERT INTO transactions (sender_account_id, amount, type, description, created_at) VALUES (?, ?, 'transfer', ?, NOW())");
                $stmt->execute([$account_id, $amount, $description]);
                $transaction_id = $pdo->lastInsertId();
                
                $final_ref = 'EUR' . str_pad($transaction_id, 8, '0', STR_PAD_LEFT);
                
                $pdo->commit();
                
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'transfer', 'Funds Transferred', CONCAT('Your fund transfer of ', ?, ' to ', ?, ' has been processed successfully. Reference: ', ?), NOW())");
                $stmt->execute([$user_id, formatCurrency($amount), $receiver_name, $final_ref]);
                
                $show_popup = true;
                $popup_data = [
                    'transaction_id' => $transaction_id,
                    'transaction_ref' => $final_ref,
                    'amount' => $amount,
                    'receiver_name' => $receiver_name,
                    'receiver_account' => $receiver_account,
                    'receiver_bank' => $receiver_bank,
                    'receiver_ifsc' => $receiver_ifsc,
                    'transfer_note' => $transfer_note,
                    'date' => date('M d, Y h:i A'),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'type' => 'Bank Transfer',
                    'currency' => $currency_symbol,
                    'message' => "Your bank transfer of " . formatCurrency($amount) . " has been successfully processed and credited to $receiver_name."
                ];
                
                $account_balance -= $amount;
                $message = "✅ Transfer Successful! " . formatCurrency($amount) . " has been processed for withdrawal.";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Transaction failed: " . $e->getMessage();
            }
        }
    }
}

// Handle Email Transfer Action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_email_transfer'])) {
    $receiver_email = trim($_POST['email']);
    $amount = floatval($_POST['email_amount']);
    $pin = trim($_POST['email_pin']);
    $transfer_note = trim($_POST['transfer_note'] ?? '');
    
    if ($pin_not_set) {
        $error = "You haven't set a transaction PIN. Please set one in your Profile first.";
    } elseif (strlen($pin) != 4 || !ctype_digit($pin)) {
        $error = "PIN must be 4 digits!";
    } elseif (!password_verify($pin, $user_transaction_pin_hash)) {
        $error = "Invalid Transaction PIN!";
    } elseif ($amount <= 0) {
        $error = "Please enter a valid amount.";
    } elseif ($amount > $account_balance) {
        $error = "Insufficient funds! Your balance is " . formatCurrency($account_balance);
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
            $stmt->execute([$receiver_email]);
            $receiver_user = $stmt->fetch();
            
            if (!$receiver_user) {
                throw new Exception("User with email '$receiver_email' not found!");
            }
            if ($receiver_user['id'] == $user_id) {
                throw new Exception("You cannot send money to yourself!");
            }
            
            $stmt = $pdo->prepare("SELECT id, account_number FROM accounts WHERE user_id = ?");
            $stmt->execute([$receiver_user['id']]);
            $receiver_acc = $stmt->fetch();
            $receiver_acc_id = $receiver_acc['id'];
            $receiver_account_num = $receiver_acc['account_number'];
            $receiver_name = $receiver_user['full_name'];
            
            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$amount, $account_id]);
            
            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$amount, $receiver_acc_id]);
            
            $description = "Email Transfer to $receiver_name";
            if (!empty($transfer_note)) { $description .= " - Note: $transfer_note"; }
            
            $stmt = $pdo->prepare("INSERT INTO transactions (sender_account_id, receiver_account_id, amount, type, description, created_at) VALUES (?, ?, ?, 'transfer', ?, NOW())");
            $stmt->execute([$account_id, $receiver_acc_id, $amount, $description]);
            $transaction_id = $pdo->lastInsertId();
            
            $final_ref = 'EML' . str_pad($transaction_id, 8, '0', STR_PAD_LEFT);
            
            $pdo->commit();
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'transfer', 'Transfer Sent', CONCAT('You sent ', ?, ' to ', ?, '. Ref: ', ?), NOW())");
            $stmt->execute([$user_id, formatCurrency($amount), $receiver_name, $final_ref]);
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'transfer', 'Transfer Received', CONCAT('You received ', ?, ' from ', ?, '. Ref: ', ?), NOW())");
            $stmt->execute([$receiver_user['id'], formatCurrency($amount), $sender_name, $final_ref]);
            
            $account_balance -= $amount;
            
            $show_popup = true;
            $popup_data = [
                'transaction_id' => $transaction_id,
                'transaction_ref' => $final_ref,
                'amount' => $amount,
                'receiver_name' => $receiver_name,
                'receiver_account' => $receiver_account_num,
                'receiver_bank' => 'Barclays Bank',
                'receiver_ifsc' => 'BARC' . substr($receiver_account_num, -6),
                'transfer_note' => $transfer_note,
                'date' => date('M d, Y h:i A'),
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => 'Email Transfer',
                'currency' => $currency_symbol,
                'message' => "Your email transfer of " . formatCurrency($amount) . " has been successfully sent to $receiver_name."
            ];
            
            $message = "✅ Email Transfer Successful! " . formatCurrency($amount) . " sent to $receiver_name.";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

// Handle QR Code Payments
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['qr_payment_data'])) {
    $amount = floatval($_POST['qr_amount']);
    $pin = trim($_POST['qr_pin']);
    $receiver_name = trim($_POST['receiver_name']);
    $receiver_account = trim($_POST['receiver_account']);
    $receiver_bank = trim($_POST['receiver_bank']);
    $transfer_note = trim($_POST['transfer_note'] ?? '');
    
    if ($pin_not_set) { echo json_encode(['success' => false, 'error' => "PIN not set"]); exit; }
    if (strlen($pin) != 4 || !ctype_digit($pin)) { echo json_encode(['success' => false, 'error' => "PIN must be 4 digits"]); exit; }
    if (!password_verify($pin, $user_transaction_pin_hash)) { echo json_encode(['success' => false, 'error' => "Invalid PIN"]); exit; }
    if ($amount <= 0) { echo json_encode(['success' => false, 'error' => "Invalid amount"]); exit; }
    if ($amount > $account_balance) { echo json_encode(['success' => false, 'error' => "Insufficient funds"]); exit; }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $account_id]);
        
        $description = "QR Payment to $receiver_name ($receiver_bank - A/C: " . substr($receiver_account, -4) . ")";
        if (!empty($transfer_note)) { $description .= " | Note: $transfer_note"; }
        
        $stmt = $pdo->prepare("INSERT INTO transactions (sender_account_id, amount, type, description, created_at) VALUES (?, ?, 'transfer', ?, NOW())");
        $stmt->execute([$account_id, $amount, $description]);
        $transaction_id = $pdo->lastInsertId();
        
        $final_ref = 'QR' . str_pad($transaction_id, 8, '0', STR_PAD_LEFT);
        
        $pdo->commit();
        
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'transfer', 'QR Payment Sent', CONCAT('You sent ', ?, ' via QR to ', ?, '. Ref: ', ?), NOW())");
        $stmt->execute([$user_id, formatCurrency($amount), $receiver_name, $final_ref]);
        
        echo json_encode([
            'success' => true, 
            'transaction_id' => $transaction_id,
            'transaction_ref' => $final_ref,
            'amount' => $amount,
            'receiver_name' => $receiver_name,
            'receiver_account' => $receiver_account,
            'receiver_bank' => $receiver_bank,
            'date' => date('M d, Y h:i A'),
            'timestamp' => date('Y-m-d H:i:s'),
            'new_balance' => $account_balance - $amount,
            'currency' => $currency_symbol,
            'message' => "QR Payment of " . formatCurrency($amount) . " processed successfully."
        ]);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle Receipt Statement Download
if (isset($_GET['download_receipt']) && isset($_GET['tid'])) {
    $transaction_id = intval($_GET['tid']);
    $amount = isset($_GET['amt']) ? floatval($_GET['amt']) : 0;
    $receiver = isset($_GET['rec']) ? urldecode($_GET['rec']) : 'Unknown';
    $account_num = isset($_GET['acc']) ? $_GET['acc'] : 'Unknown';
    $bank = isset($_GET['bank']) ? urldecode($_GET['bank']) : 'Bank Transfer';
    $date = isset($_GET['dt']) ? urldecode($_GET['dt']) : date('M d, Y h:i A');
    $transaction_ref = isset($_GET['ref']) ? $_GET['ref'] : 'EUR' . str_pad($transaction_id, 8, '0', STR_PAD_LEFT);
    $transfer_note = isset($_GET['note']) ? urldecode($_GET['note']) : '';
    $ifsc = isset($_GET['ifsc']) ? $_GET['ifsc'] : '';
    $type = isset($_GET['type']) ? urldecode($_GET['type']) : 'Bank Transfer';
    
    function generateBankStatement($transaction_id, $amount, $receiver_name, $receiver_account, $receiver_bank, $date, $transaction_ref, $transfer_note, $ifsc, $type) {
        global $sender_account, $sender_name, $account_type;
        try {
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->AddPage();
            $pdf->SetFillColor(15, 92, 140);
            $pdf->Rect(0, 0, 210, 45, 'F');
            
            $pdf->SetFont('Arial', 'B', 22);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY(20, 12);
            $pdf->Cell(0, 10, 'BARCLAYS BANK PLC', 0, 1);
            
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(200, 200, 200);
            $pdf->SetXY(20, 25);
            $pdf->Cell(0, 5, 'Official Transaction Statement', 0, 1);
            
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetXY(140, 15);
            $pdf->Cell(0, 5, 'Date: ' . date('d M Y'), 0, 1);
            
            $pdf->SetY(55);
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->SetTextColor(15, 92, 140);
            $pdf->Cell(0, 10, 'TRANSACTION STATEMENT', 0, 1, 'C');
            
            $pdf->SetY(75);
            $pdf->SetFont('Arial', 'B', 20);
            $pdf->SetTextColor(16, 185, 129);
            $pdf->Cell(0, 10, 'Y PAYMENT SUCCESSFUL', 0, 1, 'C');
            
            $pdf->SetY(95);
            $pdf->SetFillColor(240, 248, 255);
            $pdf->Rect(20, $pdf->GetY(), 170, 55, 'F');
            
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetTextColor(15, 92, 140);
            $pdf->SetXY(30, 100);
            $pdf->Cell(50, 8, 'Transaction Reference:', 0, 0);
            $pdf->SetFont('Arial', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 8, $transaction_ref, 0, 1);
            
            $pdf->SetY(160);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(15, 92, 140);
            $pdf->Cell(0, 8, 'TRANSACTION SUMMARY', 0, 1, 'L');
            
            $pdf->SetFillColor(245, 250, 255);
            $pdf->Rect(20, $pdf->GetY(), 80, 50, 'F');
            $pdf->Rect(110, $pdf->GetY(), 80, 50, 'F');
            
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetXY(25, 173);
            $pdf->Cell(30, 6, 'Sender Name:', 0, 0);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 6, substr($sender_name, 0, 25), 0, 1);
            
            $pdf->SetXY(115, 173);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(30, 6, 'Receiver Name:', 0, 0);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 6, substr($receiver_name, 0, 25), 0, 1);
            
            $pdf->SetY(225);
            $pdf->SetFillColor(15, 92, 140);
            $pdf->Rect(20, $pdf->GetY(), 170, 40, 'F');
            
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY(35, 233);
            $pdf->Cell(60, 10, 'TRANSACTION AMOUNT:', 0, 0);
            $pdf->SetFont('Arial', 'B', 22);
            $pdf->SetTextColor(212, 175, 55);
            $pdf->Cell(0, 10, 'EUR ' . number_format($amount, 2), 0, 1);
            
            $pdf->Output('D', 'Barclays_Statement_' . $transaction_ref . '.pdf');
            exit;
        } catch (Exception $e) {
            die("PDF Generation Error: " . $e->getMessage());
        }
    }
    generateBankStatement($transaction_id, $amount, $receiver, $account_num, $bank, $date, $transaction_ref, $transfer_note, $ifsc, $type);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Money - Barclays Banking</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #1e293b;
            --accent: #38bdf8;
            --text: #f8fafc;
            --danger: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--primary); color: var(--text); min-height: 100vh; padding: 20px; }
        .container { max-width: 650px; margin: 0 auto; padding-top: 20px; }
        .back-btn { color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; background: var(--secondary); padding: 10px 20px; border-radius: 30px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05); }
        .glass-card { background: var(--secondary); border: 1px solid rgba(255,255,255,0.05); border-radius: 25px; padding: 35px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .page-title { font-size: 1.6rem; margin-bottom: 25px; font-weight: 600; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: #cbd5e1; }
        .form-group input, .form-group select { width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 0.95rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--accent); }
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #38bdf8, #0284c7); color: #0f172a; border: none; border-radius: 50px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(56, 189, 248, 0.2); }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; }
        .alert-error { background: #7f1d1d; color: #fca5a5; border: 1px solid #991b1b; }
        .alert-success { background: #14532d; color: #4ade80; border: 1px solid #166534; }
    </style>
</head>
<body>

    <div class="container">
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>

        <div class="glass-card">
            <h2 class="page-title"><i class="fas fa-paper-plane"></i> Send Money (EUR €)</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="manual_transfer" value="1">
                
                <div class="form-group">
                    <label>Receiver Full Name</label>
                    <input type="text" name="receiver_name" placeholder="e.g. John Doe" required>
                </div>

                <div class="form-group">
                    <label>Receiver Account Number</label>
                    <input type="text" name="receiver_account_number" placeholder="Enter 16-digit account number" required>
                </div>

                <div class="form-group">
                    <label>Receiver Bank Name</label>
                    <input type="text" name="receiver_bank" placeholder="e.g. Barclays Bank" required>
                </div>

                <div class="form-group">
                    <label>Transfer Amount (€)</label>
                    <input type="number" name="manual_amount" step="0.01" min="1" placeholder="0.00" required>
                </div>

                <div class="form-group">
                    <label>4-Digit Transaction PIN</label>
                    <input type="password" name="manual_pin" maxlength="4" placeholder="****" required>
                </div>

                <button type="submit" class="btn-submit"><i class="fas fa-exchange-alt"></i> Process Transfer</button>
            </form>
        </div>
    </div>

</body>
</html>