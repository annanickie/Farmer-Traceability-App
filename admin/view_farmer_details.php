<?php
// admin/view_farmer_details.php
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

try {
    // Get farmer details with JOINs to display names instead of IDs
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Details - Admin Panel</title>
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
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background-color: white;
            box-shadow: var(--shadow);
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
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border: none;
            padding: 15px 20px;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
            margin-bottom: 15px;
        }
        
        .badge-custom {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .back-btn {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }
        
        .back-btn:hover {
            background-color: #5a6268;
            border-color: #545b62;
            color: white;
        }
        
        .section-divider {
            border-bottom: 2px solid #e9ecef;
            margin: 20px 0;
            padding-bottom: 10px;
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
                        <h1><i class="fas fa-user-circle me-2"></i> Farmer Details</h1>
                        <div class="d-flex gap-2">
                            <a href="edit_farmer.php?id=<?php echo $farmer_id; ?>" class="btn btn-warning">
                                <i class="fas fa-edit me-1"></i> Edit Farmer
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

                <?php if ($farmer): ?>
                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-id-card me-2"></i> Basic Information
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-label">Farmer Code</div>
                                        <div class="info-value">
                                            <span class="badge-custom"><?php echo htmlspecialchars($farmer['farmer_code']); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Registration Date</div>
                                        <div class="info-value"><?php echo date('M j, Y', strtotime($farmer['created_at'])); ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-label">Full Name</div>
                                <div class="info-value h5"><?php echo htmlspecialchars($farmer['farmer_name']); ?></div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-label">Date of Birth</div>
                                        <div class="info-value"><?php echo !empty($farmer['dob']) ? date('M j, Y', strtotime($farmer['dob'])) : 'N/A'; ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Age</div>
                                        <div class="info-value"><?php echo !empty($farmer['age']) ? htmlspecialchars($farmer['age']) : 'N/A'; ?></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-label">Gender</div>
                                        <div class="info-value"><?php echo htmlspecialchars($farmer['gender']); ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Social Category</div>
                                        <div class="info-value"><?php echo !empty($farmer['social_category']) ? htmlspecialchars($farmer['social_category']) : 'N/A'; ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-label">Farmer Category</div>
                                <div class="info-value">
                                    <span class="badge bg-info"><?php echo htmlspecialchars($farmer['farmer_category']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-address-book me-2"></i> Contact Information
                            </div>
                            <div class="card-body">
                                <div class="info-label">Mobile Number</div>
                                <div class="info-value">
                                    <i class="fas fa-phone me-2 text-muted"></i>
                                    <?php echo !empty($farmer['mobile']) ? htmlspecialchars($farmer['mobile']) : 'N/A'; ?>
                                </div>
                                
                                <div class="info-label">Email Address</div>
                                <div class="info-value">
                                    <i class="fas fa-envelope me-2 text-muted"></i>
                                    <?php echo !empty($farmer['email']) ? htmlspecialchars($farmer['email']) : 'N/A'; ?>
                                </div>
                                
                                <div class="info-label">Aadhaar Number</div>
                                <div class="info-value"><?php echo !empty($farmer['aadhaar']) ? htmlspecialchars($farmer['aadhaar']) : 'N/A'; ?></div>
                                
                                <div class="info-label">PAN Number</div>
                                <div class="info-value"><?php echo !empty($farmer['pan']) ? htmlspecialchars($farmer['pan']) : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-map-marker-alt me-2"></i> Address Information
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-label">Full Address</div>
                                        <div class="info-value"><?php echo !empty($farmer['full_address']) ? htmlspecialchars($farmer['full_address']) : 'N/A'; ?></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="info-label">Village</div>
                                        <div class="info-value"><?php echo !empty($farmer['village']) ? htmlspecialchars($farmer['village']) : 'N/A'; ?></div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-label">Block</div>
                                        <div class="info-value"><?php echo !empty($farmer['block']) ? htmlspecialchars($farmer['block']) : 'N/A'; ?></div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-label">District</div>
                                        <div class="info-value"><?php echo htmlspecialchars($farmer['district_name'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="info-label">State</div>
                                        <div class="info-value"><?php echo htmlspecialchars($farmer['state_name'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="info-label">Pincode</div>
                                        <div class="info-value"><?php echo !empty($farmer['pincode']) ? htmlspecialchars($farmer['pincode']) : 'N/A'; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Land & Bank Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-tractor me-2"></i> Land Information
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-label">Land Area</div>
                                        <div class="info-value">
                                            <?php if (!empty($farmer['land_area'])): ?>
                                                <?php echo htmlspecialchars($farmer['land_area']); ?> 
                                                <?php echo !empty($farmer['area_unit']) ? htmlspecialchars($farmer['area_unit']) : ''; ?>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Land Type</div>
                                        <div class="info-value"><?php echo !empty($farmer['land_type']) ? htmlspecialchars($farmer['land_type']) : 'N/A'; ?></div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-label">DAG Number</div>
                                        <div class="info-value"><?php echo !empty($farmer['dag_no']) ? htmlspecialchars($farmer['dag_no']) : 'N/A'; ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Patta Number</div>
                                        <div class="info-value"><?php echo !empty($farmer['patta_no']) ? htmlspecialchars($farmer['patta_no']) : 'N/A'; ?></div>
                                    </div>
                                </div>
                                
                                <div class="info-label">Irrigation Source</div>
                                <div class="info-value"><?php echo !empty($farmer['irrigation_source']) ? htmlspecialchars($farmer['irrigation_source']) : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-university me-2"></i> Bank Information
                            </div>
                            <div class="card-body">
                                <div class="info-label">Account Holder</div>
                                <div class="info-value"><?php echo !empty($farmer['account_holder']) ? htmlspecialchars($farmer['account_holder']) : 'N/A'; ?></div>
                                
                                <div class="info-label">Account Number</div>
                                <div class="info-value"><?php echo !empty($farmer['account_number']) ? htmlspecialchars($farmer['account_number']) : 'N/A'; ?></div>
                                
                                <div class="info-label">Bank Name</div>
                                <div class="info-value"><?php echo !empty($farmer['bank_name']) ? htmlspecialchars($farmer['bank_name']) : 'N/A'; ?></div>
                                
                                <div class="info-label">Bank Branch</div>
                                <div class="info-value"><?php echo !empty($farmer['bank_branch']) ? htmlspecialchars($farmer['bank_branch']) : 'N/A'; ?></div>
                                
                                <div class="info-label">IFSC Code</div>
                                <div class="info-value"><?php echo !empty($farmer['ifsc_code']) ? htmlspecialchars($farmer['ifsc_code']) : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-info-circle me-2"></i> Additional Information
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-label">Family Size</div>
                                        <div class="info-value"><?php echo !empty($farmer['family_size']) ? htmlspecialchars($farmer['family_size']) : 'N/A'; ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Dependents</div>
                                        <div class="info-value"><?php echo !empty($farmer['dependents']) ? htmlspecialchars($farmer['dependents']) : 'N/A'; ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Primary Occupation</div>
                                        <div class="info-value"><?php echo !empty($farmer['primary_occupation']) ? htmlspecialchars($farmer['primary_occupation']) : 'N/A'; ?></div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($farmer['product_name'])): ?>
                                <div class="section-divider">
                                    <h6 class="text-primary"><i class="fas fa-seedling me-2"></i> Product Information</h6>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-label">Product Name</div>
                                        <div class="info-value"><?php echo htmlspecialchars($farmer['product_name']); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Product Variety</div>
                                        <div class="info-value"><?php echo !empty($farmer['product_variety']) ? htmlspecialchars($farmer['product_variety']) : 'N/A'; ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Production (MT)</div>
                                        <div class="info-value"><?php echo !empty($farmer['production_mt']) ? htmlspecialchars($farmer['production_mt']) : 'N/A'; ?></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>