<?php
// get_states.php
require_once 'includes/config.php';
require_once 'includes/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();

    $query = "SELECT id, state_code, state_name FROM states WHERE is_active = 1 ORDER BY state_name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $states = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($states);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch states']);
}
?>