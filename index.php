<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>Barclays Banking - Modern Banking Solutions</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Swiper for testimonials carousel -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    
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

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: var(--chase-blue);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 1.2rem;
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 60px 5%;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 600px;
            align-items: center;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 20px;
            color: #0f172a;
        }

        .hero-content h1 span {
            color: var(--chase-blue);
            position: relative;
        }

        .hero-content h1 span::after {
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

        .hero-content p {
            font-size: 1.2rem;
            color: #475569;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
        }

        .btn-large {
            padding: 15px 35px;
            font-size: 1.1rem;
        }

        .trust-badges {
            display: flex;
            gap: 30px;
        }

        .trust-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #64748b;
            font-size: 0.9rem;
        }

        .trust-badge i {
            color: var(--chase-gold);
            font-size: 1.2rem;
        }

        /* Login Form */
        .hero-login {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }

        .hero-login h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #0f172a;
        }

        .hero-login p {
            color: #64748b;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--chase-blue);
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 0.9rem;
        }

        .forgot-password {
            color: var(--chase-blue);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--chase-blue);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            margin-bottom: 20px;
        }

        .btn-login:hover {
            background: var(--chase-dark-blue);
        }

        .secure-note {
            text-align: center;
            color: #94a3b8;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Banking Products Section */
        .products-section {
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
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .product-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: 0.3s;
            text-align: center;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(15, 92, 140, 0.1);
            border-color: var(--chase-blue);
        }

        .product-icon {
            width: 70px;
            height: 70px;
            background: #e6f0f7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--chase-blue);
            font-size: 1.8rem;
        }

        .product-card h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #0f172a;
        }

        .product-card p {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .product-link {
            color: var(--chase-blue);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .product-link:hover {
            gap: 10px;
        }

        /* Reviews + Currency Section */
        .reviews-currency-section {
            padding: 60px 5%;
            background: #f8fafc;
        }

        .reviews-currency-container {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Reviews Carousel */
        .reviews-carousel {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .reviews-header h3 {
            font-size: 1.5rem;
            color: #0f172a;
        }

        .reviews-header p {
            color: #64748b;
        }

        .testimonial-card {
            padding: 20px;
        }

        .testimonial-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .testimonial-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--chase-blue), var(--chase-gold));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .testimonial-info h4 {
            color: #0f172a;
            margin-bottom: 5px;
        }

        .testimonial-rating {
            color: var(--chase-gold);
        }

        .testimonial-text {
            color: #475569;
            line-height: 1.6;
            font-style: italic;
        }

        /* Swiper Pagination */
        .swiper-pagination-bullet {
            width: 10px;
            height: 10px;
            background: #cbd5e1;
            opacity: 1;
        }

        .swiper-pagination-bullet-active {
            background: var(--chase-blue);
            width: 12px;
            height: 12px;
        }

        /* Currency Widget */
        .currency-widget {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .widget-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .widget-title i {
            color: var(--chase-gold);
        }

        .rates-list {
            margin-bottom: 20px;
        }

        .rate-item-small {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }

        .rate-item-small:last-child {
            border-bottom: none;
        }

        .currency-pair-small {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .currency-flag-small {
            width: 24px;
            height: 24px;
            background: var(--chase-blue);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .rate-value-small {
            font-weight: 600;
            color: #0f172a;
        }

        .rate-change-small {
            font-size: 0.75rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }

        .rate-change-small.positive {
            background: #dcfce7;
            color: #166534;
        }

        .rate-change-small.negative {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Mini Converter */
        .mini-converter {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .converter-row {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }

        .converter-row select,
        .converter-row input {
            padding: 8px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        .converter-row select {
            width: 80px;
        }

        .converter-row input {
            flex: 1;
        }

        .swap-mini {
            width: 30px;
            height: 30px;
            background: var(--chase-blue);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            margin: 5px auto;
            display: block;
        }

        .result-mini {
            text-align: center;
            background: #e6f0f7;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .result-mini .amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--chase-blue);
        }

        /* Why Choose Barclays Section */
        .features-section {
            padding: 80px 5%;
            background: white;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-item {
            text-align: center;
            padding: 30px;
            background: #f8fafc;
            border-radius: 15px;
            transition: 0.3s;
        }

        .feature-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(15, 92, 140, 0.1);
        }

        .feature-item i {
            font-size: 2.5rem;
            color: var(--chase-blue);
            margin-bottom: 20px;
        }

        .feature-item h4 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #0f172a;
        }

        .feature-item p {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* Help Section */
        .help-section {
            background: var(--chase-blue);
            color: white;
            padding: 60px 5%;
            text-align: center;
        }

        .help-section h2 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .help-section p {
            font-size: 1.1rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }

        .help-options {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            max-width: 1000px;
            margin: 0 auto;
        }

        .help-option {
            background: rgba(255,255,255,0.1);
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
            min-width: 160px;
            justify-content: center;
        }

        .help-option:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-3px);
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

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .product-card, .feature-item, .help-option {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Manual Carousel Styles */
        .manual-carousel {
            position: relative;
            width: 100%;
            overflow: hidden;
            margin-top: 20px;
        }

        .carousel-container {
            display: flex;
            transition: transform 0.5s ease;
        }

        .carousel-slide {
            flex: 0 0 100%;
            opacity: 0;
            transition: opacity 0.5s ease;
            display: none;
        }

        .carousel-slide.active {
            opacity: 1;
            display: block;
        }

        .carousel-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 20px;
        }

        .carousel-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--chase-blue);
            color: white;
            border: none;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .carousel-btn:hover {
            background: var(--chase-dark-blue);
            transform: scale(1.1);
        }

        .carousel-dots {
            display: flex;
            gap: 10px;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #cbd5e1;
            cursor: pointer;
            transition: 0.3s;
        }

        .dot.active {
            background: var(--chase-blue);
            transform: scale(1.2);
        }

        /* ===================================== */
        /* MOBILE RESPONSIVE FIXES - AT THE VERY END */
        /* ===================================== */

        @media screen and (max-width: 1024px) {
            .products-grid,
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .reviews-currency-container {
                grid-template-columns: 1fr;
            }
        }

        @media screen and (max-width: 768px) {
            /* Mobile Menu Button */
            .mobile-menu-btn {
                display: block;
            }

            /* Fix navigation for mobile */
            .main-nav {
                flex-direction: column;
                padding: 10px 3%;
                position: relative;
            }

            .logo {
                width: 100%;
                text-align: left;
            }

            .nav-menu {
                flex-direction: column;
                width: 100%;
                gap: 5px;
                margin: 10px 0;
                display: none;
            }

            .nav-menu.show {
                display: flex;
            }

            .nav-menu a {
                width: 100%;
                text-align: center;
                padding: 12px;
                margin: 2px 0;
                border-bottom: 1px solid #e2e8f0;
            }

            .nav-buttons {
                flex-direction: column;
                width: 100%;
                gap: 10px;
                display: none;
            }

            .nav-buttons.show {
                display: flex;
            }

            .nav-buttons a {
                width: 100%;
                text-align: center;
                margin: 2px 0;
            }

            /* Fix hero section */
            .hero {
                grid-template-columns: 1fr;
                padding: 30px 3%;
            }

            .hero-content h1 {
                font-size: 2rem;
                text-align: center;
            }

            .hero-content p {
                text-align: center;
                font-size: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
                gap: 10px;
                align-items: center;
            }

            .hero-buttons a {
                width: 100%;
                text-align: center;
            }

            .trust-badges {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            /* Fix login form */
            .hero-login {
                padding: 25px;
                margin-top: 20px;
            }

            /* Fix top bar */
            .top-bar {
                flex-direction: column;
                text-align: center;
                padding: 10px 3%;
            }

            .top-bar-links {
                flex-wrap: wrap;
                justify-content: center;
                margin-top: 10px;
                gap: 15px;
            }

            /* Fix products grid */
            .products-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .product-card {
                padding: 25px;
            }

            /* Fix reviews & currency section */
            .reviews-currency-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .reviews-carousel {
                padding: 20px;
            }

            .testimonial-card {
                padding: 15px;
            }

            .testimonial-header {
                flex-direction: column;
                text-align: center;
            }

            /* Fix features grid */
            .features-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .feature-item {
                padding: 25px;
            }

            /* Fix help section */
            .help-options {
                flex-direction: column;
                gap: 15px;
            }

            .help-option {
                width: 100%;
                margin: 0;
                justify-content: center;
            }

            /* Fix footer */
            .footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 30px;
            }

            .footer-col {
                text-align: center;
            }

            .footer-col a {
                text-align: center;
            }

            .social-links {
                justify-content: center;
            }

            /* Fix currency widget */
            .converter-row {
                flex-direction: column;
            }

            .converter-row select,
            .converter-row input {
                width: 100%;
            }

            .swap-mini {
                margin: 10px auto;
            }
        }

        /* Extra small devices */
        @media screen and (max-width: 480px) {
            .hero-content h1 {
                font-size: 1.8rem;
            }

            .hero-content p {
                font-size: 0.9rem;
            }

            .login-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .section-header h2 {
                font-size: 1.8rem;
            }

            .product-card h3 {
                font-size: 1.2rem;
            }

            .widget-title {
                font-size: 1.1rem;
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

    <!-- Main Navigation with Mobile Menu -->
    <nav class="main-nav">
        <a href="index.php" class="logo">
            <i class="fas fa-university"></i>
            BARCLAYS
        </a>
        <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="nav-menu" id="navMenu">
            <a href="index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="how-it-works.php">How It Works</a>
            <a href="loans.php">Loans</a>
            <a href="contact.php">Contact</a>
        </div>
        <div class="nav-buttons" id="navButtons">
            <a href="login.php" class="btn-outline">Sign In</a>
            <a href="register.php" class="btn-primary">Open Account</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>
                Banking <span>Reimagined</span><br>
                for Your Future
            </h1>
            <p>Experience the future of finance with secure transactions, real-time tracking, and a user interface designed for you. Join over 1 million satisfied customers.</p>
            
            <div class="hero-buttons">
                <a href="register.php" class="btn-primary btn-large">Open an Account</a>
                <a href="#" class="btn-outline btn-large">Learn More</a>
            </div>
            
            <div class="trust-badges">
                <div class="trust-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>FDIC Insured</span>
                </div>
                <div class="trust-badge">
                    <i class="fas fa-lock"></i>
                    <span>256-bit Encryption</span>
                </div>
                <div class="trust-badge">
                    <i class="fas fa-mobile-alt"></i>
                    <span>24/7 Support</span>
                </div>
            </div>
        </div>
        
        <div class="hero-login">
            <h3>Welcome Back</h3>
            <p>Access your accounts securely</p>
            
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label>Username or Email</label>
                    <input type="text" name="username" placeholder="Enter your username" required>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="login-options">
                    <label class="remember-me">
                        <input type="checkbox"> Remember me
                    </label>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="btn-login">Sign In</button>
                
                <div class="secure-note">
                    <i class="fas fa-lock"></i>
                    Secure Login
                </div>
            </form>
        </div>
    </section>

    <!-- Banking Products Section -->
    <section class="products-section">
        <div class="section-header">
            <h2>Banking Products</h2>
            <p>Choose the right account for your needs</p>
        </div>
        
        <div class="products-grid">
            <div class="product-card">
                <div class="product-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <h3>Checking Accounts</h3>
                <p>Everyday banking with no monthly fees. Access your money anytime, anywhere.</p>
                <a href="#" class="product-link">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="product-card">
                <div class="product-icon">
                    <i class="fas fa-piggy-bank"></i>
                </div>
                <h3>Savings Accounts</h3>
                <p>Grow your money with competitive interest rates and no hidden fees.</p>
                <a href="#" class="product-link">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="product-card">
                <div class="product-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h3>Credit Cards</h3>
                <p>Rewards, cashback, and low interest rates. Find the perfect card for you.</p>
                <a href="#" class="product-link">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
            
            <div class="product-card">
                <div class="product-icon">
                    <i class="fas fa-home"></i>
                </div>
                <h3>Mortgages & Loans</h3>
                <p>Buy your dream home or finance your next big purchase with our help.</p>
                <a href="#" class="product-link">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <!-- Reviews + Currency Section -->
    <section class="reviews-currency-section">
        <div class="reviews-currency-container">
            <!-- Left: Reviews Carousel -->
            <div class="reviews-carousel">
                <div class="reviews-header">
                    <div>
                        <h3>What Our Customers Say</h3>
                        <p>Real reviews from verified customers</p>
                    </div>
                </div>
                
                <!-- Simple Manual Carousel -->
                <div class="manual-carousel">
                    <div class="carousel-container" id="carouselContainer">
                        <!-- Slide 1 -->
                        <div class="carousel-slide active">
                            <div class="testimonial-card">
                                <div class="testimonial-header">
                                    <div class="testimonial-avatar">J</div>
                                    <div class="testimonial-info">
                                        <h4>John Smith</h4>
                                        <div class="testimonial-rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                </div>
                                <p class="testimonial-text">"Best banking experience I've ever had! The interface is so intuitive and customer service is top-notch."</p>
                            </div>
                        </div>
                        
                        <!-- Slide 2 -->
                        <div class="carousel-slide">
                            <div class="testimonial-card">
                                <div class="testimonial-header">
                                    <div class="testimonial-avatar">S</div>
                                    <div class="testimonial-info">
                                        <h4>Sarah Johnson</h4>
                                        <div class="testimonial-rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                </div>
                                <p class="testimonial-text">"The currency exchange feature saved me so much money on international transfers. Highly recommended!"</p>
                            </div>
                        </div>
                        
                        <!-- Slide 3 -->
                        <div class="carousel-slide">
                            <div class="testimonial-card">
                                <div class="testimonial-header">
                                    <div class="testimonial-avatar">M</div>
                                    <div class="testimonial-info">
                                        <h4>Michael Chen</h4>
                                        <div class="testimonial-rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star-half-alt"></i>
                                        </div>
                                    </div>
                                </div>
                                <p class="testimonial-text">"Great mobile app and the card management features are exactly what I needed. Very secure platform."</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Carousel Controls -->
                    <div class="carousel-controls">
                        <button class="carousel-btn prev-btn" onclick="prevSlide()">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="carousel-dots">
                            <span class="dot active" onclick="currentSlide(0)"></span>
                            <span class="dot" onclick="currentSlide(1)"></span>
                            <span class="dot" onclick="currentSlide(2)"></span>
                        </div>
                        <button class="carousel-btn next-btn" onclick="nextSlide()">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Right: Currency Widget -->
            <div class="currency-widget">
                <h3 class="widget-title">
                    <i class="fas fa-chart-line"></i>
                    Live Exchange Rates
                </h3>
                
                <div class="rates-list" id="liveRates">
                    <!-- Populated by JavaScript -->
                </div>
                
                <div class="mini-converter">
                    <h4 class="widget-title" style="font-size: 1rem;">
                        <i class="fas fa-exchange-alt"></i>
                        Quick Converter
                    </h4>
                    
                    <div class="converter-row">
                        <select id="fromCurrency">
                            <option value="USD">USD</option>
                            <option value="EUR" selected>EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="JPY">JPY</option>
                        </select>
                        <input type="number" id="fromAmount" value="100" placeholder="Amount">
                    </div>
                    
                    <button class="swap-mini" onclick="swapCurrencies()">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                    
                    <div class="converter-row">
                        <select id="toCurrency">
                            <option value="USD" selected>USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="JPY">JPY</option>
                        </select>
                        <input type="text" id="toAmount" readonly placeholder="Result" value="108.70">
                    </div>
                    
                    <div class="result-mini">
                        <span class="amount" id="resultAmount">108.70</span>
                        <span id="resultCurrency">USD</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Barclays Section -->
    <section class="features-section">
        <div class="section-header">
            <h2>Why Choose Barclays</h2>
            <p>Experience banking the way it should be</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-item">
                <i class="fas fa-shield-alt"></i>
                <h4>Bank-Grade Security</h4>
                <p>Your money and data are protected with advanced encryption and monitoring.</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-mobile-alt"></i>
                <h4>Mobile First</h4>
                <p>Bank on the go with our intuitive mobile app and responsive website.</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-clock"></i>
                <h4>24/7 Support</h4>
                <p>Round-the-clock customer service whenever you need assistance.</p>
            </div>
            <div class="feature-item">
                <i class="fas fa-bolt"></i>
                <h4>Instant Transfers</h4>
                <p>Send money instantly to friends, family, and other banks.</p>
            </div>
        </div>
    </section>

    <!-- Help Section -->
    <section class="help-section">
        <h2>We're Here to Help</h2>
        <p>Have questions? Our team is ready to assist you.</p>
        
        <div class="help-options">
            <a href="#" class="help-option">
                <i class="fas fa-map-marker-alt"></i>
                Find a Branch
            </a>
            <a href="#" class="help-option">
                <i class="fas fa-phone-alt"></i>
                Call Us
            </a>
            <a href="#" class="help-option">
                <i class="fas fa-comments"></i>
                Live Chat
            </a>
            <a href="#" class="help-option">
                <i class="fas fa-question-circle"></i>
                Help Center
            </a>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        // Mobile Menu Toggle
        function toggleMobileMenu() {
            document.getElementById('navMenu').classList.toggle('show');
            document.getElementById('navButtons').classList.toggle('show');
        }

        // Simple Carousel Functions
        let currentSlideIndex = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.dot');

        function showSlide(index) {
            slides.forEach(slide => {
                slide.classList.remove('active');
            });
            
            dots.forEach(dot => {
                dot.classList.remove('active');
            });
            
            slides[index].classList.add('active');
            dots[index].classList.add('active');
            currentSlideIndex = index;
        }

        function nextSlide() {
            currentSlideIndex = (currentSlideIndex + 1) % slides.length;
            showSlide(currentSlideIndex);
        }

        function prevSlide() {
            currentSlideIndex = (currentSlideIndex - 1 + slides.length) % slides.length;
            showSlide(currentSlideIndex);
        }

        function currentSlide(index) {
            showSlide(index);
        }

        // Auto advance slides every 5 seconds
        setInterval(nextSlide, 5000);

        // Exchange Rates Data
        const exchangeRates = {
            USD: 1.00,
            EUR: 0.92,
            GBP: 0.79,
            JPY: 150.25,
            CAD: 1.36,
            AUD: 1.53,
            CHF: 0.88,
            CNY: 7.19
        };

        // Update Live Rates
        function updateLiveRates() {
            const ratesContainer = document.getElementById('liveRates');
            if (!ratesContainer) return;
            
            const currencies = ['EUR', 'GBP', 'JPY', 'CAD', 'AUD'];
            
            ratesContainer.innerHTML = '';
            currencies.forEach(curr => {
                const rate = exchangeRates[curr];
                const change = (Math.random() * 0.02 - 0.01).toFixed(4);
                const isPositive = change > 0;
                
                ratesContainer.innerHTML += `
                    <div class="rate-item-small">
                        <div class="currency-pair-small">
                            <div class="currency-flag-small">${curr.charAt(0)}</div>
                            <span>USD/${curr}</span>
                        </div>
                        <div>
                            <span class="rate-value-small">${rate.toFixed(4)}</span>
                            <span class="rate-change-small ${isPositive ? 'positive' : 'negative'}">
                                ${isPositive ? '+' : ''}${(change * 100).toFixed(2)}%
                            </span>
                        </div>
                    </div>
                `;
            });
        }

        // Currency Converter
        function convertCurrency() {
            const from = document.getElementById('fromCurrency').value;
            const to = document.getElementById('toCurrency').value;
            const amount = parseFloat(document.getElementById('fromAmount').value) || 0;
            
            const fromRate = exchangeRates[from];
            const toRate = exchangeRates[to];
            
            const result = (amount / fromRate) * toRate;
            document.getElementById('toAmount').value = result.toFixed(2);
            document.getElementById('resultAmount').innerText = result.toFixed(2);
            document.getElementById('resultCurrency').innerText = to;
        }

        function swapCurrencies() {
            const from = document.getElementById('fromCurrency');
            const to = document.getElementById('toCurrency');
            const temp = from.value;
            from.value = to.value;
            to.value = temp;
            convertCurrency();
        }

        // Dark Mode Toggle
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const button = document.querySelector('.dark-mode-toggle');
            if (document.body.classList.contains('dark-mode')) {
                button.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
            } else {
                button.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
            }
        }

        // Event Listeners
        document.getElementById('fromCurrency')?.addEventListener('change', convertCurrency);
        document.getElementById('toCurrency')?.addEventListener('change', convertCurrency);
        document.getElementById('fromAmount')?.addEventListener('input', convertCurrency);

        // Initialize everything
        window.onload = function() {
            updateLiveRates();
            convertCurrency();
            showSlide(0);
            
            // Close mobile menu when window resized above mobile breakpoint
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    document.getElementById('navMenu').classList.remove('show');
                    document.getElementById('navButtons').classList.remove('show');
                }
            });
        };
    </script>
</body>
</html>