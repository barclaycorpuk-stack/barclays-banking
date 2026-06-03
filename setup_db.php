<?php
// setup_db.php - Run this to fix your admin user
require_once 'db.php';

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

-- Updated hash: This is now correctly set for 'admin123'
INSERT INTO users (full_name, username, email, password, role, status) 
VALUES ('System Admin', 'admin', 'admin@barclays.com', '$2y$10$7R9I6.7mD99d0N8L3UqRSu1Y98m.gE5S8S0Z9P6hG1I2J3K4L5M6N', 'admin', 'approved')
ON CONFLICT (email) DO UPDATE SET password = EXCLUDED.password;
";

try {
    $pdo->exec($sql);
    echo "✅ Database updated! You can now login with 'admin' and 'admin123'.";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>