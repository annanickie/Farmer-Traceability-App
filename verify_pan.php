<?php
// user/farmer_registration.php

// Start session for verification tracking
session_start();

// Define root path
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
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Fetch states from database using the function from farmer_code.php
    $states = getStates($conn);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Generate CSRF token for verification requests
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
        
        .geotag-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .geotag-preview {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }
        
        .geotag-map {
            height: 200px;
            width: 100%;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }
        
        .geotag-btn {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: white;
        }
        
        .geotag-btn:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
            color: white;
        }
        
        .geotag-btn:disabled {
            background-color: #6c757d;
            border-color: #6c757d;
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
        
        .camera-preview {
            width: 100%;
            max-width: 300px;
            height: 200px;
            border: 2px dashed #ccc;
            border-radius: 5px;
            margin: 10px 0;
            display: none;
        }
        
        .verification-badge {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        .verified {
            background-color: var(--success-color);
            color: white;
        }
        
        .not-verified {
            background-color: var(--danger-color);
            color: white;
        }
        
        .pending {
            background-color: var(--warning-color);
            color: black;
        }
        
        .duplicate-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 5px;
            font-size: 0.875rem;
        }
        
        .verification-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
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
        
        .print-header {
            display: none;
        }
        
        @media print {
            body {
                background-color: white;
                font-size: 12pt;
            }
            
            .container {
                width: 100%;
                max-width: none;
            }
            
            .form-container {
                box-shadow: none;
                border: none;
                padding: 0;
            }
            
            .btn, .input-group-text, .input-group-btn {
                display: none !important;
            }
            
            .section-title {
                color: black;
                border-bottom: 1px solid #000;
            }
            
            .print-header {
                display: block;
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }
            
            .print-header img {
                height: 60px;
            }
            
            .farmer-code-display {
                border: 1px solid #000;
                color: black;
            }
            
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="print-header">
            <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiMwZTljNGQiLz48cGF0aCBkPSJNMjUgMjVINTBWNjVIMjVWNjVINDBWNDBIMjVWMjVaIiBmaWxsPSJ3aGl0ZSIvPjxwYXRoIGQ9Ik01MCAyNVY0MEg3NVYyNUg1MFpNNzUgNDBWNjVINjIuNVY1Mi41SDUwVjY1SDM3LjVWNTIuNUgyNVY2NUg3NVY0MFoiIGZpbGw9IndoaXRlIi8+PC9zdmc+" alt="PGP India Logo">
            <h2 class="mt-2">PGP Farmer Traceability</h2>
            <h3 class="mb-0">Farmer Registration Form</h3>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-success">
                <i class="fas fa-user-plus me-2"></i> Farmer Registration
            </h2>
            <button class="btn btn-outline-primary" onclick="window.print()">
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
                </div>
                <div class="col-md-6">
                    <label class="form-label">FARMER CODE</label>
                    <div class="farmer-code-display" id="farmerCodeDisplay">Please select state and district</div>
                    <input type="hidden" name="farmer_code" id="farmerCodeInput">
                </div>
                <div class="col-md-3">
                    <label class="form-label required-field">Date of Birth</label>
                    <input type="date" class="form-control" name="dob" id="dob" required onchange="calculateAge(this.value)">
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
                </div>
                <div class="col-md-3">
                    <label class="form-label required-field">Mobile Number</label>
                    <input type="tel" class="form-control" name="mobile" pattern="[0-9]{10}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" name="email">
                </div>
                
                <!-- AADHAAR SECTION WITH VERIFICATION -->
                <div class="col-md-4">
                    <label class="form-label">
                        Aadhaar Number 
                        <span class="verification-badge pending" id="aadhaarStatus">Not Verified</span>
                    </label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="aadhaar" id="aadhaarInput" pattern="[0-9]{12}" 
                               onblur="verifyAadhaar(this.value)" maxlength="12">
                        <button type="button" class="btn btn-outline-primary" onclick="verifyAadhaar(document.getElementById('aadhaarInput').value)">
                            <i class="fas fa-shield-alt"></i>
                        </button>
                    </div>
                    <div id="aadhaarVerificationResult"></div>
                    <input type="hidden" name="aadhaar_verified" id="aadhaarVerified" value="0">
                </div>
                
                <!-- PAN SECTION WITH VERIFICATION -->
                <div class="col-md-4">
                    <label class="form-label">
                        PAN Number 
                        <span class="verification-badge pending" id="panStatus">Not Verified</span>
                    </label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="pan" id="panInput" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}" 
                               onblur="verifyPAN(this.value)" maxlength="10" style="text-transform:uppercase">
                        <button type="button" class="btn btn-outline-primary" onclick="verifyPAN(document.getElementById('panInput').value)">
                            <i class="fas fa-shield-alt"></i>
                        </button>
                    </div>
                    <div id="panVerificationResult"></div>
                    <input type="hidden" name="pan_verified" id="panVerified" value="0">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Upload Aadhaar</label>
                    <input type="file" class="form-control" name="aadhaar_file" accept=".pdf,.jpg,.png">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Upload PAN</label>
                    <input type="file" class="form-control" name="pan_file" accept=".pdf,.jpg,.png">
                </div>
            </div>

            <!-- DOCUMENT VERIFICATION SECTION -->
            <div class="verification-section mt-3">
                <h6><i class="fas fa-shield-alt me-2"></i>Document Verification</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="manual_verification" id="manualVerification">
                            <label class="form-check-label" for="manualVerification">
                                I confirm that I have physically verified the original documents
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Verification Method</label>
                        <select class="form-select" name="verification_method">
                            <option value="manual">Manual Verification</option>
                            <option value="otp">OTP Verification</option>
                            <option value="third_party">Third Party API</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- LOCATION DETAILS -->
            <h5 class="section-title mt-4"><i class="fas fa-map-marker-alt me-2"></i> LOCATION DETAILS</h5>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label required-field">Full Address</label>
                    <textarea class="form-control" name="full_address" rows="2" required></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">Village</label>
                    <input type="text" class="form-control" name="village" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">Block</label>
                    <input type="text" class="form-control" name="block" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">District</label>
                    <select class="form-select" name="district" id="districtSelect" required onchange="generateFarmerCode()">
                        <option value="">Select District</option>
                        <!-- Districts will be populated dynamically -->
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">State</label>
                    <select class="form-select" name="state" id="stateSelect" required onchange="updateDistricts(); generateFarmerCode()">
                        <option value="">Select State</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?php echo $state['id']; ?>" data-code="<?php echo $state['state_code']; ?>">
                                <?php echo htmlspecialchars($state['state_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label required-field">Pincode</label>
                    <input type="text" class="form-control" name="pincode" pattern="[0-9]{6}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Residence Geotag</label>
                    <div class="geotag-section">
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="geotag_location" id="geotagLocation" placeholder="Latitude, Longitude" readonly>
                            <button type="button" class="btn geotag-btn" id="captureLocationBtn" onclick="captureGeolocation()">
                                <i class="fas fa-map-marker-alt"></i> Get Location
                            </button>
                        </div>
                        <div id="locationStatus" class="geotag-status"></div>
                        <div id="locationMap" class="geotag-map"></div>
                        
                        <!-- Geotagged Photo Section -->
                        <label class="form-label mt-3">Geotagged Residence Photo</label>
                        <div class="input-group mb-2">
                            <input type="file" class="form-control" name="residence_photo" id="residencePhoto" accept="image/*" capture="environment">
                            <button type="button" class="btn geotag-btn" onclick="captureGeotaggedPhoto('residence')">
                                <i class="fas fa-camera"></i> Capture Photo
                            </button>
                        </div>
                        <video id="residenceCamera" class="camera-preview" autoplay></video>
                        <canvas id="residenceCanvas" style="display:none;"></canvas>
                        <img id="residencePreview" class="geotag-preview">
                        <input type="hidden" name="residence_photo_geotag" id="residencePhotoGeotag">
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
                                    <button type="button" class="btn geotag-btn" onclick="captureLandGeolocation()">
                                        <i class="fas fa-map-marker-alt"></i> Get Land Location
                                    </button>
                                </div>
                                <div id="landLocationStatus" class="geotag-status"></div>
                                <div id="landMap" class="geotag-map"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Geotagged Land Photos</label>
                                <div class="input-group mb-2">
                                    <input type="file" class="form-control" name="land_photo" id="landPhoto" accept="image/*" capture="environment" multiple>
                                    <button type="button" class="btn geotag-btn" onclick="captureGeotaggedPhoto('land')">
                                        <i class="fas fa-camera"></i> Capture Land Photo
                                    </button>
                                </div>
                                <video id="landCamera" class="camera-preview" autoplay></video>
                                <canvas id="landCanvas" style="display:none;"></canvas>
                                <div id="landPhotosPreview" class="d-flex flex-wrap gap-2"></div>
                                <input type="hidden" name="land_photo_geotags" id="landPhotoGeotags">
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

            <!-- PRODUCT DETAILS -->
            <h5 class="section-title mt-4"><i class="fas fa-seedling me-2"></i> PRODUCT DETAILS</h5>
            <div class="table-responsive">
                <table class="table table-bordered" id="productTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Variety</th>
                            <th>Production (MT)</th>
                            <th>Area</th>
                            <th>Soil Test Report</th>
                            <th>Showing Time</th>
                            <th>Harvesting Time</th>
                            <th>Training</th>
                            <th>Remark</th>
                            <th>Geotag & Photo</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <input type="text" class="form-control" name="product[0][name]" required>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="product[0][variety]">
                            </td>
                            <td>
                                <input type="number" step="0.01" class="form-control" name="product[0][production]">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="product[0][area]">
                            </td>
                            <td>
                                <input type="file" class="form-control" name="product[0][soil_report]" accept=".pdf,.jpg,.png">
                            </td>
                            <td>
                                <input type="date" class="form-control" name="product[0][showing_time]">
                            </td>
                            <td>
                                <input type="date" class="form-control" name="product[0][harvesting_time]">
                            </td>
                            <td>
                                <select class="form-select" name="product[0][training]">
                                    <option value="">Select</option>
                                    <option>Yes</option>
                                    <option>No</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="product[0][remark]">
                            </td>
                            <td>
                                <div class="geotag-section">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="product[0][geotag]" placeholder="Lat, Long" readonly>
                                        <button type="button" class="btn btn-sm geotag-btn" onclick="captureProductGeolocation(this)">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </button>
                                    </div>
                                    <input type="file" class="form-control mb-2" name="product[0][crop_photo]" accept="image/*" capture="environment">
                                    <button type="button" class="btn btn-sm geotag-btn w-100" onclick="captureProductPhoto(this)">
                                        <i class="fas fa-camera"></i> Capture Crop Photo
                                    </button>
                                    <input type="hidden" name="product[0][crop_photo_geotag]">
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-primary mb-4" onclick="addProductRow()">
                <i class="fas fa-plus me-1"></i> Add Product
            </button>

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
            </div>
        </form>
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
        // Global variables for geotagging
        let currentLocation = null;
        let currentGeotagPhotoType = null;
        let landPhotos = [];
        let stream = null;
        let productRowCount = 1;

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
        
        // Generate farmer code based on state and district
        function generateFarmerCode() {
            const stateSelect = document.getElementById('stateSelect');
            const districtSelect = document.getElementById('districtSelect');
            const farmerCodeDisplay = document.getElementById('farmerCodeDisplay');
            const farmerCodeInput = document.getElementById('farmerCodeInput');
            
            if (stateSelect.value && districtSelect.value) {
                const stateId = stateSelect.value;
                const districtId = districtSelect.value;
                const stateCode = stateSelect.options[stateSelect.selectedIndex].getAttribute('data-code');
                const districtCode = districtSelect.options[districtSelect.selectedIndex].getAttribute('data-code');
                
                // Show loading state
                farmerCodeDisplay.textContent = 'Generating farmer code...';
                
                // Call server to generate the farmer code
                fetch('../generate_farmer_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `state_id=${stateId}&district_id=${districtId}&state_code=${stateCode}&district_code=${districtCode}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        farmerCodeDisplay.textContent = data.farmer_code;
                        farmerCodeInput.value = data.farmer_code;
                    } else {
                        farmerCodeDisplay.textContent = 'Error generating code';
                        console.error('Error:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error fetching farmer code:', error);
                    farmerCodeDisplay.textContent = 'Error generating code';
                });
            } else {
                farmerCodeDisplay.textContent = 'Please select state and district';
                farmerCodeInput.value = '';
            }
        }
        
        // Update districts based on selected state
        function updateDistricts() {
            const stateId = document.getElementById('stateSelect').value;
            const districtSelect = document.getElementById('districtSelect');
            
            if (!stateId) {
                districtSelect.innerHTML = '<option value="">Select District</option>';
                return;
            }
            
            // Show loading state
            districtSelect.innerHTML = '<option value="">Loading districts...</option>';
            
            fetch(`../get_districts.php?state_id=${stateId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(districts => {
                    districtSelect.innerHTML = '<option value="">Select District</option>';
                    
                    if (districts.length === 0) {
                        districtSelect.innerHTML = '<option value="">No districts available for this state</option>';
                        return;
                    }
                    
                    districts.forEach(district => {
                        const option = document.createElement('option');
                        option.value = district.id;
                        option.textContent = district.district_name;
                        option.setAttribute('data-code', district.district_code);
                        districtSelect.appendChild(option);
                    });
                    
                    // Clear farmer code when districts change
                    document.getElementById('farmerCodeDisplay').textContent = 'Please select state and district';
                    document.getElementById('farmerCodeInput').value = '';
                })
                .catch(error => {
                    console.error('Error fetching districts:', error);
                    districtSelect.innerHTML = '<option value="">Error loading districts</option>';
                });
        }
        
        // Aadhaar verification
        function verifyAadhaar(aadhaarNumber) {
            if (!aadhaarNumber || aadhaarNumber.length !== 12) {
                return;
            }
            
            const statusElement = document.getElementById('aadhaarStatus');
            const resultElement = document.getElementById('aadhaarVerificationResult');
            const verifiedInput = document.getElementById('aadhaarVerified');
            
            statusElement.className = 'verification-badge pending';
            statusElement.textContent = 'Verifying...';
            resultElement.innerHTML = '<div class="loading-spinner"></div> Verifying Aadhaar...';
            
            fetch('../verify_aadhaar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `aadhaar=${aadhaarNumber}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    statusElement.className = 'verification-badge verified';
                    statusElement.textContent = 'Verified';
                    resultElement.innerHTML = '<div class="text-success"><i class="fas fa-check-circle"></i> Aadhaar is valid and unique</div>';
                    verifiedInput.value = '1';
                } else {
                    statusElement.className = 'verification-badge not-verified';
                    statusElement.textContent = 'Not Verified';
                    
                    if (data.is_duplicate) {
                        resultElement.innerHTML = `
                            <div class="duplicate-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Aadhaar already registered to: ${data.existing_farmer.farmer_name} (${data.existing_farmer.farmer_code})
                            </div>
                        `;
                    } else {
                        resultElement.innerHTML = `<div class="text-danger"><i class="fas fa-times-circle"></i> ${data.message}</div>`;
                    }
                    verifiedInput.value = '0';
                }
            })
            .catch(error => {
                console.error('Aadhaar verification error:', error);
                statusElement.className = 'verification-badge not-verified';
                statusElement.textContent = 'Error';
                resultElement.innerHTML = `<div class="text-danger"><i class="fas fa-times-circle"></i> Verification failed: ${error.message}</div>`;
                verifiedInput.value = '0';
            });
        }
        
        // PAN verification
        function verifyPAN(panNumber) {
            if (!panNumber || panNumber.length !== 10) {
                return;
            }
            
            const statusElement = document.getElementById('panStatus');
            const resultElement = document.getElementById('panVerificationResult');
            const verifiedInput = document.getElementById('panVerified');
            
            statusElement.className = 'verification-badge pending';
            statusElement.textContent = 'Verifying...';
            resultElement.innerHTML = '<div class="loading-spinner"></div> Verifying PAN...';
            
            fetch('../verify_pan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `pan=${panNumber}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    statusElement.className = 'verification-badge verified';
                    statusElement.textContent = 'Verified';
                    resultElement.innerHTML = '<div class="text-success"><i class="fas fa-check-circle"></i> PAN is valid and unique</div>';
                    verifiedInput.value = '1';
                } else {
                    statusElement.className = 'verification-badge not-verified';
                    statusElement.textContent = 'Not Verified';
                    
                    if (data.is_duplicate) {
                        resultElement.innerHTML = `
                            <div class="duplicate-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                PAN already registered to: ${data.existing_farmer.farmer_name} (${data.existing_farmer.farmer_code})
                            </div>
                        `;
                    } else {
                        resultElement.innerHTML = `<div class="text-danger"><i class="fas fa-times-circle"></i> ${data.message}</div>`;
                    }
                    verifiedInput.value = '0';
                }
            })
            .catch(error => {
                console.error('PAN verification error:', error);
                statusElement.className = 'verification-badge not-verified';
                statusElement.textContent = 'Error';
                resultElement.innerHTML = `<div class="text-danger"><i class="fas fa-times-circle"></i> Verification failed: ${error.message}</div>`;
                verifiedInput.value = '0';
            });
        }
        
        // Capture geolocation for address
        function captureGeolocation() {
            const statusDiv = document.getElementById('locationStatus');
            const mapDiv = document.getElementById('locationMap');
            const button = document.getElementById('captureLocationBtn');
            
            statusDiv.style.display = 'block';
            statusDiv.className = 'geotag-status status-success';
            statusDiv.innerHTML = '<div class="loading-spinner"></div> Getting your location...';
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting Location...';
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;
                        
                        currentLocation = { lat, lng };
                        document.getElementById('geotagLocation').value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                        
                        statusDiv.className = 'geotag-status status-success';
                        statusDiv.innerHTML = `<i class="fas fa-check-circle"></i> Location captured! Accuracy: ${Math.round(accuracy)} meters`;
                        
                        // Show map preview
                        mapDiv.style.display = 'block';
                        mapDiv.innerHTML = `<iframe 
                            width="100%" 
                            height="100%" 
                            frameborder="0" 
                            scrolling="no" 
                            marginheight="0" 
                            marginwidth="0" 
                            src="https://maps.google.com/maps?q=${lat},${lng}&z=17&output=embed"
                            style="border-radius: 5px;">
                        </iframe>`;
                        
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-map-marker-alt"></i> Get Location';
                    },
                    function(error) {
                        statusDiv.className = 'geotag-status status-error';
                        let errorMessage = 'Unable to get your location: ';
                        
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage += 'User denied the request for Geolocation.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage += 'Location information is unavailable.';
                                break;
                            case error.TIMEOUT:
                                errorMessage += 'The request to get user location timed out.';
                                break;
                            default:
                                errorMessage += 'An unknown error occurred.';
                                break;
                        }
                        
                        statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${errorMessage}`;
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-map-marker-alt"></i> Get Location';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 30000,
                        maximumAge: 60000
                    }
                );
            } else {
                statusDiv.className = 'geotag-status status-error';
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Geolocation is not supported by this browser.';
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-map-marker-alt"></i> Get Location';
            }
        }
        
        // Capture geolocation for land
        function captureLandGeolocation() {
            const statusDiv = document.getElementById('landLocationStatus');
            const mapDiv = document.getElementById('landMap');
            
            statusDiv.style.display = 'block';
            statusDiv.className = 'geotag-status status-success';
            statusDiv.innerHTML = '<div class="loading-spinner"></div> Getting land location...';
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;
                        
                        document.getElementById('landGeotag').value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                        
                        statusDiv.className = 'geotag-status status-success';
                        statusDiv.innerHTML = `<i class="fas fa-check-circle"></i> Land location captured! Accuracy: ${Math.round(accuracy)} meters`;
                        
                        // Show map preview
                        mapDiv.style.display = 'block';
                        mapDiv.innerHTML = `<iframe 
                            width="100%" 
                            height="100%" 
                            frameborder="0" 
                            scrolling="no" 
                            marginheight="0" 
                            marginwidth="0" 
                            src="https://maps.google.com/maps?q=${lat},${lng}&z=17&output=embed"
                            style="border-radius: 5px;">
                        </iframe>`;
                    },
                    function(error) {
                        statusDiv.className = 'geotag-status status-error';
                        statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Unable to get land location: ${error.message}`;
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 30000
                    }
                );
            } else {
                statusDiv.className = 'geotag-status status-error';
                statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Geolocation is not supported by this browser.';
            }
        }
        
        // Capture geolocation for product
        function captureProductGeolocation(button) {
            const input = button.closest('.input-group').querySelector('input');
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        input.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                        
                        // Show success message
                        const statusDiv = document.createElement('div');
                        statusDiv.className = 'geotag-status status-success';
                        statusDiv.innerHTML = '<i class="fas fa-check-circle"></i> Location captured!';
                        statusDiv.style.display = 'block';
                        button.closest('.geotag-section').appendChild(statusDiv);
                        
                        // Remove status after 3 seconds
                        setTimeout(() => {
                            statusDiv.remove();
                        }, 3000);
                    },
                    function(error) {
                        const statusDiv = document.createElement('div');
                        statusDiv.className = 'geotag-status status-error';
                        statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${error.message}`;
                        statusDiv.style.display = 'block';
                        button.closest('.geotag-section').appendChild(statusDiv);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }
        
        // Capture geotagged photo
        function captureGeotaggedPhoto(type) {
            currentGeotagPhotoType = type;
            const cameraId = type + 'Camera';
            const video = document.getElementById(cameraId);
            
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Camera access is not supported by your browser.');
                return;
            }
            
            // Stop any existing stream
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            
            // Show camera preview
            video.style.display = 'block';
            
            // Access camera
            navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                },
                audio: false 
            })
            .then(function(cameraStream) {
                stream = cameraStream;
                video.srcObject = stream;
                
                // Create capture button
                const captureSection = video.parentElement;
                let captureBtn = captureSection.querySelector('.capture-btn');
                
                if (!captureBtn) {
                    captureBtn = document.createElement('button');
                    captureBtn.type = 'button';
                    captureBtn.className = 'btn btn-success mt-2';
                    captureBtn.innerHTML = '<i class="fas fa-camera"></i> Capture Photo';
                    captureBtn.onclick = function() { takePicture(type); };
                    captureSection.appendChild(captureBtn);
                }
            })
            .catch(function(error) {
                console.error('Camera error:', error);
                alert('Unable to access camera: ' + error.message);
            });
        }
        
        // Take picture from camera
        function takePicture(type) {
            const cameraId = type + 'Camera';
            const canvasId = type + 'Canvas';
            const previewId = type + 'Preview';
            const geotagInputId = type + 'PhotoGeotag';
            
            const video = document.getElementById(cameraId);
            const canvas = document.getElementById(canvasId);
            const preview = document.getElementById(previewId);
            const context = canvas.getContext('2d');
            
            // Set canvas dimensions to match video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Draw current video frame to canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convert canvas to data URL
            const dataURL = canvas.toDataURL('image/jpeg', 0.8);
            
            // Show preview
            preview.src = dataURL;
            preview.style.display = 'block';
            
            // Get current location for geotagging
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const geotag = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                        
                        // Store geotag in hidden input
                        document.getElementById(geotagInputId).value = geotag;
                        
                        // For land photos, add to array
                        if (type === 'land') {
                            landPhotos.push({
                                dataURL: dataURL,
                                geotag: geotag,
                                timestamp: new Date().toISOString()
                            });
                            updateLandPhotosPreview();
                        }
                        
                        // Stop camera stream
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                            stream = null;
                        }
                        
                        // Hide camera
                        video.style.display = 'none';
                        
                        // Remove capture button
                        const captureBtn = video.parentElement.querySelector('.capture-btn');
                        if (captureBtn) {
                            captureBtn.remove();
                        }
                        
                        alert(`Photo captured with geotag: ${geotag}`);
                    },
                    function(error) {
                        alert('Photo captured but could not get location. Please enable location services.');
                        
                        // Stop camera stream even if location fails
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                            stream = null;
                        }
                        
                        video.style.display = 'none';
                        const captureBtn = video.parentElement.querySelector('.capture-btn');
                        if (captureBtn) {
                            captureBtn.remove();
                        }
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000
                    }
                );
            } else {
                alert('Photo captured but geolocation is not available.');
                
                // Stop camera stream
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
                
                video.style.display = 'none';
                const captureBtn = video.parentElement.querySelector('.capture-btn');
                if (captureBtn) {
                    captureBtn.remove();
                }
            }
        }
        
        // Update land photos preview
        function updateLandPhotosPreview() {
            const previewContainer = document.getElementById('landPhotosPreview');
            previewContainer.innerHTML = '';
            
            landPhotos.forEach((photo, index) => {
                const photoDiv = document.createElement('div');
                photoDiv.className = 'position-relative';
                photoDiv.style.width = '100px';
                
                photoDiv.innerHTML = `
                    <img src="${photo.dataURL}" class="geotag-preview" style="height: 80px; width: 100px;">
                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" onclick="removeLandPhoto(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                    <small class="d-block">${photo.geotag}</small>
                `;
                
                previewContainer.appendChild(photoDiv);
            });
            
            // Update hidden input with all geotags
            const geotags = landPhotos.map(photo => photo.geotag).join('|');
            document.getElementById('landPhotoGeotags').value = geotags;
        }
        
        // Remove land photo
        function removeLandPhoto(index) {
            landPhotos.splice(index, 1);
            updateLandPhotosPreview();
        }
        
        // Capture product photo
        function captureProductPhoto(button) {
            const productSection = button.closest('.geotag-section');
            const fileInput = productSection.querySelector('input[type="file"]');
            const geotagInput = productSection.querySelector('input[type="hidden"]');
            
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Camera access is not supported by your browser.');
                return;
            }
            
            // Access camera
            navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: 'environment' },
                audio: false 
            })
            .then(function(cameraStream) {
                // Create temporary video element
                const tempVideo = document.createElement('video');
                tempVideo.srcObject = cameraStream;
                tempVideo.play();
                
                // Create temporary canvas
                const tempCanvas = document.createElement('canvas');
                const tempContext = tempCanvas.getContext('2d');
                
                // Wait for video to be ready
                tempVideo.addEventListener('loadedmetadata', function() {
                    tempCanvas.width = tempVideo.videoWidth;
                    tempCanvas.height = tempVideo.videoHeight;
                    
                    // Get location first
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                const lat = position.coords.latitude;
                                const lng = position.coords.longitude;
                                const geotag = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                                
                                // Draw frame and capture
                                tempContext.drawImage(tempVideo, 0, 0);
                                tempCanvas.toBlob(function(blob) {
                                    // Create file from blob
                                    const file = new File([blob], `crop_photo_${Date.now()}.jpg`, {
                                        type: 'image/jpeg',
                                        lastModified: Date.now()
                                    });
                                    
                                    // Create DataTransfer to set files
                                    const dataTransfer = new DataTransfer();
                                    dataTransfer.items.add(file);
                                    fileInput.files = dataTransfer.files;
                                    
                                    // Set geotag
                                    geotagInput.value = geotag;
                                    
                                    // Stop camera
                                    cameraStream.getTracks().forEach(track => track.stop());
                                    
                                    alert(`Crop photo captured with geotag: ${geotag}`);
                                }, 'image/jpeg', 0.8);
                            },
                            function(error) {
                                alert('Could not get location for photo.');
                                cameraStream.getTracks().forEach(track => track.stop());
                            }
                        );
                    } else {
                        alert('Geolocation not available for photo.');
                        cameraStream.getTracks().forEach(track => track.stop());
                    }
                });
            })
            .catch(function(error) {
                alert('Unable to access camera: ' + error.message);
            });
        }
        
        // Add product row
        function addProductRow() {
            const table = document.getElementById('productTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            newRow.innerHTML = `
                <td>
                    <input type="text" class="form-control" name="product[${productRowCount}][name]" required>
                </td>
                <td>
                    <input type="text" class="form-control" name="product[${productRowCount}][variety]">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control" name="product[${productRowCount}][production]">
                </td>
                <td>
                    <input type="text" class="form-control" name="product[${productRowCount}][area]">
                </td>
                <td>
                    <input type="file" class="form-control" name="product[${productRowCount}][soil_report]" accept=".pdf,.jpg,.png">
                </td>
                <td>
                    <input type="date" class="form-control" name="product[${productRowCount}][showing_time]">
                </td>
                <td>
                    <input type="date" class="form-control" name="product[${productRowCount}][harvesting_time]">
                </td>
                <td>
                    <select class="form-select" name="product[${productRowCount}][training]">
                        <option value="">Select</option>
                        <option>Yes</option>
                        <option>No</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="product[${productRowCount}][remark]">
                </td>
                <td>
                    <div class="geotag-section">
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="product[${productRowCount}][geotag]" placeholder="Lat, Long" readonly>
                            <button type="button" class="btn btn-sm geotag-btn" onclick="captureProductGeolocation(this)">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                        </div>
                        <input type="file" class="form-control mb-2" name="product[${productRowCount}][crop_photo]" accept="image/*" capture="environment">
                        <button type="button" class="btn btn-sm geotag-btn w-100" onclick="captureProductPhoto(this)">
                            <i class="fas fa-camera"></i> Capture Crop Photo
                        </button>
                        <input type="hidden" name="product[${productRowCount}][crop_photo_geotag]">
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            productRowCount++;
        }
        
        // Remove product row
        function removeRow(button) {
            const row = button.closest('tr');
            if (document.getElementById('productTable').getElementsByTagName('tbody')[0].rows.length > 1) {
                row.remove();
            } else {
                alert('At least one product row is required.');
            }
        }
        
        // Add document input
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
        
        // Remove document input
        function removeDocumentInput(button) {
            const container = document.getElementById('documentUploads');
            if (container.children.length > 1) {
                button.closest('.input-group').remove();
            } else {
                alert('At least one document input is required.');
            }
        }
        
        // Form submission handling
        document.getElementById('farmerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="loading-spinner"></div> Saving...';
            
            // Validate required fields
            const requiredFields = this.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!valid) {
                alert('Please fill all required fields.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }
            
            // Submit form via AJAX
            const formData = new FormData(this);
            
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
                            <p>Farmer registered successfully with code: <strong>${data.farmer_code}</strong></p>
                            <p>Farmer ID: ${data.farmer_id}</p>
                        </div>
                    `;
                    viewBtn.style.display = 'inline-block';
                    viewBtn.onclick = function() {
                        window.location.href = 'view_farmer.php?id=' + data.farmer_id;
                    };
                    // Reset form on success
                    document.getElementById('farmerForm').reset();
                    document.getElementById('farmerCodeDisplay').textContent = 'Please select state and district';
                    landPhotos = [];
                    updateLandPhotosPreview();
                } else {
                    messageDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h4><i class="fas fa-times-circle"></i> Registration Failed</h4>
                            <p>${data.message}</p>
                        </div>
                    `;
                    viewBtn.style.display = 'none';
                }
                
                modal.show();
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            })
            .catch(error => {
                console.error('Form submission error:', error);
                alert('An error occurred while saving the farmer. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    </script>
</body>
</html>