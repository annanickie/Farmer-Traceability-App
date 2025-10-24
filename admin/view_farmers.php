<?php
// admin/view_farmers.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $auth->redirect('../login.php');
}

$database = new Database();
$conn = $database->getConnection();

// Handle Excel Export
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    exportFarmersToExcel($conn);
    exit;
}

// Initialize variables
$farmers = [];
$error = '';
$success = '';
$search = '';
$district_filter = '';
$state_filter = '';
$category_filter = '';
$gender_filter = '';
$product_filter = '';

// Handle farmer deletion
if (isset($_POST['delete_farmer'])) {
    try {
        $farmer_id = $_POST['farmer_id'];
        
        // Begin transaction
        $conn->beginTransaction();
        
        // First delete related records
        $query = "DELETE FROM cultivation_records WHERE farmer_id = :farmer_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':farmer_id', $farmer_id);
        $stmt->execute();
        
        // Then delete the farmer
        $query = "DELETE FROM farmers WHERE id = :farmer_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':farmer_id', $farmer_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $success = "Farmer and all related records deleted successfully.";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Error deleting farmer: " . $e->getMessage();
    }
}

try {
    // Build query with filters
    $query = "SELECT * FROM farmers WHERE 1=1";
    $params = [];
    
    // Search filter
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $_GET['search'];
        $search_term = "%$search%";
        $query .= " AND (farmer_name LIKE :search OR farmer_code LIKE :search OR village LIKE :search OR mobile LIKE :search OR aadhaar LIKE :search)";
        $params[':search'] = $search_term;
    }
    
    // District filter
    if (isset($_GET['district']) && !empty($_GET['district'])) {
        $district_filter = $_GET['district'];
        $query .= " AND district = :district";
        $params[':district'] = $district_filter;
    }
    
    // State filter
    if (isset($_GET['state']) && !empty($_GET['state'])) {
        $state_filter = $_GET['state'];
        $query .= " AND state = :state";
        $params[':state'] = $state_filter;
    }
    
    // Category filter
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $category_filter = $_GET['category'];
        $query .= " AND farmer_category = :category";
        $params[':category'] = $category_filter;
    }
    
    // Gender filter
    if (isset($_GET['gender']) && !empty($_GET['gender'])) {
        $gender_filter = $_GET['gender'];
        $query .= " AND gender = :gender";
        $params[':gender'] = $gender_filter;
    }
    
    // Product filter
    if (isset($_GET['product']) && !empty($_GET['product'])) {
        $product_filter = $_GET['product'];
        $query .= " AND product_name LIKE :product";
        $params[':product'] = "%$product_filter%";
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    $farmers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique values for filters with counts
    $districtQuery = "SELECT district, COUNT(*) as count FROM farmers WHERE district IS NOT NULL AND district != '' GROUP BY district ORDER BY district";
    $stateQuery = "SELECT state, COUNT(*) as count FROM farmers WHERE state IS NOT NULL AND state != '' GROUP BY state ORDER BY state";
    $categoryQuery = "SELECT farmer_category, COUNT(*) as count FROM farmers WHERE farmer_category IS NOT NULL AND farmer_category != '' GROUP BY farmer_category ORDER BY farmer_category";
    $genderQuery = "SELECT gender, COUNT(*) as count FROM farmers WHERE gender IS NOT NULL AND gender != '' GROUP BY gender ORDER BY gender";
    $productQuery = "SELECT product_name, COUNT(*) as count FROM farmers WHERE product_name IS NOT NULL AND product_name != '' GROUP BY product_name ORDER BY product_name";
    
    $districtStmt = $conn->query($districtQuery);
    $stateStmt = $conn->query($stateQuery);
    $categoryStmt = $conn->query($categoryQuery);
    $genderStmt = $conn->query($genderQuery);
    $productStmt = $conn->query($productQuery);
    
    $districts = $districtStmt->fetchAll(PDO::FETCH_ASSOC);
    $states = $stateStmt->fetchAll(PDO::FETCH_ASSOC);
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    $genders = $genderStmt->fetchAll(PDO::FETCH_ASSOC);
    $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Excel Export Function with ALL database fields
function exportFarmersToExcel($conn) {
    try {
        // Get all farmers data with ALL fields
        $query = "SELECT 
                    id,
                    farmer_code,
                    farmer_name,
                    dob,
                    age,
                    gender,
                    social_category,
                    farmer_category,
                    mobile,
                    email,
                    aadhaar,
                    pan,
                    full_address,
                    village,
                    block,
                    district,
                    state,
                    pincode,
                    land_area,
                    area_unit,
                    dag_no,
                    patta_no,
                    land_type,
                    irrigation_source,
                    account_holder,
                    account_number,
                    bank_name,
                    bank_branch,
                    ifsc_code,
                    family_size,
                    dependents,
                    primary_occupation,
                    training_received,
                    training_subject,
                    training_year,
                    under_institute,
                    institute_name,
                    institute_address,
                    contact_person,
                    contact_number,
                    farmer_potential,
                    product_name,
                    product_variety,
                    production_mt,
                    production_area,
                    soil_report_path,
                    sowing_time,
                    harvesting_time,
                    product_training,
                    product_remarks,
                    aadhaar_file_path,
                    pan_file_path,
                    passbook_file_path,
                    land_docs_path,
                    DATE(created_at) as registration_date,
                    DATE(updated_at) as last_updated
                  FROM farmers 
                  ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $farmers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="farmers_complete_data_' . date('Y-m-d') . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Start Excel output
        echo "<table border='1'>";
        
        // Header row with ALL your fields
        echo "<tr style='background-color: #0e9c4d; color: white; font-weight: bold;'>";
        echo "<th>ID</th>";
        echo "<th>Farmer Code</th>";
        echo "<th>Farmer Name</th>";
        echo "<th>Date of Birth</th>";
        echo "<th>Age</th>";
        echo "<th>Gender</th>";
        echo "<th>Social Category</th>";
        echo "<th>Farmer Category</th>";
        echo "<th>Mobile</th>";
        echo "<th>Email</th>";
        echo "<th>Aadhaar Number</th>";
        echo "<th>PAN Number</th>";
        echo "<th>Full Address</th>";
        echo "<th>Village</th>";
        echo "<th>Block</th>";
        echo "<th>District</th>";
        echo "<th>State</th>";
        echo "<th>Pincode</th>";
        echo "<th>Land Area</th>";
        echo "<th>Area Unit</th>";
        echo "<th>DAG Number</th>";
        echo "<th>Patta Number</th>";
        echo "<th>Land Type</th>";
        echo "<th>Irrigation Source</th>";
        echo "<th>Account Holder</th>";
        echo "<th>Account Number</th>";
        echo "<th>Bank Name</th>";
        echo "<th>Bank Branch</th>";
        echo "<th>IFSC Code</th>";
        echo "<th>Family Size</th>";
        echo "<th>Dependents</th>";
        echo "<th>Primary Occupation</th>";
        echo "<th>Training Received</th>";
        echo "<th>Training Subject</th>";
        echo "<th>Training Year</th>";
        echo "<th>Under Institute</th>";
        echo "<th>Institute Name</th>";
        echo "<th>Institute Address</th>";
        echo "<th>Contact Person</th>";
        echo "<th>Contact Number</th>";
        echo "<th>Farmer Potential</th>";
        echo "<th>Product Name</th>";
        echo "<th>Product Variety</th>";
        echo "<th>Production (MT)</th>";
        echo "<th>Production Area</th>";
        echo "<th>Soil Report Path</th>";
        echo "<th>Sowing Time</th>";
        echo "<th>Harvesting Time</th>";
        echo "<th>Product Training</th>";
        echo "<th>Product Remarks</th>";
        echo "<th>Aadhaar File Path</th>";
        echo "<th>PAN File Path</th>";
        echo "<th>Passbook File Path</th>";
        echo "<th>Land Documents Path</th>";
        echo "<th>Registration Date</th>";
        echo "<th>Last Updated</th>";
        echo "</tr>";
        
        // Data rows
        foreach ($farmers as $farmer) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($farmer['id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['farmer_code'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['farmer_name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['dob'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['age'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['gender'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['social_category'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['farmer_category'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['mobile'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['email'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['aadhaar'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['pan'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['full_address'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['village'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['block'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['district'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['state'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['pincode'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['land_area'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['area_unit'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['dag_no'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['patta_no'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['land_type'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['irrigation_source'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['account_holder'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['account_number'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['bank_name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['bank_branch'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['ifsc_code'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['family_size'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['dependents'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['primary_occupation'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['training_received'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['training_subject'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['training_year'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['under_institute'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['institute_name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['institute_address'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['contact_person'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['contact_number'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['farmer_potential'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['product_name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['product_variety'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['production_mt'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['production_area'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['soil_report_path'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['sowing_time'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['harvesting_time'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['product_training'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['product_remarks'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['aadhaar_file_path'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['pan_file_path'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['passbook_file_path'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['land_docs_path'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['registration_date'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($farmer['last_updated'] ?? '') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        exit;
        
    } catch (Exception $e) {
        // Log error
        error_log("Excel export error: " . $e->getMessage());
        
        // Show error in Excel file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="farmers_data_' . date('Y-m-d') . '.xls"');
        echo "<table border='1'>";
        echo "<tr><th>Error</th></tr>";
        echo "<tr><td>Failed to export data: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
        echo "</table>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Farmers - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0e9c4d;
            --secondary-color: #0a6e38;
            --light-color: #f5f9f7;
            --dark-color: #333;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            padding: 20px 0;
            box-shadow: var(--shadow);
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
        }
        
        .sidebar-header img {
            height: 40px;
            margin-right: 10px;
        }
        
        .sidebar-header h3 {
            font-size: 18px;
            color: white;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-nav li {
            margin-bottom: 5px;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            transition: background-color 0.3s;
        }
        
        .sidebar-nav a:hover {
            background-color: var(--secondary-color);
        }
        
        .sidebar-nav a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-nav a.active {
            background-color: var(--secondary-color);
            border-left: 4px solid white;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        /* Header Styles */
        .header {
            background-color: white;
            box-shadow: var(--shadow);
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: var(--primary-color);
            font-size: 24px;
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 15px;
            color: #666;
        }
        
        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-excel {
            background-color: #1d6f42;
            border-color: #1d6f42;
            color: white;
        }
        
        .btn-excel:hover {
            background-color: #155f35;
            border-color: #155f35;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        /* Enhanced Filter Section */
        .filter-section {
            background-color: white;
            box-shadow: var(--shadow);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .filter-section h5 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group {
            margin-bottom: 0;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-color);
            font-size: 14px;
        }
        
        .filter-count {
            font-size: 12px;
            color: #6c757d;
            margin-left: 5px;
        }
        
        .form-select-enhanced {
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 8px 12px;
            transition: all 0.3s ease;
        }
        
        .form-select-enhanced:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(14, 156, 77, 0.25);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box .form-control {
            padding-left: 40px;
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 15px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        /* Table Styles */
        .table-container {
            background-color: white;
            box-shadow: var(--shadow);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(10, 110, 56, 0.05);
        }
        
        .badge-success {
            background-color: var(--primary-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Active Filter Badges */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .filter-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .filter-badge .remove {
            cursor: pointer;
            font-weight: bold;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 10px 0;
            }
            
            .sidebar-nav {
                display: flex;
                overflow-x: auto;
                padding: 10px 0;
            }
            
            .sidebar-nav li {
                margin-bottom: 0;
                margin-right: 5px;
            }
            
            .sidebar-nav a {
                padding: 10px 15px;
                border-radius: 4px;
                white-space: nowrap;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .user-info {
                margin-top: 15px;
                flex-direction: column;
            }
            
            .user-info span {
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="https://pgpindia.co/wp-content/uploads/2021/10/WhatsApp_Image_2025-05-29_at_14.03.45_96bf6913-removebg-preview-e1749970605607.png" alt="PGP India Logo">
                <h3>PGP Admin</h3>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="manage_states.php"><i class="fas fa-map-marked"></i> Manage States</a></li>
                <li><a href="manage_districts.php"><i class="fas fa-map-marker-alt"></i> Manage Districts</a></li>
                <li><a href="view_farmers.php" class="active"><i class="fas fa-user-tag"></i> View Farmers</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1><i class="fas fa-user-tag me-2"></i> Farmers Management</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    <a href="../logout.php" class="btn btn-sm btn-danger">Logout</a>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($farmers); ?></div>
                    <div class="stat-label">Total Farmers</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo count(array_filter($farmers, function($farmer) {
                            return $farmer['farmer_category'] === 'Small Farmer';
                        })); ?>
                    </div>
                    <div class="stat-label">Small Farmers</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo count(array_unique(array_column($farmers, 'district'))); ?>
                    </div>
                    <div class="stat-label">Districts Covered</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo count(array_unique(array_column($farmers, 'state'))); ?>
                    </div>
                    <div class="stat-label">States Covered</div>
                </div>
            </div>
            
            <!-- Enhanced Filter Section -->
            <div class="filter-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Filter Farmers</h5>
                    <div>
                        <!-- Excel Export Button -->
                        <a href="?export=excel" class="btn btn-excel">
                            <i class="fas fa-file-excel me-1"></i> Export to Excel
                        </a>
                    </div>
                </div>
                
                <form method="GET" action="">
                    <div class="filter-row">
                        <!-- Search Box -->
                        <div class="filter-group search-box">
                            <label for="search">Search Farmers</label>
                            <div class="position-relative">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Search by name, code, village, Aadhaar..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        
                        <!-- State Filter -->
                        <div class="filter-group">
                            <label for="state">State <span class="filter-count">(<?php echo count($states); ?>)</span></label>
                            <select class="form-select form-select-enhanced" id="state" name="state">
                                <option value="">All States</option>
                                <?php foreach ($states as $state): ?>
                                    <option value="<?php echo htmlspecialchars($state['state']); ?>" 
                                            <?php echo $state_filter === $state['state'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($state['state']); ?> (<?php echo $state['count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- District Filter -->
                        <div class="filter-group">
                            <label for="district">District <span class="filter-count">(<?php echo count($districts); ?>)</span></label>
                            <select class="form-select form-select-enhanced" id="district" name="district">
                                <option value="">All Districts</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo htmlspecialchars($district['district']); ?>" 
                                            <?php echo $district_filter === $district['district'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($district['district']); ?> (<?php echo $district['count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <!-- Category Filter -->
                        <div class="filter-group">
                            <label for="category">Farmer Category <span class="filter-count">(<?php echo count($categories); ?>)</span></label>
                            <select class="form-select form-select-enhanced" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['farmer_category']); ?>" 
                                            <?php echo $category_filter === $category['farmer_category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['farmer_category']); ?> (<?php echo $category['count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Gender Filter -->
                        <div class="filter-group">
                            <label for="gender">Gender <span class="filter-count">(<?php echo count($genders); ?>)</span></label>
                            <select class="form-select form-select-enhanced" id="gender" name="gender">
                                <option value="">All Genders</option>
                                <?php foreach ($genders as $gender): ?>
                                    <option value="<?php echo htmlspecialchars($gender['gender']); ?>" 
                                            <?php echo $gender_filter === $gender['gender'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($gender['gender']); ?> (<?php echo $gender['count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Product Filter -->
                        <div class="filter-group">
                            <label for="product">Product <span class="filter-count">(<?php echo count($products); ?>)</span></label>
                            <select class="form-select form-select-enhanced" id="product" name="product">
                                <option value="">All Products</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                            <?php echo $product_filter === $product['product_name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($product['product_name']); ?> (<?php echo $product['count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <button type="submit" class="btn btn-success me-2">
                                <i class="fas fa-search me-1"></i> Apply Filters
                            </button>
                            <a href="view_farmers.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sync me-1"></i> Reset All
                            </a>
                        </div>
                        <div class="text-muted small">
                            Showing <?php echo count($farmers); ?> farmers
                        </div>
                    </div>
                    
                    <!-- Active Filters -->
                    <?php if ($search || $district_filter || $state_filter || $category_filter || $gender_filter || $product_filter): ?>
                    <div class="active-filters mt-3">
                        <strong>Active Filters:</strong>
                        <?php if ($search): ?>
                            <span class="filter-badge">
                                Search: "<?php echo htmlspecialchars($search); ?>"
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['search' => ''])); ?>" class="remove text-white">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if ($state_filter): ?>
                            <span class="filter-badge">
                                State: <?php echo htmlspecialchars($state_filter); ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['state' => ''])); ?>" class="remove text-white">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if ($district_filter): ?>
                            <span class="filter-badge">
                                District: <?php echo htmlspecialchars($district_filter); ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['district' => ''])); ?>" class="remove text-white">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if ($category_filter): ?>
                            <span class="filter-badge">
                                Category: <?php echo htmlspecialchars($category_filter); ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => ''])); ?>" class="remove text-white">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if ($gender_filter): ?>
                            <span class="filter-badge">
                                Gender: <?php echo htmlspecialchars($gender_filter); ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['gender' => ''])); ?>" class="remove text-white">×</a>
                            </span>
                        <?php endif; ?>
                        <?php if ($product_filter): ?>
                            <span class="filter-badge">
                                Product: <?php echo htmlspecialchars($product_filter); ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['product' => ''])); ?>" class="remove text-white">×</a>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Farmers Table -->
            <div class="table-container">
                <div class="table-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> Farmers List</h5>
                    <span class="badge bg-light text-dark"><?php echo count($farmers); ?> Farmers</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Farmer Code</th>
                                <th>Name & Details</th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th>Category</th>
                                <th>Land Area</th>
                                <th>Product</th>
                                <th>Registered On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($farmers) > 0): ?>
                                <?php foreach ($farmers as $farmer): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-success"><?php echo htmlspecialchars($farmer['farmer_code']); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($farmer['farmer_name']); ?></strong>
                                            <?php if (!empty($farmer['gender'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($farmer['gender']); ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($farmer['age'])): ?>
                                                <br><small class="text-muted">Age: <?php echo htmlspecialchars($farmer['age']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($farmer['mobile'])): ?>
                                                <div><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($farmer['mobile']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($farmer['email'])): ?>
                                                <div class="small text-muted"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($farmer['email']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($farmer['village']); ?></div>
                                            <div class="small text-muted">
                                                <?php echo htmlspecialchars($farmer['district']); ?>, <?php echo htmlspecialchars($farmer['state']); ?>
                                            </div>
                                            <?php if (!empty($farmer['block'])): ?>
                                                <div class="small text-muted">Block: <?php echo htmlspecialchars($farmer['block']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($farmer['farmer_category']); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($farmer['land_area'])): ?>
                                                <?php echo htmlspecialchars($farmer['land_area']); ?> <?php echo htmlspecialchars($farmer['area_unit']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($farmer['product_name'])): ?>
                                                <div><?php echo htmlspecialchars($farmer['product_name']); ?></div>
                                                <?php if (!empty($farmer['product_variety'])): ?>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($farmer['product_variety']); ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($farmer['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view_farmer_details.php?id=<?php echo $farmer['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_farmer.php?id=<?php echo $farmer['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="farmer_id" value="<?php echo $farmer['id']; ?>">
                                                    <button type="submit" name="delete_farmer" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this farmer and all their records?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <i class="fas fa-users fa-2x text-muted mb-3"></i>
                                        <h5>No farmers found</h5>
                                        <p class="text-muted">No farmers match your search criteria.</p>
                                        <a href="?export=excel" class="btn btn-excel mt-2">
                                            <i class="fas fa-file-excel me-1"></i> Export All Farmers to Excel
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit form when filters change (optional)
        document.addEventListener('DOMContentLoaded', function() {
            const filters = ['state', 'district', 'category', 'gender', 'product'];
            
            filters.forEach(filter => {
                const element = document.getElementById(filter);
                if (element) {
                    element.addEventListener('change', function() {
                        this.form.submit();
                    });
                }
            });
        });
    </script>
</body>
</html>