<?php
// verify_aadhaar.php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid CSRF token');
    }
    
    $aadhaar = trim($_POST['aadhaar']);
    
    // Basic validation
    if (empty($aadhaar) || !preg_match('/^\d{12}$/', $aadhaar)) {
        echo json_encode(['valid' => false, 'message' => 'Invalid Aadhaar format']);
        exit;
    }
    
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check for duplicates
    $query = "SELECT id, farmer_name, farmer_code FROM farmers WHERE aadhaar = :aadhaar";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':aadhaar', $aadhaar);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $existing_farmer = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode([
            'valid' => false, 
            'is_duplicate' => true,
            'existing_farmer' => $existing_farmer,
            'message' => 'Aadhaar already registered'
        ]);
    } else {
        echo json_encode([
            'valid' => true,
            'is_duplicate' => false,
            'message' => 'Aadhaar is valid and unique'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['valid' => false, 'message' => $e->getMessage()]);
}
?>