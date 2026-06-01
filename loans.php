<?php
// loans.php - Public Loan Information Page
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loans - Barclays Banking</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }

        :root {
            --chase-blue: #0f5c8c;
            --chase-dark-blue: #0a3d5e;
            --chase-gold: #d4af37;
            --chase-success: #10b981;
            --chase-danger: #ef4444;
            --chase-warning: #f59e0b;
        }

        /* Top Bar */
        .top-bar {
            background: #0f172a;
            color: white;
            padding: 8px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            border-bottom: 3px solid var(--chase-gold);
        }

        .top-bar-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .top-bar-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: 0.3s;
        }

        .top-bar-links a:hover {
            color: var(--chase-gold);
        }

        /* Main Navigation */
        .main-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--chase-blue);
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo i {
            color: var(--chase-gold);
        }

        .nav-menu {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-menu a {
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: 0.3s;
        }

        .nav-menu a:hover {
            color: var(--chase-blue);
        }

        .nav-menu a.active {
            color: var(--chase-blue);
            font-weight: 600;
            border-bottom: 2px solid var(--chase-gold);
            padding-bottom: 5px;
        }

        .nav-buttons {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-outline {
            border: 2px solid var(--chase-blue);
            color: var(--chase-blue);
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-outline:hover {
            background: var(--chase-blue);
            color: white;
        }

        .btn-primary {
            background: var(--chase-blue);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background: var(--chase-dark-blue);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(15, 92, 140, 0.3);
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--chase-blue), var(--chase-dark-blue));
            color: white;
            padding: 60px 5%;
            text-align: center;
        }

        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Loan Products Grid */
        .loan-products {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 60px;
        }

        .loan-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: 0.3s;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .loan-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(15, 92, 140, 0.15);
        }

        .loan-card.featured {
            border: 2px solid var(--chase-gold);
            transform: scale(1.05);
        }

        .loan-card.featured::before {
            content: 'BEST VALUE';
            position: absolute;
            top: 20px;
            right: -30px;
            background: var(--chase-gold);
            color: #0f172a;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-size: 0.7rem;
            font-weight: bold;
        }

        .loan-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--chase-blue), var(--chase-gold));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 25px;
        }

        .loan-card h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: #0f172a;
        }

        .loan-rate {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--chase-blue);
            margin-bottom: 10px;
        }

        .loan-rate small {
            font-size: 1rem;
            color: #64748b;
        }

        .loan-term {
            color: #64748b;
            margin-bottom: 25px;
            font-size: 1.1rem;
        }

        .loan-features {
            text-align: left;
            margin: 30px 0;
        }

        .loan-feature {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .loan-feature:last-child {
            border-bottom: none;
        }

        .loan-feature i {
            color: var(--chase-gold);
            width: 20px;
            font-size: 1.1rem;
        }

        .loan-feature span {
            color: #475569;
        }

        .btn-learn {
            width: 100%;
            padding: 15px;
            background: var(--chase-blue);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 20px;
        }

        .btn-learn:hover {
            background: var(--chase-dark-blue);
        }

        /* EMI Calculator Section */
        .calculator-section {
            background: white;
            border-radius: 30px;
            padding: 50px;
            margin-bottom: 60px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        }

        .calculator-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
        }

        .calculator-content h2 {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #0f172a;
        }

        .calculator-content p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .calculator-form {
            background: #f8fafc;
            padding: 30px;
            border-radius: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--chase-blue);
        }

        .emi-result {
            text-align: center;
            padding: 25px;
            background: var(--chase-blue);
            color: white;
            border-radius: 15px;
            margin-top: 20px;
        }

        .emi-amount {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .emi-breakdown {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        /* Why Choose Us */
        .features-section {
            margin-bottom: 60px;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 40px;
            color: #0f172a;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
        }

        .feature-item {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .feature-item i {
            font-size: 2.5rem;
            color: var(--chase-blue);
            margin-bottom: 15px;
        }

        .feature-item h4 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .feature-item p {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* FAQ Section */
        .faq-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
        }

        .faq-item {
            border-bottom: 1px solid #e2e8f0;
        }

        .faq-question {
            padding: 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .faq-question h4 {
            font-size: 1.1rem;
            color: #0f172a;
        }

        .faq-answer {
            display: none;
            padding-bottom: 20px;
            color: #64748b;
            line-height: 1.6;
        }

        .faq-item.active .faq-answer {
            display: block;
        }

        .faq-item.active .fa-chevron-down {
            transform: rotate(180deg);
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--chase-blue), var(--chase-dark-blue));
            color: white;
            padding: 60px;
            border-radius: 30px;
            text-align: center;
            margin-top: 60px;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn-white {
            background: white;
            color: var(--chase-blue);
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-white:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .btn-outline-white {
            border: 2px solid white;
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-outline-white:hover {
            background: white;
            color: var(--chase-blue);
        }

        /* Footer */
        footer {
            background: #0f172a;
            color: white;
            padding: 60px 5% 20px;
            margin-top: 60px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto 40px;
        }

        .footer-col h4 {
            color: var(--chase-gold);
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .footer-col a {
            color: #94a3b8;
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            transition: 0.3s;
        }

        .footer-col a:hover {
            color: white;
            padding-left: 5px;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #64748b;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .loan-products,
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .calculator-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-nav {
                flex-direction: column;
                gap: 20px;
            }
            
            .nav-menu {
                flex-direction: column;
                width: 100%;
            }
            
            .loan-products,
            .features-grid {
                grid-template-columns: 1fr;
            }

            .loan-card.featured {
                transform: none;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div>FDIC Insured | Equal Housing Lender</div>
        <div class="top-bar-links">
            <a href="#">Locations</a>
            <a href="#">Contact</a>
            <a href="#">Help</a>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="main-nav">
        <a href="index.php" class="logo">
            <i class="fas fa-university"></i>
            BARCLAYS
        </a>
        <div class="nav-menu">
            <a href="index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="how-it-works.php">How It Works</a>
            <a href="loans.php">Loans</a>
            <a href="contact.php" class="active">Contact</a>
        </div>
        <div class="nav-buttons">
            <a href="login.php" class="btn-outline">Sign In</a>
            <a href="register.php" class="btn-primary">Open Account</a>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <h1>Loans for Every Need</h1>
        <p>Flexible financing solutions tailored to your life goals</p>
    </section>

    <!-- Main Content -->
    <div class="container">
        <!-- Loan Products -->
        <div class="loan-products">
            <!-- Personal Loan -->
            <div class="loan-card">
                <div class="loan-icon"><i class="fas fa-user"></i></div>
                <h3>Personal Loan</h3>
                <div class="loan-rate">8.5% <small>p.a.</small></div>
                <div class="loan-term">1 - 5 years tenure</div>
                <div class="loan-features">
                    <div class="loan-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Amount: $1,000 - $50,000</span>
                    </div>
                    <div class="loan-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>No collateral required</span>
                    </div>
                    <div class="loan-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Instant approval</span>
                    </div>
                    <div class="loan-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Flexible repayment options</span>
                    </div>
                </div>
                <button class="btn-learn" onclick="document.getElementById('calculator').scrollIntoView({behavior: 'smooth'})">
                    Calculate EMI
                </button>
            </div>

            <!-- Home Loan - Featured -->
            <div class="loan-card featured">
                <div class="loan-icon"><i class="fas fa-home"></i></div>
                <h3>Home Loan</h3>
                <div class="loan-rate">6.75% <small>p.a.</small></div>
                <div class="loan-term">5 - 30 years tenure</div>
                <div class="loan-features">
                    <div class="loan-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Amount: Up to $500,000</span>
                    </div>
                    <div class="loan-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Low interest rates</span>
                    </div>
                    <div class="loan-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Tax benefits available</span>
                    </div>
                    <div class="loan-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Up to 90% financing</span>
                    </div>
                </div>
                <button class="btn-learn" onclick="document.getElementById('calculator').scrollIntoView({behavior: 'smooth'})">
                    Calculate EMI
                </button>
            </div>

            <!-- Education Loan -->
            <div class="loan-card">
                <div class="loan-icon"><i class="fas fa-graduation-cap"></i></div>
                <h3>Education Loan</h3>
                <div class="loan-rate">7.25% <small>p.a.</small></div>
                <div class="loan-term">1 - 15 years tenure</div>
                <div class="loan-features">
                    <div class="loan-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Amount: Up to $100,000</span>
                    </div>
                    <div class="loan-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Moratorium period</span>
                    </div>
                    <div class="loan-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>No collateral up to $50k</span>
                    </div>
                    <div class="loan-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Flexible repayment</span>
                    </div>
                </div>
                <button class="btn-learn" onclick="document.getElementById('calculator').scrollIntoView({behavior: 'smooth'})">
                    Calculate EMI
                </button>
            </div>
        </div>

        <!-- EMI Calculator Section -->
        <div class="calculator-section" id="calculator">
            <div class="calculator-grid">
                <div class="calculator-content">
                    <h2>Loan EMI Calculator</h2>
                    <p>Plan your finances better with our easy-to-use EMI calculator. Know your monthly payments before you apply.</p>
                    
                    <div class="feature-item" style="text-align: left; padding: 0; background: none;">
                        <i class="fas fa-chart-pie"></i>
                        <h4>Transparent Breakdown</h4>
                        <p>See principal, interest, and total payment clearly</p>
                    </div>
                    
                    <div class="feature-item" style="text-align: left; padding: 0; background: none;">
                        <i class="fas fa-clock"></i>
                        <h4>Flexible Tenure</h4>
                        <p>Choose from 1 to 30 years based on loan type</p>
                    </div>
                </div>
                
                <div class="calculator-form">
                    <h3 style="margin-bottom: 20px;">Calculate Your EMI</h3>
                    
                    <div class="form-group">
                        <label>Loan Amount ($)</label>
                        <input type="range" id="amountRange" min="1000" max="500000" step="1000" value="50000" oninput="updateAmount(this.value)">
                        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                            <input type="number" id="amountInput" value="50000" oninput="updateRange(this.value)" style="width: 120px;">
                            <span style="color: var(--chase-blue); font-weight: 600;">$50,000</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Interest Rate (%)</label>
                        <input type="range" id="rateRange" min="5" max="15" step="0.1" value="8.5" oninput="updateRate(this.value)">
                        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                            <input type="number" id="rateInput" value="8.5" step="0.1" oninput="updateRateInput(this.value)" style="width: 80px;">
                            <span style="color: var(--chase-blue); font-weight: 600;">8.5%</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Loan Term (years)</label>
                        <select id="termSelect" onchange="calculateEMI()">
                            <option value="1">1 Year</option>
                            <option value="2">2 Years</option>
                            <option value="3">3 Years</option>
                            <option value="5">5 Years</option>
                            <option value="10">10 Years</option>
                            <option value="15">15 Years</option>
                            <option value="20" selected>20 Years</option>
                            <option value="25">25 Years</option>
                            <option value="30">30 Years</option>
                        </select>
                    </div>
                    
                    <div class="emi-result">
                        <div style="font-size: 1rem; opacity: 0.9;">Your Monthly EMI</div>
                        <div class="emi-amount" id="emiAmount">$0.00</div>
                        
                        <div class="emi-breakdown">
                            <div>
                                <div style="font-size: 0.9rem; opacity: 0.8;">Principal</div>
                                <div style="font-size: 1.2rem; font-weight: 600;" id="principalPart">$0</div>
                            </div>
                            <div>
                                <div style="font-size: 0.9rem; opacity: 0.8;">Interest</div>
                                <div style="font-size: 1.2rem; font-weight: 600;" id="interestPart">$0</div>
                            </div>
                            <div>
                                <div style="font-size: 0.9rem; opacity: 0.8;">Total</div>
                                <div style="font-size: 1.2rem; font-weight: 600;" id="totalPayment">$0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Why Choose Us -->
        <div class="features-section">
            <h2 class="section-title">Why Choose Barclays for Loans</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-bolt"></i>
                    <h4>Quick Approval</h4>
                    <p>Get approved in as little as 24 hours</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-percent"></i>
                    <h4>Lowest Rates</h4>
                    <p>Competitive interest rates guaranteed</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-file-alt"></i>
                    <h4>Minimal Documentation</h4>
                    <p>Simple online application process</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-headset"></i>
                    <h4>Expert Support</h4>
                    <p>Dedicated loan officers to help you</p>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <h2 style="margin-bottom: 30px;">Frequently Asked Questions</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>What documents do I need to apply for a loan?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    You'll need: Proof of identity (Passport/Driver's License), Proof of income (pay stubs/tax returns), Bank statements (last 3 months), and Proof of address. Self-employed applicants may need additional business documents.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>How long does loan approval take?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Personal loans are typically approved within 24-48 hours. Home loans may take 3-5 business days due to property evaluation. We'll keep you updated throughout the process.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>Can I prepay my loan?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Yes, you can prepay your loan at any time with no prepayment penalties. Partial prepayments are also allowed. Use our online portal to make additional payments.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>What is the maximum loan amount I can get?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Personal loans: up to $50,000<br>
                    Home loans: up to $500,000<br>
                    Education loans: up to $100,000<br>
                    Final amount depends on your income, credit score, and repayment capacity.
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="cta-section">
            <h2>Ready to Get Started?</h2>
            <p>Join thousands of satisfied customers who achieved their dreams with Barclays loans</p>
            <div class="cta-buttons">
                <a href="register.php" class="btn-white">Open an Account</a>
                <a href="contact.php" class="btn-outline-white">Talk to an Expert</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <h4>Personal Banking</h4>
                <a href="#">Checking Accounts</a>
                <a href="#">Savings Accounts</a>
                <a href="#">Credit Cards</a>
                <a href="#">Personal Loans</a>
                <a href="#">Mortgages</a>
            </div>
            <div class="footer-col">
                <h4>Business Banking</h4>
                <a href="#">Business Checking</a>
                <a href="#">Business Savings</a>
                <a href="#">Merchant Services</a>
                <a href="#">Business Loans</a>
                <a href="#">Payroll Services</a>
            </div>
            <div class="footer-col">
                <h4>Resources</h4>
                <a href="#">Help Center</a>
                <a href="#">FAQs</a>
                <a href="#">Rates</a>
                <a href="#">Security Center</a>
                <a href="#">Privacy Policy</a>
            </div>
            <div class="footer-col">
                <h4>Connect With Us</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
                <p style="margin-top: 20px; color: #94a3b8;">
                    <i class="fas fa-phone"></i> 1-800-BARCLAYS<br>
                    <i class="fas fa-envelope"></i> support@barclays.com
                </p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 Barclays Banking. All rights reserved. | FDIC Insured | Equal Housing Lender | Loans subject to approval.</p>
        </div>
    </footer>

    <script>
        // EMI Calculator Functions
        function updateAmount(val) {
            document.getElementById('amountInput').value = val;
            document.getElementById('amountRange').value = val;
            calculateEMI();
        }

        function updateRange(val) {
            document.getElementById('amountRange').value = val;
            document.getElementById('amountInput').value = val;
            calculateEMI();
        }

        function updateRate(val) {
            document.getElementById('rateInput').value = val;
            document.getElementById('rateRange').value = val;
            calculateEMI();
        }

        function updateRateInput(val) {
            document.getElementById('rateRange').value = val;
            document.getElementById('rateInput').value = val;
            calculateEMI();
        }

        function calculateEMI() {
            const amount = parseFloat(document.getElementById('amountInput').value) || 0;
            const rate = parseFloat(document.getElementById('rateInput').value) || 0;
            const years = parseFloat(document.getElementById('termSelect').value) || 1;
            
            const months = years * 12;
            const monthlyRate = rate / 12 / 100;
            
            const emi = amount * monthlyRate * Math.pow(1 + monthlyRate, months) / (Math.pow(1 + monthlyRate, months) - 1);
            const totalPayment = emi * months;
            const totalInterest = totalPayment - amount;
            
            document.getElementById('emiAmount').innerHTML = '$' + (isNaN(emi) ? '0.00' : emi.toFixed(2));
            document.getElementById('principalPart').innerHTML = '$' + amount.toLocaleString();
            document.getElementById('interestPart').innerHTML = '$' + (isNaN(totalInterest) ? '0' : Math.round(totalInterest).toLocaleString());
            document.getElementById('totalPayment').innerHTML = '$' + (isNaN(totalPayment) ? '0' : Math.round(totalPayment).toLocaleString());
        }

        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentElement;
                item.classList.toggle('active');
            });
        });

        // Initialize calculator
        calculateEMI();
    </script>
</body>
</html>