<?php
// includes/database.php
include 'config.php';

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Add small improvement - set timezone
            $this->conn->exec("SET time_zone = '+05:30'");
            
        } catch(PDOException $exception) {
            // Better error handling without breaking anything
            error_log("Database connection error: " . $exception->getMessage());
            
            if (IS_LOCALHOST) {
                echo "Connection error: " . $exception->getMessage();
            } else {
                echo "Database connection failed. Please try again later.";
            }
        }
        
        return $this->conn;
    }
    
    // Add simple helper method that won't break existing code
    public function getSingleRow($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }
}
?>