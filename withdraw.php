<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = floatval($_POST['amount']);
    $user_id = $_SESSION['user_id'];

    if ($amount <= 0) {
        $error = "Amount must be positive.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Get Account Info & Lock Row
            $stmt = $pdo->prepare("SELECT id, balance FROM accounts WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$user_id]);
            $account = $stmt->fetch();

            if ($account['balance'] < $amount) {
                throw new Exception("Insufficient funds!");
            }

            // 2. Deduct Money
            $update = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE id = ?");
            $update->execute([$amount, $account['id']]);

            // 3. Record Transaction
            $log = $pdo->prepare("INSERT INTO transactions (sender_account_id, receiver_account_id, amount, type) VALUES (?, ?, ?, 'withdrawal')");
            $log->execute([$account['id'], $account['id'], $amount]);

            $pdo->commit();
            $message = "Success! $$amount withdrawn.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Withdraw - Barclay Banking</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #0f172a; --accent: #38bdf8; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white; height: 100vh; display: flex; justify-content: center; align-items: center; margin: 0;
        }
        .card {
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(10px); padding: 40px;
            border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); width: 100%; max-width: 400px; text-align: center;
        }
        h2 { margin-bottom: 20px; color: var(--accent); }
        input {
            width: 100%; padding: 15px; margin: 20px 0; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white; font-size: 1.2rem; text-align: center; box-sizing: border-box;
        }
        .btn {
            width: 100%; padding: 15px; background: #f87171; /* Red for withdraw */
            color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 1rem;
        }
        .btn:hover { background: #ef4444; }
        .back-link { display: block; margin-top: 20px; color: rgba(255,255,255,0.5); text-decoration: none; }
        .success { color: #34d399; margin-bottom: 15px; }
        .error { color: #f87171; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="card">
    <h2><i class="fas fa-money-bill-wave"></i> Withdraw Cash</h2>

    <?php if ($message): ?> <div class="success"><?php echo $message; ?></div> <?php endif; ?>
    <?php if ($error): ?> <div class="error"><?php echo $error; ?></div> <?php endif; ?>

    <form method="POST">
        <label>Enter Amount to Withdraw</label>
        <input type="number" name="amount" placeholder="$0.00" step="0.01" required>
        <button type="submit" class="btn">Withdraw Now</button>
    </form>

    <a href="dashboard.php" class="back-link">Back to Dashboard</a>
</div>

</body>
</html>