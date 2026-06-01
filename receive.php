<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";
$currency_symbol = CURRENCY_SYMBOL;

// Get user account info
$stmt = $pdo->prepare("SELECT u.full_name, u.email, a.account_number, a.balance, a.account_type FROM users u JOIN accounts a ON u.id = a.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get linked bank accounts
$stmt = $pdo->prepare("SELECT * FROM linked_bank_accounts WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$linked_accounts = $stmt->fetchAll();

// Handle add bank account
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_bank_account'])) {
    $bank_name = trim($_POST['bank_name']);
    $account_holder = trim($_POST['account_holder']);
    $account_number = trim($_POST['account_number']);
    $ifsc_code = trim($_POST['ifsc_code']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    if (empty($bank_name) || empty($account_holder) || empty($account_number)) {
        $error = "Please fill all required fields.";
    } else {
        if ($is_default) {
            $pdo->prepare("UPDATE linked_bank_accounts SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }
        
        $stmt = $pdo->prepare("INSERT INTO linked_bank_accounts (user_id, bank_name, account_holder, account_number, ifsc_code, is_default, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $bank_name, $account_holder, $account_number, $ifsc_code, $is_default]);
        $message = "✅ Bank account linked successfully!";
        header("Refresh:0");
        exit;
    }
}

// Handle delete bank account
if (isset($_GET['delete_account']) && isset($_GET['id'])) {
    $account_id = $_GET['id'];
    $pdo->prepare("DELETE FROM linked_bank_accounts WHERE id = ? AND user_id = ?")->execute([$account_id, $user_id]);
    $message = "✅ Bank account removed!";
    header("Refresh:0");
    exit;
}

// Handle set default bank account
if (isset($_GET['set_default']) && isset($_GET['id'])) {
    $account_id = $_GET['id'];
    $pdo->prepare("UPDATE linked_bank_accounts SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
    $pdo->prepare("UPDATE linked_bank_accounts SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$account_id, $user_id]);
    $message = "✅ Default account updated!";
    header("Refresh:0");
    exit;
}

// Generate QR Code for user
$qr_data = json_encode([
    'bank' => 'Barclays Bank',
    'name' => $user['full_name'],
    'account' => $user['account_number'],
    'type' => $user['account_type'],
    'email' => $user['email'],
    'currency' => 'EUR'
]);
$qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive Money - Barclays Banking</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 20px; padding: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; color: white; flex-wrap: wrap; gap: 15px; }
        .back-btn { color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 30px; transition: 0.3s; }
        .glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 30px; padding: 30px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); margin-bottom: 30px; }
        .card-header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; }
        .card-header .icon { width: 60px; height: 60px; background: linear-gradient(135deg, #0f5c8c, #d4af37); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.8rem; }
        .account-info { background: linear-gradient(135deg, #0f5c8c, #0a3d5e); color: white; border-radius: 20px; padding: 25px; margin-bottom: 30px; }
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .copy-btn { background: rgba(255,255,255,0.2); border: none; color: white; padding: 5px 10px; border-radius: 8px; cursor: pointer; margin-left: 10px; font-size: 0.8rem; }
        .qr-section { text-align: center; padding: 20px; }
        .qr-code { background: white; padding: 20px; border-radius: 20px; display: inline-block; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .qr-code img { width: 200px; height: 200px; }
        .qr-actions { margin-top: 20px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn-download { background: #10b981; color: white; padding: 10px 20px; border-radius: 30px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; transition: 0.3s; }
        .accounts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .account-card { background: #f8fafc; border-radius: 15px; padding: 20px; border: 1px solid #e2e8f0; transition: 0.3s; position: relative; }
        .account-card.default { border: 2px solid #d4af37; background: linear-gradient(135deg, #fff, #fef9e6); }
        .default-badge { position: absolute; top: -10px; right: 15px; background: #d4af37; color: #0f172a; padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .bank-name { font-size: 1.2rem; font-weight: 700; color: #0f5c8c; margin-bottom: 10px; }
        .btn-sm { padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: 0.3s; }
        .btn-default { background: #d4af37; color: #0f172a; }
        .btn-delete { background: #ef4444; color: white; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 1rem; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
        .btn-primary { width: 100%; padding: 15px; background: linear-gradient(135deg, #0f5c8c, #0a3d5e); color: white; border: none; border-radius: 50px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #dcfce7; color: #16a34a; }
        .alert-error { background: #fee2e2; color: #dc2626; }
        .empty-state { text-align: center; padding: 40px; background: #f8fafc; border-radius: 15px; color: #64748b; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .accounts-grid { grid-template-columns: 1fr; } .info-row { flex-direction: column; gap: 5px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header"><a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a><h2><i class="fas fa-download"></i> Receive Money</h2><div class="balance">Balance: <span><?php echo $currency_symbol; ?><?php echo number_format($user['balance'], 2); ?></span></div></div>
        <?php if (!empty($message)): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
        
        <div class="glass-card"><div class="card-header"><div class="icon"><i class="fas fa-user-circle"></i></div><div><h2>Your Account Information</h2><p>Share these details to receive money</p></div></div>
        <div class="account-info"><div class="info-row"><span class="info-label">Account Holder Name</span><span class="info-value"><?php echo htmlspecialchars($user['full_name']); ?><button class="copy-btn" onclick="copyText('<?php echo htmlspecialchars($user['full_name']); ?>')"><i class="fas fa-copy"></i></button></span></div>
        <div class="info-row"><span class="info-label">Account Number</span><span class="info-value"><?php echo $user['account_number']; ?><button class="copy-btn" onclick="copyText('<?php echo $user['account_number']; ?>')"><i class="fas fa-copy"></i></button></span></div>
        <div class="info-row"><span class="info-label">Account Type</span><span class="info-value"><?php echo $user['account_type']; ?></span></div>
        <div class="info-row"><span class="info-label">Bank Name</span><span class="info-value">Barclays Bank PLC</span></div>
        <div class="info-row"><span class="info-label">IFSC Code</span><span class="info-value">BARC<?php echo substr($user['account_number'], -6); ?><button class="copy-btn" onclick="copyText('BARC<?php echo substr($user['account_number'], -6); ?>')"><i class="fas fa-copy"></i></button></span></div>
        <div class="info-row"><span class="info-label">SWIFT Code</span><span class="info-value">BARCGB22<button class="copy-btn" onclick="copyText('BARCGB22')"><i class="fas fa-copy"></i></button></span></div></div></div>
        
        <div class="glass-card"><div class="card-header"><div class="icon"><i class="fas fa-qrcode"></i></div><div><h2>Your QR Code</h2><p>Scan to receive payments instantly</p></div></div>
        <div class="qr-section"><div class="qr-code"><img src="<?php echo $qr_image_url; ?>" alt="Your QR Code"></div><div class="qr-actions"><button class="btn-download" onclick="downloadQR()"><i class="fas fa-download"></i> Download QR Code</button><button class="btn-download" style="background: #0f5c8c;" onclick="printQR()"><i class="fas fa-print"></i> Print QR Code</button></div><p style="color: #64748b; margin-top: 15px;"><i class="fas fa-info-circle"></i> Share this QR code with anyone to receive money directly into your account</p></div></div>
        
        <div class="glass-card"><div class="card-header"><div class="icon"><i class="fas fa-university"></i></div><div><h2>Linked Bank Accounts</h2><p>Add external bank accounts to receive transfers</p></div></div>
        <?php if (empty($linked_accounts)): ?><div class="empty-state"><i class="fas fa-university" style="font-size: 3rem; margin-bottom: 15px;"></i><p>No linked bank accounts</p><p style="font-size: 0.9rem;">Add a bank account below to receive money from other banks</p></div>
        <?php else: ?><div class="accounts-grid"><?php foreach ($linked_accounts as $acc): ?><div class="account-card <?php echo $acc['is_default'] ? 'default' : ''; ?>"><?php if ($acc['is_default']): ?><div class="default-badge"><i class="fas fa-star"></i> DEFAULT</div><?php endif; ?><div class="bank-name"><?php echo htmlspecialchars($acc['bank_name']); ?></div><div class="account-detail"><i class="fas fa-user"></i> <?php echo htmlspecialchars($acc['account_holder']); ?></div><div class="account-detail"><i class="fas fa-credit-card"></i> ****<?php echo substr($acc['account_number'], -4); ?></div><?php if ($acc['ifsc_code']): ?><div class="account-detail"><i class="fas fa-code"></i> <?php echo $acc['ifsc_code']; ?></div><?php endif; ?><div class="account-actions"><?php if (!$acc['is_default']): ?><a href="?set_default=1&id=<?php echo $acc['id']; ?>" class="btn-sm btn-default"><i class="fas fa-star"></i> Set Default</a><?php endif; ?><a href="?delete_account=1&id=<?php echo $acc['id']; ?>" class="btn-sm btn-delete" onclick="return confirm('Remove this account?')"><i class="fas fa-trash"></i> Remove</a></div></div><?php endforeach; ?></div><?php endif; ?></div>
        
        <div class="glass-card"><div class="card-header"><div class="icon"><i class="fas fa-plus-circle"></i></div><div><h2>Link New Bank Account</h2><p>Add external bank account to receive money</p></div></div>
        <form method="POST"><input type="hidden" name="add_bank_account" value="1"><div class="form-grid"><div class="form-group"><label>Bank Name *</label><input type="text" name="bank_name" placeholder="e.g., Global IME Bank, Nabil Bank" required></div><div class="form-group"><label>Account Holder Name *</label><input type="text" name="account_holder" placeholder="Name on account" required></div><div class="form-group"><label>Account Number *</label><input type="text" name="account_number" placeholder="Account number" required></div><div class="form-group"><label>IFSC / Routing Code</label><input type="text" name="ifsc_code" placeholder="IFSC or routing number"></div></div><div class="checkbox-group"><input type="checkbox" name="is_default" id="is_default"><label for="is_default">Set as default receiving account</label></div><div class="alert alert-info" style="margin-top: 20px; background: #e6f0f7;"><i class="fas fa-lock"></i><span>Your account details are secure and encrypted. Only you can see this information.</span></div><button type="submit" class="btn-primary"><i class="fas fa-link"></i> Link Bank Account</button></form></div>
    </div>
    <script>
        function copyText(text) { navigator.clipboard.writeText(text); alert('Copied to clipboard: ' + text); }
        function downloadQR() { const qrImage = document.querySelector('.qr-code img'); const link = document.createElement('a'); link.download = 'barclays_qr_code.png'; link.href = qrImage.src; link.click(); }
        function printQR() { const qrCode = document.querySelector('.qr-code').innerHTML; const printWindow = window.open('', '_blank'); printWindow.document.write(`<html><head><title>Barclays Banking - QR Code</title><style>body{font-family:Arial;text-align:center;padding:50px;}.qr{margin:20px auto;}h2{color:#0f5c8c;}</style></head><body><h2>Barclays Banking</h2><p>Scan this QR code to send money to</p><div class="qr">${qrCode}</div><div class="info"><p><strong>Account Holder:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p><p><strong>Account Number:</strong> <?php echo $user['account_number']; ?></p><p><strong>Currency:</strong> EUR (€)</p></div><p>Generated on: <?php echo date('M d, Y'); ?></p></body></html>`); printWindow.print(); }
    </script>
</body>
</html>