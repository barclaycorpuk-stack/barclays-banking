<?php
// db.php - Safe database configuration for Render PostgreSQL
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$dbname = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Absolute fallback: These will ALWAYS define safely now
    if (!defined('CURRENCY_SYMBOL')) {
        define('CURRENCY_SYMBOL', getenv('CURRENCY_SYMBOL') ?: '€');
    }
    if (!defined('CURRENCY_CODE')) {
        define('CURRENCY_CODE', getenv('CURRENCY_CODE') ?: 'EUR');
    }
    if (!defined('CURRENCY_NAME')) {
        define('CURRENCY_NAME', 'Euro');
    }
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>