<?php
// setup_db.php - Run this once to create tables
require 'db.php';

$sql = "
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) DEFAULT 'user',
    status VARCHAR(20) DEFAULT 'pending',
    is_frozen INTEGER DEFAULT 0,
    transaction_pin VARCHAR(255),
    profile_pic VARCHAR(255),
    cover_pic VARCHAR(255),
    two_factor_enabled INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS accounts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    account_type VARCHAR(50) DEFAULT 'Savings',
    balance DECIMAL(15,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'EUR',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transactions (
    id SERIAL PRIMARY KEY,
    sender_account_id INTEGER REFERENCES accounts(id),
    receiver_account_id INTEGER REFERENCES accounts(id),
    amount DECIMAL(15,2) NOT NULL,
    type VARCHAR(20) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    is_read INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cards (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    card_name VARCHAR(100) NOT NULL,
    card_type VARCHAR(20) DEFAULT 'visa',
    card_number VARCHAR(16) NOT NULL,
    masked_number VARCHAR(20) NOT NULL,
    expiry_month VARCHAR(2) NOT NULL,
    expiry_year VARCHAR(2) NOT NULL,
    cvv VARCHAR(3) NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0,
    is_default INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS deposit_requests (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    amount DECIMAL(15,2) NOT NULL,
    reason TEXT,
    payment_method VARCHAR(50),
    receipt_path VARCHAR(255),
    status VARCHAR(20) DEFAULT 'pending',
    admin_notes TEXT,
    requested_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_date TIMESTAMP
);

CREATE TABLE IF NOT EXISTS loan_applications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    amount DECIMAL(15,2) NOT NULL,
    loan_type VARCHAR(50),
    tenure INTEGER,
    emi DECIMAL(15,2),
    status VARCHAR(20) DEFAULT 'pending',
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_date TIMESTAMP
);

CREATE TABLE IF NOT EXISTS linked_bank_accounts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    bank_name VARCHAR(100) NOT NULL,
    account_holder VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    ifsc_code VARCHAR(20),
    is_default INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert admin user (password: admin123)
INSERT INTO users (full_name, username, email, password, role, status) 
VALUES ('System Admin', 'admin', 'admin@barclays.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved')
ON CONFLICT (email) DO NOTHING;

-- Insert account for admin
INSERT INTO accounts (user_id, account_number, balance, currency) 
SELECT id, 'EUR202400001234', 10000.00, 'EUR'
FROM users WHERE email = 'admin@barclays.com'
ON CONFLICT DO NOTHING;

echo "✅ Database setup complete! Tables created successfully.";
?>
 