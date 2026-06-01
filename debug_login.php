<?php
require 'db.php';

$email = 'admin@barclays.com';
$test_password = 'admin123';

echo "<h2>Login Debug</h2>";

// Check if user exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    echo "<p style='color:green'>✅ User found!</p>";
    echo "<p>Email: " . $user['email'] . "</p>";
    echo "<p>Stored password hash: " . $user['password'] . "</p>";
    echo "<p>Hash length: " . strlen($user['password']) . " characters</p>";
    
    // Test password_verify
    if (password_verify($test_password, $user['password'])) {
        echo "<p style='color:green; font-size:1.2rem'>✅✅✅ PASSWORD MATCHES! Login should work! ✅✅✅</p>";
    } else {
        echo "<p style='color:red'>❌ Password verification FAILED!</p>";
        
        // Try generating new hash
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo "<p>New hash for '$test_password': <code>" . $new_hash . "</code></p>";
        echo "<p>Run this SQL to fix:</p>";
        echo "<pre>UPDATE users SET password = '$new_hash' WHERE email = '$email';</pre>";
    }
    
    // Check status
    echo "<p>Status: " . $user['status'] . "</p>";
    echo "<p>Role: " . $user['role'] . "</p>";
    
} else {
    echo "<p style='color:red'>❌ User not found with email: $email</p>";
}

// Also check what's in the database
echo "<h3>All users in database:</h3>";
$stmt = $pdo->query("SELECT id, email, role, status, LEFT(password, 30) as hash_preview FROM users");
$users = $stmt->fetchAll();
foreach ($users as $u) {
    echo "<p>ID: {$u['id']} | Email: {$u['email']} | Role: {$u['role']} | Status: {$u['status']} | Hash: {$u['hash_preview']}...</p>";
}
?>