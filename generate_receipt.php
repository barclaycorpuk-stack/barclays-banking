<?php
// generate_receipt.php - Modern Receipt Generator
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$transaction_id = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
$amount = isset($_GET['amt']) ? floatval($_GET['amt']) : 0;
$receiver = isset($_GET['rec']) ? urldecode($_GET['rec']) : 'Unknown';
$account = isset($_GET['acc']) ? $_GET['acc'] : 'Unknown';
$bank = isset($_GET['bank']) ? urldecode($_GET['bank']) : 'Global IME Bank';
$date = isset($_GET['dt']) ? urldecode($_GET['dt']) : date('M d, Y h:i A');

// Get sender name from database - Set to AG Recruitment
$sender_name = 'AG Recruitment'; // Fixed as requested

// Get sender account from database (optional)
$stmt = $pdo->prepare("SELECT account_number FROM accounts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$sender_account = $stmt->fetchColumn();
if (!$sender_account) {
    $sender_account = '2024-5678-9012-3456'; // Default if not found
}

try {
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Modern gradient header
    $pdf->SetFillColor(15, 92, 140); // Barclays blue
    $pdf->Rect(0, 0, 210, 40, 'F');
    
    // Logo/Icon
    $pdf->SetFont('ZapfDingbats', '', 30);
    $pdf->SetTextColor(212, 175, 55); // Gold
    $pdf->SetXY(15, 10);
    $pdf->Cell(10, 10, '4', 0, 1); // Checkmark icon
    
    // Bank Name
    $pdf->SetFont('Arial', 'B', 24);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY(35, 12);
    $pdf->Cell(0, 10, 'BARCLAYS BANKING', 0, 1);
    
    // Receipt Title with background
    $pdf->SetY(50);
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetTextColor(15, 92, 140);
    $pdf->Cell(0, 15, 'PAYMENT RECEIPT', 0, 1, 'C');
    
    // Success Message with icon
    $pdf->SetY(70);
    $pdf->SetFont('ZapfDingbats', '', 25);
    $pdf->SetTextColor(16, 185, 129);
    $pdf->Cell(0, 10, '4', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(16, 185, 129);
    $pdf->Cell(0, 10, 'Payment Successful', 0, 1, 'C');
    
    // Transaction ID Box
    $pdf->SetY(95);
    $pdf->SetFillColor(240, 245, 255);
    $pdf->SetTextColor(15, 92, 140);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(45, 10, 'Transaction ID:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, 'TXN' . str_pad($transaction_id, 8, '0', STR_PAD_LEFT), 0, 1);
    
    // Date
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(15, 92, 140);
    $pdf->Cell(45, 10, 'Date & Time:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 10, $date, 0, 1);
    
    // Divider Line
    $pdf->SetY(125);
    $pdf->SetDrawColor(212, 175, 55);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
    
    // Transaction Details Header
    $pdf->SetY(135);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetTextColor(15, 92, 140);
    $pdf->Cell(0, 10, 'Transaction Details', 0, 1, 'L');
    
    // From Section
    $pdf->SetY(150);
    $pdf->SetFillColor(245, 250, 255);
    $pdf->Rect(20, $pdf->GetY(), 170, 35, 'F');
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(15, 92, 140);
    $pdf->Cell(40, 8, 'FROM:', 0, 0);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 8, $sender_name, 0, 1);
    
    // Sender Account - Full number
    $pdf->SetX(60);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 6, 'Account: ' . $sender_account, 0, 1);
    
    $pdf->SetX(60);
    $pdf->Cell(0, 6, 'Bank: Barclays Bank PLC', 0, 1);
    
    // To Section
    $pdf->SetY(190);
    $pdf->SetFillColor(245, 250, 255);
    $pdf->Rect(20, $pdf->GetY(), 170, 45, 'F');
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(15, 92, 140);
    $pdf->Cell(40, 8, 'TO:', 0, 0);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 8, $receiver, 0, 1);
    
    // Receiver Details - Show Full Account Number (NOT masked)
    $pdf->SetX(60);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 6, 'Account Number: ' . $account, 0, 1); // Full account number
    
    $pdf->SetX(60);
    $pdf->Cell(0, 6, 'Bank: ' . $bank, 0, 1);
    
    $pdf->SetX(60);
    $pdf->Cell(0, 6, 'IFSC Code: ' . substr($account, 0, 4) . 'B' . substr($account, -3), 0, 1);
    
    // Amount Box
    $pdf->SetY(245);
    $pdf->SetFillColor(15, 92, 140);
    $pdf->Rect(20, $pdf->GetY(), 170, 35, 'F');
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(50, 10, 'Amount Paid:', 0, 0);
    $pdf->SetFont('Arial', 'B', 24);
    $pdf->SetTextColor(212, 175, 55);
    $pdf->Cell(0, 10, '$' . number_format($amount, 2), 0, 1);
    
    $pdf->SetX(70);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(200, 200, 200);
    $pdf->Cell(0, 6, '(' . ucfirst(number_format($amount, 2)) . ' US Dollars)', 0, 1);
    
    // Footer
    $pdf->SetY(290);
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 5, 'Thank you for banking with Barclays', 0, 1, 'C');
    $pdf->Cell(0, 5, 'This is an electronically generated receipt', 0, 1, 'C');
    
    // Time stamp
    $pdf->SetY(280);
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetTextColor(180, 180, 180);
    $pdf->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
    
    // Output PDF
    $pdf->Output('D', 'receipt_TXN' . str_pad($transaction_id, 8, '0', STR_PAD_LEFT) . '.pdf');
    exit;
    
} catch (Exception $e) {
    die("PDF Generation Error: " . $e->getMessage());
}
?>