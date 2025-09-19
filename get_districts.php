<?php
// get_districts.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/farmer_code.php';

header('Content-Type: application/json');

if (!isset($_GET['state_id']) || empty($_GET['state_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'State ID is required']);
    exit;
}

$stateId = filter_var($_GET['state_id'], FILTER_VALIDATE_INT);

if (!$stateId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid State ID']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Use the function from farmer_code.php
    $districts = getDistrictsByState($stateId, $conn);
    echo json_encode($districts);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch districts: ' . $e->getMessage()]);
}
?>