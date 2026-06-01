<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Get user info
$stmt = $pdo->prepare("SELECT u.full_name, u.email, u.phone, a.balance, a.account_number FROM users u JOIN accounts a ON u.id = a.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!isset($user['phone'])) $user['phone'] = '';

// No loan limit - user can choose any amount
$max_loan = 10000000; // No practical limit (10 million)

// Currency rates (for display) - Including PHP
$currency_rates = [
    'USD' => 1.00,      // US Dollar
    'EUR' => 0.92,      // Euro
    'GBP' => 0.79,      // British Pound
    'NPR' => 133.50,    // Nepalese Rupee
    'INR' => 83.50,     // Indian Rupee
    'AUD' => 1.53,      // Australian Dollar
    'CAD' => 1.36,      // Canadian Dollar
    'JPY' => 150.25,    // Japanese Yen
    'PHP' => 56.50      // Philippine Peso
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply_loan'])) {
    $amount = floatval($_POST['amount']);
    $currency = $_POST['currency'];
    $loan_type = $_POST['loan_type'];
    $purpose = trim($_POST['purpose']);
    $tenure = intval($_POST['tenure']);
    $employment_status = $_POST['employment_status'];
    $monthly_income = floatval($_POST['monthly_income']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $existing_loans = $_POST['existing_loans'] ?? 'no';
    $existing_loan_amount = isset($_POST['existing_loan_amount']) ? floatval($_POST['existing_loan_amount']) : 0;
    $employer_name = trim($_POST['employer_name']);
    $work_experience = intval($_POST['work_experience']);
    
    // Document uploads
    $photo_path = null;
    $id_front_path = null;
    $id_back_path = null;
    $id_type = $_POST['id_type'] ?? 'citizenship';
    
    // Upload Passport Size Photo (max 10MB)
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = 'uploads/loan_documents/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['photo']['size'] <= 10 * 1024 * 1024) {
            $photo_path = $upload_dir . uniqid() . '_photo.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
        } elseif ($_FILES['photo']['size'] > 10 * 1024 * 1024) {
            $error = "Photo file size exceeds 10MB limit!";
        }
    }
    
    // Upload ID Front (max 10MB)
    if (isset($_FILES['id_front']) && $_FILES['id_front']['error'] == 0) {
        $upload_dir = 'uploads/loan_documents/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES['id_front']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['id_front']['size'] <= 10 * 1024 * 1024) {
            $id_front_path = $upload_dir . uniqid() . '_id_front.' . $ext;
            move_uploaded_file($_FILES['id_front']['tmp_name'], $id_front_path);
        } elseif ($_FILES['id_front']['size'] > 10 * 1024 * 1024) {
            $error = "ID Front file size exceeds 10MB limit!";
        }
    }
    
    // Upload ID Back (max 10MB)
    if (isset($_FILES['id_back']) && $_FILES['id_back']['error'] == 0) {
        $upload_dir = 'uploads/loan_documents/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES['id_back']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['id_back']['size'] <= 10 * 1024 * 1024) {
            $id_back_path = $upload_dir . uniqid() . '_id_back.' . $ext;
            move_uploaded_file($_FILES['id_back']['tmp_name'], $id_back_path);
        } elseif ($_FILES['id_back']['size'] > 10 * 1024 * 1024) {
            $error = "ID Back file size exceeds 10MB limit!";
        }
    }
    
    if ($amount <= 0) {
        $error = "Please enter a valid loan amount.";
    } elseif (strlen($purpose) < 10) {
        $error = "Please provide a detailed purpose (minimum 10 characters).";
    } elseif (empty($phone)) {
        $error = "Please enter your phone number with country code.";
    } elseif (empty($address)) {
        $error = "Please enter your address.";
    } elseif (!$photo_path) {
        $error = "Please upload your passport size photo.";
    } elseif (!$id_front_path) {
        $error = "Please upload the front side of your ID document.";
    } else {
        // Interest rate: 0.5% for all customers
        $interest_rate = 0.5;
        
        // Convert amount to USD for calculation if needed
        $amount_usd = $amount / $currency_rates[$currency];
        
        // Calculate EMI in USD then convert back to selected currency
        $monthly_rate = $interest_rate / 12 / 100;
        $emi_usd = $amount_usd * $monthly_rate * pow(1 + $monthly_rate, $tenure) / (pow(1 + $monthly_rate, $tenure) - 1);
        $emi = $emi_usd * $currency_rates[$currency];
        
        // Update user phone
        if (!empty($phone) && $phone != $user['phone']) {
            $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?")->execute([$phone, $user_id]);
        }
        
        // Insert loan application
        $stmt = $pdo->prepare("INSERT INTO loan_applications (user_id, amount, currency, loan_type, purpose, tenure, interest_rate, emi, employment_status, monthly_income, phone, address, existing_loans, existing_loan_amount, employer_name, work_experience, id_type, photo_path, id_front_path, id_back_path, status, applied_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$user_id, $amount, $currency, $loan_type, $purpose, $tenure, $interest_rate, $emi, $employment_status, $monthly_income, $phone, $address, $existing_loans, $existing_loan_amount, $employer_name, $work_experience, $id_type, $photo_path, $id_front_path, $id_back_path]);
        
        // Notification for user
        $stmt2 = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, 'loan', 'Loan Application Submitted', CONCAT('Your loan application for ', ?, ' ', ?, ' has been submitted and is under review.'), NOW())");
        $stmt2->execute([$user_id, number_format($amount, 2), $currency]);
        
        // Notification for admin
        $stmt3 = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (1, 'loan', 'New Loan Application', CONCAT('User ', ?, ' applied for a loan of ', ?, ' ', ?), NOW())");
        $stmt3->execute([$user['full_name'], number_format($amount, 2), $currency]);
        
        $message = "✅ Loan application submitted successfully! You will receive a decision within 2-3 business days.";
    }
}

$stmt = $pdo->prepare("SELECT * FROM loan_applications WHERE user_id = ? ORDER BY applied_date DESC");
$stmt->execute([$user_id]);
$loan_applications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Loan - Barclays Banking</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            flex-wrap: wrap;
            gap: 15px;
        }
        .balance { background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 50px; }
        .balance span { font-size: 1.5rem; font-weight: 700; margin-left: 10px; }
        .back-btn {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 30px;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
        }
        .eligibility-card {
            background: linear-gradient(135deg, #0f5c8c, #0a3d5e);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .eligibility-amount { font-size: 2rem; font-weight: 700; color: #d4af37; }
        .card-header { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; }
        .card-header .icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #0f5c8c, #d4af37);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
        }
        .card-header h2 { font-size: 1.8rem; color: #333; }
        .card-header p { color: #666; margin-top: 5px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
        }
        .radio-group { display: flex; gap: 20px; margin-top: 5px; flex-wrap: wrap; }
        .radio-group label { display: flex; align-items: center; gap: 8px; font-weight: normal; cursor: pointer; }
        .conditional-field { display: none; margin-top: 15px; padding: 15px; background: #f8fafc; border-radius: 10px; }
        .file-upload {
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            background: #f8fafc;
        }
        .file-upload:hover { border-color: #0f5c8c; background: #e6f0f7; }
        .file-upload i { font-size: 2rem; color: #0f5c8c; margin-bottom: 10px; }
        .file-upload p { color: #64748b; font-size: 0.85rem; }
        .file-name { margin-top: 5px; font-size: 0.8rem; color: #10b981; display: none; }
        .btn-primary {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #0f5c8c, #0a3d5e);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #dcfce7; color: #16a34a; }
        .alert-error { background: #fee2e2; color: #dc2626; }
        .interest-badge { background: #d4af37; color: #0f172a; padding: 8px 15px; border-radius: 30px; display: inline-block; font-weight: 700; }
        .phone-hint { font-size: 0.75rem; color: #64748b; margin-top: 5px; display: flex; align-items: center; gap: 5px; }
        .emi-display { background: #e6f0f7; padding: 15px; border-radius: 10px; margin-top: 20px; text-align: center; }
        .emi-display .amount { font-size: 1.8rem; font-weight: 700; color: #0f5c8c; }
        .loans-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 30px;
        }
        .loan-item {
            background: #f8fafc;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
        }
        .loan-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 10px; }
        .loan-amount { font-size: 1.5rem; font-weight: 700; color: #0f5c8c; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-pending { background: #fef08a; color: #854d0e; }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .eligibility-card { flex-direction: column; text-align: center; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <h2><i class="fas fa-hand-holding-usd"></i> Loan Application</h2>
            <div class="balance">Your Balance: <span>$<?php echo number_format($user['balance'], 2); ?></span></div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="eligibility-card">
            <div><i class="fas fa-star" style="color: #d4af37;"></i> <strong>Special Offer!</strong><p>0.5% Interest Rate for ALL customers</p></div>
            <div><div class="eligibility-amount">Unlimited</div><small>No maximum loan limit</small></div>
            <div><span class="interest-badge"><i class="fas fa-percent"></i> 0.5% p.a.</span></div>
        </div>

        <div class="glass-card">
            <div class="card-header">
                <div class="icon"><i class="fas fa-file-alt"></i></div>
                <div><h2>Apply for a Loan</h2><p>Fill out the form to request a loan at 0.5% interest</p></div>
            </div>

            <form method="POST" enctype="multipart/form-data" id="loanForm">
                <input type="hidden" name="apply_loan" value="1">
                
                <!-- Personal Information -->
                <div class="form-grid">
                    <div class="form-group"><label>Full Name</label><input type="text" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly></div>
                    <div class="form-group"><label>Email</label><input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly></div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="+1 234 567 8900" required>
                        <div class="phone-hint">
                            <i class="fas fa-info-circle"></i> 
                            <span>Enter with country code (e.g., +1 for USA, +44 for UK, +977 for Nepal, +63 for Philippines)</span>
                        </div>
                    </div>
                    <div class="form-group"><label>Address *</label><input type="text" name="address" placeholder="Full address" required></div>
                </div>

                <!-- Loan Details -->
                <div class="form-grid">
                    <div class="form-group"><label>Loan Amount *</label><input type="number" name="amount" id="loanAmount" min="100" step="1000" placeholder="Enter any amount" required oninput="calculateEMI()"></div>
                    <div class="form-group"><label>Currency *</label><select name="currency" id="currency" onchange="calculateEMI()">
                        <option value="USD">💵 USD - US Dollar</option>
                        <option value="EUR">💶 EUR - Euro</option>
                        <option value="GBP">💷 GBP - British Pound</option>
                        <option value="NPR">🇳🇵 NPR - Nepalese Rupee</option>
                        <option value="INR">🇮🇳 INR - Indian Rupee</option>
                        <option value="AUD">🇦🇺 AUD - Australian Dollar</option>
                        <option value="CAD">🇨🇦 CAD - Canadian Dollar</option>
                        <option value="JPY">🇯🇵 JPY - Japanese Yen</option>
                        <option value="PHP">🇵🇭 PHP - Philippine Peso</option>
                    </select></div>
                    <div class="form-group"><label>Loan Type</label><select name="loan_type" id="loanType">
                        <option value="personal">💼 Personal Loan</option>
                        <option value="home">🏠 Home Loan</option>
                        <option value="education">📚 Education Loan</option>
                        <option value="business">🏢 Business Loan</option>
                        <option value="car">🚗 Car Loan</option>
                    </select></div>
                    <div class="form-group"><label>Tenure (Months)</label><select name="tenure" id="tenure" onchange="calculateEMI()">
                        <option value="12">12 months (1 year)</option>
                        <option value="24">24 months (2 years)</option>
                        <option value="36">36 months (3 years)</option>
                        <option value="48">48 months (4 years)</option>
                        <option value="60">60 months (5 years)</option>
                        <option value="120">120 months (10 years)</option>
                        <option value="180">180 months (15 years)</option>
                        <option value="240">240 months (20 years)</option>
                    </select></div>
                    <div class="form-group"><label>Loan Purpose *</label><textarea name="purpose" rows="3" placeholder="Describe the purpose of this loan in detail..." required></textarea></div>
                </div>

                <!-- Employment & Income -->
                <div class="form-grid">
                    <div class="form-group"><label>Employment Status</label><select name="employment_status" id="employmentStatus">
                        <option value="salaried">Salaried Employee</option>
                        <option value="self_employed">Self Employed</option>
                        <option value="business">Business Owner</option>
                        <option value="professional">Professional</option>
                        <option value="student">Student</option>
                        <option value="retired">Retired</option>
                    </select></div>
                    <div class="form-group"><label>Monthly Income ($)</label><input type="number" name="monthly_income" id="monthlyIncome" step="1000" placeholder="Enter monthly income" required oninput="calculateEMI()"></div>
                    <div class="form-group" id="employerGroup"><label>Employer/Business Name</label><input type="text" name="employer_name" placeholder="Employer or business name"></div>
                    <div class="form-group"><label>Work Experience (Years)</label><input type="number" name="work_experience" min="0" max="50" step="1" placeholder="Years of experience" value="0"></div>
                </div>

                <!-- Existing Loans -->
                <div class="form-group"><label>Do you have any existing loans?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="existing_loans" value="no" checked onchange="toggleExistingLoans()"> No</label>
                        <label><input type="radio" name="existing_loans" value="yes" onchange="toggleExistingLoans()"> Yes</label>
                    </div>
                </div>
                <div id="existingLoansField" class="conditional-field">
                    <div class="form-group"><label>Total Outstanding Loan Amount ($)</label><input type="number" name="existing_loan_amount" step="1000" placeholder="Enter total outstanding amount"></div>
                </div>

                <!-- ID Proof Section -->
                <h3 style="margin: 20px 0 15px; color: #333;"><i class="fas fa-id-card"></i> Identity Verification</h3>
                <div class="form-grid">
                    <div class="form-group"><label>ID Type *</label><select name="id_type" required>
                        <option value="citizenship">Citizenship</option>
                        <option value="citizenship">License</option>
                        <option value="citizenship">Passport</option>


                    </select></div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Passport Size Photo * (Max 10MB)</label>
                        <div class="file-upload" onclick="document.getElementById('photo').click()">
                            <i class="fas fa-camera"></i>
                            <p>Click to upload photo</p>
                            <small>JPG, PNG (Max 10MB)</small>
                        </div>
                        <input type="file" id="photo" name="photo" accept="image/*" style="display: none;" required onchange="updateFileName(this, 'photo-name')">
                        <div id="photo-name" class="file-name"></div>
                    </div>
                    <div class="form-group">
                        <label>ID Document (Front Side) * (Max 10MB)</label>
                        <div class="file-upload" onclick="document.getElementById('id_front').click()">
                            <i class="fas fa-id-card"></i>
                            <p>Click to upload front side</p>
                            <small>JPG, PNG, PDF (Max 10MB)</small>
                        </div>
                        <input type="file" id="id_front" name="id_front" accept="image/*,application/pdf" style="display: none;" required onchange="updateFileName(this, 'front-name')">
                        <div id="front-name" class="file-name"></div>
                    </div>
                    <div class="form-group">
                        <label>ID Document (Back Side) * (Max 10MB)</label>
                        <div class="file-upload" onclick="document.getElementById('id_back').click()">
                            <i class="fas fa-id-card"></i>
                            <p>Click to upload back side</p>
                            <small>JPG, PNG, PDF (Max 10MB)</small>
                        </div>
                        <input type="file" id="id_back" name="id_back" accept="image/*,application/pdf" style="display: none;" required onchange="updateFileName(this, 'back-name')">
                        <div id="back-name" class="file-name"></div>
                    </div>
                </div>

                <!-- EMI Display -->
                <div class="emi-display">
                    <i class="fas fa-calculator"></i>
                    <p>Estimated Monthly EMI at <strong>0.5% interest</strong></p>
                    <div class="amount" id="emiDisplay">$0.00</div>
                    <small>Interest rate: 0.5% per annum (fixed for all customers)</small>
                </div>

                <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Submit Loan Application</button>
            </form>
        </div>

        <!-- My Loan Applications -->
        <div class="loans-section">
            <h3><i class="fas fa-history"></i> My Loan Applications</h3>
            <?php if (empty($loan_applications)): ?>
                <div style="text-align: center; padding: 40px; color: #64748b;"><i class="fas fa-file-invoice" style="font-size: 3rem; margin-bottom: 15px;"></i><p>No loan applications yet</p></div>
            <?php else: ?>
                <?php foreach ($loan_applications as $loan): ?>
                <div class="loan-item">
                    <div class="loan-header">
                        <div class="loan-amount"><?php echo number_format($loan['amount'], 2); ?> <?php echo $loan['currency']; ?></div>
                        <div class="status-badge status-<?php echo $loan['status']; ?>"><i class="fas <?php echo $loan['status'] == 'pending' ? 'fa-clock' : ($loan['status'] == 'approved' ? 'fa-check-circle' : 'fa-times-circle'); ?>"></i> <?php echo ucfirst($loan['status']); ?></div>
                    </div>
                    <div class="loan-details">
                        <span><i class="fas fa-tag"></i> <?php echo ucfirst($loan['loan_type']); ?> Loan</span>
                        <span><i class="fas fa-calendar"></i> Applied: <?php echo date('M d, Y', strtotime($loan['applied_date'])); ?></span>
                        <span><i class="fas fa-chart-line"></i> Tenure: <?php echo $loan['tenure']; ?> months</span>
                        <span><i class="fas fa-percent"></i> Rate: <?php echo $loan['interest_rate']; ?>%</span>
                    </div>
                    <div class="emi-display" style="margin-top: 10px; padding: 10px;"><i class="fas fa-calculator"></i> EMI: <?php echo number_format($loan['emi'], 2); ?> <?php echo $loan['currency']; ?>/month</div>
                    <?php if ($loan['status'] == 'approved'): ?>
                        <div style="margin-top: 15px; padding: 10px; background: #dcfce7; border-radius: 8px; color: #166534;"><i class="fas fa-check-circle"></i> ✅ Congratulations! Your loan has been approved.</div>
                    <?php elseif ($loan['status'] == 'rejected'): ?>
                        <div style="margin-top: 15px; padding: 10px; background: #fee2e2; border-radius: 8px; color: #991b1b;"><i class="fas fa-times-circle"></i> ❌ Your loan application was not approved.</div>
                    <?php else: ?>
                        <div style="margin-top: 15px; padding: 10px; background: #fef08a; border-radius: 8px; color: #854d0e;"><i class="fas fa-clock"></i> ⏳ Your application is under review.</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateFileName(input, displayId) {
            if (input.files && input.files[0]) {
                const fileSize = input.files[0].size / (1024 * 1024);
                if (fileSize > 10) {
                    alert('File size exceeds 10MB limit! Please choose a smaller file.');
                    input.value = '';
                    return;
                }
                document.getElementById(displayId).innerHTML = '<i class="fas fa-check-circle"></i> ' + input.files[0].name + ' (' + fileSize.toFixed(2) + ' MB)';
                document.getElementById(displayId).style.display = 'block';
            }
        }

        function toggleExistingLoans() {
            const field = document.getElementById('existingLoansField');
            const selected = document.querySelector('input[name="existing_loans"]:checked').value;
            field.style.display = selected === 'yes' ? 'block' : 'none';
        }

        function toggleEmployerField() {
            const status = document.getElementById('employmentStatus').value;
            const employerGroup = document.getElementById('employerGroup');
            employerGroup.style.display = (status === 'salaried' || status === 'self_employed' || status === 'business') ? 'block' : 'none';
        }

        const currencyRates = {
            'USD': 1.00, 'EUR': 0.92, 'GBP': 0.79, 'NPR': 133.50,
            'INR': 83.50, 'AUD': 1.53, 'CAD': 1.36, 'JPY': 150.25,
            'PHP': 56.50
        };

        function calculateEMI() {
            const amount = parseFloat(document.getElementById('loanAmount').value) || 0;
            const tenure = parseInt(document.getElementById('tenure').value) || 12;
            const currency = document.getElementById('currency').value;
            const rate = 0.5; // 0.5% interest rate
            
            const monthlyRate = rate / 12 / 100;
            const emi = amount * monthlyRate * Math.pow(1 + monthlyRate, tenure) / (Math.pow(1 + monthlyRate, tenure) - 1);
            
            document.getElementById('emiDisplay').innerHTML = (isNaN(emi) ? '0.00' : emi.toFixed(2)) + ' ' + currency;
        }

        document.getElementById('loanAmount').addEventListener('input', calculateEMI);
        document.getElementById('tenure').addEventListener('change', calculateEMI);
        document.getElementById('currency').addEventListener('change', calculateEMI);
        
        toggleExistingLoans();
        toggleEmployerField();
    </script>
</body>
</html>