<?php
// user/vendor_registration.php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/database.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    $auth->redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Generate vendor code
function generateVendorCode() {
    $database = new Database();
    $conn = $database->getConnection();
    
    try {
        // Get the latest vendor code
        $query = "SELECT vendor_code FROM vendors ORDER BY id DESC LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $current_year = date('y');
        if ($stmt->rowCount() > 0) {
            $last_code = $stmt->fetch(PDO::FETCH_ASSOC)['vendor_code'];
            $parts = explode('-', $last_code);
            if (count($parts) === 3 && $parts[1] == $current_year) {
                $counter = intval($parts[2]) + 1;
            } else {
                $counter = 1;
            }
        } else {
            $counter = 1;
        }
        
        return "PGP-" . $current_year . "-" . str_pad($counter, 4, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        return "PGP-" . date('y') . "-0001";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_vendor'])) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Generate vendor code
        $vendor_code = generateVendorCode();
        
        // Prepare form data
        $form_data = [
            'user_id' => $user_id,
            'vendor_code' => $vendor_code,
            'proposed_by' => sanitizeInput($_POST['proposed_by']),
            'firm_name' => sanitizeInput($_POST['firm_name']),
            'registered_address' => sanitizeInput($_POST['registered_address']),
            'city' => sanitizeInput($_POST['city']),
            'state' => sanitizeInput($_POST['state']),
            'pin' => sanitizeInput($_POST['pin']),
            'landline' => sanitizeInput($_POST['landline']),
            'mobile' => sanitizeInput($_POST['mobile']),
            'fax' => sanitizeInput($_POST['fax']),
            'pancard' => sanitizeInput($_POST['pancard']),
            'gst_no' => sanitizeInput($_POST['gst_no']),
            'udyam' => sanitizeInput($_POST['udyam']),
            'msme_type' => sanitizeInput($_POST['msme_type']),
            'activity' => sanitizeInput($_POST['activity']),
            'vendor_type' => sanitizeInput($_POST['vendor_type']),
            'members' => sanitizeInput($_POST['members']),
            'department' => sanitizeInput($_POST['department']),
            'scheme' => sanitizeInput($_POST['scheme']),
            'business_address' => sanitizeInput($_POST['business_address']),
            'business_city' => sanitizeInput($_POST['business_city']),
            'business_state' => sanitizeInput($_POST['business_state']),
            'business_pin' => sanitizeInput($_POST['business_pin']),
            'business_landline' => sanitizeInput($_POST['business_landline']),
            'business_mobile' => sanitizeInput($_POST['business_mobile']),
            'business_fax' => sanitizeInput($_POST['business_fax']),
            'business_email' => sanitizeInput($_POST['business_email']),
            'service_type' => sanitizeInput($_POST['service_type']),
            'bank_holder' => sanitizeInput($_POST['bank_holder']),
            'bank_name' => sanitizeInput($_POST['bank_name']),
            'bank_address' => sanitizeInput($_POST['bank_address']),
            'bank_account' => sanitizeInput($_POST['bank_account']),
            'ifsc' => sanitizeInput($_POST['ifsc']),
            'vendor_contact_name' => sanitizeInput($_POST['vendor_contact_name']),
            'vendor_email' => sanitizeInput($_POST['vendor_email']),
            'vendor_contact_no' => sanitizeInput($_POST['vendor_contact_no']),
            'comments_biz_head' => sanitizeInput($_POST['comments_biz_head']),
            'comments_quality' => sanitizeInput($_POST['comments_quality']),
            'comments_finance' => sanitizeInput($_POST['comments_finance']),
            'sign_proposer' => sanitizeInput($_POST['sign_proposer']),
            'sign_biz_head' => sanitizeInput($_POST['sign_biz_head']),
            'sign_cfo' => sanitizeInput($_POST['sign_cfo']),
            'created_by' => sanitizeInput($_POST['created_by']),
            'checked_by' => sanitizeInput($_POST['checked_by']),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle key persons
        for ($i = 1; $i <= 2; $i++) {
            $form_data["keyperson{$i}_name"] = sanitizeInput($_POST["keyperson{$i}_name"] ?? '');
            $form_data["keyperson{$i}_designation"] = sanitizeInput($_POST["keyperson{$i}_designation"] ?? '');
            $form_data["keyperson{$i}_contact"] = sanitizeInput($_POST["keyperson{$i}_contact"] ?? '');
            $form_data["keyperson{$i}_email"] = sanitizeInput($_POST["keyperson{$i}_email"] ?? '');
        }
        
        // Handle references
        for ($i = 1; $i <= 2; $i++) {
            $form_data["ref{$i}_name"] = sanitizeInput($_POST["ref{$i}_name"] ?? '');
            $form_data["ref{$i}_email"] = sanitizeInput($_POST["ref{$i}_email"] ?? '');
            $form_data["ref{$i}_mobile"] = sanitizeInput($_POST["ref{$i}_mobile"] ?? '');
        }
        
        // Handle product list
        $product_list = [];
        for ($i = 0; $i < 5; $i++) {
            if (!empty($_POST["product_{$i}_name"])) {
                $product_list[] = [
                    'sl_no' => $i + 1,
                    'product_name' => sanitizeInput($_POST["product_{$i}_name"]),
                    'quantity' => sanitizeInput($_POST["product_{$i}_qty"]),
                    'unit' => sanitizeInput($_POST["product_{$i}_unit"])
                ];
            }
        }
        $form_data['product_list'] = json_encode($product_list);
        
        // Handle file uploads
        $uploaded_files = [];
        $upload_dir = "../uploads/vendors/{$user_id}/{$vendor_code}/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_fields = [
            'registration_certificate', 'pan_document', 'gst_document', 
            'udyam_document', 'members_list', 'cancelled_cheque'
        ];
        
        foreach ($file_fields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file_name = basename($_FILES[$field]['name']);
                $file_path = $upload_dir . $file_name;
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $file_path)) {
                    $uploaded_files[$field] = $file_path;
                }
            }
        }
        $form_data['uploaded_files'] = json_encode($uploaded_files);
        
        // Insert into database
        $columns = implode(', ', array_keys($form_data));
        $placeholders = ':' . implode(', :', array_keys($form_data));
        
        $query = "INSERT INTO vendors ($columns) VALUES ($placeholders)";
        $stmt = $conn->prepare($query);
        
        foreach ($form_data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        if ($stmt->execute()) {
            $success = "Vendor registered successfully! Vendor Code: <strong>{$vendor_code}</strong>";
            
            // Generate PDF (optional)
            generateVendorPDF($form_data, $vendor_code, $upload_dir);
            
        } else {
            $error = "Failed to register vendor. Please try again.";
        }
        
    } catch (PDOException $e) {
        error_log("Vendor registration error: " . $e->getMessage());
        $error = "Database error occurred. Please try again.";
    }
}

// Function to generate PDF (basic implementation)
function generateVendorPDF($form_data, $vendor_code, $upload_dir) {
    // This is a basic implementation - you might want to use a PDF library like TCPDF
    $pdf_content = "
        PGP VENDOR REGISTRATION FORM
        =============================
        
        Vendor Code: {$vendor_code}
        Date Created: {$form_data['created_at']}
        
        BASIC INFORMATION:
        -----------------
        Firm Name: {$form_data['firm_name']}
        Proposed By: {$form_data['proposed_by']}
        Registered Address: {$form_data['registered_address']}
        City: {$form_data['city']}, State: {$form_data['state']}, PIN: {$form_data['pin']}
        PAN: {$form_data['pancard']}, GST: {$form_data['gst_no']}
        MSME Type: {$form_data['msme_type']}, Activity: {$form_data['activity']}
        Vendor Type: {$form_data['vendor_type']}
        
        CONTACT INFORMATION:
        -------------------
        Email: {$form_data['business_email']}
        Mobile: {$form_data['business_mobile']}
        
        BANK DETAILS:
        -------------
        Bank Holder: {$form_data['bank_holder']}
        Bank Name: {$form_data['bank_name']}
        Account No: {$form_data['bank_account']}
        IFSC: {$form_data['ifsc']}
    ";
    
    file_put_contents($upload_dir . "{$vendor_code}.txt", $pdf_content);
    return true;
}

// Validation functions
function isValidGST($gst) {
    return preg_match('/^\d{2}[A-Z]{5}\d{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gst);
}

function isValidPAN($pan) {
    return preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan);
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isValidMobile($mobile) {
    return preg_match('/^[6-9]\d{9}$/', $mobile);
}

function isValidPIN($pin) {
    return preg_match('/^\d{6}$/', $pin);
}

function isValidIFSC($ifsc) {
    return preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Registration - PGP Farmer Traceability</title>
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
        }
        
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light-color);
        }
        
        .form-section h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(14, 156, 77, 0.2);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s;
            margin-right: 10px;
        }
        
        .btn:hover {
            background: var(--secondary-color);
        }
        
        .btn-clear {
            background: #6c757d;
        }
        
        .btn-clear:hover {
            background: #545b62;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .product-table th, .product-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        
        .product-table th {
            background-color: var(--light-color);
            font-weight: 600;
        }
        
        .upload-btn {
            background: #007bff;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .upload-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="https://pgpindia.co/wp-content/uploads/2021/10/WhatsApp_Image_2025-05-29_at_14.03.45_96bf6913-removebg-preview-e1749970605607.png" alt="PGP India Logo">
                <h3>PGP Farmer</h3>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="farmer_registration.php"><i class="fas fa-user-plus"></i> Farmer Registration</a></li>
                <li><a href="cultivation_form.php"><i class="fas fa-seedling"></i> Cultivation Form</a></li>
                <li><a href="vendor_registration.php" class="active"><i class="fas fa-users"></i> Vendor Registration</a></li>
                <li><a href="vendor_management.php"><i class="fas fa-list"></i> Vendor Management</a></li>
                <li><a href="history.php"><i class="fas fa-history"></i> History</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1><i class="fas fa-users"></i> Vendor Registration Form</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="../logout.php" class="btn">Logout</a>
                </div>
            </div>
            
            <?php if ($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" action="" enctype="multipart/form-data" onsubmit="return validateForm()">
                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Proposed By *</label>
                                <input type="text" name="proposed_by" required>
                            </div>
                            <div class="form-group">
                                <label>Vendor Code</label>
                                <input type="text" value="<?php echo generateVendorCode(); ?>" readonly style="background-color: #f8f9fa;">
                            </div>
                            <div class="form-group">
                                <label>Date of Vendor Creation</label>
                                <input type="text" value="<?php echo date('Y-m-d'); ?>" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Name of the Firm *</label>
                            <input type="text" name="firm_name" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Registration Certificate</label>
                                <input type="file" name="registration_certificate" class="upload-btn">
                            </div>
                            <div class="form-group">
                                <label>Entity Registration Number</label>
                                <input type="text" name="entity_reg_number">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Registered Address Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Registered Address</h3>
                        
                        <div class="form-group">
                            <label>Registered Address *</label>
                            <textarea name="registered_address" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>PIN Code *</label>
                                <input type="text" name="pin" pattern="\d{6}" title="6-digit PIN code" required>
                            </div>
                            <div class="form-group">
                                <label>City *</label>
                                <input type="text" name="city" required>
                            </div>
                            <div class="form-group">
                                <label>State *</label>
                                <input type="text" name="state" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Landline No.</label>
                                <input type="text" name="landline">
                            </div>
                            <div class="form-group">
                                <label>Mobile No. *</label>
                                <input type="text" name="mobile" pattern="[6-9]\d{9}" title="10-digit mobile number" required>
                            </div>
                            <div class="form-group">
                                <label>Fax No.</label>
                                <input type="text" name="fax">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Business Documents Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-file-contract"></i> Business Documents</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>PAN Card *</label>
                                <input type="text" name="pancard" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" title="Valid PAN card number" required>
                            </div>
                            <div class="form-group">
                                <label>Upload PAN</label>
                                <input type="file" name="pan_document" class="upload-btn">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>GST No. *</label>
                                <input type="text" name="gst_no" required>
                            </div>
                            <div class="form-group">
                                <label>Upload GST</label>
                                <input type="file" name="gst_document" class="upload-btn">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Udyam Registration No.</label>
                                <input type="text" name="udyam">
                            </div>
                            <div class="form-group">
                                <label>Upload Udyam</label>
                                <input type="file" name="udyam_document" class="upload-btn">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>MSME Type</label>
                                <select name="msme_type">
                                    <option value="">Select Type</option>
                                    <option value="MICRO">MICRO</option>
                                    <option value="SMALL">SMALL</option>
                                    <option value="MEDIUM">MEDIUM</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Major Activity</label>
                                <select name="activity">
                                    <option value="">Select Activity</option>
                                    <option value="Manufacturing">Manufacturing</option>
                                    <option value="Services">Services</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vendor Type Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-tag"></i> Vendor Type</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Type of Vendor</label>
                                <select name="vendor_type">
                                    <option value="">Select Type</option>
                                    <option value="INDIVIDUAL">INDIVIDUAL</option>
                                    <option value="FPC">FPC</option>
                                    <option value="TRADERS">TRADERS</option>
                                    <option value="NGO">NGO</option>
                                    <option value="OTHERS">OTHERS</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Members</label>
                                <input type="text" name="members">
                            </div>
                            <div class="form-group">
                                <label>Upload Members List</label>
                                <input type="file" name="members_list" class="upload-btn">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Department</label>
                                <input type="text" name="department">
                            </div>
                            <div class="form-group">
                                <label>Scheme</label>
                                <input type="text" name="scheme">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Business Address Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-building"></i> Business Address (if different)</h3>
                        
                        <div class="form-group">
                            <label>Business Address</label>
                            <textarea name="business_address" rows="3"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>PIN Code</label>
                                <input type="text" name="business_pin" pattern="\d{6}" title="6-digit PIN code">
                            </div>
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="business_city">
                            </div>
                            <div class="form-group">
                                <label>State</label>
                                <input type="text" name="business_state">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Landline No.</label>
                                <input type="text" name="business_landline">
                            </div>
                            <div class="form-group">
                                <label>Mobile No.</label>
                                <input type="text" name="business_mobile" pattern="[6-9]\d{9}" title="10-digit mobile number">
                            </div>
                            <div class="form-group">
                                <label>Fax No.</label>
                                <input type="text" name="business_fax">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="business_email" required>
                        </div>
                    </div>
                    
                    <!-- Key Persons Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-user-tie"></i> Key Persons</h3>
                        
                        <?php for ($i = 1; $i <= 2; $i++): ?>
                        <div class="person-section" style="margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 5px;">
                            <h4>Key Person <?php echo $i; ?></h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" name="keyperson<?php echo $i; ?>_name">
                                </div>
                                <div class="form-group">
                                    <label>Designation</label>
                                    <input type="text" name="keyperson<?php echo $i; ?>_designation">
                                </div>
                                <div class="form-group">
                                    <label>Contact</label>
                                    <input type="text" name="keyperson<?php echo $i; ?>_contact">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="keyperson<?php echo $i; ?>_email">
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Service and Products Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-cogs"></i> Services & Products</h3>
                        
                        <div class="form-group">
                            <label>Type of Service</label>
                            <input type="text" name="service_type">
                        </div>
                        
                        <h4>Product List</h4>
                        <table class="product-table">
                            <thead>
                                <tr>
                                    <th>SL.NO.</th>
                                    <th>PRODUCT NAME</th>
                                    <th>QTY.</th>
                                    <th>UNIT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><input type="text" name="product_<?php echo $i; ?>_name" style="width: 100%; border: none; padding: 5px;"></td>
                                    <td><input type="text" name="product_<?php echo $i; ?>_qty" style="width: 100%; border: none; padding: 5px;"></td>
                                    <td><input type="text" name="product_<?php echo $i; ?>_unit" style="width: 100%; border: none; padding: 5px;"></td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- References Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-handshake"></i> References</h3>
                        
                        <?php for ($i = 1; $i <= 2; $i++): ?>
                        <div class="reference-section" style="margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 5px;">
                            <h4>Reference <?php echo $i; ?></h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" name="ref<?php echo $i; ?>_name">
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="ref<?php echo $i; ?>_email">
                                </div>
                                <div class="form-group">
                                    <label>Mobile</label>
                                    <input type="text" name="ref<?php echo $i; ?>_mobile">
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Bank Details Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-university"></i> Bank Details</h3>
                        
                        <div class="form-group">
                            <label>Bank A/C Holder *</label>
                            <input type="text" name="bank_holder" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Bank Name *</label>
                                <input type="text" name="bank_name" required>
                            </div>
                            <div class="form-group">
                                <label>Bank Address</label>
                                <input type="text" name="bank_address">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Bank A/C No *</label>
                                <input type="text" name="bank_account" required>
                            </div>
                            <div class="form-group">
                                <label>IFSC Code *</label>
                                <input type="text" name="ifsc" pattern="[A-Z]{4}0[A-Z0-9]{6}" title="Valid IFSC code" required>
                            </div>
                            <div class="form-group">
                                <label>Upload Cancelled Cheque</label>
                                <input type="file" name="cancelled_cheque" class="upload-btn">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vendor Contact Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-address-book"></i> Vendor Contact</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Vendor Contact Name *</label>
                                <input type="text" name="vendor_contact_name" required>
                            </div>
                            <div class="form-group">
                                <label>Vendor Email *</label>
                                <input type="email" name="vendor_email" required>
                            </div>
                            <div class="form-group">
                                <label>Vendor Contact No *</label>
                                <input type="text" name="vendor_contact_no" pattern="[6-9]\d{9}" title="10-digit mobile number" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comments Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-comments"></i> Comments</h3>
                        
                        <div class="form-group">
                            <label>Comments by Business Head</label>
                            <textarea name="comments_biz_head" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Comments on Quality/Etc.</label>
                            <textarea name="comments_quality" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Comments by Finance</label>
                            <textarea name="comments_finance" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <!-- Signatures Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-signature"></i> Signatures</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Name and Sign of Proposer</label>
                                <input type="text" name="sign_proposer">
                            </div>
                            <div class="form-group">
                                <label>Business Head</label>
                                <input type="text" name="sign_biz_head">
                            </div>
                            <div class="form-group">
                                <label>Sign of CFO</label>
                                <input type="text" name="sign_cfo">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Created By</label>
                                <input type="text" name="created_by" value="<?php echo htmlspecialchars($_SESSION['first_name+last_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Checked By</label>
                                <input type="text" name="checked_by">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-section" style="border-bottom: none; text-align: center;">
                        <button type="submit" name="save_vendor" class="btn">
                            <i class="fas fa-save"></i> Save Vendor
                        </button>
                        <button type="button" class="btn btn-clear" onclick="clearForm()">
                            <i class="fas fa-trash"></i> Clear Form
                        </button>
                        <button type="button" class="btn" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Form
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function validateForm() {
            const email = document.querySelector('input[name="business_email"]');
            const mobile = document.querySelector('input[name="mobile"]');
            const pan = document.querySelector('input[name="pancard"]');
            const ifsc = document.querySelector('input[name="ifsc"]');
            
            // Email validation
            if (!isValidEmail(email.value)) {
                alert('Please enter a valid email address');
                email.focus();
                return false;
            }
            
            // Mobile validation
            if (!isValidMobile(mobile.value)) {
                alert('Please enter a valid 10-digit mobile number');
                mobile.focus();
                return false;
            }
            
            // PAN validation
            if (pan.value && !isValidPAN(pan.value)) {
                alert('Please enter a valid PAN card number');
                pan.focus();
                return false;
            }
            
            // IFSC validation
            if (ifsc.value && !isValidIFSC(ifsc.value)) {
                alert('Please enter a valid IFSC code');
                ifsc.focus();
                return false;
            }
            
            return true;
        }
        
        function isValidEmail(email) {
            const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            return re.test(email);
        }
        
        function isValidMobile(mobile) {
            const re = /^[6-9]\d{9}$/;
            return re.test(mobile);
        }
        
        function isValidPAN(pan) {
            const re = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
            return re.test(pan);
        }
        
        function isValidIFSC(ifsc) {
            const re = /^[A-Z]{4}0[A-Z0-9]{6}$/;
            return re.test(ifsc);
        }
        
        function clearForm() {
            if (confirm('Are you sure you want to clear the entire form?')) {
                document.querySelector('form').reset();
            }
        }
        
        // Auto-format PAN to uppercase
        document.querySelector('input[name="pancard"]').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });
        
        // Auto-format IFSC to uppercase
        document.querySelector('input[name="ifsc"]').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>