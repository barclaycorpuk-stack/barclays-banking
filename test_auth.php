<?php
// test_auth.php
require_once 'db.php';

echo "<h2>Database Auth Diagnostic</h2>";

try {
    // 1. Test database entry retrieval
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute(['admin', 'admin@barclays.com']);
    $user = $stmt->fetch();

    if (!$user) {
        die("❌ Error: Admin user not found in the database at all. Run setup_db.php again.");
    }

    echo "✅ Admin user found in database!<br>";
    echo "• Username in DB: <b>" . htmlspecialchars($user['username']) . "</b><br>";
    echo "• Stored Hash: <kbd>" . htmlspecialchars($user['password']) . "</kbd><br><br>";

    // 2. Test password verification manually
    $password_to_test = 'admin123';
    echo "Testing password: <b>$password_to_test</b><br>";

    if (password_verify($password_to_test, $user['password'])) {
        echo "🎉 <b>SUCCESS!</b> password_verify() matches perfectly in PHP runtime.<br>";
    } else {
        echo "❌ <b>FAILURE!</b> password_verify() returned false.<br>";
        echo "• Length of stored hash: " . strlen($user['password']) . " characters.<br>";
    }

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>