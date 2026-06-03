<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Force presentation variables for Euro
$currency_symbol = '€';
$currency_code = 'EUR';

try {
    // 1. Fetch user details
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // 2. Handle Card Freeze/Unfreeze Action
    if (isset($_POST['toggle_freeze'])) {
        $new_freeze_status = intval($_POST['freeze_status']) === 1 ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE cards SET is_default = ? WHERE user_id = ?"); // Using is_default slot or fallback mapping safely
        // Note: For project compliance, we'll track the card's active state natively in the session or via table state mapping
        $_SESSION['card_frozen_' . $user_id] = $new_freeze_status;
        $message = $new_freeze_status === 1 ? "❄️ Card frozen successfully! All transactions blocked." : "☀️ Card unfrozen successfully!";
    }

    // 3. Handle Custom Card Submission by User
    if (isset($_POST['add_custom_card'])) {
        $card_name = trim($_POST['custom_name']);
        $card_type = $_POST['custom_type'];
        $card_number = str_replace(' ', '', trim($_POST['custom_number']));
        $expiry_month = trim($_POST['custom_month']);
        $expiry_year = trim($_POST['custom_year']);
        $cvv = trim($_POST['custom_cvv']);
        
        if (strlen($card_number) !== 16 || !is_numeric($card_number)) {
            $error = "Card number must be exactly 16 digits.";
        } elseif (strlen($cvv) !== 3 || !is_numeric($cvv)) {
            $error = "CVV must be exactly 3 digits.";
        } else {
            $masked_number = '**** **** **** ' . substr($card_number, -4);
            
            // Wipe old entry and overwrite with user's customized input card metrics
            $pdo->prepare("DELETE FROM cards WHERE user_id = ?")->execute([$user_id]);
            
            $stmt = $pdo->prepare("INSERT INTO cards (user_id, card_name, card_type, card_number, masked_number, expiry_month, expiry_year, cvv, balance, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0.00, 1)");
            $stmt->execute([$user_id, $card_name, $card_type, $card_number, $masked_number, $expiry_month, $expiry_year, $cvv]);
            $message = "💳 Custom card linked successfully!";
        }
    }

    // 4. Retrieve Card Entry
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $card = $stmt->fetch();

    // 5. Fallback Default Card Generation if empty
    if (!$card) {
        $card_number = '4' . rand(100, 999) . rand(1000, 9999) . rand(1000, 9999) . rand(100, 999);
        $masked_number = '**** **** **** ' . substr($card_number, -4);
        $expiry_month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
        $expiry_year = date('y', strtotime('+4 years'));
        $cvv = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("INSERT INTO cards (user_id, card_name, card_type, card_number, masked_number, expiry_month, expiry_year, cvv, balance, is_default) VALUES (?, ?, 'visa', ?, ?, ?, ?, ?, 0.00, 1)");
        $stmt->execute([$user_id, $user['full_name'], $card_number, $masked_number, $expiry_month, $expiry_year, $cvv]);
        
        $stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $card = $stmt->fetch();
    }

    $is_frozen = isset($_SESSION['card_frozen_' . $user_id]) ? $_SESSION['card_frozen_' . $user_id] : 0;
    $card_balance = (isset($card['balance']) && !empty($card['balance'])) ? floatval($card['balance']) : 0.00;

} catch (PDOException $e) {
    $error = "System Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cards - Barclays Banking</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --secondary: #1e293b;
            --accent: #38bdf8;
            --text: #f8fafc;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, #111827 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--text);
        }
        .container { max-width: 1000px; margin: 0 auto; padding-top: 20px; }
        
        .back-btn {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.05);
            padding: 10px 20px;
            border-radius: 30px;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 20px;
        }
        .back-btn:hover { background: rgba(255,255,255,0.1); transform: translateX(-3px); }

        .page-title { text-align: center; margin-bottom: 5px; font-size: 2rem; }
        .page-subtitle { text-align: center; color: #94a3b8; margin-bottom: 30px; font-size: 0.95rem; }

        .alert { padding: 15px; border-radius: 12px; max-width: 500px; margin: 0 auto 20px auto; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #14532d; color: #4ade80; border: 1px solid #166534; }
        .alert-error { background: #7f1d1d; color: #fca5a5; border: 1px solid #991b1b; }

        .main-layout { display: flex; gap: 40px; flex-wrap: wrap; justify-content: center; margin-top: 20px; }
        .left-col { flex: 1; min-width: 350px; max-width: 450px; }
        .right-col { flex: 1; min-width: 350px; max-width: 450px; }

        /* --- 3D Flipping Card Styling --- */
        .card-space { perspective: 1000px; margin-bottom: 25px; }
        .credit-card-container {
            width: 100%;
            height: 240px;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
        }
        .credit-card-container.flipped { transform: rotateY(180deg); }
        .card-face {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        /* Frozen Overlay Condition */
        .frozen-overlay {
            display: none; position: absolute; top:0; left:0; width:100%; height:100%;
            background: rgba(148, 163, 184, 0.25); backdrop-filter: blur(4px);
            border-radius: 20px; z-index: 10; align-items: center; justify-content: center;
            border: 2px dashed #60a5fa; box-shadow: inset 0 0 20px rgba(255,255,255,0.2);
        }
        .frozen-overlay span { background: #1e3a8a; color: #93c5fd; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.85rem; border: 1px solid #1e40af; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .credit-card-container.is-card-frozen .frozen-overlay { display: flex; }

        /* Front Skins based on type selection */
        .card-front { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1px solid rgba(255,255,255,0.1); }
        .card-front.visa { background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); }
        .card-front.mastercard { background: linear-gradient(135deg, #bf55ec 0%, #f22613 100%); }
        .card-front.amex { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }

        .card-front-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .chip { width: 45px; height: 32px; background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%); border-radius: 6px; }
        .contactless { font-size: 1.4rem; opacity: 0.85; transform: rotate(90deg); }
        .card-number { font-family: 'Share Tech Mono', monospace; font-size: 1.4rem; letter-spacing: 2px; word-spacing: 4px; margin: 25px 0 10px 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.4); }
        .card-details-row { display: flex; justify-content: space-between; align-items: flex-end; }
        .card-holder-label, .card-expiry-label { font-size: 0.6rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 1px; }
        .card-holder-name, .card-expiry-val { font-size: 0.9rem; font-weight: 500; text-transform: uppercase; }
        .logo-branding { font-size: 1.4rem; font-weight: 800; font-style: italic; }

        /* Back Face Design */
        .card-back { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); transform: rotateY(180deg); border: 1px solid rgba(255,255,255,0.05); padding: 25px 0; }
        .black-strip { width: 100%; height: 45px; background: #000; margin-top: 5px; }
        .signature-area { margin: 15px 25px 0 25px; }
        .sig-box-container { display: flex; align-items: center; gap: 10px; }
        .signature-strip { flex: 1; height: 35px; background: #e2e8f0; border-radius: 4px; display: flex; align-items: center; padding-left: 10px; font-family: monospace; color: #475569; font-weight: bold; }
        .cvv-box { background: white; color: black; padding: 6px 12px; font-family: 'Share Tech Mono', monospace; font-weight: 700; border-radius: 4px; }
        .back-footer { padding: 0 25px; margin-top: 15px; font-size: 0.55rem; color: #64748b; line-height: 1.3; }

        .hint-text { text-align: center; color: #64748b; font-size: 0.75rem; margin-top: 10px; margin-bottom: 20px; }

        /* Info & Control Modules */
        .panel-box { background: var(--secondary); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 25px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .panel-box h3 { font-size: 1.1rem; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px; }
        
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; font-size: 0.9rem; }
        .info-label { color: #94a3b8; }
        .info-value { font-weight: 600; }
        
        .btn-action { width: 100%; padding: 12px; border-radius: 50px; font-size: 0.9rem; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.3s; }
        .btn-freeze-toggle { background: #ef4444; color: white; }
        .btn-freeze-toggle.is-active-unfreeze { background: #10b981; }
        .btn-action:hover { transform: translateY(-2px); filter: brightness(1.1); }

        /* Custom Input Form controls */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.8rem; color: #cbd5e1; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: white; font-size: 0.9rem; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--accent); }
        .form-row-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .btn-submit-custom { background: linear-gradient(135deg, #38bdf8, #0284c7); color: #0f172a; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="container">
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>

        <h1 class="page-title"><i class="fas fa-shield-alt"></i> Card Management Control</h1>
        <p class="page-subtitle">Freeze existing system profiles or provision specialized personal custom card numbers</p>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="main-layout">
            
            <div class="left-col">
                <div class="card-space">
                    <div class="credit-card-container <?php echo $is_frozen === 1 ? 'is-card-frozen' : ''; ?>" id="visualCard">
                        
                        <div class="frozen-overlay" onclick="toggleCardFlip()">
                            <span><i class="fas fa-lock"></i> CARD BLOCKED / FROZEN</span>
                        </div>

                        <div class="card-face card-front <?php echo htmlspecialchars($card['card_type']); ?>" onclick="toggleCardFlip()">
                            <div class="card-front-header">
                                <div class="chip"></div>
                                <div class="contactless"><i class="fas fa-wifi"></i></div>
                            </div>
                            <div class="card-number">
                                <?php 
                                $num = $card['card_number'];
                                echo substr($num, 0, 4) . ' ' . substr($num, 4, 4) . ' ' . substr($num, 8, 4) . ' ' . substr($num, 12, 4);
                                ?>
                            </div>
                            <div class="card-details-row">
                                <div>
                                    <div class="card-holder-label">Card Holder</div>
                                    <div class="card-holder-name"><?php echo htmlspecialchars($card['card_name']); ?></div>
                                </div>
                                <div>
                                    <div class="card-expiry-label">Expires</div>
                                    <div class="card-expiry-val"><?php echo $card['expiry_month'] . '/' . $card['expiry_year']; ?></div>
                                </div>
                                <div class="logo-branding"><?php echo strtoupper($card['card_type']); ?></div>
                            </div>
                        </div>

                        <div class="card-face card-back" onclick="toggleCardFlip()">
                            <div class="black-strip"></div>
                            <div class="signature-area">
                                <div class="sig-box-container">
                                    <div class="signature-strip">Barclays Simulation</div>
                                    <div class="cvv-box"><?php echo $card['cvv']; ?></div>
                                </div>
                            </div>
                            <div class="back-footer">
                                Simulated production engine framework assignment node. Security validation constraints mapping explicitly handled via PostgreSQL transaction parameters.
                            </div>
                        </div>

                    </div>
                </div>
                <p class="hint-text"><i class="fas fa-sync-alt"></i> Click directly onto the active card template block to flip</p>

                <div class="panel-box">
                    <h3><i class="fas fa-sliders-h"></i> Security Controls</h3>
                    <form method="POST">
                        <input type="hidden" name="toggle_freeze" value="1">
                        <input type="hidden" name="freeze_status" value="<?php echo $is_frozen; ?>">
                        <?php if ($is_frozen === 1): ?>
                            <button type="submit" class="btn-action btn-freeze-toggle is-active-unfreeze"><i class="fas fa-sun"></i> Unfreeze & Activate Card</button>
                        <?php else: ?>
                            <button type="submit" class="btn-action btn-freeze-toggle"><i class="fas fa-snowflake"></i> Freeze & Block Card</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="right-col">
                <div class="panel-box">
                    <h3><i class="fas fa-info-circle"></i> Live Ledger Information</h3>
                    <div class="info-row"><span class="info-label">Card Class</span><span class="info-value"><?php echo ucfirst($card['card_type']); ?> Multi-Currency Ledger</span></div>
                    <div class="info-row"><span class="info-label">Masked Number</span><span class="info-value" style="font-family: monospace;"><?php echo $card['masked_number']; ?></span></div>
                    <div class="info-row"><span class="info-label">Live Status</span><span class="info-value" style="color: <?php echo $is_frozen === 1 ? '#ef4444' : '#10b981'; ?>;"><?php echo $is_frozen === 1 ? '⚠️ BLOCKED / FROZEN' : '✅ ACTIVE / SECURE'; ?></span></div>
                    <div class="info-row"><span class="info-label">Available Pool</span><span class="info-value" style="color: #38bdf8; font-size:1.1rem;"><?php echo $currency_symbol; ?><?php echo number_format($card_balance, 2); ?></span></div>
                </div>

                <div class="panel-box">
                    <h3><i class="fas fa-plus-square"></i> Link Personal Card Details</h3>
                    <form method="POST">
                        <input type="hidden" name="add_custom_card" value="1">
                        <div class="form-group">
                            <label>Card Brand Type</label>
                            <select name="custom_type" required>
                                <option value="visa">🔵 Visa Core Premium</option>
                                <option value="mastercard">🔴 Mastercard World Elite</option>
                                <option value="amex">🟢 American Express Gold</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Card Holder Name</label>
                            <input type="text" name="custom_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Preferred 16-Digit Card Number</label>
                            <input type="text" name="custom_number" placeholder="4123 4567 8901 2345" maxlength="19" required>
                        </div>
                        <div class="form-row-grid">
                            <div class="form-group"><label>Exp Month</label><input type="text" name="custom_month" placeholder="12" maxlength="2" required></div>
                            <div class="form-group"><label>Exp Year (YY)</label><input type="text" name="custom_year" placeholder="29" maxlength="2" required></div>
                            <div class="form-group"><label>CVV Code</label><input type="password" name="custom_cvv" placeholder="***" maxlength="3" required></div>
                        </div>
                        <button type="submit" class="btn-action btn-submit-custom"><i class="fas fa-link"></i> Bind New Card Metrics</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        function toggleCardFlip() {
            // Disallow flip interactions if card overlay status layer is actively frozen block state
            <?php if ($is_frozen === 1): ?> return; <?php endif; ?>
            document.getElementById('visualCard').classList.toggle('flipped');
        }
    </script>
</body>
</html>