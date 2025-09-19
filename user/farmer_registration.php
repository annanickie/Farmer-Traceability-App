<?php
// user/farmer_registration.php
include '../includes/config.php';
include '../includes/database.php';
include '../includes/farmer_code.php'; // Include the farmer code generator

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
            color: #dc3545;
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
        
        .print-header {
            display: none;
        }
        
        .loading {
            color: #6c757d;
            font-style: italic;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
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
            <div class="alert-error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="save_farmer.php" method="POST" enctype="multipart/form-data" class="form-container">
            <!-- PERSONAL DETAILS -->
            <h5 class="section-title"><i class="fas fa-user-circle me-2"></i> PERSONAL DETAILS</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label required-field">Farmer Name</label>
                    <input type="text" class="form-control" name="farmer_name" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">FARMER CODE</label>
                    <div class="farmer-code-display" id="farmerCodeDisplay">Please select state and district</div>
                    <input type="hidden" name="farmer_code" id="farmerCodeInput">
                </div>
                <div class="col-md-3">
                    <label class="form-label required-field">Date of Birth</label>
                    <input type="date" class="form-control" name="dob" required onchange="calculateAge(this.value)">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Age</label>
                    <input type="number" class="form-control" name="age" readonly>
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
                <div class="col-md-3">
                    <label class="form-label">Aadhaar Number</label>
                    <input type="text" class="form-control" name="aadhaar" pattern="[0-9]{12}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">PAN Number</label>
                    <input type="text" class="form-control" name="pan" pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Upload Aadhaar</label>
                    <input type="file" class="form-control" name="aadhaar_file" accept=".pdf,.jpg,.png">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Upload PAN</label>
                    <input type="file" class="form-control" name="pan_file" accept=".pdf,.jpg,.png">
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
                    <label class="form-label">Geotag Location</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="geotag_location" id="geotagLocation" placeholder="Latitude, Longitude">
                        <button type="button" class="btn geotag-btn" onclick="captureGeolocation()">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
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
                <div class="col-md-4">
                    <label class="form-label">Land Documents</label>
                    <input type="file" class="form-control" name="land_docs" accept=".pdf,.jpg,.png">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Land Photo</label>
                    <input type="file" class="form-control" name="land_photo" accept="image/*">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Land Video</label>
                    <input type="file" class="form-control" name="land_video" accept="video/*">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Land Geotag</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="land_geotag" id="landGeotag" placeholder="Latitude, Longitude">
                        <button type="button" class="btn geotag-btn" onclick="captureLandGeolocation()">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
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
                            <th>Geotag</th>
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
                                <div class="input-group">
                                    <input type="text" class="form-control" name="product[0][geotag]" placeholder="Lat, Long">
                                    <button type="button" class="btn btn-sm geotag-btn" onclick="captureProductGeolocation(this)">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </button>
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
                <button type="submit" class="btn btn-success btn-lg px-5">
                    <i class="fas fa-save me-2"></i> Save Farmer
                </button>
                <button type="reset" class="btn btn-outline-secondary btn-lg px-5 ms-3">
                    <i class="fas fa-undo me-2"></i> Reset
                </button>
            </div>
        </form>
    </div>

    <script>
        // Calculate age based on date of birth
        function calculateAge(dob) {
            const birthDate = new Date(dob);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            document.querySelector('input[name="age"]').value = age;
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
                    body: `state_id=${stateId}&district_id=${districtId}&state_code=${stateCode}&district_code=${districtCode}`
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
        
        // Capture geolocation for address
        function captureGeolocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        document.getElementById('geotagLocation').value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    },
                    function(error) {
                        alert('Unable to get your location: ' + error.message);
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }
        
        // Capture geolocation for land
        function captureLandGeolocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        document.getElementById('landGeotag').value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    },
                    function(error) {
                        alert('Unable to get land location: ' + error.message);
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }
        
        // Capture geolocation for product
        function captureProductGeolocation(button) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const input = button.closest('.input-group').querySelector('input');
                        input.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    },
                    function(error) {
                        alert('Unable to get product location: ' + error.message);
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }
        
        // Add product row
        function addProductRow() {
            const table = document.getElementById('productTable');
            const rowCount = table.rows.length;
            const row = table.insertRow();
            
            row.innerHTML = `
                <td>
                    <input type="text" class="form-control" name="product[${rowCount-1}][name]" required>
                </td>
                <td>
                    <input type="text" class="form-control" name="product[${rowCount-1}][variety]">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control" name="product[${rowCount-1}][production]">
                </td>
                <td>
                    <input type="text" class="form-control" name="product[${rowCount-1}][area]">
                </td>
                <td>
                    <input type="file" class="form-control" name="product[${rowCount-1}][soil_report]" accept=".pdf,.jpg,.png">
                </td>
                <td>
                    <input type="date" class="form-control" name="product[${rowCount-1}][showing_time]">
                </td>
                <td>
                    <input type="date" class="form-control" name="product[${rowCount-1}][harvesting_time]">
                </td>
                <td>
                    <select class="form-select" name="product[${rowCount-1}][training]">
                        <option value="">Select</option>
                        <option>Yes</option>
                        <option>No</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="product[${rowCount-1}][remark]">
                </td>
                <td>
                    <div class="input-group">
                        <input type="text" class="form-control" name="product[${rowCount-1}][geotag]" placeholder="Lat, Long">
                        <button type="button" class="btn btn-sm geotag-btn" onclick="captureProductGeolocation(this)">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
        }
        
        // Remove row
        function removeRow(button) {
            const row = button.closest('tr');
            if (document.getElementById('productTable').rows.length > 2) {
                row.remove();
            } else {
                alert("You need at least one product row.");
            }
        }
        
        // Add document input
        function addDocumentInput() {
            const container = document.getElementById('documentUploads');
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `
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
            container.appendChild(div);
        }
        
        // Remove document input
        function removeDocumentInput(button) {
            const container = document.getElementById('documentUploads');
            if (container.children.length > 1) {
                button.closest('.input-group').remove();
            } else {
                alert("You need at least one document input.");
            }
        }
    </script>
</body>
</html>