<?php
// config.php - Central configuration file

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once 'db.php';

// Load FPDF only once
if (!class_exists('FPDF')) {
    require_once 'fpdf/fpdf.php';
}
?>