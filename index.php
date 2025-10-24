<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PGP Farmer Traceability - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0e9c4d;
            --secondary-color: #0a6e38;
            --light-color: #f5f9f7;
            --dark-color: #333;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(rgba(14, 156, 77, 0.1), rgba(14, 156, 77, 0.2)), url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%23f8f8f8"/><path d="M0,0 L100,100 M100,0 L0,100" stroke="%23e8e8e8" stroke-width="1"/></svg>');
            background-size: cover;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        header {
            background-color: white;
            box-shadow: var(--shadow);
            padding: 15px 0;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 50px;
            margin-right: 10px;
        }
        
        .logo h1 {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 700;
        }
        
        /* Hero Section */
        .hero {
            padding: 80px 0;
            text-align: center;
            flex: 1;
        }
        
        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h2 {
            font-size: 42px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 18px;
            color: #555;
            margin-bottom: 40px;
            line-height: 1.8;
        }
        
        /* Feature Cards */
        .features {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 60px 0;
            flex-wrap: wrap;
        }
        
        .feature-card {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 30px;
            width: 280px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            background-color: var(--light-color);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--primary-color);
            font-size: 30px;
        }
        
        .feature-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: #666;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 16px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
            min-width: 200px;
            text-align: center;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: 2px solid var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        /* Footer Styles */
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 40px 0 20px;
            text-align: center;
        }
        
        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .footer-logo {
            margin-bottom: 20px;
        }
        
        .footer-logo img {
            height: 40px;
            filter: brightness(0) invert(1);
        }
        
        .footer-links {
            display: flex;
            gap: 25px;
            margin: 20px 0;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .footer-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
        .copyright {
            margin-top: 20px;
            color: #ccc;
            font-size: 14px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h2 {
                font-size: 32px;
            }
            
            .hero p {
                font-size: 16px;
            }
            
            .feature-card {
                width: 100%;
                max-width: 350px;
            }
        }
        
        @media (max-width: 480px) {
            .logo h1 {
                font-size: 20px;
            }
            
            .hero h2 {
                font-size: 28px;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-content">
            <div class="logo">
                <!-- PGP India Logo -->
                <img src="https://pgpindia.co/wp-content/uploads/2021/10/WhatsApp_Image_2025-05-29_at_14.03.45_96bf6913-removebg-preview-e1749970605607.png" alt="PGP India Logo">
                <h1>PGP Farmer Traceability</h1>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="hero">
        <div class="container hero-content">
            <h2>Welcome to PGP Farmer Traceability System</h2>
            <p>A comprehensive platform designed to connect farmers with the agricultural ecosystem. Register to access farmer services, track your cultivation activities, and become part of a transparent supply chain.</p>
            
            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3>Farmer Registration</h3>
                    <p>Register as a farmer to get your unique ID and access all services</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <h3>Cultivation Tracking</h3>
                    <p>Log your cultivation activities and monitor your farm's progress</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Dashboard Analytics</h3>
                    <p>View insights and analytics about your farming activities</p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login to Account
                </a>
                <a href="register.php" class="btn btn-outline">
                    <i class="fas fa-user-plus"></i> Register as Farmer
                </a>
            </div>
            
            
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <div class="footer-logo">
                <img src="https://pgpindia.co/wp-content/uploads/2021/10/WhatsApp_Image_2025-05-29_at_14.03.45_96bf6913-removebg-preview-e1749970605607.png" alt="PGP India Logo">
            </div>
            
            <div class="footer-links">
                <a href="#">About Us</a>
                <a href="#">Contact</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">FAQ</a>
            </div>
            
            <p class="copyright">&copy; 2020 PGP India. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>