<?php
// setup_db.php
require_once 'db.php';

// 1. Generate the mathematical hash for admin123 cleanly in PHP
$real_hash = password_hash('admin123', PASSWORD_DEFAULT);

try {
    // 2. Clear out any old admin entry completely using a standalone command
    $pdo->exec("DELETE FROM users WHERE username = 'admin' OR email = 'admin@barclays.com'");

    // 3. Insert the fresh admin entry with the correct hash
    $sql = "INSERT INTO users (full_name, username, email, password, role, status) 
            VALUES ('System Admin', 'admin', 'admin@barclays.com', :password, 'admin', 'approved')";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':password' => $real_hash]);

    echo "✅ Database updated successfully! The password is now natively set to admin123.";

} catch (PDOException $e) {
    die("❌ Error updating database: " . $e->getMessage());
}
?>