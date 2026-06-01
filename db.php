<?php
// db.php - Database configuration with currency settings

// Only start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'simple_bank';  // Your database name
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Currency configuration - Now set to EURO
    define('CURRENCY_SYMBOL', '€');
    define('CURRENCY_CODE', 'EUR');
    define('CURRENCY_NAME', 'Euro');
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>