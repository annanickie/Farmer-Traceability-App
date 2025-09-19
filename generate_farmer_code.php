<?php
// generate_farmer_code.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/farmer_code.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['state_id']) || !isset($_POST['district_id']) || 
    !isset($_POST['state_code']) || !isset($_POST['district_code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$stateId = $_POST['state_id'];
$districtId = $_POST['district_id'];
$stateCode = $_POST['state_code'];
$districtCode = $_POST['district_code'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if connection was successful
    if ($conn === null) {
        throw new Exception("Database connection failed");
    }
    
    // Generate the farmer code using your existing function
    $farmerCode = generateFarmerCode($stateCode, $districtCode, $conn);
    
    echo json_encode([
        'success' => true,
        'farmer_code' => $farmerCode,
        'state_code' => $stateCode,
        'district_code' => $districtCode
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to generate farmer code: ' . $e->getMessage()]);
}
?>