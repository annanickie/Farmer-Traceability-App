<?php
// view_farmer.php

// Start session
session_start();

// Define root path
$root_path = dirname(__DIR__);

// Include required files
require_once $root_path . '/includes/config.php';
require_once $root_path . '/includes/database.php';

// Check if farmer ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: farmer_list.php');
    exit;
}

$farmer_id = intval($_GET['id']);
$farmer = null;
$error = '';

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Fetch farmer details
    $query = "SELECT * FROM farmers WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$farmer_id]);
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
    <title>View Farmer - PGP Farmer Traceability</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0e9c4d;
            --secondary-color: #0a6e38;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        
        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin: 25px 0 20px 0;
            font-weight: 600;
        }
        
        .farmer-code {
            background-color: #e8f5e9;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px dashed var(--primary-color);
            font-weight: 600;
            color: var(--primary-color);
            text-align: center;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        
        .info-value {
            color: #6c757d;
        }
        
        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .document-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 8px 12px;
            border-radius: 5px;
            margin: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-success">
                <i class="fas fa-eye me-2"></i> Farmer Details
            </h2>
            <div>
                <a href="farmer_registration.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i> Add New Farmer
                </a>
                <a href="farmer_list.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-list me-2"></i> Back to List
                </a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif ($farmer): ?>

        <!-- Farmer Code & Basic Info -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-id-card me-2"></i> Farmer Information
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="farmer-code">
                            FARMER CODE: <?php echo htmlspecialchars($farmer['farmer_code']); ?>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            Registered on: <?php echo date('d M Y, h:i A', strtotime($farmer['created_at'])); ?>
                        </small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <label class="info-label">Farmer Name</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['farmer_name']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Mobile Number</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['mobile']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Date of Birth</label>
                        <p class="info-value"><?php echo date('d M Y', strtotime($farmer['dob'])); ?> (Age: <?php echo $farmer['age']; ?>)</p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Gender</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['gender']); ?></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label class="info-label">Social Category</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['social_category']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Farmer Category</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['farmer_category']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Email</label>
                        <p class="info-value"><?php echo !empty($farmer['email']) ? htmlspecialchars($farmer['email']) : 'Not provided'; ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Aadhaar</label>
                        <p class="info-value"><?php echo !empty($farmer['aadhaar']) ? htmlspecialchars($farmer['aadhaar']) : 'Not provided'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location Details -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-map-marker-alt me-2"></i> Location Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="info-label">Full Address</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['full_address']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Village</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['village']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Block</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['block']); ?></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label class="info-label">District</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['district']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">State</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['state']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Pincode</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['pincode']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Land Details -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-tractor me-2"></i> Land Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="info-label">Land Area</label>
                        <p class="info-value">
                            <?php 
                            if (!empty($farmer['land_area'])) {
                                echo htmlspecialchars($farmer['land_area']) . ' ' . htmlspecialchars($farmer['area_unit'] ?? '');
                            } else {
                                echo 'Not provided';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Dag Number</label>
                        <p class="info-value"><?php echo !empty($farmer['dag_no']) ? htmlspecialchars($farmer['dag_no']) : 'Not provided'; ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Patta Number</label>
                        <p class="info-value"><?php echo !empty($farmer['patta_no']) ? htmlspecialchars($farmer['patta_no']) : 'Not provided'; ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Land Type</label>
                        <p class="info-value"><?php echo !empty($farmer['land_type']) ? htmlspecialchars($farmer['land_type']) : 'Not provided'; ?></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label class="info-label">Irrigation Source</label>
                        <p class="info-value"><?php echo !empty($farmer['irrigation_source']) ? htmlspecialchars($farmer['irrigation_source']) : 'Not provided'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Details -->
        <?php if (!empty($farmer['product_name'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-seedling me-2"></i> Product Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="info-label">Product Name</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['product_name']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Variety</label>
                        <p class="info-value"><?php echo !empty($farmer['product_variety']) ? htmlspecialchars($farmer['product_variety']) : 'Not specified'; ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Production</label>
                        <p class="info-value">
                            <?php 
                            if (!empty($farmer['production_mt'])) {
                                echo htmlspecialchars($farmer['production_mt']) . ' MT';
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Area</label>
                        <p class="info-value"><?php echo !empty($farmer['production_area']) ? htmlspecialchars($farmer['production_area']) : 'Not specified'; ?></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label class="info-label">Sowing Time</label>
                        <p class="info-value">
                            <?php 
                            if (!empty($farmer['sowing_time'])) {
                                echo date('d M Y', strtotime($farmer['sowing_time']));
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Harvesting Time</label>
                        <p class="info-value">
                            <?php 
                            if (!empty($farmer['harvesting_time'])) {
                                echo date('d M Y', strtotime($farmer['harvesting_time']));
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Training Received</label>
                        <p class="info-value"><?php echo !empty($farmer['product_training']) ? htmlspecialchars($farmer['product_training']) : 'Not specified'; ?></p>
                    </div>
                    <div class="col-md-3">
                        <label class="info-label">Remarks</label>
                        <p class="info-value"><?php echo !empty($farmer['product_remarks']) ? htmlspecialchars($farmer['product_remarks']) : 'None'; ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Bank Details -->
        <?php if (!empty($farmer['account_holder'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-university me-2"></i> Bank Details
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label class="info-label">Account Holder</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['account_holder']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="info-label">Account Number</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['account_number']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <label class="info-label">IFSC Code</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['ifsc_code']); ?></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="info-label">Bank Name</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['bank_name']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label class="info-label">Branch</label>
                        <p class="info-value"><?php echo htmlspecialchars($farmer['bank_branch']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Family & Training Details -->
        <div class="row">
            <!-- Family Details -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-users me-2"></i> Family Details
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="info-label">Family Size</label>
                                <p class="info-value"><?php echo !empty($farmer['family_size']) ? htmlspecialchars($farmer['family_size']) : 'Not provided'; ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="info-label">Dependents</label>
                                <p class="info-value"><?php echo !empty($farmer['dependents']) ? htmlspecialchars($farmer['dependents']) : 'Not provided'; ?></p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="info-label">Primary Occupation</label>
                                <p class="info-value"><?php echo !empty($farmer['primary_occupation']) ? htmlspecialchars($farmer['primary_occupation']) : 'Not provided'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Training Details -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header">
                        <i class="fas fa-graduation-cap me-2"></i> Training Details
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="info-label">Training Received</label>
                                <p class="info-value"><?php echo !empty($farmer['training_received']) ? htmlspecialchars($farmer['training_received']) : 'Not specified'; ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="info-label">Training Year</label>
                                <p class="info-value"><?php echo !empty($farmer['training_year']) ? htmlspecialchars($farmer['training_year']) : 'Not specified'; ?></p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label class="info-label">Training Subject</label>
                                <p class="info-value"><?php echo !empty($farmer['training_subject']) ? htmlspecialchars($farmer['training_subject']) : 'Not specified'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Section -->
        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-file-alt me-2"></i> Documents
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (!empty($farmer['aadhaar_file_path'])): ?>
                    <div class="col-md-3 mb-3">
                        <div class="document-badge">
                            <i class="fas fa-id-card me-2"></i>
                            <a href="../<?php echo htmlspecialchars($farmer['aadhaar_file_path']); ?>" target="_blank">Aadhaar Card</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($farmer['pan_file_path'])): ?>
                    <div class="col-md-3 mb-3">
                        <div class="document-badge">
                            <i class="fas fa-file-invoice me-2"></i>
                            <a href="../<?php echo htmlspecialchars($farmer['pan_file_path']); ?>" target="_blank">PAN Card</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($farmer['passbook_file_path'])): ?>
                    <div class="col-md-3 mb-3">
                        <div class="document-badge">
                            <i class="fas fa-piggy-bank me-2"></i>
                            <a href="../<?php echo htmlspecialchars($farmer['passbook_file_path']); ?>" target="_blank">Bank Passbook</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($farmer['land_docs_path'])): ?>
                    <div class="col-md-3 mb-3">
                        <div class="document-badge">
                            <i class="fas fa-map me-2"></i>
                            <a href="../<?php echo htmlspecialchars($farmer['land_docs_path']); ?>" target="_blank">Land Documents</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($farmer['soil_report_path'])): ?>
                    <div class="col-md-3 mb-3">
                        <div class="document-badge">
                            <i class="fas fa-flask me-2"></i>
                            <a href="../<?php echo htmlspecialchars($farmer['soil_report_path']); ?>" target="_blank">Soil Test Report</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php 
                // Fetch additional documents from documents table
                try {
                    $doc_query = "SELECT * FROM documents WHERE farmer_id = ? ORDER BY uploaded_at DESC";
                    $doc_stmt = $conn->prepare($doc_query);
                    $doc_stmt->execute([$farmer_id]);
                    $additional_docs = $doc_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($additional_docs): ?>
                    <h6 class="mt-4 mb-3">Additional Documents:</h6>
                    <div class="row">
                        <?php foreach ($additional_docs as $doc): ?>
                        <div class="col-md-4 mb-3">
                            <div class="document-badge">
                                <i class="fas fa-file me-2"></i>
                                <a href="../<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($doc['document_type']); ?>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif;
                } catch (Exception $e) {
                    // Silently fail for additional documents
                }
                ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-center mt-4">
            <a href="edit_farmer.php?id=<?php echo $farmer_id; ?>" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i> Edit Farmer
            </a>
            <a href="farmer_list.php" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left me-2"></i> Back to List
            </a>
            <button onclick="window.print()" class="btn btn-outline-primary ms-2">
                <i class="fas fa-print me-2"></i> Print
            </button>
        </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>