<?php
// db.php - For Render PostgreSQL

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get database info from Render environment variables
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

// Use PostgreSQL connection
try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Currency configuration
    define('CURRENCY_SYMBOL', getenv('CURRENCY_SYMBOL') ?: '€');
    define('CURRENCY_CODE', getenv('CURRENCY_CODE') ?: 'EUR');
    define('CURRENCY_NAME', 'Euro');
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>