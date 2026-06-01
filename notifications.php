<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle mark as read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([$_GET['id'], $user_id]);
    header("Location: notifications.php");
    exit;
}

// Handle mark all as read
if (isset($_GET['mark_all_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    header("Location: notifications.php");
    exit;
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")->execute([$_GET['id'], $user_id]);
    header("Location: notifications.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$unread_count = 0;
foreach ($notifications as $n) { if ($n['is_read'] == 0) $unread_count++; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Barclays Banking</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            flex-wrap: wrap;
            gap: 15px;
        }
        .back-btn {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 30px;
        }
        .unread-badge { background: #ef4444; color: white; padding: 5px 12px; border-radius: 30px; font-size: 0.8rem; font-weight: 600; }
        .mark-all-btn { background: rgba(255,255,255,0.1); color: white; border: none; padding: 8px 15px; border-radius: 30px; cursor: pointer; }
        .notifications-list { background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); border-radius: 20px; overflow: hidden; }
        .notification-item { display: flex; align-items: center; gap: 15px; padding: 20px; border-bottom: 1px solid #e2e8f0; transition: 0.3s; }
        .notification-item.unread { background: #e6f0f7; border-left: 4px solid #0f5c8c; }
        .notification-icon { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .notification-icon.deposit { background: #dcfce7; color: #10b981; }
        .notification-icon.transfer { background: #e6f0f7; color: #0f5c8c; }
        .notification-icon.loan { background: #fef08a; color: #d4af37; }
        .notification-icon.security { background: #fee2e2; color: #ef4444; }
        .notification-content { flex: 1; }
        .notification-title { font-weight: 600; color: #333; margin-bottom: 5px; }
        .notification-message { color: #64748b; font-size: 0.85rem; margin-bottom: 5px; }
        .notification-time { color: #94a3b8; font-size: 0.7rem; display: flex; align-items: center; gap: 5px; }
        .action-btn { background: none; border: none; color: #94a3b8; cursor: pointer; padding: 5px; border-radius: 5px; }
        .action-btn:hover { background: #e2e8f0; color: #0f5c8c; }
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-state i { font-size: 4rem; margin-bottom: 15px; opacity: 0.5; }
        @media (max-width: 768px) { .notification-item { flex-direction: column; text-align: center; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <h2><i class="fas fa-bell"></i> Notifications</h2>
            <div class="unread-badge"><?php echo $unread_count; ?> unread</div>
        </div>
        <div class="notifications-list">
            <?php if (empty($notifications)): ?>
                <div class="empty-state"><i class="fas fa-bell-slash"></i><h3>No Notifications</h3><p>You're all caught up!</p></div>
            <?php else: ?>
                <div style="padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
                    <button class="mark-all-btn" onclick="window.location.href='?mark_all_read=1'"><i class="fas fa-check-double"></i> Mark all as read</button>
                </div>
                <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?php echo $notif['is_read'] == 0 ? 'unread' : ''; ?>">
                    <div class="notification-icon <?php echo $notif['type']; ?>">
                        <?php if ($notif['type'] == 'deposit'): ?><i class="fas fa-wallet"></i>
                        <?php elseif ($notif['type'] == 'transfer'): ?><i class="fas fa-exchange-alt"></i>
                        <?php elseif ($notif['type'] == 'loan'): ?><i class="fas fa-hand-holding-usd"></i>
                        <?php else: ?><i class="fas fa-bell"></i><?php endif; ?>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                        <div class="notification-time"><i class="farfa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></div>
                    </div>
                    <div class="notification-actions">
                        <?php if ($notif['is_read'] == 0): ?>
                            <a href="?mark_read=1&id=<?php echo $notif['id']; ?>" class="action-btn" title="Mark as read"><i class="fas fa-check"></i></a>
                        <?php endif; ?>
                        <a href="?delete=1&id=<?php echo $notif['id']; ?>" class="action-btn" title="Delete" onclick="return confirm('Delete this notification?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>