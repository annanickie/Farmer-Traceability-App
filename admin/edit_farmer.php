<?php
// admin/edit_farmer.php
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

// Check if farmer ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: view_farmers.php');
    exit;
}

$farmer_id = $_GET['id'];
$farmer = [];
$error = '';
$success = '';

// Get farmer details with JOINs
try {
    $query = "SELECT 
                f.*,
                d.district_name,
                s.state_name
              FROM farmers f
              LEFT JOIN districts d ON f.district = d.id
              LEFT JOIN states s ON f.state = s.id
              WHERE f.id = :farmer_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':farmer_id', $farmer_id);
    $stmt->execute();
    
    $farmer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$farmer) {
        $error = "Farmer not found.";
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get dropdown data
$districts = [];
$states = [];
$categories = [];
$genders = [];

try {
    // Get districts
    $districtQuery = "SELECT id, district_name FROM districts ORDER BY district_name";
    $districtStmt = $conn->query($districtQuery);
    $districts = $districtStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get states
    $stateQuery = "SELECT id, state_name FROM states ORDER BY state_name";
    $stateStmt = $conn->query($stateQuery);
    $states = $stateStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique categories from farmers table
    $categoryQuery = "SELECT DISTINCT farmer_category FROM farmers WHERE farmer_category IS NOT NULL AND farmer_category != '' ORDER BY farmer_category";
    $categoryStmt = $conn->query($categoryQuery);
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique genders from farmers table
    $genderQuery = "SELECT DISTINCT gender FROM farmers WHERE gender IS NOT NULL AND gender != '' ORDER BY gender";
    $genderStmt = $conn->query($genderQuery);
    $genders = $genderStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error loading dropdown data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Collect form data
        $farmer_name = $_POST['farmer_name'] ?? '';
        $farmer_code = $_POST['farmer_code'] ?? '';
        $dob = $_POST['dob'] ?? '';
        $age = $_POST['age'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $social_category = $_POST['social_category'] ?? '';
        $farmer_category = $_POST['farmer_category'] ?? '';
        $mobile = $_POST['mobile'] ?? '';
        $email = $_POST['email'] ?? '';
        $aadhaar = $_POST['aadhaar'] ?? '';
        $pan = $_POST['pan'] ?? '';
        $full_address = $_POST['full_address'] ?? '';
        $village = $_POST['village'] ?? '';
        $block = $_POST['block'] ?? '';
        $district = $_POST['district'] ?? '';
        $state = $_POST['state'] ?? '';
        $pincode = $_POST['pincode'] ?? '';
        $land_area = $_POST['land_area'] ?? '';
        $area_unit = $_POST['area_unit'] ?? '';
        $dag_no = $_POST['dag_no'] ?? '';
        $patta_no = $_POST['patta_no'] ?? '';
        $land_type = $_POST['land_type'] ?? '';
        $irrigation_source = $_POST['irrigation_source'] ?? '';
        $account_holder = $_POST['account_holder'] ?? '';
        $account_number = $_POST['account_number'] ?? '';
        $bank_name = $_POST['bank_name'] ?? '';
        $bank_branch = $_POST['bank_branch'] ?? '';
        $ifsc_code = $_POST['ifsc_code'] ?? '';
        $family_size = $_POST['family_size'] ?? '';
        $dependents = $_POST['dependents'] ?? '';
        $primary_occupation = $_POST['primary_occupation'] ?? '';
        $training_received = $_POST['training_received'] ?? '';
        $training_subject = $_POST['training_subject'] ?? '';
        $training_year = $_POST['training_year'] ?? '';
        $under_institute = $_POST['under_institute'] ?? '';
        $institute_name = $_POST['institute_name'] ?? '';
        $institute_address = $_POST['institute_address'] ?? '';
        $contact_person = $_POST['contact_person'] ?? '';
        $contact_number = $_POST['contact_number'] ?? '';
        $farmer_potential = $_POST['farmer_potential'] ?? '';
        $product_name = $_POST['product_name'] ?? '';
        $product_variety = $_POST['product_variety'] ?? '';
        $production_mt = $_POST['production_mt'] ?? '';
        $production_area = $_POST['production_area'] ?? '';
        $soil_report_path = $_POST['soil_report_path'] ?? '';
        $sowing_time = $_POST['sowing_time'] ?? '';
        $harvesting_time = $_POST['harvesting_time'] ?? '';
        $product_training = $_POST['product_training'] ?? '';
        $product_remarks = $_POST['product_remarks'] ?? '';

        // Update query
        $updateQuery = "UPDATE farmers SET 
                        farmer_name = :farmer_name,
                        farmer_code = :farmer_code,
                        dob = :dob,
                        age = :age,
                        gender = :gender,
                        social_category = :social_category,
                        farmer_category = :farmer_category,
                        mobile = :mobile,
                        email = :email,
                        aadhaar = :aadhaar,
                        pan = :pan,
                        full_address = :full_address,
                        village = :village,
                        block = :block,
                        district = :district,
                        state = :state,
                        pincode = :pincode,
                        land_area = :land_area,
                        area_unit = :area_unit,
                        dag_no = :dag_no,
                        patta_no = :patta_no,
                        land_type = :land_type,
                        irrigation_source = :irrigation_source,
                        account_holder = :account_holder,
                        account_number = :account_number,
                        bank_name = :bank_name,
                        bank_branch = :bank_branch,
                        ifsc_code = :ifsc_code,
                        family_size = :family_size,
                        dependents = :dependents,
                        primary_occupation = :primary_occupation,
                        training_received = :training_received,
                        training_subject = :training_subject,
                        training_year = :training_year,
                        under_institute = :under_institute,
                        institute_name = :institute_name,
                        institute_address = :institute_address,
                        contact_person = :contact_person,
                        contact_number = :contact_number,
                        farmer_potential = :farmer_potential,
                        product_name = :product_name,
                        product_variety = :product_variety,
                        production_mt = :production_mt,
                        production_area = :production_area,
                        soil_report_path = :soil_report_path,
                        sowing_time = :sowing_time,
                        harvesting_time = :harvesting_time,
                        product_training = :product_training,
                        product_remarks = :product_remarks,
                        updated_at = NOW()
                        WHERE id = :farmer_id";

        $stmt = $conn->prepare($updateQuery);
        
        // Bind parameters
        $stmt->bindParam(':farmer_name', $farmer_name);
        $stmt->bindParam(':farmer_code', $farmer_code);
        $stmt->bindParam(':dob', $dob);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':social_category', $social_category);
        $stmt->bindParam(':farmer_category', $farmer_category);
        $stmt->bindParam(':mobile', $mobile);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':aadhaar', $aadhaar);
        $stmt->bindParam(':pan', $pan);
        $stmt->bindParam(':full_address', $full_address);
        $stmt->bindParam(':village', $village);
        $stmt->bindParam(':block', $block);
        $stmt->bindParam(':district', $district);
        $stmt->bindParam(':state', $state);
        $stmt->bindParam(':pincode', $pincode);
        $stmt->bindParam(':land_area', $land_area);
        $stmt->bindParam(':area_unit', $area_unit);
        $stmt->bindParam(':dag_no', $dag_no);
        $stmt->bindParam(':patta_no', $patta_no);
        $stmt->bindParam(':land_type', $land_type);
        $stmt->bindParam(':irrigation_source', $irrigation_source);
        $stmt->bindParam(':account_holder', $account_holder);
        $stmt->bindParam(':account_number', $account_number);
        $stmt->bindParam(':bank_name', $bank_name);
        $stmt->bindParam(':bank_branch', $bank_branch);
        $stmt->bindParam(':ifsc_code', $ifsc_code);
        $stmt->bindParam(':family_size', $family_size);
        $stmt->bindParam(':dependents', $dependents);
        $stmt->bindParam(':primary_occupation', $primary_occupation);
        $stmt->bindParam(':training_received', $training_received);
        $stmt->bindParam(':training_subject', $training_subject);
        $stmt->bindParam(':training_year', $training_year);
        $stmt->bindParam(':under_institute', $under_institute);
        $stmt->bindParam(':institute_name', $institute_name);
        $stmt->bindParam(':institute_address', $institute_address);
        $stmt->bindParam(':contact_person', $contact_person);
        $stmt->bindParam(':contact_number', $contact_number);
        $stmt->bindParam(':farmer_potential', $farmer_potential);
        $stmt->bindParam(':product_name', $product_name);
        $stmt->bindParam(':product_variety', $product_variety);
        $stmt->bindParam(':production_mt', $production_mt);
        $stmt->bindParam(':production_area', $production_area);
        $stmt->bindParam(':soil_report_path', $soil_report_path);
        $stmt->bindParam(':sowing_time', $sowing_time);
        $stmt->bindParam(':harvesting_time', $harvesting_time);
        $stmt->bindParam(':product_training', $product_training);
        $stmt->bindParam(':product_remarks', $product_remarks);
        $stmt->bindParam(':farmer_id', $farmer_id);

        if ($stmt->execute()) {
            $success = "Farmer details updated successfully!";
            // Refresh farmer data
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':farmer_id', $farmer_id);
            $stmt->execute();
            $farmer = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to update farmer details.";
        }
        
    } catch (Exception $e) {
        $error = "Error updating farmer: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Farmer - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0e9c4d;
            --secondary-color: #0a6e38;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .header h1 {
            color: var(--primary-color);
            font-size: 24px;
            margin: 0;
        }
        
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 20px;
        }
        
        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }
        
        .required::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="sidebar-header text-center mb-4">
                        <img src="https://pgpindia.co/wp-content/uploads/2021/10/WhatsApp_Image_2025-05-29_at_14.03.45_96bf6913-removebg-preview-e1749970605607.png" alt="PGP India Logo" class="img-fluid" style="max-height: 50px;">
                        <h5 class="text-white mt-2">PGP Admin</h5>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manage_users.php">
                                <i class="fas fa-users me-2"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manage_states.php">
                                <i class="fas fa-map-marked me-2"></i> Manage States
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="manage_districts.php">
                                <i class="fas fa-map-marker-alt me-2"></i> Manage Districts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" style="background-color: var(--secondary-color);" href="view_farmers.php">
                                <i class="fas fa-user-tag me-2"></i> View Farmers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Header -->
                <div class="header mt-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1><i class="fas fa-edit me-2"></i> Edit Farmer</h1>
                        <div class="d-flex gap-2">
                            <a href="view_farmer_details.php?id=<?php echo $farmer_id; ?>" class="btn btn-info">
                                <i class="fas fa-eye me-1"></i> View Details
                            </a>
                            <a href="view_farmers.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to List
                            </a>
                        </div>
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

                <?php if ($farmer): ?>
                <form method="POST" action="">
                    <!-- Basic Information -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-id-card me-2"></i> Basic Information
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required">Farmer Code</label>
                                    <input type="text" class="form-control" name="farmer_code" 
                                           value="<?php echo htmlspecialchars($farmer['farmer_code']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required">Farmer Name</label>
                                    <input type="text" class="form-control" name="farmer_name" 
                                           value="<?php echo htmlspecialchars($farmer['farmer_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="dob" 
                                           value="<?php echo htmlspecialchars($farmer['dob']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Age</label>
                                    <input type="number" class="form-control" name="age" 
                                           value="<?php echo htmlspecialchars($farmer['age']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender">
                                        <option value="">Select Gender</option>
                                        <?php foreach ($genders as $gender): ?>
                                            <option value="<?php echo htmlspecialchars($gender['gender']); ?>" 
                                                <?php echo $farmer['gender'] === $gender['gender'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($gender['gender']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Social Category</label>
                                    <input type="text" class="form-control" name="social_category" 
                                           value="<?php echo htmlspecialchars($farmer['social_category']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Farmer Category</label>
                                    <select class="form-select" name="farmer_category">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars($category['farmer_category']); ?>" 
                                                <?php echo $farmer['farmer_category'] === $category['farmer_category'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['farmer_category']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-address-book me-2"></i> Contact Information
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label required">Mobile Number</label>
                                    <input type="tel" class="form-control" name="mobile" 
                                           value="<?php echo htmlspecialchars($farmer['mobile']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($farmer['email']); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Aadhaar Number</label>
                                    <input type="text" class="form-control" name="aadhaar" 
                                           value="<?php echo htmlspecialchars($farmer['aadhaar']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">PAN Number</label>
                                    <input type="text" class="form-control" name="pan" 
                                           value="<?php echo htmlspecialchars($farmer['pan']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-map-marker-alt me-2"></i> Address Information
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Full Address</label>
                                <textarea class="form-control" name="full_address" rows="3"><?php echo htmlspecialchars($farmer['full_address']); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Village</label>
                                    <input type="text" class="form-control" name="village" 
                                           value="<?php echo htmlspecialchars($farmer['village']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Block</label>
                                    <input type="text" class="form-control" name="block" 
                                           value="<?php echo htmlspecialchars($farmer['block']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" class="form-control" name="pincode" 
                                           value="<?php echo htmlspecialchars($farmer['pincode']); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">District</label>
                                    <select class="form-select" name="district">
                                        <option value="">Select District</option>
                                        <?php foreach ($districts as $district): ?>
                                            <option value="<?php echo htmlspecialchars($district['id']); ?>" 
                                                <?php echo $farmer['district'] == $district['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($district['district_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($farmer['district_name'])): ?>
                                        <small class="text-muted">Current: <?php echo htmlspecialchars($farmer['district_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">State</label>
                                    <select class="form-select" name="state">
                                        <option value="">Select State</option>
                                        <?php foreach ($states as $state): ?>
                                            <option value="<?php echo htmlspecialchars($state['id']); ?>" 
                                                <?php echo $farmer['state'] == $state['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($state['state_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($farmer['state_name'])): ?>
                                        <small class="text-muted">Current: <?php echo htmlspecialchars($farmer['state_name']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Land Information -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-tractor me-2"></i> Land Information
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Land Area</label>
                                    <input type="number" step="0.01" class="form-control" name="land_area" 
                                           value="<?php echo htmlspecialchars($farmer['land_area']); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Area Unit</label>
                                    <select class="form-select" name="area_unit">
                                        <option value="">Select Unit</option>
                                        <option value="Acres" <?php echo $farmer['area_unit'] === 'Acres' ? 'selected' : ''; ?>>Acres</option>
                                        <option value="Hectares" <?php echo $farmer['area_unit'] === 'Hectares' ? 'selected' : ''; ?>>Hectares</option>
                                        <option value="Bigha" <?php echo $farmer['area_unit'] === 'Bigha' ? 'selected' : ''; ?>>Bigha</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Land Type</label>
                                    <input type="text" class="form-control" name="land_type" 
                                           value="<?php echo htmlspecialchars($farmer['land_type']); ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">DAG Number</label>
                                    <input type="text" class="form-control" name="dag_no" 
                                           value="<?php echo htmlspecialchars($farmer['dag_no']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Patta Number</label>
                                    <input type="text" class="form-control" name="patta_no" 
                                           value="<?php echo htmlspecialchars($farmer['patta_no']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Irrigation Source</label>
                                <input type="text" class="form-control" name="irrigation_source" 
                                       value="<?php echo htmlspecialchars($farmer['irrigation_source']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <a href="view_farmers.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i> Update Farmer
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-calculate age from DOB
        document.addEventListener('DOMContentLoaded', function() {
            const dobField = document.querySelector('input[name="dob"]');
            const ageField = document.querySelector('input[name="age"]');
            
            if (dobField && ageField) {
                dobField.addEventListener('change', function() {
                    if (this.value) {
                        const dob = new Date(this.value);
                        const today = new Date();
                        let age = today.getFullYear() - dob.getFullYear();
                        const monthDiff = today.getMonth() - dob.getMonth();
                        
                        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                            age--;
                        }
                        
                        ageField.value = age;
                    }
                });
            }
        });
    </script>
</body>
</html>