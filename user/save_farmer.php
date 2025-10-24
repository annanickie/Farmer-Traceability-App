<?php
// save_farmer.php - FIXED PARAMETER COUNT

// Start session
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define root path
$root_path = dirname(__DIR__);

// Include required files
require_once $root_path . '/includes/config.php';
require_once $root_path . '/includes/database.php';
require_once $root_path . '/includes/farmer_code.php';

// Set JSON header
header('Content-Type: application/json');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

// Initialize variables
$success = false;
$message = '';
$farmer_id = null;
$farmer_code = '';
$errors = [];

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Unable to connect to database.');
    }

    // Validate required fields
    $required_fields = [
        'farmer_name', 'dob', 'gender', 'social_category', 'farmer_category',
        'mobile', 'full_address', 'village', 'block', 'district', 'state', 'pincode'
    ];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Required field missing: " . str_replace('_', ' ', $field);
        }
    }
    
    if (!empty($errors)) {
        throw new Exception(implode(', ', $errors));
    }
    
    // Check for duplicate mobile
    if (!empty($_POST['mobile'])) {
        $check_mobile = "SELECT id FROM farmers WHERE mobile = ?";
        $stmt_check_mobile = $conn->prepare($check_mobile);
        $stmt_check_mobile->execute([$_POST['mobile']]);
        
        if ($stmt_check_mobile->rowCount() > 0) {
            throw new Exception('Mobile number already registered in the system.');
        }
    }
    
    // Generate farmer code
    $farmer_code = !empty($_POST['farmer_code']) ? $_POST['farmer_code'] : generateFarmerCode($conn, $_POST['state'], $_POST['district']);
    
    if (!$farmer_code) {
        throw new Exception('Failed to generate farmer code.');
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Prepare SQL query - COUNTED CORRECTLY: 48 columns + NOW() = 49 parameters
    $query = "INSERT INTO farmers (
        farmer_code, farmer_name, dob, age, gender, social_category, farmer_category,
        mobile, email, aadhaar, pan, full_address, village, block, district, state,
        pincode, land_area, area_unit, dag_no, patta_no, land_type, irrigation_source,
        account_holder, account_number, bank_name, bank_branch, ifsc_code, family_size,
        dependents, primary_occupation, training_received, training_subject, training_year,
        under_institute, institute_name, institute_address, contact_person, contact_number,
        farmer_potential, product_name, product_variety, production_mt, production_area,
        sowing_time, harvesting_time, product_training, product_remarks, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($query);
    
    // Bind parameters - EXACTLY 48 parameters + NOW() = 49 total
    $params = [
        $farmer_code,
        $_POST['farmer_name'],
        $_POST['dob'],
        !empty($_POST['age']) ? $_POST['age'] : null,
        $_POST['gender'],
        $_POST['social_category'],
        $_POST['farmer_category'],
        $_POST['mobile'],
        !empty($_POST['email']) ? $_POST['email'] : null,
        !empty($_POST['aadhaar']) ? $_POST['aadhaar'] : null,
        !empty($_POST['pan']) ? $_POST['pan'] : null,
        $_POST['full_address'],
        $_POST['village'],
        $_POST['block'],
        $_POST['district'],
        $_POST['state'],
        $_POST['pincode'],
        !empty($_POST['land_area']) ? $_POST['land_area'] : null,
        !empty($_POST['area_unit']) ? $_POST['area_unit'] : null,
        !empty($_POST['dag_no']) ? $_POST['dag_no'] : null,
        !empty($_POST['patta_no']) ? $_POST['patta_no'] : null,
        !empty($_POST['land_type']) ? $_POST['land_type'] : null,
        !empty($_POST['irrigation_source']) ? $_POST['irrigation_source'] : null,
        !empty($_POST['account_holder']) ? $_POST['account_holder'] : null,
        !empty($_POST['account_number']) ? $_POST['account_number'] : null,
        !empty($_POST['bank_name']) ? $_POST['bank_name'] : null,
        !empty($_POST['bank_branch']) ? $_POST['bank_branch'] : null,
        !empty($_POST['ifsc_code']) ? $_POST['ifsc_code'] : null,
        !empty($_POST['family_size']) ? $_POST['family_size'] : null,
        !empty($_POST['dependents']) ? $_POST['dependents'] : null,
        !empty($_POST['primary_occupation']) ? $_POST['primary_occupation'] : null,
        !empty($_POST['training_received']) ? $_POST['training_received'] : null,
        !empty($_POST['training_subject']) ? $_POST['training_subject'] : null,
        !empty($_POST['training_year']) ? $_POST['training_year'] : null,
        !empty($_POST['under_institute']) ? $_POST['under_institute'] : null,
        !empty($_POST['institute_name']) ? $_POST['institute_name'] : null,
        !empty($_POST['institute_address']) ? $_POST['institute_address'] : null,
        !empty($_POST['contact_person']) ? $_POST['contact_person'] : null,
        !empty($_POST['contact_number']) ? $_POST['contact_number'] : null,
        !empty($_POST['farmer_potential']) ? $_POST['farmer_potential'] : null,
        // Production details
        !empty($_POST['product_name']) ? $_POST['product_name'] : null,
        !empty($_POST['product_variety']) ? $_POST['product_variety'] : null,
        !empty($_POST['production_mt']) ? $_POST['production_mt'] : null,
        !empty($_POST['production_area']) ? $_POST['production_area'] : null,
        !empty($_POST['sowing_time']) ? $_POST['sowing_time'] : null,
        !empty($_POST['harvesting_time']) ? $_POST['harvesting_time'] : null,
        !empty($_POST['product_training']) ? $_POST['product_training'] : null,
        !empty($_POST['product_remarks']) ? $_POST['product_remarks'] : null
    ];
    
    // Debug: Check parameter count
    error_log("Number of parameters: " . count($params));
    error_log("Parameters: " . print_r(array_keys($_POST), true));
    
    $success = $stmt->execute($params);
    
    if ($success) {
        $farmer_id = $conn->lastInsertId();
        
        // Handle file uploads
        $upload_results = handleFileUploads($farmer_id);
        
        // Handle additional documents using existing documents table
        if (!empty($_POST['document_type']) && is_array($_POST['document_type'])) {
            saveAdditionalDocuments($conn, $farmer_id, $_POST['document_type'], $_FILES['document_file']);
        }
        
        // Handle soil report file
        if (isset($_FILES['soil_report_file']) && $_FILES['soil_report_file']['error'] === UPLOAD_ERR_OK) {
            handleSoilReportFile($conn, $farmer_id);
        }
        
        // Commit transaction
        $conn->commit();
        
        $success = true;
        $message = 'Farmer registered successfully!';
        
    } else {
        $errorInfo = $stmt->errorInfo();
        throw new Exception('Failed to insert farmer record: ' . ($errorInfo[2] ?? 'Unknown error'));
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollBack();
    }
    
    $success = false;
    $message = $e->getMessage();
    error_log('Farmer Registration Error: ' . $e->getMessage());
}

// Return JSON response
echo json_encode([
    'success' => $success,
    'message' => $message,
    'farmer_id' => $farmer_id,
    'farmer_code' => $farmer_code,
    'errors' => $errors
]);
exit;

// ==================== HELPER FUNCTIONS ====================

/**
 * Handle file uploads
 */
function handleFileUploads($farmer_id) {
    $results = ['success' => [], 'errors' => []];
    
    // Create upload directory
    $upload_dir = dirname(__DIR__) . '/uploads/farmers/' . $farmer_id . '/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Handle simple file uploads
    $file_fields = ['aadhaar_file', 'pan_file', 'passbook_file', 'land_docs'];
    
    foreach ($file_fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$field];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $file_name = $field . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            // Validate file types
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $results['success'][$field] = $file_path;
                    updateFilePathInDatabase($GLOBALS['conn'], $farmer_id, $field, $file_path);
                } else {
                    $results['errors'][] = "Failed to upload {$field}";
                }
            } else {
                $results['errors'][] = "Invalid file type for {$field}. Allowed: " . implode(', ', $allowed_extensions);
            }
        }
    }
    
    return $results;
}

/**
 * Update file path in database
 */
function updateFilePathInDatabase($conn, $farmer_id, $field, $file_path) {
    $field_mapping = [
        'aadhaar_file' => 'aadhaar_file_path',
        'pan_file' => 'pan_file_path',
        'passbook_file' => 'passbook_file_path',
        'land_docs' => 'land_docs_path'
    ];
    
    if (isset($field_mapping[$field])) {
        $db_field = $field_mapping[$field];
        $query = "UPDATE farmers SET {$db_field} = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$file_path, $farmer_id]);
    }
}

/**
 * Handle soil report file
 */
function handleSoilReportFile($conn, $farmer_id) {
    $upload_dir = dirname(__DIR__) . '/uploads/farmers/' . $farmer_id . '/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['soil_report_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $file_name = 'soil_report_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        if (in_array($file_extension, $allowed_extensions)) {
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Update soil report path in farmers table
                $query = "UPDATE farmers SET soil_report_path = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$file_path, $farmer_id]);
            }
        }
    }
}

/**
 * Save additional documents using existing documents table
 */
function saveAdditionalDocuments($conn, $farmer_id, $document_types, $document_files) {
    $query = "INSERT INTO documents (
        farmer_id, document_type, file_name, file_path, uploaded_at
    ) VALUES (?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($query);
    $upload_dir = dirname(__DIR__) . '/uploads/farmers/' . $farmer_id . '/documents/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    foreach ($document_types as $index => $document_type) {
        if (!empty($document_type) && 
            isset($document_files['name'][$index]) && 
            !empty($document_files['name'][$index])) {
            
            $file = [
                'name' => $document_files['name'][$index],
                'type' => $document_files['type'][$index],
                'tmp_name' => $document_files['tmp_name'][$index],
                'error' => $document_files['error'][$index],
                'size' => $document_files['size'][$index]
            ];
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $file_name = 'document_' . $index . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
                if (in_array($file_extension, $allowed_extensions)) {
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        $stmt->execute([$farmer_id, $document_type, $file['name'], $file_path]);
                    }
                }
            }
        }
    }
}
?>