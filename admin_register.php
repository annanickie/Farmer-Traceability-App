<?php
// admin_register.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Initialize variables
$error = '';
$success = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $adminCode = trim($_POST['admin_code']);
    $adminRole = trim($_POST['admin_role']);
    
    // Initialize auth class
    $auth = new Auth();
    
    // Validate input
    if (empty($username) || empty($password) || empty($email) || empty($firstName) || empty($adminCode)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 10) {
        $error = 'Password must be at least 10 characters long.';
    } elseif ($adminCode !== 'PGP2024ADMIN') { // Admin authorization code
        $error = 'Invalid admin authorization code.';
    } else {
        // Register the admin user
        if ($auth->register($username, $password, $email, 'admin', $firstName, $lastName, $phone)) {
            $success = 'Admin registration successful! You can now login.';
            // Clear form fields
            $username = $email = $firstName = $lastName = $phone = '';
        } else {
            $error = 'Registration failed. Username or email might already exist.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - PGP Farmer Traceability</title>
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
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        
        /* Registration Form Styles */
        .registration-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            margin: 20px;
        }
        
        .registration-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .registration-header h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .registration-header p {
            color: #666;
        }
        
        .admin-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(14, 156, 77, 0.2);
        }
        
        .password-strength {
            margin-top: 8px;
            height: 6px;
            border-radius: 3px;
            background-color: #eee;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .password-strength-text {
            font-size: 12px;
            margin-top: 5px;
            color: #666;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            transition: background-color 0.3s, transform 0.2s;
            width: 100%;
        }
        
        .btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .login-link p {
            color: #666;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        /* Footer Styles */
        footer {
            background-color: var(--dark-color);
            color: white;
            padding: 30px 0;
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
            .registration-container {
                padding: 30px 25px;
            }
            
            .logo h1 {
                font-size: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .registration-container {
                padding: 25px 20px;
            }
            
            .logo h1 {
                font-size: 18px;
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
                <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg=" alt="PGP India Logo">
                <h1>PGP Farmer Traceability</h1>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <section class="main-content">
        <div class="container">
            <div class="registration-container">
                <div class="registration-header">
                    <h2>Admin Registration</h2>
                    <p>Create an administrator account</p>
                </div>
                
                <div class="admin-notice">
                    <p><i class="fas fa-shield-alt"></i> <strong>Admin Access Notice:</strong> Administrator accounts have full access to the system. Only authorized personnel should register.</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="admin_code">Admin Authorization Code *</label>
                        <input type="password" id="admin_code" name="admin_code" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <div class="password-strength-text" id="passwordStrengthText">Password strength: None</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo isset($firstName) ? htmlspecialchars($firstName) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo isset($lastName) ? htmlspecialchars($lastName) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_role">Admin Role *</label>
                        <select id="admin_role" name="admin_role" required>
                            <option value="">Select Admin Role</option>
                            <option value="super_admin">Super Administrator</option>
                            <option value="state_admin">State Administrator</option>
                            <option value="district_admin">District Administrator</option>
                            <option value="support_admin">Support Administrator</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn">Register Admin Account</button>
                    
                    <div class="login-link">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                        <p>Not an admin? <a href="register.php">Register as farmer</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <div class="footer-logo">
                <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg=" alt="PGP India Logo">
            </div>
            
            <div class="footer-links">
                <a href="index.php">Home</a>
                <a href="#">About Us</a>
                <a href="#">Contact</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
            
            <p class="copyright">&copy; 2023 PGP India. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Password strength calculator
        function checkPasswordStrength(password) {
            let strength = 0;
            let text = '';
            
            if (password.length >= 10) strength += 20;
            if (password.match(/[a-z]+/)) strength += 20;
            if (password.match(/[A-Z]+/)) strength += 20;
            if (password.match(/[0-9]+/)) strength += 20;
            if (password.match(/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]+/)) strength += 20;
            
            // Update strength bar
            const strengthBar = document.getElementById('passwordStrengthBar');
            strengthBar.style.width = strength + '%';
            
            // Update strength text and color
            const strengthText = document.getElementById('passwordStrengthText');
            if (strength < 40) {
                strengthBar.style.backgroundColor = '#dc3545';
                text = 'Weak';
            } else if (strength < 80) {
                strengthBar.style.backgroundColor = '#fd7e14';
                text = 'Medium';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
                text = 'Strong';
            }
            
            strengthText.textContent = `Password strength: ${text}`;
            return strength;
        }
        
        // Real-time password strength checking
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });
    </script>
</body>
</html>