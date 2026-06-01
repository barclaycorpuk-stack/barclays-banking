<?php
// transfer.php - Complete Payment System with EUR Currency
require 'db.php';
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

// Currency settings
$currency_symbol = CURRENCY_SYMBOL;
$currency_code = CURRENCY_CODE;

// Get user's account info AND transaction PIN
$stmt = $pdo->prepare("SELECT a.id, a.balance, a.account_number, a.account_type, u.full_name, u.email, u.phone, u.transaction_pin FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.user_id = ?");
$stmt->execute([$user_id]);
$account = $stmt->fetch();
$account_id = $account['id'] ?? null;
$account_balance = $account['balance'] ?? 0;
$sender_account = $account['account_number'] ?? '2024567890123456';
$sender_name = $account['full_name'] ?? 'User';
$sender_email = $account['email'] ?? '';
$sender_phone = $account['phone'] ?? '';
$account_type = $account['account_type'] ?? 'Savings';
$user_transaction_pin_hash = $account['transaction_pin'] ?? null;
$pin_not_set = empty($user_transaction_pin_hash);

// Fetch user's linked bank accounts
$stmt = $pdo->prepare("SELECT * FROM linked_bank_accounts WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$bank_accounts = $stmt->fetchAll();

// Format currency function
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

// Handle Manual Bank Transfer
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
                if (!empty($receiver_ifsc)) {
                    $description .= " IFSC: $receiver_ifsc";
                }
                if (!empty($transfer_note)) {
                    $description .= " | Note: $transfer_note";
                }
                
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
                
                $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ?");
                $stmt->execute([$account_id]);
                $account_balance = $stmt->fetchColumn();
                
                $message = "✅ Transfer Successful! " . formatCurrency($amount) . " has been processed for withdrawal to $receiver_name.";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Transaction failed: " . $e->getMessage();
            }
        }
    }
}

// Handle Email Transfer
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
            if (!empty($transfer_note)) {
                $description .= " - Note: $transfer_note";
            }
            
            $stmt = $pdo->prepare("INSERT INTO transactions (sender_account_id, receiver_account_id, amount, type, description, created_at) VALUES (?, ?, ?, 'transfer', ?, NOW())");
            $stmt->execute([$account_id, $receiver_acc_id, $amount, $description]);
            $transaction_id = $pdo->lastInsertId();
            
            $final_ref = 'EML' . str_pad($transaction_id, 8, '0', STR_PAD_LEFT);
            
            $pdo->commit();
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'transfer', 'Transfer Sent', CONCAT('You sent ', ?, ' to ', ?, '. Ref: ', ?), NOW())");
            $stmt->execute([$user_id, formatCurrency($amount), $receiver_name, $final_ref]);
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'transfer', 'Transfer Received', CONCAT('You received ', ?, ' from ', ?, '. Ref: ', ?), NOW())");
            $stmt->execute([$receiver_user['id'], formatCurrency($amount), $sender_name, $final_ref]);
            
            $stmt = $pdo->prepare("SELECT balance FROM accounts WHERE id = ?");
            $stmt->execute([$account_id]);
            $account_balance = $stmt->fetchColumn();
            
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

// Handle QR Code Data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['qr_payment_data'])) {
    $amount = floatval($_POST['qr_amount']);
    $pin = trim($_POST['qr_pin']);
    $receiver_name = trim($_POST['receiver_name']);
    $receiver_account = trim($_POST['receiver_account']);
    $receiver_bank = trim($_POST['receiver_bank']);
    $transfer_note = trim($_POST['transfer_note'] ?? '');
    
    if ($pin_not_set) {
        echo json_encode(['success' => false, 'error' => "PIN not set"]);
        exit;
    } elseif (strlen($pin) != 4 || !ctype_digit($pin)) {
        echo json_encode(['success' => false, 'error' => "PIN must be 4 digits"]);
        exit;
    } elseif (!password_verify($pin, $user_transaction_pin_hash)) {
        echo json_encode(['success' => false, 'error' => "Invalid PIN"]);
        exit;
    } elseif ($amount <= 0) {
        echo json_encode(['success' => false, 'error' => "Invalid amount"]);
        exit;
    } elseif ($amount > $account_balance) {
        echo json_encode(['success' => false, 'error' => "Insufficient funds"]);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $account_id]);
        
        $description = "QR Payment to $receiver_name ($receiver_bank - A/C: " . substr($receiver_account, -4) . ")";
        if (!empty($transfer_note)) {
            $description .= " | Note: $transfer_note";
        }
        
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
            'message' => "QR Payment of " . formatCurrency($amount) . " processed successfully to $receiver_name."
        ]);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Handle Receipt Download
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
    
    // PDF generation function here (keep your existing one)
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
            $pdf->SetXY(140, 22);
            $pdf->Cell(0, 5, 'Time: ' . date('h:i A'), 0, 1);
            
            $pdf->SetFillColor(212, 175, 55);
            $pdf->Rect(160, 8, 30, 12, 'F');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(15, 92, 140);
            $pdf->SetXY(162, 11);
            $pdf->Cell(0, 5, CURRENCY_CODE, 0, 1);
            
            $pdf->SetY(55);
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->SetTextColor(15, 92, 140);
            $pdf->Cell(0, 10, 'TRANSACTION STATEMENT', 0, 1, 'C');
            
            $pdf->SetDrawColor(212, 175, 55);
            $pdf->SetLineWidth(0.5);
            $pdf->Line(50, $pdf->GetY(), 160, $pdf->GetY());
            
            $pdf->SetY(75);
            $pdf->SetFont('Arial', 'B', 20);
            $pdf->SetTextColor(16, 185, 129);
            $pdf->Cell(0, 10, '✓ PAYMENT SUCCESSFUL', 0, 1, 'C');
            
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
            
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetTextColor(15, 92, 140);
            $pdf->SetXY(30, 110);
            $pdf->Cell(50, 8, 'Transaction Date:', 0, 0);
            $pdf->SetFont('Arial', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 8, $date, 0, 1);
            
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetTextColor(15, 92, 140);
            $pdf->SetXY(30, 120);
            $pdf->Cell(50, 8, 'Transaction Type:', 0, 0);
            $pdf->SetFont('Arial', '', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 8, $type, 0, 1);
            
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetTextColor(15, 92, 140);
            $pdf->SetXY(30, 130);
            $pdf->Cell(50, 8, 'Status:', 0, 0);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetTextColor(16, 185, 129);
            $pdf->Cell(0, 8, 'COMPLETED', 0, 1);
            
            $pdf->SetY(160);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(15, 92, 140);
            $pdf->Cell(0, 8, 'SENDER DETAILS', 0, 1, 'L');
            
            $pdf->SetFillColor(245, 250, 255);
            $pdf->Rect(20, $pdf->GetY(), 80, 50, 'F');
            $pdf->Rect(110, $pdf->GetY(), 80, 50, 'F');
            
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetXY(25, 173);
            $pdf->Cell(30, 6, 'Account Name:', 0, 0);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 6, substr($sender_name, 0, 25), 0, 1);
            
            $pdf->SetXY(25, 181);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(30, 6, 'Account No:', 0, 0);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 6, 'XXXX' . substr($sender_account, -4), 0, 1);
            
            $pdf->SetXY(25, 189);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(30, 6, 'Account Type:', 0, 0);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 6, $account_type, 0, 1);
            
            $pdf->SetXY(25, 197);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(30, 6, 'Bank:', 0, 0);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 6, 'Barclays Bank PLC', 0, 1);
            
            $pdf->SetXY(115, 173);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(30, 6, 'Account Name:', 0, 0);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 6, substr($receiver_name, 0, 25), 0, 1);
            
            $pdf->SetXY(115, 181);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(30, 6, 'Account No:', 0, 0);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 6, 'XXXX' . substr($receiver_account, -4), 0, 1);
            
            $pdf->SetXY(115, 189);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(30, 6, 'Bank:', 0, 0);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 6, substr($receiver_bank, 0, 25), 0, 1);
            
            $pdf->SetY(225);
            $pdf->SetFillColor(15, 92, 140);
            $pdf->Rect(20, $pdf->GetY(), 170, 40, 'F');
            
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetXY(35, 233);
            $pdf->Cell(60, 10, 'TRANSACTION AMOUNT:', 0, 0);
            $pdf->SetFont('Arial', 'B', 22);
            $pdf->SetTextColor(212, 175, 55);
            $pdf->Cell(0, 10, CURRENCY_SYMBOL . number_format($amount, 2), 0, 1);
            
            $pdf->SetY(275);
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->Cell(0, 5, 'Thank you for banking with Barclays', 0, 1, 'C');
            $pdf->Cell(0, 5, 'This is an electronically generated statement', 0, 1, 'C');
            
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

<!-- HTML remains the same as before - just replace any $ with € or use <?php echo CURRENCY_SYMBOL; ?> -->
<!-- ... Keep your existing HTML/CSS/JS from previous transfer.php ... -->