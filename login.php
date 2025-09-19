<?php
// login.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Initialize variables
$error = '';
$success = '';
$email = '';

// Check if login form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    $auth = new Auth();
    
    if ($auth->login($username, $password)) {
        // Redirect based on user role
        if ($auth->isAdmin()) {
            $auth->redirect('admin/dashboard.php');
        } else {
            $auth->redirect('user/dashboard.php');
        }
    } else {
        $error = 'Invalid username or password.';
    }
}

// Check if forgot password form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $auth = new Auth();
        
        if ($auth->recoverPassword($email)) {
            $success = "Password recovery email sent to $email.";
        } else {
            $error = 'No account found with that email address.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PGP Farmer Traceability</title>
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
        
        /* Login Form Styles */
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            margin: 20px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .login-header p {
            color: #666;
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
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(14, 156, 77, 0.2);
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
        
        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .forgot-password a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .register-link p {
            color: #666;
        }
        
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .register-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        /* Forgot Password Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 450px;
            box-shadow: var(--shadow);
            position: relative;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 20px;
            top: 15px;
            cursor: pointer;
        }
        
        .close:hover {
            color: var(--dark-color);
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
            .login-container {
                padding: 30px 25px;
            }
            
            .logo h1 {
                font-size: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .login-container {
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
                <img src="https://pgpindia.co/wp-content/uploads/2021/10/WhatsApp_Image_2025-05-29_at_14.03.45_96bf6913-removebg-preview-e1749970605607.png" alt="PGP India Logo">
                <h1>PGP Farmer Traceability</h1>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <section class="main-content">
        <div class="container">
            <div class="login-container">
                <div class="login-header">
                    <h2>Login to Your Account</h2>
                    <p>Access your farmer dashboard</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="login" value="1">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="forgot-password">
                        <a href="#" id="forgotPasswordLink">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn">Login</button>
                    
                    <div class="register-link">
                        <p>Don't have an account? <a href="register.php">Register as Farmer</a></p>
                        <p>Admin registration? <a href="admin_register.php">Register here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="login-header">
                <h2>Reset Your Password</h2>
                <p>Enter your email to receive password reset instructions</p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="forgot_password" value="1">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <button type="submit" class="btn">Send Reset Instructions</button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container footer-content">
            <div class="footer-logo">
            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiMwZTljNGQiLz48cGF0aCBkPSJNMjUgMjVINTBWNjVIMjVWNjVINDBWNDBIMjVWMjVaIiBmaWxsPSJ3aGl0ZSIvPjxwYXRoIGQ9Ik01MCAyNVY0MEg3NVYyNUg1MFpNNzUgNDBWNjVINjIuNVY1Mi41SDUwVjY1SDM3LjVWNTIuNUgyNVY2NUg3NVY0MFoiIGZpbGw9IndoaXRlIi8+PC9zdmc+" alt="PGP India Logo">
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
    // Forgot Password Modal
    const modal = document.getElementById('forgotPasswordModal');
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');
    const closeBtn = document.querySelector('.close');
    
    forgotPasswordLink.addEventListener('click', function(e) {
        e.preventDefault();
        modal.style.display = 'block';
    });
    
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Show modal if there was an error with forgot password
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])): ?>
        modal.style.display = 'block';
    <?php endif; ?>
</script>
</body> 
</html>