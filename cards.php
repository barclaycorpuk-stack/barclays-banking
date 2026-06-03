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

// Force clean presentation variables for Euro
$currency_symbol = '€';
$currency_code = 'EUR';

try {
    // 1. Get clean user info
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // 2. Check if the user already has a card assigned
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $card = $stmt->fetch();

    // 3. If no card exists, auto-generate a realistic one for the college project demo
    if (!$card) {
        $card_name = $user['full_name'];
        $card_type = 'visa';
        
        // Generate a realistic 16-digit Visa number starting with 4
        $card_number = '4' . rand(100, 999) . rand(1000, 9999) . rand(1000, 9999) . rand(100, 999);
        $masked_number = '**** **** **** ' . substr($card_number, -4);
        
        $expiry_month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
        $expiry_year = date('y', strtotime('+4 years')); // Valid for 4 years
        $cvv = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        // Insert the newly generated card with a safe 0.00 balance fallback
        $stmt = $pdo->prepare("INSERT INTO cards (user_id, card_name, card_type, card_number, masked_number, expiry_month, expiry_year, cvv, balance, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0.00, 1)");
        $stmt->execute([$user_id, $card_name, $card_type, $card_number, $masked_number, $expiry_month, $expiry_year, $cvv]);
        
        // Fetch the newly written card data
        $stmt = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $card = $stmt->fetch();
    }

    // 4. Force balance initialization cleanly to 0.00
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
        .container { max-width: 800px; margin: 0 auto; padding-top: 20px; }
        
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
            margin-bottom: 30px;
        }
        .back-btn:hover { background: rgba(255,255,255,0.1); transform: translateX(-3px); }

        .page-title { text-align: center; margin-bottom: 10px; font-size: 2rem; }
        .page-subtitle { text-align: center; color: #94a3b8; margin-bottom: 40px; font-size: 0.95rem; }

        /* --- 3D Flipping Card Styling --- */
        .card-space {
            perspective: 1000px;
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }
        .credit-card-container {
            width: 400px;
            height: 250px;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
        }
        .credit-card-container.flipped {
            transform: rotateY(180deg);
        }
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
        
        /* Front Design */
        .card-front {
            background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .card-front-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .chip { width: 50px; height: 38px; background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%); border-radius: 8px; }
        .contactless { font-size: 1.6rem; opacity: 0.85; transform: rotate(90deg); }
        .card-number {
            font-family: 'Share Tech Mono', monospace;
            font-size: 1.45rem;
            letter-spacing: 3px;
            word-spacing: 5px;
            margin: 25px 0 15px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.4);
        }
        .card-details-row { display: flex; justify-content: space-between; align-items: flex-end; }
        .card-holder-label, .card-expiry-label { font-size: 0.65rem; text-transform: uppercase; opacity: 0.7; letter-spacing: 1px; margin-bottom: 2px; }
        .card-holder-name, .card-expiry-val { font-size: 0.95rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .visa-logo { font-size: 1.8rem; font-weight: 800; font-style: italic; text-shadow: 1px 1px 2px rgba(0,0,0,0.3); }

        /* Back Design */
        .card-back {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            transform: rotateY(180deg);
            border: 1px solid rgba(255,255,255,0.05);
            padding: 25px 0;
        }
        .black-strip { width: 100%; height: 50px; background: #000; margin-top: 5px; }
        .signature-area { margin: 20px 25px 0 25px; }
        .sig-label { font-size: 0.65rem; text-transform: uppercase; opacity: 0.7; margin-bottom: 5px; padding-left: 5px; }
        .sig-box-container { display: flex; align-items: center; gap: 10px; }
        .signature-strip {
            flex: 1; height: 40px; background: repeating-linear-gradient(45deg, #e2e8f0, #e2e8f0 10px, #cbd5e1 10px, #cbd5e1 20px);
            border-radius: 4px; display: flex; align-items: center; padding-left: 15px;
            font-family: 'Courier New', Courier, monospace; color: #334155; font-weight: bold; font-style: italic; pointer-events: none;
        }
        .cvv-box { background: white; color: black; padding: 8px 12px; font-family: 'Share Tech Mono', monospace; font-weight: 700; border-radius: 4px; font-size: 1rem; box-shadow: inset 0 2px 4px rgba(0,0,0,0.2); }
        .back-footer { padding: 0 25px; margin-top: 15px; font-size: 0.6rem; color: #64748b; line-height: 1.4; }

        /* Action Info Card */
        .info-card {
            background: var(--secondary);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 25px;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.95rem; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #94a3b8; }
        .info-value { font-weight: 600; }
        .info-value.balance { color: #38bdf8; font-size: 1.1rem; }
        
        .hint-text { text-align: center; color: #64748b; font-size: 0.8rem; margin-top: 15px; display: flex; align-items: center; justify-content: center; gap: 6px; }
    </style>
</head>
<body>

    <div class="container">
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>

        <h1 class="page-title"><i class="fas fa-credit-card"></i> Card Management</h1>
        <p class="page-subtitle">View your securely auto-generated digital card credentials below</p>

        <div class="card-space">
            <div class="credit-card-container" id="myCard" onclick="toggleCardFlip()">
                
                <div class="card-face card-front">
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
                        <div class="visa-logo">VISA</div>
                    </div>
                </div>

                <div class="card-face card-back">
                    <div class="black-strip"></div>
                    
                    <div class="signature-area">
                        <div class="sig-label">Authorized Signature</div>
                        <div class="sig-box-container">
                            <div class="signature-strip">Barclays Bank System</div>
                            <div class="cvv-box"><?php echo $card['cvv']; ?></div>
                        </div>
                    </div>
                    
                    <div class="back-footer">
                        This card is property of Barclays Banking Corp. International project simulation framework. If found, please return to system administrator infrastructure.
                    </div>
                </div>

            </div>
        </div>

        <p class="hint-text"><i class="fas fa-sync-alt animate-spin"></i> Click or tap on the card above to flip it over</p>
        <br>

        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Card Brand</span>
                <span class="info-value"><i class="fab fa-cc-visa" style="color: #38bdf8;"></i> Visa Platinum Digital</span>
            </div>
            <div class="info-row">
                <span class="info-label">Linked Number</span>
                <span class="info-value" style="font-family: monospace; letter-spacing: 0.5px;"><?php echo $card['masked_number']; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Security Code (CVV)</span>
                <span class="info-value">*** (Flipped view only)</span>
            </div>
            <div class="info-row">
                <span class="info-label">Card Status</span>
                <span class="info-value" style="color: #10b981;"><i class="fas fa-check-circle"></i> Active / Approved</span>
            </div>
            <div class="info-row">
                <span class="info-label">Available Balance</span>
                <span class="info-value balance"><?php echo $currency_symbol; ?><?php echo number_format($card_balance, 2); ?></span>
            </div>
        </div>

    </div>

    <script>
        function toggleCardFlip() {
            document.getElementById('myCard').classList.toggle('flipped');
        }
    </script>
</body>
</html>