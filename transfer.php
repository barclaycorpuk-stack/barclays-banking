<?php
// transfer.php - Complete Payment Hub with EUR Currency & Multiple Tabs
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
    // Fetch user account details along with security transaction PIN
    $stmt = $pdo->prepare("SELECT a.id, a.balance, a.account_number, a.account_type, u.full_name, u.email, u.phone, u.transaction_pin FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.user_id = ?");
    $stmt->execute([$user_id]);
    $account = $stmt->fetch();
    
    $account_id = $account['id'] ?? null;
    $account_balance = (isset($account['balance']) && !empty($account['balance'])) ? floatval($account['balance']) : 0.00;
    $sender_account = $account['account_number'] ?? '0000000000';
    $sender_name = $account['full_name'] ?? 'User';
    $sender_email = $account['email'] ?? '';
    $sender_phone = $account['phone'] ?? '';
    $account_type = $account['account_type'] ?? 'Savings';
    $user_transaction_pin_hash = $account['transaction_pin'] ?? null;
    $pin_not_set = empty($user_transaction_pin_hash);

    // Fetch user's linked bank accounts for the shortcut dropdown list
    $stmt = $pdo->prepare("SELECT * FROM linked_bank_accounts WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$user_id]);
    $bank_accounts = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Database Connection Error: " . $e->getMessage();
}

function formatCurrency($amount) {
    return '€' . number_format($amount, 2);
}

// --- HANDLE 1: MANUAL BANK TRANSFER OR LINKED BANK SELECTION ---
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
            } else { $error = "Selected linked bank account not found!"; }
        } else { $error = "Please choose an active linked bank profile!"; }
    } else {
        $receiver_name = trim($_POST['receiver_name'] ?? '');
        $receiver_account = trim($_POST['receiver_account_number'] ?? '');
        $receiver_bank = trim($_POST['receiver_bank'] ?? '');
        $receiver_ifsc = trim($_POST['receiver_ifsc'] ?? '');
        
        if (empty($receiver_name) || empty($receiver_account) || empty($receiver_bank)) {
            $error = "Please fill all receiver data form bounds!";
        }
    }
    
    if (empty($error)) {
        if ($pin_not_set) { $error = "Set a 4-Digit transaction security PIN in your profile layout first."; }
        elseif (strlen($pin) != 4 || !ctype_digit($pin)) { $error = "PIN parameter validation failed. Must be 4 numbers."; }
        elseif (!password_verify($pin, $user_transaction_pin_hash)) { $error = "Security validation exception: Invalid Transaction PIN."; }
        elseif ($amount <= 0) { $error = "Transfer amount metrics bounds must be greater than zero."; }
        elseif ($amount > $account_balance) { $error = "Insufficient ledger liquidity. Balance: " . formatCurrency($account_balance); }
        else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$amount, $account_id]);
                
                $description = "Bank Transfer to $receiver_name ($receiver_bank)";
                if (!empty($transfer_note)) { $description .= " | Note: $transfer_note"; }
                
                $stmt = $pdo->prepare("INSERT INTO transactions (sender_account_id, amount, type, description, created_at) VALUES (?, ?, 'transfer', ?, NOW())");
                $stmt->execute([$account_id, $amount, $description]);
                $transaction_id = $pdo->lastInsertId();
                
                $final_ref = 'EUR' . str_pad($transaction_id, 8, '0', STR_PAD_LEFT);
                $pdo->commit();
                
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'transfer', 'Funds Dispatched', CONCAT('Processed transfer of ', ?, ' to ', ?, '. Ref: ', ?), NOW())");
                $stmt->execute([$user_id, formatCurrency($amount), $receiver_name, $final_ref]);
                
                $account_balance -= $amount;
                $message = "✅ Bank Transfer processed successfully! " . formatCurrency($amount) . " sent to $receiver_name.";
            } catch (Exception $e) { $pdo->rollBack(); $error = "Execution Error: " . $e->getMessage(); }
        }
    }
}

// --- HANDLE 2: EMAIL TO EMAIL INSTANT BANKING TRANSFER ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_email_transfer'])) {
    $receiver_email = trim($_POST['email']);
    $amount = floatval($_POST['email_amount']);
    $pin = trim($_POST['email_pin']);
    $transfer_note = trim($_POST['transfer_note'] ?? '');
    
    if ($pin_not_set) { $error = "Please provision a transaction PIN within profile dashboard context nodes first."; }
    elseif (strlen($pin) != 4 || !ctype_digit($pin)) { $error = "PIN configuration boundaries require 4 integers."; }
    elseif (!password_verify($pin, $user_transaction_pin_hash)) { $error = "Authentication exception: Transaction PIN verification mismatch."; }
    elseif ($amount <= 0) { $error = "Invalid transfer pool size constraint mapping."; }
    elseif ($amount > $account_balance) { $error = "Insufficient funds trace tracking parameter logic limits."; }
    else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
            $stmt->execute([$receiver_email]);
            $receiver_user = $stmt->fetch();
            
            if (!$receiver_user) { throw new Exception("Target client node associated with email '$receiver_email' not found."); }
            if ($receiver_user['id'] == $user_id) { throw new Exception("Loopback execution denied: Self-transfers over email parameters are invalid."); }
            
            $stmt = $pdo->prepare("SELECT id, account_number FROM accounts WHERE user_id = ?");
            $stmt->execute([$receiver_user['id']]);
            $receiver_acc = $stmt->fetch();
            $receiver_acc_id = $receiver_acc['id'];
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
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'transfer', 'Transfer Cleared', CONCAT('Dispatched ', ?, ' to ', ?), NOW())");
            $stmt->execute([$user_id, formatCurrency($amount), $receiver_name]);
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'transfer', 'Funds Collected', CONCAT('Credited ', ' from ', ?), NOW())");
            $stmt->execute([$receiver_user['id'], $sender_name]);
            
            $account_balance -= $amount;
            $message = "✅ Instant email transfer finalized! Sent " . formatCurrency($amount) . " to $receiver_name.";
        } catch (Exception $e) { $pdo->rollBack(); $error = $e->getMessage(); }
    }
}

// --- HANDLE 3: AJAX ASYNC QR READ AND PAY PROCESSOR ENDPOINT ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['qr_payment_data'])) {
    $amount = floatval($_POST['qr_amount']);
    $pin = trim($_POST['qr_pin']);
    $receiver_name = trim($_POST['receiver_name']);
    $receiver_account = trim($_POST['receiver_account']);
    $receiver_bank = trim($_POST['receiver_bank']);
    $transfer_note = trim($_POST['transfer_note'] ?? '');
    
    if ($pin_not_set) { echo json_encode(['success' => false, 'error' => "Transaction PIN not set."]); exit; }
    if (strlen($pin) != 4 || !ctype_digit($pin)) { echo json_encode(['success' => false, 'error' => "PIN boundary must register 4 digits."]); exit; }
    if (!password_verify($pin, $user_transaction_pin_hash)) { echo json_encode(['success' => false, 'error' => "Invalid security verification parameters."]); exit; }
    if ($amount <= 0) { echo json_encode(['success' => false, 'error' => "Transfer pool size metrics must be positive alignment values."]); exit; }
    if ($amount > $account_balance) { echo json_encode(['success' => false, 'error' => "Ledger tracking balance liquidity exhaust parameters."]); exit; }
    
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $account_id]);
        
        $description = "QR Payment to $receiver_name ($receiver_bank)";
        if (!empty($transfer_note)) { $description .= " | Note: $transfer_note"; }
        
        $stmt = $pdo->prepare("INSERT INTO transactions (sender_account_id, amount, type, description, created_at) VALUES (?, ?, 'transfer', ?, NOW())");
        $stmt->execute([$account_id, $amount, $description]);
        $transaction_id = $pdo->lastInsertId();
        
        $final_ref = 'QR' . str_pad($transaction_id, 8, '0', STR_PAD_LEFT);
        $pdo->commit();
        
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'transfer', 'QR Clearing Verified', CONCAT('Sent ', ?), NOW())");
        $stmt->execute([$user_id, formatCurrency($amount)]);
        
        echo json_encode([
            'success' => true, 
            'transaction_ref' => $final_ref,
            'amount' => $amount,
            'receiver_name' => $receiver_name,
            'message' => "QR Payment transaction processed with dynamic reference node sequence mapping matches!"
        ]);
        exit;
    } catch (Exception $e) { $pdo->rollBack(); echo json_encode(['success' => false, 'error' => $e->getMessage()]); exit; }
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
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--primary); color: var(--text); min-height: 100vh; padding: 20px; }
        .container { max-width: 650px; margin: 0 auto; padding-top: 20px; }
        
        .back-btn { color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; background: var(--secondary); padding: 10px 20px; border-radius: 30px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s; }
        .back-btn:hover { background: rgba(255,255,255,0.1); transform: translateX(-3px); }

        .glass-card { background: var(--secondary); border: 1px solid rgba(255,255,255,0.05); border-radius: 25px; padding: 35px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); }
        .page-title { font-size: 1.6rem; margin-bottom: 25px; font-weight: 600; text-align: center; }

        /* Tabs Selection Styling */
        .tab-nav { display: flex; gap: 10px; background: #0f172a; padding: 5px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #334155; }
        .tab-btn { flex: 1; padding: 12px; border-radius: 8px; background: transparent; border: none; color: #94a3b8; font-weight: 600; font-size: 0.85rem; cursor: pointer; transition: 0.3s; text-align: center; }
        .tab-btn.active { background: var(--accent); color: var(--primary); box-shadow: 0 4px 12px rgba(56, 189, 248, 0.2); }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 0.85rem; color: #cbd5e1; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: white; font-size: 0.95rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--accent); }

        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #38bdf8, #0284c7); color: #0f172a; border: none; border-radius: 50px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(56, 189, 248, 0.2); }

        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 0.9rem; }
        .alert-error { background: #7f1d1d; color: #fca5a5; border: 1px solid #991b1b; }
        .alert-success { background: #14532d; color: #4ade80; border: 1px solid #166534; }

        .qr-mock-area { border: 2px dashed #475569; background: #0f172a; border-radius: 15px; padding: 30px; text-align: center; cursor: pointer; transition: 0.3s; }
        .qr-mock-area:hover { border-color: var(--accent); background: rgba(56,189,248,0.02); }
    </style>
</head>
<body>

    <div class="container">
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>

        <div class="glass-card">
            <h2 class="page-title"><i class="fas fa-wallet" style="color:var(--accent);"></i> Barclays Payment Hub</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <div class="tab-nav">
                <button class="tab-btn active" onclick="switchPanel(event, 'bankTab')">🏦 Bank Wire</button>
                <button class="tab-btn" onclick="switchPanel(event, 'emailTab')">✉️ Email Pay</button>
                <button class="tab-btn" onclick="switchPanel(event, 'qrTab')">📷 Scan QR</button>
            </div>

            <div id="bankTab" class="tab-panel active">
                <form method="POST">
                    <input type="hidden" name="manual_transfer" value="1">
                    
                    <div class="form-group">
                        <label>Transfer Pathway Mode</label>
                        <select name="transfer_option" id="transfer_option" onchange="toggleLinkedView()">
                            <option value="manual">Type New Account Info Coordinates</option>
                            <option value="linked_bank">Select Pre-Linked System Bank Profile</option>
                        </select>
                    </div>

                    <div class="form-group" id="linkedBankWrapper" style="display:none;">
                        <label>Choose Linked Bank Profile Target</label>
                        <select name="bank_account_id">
                            <?php if (empty($bank_accounts)): ?>
                                <option value="">No linked profiles found tracking this account node slot</option>
                            <?php else: ?>
                                <?php foreach ($bank_accounts as $b): ?>
                                    <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['bank_name'] . ' - ' . $b['account_holder'] . ' (****' . substr($b['account_number'],-4) . ')'); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div id="manualBankWrapper">
                        <div class="form-group">
                            <label>Receiver Full Name</label>
                            <input type="text" name="receiver_name" placeholder="John Doe">
                        </div>
                        <div class="form-group">
                            <label>Receiver Account Number</label>
                            <input type="text" name="receiver_account_number" placeholder="Enter full 16-digit account route string">
                        </div>
                        <div class="form-group">
                            <label>Receiver Bank Institution Name</label>
                            <input type="text" name="receiver_bank" placeholder="e.g. Barclays Branch Node Core">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Amount to Dispatch (€)</label>
                        <input type="number" name="manual_amount" step="0.01" min="1" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Description Note (Optional)</label>
                        <input type="text" name="transfer_note" placeholder="Invoice reference number context metrics">
                    </div>
                    <div class="form-group">
                        <label>4-Digit Secure Transaction PIN</label>
                        <input type="password" name="manual_pin" maxlength="4" placeholder="****" required>
                    </div>

                    <button type="submit" class="btn-submit"><i class="fas fa-share-square"></i> Authorize External Bank Transfer</button>
                </form>
            </div>

            <div id="emailTab" class="tab-panel">
                <form method="POST">
                    <input type="hidden" name="send_email_transfer" value="1">
                    <div class="form-group">
                        <label>Registered Recipient Client Email Address</label>
                        <input type="email" name="email" placeholder="client@barclays-banking.com" required>
                    </div>
                    <div class="form-group">
                        <label>Amount to Send Instantly (€)</label>
                        <input type="number" name="email_amount" step="0.01" min="1" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label>Memo Note</label>
                        <input type="text" name="transfer_note" placeholder="Dinner split tracking sequence parameters">
                    </div>
                    <div class="form-group">
                        <label>4-Digit Secure Transaction PIN</label>
                        <input type="password" name="email_pin" maxlength="4" placeholder="****" required>
                    </div>
                    <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Finalize Instant Inter-Email Transfer</button>
                </form>
            </div>

            <div id="qrTab" class="tab-panel">
                <div id="qrCaptureArea" class="qr-mock-area" onclick="triggerSimulatedQRRead()">
                    <i class="fas fa-qrcode fa-4x" style="color:var(--accent); margin-bottom:15px;"></i>
                    <h3>Simulate QR Code Reading Frame</h3>
                    <p style="color:#94a3b8; font-size:0.8rem; margin-top:5px;">Click directly onto this boundary box loop vector matrix node to read standard encrypted payload structures.</p>
                </div>

                <div id="qrInputWrapper" style="display:none; margin-top:20px;">
                    <div style="background:#0f172a; padding:15px; border-radius:10px; margin-bottom:15px; border:1px solid #334155;">
                        <p style="font-size:0.85rem; color:#94a3b8;">Decrypted Payee Information Target:</p>
                        <h4 id="lbl_qr_payee" style="margin-top:5px; color:#38bdf8;"></h4>
                    </div>
                    <div class="form-group">
                        <label>Enter Amount to Clear via QR (€)</label>
                        <input type="number" id="txt_qr_amt" step="0.01" min="1" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>4-Digit Secure Transaction PIN</label>
                        <input type="password" id="txt_qr_pin" maxlength="4" placeholder="****">
                    </div>
                    <button type="button" class="btn-submit" onclick="executeAsyncQRPayment()"><i class="fas fa-bolt"></i> Execute Fast QR Clearing</button>
                </div>
            </div>

        </div>
    </div>

    <script>
        function switchPanel(evt, panelId) {
            let panels = document.getElementsByClassName("tab-panel");
            for (let i = 0; i < panels.length; i++) { panels[i].classList.remove("active"); }
            
            let buttons = document.getElementsByClassName("tab-btn");
            for (let i = 0; i < buttons.length; i++) { buttons[i].classList.remove("active"); }
            
            document.getElementById(panelId).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        function toggleLinkedView() {
            let mode = document.getElementById("transfer_option").value;
            if (mode === "linked_bank") {
                document.getElementById("linkedBankWrapper").style.display = "block";
                document.getElementById("manualBankWrapper").style.display = "none";
            } else {
                document.getElementById("linkedBankWrapper").style.display = "none";
                document.getElementById("manualBankWrapper").style.display = "block";
            }
        }

        // Globally cached JSON payload structural trace parameters
        let parsedQRDataNode = null;

        function triggerSimulatedQRRead() {
            // Simulated encrypted payload string matching your app structural specifications
            parsedQRDataNode = {
                name: "Surya Merchants Ltd",
                account: "2029988776655441",
                bank: "Barclays Commercial Node"
            };

            document.getElementById("lbl_qr_payee").innerText = parsedQRDataNode.name + " (" + parsedQRDataNode.bank + ")";
            document.getElementById("qrInputWrapper").style.display = "block";
            document.getElementById("qrCaptureArea").innerHTML = '<i class="fas fa-check-circle fa-3x" style="color:#10b981;"></i><h4 style="margin-top:10px;">QR Target Captured & Verified!</h4>';
        }

        function executeAsyncQRPayment() {
            let amt = document.getElementById("txt_qr_amt").value;
            let pin = document.getElementById("txt_qr_pin").value;

            if (!amt || !pin) { alert("Please complete form constraints balance fields."); return; }

            // Dynamic Form mapping injection to invoke target handler code blocks via AJAX asynchronous routes
            let formData = new FormData();
            formData.append("qr_payment_data", "1");
            formData.append("qr_amount", amt);
            formData.append("qr_pin", pin);
            formData.append("receiver_name", parsedQRDataNode.name);
            formData.append("receiver_account", parsedQRDataNode.account);
            formData.append("receiver_bank", parsedQRDataNode.bank);

            fetch("transfer.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("🎉 TRANSACTION SUCCESSFUL!\nReference ID: " + data.transaction_ref + "\nFunds settled securely.");
                    window.location.href = "dashboard.php";
                } else {
                    alert("❌ Transaction Rejected: " + data.error);
                }
            })
            .catch(err => {
                alert("Processing verification fault parameter logic error trace paths.");
            });
        }
    </script>
</body>
</html>