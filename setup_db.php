<?php
// setup_db.php - Run this once to clean up the admin user
require_once 'db.php';

// Dynamically generate a clean, perfect hash right now in PHP
$real_hash = password_hash('admin123', PASSWORD_DEFAULT);

$sql = "
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    status VARCHAR(20) DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Delete the broken admin user so we can write a fresh one
DELETE FROM users WHERE username = 'admin' OR email = 'admin@barclays.com';

-- Insert the admin user using our clean variable
INSERT INTO users (full_name, username, email, password, role, status) 
VALUES ('System Admin', 'admin', 'admin@barclays.com', :password, 'admin', 'approved');
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':password' => $real_hash]);
    echo "✅ Dynamic Database Setup Complete! Try running test_auth.php now.";
} catch (PDOException $e) {
    die("❌ Error updating database: " . $e->getMessage());
}
?>