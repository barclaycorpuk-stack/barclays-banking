<?php
// contact.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Barclays Banking</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Leaflet for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
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
            margin: -40px auto 40px;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        /* Contact Cards */
        .contact-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .contact-card {
            background: white;
            padding: 30px 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: 0.3s;
            border: 1px solid #e2e8f0;
        }

        .contact-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(15, 92, 140, 0.15);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--chase-blue), var(--chase-gold));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 1.5rem;
        }

        .contact-card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #0f172a;
        }

        .contact-card p {
            color: #64748b;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .contact-link {
            color: var(--chase-blue);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .contact-link:hover {
            color: var(--chase-dark-blue);
            gap: 10px;
        }

        /* Contact Form and Map */
        .contact-main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .contact-form {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .contact-form h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: #0f172a;
        }

        .contact-form p {
            color: #64748b;
            margin-bottom: 30px;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--chase-blue);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn-submit {
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
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: var(--chase-dark-blue);
            transform: translateY(-2px);
        }

        /* Map Section */
        .map-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .map-section h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: #0f172a;
        }

        #map {
            height: 350px;
            border-radius: 15px;
            margin-bottom: 20px;
        }

        .branch-list {
            max-height: 200px;
            overflow-y: auto;
        }

        .branch-item {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            transition: 0.3s;
        }

        .branch-item:hover {
            background: #f8fafc;
        }

        .branch-item.active {
            background: #e6f0f7;
            border-left: 3px solid var(--chase-blue);
        }

        .branch-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .branch-address {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Business Hours */
        .hours-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .hours-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }

        .hours-day {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .hours-day span:first-child {
            font-weight: 600;
        }

        .hours-day span:last-child {
            color: #64748b;
        }

        /* FAQ Section */
        .faq-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
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
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .contact-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .contact-main {
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
            
            .contact-cards {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .hours-grid {
                grid-template-columns: 1fr;
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

    <!-- Main Navigation - Exactly as you specified -->
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
        <h1>Get in Touch</h1>
        <p>We're here to help 24/7. Reach out to us anytime.</p>
    </section>

    <!-- Main Content -->
    <div class="container">
        <!-- Contact Cards -->
        <div class="contact-cards">
            <div class="contact-card">
                <div class="contact-icon"><i class="fas fa-phone"></i></div>
                <h3>Call Us</h3>
                <p>24/7 Customer Support</p>
                <a href="tel:18002272597" class="contact-link">1-800-BARCLAYS <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="contact-card">
                <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                <h3>Email Us</h3>
                <p>Get reply within 24h</p>
                <a href="mailto:support@barclays.com" class="contact-link">support@barclays.com <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="contact-card">
                <div class="contact-icon"><i class="fas fa-comments"></i></div>
                <h3>Live Chat</h3>
                <p>Instant messaging</p>
                <a href="#" class="contact-link">Start Chat <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="contact-card">
                <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h3>Visit Us</h3>
                <p>Find a branch near you</p>
                <a href="#map" class="contact-link">View Locations <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <!-- Contact Form and Map -->
        <div class="contact-main">
            <!-- Contact Form -->
            <div class="contact-form">
                <h2>Send us a Message</h2>
                <p>Fill out the form below and we'll get back to you as soon as possible.</p>
                
                <form id="contactForm" onsubmit="return submitForm(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" placeholder="John" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" placeholder="Doe" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" placeholder="john.doe@example.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" placeholder="(555) 123-4567">
                    </div>
                    
                    <div class="form-group">
                        <label>Subject</label>
                        <select required>
                            <option value="">Select a topic</option>
                            <option value="general">General Inquiry</option>
                            <option value="account">Account Support</option>
                            <option value="loan">Loan Information</option>
                            <option value="card">Card Services</option>
                            <option value="complaint">Complaint</option>
                            <option value="feedback">Feedback</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Message</label>
                        <textarea rows="5" placeholder="How can we help you?" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
            
            <!-- Map and Branches -->
            <div class="map-section">
                <h3><i class="fas fa-map-marker-alt"></i> Our Locations</h3>
                <div id="map"></div>
                
                <div class="branch-list" id="branchList">
                    <div class="branch-item active" data-lat="51.5074" data-lng="-0.1278" onclick="selectBranch(this, 51.5074, -0.1278)">
                        <div class="branch-name">🏦 London Headquarters</div>
                        <div class="branch-address">123 Banking Street, London, UK</div>
                    </div>
                    
                    <div class="branch-item" data-lat="40.7128" data-lng="-74.0060" onclick="selectBranch(this, 40.7128, -74.0060)">
                        <div class="branch-name">🏦 New York Financial District</div>
                        <div class="branch-address">45 Wall Street, New York, NY 10005</div>
                    </div>
                    
                    <div class="branch-item" data-lat="37.7749" data-lng="-122.4194" onclick="selectBranch(this, 37.7749, -122.4194)">
                        <div class="branch-name">🏦 San Francisco Center</div>
                        <div class="branch-address">1 Market Plaza, San Francisco, CA 94105</div>
                    </div>
                    
                    <div class="branch-item" data-lat="34.0522" data-lng="-118.2437" onclick="selectBranch(this, 34.0522, -118.2437)">
                        <div class="branch-name">🏦 Los Angeles Branch</div>
                        <div class="branch-address">444 Flower Street, Los Angeles, CA 90071</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Hours -->
        <div class="hours-section">
            <h2 style="margin-bottom: 30px;">Business Hours</h2>
            <div class="hours-grid">
                <div>
                    <h3 style="color: var(--chase-blue); margin-bottom: 15px;">🏢 Branch Hours</h3>
                    <div class="hours-day">
                        <span>Monday - Friday</span>
                        <span>9:00 AM - 5:00 PM</span>
                    </div>
                    <div class="hours-day">
                        <span>Saturday</span>
                        <span>10:00 AM - 2:00 PM</span>
                    </div>
                    <div class="hours-day">
                        <span>Sunday</span>
                        <span>Closed</span>
                    </div>
                </div>
                
                <div>
                    <h3 style="color: var(--chase-blue); margin-bottom: 15px;">📞 Customer Support</h3>
                    <div class="hours-day">
                        <span>Phone Support</span>
                        <span>24/7</span>
                    </div>
                    <div class="hours-day">
                        <span>Live Chat</span>
                        <span>24/7</span>
                    </div>
                    <div class="hours-day">
                        <span>Email Response</span>
                        <span>Within 24h</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <h2 style="margin-bottom: 30px;">Frequently Asked Questions</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>What are your customer service hours?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Our customer service team is available 24/7 via phone and live chat. Branch locations have varying hours listed above.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>How do I report a lost or stolen card?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Call our 24/7 emergency line at 1-800-BARCLAYS immediately. We'll block your card and issue a replacement.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>How long does it take to get a response to my email?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    We aim to respond to all emails within 24 hours during business days. For urgent matters, please call or use live chat.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    <h4>Do you have branches outside the US?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    Yes, we have international branches in London, Singapore, and Dubai. International customer support is also available 24/7.
                </div>
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
            <p>&copy; 2026 Barclays Banking. All rights reserved. | FDIC Insured | Equal Housing Lender</p>
        </div>
    </footer>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Initialize Map
        let map;
        let marker;
        
        function initMap() {
            map = L.map('map').setView([51.5074, -0.1278], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            marker = L.marker([51.5074, -0.1278]).addTo(map)
                .bindPopup('🏦 London Headquarters<br>123 Banking Street')
                .openPopup();
        }

        // Select Branch
        function selectBranch(element, lat, lng) {
            // Remove active class from all
            document.querySelectorAll('.branch-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to selected
            element.classList.add('active');
            
            // Update map
            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 13);
            marker.bindPopup(element.querySelector('.branch-name').textContent + '<br>' + element.querySelector('.branch-address').textContent).openPopup();
        }

        // Form Submission
        function submitForm(event) {
            event.preventDefault();
            alert('Thank you for contacting us! We will respond within 24 hours.');
            document.getElementById('contactForm').reset();
            return false;
        }

        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const item = question.parentElement;
                item.classList.toggle('active');
            });
        });

        // Initialize map when page loads
        window.onload = initMap;
    </script>
</body>
</html>