<?php
// rates.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interest Rates - Barclays Banking</title>
    
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
            --chase-light: #f8fafc;
            --chase-gray: #64748b;
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

        .dark-mode-toggle {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            padding: 5px 15px;
            border-radius: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
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

        /* Container */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .page-header h1 {
            font-size: 3rem;
            color: #0f172a;
            margin-bottom: 15px;
        }

        .page-header p {
            color: #64748b;
            font-size: 1.2rem;
        }

        /* Rate Cards */
        .rates-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 50px;
        }

        .rate-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: 0.3s;
            position: relative;
            overflow: hidden;
        }

        .rate-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(15, 92, 140, 0.15);
        }

        .rate-card.featured {
            border: 2px solid var(--chase-gold);
            transform: scale(1.05);
        }

        .rate-card.featured::before {
            content: 'MOST POPULAR';
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

        .rate-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .rate-icon {
            width: 60px;
            height: 60px;
            background: #e6f0f7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: var(--chase-blue);
            font-size: 1.5rem;
        }

        .rate-header h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
        }

        .rate-percentage {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--chase-blue);
        }

        .rate-percentage small {
            font-size: 0.9rem;
            color: #64748b;
        }

        .rate-features {
            margin: 25px 0;
        }

        .rate-feature {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .rate-feature span:first-child {
            color: #64748b;
        }

        .rate-feature span:last-child {
            font-weight: 600;
        }

        .rate-action {
            text-align: center;
            margin-top: 20px;
        }

        .btn-rate {
            background: var(--chase-blue);
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
        }

        .btn-rate:hover {
            background: var(--chase-dark-blue);
            transform: translateY(-2px);
        }

        /* Comparison Table */
        .comparison-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-top: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .comparison-table {
            width: 100%;
            border-collapse: collapse;
        }

        .comparison-table th {
            background: #f8fafc;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #0f172a;
            border-bottom: 2px solid var(--chase-gold);
        }

        .comparison-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .comparison-table tr:hover {
            background: #f8fafc;
        }

        .check-icon {
            color: #22c55e;
        }

        .times-icon {
            color: #ef4444;
        }

        .footnote {
            margin-top: 20px;
            color: #64748b;
            font-size: 0.85rem;
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

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 0;
        }

        .social-links a:hover {
            background: var(--chase-gold);
            color: #0f172a;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .rates-grid {
                grid-template-columns: repeat(2, 1fr);
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
            
            .nav-buttons {
                width: 100%;
                justify-content: center;
            }
            
            .rates-grid {
                grid-template-columns: 1fr;
            }

            .rate-card.featured {
                transform: none;
            }

            .comparison-table {
                font-size: 0.85rem;
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
            <button class="dark-mode-toggle" onclick="toggleDarkMode()">
                <i class="fas fa-moon"></i> Dark Mode
            </button>
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
    <a href="#">Contact</a>
</div>
        <div class="nav-buttons">
            <a href="login.php" class="btn-outline">Sign In</a>
            <a href="register.php" class="btn-primary">Open Account</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h1>Interest Rates</h1>
            <p>Competitive rates designed to help your money grow</p>
        </div>

        <!-- Rate Cards -->
        <div class="rates-grid">
            <!-- Savings Account -->
            <div class="rate-card">
                <div class="rate-header">
                    <div class="rate-icon"><i class="fas fa-piggy-bank"></i></div>
                    <h3>Savings Account</h3>
                    <div class="rate-percentage">3.50% <small>APY</small></div>
                </div>
                <div class="rate-features">
                    <div class="rate-feature">
                        <span>Minimum Balance</span>
                        <span>$1,000</span>
                    </div>
                    <div class="rate-feature">
                        <span>Monthly Fee</span>
                        <span>$0</span>
                    </div>
                    <div class="rate-feature">
                        <span>Online Access</span>
                        <span><i class="fas fa-check check-icon"></i></span>
                    </div>
                    <div class="rate-feature">
                        <span>Transfer Limit</span>
                        <span>$50,000/day</span>
                    </div>
                </div>
                <div class="rate-action">
                    <a href="#" class="btn-rate">Open Account</a>
                </div>
            </div>

            <!-- Current Account -->
            <div class="rate-card featured">
                <div class="rate-header">
                    <div class="rate-icon"><i class="fas fa-wallet"></i></div>
                    <h3>Current Account</h3>
                    <div class="rate-percentage">1.25% <small>APY</small></div>
                </div>
                <div class="rate-features">
                    <div class="rate-feature">
                        <span>Minimum Balance</span>
                        <span>$500</span>
                    </div>
                    <div class="rate-feature">
                        <span>Monthly Fee</span>
                        <span>$5</span>
                    </div>
                    <div class="rate-feature">
                        <span>Online Access</span>
                        <span><i class="fas fa-check check-icon"></i></span>
                    </div>
                    <div class="rate-feature">
                        <span>Transfer Limit</span>
                        <span>$100,000/day</span>
                    </div>
                </div>
                <div class="rate-action">
                    <a href="#" class="btn-rate">Open Account</a>
                </div>
            </div>

            <!-- Business Account -->
            <div class="rate-card">
                <div class="rate-header">
                    <div class="rate-icon"><i class="fas fa-briefcase"></i></div>
                    <h3>Business Account</h3>
                    <div class="rate-percentage">2.75% <small>APY</small></div>
                </div>
                <div class="rate-features">
                    <div class="rate-feature">
                        <span>Minimum Balance</span>
                        <span>$5,000</span>
                    </div>
                    <div class="rate-feature">
                        <span>Monthly Fee</span>
                        <span>$15</span>
                    </div>
                    <div class="rate-feature">
                        <span>Online Access</span>
                        <span><i class="fas fa-check check-icon"></i></span>
                    </div>
                    <div class="rate-feature">
                        <span>Transfer Limit</span>
                        <span>$250,000/day</span>
                    </div>
                </div>
                <div class="rate-action">
                    <a href="#" class="btn-rate">Open Account</a>
                </div>
            </div>

            <!-- Fixed Deposit -->
            <div class="rate-card">
                <div class="rate-header">
                    <div class="rate-icon"><i class="fas fa-clock"></i></div>
                    <h3>Fixed Deposit</h3>
                    <div class="rate-percentage">5.25% <small>p.a.</small></div>
                </div>
                <div class="rate-features">
                    <div class="rate-feature">
                        <span>Minimum Deposit</span>
                        <span>$10,000</span>
                    </div>
                    <div class="rate-feature">
                        <span>Term</span>
                        <span>12 months</span>
                    </div>
                    <div class="rate-feature">
                        <span>Early Withdrawal</span>
                        <span><i class="fas fa-times times-icon"></i></span>
                    </div>
                    <div class="rate-feature">
                        <span>Interest Payment</span>
                        <span>Monthly</span>
                    </div>
                </div>
                <div class="rate-action">
                    <a href="#" class="btn-rate">Open Account</a>
                </div>
            </div>
        </div>

        <!-- Comparison Table -->
        <div class="comparison-section">
            <h2 style="margin-bottom: 25px;">Detailed Comparison</h2>
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Account Type</th>
                        <th>Interest Rate</th>
                        <th>Min Balance</th>
                        <th>Monthly Fee</th>
                        <th>Online Access</th>
                        <th>Transfer Limit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Savings</strong></td>
                        <td>3.50% APY</td>
                        <td>$1,000</td>
                        <td>$0</td>
                        <td><i class="fas fa-check check-icon"></i></td>
                        <td>$50,000</td>
                    </tr>
                    <tr>
                        <td><strong>Current</strong></td>
                        <td>1.25% APY</td>
                        <td>$500</td>
                        <td>$5</td>
                        <td><i class="fas fa-check check-icon"></i></td>
                        <td>$100,000</td>
                    </tr>
                    <tr>
                        <td><strong>Business</strong></td>
                        <td>2.75% APY</td>
                        <td>$5,000</td>
                        <td>$15</td>
                        <td><i class="fas fa-check check-icon"></i></td>
                        <td>$250,000</td>
                    </tr>
                    <tr>
                        <td><strong>Fixed Deposit</strong></td>
                        <td>5.25% p.a.</td>
                        <td>$10,000</td>
                        <td>$0</td>
                        <td><i class="fas fa-check check-icon"></i></td>
                        <td>No transfers</td>
                    </tr>
                </tbody>
            </table>
            <div class="footnote">
                * Rates effective as of March 2026. APY = Annual Percentage Yield. Terms and conditions apply.
            </div>
        </div>

        <!-- Rate Disclaimer -->
        <div style="margin-top: 30px; padding: 20px; background: #e6f0f7; border-radius: 10px; color: #0f5c8c;">
            <i class="fas fa-info-circle"></i> Rates are variable and subject to change. Contact us for current rates on large deposits.
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
            <p>&copy; 2026 Barclays Banking. All rights reserved. | FDIC Insured | Equal Housing Lender | Rates effective 03/2026</p>
        </div>
    </footer>

    <script>
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const button = document.querySelector('.dark-mode-toggle');
            if (document.body.classList.contains('dark-mode')) {
                button.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
                // Add dark mode styles
                document.body.style.background = '#0f172a';
                document.body.style.color = '#f8fafc';
            } else {
                button.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
                document.body.style.background = '#f8fafc';
                document.body.style.color = '#1e293b';
            }
        }
    </script>
</body>
</html>