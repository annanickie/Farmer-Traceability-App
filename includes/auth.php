<?php
// includes/auth.php
class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function register($username, $password, $email, $role = 'user', $firstName = null, $lastName = null, $phone = null) {
        // Use sanitization
        $username = sanitizeInput($username);
        $email = sanitizeInput($email);
        $firstName = sanitizeInput($firstName);
        $lastName = sanitizeInput($lastName);
        $phone = sanitizeInput($phone);
        
        // Check if user already exists
        $query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return false;
        }
        
        // Insert new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users SET username=:username, password=:password, email=:email, role=:role, 
                 first_name=:first_name, last_name=:last_name, phone=:phone, created_at=NOW()";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':phone', $phone);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    public function login($username, $password) {
        // Simple sanitization
        $username = sanitizeInput($username);
        
        // FIXED: Changed 'is_active' to 'status' to match your database
        $query = "SELECT id, username, password, role, status FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // FIXED: Check 'status' column instead of 'is_active'
            if(password_verify($password, $row['password']) && $row['status'] == 'active') {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                
                // Update last login
                $this->updateLastLogin($row['id']);
                
                return true;
            }
        }
        
        return false;
    }
    
    private function updateLastLogin($userId) {
        $query = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
    }
    
    public function redirect($url) {
        header("Location: $url");
        exit;
    }
    
    public function logout() {
        session_destroy();
        $this->redirect('index.php');
    }
    
    public function getUserProfile($userId) {
        $query = "SELECT username, email, first_name, last_name, phone, created_at, last_login 
                  FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }
    
    // Improved password recovery with basic validation
    public function recoverPassword($email) {
        $email = sanitizeInput($email);
        
        // Basic email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        $query = "SELECT id, username, password FROM users WHERE email = :email AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // In a real application, you would:
            // 1. Generate a reset token
            // 2. Send reset link via email
            // 3. Not send password in email
            
            // For now, just return success but don't actually send email
            // This prevents security issues in production
            return ['success' => true, 'message' => 'If this email exists, you will receive password reset instructions.'];
        }
        
        // Always return the same message for security (don't reveal if email exists)
        return ['success' => true, 'message' => 'If this email exists, you will receive password reset instructions.'];
    }
    
    // Add a simple method to check if username exists
    public function usernameExists($username) {
        $username = sanitizeInput($username);
        
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
?>