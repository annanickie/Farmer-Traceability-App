<?php
// user/farmer_registration.php

session_start();
$root_path = dirname(__DIR__);

// Include required files
include $root_path . '/includes/config.php';
include $root_path . '/includes/database.php';
include $root_path . '/includes/farmer_code.php';

// Initialize variables
$states = [];
$districts = [];
$error = '';

try {
    $database = new Database();
    $conn = $database->getConnection();
    $states = getStates($conn);
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Registration - PGP Farmer Traceability</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0e9c4d;
            --secondary-color: #0a6e38;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --success-color: #198754;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin: 25px 0 20px 0;
            font-weight: 600;
        }
        
        .required-field::after {
            content: " *";
            color: var(--danger-color);
        }
        
        .farmer-code-display {
            background-color: #e8f5e9;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px dashed var(--primary-color);
            font-weight: 600;
            color: var(--primary-color);
            text-align: center;
        }
        
        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        /* Product Section Styles */
        .product-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .product-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .product-number {
            background-color: var(--primary-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .remove-product-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .remove-product-btn:hover {
            background-color: #c82333;
        }
        
        .add-product-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        .add-product-btn:hover {
            background-color: #157347;
        }
        
        .geotag-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .geotag-status {
            font-size: 0.875rem;
            padding: 5px 10px;
            border-radius: 4px;
            margin-top: 5px;
            display: none;
        }
        
        .status-success {
            background-color: #d1edff;
            color: #0d6efd;
            border: 1px solid #b3d7ff;
        }
        
        .status-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .gps-info {
            background-color: #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.875rem;
            margin-top: 5px;
        }
        
        .photo-preview-container {
            position: relative;
            display: inline-block;
            margin: 5px;
        }
        
        .photo-preview {
            width: 120px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            border: 2px solid #dee2e6;
        }
        
        .remove-photo-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #dc3545;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
        }
        
        .input-error {
            border-color: #dc3545 !important;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
        }
        
        .gps-camera-preview {
            width: 100%;
            max-height: 400px;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }
        
        .photo-metadata {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .document-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .document-upload-area:hover {
            border-color: var(--primary-color);
            background-color: #e8f5e9;
        }
        
        .upload-preview {
            max-width: 150px;
            max-height: 100px;
            border-radius: 5px;
            margin: 5px;
        }
        
        select:disabled {
            background-color: #f8f9fa;
            opacity: 0.7;
        }
        
        .farmer-code-placeholder {
            color: #6c757d;
            font-style: italic;
        }
        
        .farmer-code-ready {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .farmer-code-error {
            color: var(--danger-color);
            font-weight: normal;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-success">
                <i class="fas fa-user-plus me-2"></i> Farmer Registration
            </h2>
            <button class="btn btn-outline-primary no-print" onclick="window.print()">
                <i class="fas fa-print me-2"></i> Print Form
            </button>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="save_farmer.php" method="POST" enctype="multipart/form-data" class="form-container" id="farmerForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <!-- PERSONAL DETAILS -->
            <h5 class="section-title"><i class="fas fa-user-circle me-2"></i> PERSONAL DETAILS</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label required-field">Farmer Name</label>
                    <input type="text" class="form-control" name="farmer_name" id="farmerName" required>
                    <div class="error-message" id="nameError"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">FARMER CODE</label>
                    <div class="farmer-code-display" id="farmerCodeDisplay">Please select state and district</div>
                    <input type="hidden" name="farmer_code" id="farmerCodeInput">
                    <div class="error-message" id="codeError"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label required-field">Date of Birth</label>
                    <input type="date" class="form-control" name="dob" id="dob" required onchange="calculateAge(this.value)">
                    <div class="error-message" id="dobError"></div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Age</label>
                    <input type="number" class="form-control" name="age" id="age" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label required-field">Gender</label>
                    <select class="form-select" name="gender" required>
                        <option value="">Select</option>
                        <option>Male</option>
                        <option>Female</option>
                        <option>Other</option>
                    </select>
                    <div class="error-message" id="genderError"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">Social Category</label>
                    <select class="form-select" name="social_category" required>
                        <option value="">Select</option>
                        <option>Unreserved</option>
                        <option>OBC</option>
                        <option>MOBC</option>
                        <option>ST(Hills)</option>
                        <option>ST(Plains)</option>
                    </select>
                    <div class="error-message" id="socialCategoryError"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">Farmer Category</label>
                    <select class="form-select" name="farmer_category" required>
                        <option value="">Select</option>
                        <option>Big Farmer</option>
                        <option>Marginal Farmer</option>
                        <option>Small Farmer</option>
                        <option>Others</option>
                    </select>
                    <div class="error-message" id="farmerCategoryError"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label required-field">Mobile Number</label>
                    <input type="tel" class="form-control" name="mobile" id="mobile" pattern="[0-9]{10}" required>
                    <div class="error-message" id="mobileError"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" name="email" id="email">
                </div>
                
                <!-- AADHAAR & PAN -->
                <div class="col-md-4">
                    <label class="form-label">Aadhaar Number</label>
                    <input type="text" class="form-control" name="aadhaar" id="aadhaarInput" pattern="[0-9]{12}" maxlength="12">
                </div>
                <div class="col-md-4">
                    <label class="form-label">PAN Number</label>
                    <input type="text" class="form-control" name="pan" id="panInput" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" maxlength="10" style="text-transform:uppercase">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Upload Aadhaar</label>
                    <div class="document-upload-area" onclick="document.getElementById('aadhaarFile').click()">
                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                        <p class="mb-1">Click to upload</p>
                        <small class="text-muted">PDF, JPG, PNG</small>
                        <input type="file" class="d-none" name="aadhaar_file" id="aadhaarFile" accept=".pdf,.jpg,.jpeg,.png" onchange="previewFile(this, 'aadhaarPreview')">
                    </div>
                    <div id="aadhaarPreview" class="mt-2"></div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Upload PAN</label>
                    <div class="document-upload-area" onclick="document.getElementById('panFile').click()">
                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                        <p class="mb-1">Click to upload</p>
                        <small class="text-muted">PDF, JPG, PNG</small>
                        <input type="file" class="d-none" name="pan_file" id="panFile" accept=".pdf,.jpg,.jpeg,.png" onchange="previewFile(this, 'panPreview')">
                    </div>
                    <div id="panPreview" class="mt-2"></div>
                </div>
            </div>

            <!-- LOCATION DETAILS -->
            <h5 class="section-title mt-4"><i class="fas fa-map-marker-alt me-2"></i> LOCATION DETAILS</h5>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label required-field">Full Address</label>
                    <textarea class="form-control" name="full_address" id="fullAddress" rows="2" required></textarea>
                    <div class="error-message" id="addressError"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">Village</label>
                    <input type="text" class="form-control" name="village" id="village" required>
                    <div class="error-message" id="villageError"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">Block</label>
                    <input type="text" class="form-control" name="block" id="block" required>
                    <div class="error-message" id="blockError"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">District</label>
                    <select class="form-select" name="district" id="districtSelect" required onchange="generateFarmerCode()">
                        <option value="">Select District</option>
                    </select>
                    <div class="error-message" id="districtError"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">State</label>
                    <select class="form-select" name="state" id="stateSelect" required onchange="handleStateChange()">
                        <option value="">Select State</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state['id']; ?>" data-code="<?php echo $state['state_code']; ?>">
                                <?php echo htmlspecialchars($state['state_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="error-message" id="stateError"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">Pincode</label>
                    <input type="text" class="form-control" name="pincode" id="pincode" pattern="[0-9]{6}" required>
                    <div class="error-message" id="pincodeError"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Residence Geotag</label>
                    <div class="geotag-section">
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="geotag_location" id="geotagLocation" placeholder="Latitude, Longitude" readonly>
                            <button type="button" class="btn btn-primary" onclick="captureGeolocation()">
                                <i class="fas fa-map-marker-alt"></i> Get Location
                            </button>
                        </div>
                        <div id="locationStatus" class="geotag-status"></div>
                        
                        <!-- GPS Camera for Residence Photo -->
                        <label class="form-label mt-3">Residence Photo with GPS</label>
                        <div class="mb-3">
                            <button type="button" class="btn btn-success w-100" onclick="openGPSCamera('residence')">
                                <i class="fas fa-camera me-2"></i> Capture Residence Photo with GPS
                            </button>
                        </div>
                        <div id="residencePhotoPreview" class="d-flex flex-wrap gap-2 mb-2"></div>
                        <div id="residenceGpsInfo" class="gps-info" style="display:none;"></div>
                        <input type="hidden" name="residence_photo_geotag" id="residencePhotoGeotag">
                        <input type="hidden" name="residence_photo_place" id="residencePhotoPlace">
                        <input type="hidden" name="residence_photos_data" id="residencePhotosData">
                    </div>
                </div>
            </div>

            <!-- LAND DETAILS -->
            <h5 class="section-title mt-4"><i class="fas fa-tractor me-2"></i> LAND DETAILS</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Land Area</label>
                    <input type="number" step="0.01" class="form-control" name="land_area">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unit</label>
                    <select class="form-select" name="area_unit">
                        <option value="">Select</option>
                        <option>Acres</option>
                        <option>Hectares</option>
                        <option>Bigha</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dag Number</label>
                    <input type="text" class="form-control" name="dag_no">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Patta Number</label>
                    <input type="text" class="form-control" name="patta_no">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Land Type</label>
                    <input type="text" class="form-control" name="land_type">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Irrigation Source</label>
                    <select class="form-select" name="irrigation_source">
                        <option value="">Select</option>
                        <option>Rainfed</option>
                        <option>Canal</option>
                        <option>Well</option>
                        <option>Tube Well</option>
                        <option>Other</option>
                    </select>
                </div>
                
                <!-- Land Geotag Section -->
                <div class="col-12">
                    <div class="geotag-section">
                        <h6><i class="fas fa-map-marked-alt me-2"></i>Land Geotagging</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Land Location Coordinates</label>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="land_geotag" id="landGeotag" placeholder="Latitude, Longitude" readonly>
                                    <button type="button" class="btn btn-primary" onclick="captureLandGeolocation()">
                                        <i class="fas fa-map-marker-alt"></i> Get Land Location
                                    </button>
                                </div>
                                <div id="landLocationStatus" class="geotag-status"></div>
                            </div>
                            <div class="col-md-6">
                                <!-- GPS Camera for Land Photos -->
                                <label class="form-label">Land Photos with GPS</label>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-success w-100" onclick="openGPSCamera('land')">
                                        <i class="fas fa-camera me-2"></i> Capture Land Photo with GPS
                                    </button>
                                </div>
                                <div id="landPhotosPreview" class="d-flex flex-wrap gap-2 mb-2"></div>
                                <div id="landGpsInfo" class="gps-info" style="display:none;"></div>
                                <input type="hidden" name="land_photo_geotags" id="landPhotoGeotags">
                                <input type="hidden" name="land_photo_places" id="landPhotoPlaces">
                                <input type="hidden" name="land_photos_data" id="landPhotosData">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Land Documents</label>
                    <input type="file" class="form-control" name="land_docs" accept=".pdf,.jpg,.png">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Land Video</label>
                    <input type="file" class="form-control" name="land_video" accept="video/*">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Remarks</label>
                    <input type="text" class="form-control" name="land_remarks">
                </div>
            </div>

            <!-- BANK DETAILS -->
            <h5 class="section-title mt-4"><i class="fas fa-university me-2"></i> BANK DETAILS</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Account Holder Name</label>
                    <input type="text" class="form-control" name="account_holder">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Number</label>
                    <input type="text" class="form-control" name="account_number">
                </div>
                <div class="col-md-4">
                    <label class="form-label">IFSC Code</label>
                    <input type="text" class="form-control" name="ifsc_code">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bank Name</label>
                    <input type="text" class="form-control" name="bank_name">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Branch Name & Address</label>
                    <input type="text" class="form-control" name="bank_branch">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Bank Passbook</label>
                    <input type="file" class="form-control" name="passbook_file" accept=".pdf,.jpg,.png">
                </div>
            </div>

            <!-- FAMILY DETAILS -->
            <h5 class="section-title mt-4"><i class="fas fa-users me-2"></i> FAMILY DETAILS</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Family Size</label>
                    <input type="number" class="form-control" name="family_size" min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dependents</label>
                    <input type="number" class="form-control" name="dependents" min="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Primary Occupation</label>
                    <input type="text" class="form-control" name="primary_occupation">
                </div>
            </div>

            <!-- PRODUCT DETAILS - MULTIPLE PRODUCTS -->
            <h5 class="section-title mt-4"><i class="fas fa-seedling me-2"></i> PRODUCT DETAILS</h5>
            
            <!-- Add Product Button -->
            <div class="text-center mb-4">
                <button type="button" class="btn add-product-btn" onclick="addProductSection()">
                    <i class="fas fa-plus me-2"></i> Add Another Product
                </button>
            </div>
            
            <!-- Product Sections Container -->
            <div id="productSectionsContainer">
                <!-- Default Product Section -->
                <div class="product-section" id="productSection_1">
                    <div class="product-header">
                        <div class="d-flex align-items-center">
                            <div class="product-number">1</div>
                            <h6 class="mb-0">Product Details</h6>
                        </div>
                        <button type="button" class="remove-product-btn" onclick="removeProductSection(1)" id="removeBtn_1" style="display:none;">
                            <i class="fas fa-times me-1"></i> Remove
                        </button>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="products[1][product_name]">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Variety</label>
                            <input type="text" class="form-control" name="products[1][product_variety]">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Production (MT)</label>
                            <input type="number" step="0.01" class="form-control" name="products[1][production_mt]">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Area</label>
                            <input type="text" class="form-control" name="products[1][production_area]">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Soil Test Report</label>
                            <input type="file" class="form-control" name="products[1][soil_report_file]" accept=".pdf,.jpg,.png">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sowing Time</label>
                            <input type="date" class="form-control" name="products[1][sowing_time]">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Harvesting Time</label>
                            <input type="date" class="form-control" name="products[1][harvesting_time]">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Training Received</label>
                            <select class="form-select" name="products[1][product_training]">
                                <option value="">Select</option>
                                <option>Yes</option>
                                <option>No</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Remarks</label>
                            <input type="text" class="form-control" name="products[1][product_remarks]">
                        </div>
                    </div>
                </div>
            </div>

            <!-- TRAINING DETAILS -->
            <h5 class="section-title mt-4"><i class="fas fa-graduation-cap me-2"></i> TRAINING DETAILS</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Received Training?</label>
                    <select class="form-select" name="training_received">
                        <option value="">Select</option>
                        <option>Yes</option>
                        <option>No</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Training Subject</label>
                    <input type="text" class="form-control" name="training_subject">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Training Year</label>
                    <input type="number" class="form-control" name="training_year" min="2000" max="<?= date('Y') ?>">
                </div>
            </div>

            <!-- UNDER GUIDANCE -->
            <h5 class="section-title mt-4"><i class="fas fa-graduation-cap me-2"></i> UNDER GUIDANCE</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Under Institute</label>
                    <select class="form-select" name="under_institute">
                        <option value="">Select</option>
                        <option>FPC</option>
                        <option>Cooperative Society</option>
                        <option>NGO</option>
                        <option>Under Government</option>
                        <option>Individual Farmer</option>
                        <option>Under Aggregator</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Name of the Institute</label>
                    <input type="text" class="form-control" name="institute_name">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="institute_address" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Person Name</label>
                    <input type="text" class="form-control" name="contact_person">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Number</label>
                    <input type="text" class="form-control" name="contact_number">
                </div>
            </div>

            <!-- ADDITIONAL DOCUMENTS -->
            <h5 class="section-title mt-4"><i class="fas fa-file-alt me-2"></i> ADDITIONAL DOCUMENTS</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Other Documents</label>
                    <div id="documentUploads">
                        <div class="input-group mb-2">
                            <select class="form-select" name="document_type[]">
                                <option value="Farmer Certificate">Farmer Certificate</option>
                                <option value="Land Documents">Land Documents</option>
                                <option value="Other">Other</option>
                            </select>
                            <input type="file" class="form-control" name="document_file[]">
                            <button type="button" class="btn btn-outline-danger" onclick="removeDocumentInput(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-primary" onclick="addDocumentInput()">
                        <i class="fas fa-plus me-1"></i> Add Document
                    </button>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Farmer Potential</label>
                    <textarea class="form-control" name="farmer_potential" rows="3"></textarea>
                </div>
            </div>

            <!-- SUBMIT BUTTONS -->
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success btn-lg px-5" id="submitBtn">
                    <i class="fas fa-save me-2"></i> Save Farmer
                </button>
                <button type="reset" class="btn btn-outline-secondary btn-lg px-5 ms-3">
                    <i class="fas fa-undo me-2"></i> Reset
                </button>
                <button type="button" class="btn btn-outline-warning btn-lg px-5 ms-3" onclick="saveAsDraft()">
                    <i class="fas fa-save me-2"></i> Save Draft
                </button>
            </div>
        </form>
    </div>

    <!-- GPS Camera Modal -->
    <div class="modal fade" id="gpsCameraModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="gpsCameraModalTitle">Capture Photo with GPS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <video id="gpsCameraPreview" class="gps-camera-preview" autoplay></video>
                    <canvas id="gpsCameraCanvas" style="display:none;"></canvas>
                    <div id="gpsCameraStatus" class="gps-info mt-2" style="display:none;"></div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-success" id="gpsCaptureBtn" onclick="captureGPSCameraPhoto()">
                            <i class="fas fa-camera me-2"></i>Capture Photo with GPS
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="switchGPSCamera()">
                            <i class="fas fa-sync-alt me-2"></i>Switch Camera
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Response Modal -->
    <div class="modal fade" id="responseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registration Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="responseMessage">
                    <!-- Response message will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="viewFarmerBtn" style="display:none;">View Farmer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let currentGPSCameraType = null;
        let gpsStream = null;
        let landPhotos = [];
        let residencePhotos = [];
        let productCounter = 1;

        // Multiple Products Functions
        function addProductSection() {
            productCounter++;
            const container = document.getElementById('productSectionsContainer');
            const newSection = document.createElement('div');
            newSection.className = 'product-section';
            newSection.id = `productSection_${productCounter}`;
            
            newSection.innerHTML = `
                <div class="product-header">
                    <div class="d-flex align-items-center">
                        <div class="product-number">${productCounter}</div>
                        <h6 class="mb-0">Product Details</h6>
                    </div>
                    <button type="button" class="remove-product-btn" onclick="removeProductSection(${productCounter})">
                        <i class="fas fa-times me-1"></i> Remove
                    </button>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Product Name</label>
                        <input type="text" class="form-control" name="products[${productCounter}][product_name]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Variety</label>
                        <input type="text" class="form-control" name="products[${productCounter}][product_variety]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Production (MT)</label>
                        <input type="number" step="0.01" class="form-control" name="products[${productCounter}][production_mt]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Area</label>
                        <input type="text" class="form-control" name="products[${productCounter}][production_area]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Soil Test Report</label>
                        <input type="file" class="form-control" name="products[${productCounter}][soil_report_file]" accept=".pdf,.jpg,.png">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sowing Time</label>
                        <input type="date" class="form-control" name="products[${productCounter}][sowing_time]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Harvesting Time</label>
                        <input type="date" class="form-control" name="products[${productCounter}][harvesting_time]">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Training Received</label>
                        <select class="form-select" name="products[${productCounter}][product_training]">
                            <option value="">Select</option>
                            <option>Yes</option>
                            <option>No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Remarks</label>
                        <input type="text" class="form-control" name="products[${productCounter}][product_remarks]">
                    </div>
                </div>
            `;
            
            container.appendChild(newSection);
            
            // Show remove button on first section if there are multiple sections
            if (productCounter > 1) {
                document.getElementById('removeBtn_1').style.display = 'block';
            }
        }

        function removeProductSection(sectionId) {
            const section = document.getElementById(`productSection_${sectionId}`);
            if (section) {
                section.remove();
                
                // Update product numbers and show/hide remove buttons
                updateProductSections();
            }
        }

        function updateProductSections() {
            const sections = document.querySelectorAll('.product-section');
            productCounter = sections.length;
            
            sections.forEach((section, index) => {
                const sectionNumber = index + 1;
                const sectionId = section.id.split('_')[1];
                
                // Update product number
                const numberElement = section.querySelector('.product-number');
                numberElement.textContent = sectionNumber;
                
                // Update input names
                const inputs = section.querySelectorAll('input, select');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    if (name && name.includes('products[')) {
                        const newName = name.replace(/products\[\d+\]/, `products[${sectionNumber}]`);
                        input.setAttribute('name', newName);
                    }
                });
                
                // Update remove button
                const removeBtn = section.querySelector('.remove-product-btn');
                if (removeBtn) {
                    removeBtn.setAttribute('onclick', `removeProductSection(${sectionNumber})`);
                }
                
                // Update section ID
                section.id = `productSection_${sectionNumber}`;
            });
            
            // Show/hide remove button on first section
            const firstRemoveBtn = document.getElementById('removeBtn_1');
            if (firstRemoveBtn) {
                firstRemoveBtn.style.display = productCounter > 1 ? 'block' : 'none';
            }
        }

        // Calculate age based on date of birth
        function calculateAge(dob) {
            if (!dob) return;
            
            const birthDate = new Date(dob);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            document.getElementById('age').value = age;
        }
        
        // Handle state change
        function handleStateChange() {
            updateDistricts();
            // Clear farmer code when state changes
            document.getElementById('farmerCodeDisplay').textContent = 'Please select district';
            document.getElementById('farmerCodeInput').value = '';
        }
        
        // Update districts based on selected state - FIXED PATH
        function updateDistricts() {
            const stateId = document.getElementById('stateSelect').value;
            const districtSelect = document.getElementById('districtSelect');
            const farmerCodeDisplay = document.getElementById('farmerCodeDisplay');
            
            console.log('Updating districts for state:', stateId);
            
            // Reset districts and farmer code
            districtSelect.innerHTML = '<option value="">Select District</option>';
            farmerCodeDisplay.textContent = 'Please select state and district';
            document.getElementById('farmerCodeInput').value = '';
            
            if (!stateId) {
                console.log('No state selected');
                return;
            }
            
            // Show loading state
            districtSelect.disabled = true;
            districtSelect.innerHTML = '<option value="">Loading districts...</option>';
            
            // CORRECTED PATH - get_districts.php is in main directory
            fetch(`../get_districts.php?state_id=${stateId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data);
                    
                    districtSelect.innerHTML = '<option value="">Select District</option>';
                    
                    if (Array.isArray(data)) {
                        if (data.length > 0) {
                            data.forEach(district => {
                                const option = document.createElement('option');
                                option.value = district.id;
                                option.textContent = district.district_name;
                                districtSelect.appendChild(option);
                            });
                            console.log('Successfully loaded ' + data.length + ' districts');
                        } else {
                            districtSelect.innerHTML = '<option value="">No districts available for this state</option>';
                            console.log('No districts found for this state');
                        }
                    } else if (data.error) {
                        throw new Error(data.error);
                    } else {
                        throw new Error('Unexpected response format');
                    }
                })
                .catch(error => {
                    console.error('Error fetching districts:', error);
                    districtSelect.innerHTML = '<option value="">Error loading districts</option>';
                    
                    // Show error to user
                    const errorDiv = document.getElementById('districtError');
                    if (errorDiv) {
                        errorDiv.textContent = 'Failed to load districts: ' + error.message;
                    }
                })
                .finally(() => {
                    districtSelect.disabled = false;
                });
        }
        
        // Generate farmer code based on state and district - FIXED PATH
        function generateFarmerCode() {
            const stateSelect = document.getElementById('stateSelect');
            const districtSelect = document.getElementById('districtSelect');
            const farmerCodeDisplay = document.getElementById('farmerCodeDisplay');
            const farmerCodeInput = document.getElementById('farmerCodeInput');
            
            if (!stateSelect.value || !districtSelect.value) {
                farmerCodeDisplay.textContent = 'Please select state and district';
                farmerCodeInput.value = '';
                return;
            }
            
            const stateId = stateSelect.value;
            const districtId = districtSelect.value;
            
            // Show loading state
            farmerCodeDisplay.textContent = 'Generating farmer code...';
            farmerCodeDisplay.style.color = '#6c757d';
            
            // CORRECTED PATH - generate_farmer_code.php is in main directory
            fetch('../generate_farmer_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `state_id=${stateId}&district_id=${districtId}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.farmer_code) {
                    farmerCodeDisplay.textContent = data.farmer_code;
                    farmerCodeDisplay.style.color = 'var(--primary-color)';
                    farmerCodeInput.value = data.farmer_code;
                    document.getElementById('codeError').textContent = '';
                } else {
                    farmerCodeDisplay.textContent = 'Error generating code';
                    farmerCodeDisplay.style.color = 'var(--danger-color)';
                    farmerCodeInput.value = '';
                    document.getElementById('codeError').textContent = data.error || 'Error generating farmer code';
                }
            })
            .catch(error => {
                console.error('Error fetching farmer code:', error);
                farmerCodeDisplay.textContent = 'Network error - try again';
                farmerCodeDisplay.style.color = 'var(--danger-color)';
                farmerCodeInput.value = '';
                document.getElementById('codeError').textContent = 'Failed to generate farmer code. Please check your connection.';
            });
        }

        // GPS Camera Functions
        function openGPSCamera(type) {
            currentGPSCameraType = type;
            const modal = new bootstrap.Modal(document.getElementById('gpsCameraModal'));
            const title = document.getElementById('gpsCameraModalTitle');
            
            if (type === 'residence') {
                title.textContent = 'Capture Residence Photo with GPS';
            } else if (type === 'land') {
                title.textContent = 'Capture Land Photo with GPS';
            }
            
            modal.show();
            startGPSCamera();
        }

        function startGPSCamera() {
            const video = document.getElementById('gpsCameraPreview');
            const statusDiv = document.getElementById('gpsCameraStatus');
            
            if (gpsStream) {
                gpsStream.getTracks().forEach(track => track.stop());
            }
            
            // Show GPS status
            statusDiv.style.display = 'block';
            statusDiv.className = 'gps-info status-success';
            statusDiv.innerHTML = '<div class="loading-spinner"></div> Starting camera and getting GPS location...';
            
            // Get GPS location first
            getLocationForPhoto().then(locationData => {
                statusDiv.innerHTML = `
                    <i class="fas fa-check-circle text-success"></i> 
                    <strong>GPS Ready:</strong> ${locationData.coordinates} 
                    | <strong>Place:</strong> ${locationData.placeName}
                    | <strong>Accuracy:</strong> ${locationData.accuracy}m
                `;
                
                // Start camera after getting location
                navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'environment',
                        width: { ideal: 1920 },
                        height: { ideal: 1080 }
                    },
                    audio: false 
                })
                .then(function(cameraStream) {
                    gpsStream = cameraStream;
                    video.srcObject = gpsStream;
                })
                .catch(function(error) {
                    console.error('GPS Camera error:', error);
                    statusDiv.className = 'gps-info status-error';
                    statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Camera error: ${error.message}`;
                });
                
            }).catch(error => {
                statusDiv.className = 'gps-info status-error';
                statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> GPS Error: ${error.message}`;
            });
        }

        function captureGPSCameraPhoto() {
            const video = document.getElementById('gpsCameraPreview');
            const canvas = document.getElementById('gpsCameraCanvas');
            const context = canvas.getContext('2d');
            const statusDiv = document.getElementById('gpsCameraStatus');
            
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Show capturing status
            statusDiv.innerHTML = '<div class="loading-spinner"></div> Capturing photo with GPS...';
            
            // Get fresh GPS location for the capture
            getLocationForPhoto().then(locationData => {
                canvas.toBlob(function(blob) {
                    processGPSCapturedPhoto(blob, locationData);
                    
                    // Update status
                    statusDiv.innerHTML = `
                        <i class="fas fa-check-circle text-success"></i> 
                        Photo captured! 
                        <strong>GPS:</strong> ${locationData.coordinates}
                    `;
                    
                    // Close modal after 2 seconds
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('gpsCameraModal'));
                        modal.hide();
                        
                        // Stop camera stream
                        if (gpsStream) {
                            gpsStream.getTracks().forEach(track => track.stop());
                            gpsStream = null;
                        }
                    }, 2000);
                    
                }, 'image/jpeg', 0.8);
            }).catch(error => {
                statusDiv.className = 'gps-info status-error';
                statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Failed to get GPS: ${error.message}`;
            });
        }

        function switchGPSCamera() {
            if (gpsStream) {
                gpsStream.getTracks().forEach(track => track.stop());
            }
            startGPSCamera();
        }

        function processGPSCapturedPhoto(blob, locationData) {
            const file = new File([blob], `gps_photo_${Date.now()}.jpg`, {
                type: 'image/jpeg',
                lastModified: Date.now()
            });
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const dataURL = e.target.result;
                const photoData = {
                    file: file,
                    dataURL: dataURL,
                    geotag: locationData.coordinates,
                    place: locationData.placeName,
                    timestamp: new Date().toISOString(),
                    accuracy: locationData.accuracy
                };
                
                if (currentGPSCameraType === 'residence') {
                    residencePhotos.push(photoData);
                    updateResidencePhotosPreview();
                } else if (currentGPSCameraType === 'land') {
                    landPhotos.push(photoData);
                    updateLandPhotosPreview();
                }
            };
            reader.readAsDataURL(file);
        }

        function updateResidencePhotosPreview() {
            const previewContainer = document.getElementById('residencePhotoPreview');
            const gpsInfo = document.getElementById('residenceGpsInfo');
            previewContainer.innerHTML = '';
            
            residencePhotos.forEach((photo, index) => {
                const photoDiv = document.createElement('div');
                photoDiv.className = 'photo-preview-container';
                
                photoDiv.innerHTML = `
                    <img src="${photo.dataURL}" class="photo-preview">
                    <button type="button" class="remove-photo-btn" onclick="removeResidencePhoto(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="photo-metadata">
                        <small><i class="fas fa-map-marker-alt"></i> ${photo.geotag}</small><br>
                        <small>${photo.place}</small>
                    </div>
                `;
                
                previewContainer.appendChild(photoDiv);
            });
            
            // Update GPS info
            if (residencePhotos.length > 0) {
                const lastPhoto = residencePhotos[residencePhotos.length - 1];
                gpsInfo.innerHTML = `
                    <i class="fas fa-map-marker-alt"></i> 
                    <strong>Last Photo GPS:</strong> ${lastPhoto.geotag} 
                    | <strong>Place:</strong> ${lastPhoto.place}
                    | <strong>Accuracy:</strong> ${lastPhoto.accuracy}m
                    | <strong>Total Photos:</strong> ${residencePhotos.length}
                `;
                gpsInfo.style.display = 'block';
                
                // Update hidden inputs
                document.getElementById('residencePhotoGeotag').value = lastPhoto.geotag;
                document.getElementById('residencePhotoPlace').value = lastPhoto.place;
                document.getElementById('residencePhotosData').value = JSON.stringify(residencePhotos);
            }
        }

        function removeResidencePhoto(index) {
            residencePhotos.splice(index, 1);
            updateResidencePhotosPreview();
        }

        function updateLandPhotosPreview() {
            const previewContainer = document.getElementById('landPhotosPreview');
            const gpsInfo = document.getElementById('landGpsInfo');
            previewContainer.innerHTML = '';
            
            landPhotos.forEach((photo, index) => {
                const photoDiv = document.createElement('div');
                photoDiv.className = 'photo-preview-container';
                
                photoDiv.innerHTML = `
                    <img src="${photo.dataURL}" class="photo-preview">
                    <button type="button" class="remove-photo-btn" onclick="removeLandPhoto(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="photo-metadata">
                        <small><i class="fas fa-map-marker-alt"></i> ${photo.geotag}</small><br>
                        <small>${photo.place}</small>
                    </div>
                `;
                
                previewContainer.appendChild(photoDiv);
            });
            
            // Update GPS info
            if (landPhotos.length > 0) {
                const lastPhoto = landPhotos[landPhotos.length - 1];
                gpsInfo.innerHTML = `
                    <i class="fas fa-map-marker-alt"></i> 
                    <strong>Last Photo GPS:</strong> ${lastPhoto.geotag} 
                    | <strong>Place:</strong> ${lastPhoto.place}
                    | <strong>Accuracy:</strong> ${lastPhoto.accuracy}m
                    | <strong>Total Photos:</strong> ${landPhotos.length}
                `;
                gpsInfo.style.display = 'block';
                
                // Update hidden inputs
                document.getElementById('landPhotoGeotags').value = landPhotos.map(p => p.geotag).join('|');
                document.getElementById('landPhotoPlaces').value = landPhotos.map(p => p.place).join('|');
                document.getElementById('landPhotosData').value = JSON.stringify(landPhotos);
            }
        }

        function removeLandPhoto(index) {
            landPhotos.splice(index, 1);
            updateLandPhotosPreview();
        }

        // Get current location with place name
        function getLocationForPhoto() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocation not supported'));
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    async function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;
                        
                        // Get place name using reverse geocoding
                        try {
                            const placeName = await getPlaceName(lat, lng);
                            
                            resolve({
                                coordinates: `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
                                placeName: placeName,
                                accuracy: Math.round(accuracy)
                            });
                        } catch (error) {
                            resolve({
                                coordinates: `${lat.toFixed(6)}, ${lng.toFixed(6)}`,
                                placeName: 'Location name not available',
                                accuracy: Math.round(accuracy)
                            });
                        }
                    },
                    function(error) {
                        let errorMessage = 'Unable to get location: ';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage += 'Permission denied';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage += 'Position unavailable';
                                break;
                            case error.TIMEOUT:
                                errorMessage += 'Request timeout';
                                break;
                            default:
                                errorMessage += 'Unknown error';
                                break;
                        }
                        reject(new Error(errorMessage));
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            });
        }

        // Get place name from coordinates using reverse geocoding
        async function getPlaceName(lat, lng) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
                const data = await response.json();
                
                if (data && data.address) {
                    const address = data.address;
                    // Try to get meaningful place name
                    return address.village || address.town || address.city || address.county || address.state || 'Unknown Location';
                }
                return 'Unknown Location';
            } catch (error) {
                console.error('Reverse geocoding error:', error);
                return 'Location name not available';
            }
        }

        // Geolocation functions
        function captureGeolocation() {
            const statusDiv = document.getElementById('locationStatus');
            const locationInput = document.getElementById('geotagLocation');
            
            statusDiv.innerHTML = '<div class="loading-spinner"></div> Getting your location...';
            statusDiv.className = 'geotag-status status-success';
            statusDiv.style.display = 'block';
            
            if (!navigator.geolocation) {
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Geolocation is not supported by this browser.';
                statusDiv.className = 'geotag-status status-error';
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;
                    
                    const coordinates = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    locationInput.value = coordinates;
                    
                    statusDiv.innerHTML = `
                        <i class="fas fa-check-circle text-success"></i> 
                        <strong>Location captured:</strong> ${coordinates} 
                        | <strong>Accuracy:</strong> ${Math.round(accuracy)} meters
                    `;
                    statusDiv.className = 'geotag-status status-success';
                },
                function(error) {
                    let errorMessage = 'Unable to get location: ';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage += 'Permission denied. Please allow location access.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage += 'Location information unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage += 'Location request timed out.';
                            break;
                        default:
                            errorMessage += 'An unknown error occurred.';
                            break;
                    }
                    statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${errorMessage}`;
                    statusDiv.className = 'geotag-status status-error';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        }

        function captureLandGeolocation() {
            const statusDiv = document.getElementById('landLocationStatus');
            const locationInput = document.getElementById('landGeotag');
            
            statusDiv.innerHTML = '<div class="loading-spinner"></div> Getting land location...';
            statusDiv.className = 'geotag-status status-success';
            statusDiv.style.display = 'block';
            
            if (!navigator.geolocation) {
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Geolocation is not supported by this browser.';
                statusDiv.className = 'geotag-status status-error';
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;
                    
                    const coordinates = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    locationInput.value = coordinates;
                    
                    statusDiv.innerHTML = `
                        <i class="fas fa-check-circle text-success"></i> 
                        <strong>Land location captured:</strong> ${coordinates} 
                        | <strong>Accuracy:</strong> ${Math.round(accuracy)} meters
                    `;
                    statusDiv.className = 'geotag-status status-success';
                },
                function(error) {
                    let errorMessage = 'Unable to get land location: ';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage += 'Permission denied. Please allow location access.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage += 'Location information unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMessage += 'Location request timed out.';
                            break;
                        default:
                            errorMessage += 'An unknown error occurred.';
                            break;
                    }
                    statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${errorMessage}`;
                    statusDiv.className = 'geotag-status status-error';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        }

        // File preview functionality
        function previewFile(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        preview.innerHTML = `<img src="${e.target.result}" class="upload-preview" alt="Preview">`;
                    } else if (file.type === 'application/pdf') {
                        preview.innerHTML = `
                            <div class="alert alert-info">
                                <i class="fas fa-file-pdf fa-2x"></i>
                                <div>${file.name}</div>
                                <small>PDF Document</small>
                            </div>
                        `;
                    } else {
                        preview.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-file fa-2x"></i>
                                <div>${file.name}</div>
                                <small>${file.type}</small>
                            </div>
                        `;
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        // Document upload functions
        function addDocumentInput() {
            const container = document.getElementById('documentUploads');
            const newInput = document.createElement('div');
            newInput.className = 'input-group mb-2';
            newInput.innerHTML = `
                <select class="form-select" name="document_type[]">
                    <option value="Farmer Certificate">Farmer Certificate</option>
                    <option value="Land Documents">Land Documents</option>
                    <option value="Other">Other</option>
                </select>
                <input type="file" class="form-control" name="document_file[]">
                <button type="button" class="btn btn-outline-danger" onclick="removeDocumentInput(this)">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(newInput);
        }

        function removeDocumentInput(button) {
            const container = document.getElementById('documentUploads');
            if (container.children.length > 1) {
                button.closest('.input-group').remove();
            } else {
                alert('You must have at least one document input.');
            }
        }

        // Form validation
        function validateForm() {
            let isValid = true;
            const requiredFields = [
                'farmer_name', 'dob', 'gender', 'social_category', 'farmer_category',
                'mobile', 'full_address', 'village', 'block', 'district', 'state', 'pincode'
            ];
            
            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
            
            // Check required fields
            requiredFields.forEach(field => {
                const element = document.querySelector(`[name="${field}"]`);
                if (!element || !element.value.trim()) {
                    isValid = false;
                    element.classList.add('input-error');
                    const errorElement = document.getElementById(field + 'Error');
                    if (errorElement) {
                        errorElement.textContent = 'This field is required';
                    }
                }
            });
            
            // Check farmer code
            const farmerCode = document.getElementById('farmerCodeInput').value;
            if (!farmerCode) {
                isValid = false;
                document.getElementById('codeError').textContent = 'Please generate farmer code by selecting state and district';
            }
            
            // Validate mobile
            const mobile = document.getElementById('mobile').value;
            if (mobile && !/^\d{10}$/.test(mobile)) {
                isValid = false;
                document.getElementById('mobile').classList.add('input-error');
                document.getElementById('mobileError').textContent = 'Mobile number must be 10 digits';
            }
            
            // Validate pincode
            const pincode = document.getElementById('pincode').value;
            if (pincode && !/^\d{6}$/.test(pincode)) {
                isValid = false;
                document.getElementById('pincode').classList.add('input-error');
                document.getElementById('pincodeError').textContent = 'Pincode must be 6 digits';
            }
            
            return isValid;
        }

        // Form submission handler
        document.getElementById('farmerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                alert('Please fix the validation errors before submitting.');
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="loading-spinner"></div> Saving...';
            
            // Create FormData
            const formData = new FormData(this);
            
            // Submit form
            fetch('save_farmer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const modal = new bootstrap.Modal(document.getElementById('responseModal'));
                const messageDiv = document.getElementById('responseMessage');
                const viewBtn = document.getElementById('viewFarmerBtn');
                
                if (data.success) {
                    messageDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h4><i class="fas fa-check-circle"></i> Registration Successful!</h4>
                            <p>${data.message}</p>
                            <p><strong>Farmer Code:</strong> ${data.farmer_code}</p>
                            <p><strong>Farmer ID:</strong> ${data.farmer_id}</p>
                        </div>
                    `;
                    viewBtn.style.display = 'inline-block';
                    viewBtn.onclick = function() {
                        window.location.href = 'view_farmer.php?id=' + data.farmer_id;
                    };
                    
                    // Reset form on success
                    setTimeout(() => {
                        this.reset();
                        document.getElementById('farmerCodeDisplay').textContent = 'Please select state and district';
                        document.getElementById('farmerCodeInput').value = '';
                        document.getElementById('age').value = '';
                        landPhotos = [];
                        residencePhotos = [];
                        updateLandPhotosPreview();
                        updateResidencePhotosPreview();
                        
                        // Reset product sections to default
                        const productContainer = document.getElementById('productSectionsContainer');
                        productContainer.innerHTML = `
                            <div class="product-section" id="productSection_1">
                                <div class="product-header">
                                    <div class="d-flex align-items-center">
                                        <div class="product-number">1</div>
                                        <h6 class="mb-0">Product Details</h6>
                                    </div>
                                    <button type="button" class="remove-product-btn" onclick="removeProductSection(1)" id="removeBtn_1" style="display:none;">
                                        <i class="fas fa-times me-1"></i> Remove
                                    </button>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Product Name</label>
                                        <input type="text" class="form-control" name="products[1][product_name]">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Variety</label>
                                        <input type="text" class="form-control" name="products[1][product_variety]">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Production (MT)</label>
                                        <input type="number" step="0.01" class="form-control" name="products[1][production_mt]">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Area</label>
                                        <input type="text" class="form-control" name="products[1][production_area]">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Soil Test Report</label>
                                        <input type="file" class="form-control" name="products[1][soil_report_file]" accept=".pdf,.jpg,.png">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Sowing Time</label>
                                        <input type="date" class="form-control" name="products[1][sowing_time]">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Harvesting Time</label>
                                        <input type="date" class="form-control" name="products[1][harvesting_time]">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Training Received</label>
                                        <select class="form-select" name="products[1][product_training]">
                                            <option value="">Select</option>
                                            <option>Yes</option>
                                            <option>No</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Remarks</label>
                                        <input type="text" class="form-control" name="products[1][product_remarks]">
                                    </div>
                                </div>
                            </div>
                        `;
                        productCounter = 1;
                    }, 1000);
                    
                } else {
                    messageDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h4><i class="fas fa-times-circle"></i> Registration Failed</h4>
                            <p>${data.message || 'Unknown error occurred'}</p>
                            ${data.errors ? `<ul>${data.errors.map(err => `<li>${err}</li>`).join('')}</ul>` : ''}
                        </div>
                    `;
                    viewBtn.style.display = 'none';
                }
                
                modal.show();
            })
            .catch(error => {
                console.error('Form submission error:', error);
                const modal = new bootstrap.Modal(document.getElementById('responseModal'));
                const messageDiv = document.getElementById('responseMessage');
                
                messageDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h4><i class="fas fa-times-circle"></i> Submission Error</h4>
                        <p>${error.message}</p>
                    </div>
                `;
                modal.show();
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Farmer registration form loaded');
            // Load draft data if exists
            loadDraftData();
        });

        // Draft functionality
        function saveAsDraft() {
            const formData = new FormData(document.getElementById('farmerForm'));
            const draftData = {};
            
            for (let [key, value] of formData.entries()) {
                draftData[key] = value;
            }
            
            // Add photos data
            draftData.residencePhotos = residencePhotos;
            draftData.landPhotos = landPhotos;
            draftData.productCounter = productCounter;
            
            localStorage.setItem('farmerRegistrationDraft', JSON.stringify(draftData));
            alert('Draft saved successfully!');
        }

        function loadDraftData() {
            const draftData = localStorage.getItem('farmerRegistrationDraft');
            if (draftData) {
                if (confirm('Found saved draft. Do you want to load it?')) {
                    const data = JSON.parse(draftData);
                    
                    // Populate form fields
                    Object.keys(data).forEach(key => {
                        const element = document.querySelector(`[name="${key}"]`);
                        if (element && element.type !== 'file') {
                            element.value = data[key];
                        }
                    });
                    
                    // Load photos
                    if (data.residencePhotos) {
                        residencePhotos = data.residencePhotos;
                        updateResidencePhotosPreview();
                    }
                    if (data.landPhotos) {
                        landPhotos = data.landPhotos;
                        updateLandPhotosPreview();
                    }
                    
                    // Load multiple products if exists
                    if (data.productCounter && data.productCounter > 1) {
                        productCounter = data.productCounter;
                        for (let i = 2; i <= productCounter; i++) {
                            addProductSection();
                        }
                    }
                    
                    // Regenerate farmer code if state/district selected
                    if (data.state && data.district) {
                        generateFarmerCode();
                    }
                    
                    localStorage.removeItem('farmerRegistrationDraft');
                }
            }
        }
    </script>
</body>
</html>