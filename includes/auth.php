<?php
// includes/auth.php
class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function register($username, $password, $email, $role = 'user', $firstName = null, $lastName = null, $phone = null) {
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
                 first_name=:first_name, last_name=:last_name, phone=:phone";
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
        $query = "SELECT id, username, password, role, is_active FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password']) && $row['is_active']) {
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
    
    public function sendPasswordEmail($email, $password) {
        // In a real application, you would send an email here
        // This is a simplified version for demonstration
        
        $to = $email;
        $subject = "Password Recovery - PGP Farmer Traceability";
        $message = "Hello,\n\n";
        $message .= "You requested to recover your password for PGP Farmer Traceability.\n\n";
        $message .= "Your password is: $password\n\n";
        $message .= "For security reasons, we recommend changing your password after logging in.\n\n";
        $message .= "Regards,\nPGP Farmer Traceability Team";
        $headers = "From: no-reply@pgpindia.co\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // In a real application, you would use a proper email library like PHPMailer
        // For now, we'll just simulate the email sending
        $simulatedSuccess = true; // Simulate successful email sending
        
        if ($simulatedSuccess) {
            // Log the email sending (in production, you would actually send the email)
            error_log("Password email would be sent to: $email with password: $password");
            return true;
        }
        
        return false;
    }
    
    public function recoverPassword($email) {
        $query = "SELECT id, username, password FROM users WHERE email = :email AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Send password via email
            if ($this->sendPasswordEmail($email, $user['password'])) {
                return true;
            }
        }
        
        return false;
    }
}
?>