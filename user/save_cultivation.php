<?php
// user/save_cultivation.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Initialize response
$response = ['success' => false, 'message' => ''];

try {
    // Check if form is submitted
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    if (empty($_POST['farmer_id']) || empty($_POST['farmer_name']) || empty($_POST['farmer_code'])) {
        throw new Exception('Farmer information is required');
    }

    // Begin transaction
    $conn->beginTransaction();

    // Handle file uploads first
    $uploadedFiles = handleCultivationUploads($_POST['farmer_id']);

    // Prepare cultivation data for cultivation_records table
    $cultivationData = [
        'farmer_id' => $_POST['farmer_id'],
        'farmer_code' => $_POST['farmer_code'],
        
        // Farmer Information
        'link_type' => $_POST['link_type'] ?? '',
        'fpc_name' => $_POST['fpc_name'] ?? '',
        
        // Land Holding Details
        'land_area' => !empty($_POST['land_area']) ? $_POST['land_area'] : null,
        'area_unit' => $_POST['area_unit'] ?? '',
        'dag_no' => $_POST['dag_no'] ?? '',
        'patta_no' => $_POST['patta_no'] ?? '',
        'land_type' => $_POST['land_type'] ?? '',
        'land_geotag' => $_POST['land_geotag'] ?? '',
        'land_geotag_photo' => $uploadedFiles['land_geotag_photo'] ?? '',
        'land_photo' => $uploadedFiles['land_photo'] ?? '',
        'land_video' => $uploadedFiles['land_video'] ?? '',
        'land_docs' => $uploadedFiles['land_docs'] ?? '',
        'survey_verification' => $_POST['survey_verification'] ?? '',
        
        // Soil Test Details
        'soil_sample_collected' => !empty($_POST['soil_sample_collected']) ? 1 : 0,
        'sample_code_id' => $_POST['sample_code_id'] ?? '',
        'collection_date' => !empty($_POST['collection_date']) ? $_POST['collection_date'] : null,
        'soil_geotag' => $_POST['soil_geotag'] ?? '',
        'soil_geotag_photo' => $uploadedFiles['soil_geotag_photo'] ?? '',
        'collected_by' => $_POST['collected_by'] ?? '',
        'collection_photo' => $uploadedFiles['collection_photo'] ?? '',
        'collection_video' => $uploadedFiles['collection_video'] ?? '',
        'soil_testing' => !empty($_POST['soil_testing']) ? 1 : 0,
        'testing_date' => !empty($_POST['testing_date']) ? $_POST['testing_date'] : null,
        'lab_name' => $_POST['lab_name'] ?? '',
        'soil_test_report' => !empty($_POST['soil_test_report']) ? 1 : 0,
        'report_id' => $_POST['report_id'] ?? '',
        'report_upload' => $uploadedFiles['report_upload'] ?? '',
        'soil_key_values' => $_POST['soil_key_values'] ?? '',
        
        // Land Preparation
        'land_preparation' => !empty($_POST['land_preparation']) ? 1 : 0,
        'preparation_date' => !empty($_POST['preparation_date']) ? $_POST['preparation_date'] : null,
        'preparation_photo' => $uploadedFiles['preparation_photo'] ?? '',
        'preparation_remark' => $_POST['preparation_remark'] ?? '',
        
        // Irrigation
        'irrigation' => !empty($_POST['irrigation']) ? 1 : 0,
        'irrigation_date' => !empty($_POST['irrigation_date']) ? $_POST['irrigation_date'] : null,
        'irrigation_photo' => $uploadedFiles['irrigation_photo'] ?? '',
        'irrigation_remark' => $_POST['irrigation_remark'] ?? '',
        
        // Distribution to Farmer
        'planting_material' => !empty($_POST['planting_material']) ? 1 : 0,
        'material_type' => $_POST['material_type'] ?? '',
        'material_qty' => !empty($_POST['material_qty']) ? $_POST['material_qty'] : null,
        'distribution_date' => !empty($_POST['distribution_date']) ? $_POST['distribution_date'] : null,
        'distribution_photo' => $uploadedFiles['distribution_photo'] ?? '',
        'field_record' => $_POST['field_record'] ?? '',
        'voucher_upload' => $uploadedFiles['voucher_upload'] ?? '',
        
        // Production Details
        'sowing' => !empty($_POST['sowing']) ? 1 : 0,
        'crop_name' => $_POST['crop_name'] ?? '',
        'crop_variety' => $_POST['crop_variety'] ?? '',
        'sowing_date' => !empty($_POST['sowing_date']) ? $_POST['sowing_date'] : null,
        'sowing_geotag' => $_POST['sowing_geotag'] ?? '',
        'sowing_geotag_photo' => $uploadedFiles['sowing_geotag_photo'] ?? '',
        'sowing_photo' => $uploadedFiles['sowing_photo'] ?? '',
        'sowing_video' => $uploadedFiles['sowing_video'] ?? '',
        'sowing_remark' => $_POST['sowing_remark'] ?? '',
        
        // Training
        'training_topic' => $_POST['training_topic'] ?? '',
        'training_mode' => $_POST['training_mode'] ?? '',
        'training_conducted_by' => $_POST['training_conducted_by'] ?? '',
        
        // Monitoring Visits
        'monitoring_officer_1' => $_POST['monitoring_officer_1'] ?? '',
        'monitoring_date_1' => !empty($_POST['monitoring_date_1']) ? $_POST['monitoring_date_1'] : null,
        'monitoring_geotag_1' => $_POST['monitoring_geotag_1'] ?? '',
        'monitoring_geotag_photo_1' => $uploadedFiles['monitoring_geotag_photo_1'] ?? '',
        'monitoring_notes_1' => $_POST['monitoring_notes_1'] ?? '',
        'monitoring_officer_2' => $_POST['monitoring_officer_2'] ?? '',
        'monitoring_date_2' => !empty($_POST['monitoring_date_2']) ? $_POST['monitoring_date_2'] : null,
        'monitoring_geotag_2' => $_POST['monitoring_geotag_2'] ?? '',
        'monitoring_geotag_photo_2' => $uploadedFiles['monitoring_geotag_photo_2'] ?? '',
        'monitoring_notes_2' => $_POST['monitoring_notes_2'] ?? '',
        'pest_observation' => $_POST['pest_observation'] ?? '',
        
        // Harvesting Details
        'harvesting' => !empty($_POST['harvesting']) ? 1 : 0,
        'harvesting_date' => !empty($_POST['harvesting_date']) ? $_POST['harvesting_date'] : null,
        'harvesting_geotag' => $_POST['harvesting_geotag'] ?? '',
        'harvesting_geotag_photo' => $uploadedFiles['harvesting_geotag_photo'] ?? '',
        'crop_details' => $_POST['crop_details'] ?? '',
        'total_quantity' => !empty($_POST['total_quantity']) ? $_POST['total_quantity'] : null,
        'yield' => !empty($_POST['yield']) ? $_POST['yield'] : null,
        'yield_photo' => $uploadedFiles['yield_photo'] ?? '',
        'crop_status' => $_POST['crop_status'] ?? '',
        'harvest_training_topic' => $_POST['harvest_training_topic'] ?? '',
        'harvest_training_mode' => $_POST['harvest_training_mode'] ?? '',
        'harvest_training_conducted_by' => $_POST['harvest_training_conducted_by'] ?? ''
    ];

    // Insert cultivation record
    $cultivationId = insertCultivationRecord($conn, $cultivationData);
    
    if (!$cultivationId) {
        throw new Exception('Failed to save cultivation record');
    }

    // Also create a simple record in the cultivation table for dashboard
    createSimpleCultivationRecord($conn, $_POST['farmer_id'], $cultivationData);

    // Commit transaction
    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Cultivation data saved successfully!';
    $response['cultivation_id'] = $cultivationId;

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollBack();
    }
    
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Cultivation Save Error: ' . $e->getMessage());
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;

/**
 * Insert cultivation record into cultivation_records table
 */
function insertCultivationRecord($conn, $data) {
    $columns = [];
    $placeholders = [];
    $values = [];

    foreach ($data as $key => $value) {
        // Only include fields that have values
        if ($value !== '' && $value !== null) {
            $columns[] = $key;
            $placeholders[] = ":$key";
            $values[":$key"] = $value;
        }
    }

    if (empty($columns)) {
        throw new Exception('No data to insert');
    }

    $query = "INSERT INTO cultivation_records (" . implode(', ', $columns) . ") 
              VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $conn->prepare($query);
    
    foreach ($values as $param => $value) {
        $stmt->bindValue($param, $value);
    }

    if ($stmt->execute()) {
        return $conn->lastInsertId();
    } else {
        throw new Exception('Database insert failed: ' . implode(', ', $stmt->errorInfo()));
    }
}

/**
 * Create a simple record in the cultivation table for dashboard display
 */
function createSimpleCultivationRecord($conn, $farmerId, $data) {
    try {
        $query = "INSERT INTO cultivation (
            farmer_id, 
            crop_type, 
            cultivation_area, 
            planting_date, 
            expected_harvest_date,
            created_at
        ) VALUES (
            :farmer_id, 
            :crop_type, 
            :cultivation_area, 
            :planting_date, 
            :expected_harvest_date,
            NOW()
        )";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':farmer_id', $farmerId);
        $stmt->bindValue(':crop_type', $data['crop_name'] ?? '');
        $stmt->bindValue(':cultivation_area', $data['land_area'] ?? null);
        $stmt->bindValue(':planting_date', $data['sowing_date'] ?? null);
        $stmt->bindValue(':expected_harvest_date', $data['harvesting_date'] ?? null);

        $stmt->execute();
        return $conn->lastInsertId();
    } catch (Exception $e) {
        // Log error but don't stop the main process
        error_log('Error creating simple cultivation record: ' . $e->getMessage());
        return false;
    }
}

/**
 * Handle file uploads for cultivation
 */
function handleCultivationUploads($farmerId) {
    $upload_dir = dirname(__DIR__) . '/uploads/cultivations/' . $farmerId . '/';
    $uploaded_files = [];

    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Define all file fields and their upload names
    $file_fields = [
        // Land holding files
        'land_docs' => 'land_documents',
        'land_photo' => 'land_photo',
        'land_video' => 'land_video',
        'land_geotag_photo' => 'land_geotag_photo',
        
        // Soil test files
        'collection_photo' => 'soil_collection_photo',
        'collection_video' => 'soil_collection_video',
        'report_upload' => 'soil_report',
        'soil_geotag_photo' => 'soil_geotag_photo',
        
        // Land preparation files
        'preparation_photo' => 'land_prep_photo',
        
        // Irrigation files
        'irrigation_photo' => 'irrigation_photo',
        
        // Distribution files
        'distribution_photo' => 'distribution_photo',
        'voucher_upload' => 'voucher',
        
        // Sowing files
        'sowing_photo' => 'sowing_photo',
        'sowing_video' => 'sowing_video',
        'sowing_geotag_photo' => 'sowing_geotag_photo',
        
        // Monitoring files
        'monitoring_geotag_photo_1' => 'monitoring_geotag_photo_1',
        'monitoring_geotag_photo_2' => 'monitoring_geotag_photo_2',
        
        // Harvesting files
        'yield_photo' => 'yield_photo',
        'harvesting_geotag_photo' => 'harvesting_geotag_photo'
    ];

    foreach ($file_fields as $field => $prefix) {
        if (!empty($_FILES[$field]['name'])) {
            $file = $_FILES[$field];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_name = $prefix . '_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;

            // Validate and move file
            if ($file['error'] === UPLOAD_ERR_OK) {
                // Check file size (max 10MB)
                if ($file['size'] > 10 * 1024 * 1024) {
                    continue; // Skip this file if too large
                }
                
                // Check file type
                $allowed_image_types = ['jpg', 'jpeg', 'png', 'gif'];
                $allowed_doc_types = ['pdf', 'doc', 'docx'];
                $allowed_video_types = ['mp4', 'avi', 'mov'];
                
                $is_allowed = false;
                if (in_array($file_extension, $allowed_image_types) && strpos($prefix, 'photo') !== false) {
                    $is_allowed = true;
                } elseif (in_array($file_extension, $allowed_doc_types) && strpos($prefix, 'doc') !== false) {
                    $is_allowed = true;
                } elseif (in_array($file_extension, $allowed_video_types) && strpos($prefix, 'video') !== false) {
                    $is_allowed = true;
                } else {
                    $is_allowed = true; // Allow other types for flexibility
                }
                
                if ($is_allowed && move_uploaded_file($file['tmp_name'], $file_path)) {
                    $uploaded_files[$field] = $file_path;
                }
            }
        }
    }

    return $uploaded_files;
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}