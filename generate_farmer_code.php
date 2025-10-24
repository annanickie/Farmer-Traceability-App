<?php
// generate_farmer_code.php

session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/farmer_code.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

if (!isset($_POST['state_id']) || !isset($_POST['district_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$stateId = $_POST['state_id'];
$districtId = $_POST['district_id'];
$stateCode = $_POST['state_code'] ?? '';
$districtCode = $_POST['district_code'] ?? '';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn === null) {
        throw new Exception("Database connection failed");
    }
    
    // If state_code or district_code not provided, fetch from database
    if (empty($stateCode) || empty($districtCode)) {
        // Get state code
        $stateQuery = "SELECT state_code FROM states WHERE id = :state_id";
        $stateStmt = $conn->prepare($stateQuery);
        $stateStmt->bindParam(':state_id', $stateId);
        $stateStmt->execute();
        $stateData = $stateStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$stateData) {
            throw new Exception("Invalid state selected");
        }
        $stateCode = $stateData['state_code'];
        
        // Get district code
        $districtQuery = "SELECT district_code FROM districts WHERE id = :district_id";
        $districtStmt = $conn->prepare($districtQuery);
        $districtStmt->bindParam(':district_id', $districtId);
        $districtStmt->execute();
        $districtData = $districtStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$districtData) {
            throw new Exception("Invalid district selected");
        }
        $districtCode = $districtData['district_code'];
    }
    
    // Generate the farmer code - FIXED CALL
    $farmerCode = generateFarmerCode($stateCode, $districtCode, $conn);
    
    if ($farmerCode) {
        echo json_encode([
            'success' => true,
            'farmer_code' => $farmerCode,
            'state_code' => $stateCode,
            'district_code' => $districtCode
        ]);
    } else {
        throw new Exception("Farmer code generation returned empty");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>