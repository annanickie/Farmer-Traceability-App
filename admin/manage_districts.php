<?php
// admin/manage_districts.php

// Fix the file paths - use relative paths from the admin directory
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin using your Auth class
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $auth->redirect('../login.php');
}

$database = new Database();
$conn = $database->getConnection();
$error = '';
$success = '';

// Get all states for dropdown
$states = [];
try {
    $statesQuery = "SELECT * FROM states WHERE is_active = 1 ORDER BY state_name";
    $statesStmt = $conn->prepare($statesQuery);
    $statesStmt->execute();
    $states = $statesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching states: ' . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_district'])) {
        $districtCode = strtoupper(trim($_POST['district_code']));
        $districtName = trim($_POST['district_name']);
        $stateId = $_POST['state_id'];
        
        if (empty($districtCode) || empty($districtName) || empty($stateId)) {
            $error = 'Please fill in all fields.';
        } else {
            try {
                $query = "INSERT INTO districts (district_code, district_name, state_id, created_by) 
                         VALUES (:district_code, :district_name, :state_id, :created_by)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':district_code', $districtCode);
                $stmt->bindParam(':district_name', $districtName);
                $stmt->bindParam(':state_id', $stateId, PDO::PARAM_INT);
                $stmt->bindValue(':created_by', $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success = 'District added successfully!';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate entry
                    $error = 'District code already exists for this state.';
                } else {
                    $error = 'Error adding district: ' . $e->getMessage();
                }
            }
        }
    }
    
    if (isset($_POST['toggle_district'])) {
        $districtId = $_POST['district_id'];
        $isActive = $_POST['is_active'] ? 0 : 1;
        
        try {
            $query = "UPDATE districts SET is_active = :is_active WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':is_active', $isActive, PDO::PARAM_INT);
            $stmt->bindParam(':id', $districtId, PDO::PARAM_INT);
            $stmt->execute();
            
            // Refresh page to show updated status
            header("Location: manage_districts.php");
            exit();
        } catch (PDOException $e) {
            $error = 'Error updating district: ' . $e->getMessage();
        }
    }
}

// Get all districts with state information
$districts = [];
try {
    $query = "SELECT d.*, s.state_code, s.state_name 
              FROM districts d 
              JOIN states s ON d.state_id = s.id 
              ORDER BY s.state_name, d.district_name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching districts: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Districts - PGP Farmer Traceability</title>
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
            color: var(--dark-color);
        }
        
        .logout-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: var(--secondary-color);
        }
        
        /* Alert Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Card Styles */
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .card-header h3 {
            color: var(--primary-color);
            margin-left: 10px;
            margin-bottom: 0;
        }
        
        .card-header i {
            color: var(--primary-color);
            font-size: 20px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: var(--light-color);
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-toggle {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
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
            
            .table-container {
                overflow-x: auto;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <!-- PGP India Logo -->
                <img src="https://pgpindia.co/wp-content/uploads/2021/10/WhatsApp_Image_2025-05-29_at_14.03.45_96bf6913-removebg-preview-e1749970605607.png" alt="PGP India Logo">
                <h3>PGP Admin</h3>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="manage_states.php"><i class="fas fa-map-marked"></i> Manage States</a></li>
                <li><a href="manage_districts.php" class="active"><i class="fas fa-map-marker-alt"></i> Manage Districts</a></li>
                <li><a href="view_farmers.php"><i class="fas fa-user-tag"></i> View Farmers</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Manage Districts</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Add District Form -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Add New District</h3>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label for="state_id">State</label>
                        <select id="state_id" name="state_id" required>
                            <option value="">Select State</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?php echo $state['id']; ?>">
                                    <?php echo htmlspecialchars($state['state_code'] . ' - ' . $state['state_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="district_code">District Code (2-3 letters)</label> <input type="text" id="district_code" name="district_code" maxlength="3" required placeholder="e.g., MUM for Mumbai"> </div> <div class="form-group"> <label for="district_name">District Name</label> <input type="text" id="district_name" name="district_name" required placeholder="e.g., Mumbai"> </div> <button type="submit" name="add_district" class="btn">Add District</button> </form> </div><!-- Districts List --><div class="card"> <div class="card-header"> <i class="fas fa-list"></i> <h3>All Districts</h3> </div> <div class="table-container"> <table> <thead> <tr> <th>ID</th> <th>State</th> <th>District Code</th> <th>District Name</th> <th>Status</th> <th>Actions</th> </tr> </thead> <tbody> <?php if (count($districts) > 0): ?> <?php foreach ($districts as $district): ?> <tr> <td><?php echo $district['id']; ?></td> <td><?php echo htmlspecialchars($district['state_code'] . ' - ' . $district['state_name']); ?></td> <td><?php echo htmlspecialchars($district['district_code']); ?></td> <td><?php echo htmlspecialchars($district['district_name']); ?></td> <td> <span class="status-toggle <?php echo $district['is_active'] ? 'status-active' : 'status-inactive'; ?>"> <?php echo $district['is_active'] ? 'Active' : 'Inactive'; ?> </span> </td> <td> <form method="POST" style="display: inline;"> <input type="hidden" name="district_id" value="<?php echo $district['id']; ?>"> <input type="hidden" name="is_active" value="<?php echo $district['is_active']; ?>"> <button type="submit" name="toggle_district" class="btn btn-sm"> <?php echo $district['is_active'] ? 'Deactivate' : 'Activate'; ?> </button> </form> </td> </tr> <?php endforeach; ?> <?php else: ?> <tr> <td colspan="6" style="text-align: center;">No districts found. Please add districts.</td> </tr> <?php endif; ?> </tbody> </table> </div> </div> </div> </div><script> // Simple form validation document.addEventListener('DOMContentLoaded', function() { const form = document.querySelector('form'); if (form) { form.addEventListener('submit', function(e) { const stateSelect = document.getElementById('state_id'); const districtCode = document.getElementById('district_code'); const districtName = document.getElementById('district_name'); if (stateSelect.value === '') { e.preventDefault(); alert('Please select a state.'); stateSelect.focus(); return false; } if (districtCode.value.trim() === '') { e.preventDefault(); alert('Please enter a district code.'); districtCode.focus(); return false; } if (districtName.value.trim() === '') { e.preventDefault(); alert('Please enter a district name.'); districtName.focus(); return false; } // Validate district code format (2-3 uppercase letters) const codeRegex = /^[A-Z]{2,3}$/; if (!codeRegex.test(districtCode.value.trim())) { e.preventDefault(); alert('District code must be 2-3 uppercase letters.'); districtCode.focus(); return false; } }); } }); </script></body> </html>