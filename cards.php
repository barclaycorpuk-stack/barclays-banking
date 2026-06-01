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
$show_add_card_form = false;
$show_load_money_form = false;
$selected_card_id = null;
$currency_symbol = CURRENCY_SYMBOL;

// Fetch User Data
$stmt = $pdo->prepare("SELECT users.*, accounts.balance, accounts.account_number FROM users JOIN accounts ON users.id = accounts.user_id WHERE users.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$main_balance = $user['balance'] ?? 0;

// Fetch all user's cards
$stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$cards = $stmt->fetchAll();

// Handle Add New Card
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_card'])) {
    $card_name = trim($_POST['card_name']);
    $card_type = trim($_POST['card_type']);
    $card_number = trim($_POST['card_number']);
    $expiry_month = trim($_POST['expiry_month']);
    $expiry_year = trim($_POST['expiry_year']);
    $cvv = trim($_POST['cvv']);
    
    if (empty($card_name) || empty($card_number) || empty($cvv)) {
        $error = "Please fill all required fields!";
    } elseif (strlen($card_number) < 15 || strlen($card_number) > 16) {
        $error = "Invalid card number! Must be 15-16 digits.";
    } elseif (strlen($cvv) != 3 || !ctype_digit($cvv)) {
        $error = "CVV must be 3 digits!";
    } elseif (strlen($expiry_month) != 2 || $expiry_month < 1 || $expiry_month > 12) {
        $error = "Invalid expiry month!";
    } elseif (strlen($expiry_year) != 2 || $expiry_year < date('y')) {
        $error = "Invalid expiry year!";
    } else {
        $masked_number = "**** **** **** " . substr($card_number, -4);
        $is_default = (count($cards) == 0) ? 1 : 0;
        
        $stmt = $pdo->prepare("INSERT INTO cards (user_id, card_name, card_type, card_number, masked_number, expiry_month, expiry_year, cvv, is_default, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        if ($stmt->execute([$user_id, $card_name, $card_type, $card_number, $masked_number, $expiry_month, $expiry_year, $cvv, $is_default])) {
            $message = "Card added successfully!";
            $stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
            $stmt->execute([$user_id]);
            $cards = $stmt->fetchAll();
        } else {
            $error = "Failed to add card. Please try again.";
        }
    }
    $show_add_card_form = false;
}

// Handle Load Money to Card
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['load_money'])) {
    $card_id = intval($_POST['card_id']);
    $load_amount = floatval($_POST['load_amount']);
    
    if ($load_amount <= 0) {
        $error = "Please enter a valid amount!";
    } elseif ($load_amount > $main_balance) {
        $error = "Insufficient balance! Your available balance is " . $currency_symbol . number_format($main_balance, 2);
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("SELECT * FROM cards WHERE id = ? AND user_id = ?");
            $stmt->execute([$card_id, $user_id]);
            $card = $stmt->fetch();
            
            if (!$card) {
                throw new Exception("Card not found!");
            }
            
            $stmt = $pdo->prepare("UPDATE cards SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$load_amount, $card_id]);
            
            $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE user_id = ?");
            $stmt->execute([$load_amount, $user_id]);
            
            $description = "Loaded " . $currency_symbol . number_format($load_amount, 2) . " to " . $card['card_name'] . " (" . $card['masked_number'] . ")";
            $stmt = $pdo->prepare("INSERT INTO transactions (sender_account_id, amount, type, description, created_at) VALUES ((SELECT id FROM accounts WHERE user_id = ?), ?, 'card_load', ?, NOW())");
            $stmt->execute([$user_id, $load_amount, $description]);
            
            $pdo->commit();
            
            $main_balance -= $load_amount;
            $message = "Successfully loaded " . $currency_symbol . number_format($load_amount, 2) . " to your card!";
            
            $stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
            $stmt->execute([$user_id]);
            $cards = $stmt->fetchAll();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to load money: " . $e->getMessage();
        }
    }
    $show_load_money_form = false;
    $selected_card_id = null;
}

// Handle Delete Card
if (isset($_GET['delete_card'])) {
    $card_id = intval($_GET['delete_card']);
    
    $stmt = $pdo->prepare("SELECT balance FROM cards WHERE id = ? AND user_id = ?");
    $stmt->execute([$card_id, $user_id]);
    $card_balance = $stmt->fetchColumn();
    
    if ($card_balance > 0) {
        $error = "Cannot delete card with remaining balance. Please withdraw or transfer the balance first.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM cards WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$card_id, $user_id])) {
            $message = "Card deleted successfully!";
            $stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
            $stmt->execute([$user_id]);
            $cards = $stmt->fetchAll();
        } else {
            $error = "Failed to delete card.";
        }
    }
}

// Handle Set Default Card
if (isset($_GET['set_default'])) {
    $card_id = intval($_GET['set_default']);
    
    $stmt = $pdo->prepare("UPDATE cards SET is_default = 0 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    $stmt = $pdo->prepare("UPDATE cards SET is_default = 1 WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$card_id, $user_id])) {
        $message = "Default card updated!";
        $stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$user_id]);
        $cards = $stmt->fetchAll();
    } else {
        $error = "Failed to update default card.";
    }
}

// Handle Freeze/Unfreeze
if (isset($_POST['toggle_freeze'])) {
    $current_status = $user['is_frozen'] ?? 0;
    $new_status = $current_status ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET is_frozen = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);
    
    $stmt = $pdo->prepare("SELECT users.*, accounts.balance, accounts.account_number FROM users JOIN accounts ON users.id = accounts.user_id WHERE users.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    $message = $new_status ? "Card frozen successfully!" : "Card unfrozen successfully!";
}

// Handle Online Payments Toggle
if (isset($_POST['toggle_online'])) {
    $current_online = $user['online_payments_enabled'] ?? 1;
    $new_online = $current_online ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET online_payments_enabled = ? WHERE id = ?");
    $stmt->execute([$new_online, $user_id]);
    
    $stmt = $pdo->prepare("SELECT users.*, accounts.balance, accounts.account_number FROM users JOIN accounts ON users.id = accounts.user_id WHERE users.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    $message = $new_online ? "Online payments enabled!" : "Online payments disabled!";
}

if (isset($_GET['show_add_card'])) { $show_add_card_form = true; }
if (isset($_GET['show_load_money'])) { $show_load_money_form = true; $selected_card_id = intval($_GET['card_id']); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cards - Barclays Banking</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 40px; display: flex; flex-direction: column; align-items: center; }
        .container { max-width: 900px; width: 100%; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; color: white; flex-wrap: wrap; gap: 15px; }
        .back-btn { color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 30px; transition: 0.3s; }
        .balance-display { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 15px 25px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .balance-display .amount { font-size: 1.8rem; font-weight: 700; color: #d4af37; }
        .card-container { perspective: 1000px; margin-bottom: 40px; display: flex; justify-content: center; }
        .atm-card { background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); width: 450px; height: 260px; border-radius: 20px; padding: 25px; color: white; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.4); transition: transform 0.6s; }
        .atm-card:hover { transform: translateY(-10px); }
        .chip { width: 50px; height: 35px; background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%); border-radius: 5px; margin-bottom: 20px; }
        .contactless { position: absolute; top: 25px; right: 25px; font-size: 1.5rem; opacity: 0.8; }
        .card-number { font-size: 1.4rem; letter-spacing: 2px; margin: 15px 0; font-family: monospace; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        .section-title { color: white; margin: 30px 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .btn-add-card { background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 30px; text-decoration: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card-item { background: white; border-radius: 20px; padding: 20px; position: relative; transition: 0.3s; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .card-item.default { border: 2px solid #d4af37; background: linear-gradient(135deg, #fff, #fef3c7); }
        .default-badge { position: absolute; top: -10px; right: 20px; background: #d4af37; color: #0f172a; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; }
        .card-balance { font-size: 1.5rem; font-weight: 700; color: #10b981; margin: 10px 0; }
        .card-actions { display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap; }
        .btn-card { padding: 8px 15px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 500; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; }
        .btn-load { background: #0f5c8c; color: white; }
        .btn-default { background: #d4af37; color: #0f172a; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .controls-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; width: 100%; margin-top: 20px; }
        .control-box { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 20px; border-radius: 15px; display: flex; align-items: center; justify-content: space-between; border: 1px solid rgba(255,255,255,0.05); transition: 0.3s; cursor: pointer; }
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #4b5563; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #38bdf8; }
        input:checked + .slider:before { transform: translateX(26px); }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #dcfce7; color: #16a34a; }
        .alert-error { background: #fee2e2; color: #dc2626; }
        .info-box { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 15px; padding: 20px; margin-top: 30px; text-align: center; border: 1px solid rgba(255,255,255,0.05); }
        .empty-cards { text-align: center; padding: 40px; background: rgba(255,255,255,0.1); border-radius: 20px; color: white; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal { background: white; border-radius: 20px; padding: 30px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn-submit { flex: 1; padding: 12px; background: #0f5c8c; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }
        .btn-cancel { flex: 1; padding: 12px; background: #e2e8f0; color: #333; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }
        @media (max-width: 768px) { body { padding: 20px; } .atm-card { width: 100%; max-width: 400px; } .controls-grid { grid-template-columns: 1fr; } .cards-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header"><h2><i class="fas fa-credit-card"></i> My Cards</h2><a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a></div>
        <div class="balance-display"><div><div class="label"><i class="fas fa-wallet"></i> Available Balance</div><div class="amount"><?php echo $currency_symbol; ?><?php echo number_format($main_balance, 2); ?></div></div><div><div class="label"><i class="fas fa-credit-card"></i> Total Cards</div><div class="amount" style="font-size: 1.5rem;"><?php echo count($cards); ?></div></div></div>
        <?php if (!empty($message)): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>
        <div class="card-container"><div class="atm-card"><div class="contactless"><i class="fas fa-wifi" style="transform: rotate(90deg);"></i></div><div class="chip"></div><div class="card-number"><?php echo implode(' ', str_split($user['account_number'], 4)); ?></div><div class="card-info"><div><span>CARD HOLDER</span><br><strong><?php echo strtoupper($user['full_name']); ?></strong></div><div><span>EXPIRES</span><br><strong>12/28</strong></div></div><div class="card-footer"><div class="card-logo">VISA</div></div><?php if ($user['is_frozen'] ?? 0): ?><div class="frozen-overlay"><div class="frozen-badge"><i class="fas fa-snowflake"></i> FROZEN</div></div><?php endif; ?></div></div>
        <div class="section-title"><h3><i class="fas fa-plus-circle"></i> Your Added Cards</h3><a href="?show_add_card=1" class="btn-add-card"><i class="fas fa-plus"></i> Add New Card</a></div>
        <?php if (empty($cards)): ?><div class="empty-cards"><i class="fas fa-credit-card" style="font-size: 3rem; margin-bottom: 15px;"></i><p>You haven't added any cards yet.</p><p style="font-size: 0.9rem;">Click "Add New Card" to link a card to your account.</p></div>
        <?php else: ?><div class="cards-grid"><?php foreach ($cards as $card): ?><div class="card-item <?php echo $card['is_default'] ? 'default' : ''; ?>"><?php if ($card['is_default']): ?><div class="default-badge"><i class="fas fa-star"></i> Default</div><?php endif; ?><div class="card-type"><i class="fas <?php echo $card['card_type'] == 'visa' ? 'fa-cc-visa' : ($card['card_type'] == 'mastercard' ? 'fa-cc-mastercard' : 'fa-credit-card'); ?>"></i> <?php echo strtoupper($card['card_name']); ?></div><div class="card-number"><?php echo $card['masked_number']; ?></div><div class="card-balance"><?php echo $currency_symbol; ?><?php echo number_format($card['balance'], 2); ?></div><div class="card-actions"><a href="?show_load_money=1&card_id=<?php echo $card['id']; ?>" class="btn-card btn-load"><i class="fas fa-download"></i> Load Money</a><?php if (!$card['is_default']): ?><a href="?set_default=<?php echo $card['id']; ?>" class="btn-card btn-default" onclick="return confirm('Set this as your default card?')"><i class="fas fa-check-circle"></i> Set Default</a><?php endif; ?><a href="?delete_card=<?php echo $card['id']; ?>" class="btn-card btn-delete" onclick="return confirm('Delete this card? This action cannot be undone.')"><i class="fas fa-trash"></i> Delete</a></div></div><?php endforeach; ?></div><?php endif; ?>
        <div class="controls-grid"><div class="control-box"><div style="display:flex; gap:15px; align-items:center;"><div class="icon-box"><i class="fas fa-snowflake"></i></div><div class="text-box"><h4><?php echo ($user['is_frozen'] ?? 0) ? 'Unfreeze Card' : 'Freeze Card'; ?></h4><p><?php echo ($user['is_frozen'] ?? 0) ? 'Reactivate your card' : 'Temporarily disable card'; ?></p></div></div><form method="POST" style="margin:0;"><input type="hidden" name="toggle_freeze" value="1"><label class="switch"><input type="checkbox" <?php echo ($user['is_frozen'] ?? 0) ? 'checked' : ''; ?> onchange="this.form.submit()"><span class="slider"></span></label></form></div><div class="control-box"><div style="display:flex; gap:15px; align-items:center;"><div class="icon-box"><i class="fas fa-globe"></i></div><div class="text-box"><h4>Online Payments</h4><p>Enable/Disable internet usage</p></div></div><form method="POST" style="margin:0;"><input type="hidden" name="toggle_online" value="1"><label class="switch"><input type="checkbox" <?php echo ($user['online_payments_enabled'] ?? 1) ? 'checked' : ''; ?> onchange="this.form.submit()"><span class="slider"></span></label></form></div></div>
        <div class="info-box"><i class="fas fa-shield-alt"></i><h4 style="margin-bottom: 10px;">Card Security</h4><p>Your cards are protected with 256-bit encryption. For security, never share your PIN or CVV with anyone.</p><p style="margin-top: 10px; font-size: 0.8rem;"><i class="fas fa-phone"></i> Report lost/stolen: 1-800-BARCLAYS</p></div>
    </div>
    <div id="addCardModal" class="modal-overlay" <?php echo $show_add_card_form ? 'style="display: flex;"' : ''; ?>><div class="modal"><h3><i class="fas fa-plus-circle"></i> Add New Card</h3><form method="POST"><input type="hidden" name="add_card" value="1"><div class="form-group"><label>Card Name</label><input type="text" name="card_name" placeholder="e.g., My Personal Card" required></div><div class="form-group"><label>Card Type</label><select name="card_type" required><option value="visa">Visa</option><option value="mastercard">Mastercard</option><option value="amex">American Express</option></select></div><div class="form-group"><label>Card Number</label><input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="16" pattern="[0-9]{15,16}" required></div><div class="form-group"><label>Expiry Date</label><div style="display: flex; gap: 10px;"><input type="text" name="expiry_month" placeholder="MM" maxlength="2" style="width: 80px;" required><input type="text" name="expiry_year" placeholder="YY" maxlength="2" style="width: 80px;" required></div></div><div class="form-group"><label>CVV</label><input type="password" name="cvv" placeholder="123" maxlength="3" pattern="[0-9]{3}" required></div><div class="modal-actions"><button type="submit" class="btn-submit">Add Card</button><a href="cards.php" class="btn-cancel">Cancel</a></div></form></div></div>
    <div id="loadMoneyModal" class="modal-overlay" <?php echo $show_load_money_form ? 'style="display: flex;"' : ''; ?>><div class="modal"><h3><i class="fas fa-download"></i> Load Money to Card</h3><form method="POST"><input type="hidden" name="load_money" value="1"><input type="hidden" name="card_id" value="<?php echo $selected_card_id; ?>"><div class="form-group"><label>Available Balance</label><div style="font-size: 1.5rem; font-weight: 700; color: #10b981;"><?php echo $currency_symbol; ?><?php echo number_format($main_balance, 2); ?></div></div><div class="form-group"><label>Amount to Load (<?php echo $currency_symbol; ?>)</label><input type="number" name="load_amount" min="1" max="<?php echo $main_balance; ?>" step="0.01" placeholder="Enter amount" required></div><div class="modal-actions"><button type="submit" class="btn-submit">Load Money</button><a href="cards.php" class="btn-cancel">Cancel</a></div></form></div></div>
    <script>setTimeout(()=>{document.querySelectorAll('.alert').forEach(alert=>{alert.style.display='none';});},5000);</script>
</body>
</html>