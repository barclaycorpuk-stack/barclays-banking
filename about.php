<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Barclays Banking</title>
    
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
            color: #1e293b;
            overflow-x: hidden;
            background: #f8fafc;
        }

        /* Chase Bank Style Colors */
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

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--chase-blue), var(--chase-dark-blue));
            color: white;
            padding: 60px 5%;
            text-align: center;
        }

        .page-header h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .page-header p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.9;
        }

        /* About Section */
        .about-section {
            padding: 80px 5%;
            background: white;
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }

        .about-content h2 {
            font-size: 2.5rem;
            color: #0f172a;
            margin-bottom: 20px;
        }

        .about-content h2 span {
            color: var(--chase-blue);
            position: relative;
        }

        .about-content h2 span::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 0;
            width: 100%;
            height: 8px;
            background: var(--chase-gold);
            opacity: 0.3;
            z-index: -1;
        }

        .about-content p {
            color: #475569;
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        .about-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-top: 40px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-item h3 {
            font-size: 2.5rem;
            color: var(--chase-blue);
            margin-bottom: 5px;
        }

        .stat-item p {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .about-image {
            position: relative;
        }

        .about-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .about-image .experience-badge {
            position: absolute;
            bottom: -20px;
            right: -20px;
            background: var(--chase-gold);
            color: #0f172a;
            padding: 20px;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-weight: bold;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .experience-badge span {
            font-size: 2rem;
            line-height: 1;
        }

        /* Mission Vision Section */
        .mission-section {
            padding: 80px 5%;
            background: #f8fafc;
        }

        .mission-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
        }

        .mission-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            text-align: center;
            transition: 0.3s;
        }

        .mission-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(15, 92, 140, 0.1);
        }

        .mission-card i {
            font-size: 3rem;
            color: var(--chase-blue);
            margin-bottom: 20px;
        }

        .mission-card h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: #0f172a;
        }

        .mission-card p {
            color: #475569;
            line-height: 1.6;
        }

        /* Core Values */
        .values-section {
            padding: 80px 5%;
            background: white;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-header h2 {
            font-size: 2.5rem;
            color: #0f172a;
            margin-bottom: 15px;
        }

        .section-header p {
            color: #64748b;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .value-card {
            text-align: center;
            padding: 30px;
            background: #f8fafc;
            border-radius: 15px;
            transition: 0.3s;
        }

        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(15, 92, 140, 0.1);
        }

        .value-icon {
            width: 70px;
            height: 70px;
            background: var(--chase-blue);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 20px;
        }

        .value-card h4 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #0f172a;
        }

        .value-card p {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* Team Section */
        .team-section {
            padding: 80px 5%;
            background: #f8fafc;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .team-member {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: 0.3s;
            text-align: center;
        }

        .team-member:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(15, 92, 140, 0.1);
        }

        .member-image {
            height: 250px;
            background: linear-gradient(135deg, var(--chase-blue), var(--chase-dark-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            font-weight: bold;
        }

        .member-info {
            padding: 20px;
        }

        .member-info h4 {
            font-size: 1.2rem;
            margin-bottom: 5px;
            color: #0f172a;
        }

        .member-info p {
            color: var(--chase-blue);
            font-weight: 500;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .member-bio {
            color: #64748b;
            font-size: 0.85rem;
            line-height: 1.6;
        }

        /* Milestones Section */
        .milestones-section {
            padding: 60px 5%;
            background: linear-gradient(135deg, var(--chase-blue), var(--chase-dark-blue));
            color: white;
        }

        .milestones-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            text-align: center;
        }

        .milestone-item h3 {
            font-size: 3rem;
            margin-bottom: 10px;
            color: var(--chase-gold);
        }

        .milestone-item p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* CTA Section */
        .cta-section {
            padding: 80px 5%;
            background: white;
            text-align: center;
        }

        .cta-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-container h2 {
            font-size: 2.5rem;
            color: #0f172a;
            margin-bottom: 20px;
        }

        .cta-container p {
            color: #475569;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .btn-large {
            padding: 15px 40px;
            font-size: 1.1rem;
        }

        /* Footer */
        footer {
            background: #0f172a;
            color: white;
            padding: 60px 5% 20px;
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
            .about-container,
            .mission-container,
            .values-grid,
            .team-grid,
            .milestones-container {
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
            
            .about-container,
            .mission-container,
            .values-grid,
            .team-grid,
            .milestones-container,
            .footer-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 2.5rem;
            }
            
            .about-image .experience-badge {
                width: 80px;
                height: 80px;
                font-size: 0.8rem;
            }
            
            .experience-badge span {
                font-size: 1.5rem;
            }
            
            .cta-buttons {
                flex-direction: column;
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
            <a href="contact.php" class="active">Contact</a>
        </div>
        <div class="nav-buttons">
            <a href="login.php" class="btn-outline">Sign In</a>
            <a href="register.php" class="btn-primary">Open Account</a>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <h1>About Barclays Banking</h1>
        <p>Building a better banking experience for over 100 years. Trusted by millions, committed to your financial success.</p>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="about-container">
            <div class="about-content">
                <h2>Our <span>Story</span></h2>
                <p>Founded in 1920, Barclays Banking has grown from a small community bank to a trusted financial institution serving millions of customers worldwide. Our journey has been defined by innovation, integrity, and an unwavering commitment to our clients' financial well-being.</p>
                <p>Today, we combine traditional banking values with cutting-edge technology to provide you with the best possible banking experience. From our humble beginnings to becoming a leader in digital banking, we've always put our customers first.</p>
                
                <div class="about-stats">
                    <div class="stat-item">
                        <h3>100+</h3>
                        <p>Years of Trust</p>
                    </div>
                    <div class="stat-item">
                        <h3>5M+</h3>
                        <p>Happy Customers</p>
                    </div>
                    <div class="stat-item">
                        <h3>$50B+</h3>
                        <p>Assets Managed</p>
                    </div>
                </div>
            </div>
            <div class="about-image">
                <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Barclays Headquarters">
                <div class="experience-badge">
                    <span>100+</span>
                    Years
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="mission-section">
        <div class="mission-container">
            <div class="mission-card">
                <i class="fas fa-bullseye"></i>
                <h3>Our Mission</h3>
                <p>To empower individuals and businesses to achieve their financial goals through innovative, secure, and accessible banking solutions that simplify their lives and build lasting prosperity.</p>
            </div>
            <div class="mission-card">
                <i class="fas fa-eye"></i>
                <h3>Our Vision</h3>
                <p>To be the world's most trusted and innovative digital bank, where technology meets human touch to create exceptional financial experiences for every customer.</p>
            </div>
        </div>
    </section>

    <!-- Core Values -->
    <section class="values-section">
        <div class="section-header">
            <h2>Our Core Values</h2>
            <p>The principles that guide everything we do</p>
        </div>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <h4>Customer First</h4>
                <p>Every decision we make starts with what's best for our customers. Your success is our success.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4>Integrity</h4>
                <p>We operate with honesty, transparency, and the highest ethical standards in everything we do.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h4>Innovation</h4>
                <p>We continuously evolve and adapt to bring you the best banking technology and solutions.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h4>Community</h4>
                <p>We're committed to giving back and strengthening the communities we serve.</p>
            </div>
        </div>
    </section>

    <!-- Milestones -->
    <section class="milestones-section">
        <div class="milestones-container">
            <div class="milestone-item">
                <h3>1920</h3>
                <p>Founded in London</p>
            </div>
            <div class="milestone-item">
                <h3>1985</h3>
                <p>First Digital Banking</p>
            </div>
            <div class="milestone-item">
                <h3>2010</h3>
                <p>Mobile App Launch</p>
            </div>
            <div class="milestone-item">
                <h3>2026</h3>
                <p>5M+ Customers</p>
            </div>
        </div>
    </section>

    <!-- Leadership Team -->
    <section class="team-section">
        <div class="section-header">
            <h2>Our Leadership</h2>
            <p>Meet the team dedicated to your financial success</p>
        </div>
        
        <div class="team-grid">
            <div class="team-member">
                <div class="member-image">JD</div>
                <div class="member-info">
                    <h4>John Davis</h4>
                    <p>CEO & Founder</p>
                    <p class="member-bio">30+ years of banking experience, passionate about financial inclusion.</p>
                </div>
            </div>
            <div class="team-member">
                <div class="member-image">SW</div>
                <div class="member-info">
                    <h4>Sarah Williams</h4>
                    <p>Chief Financial Officer</p>
                    <p class="member-bio">Former Goldman Sachs executive, expert in financial strategy.</p>
                </div>
            </div>
            <div class="team-member">
                <div class="member-image">MC</div>
                <div class="member-info">
                    <h4>Michael Chen</h4>
                    <p>CTO</p>
                    <p class="member-bio">Tech innovator leading our digital transformation.</p>
                </div>
            </div>
            <div class="team-member">
                <div class="member-image">ER</div>
                <div class="member-info">
                    <h4>Emily Rodriguez</h4>
                    <p>Head of Customer Experience</p>
                    <p class="member-bio">Dedicated to making banking simple and accessible.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-container">
            <h2>Ready to Start Your Banking Journey?</h2>
            <p>Join millions of satisfied customers who trust Barclays for their financial needs. Open an account today and experience banking reimagined.</p>
            <div class="cta-buttons">
                <a href="register.php" class="btn-primary btn-large">Open an Account</a>
                <a href="contact.php" class="btn-outline btn-large">Contact Us</a>
            </div>
        </div>
    </section>

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
            <p>&copy; 2026 Barclays Banking. All rights reserved. | FDIC Insured | Equal Housing Lender</p>
        </div>
    </footer>

    <!-- Dark Mode Toggle Script -->
    <script>
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const button = document.querySelector('.dark-mode-toggle');
            if (document.body.classList.contains('dark-mode')) {
                button.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
                // Add dark mode styles if needed
            } else {
                button.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
            }
        }
    </script>
</body>
</html>