<?php
// cultivation_form.php
include '../includes/config.php';
include '../includes/database.php';

// Initialize variables
$farmerData = null;
$error = '';

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if farmer_id is provided in the query string
    if (isset($_GET['farmer_id']) && !empty($_GET['farmer_id'])) {
        $farmer_id = $_GET['farmer_id'];
        
        // Fetch farmer data from database
        $query = "SELECT * FROM farmers WHERE id = :farmer_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':farmer_id', $farmer_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $farmerData = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Farmer not found with ID: " . htmlspecialchars($farmer_id);
        }
    } else {
        $error = "No farmer ID provided. Please select a farmer first.";
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
    <title>Cultivation Form - PGP Farmer Traceability</title>
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
        
        .autofilled-value {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            font-weight: 500;
        }
        
        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
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
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-container input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-success">
                <i class="fas fa-seedling me-2"></i> Cultivation Form
            </h2>
            <div>
                <button class="btn btn-outline-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i> Print Form
                </button>
                <a href="select_farmer.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-arrow-left me-2"></i> Select Another Farmer
                </a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($farmerData): ?>
        <form action="save_cultivation.php" method="POST" enctype="multipart/form-data" class="form-container">
            <input type="hidden" name="farmer_id" value="<?php echo htmlspecialchars($farmerData['id']); ?>">
            
            <!-- FARMER INFORMATION -->
            <h5 class="section-title"><i class="fas fa-user-circle me-2"></i> FARMER INFORMATION</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Farmer Name</label>
                    <div class="autofilled-value"><?php echo htmlspecialchars($farmerData['farmer_name']); ?></div>
                    <input type="hidden" name="farmer_name" value="<?php echo htmlspecialchars($farmerData['farmer_name']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Farmer Code</label>
                    <div class="autofilled-value"><?php echo htmlspecialchars($farmerData['farmer_code']); ?></div>
                    <input type="hidden" name="farmer_code" value="<?php echo htmlspecialchars($farmerData['farmer_code']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">District</label>
                    <div class="autofilled-value"><?php echo htmlspecialchars($farmerData['district']); ?></div>
                    <input type="hidden" name="district" value="<?php echo htmlspecialchars($farmerData['district']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <div class="autofilled-value"><?php echo htmlspecialchars($farmerData['state']); ?></div>
                    <input type="hidden" name="state" value="<?php echo htmlspecialchars($farmerData['state']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Village</label>
                    <div class="autofilled-value"><?php echo htmlspecialchars($farmerData['village']); ?></div>
                    <input type="hidden" name="village" value="<?php echo htmlspecialchars($farmerData['village']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Linked with FPC/Individual</label>
                    <select class="form-select" name="link_type" id="linkTypeInput">
                        <option value="">Select</option>
                        <option>FPC</option>
                        <option>Individual</option>
                        <option>Cooperative</option>
                        <option>NGO</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">FPC or Cluster Name</label>
                    <input type="text" class="form-control" name="fpc_name" id="fpcNameInput">
                </div>
            </div>

            <!-- LAND HOLDING DETAILS -->
            <h5 class="section-title mt-4"><i class="fas fa-tractor me-2"></i> LAND HOLDING DETAILS</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Land Area</label>
                    <input type="number" step="0.01" class="form-control" name="land_area" id="landAreaInput" value="<?php echo htmlspecialchars($farmerData['land_area'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unit</label>
                    <select class="form-select" name="area_unit" id="areaUnitInput">
                        <option value="">Select</option>
                        <option <?php echo (isset($farmerData['area_unit']) && $farmerData['area_unit'] == 'Bigha') ? 'selected' : ''; ?>>Bigha</option>
                        <option <?php echo (isset($farmerData['area_unit']) && $farmerData['area_unit'] == 'Hectare') ? 'selected' : ''; ?>>Hectare</option>
                        <option <?php echo (isset($farmerData['area_unit']) && $farmerData['area_unit'] == 'Acre') ? 'selected' : ''; ?>>Acre</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dag No.</label>
                    <input type="text" class="form-control" name="dag_no" id="dagNoInput" value="<?php echo htmlspecialchars($farmerData['dag_no'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Patta No.</label>
                    <input type="text" class="form-control" name="patta_no" id="pattaNoInput" value="<?php echo htmlspecialchars($farmerData['patta_no'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Land Type</label>
                    <input type="text" class="form-control" name="land_type" id="landTypeInput" value="<?php echo htmlspecialchars($farmerData['land_type'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Documents Upload</label>
                    <input type="file" class="form-control" name="land_docs" accept=".pdf,.jpg,.png">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Geotagging</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="land_geotag" id="landGeotagInput" placeholder="Latitude, Longitude">
                        <button type="button" class="btn geotag-btn" onclick="captureGeolocation('landGeotagInput')">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Geotag Photo</label>
                    <input type="file" class="form-control" name="geotag_photo" accept="image/*">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Video</label>
                    <input type="file" class="form-control" name="land_video" accept="video/*">
                </div>
                <div class="col-12">
                    <label class="form-label">Survey verification</label>
                    <textarea class="form-control" name="survey_verification" rows="2"></textarea>
                </div>
            </div>

            <!-- SOIL TEST DETAILS -->
            <h5 class="section-title mt-4"><i class="fas fa-vial me-2"></i> SOIL TEST DETAILS</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Soil Sample Collected</label>
                    <div class="checkbox-container">
                        <input type="checkbox" name="soil_sample_collected" id="soilSampleCollected" onchange="toggleSoilSampleFields()">
                        <label for="soilSampleCollected">Yes</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sample Code ID</label>
                    <input type="text" class="form-control" name="sample_code_id" id="sampleCodeId" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Collection Date</label>
                    <input type="date" class="form-control" name="collection_date" id="collectionDate" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Add Geotag</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="soil_geotag" id="soilGeotag" placeholder="Latitude, Longitude" disabled>
                        <button type="button" class="btn geotag-btn" onclick="captureGeolocation('soilGeotag')" disabled>
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Collected by FPC or Individual</label>
                    <input type="text" class="form-control" name="collected_by" id="collectedBy" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Photo</label>
                    <input type="file" class="form-control" name="collection_photo" id="collectionPhoto" accept="image/*" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Video</label>
                    <input type="file" class="form-control" name="collection_video" id="collectionVideo" accept="video/*" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Soil Testing</label>
                    <div class="checkbox-container">
                        <input type="checkbox" name="soil_testing" id="soilTesting" onchange="toggleSoilTestingFields()">
                        <label for="soilTesting">Yes</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Testing Date</label>
                    <input type="date" class="form-control" name="testing_date" id="testingDate" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Lab Name</label>
                    <input type="text" class="form-control" name="lab_name" id="labName" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Soil Test Report</label>
                    <div class="checkbox-container">
                        <input type="checkbox" name="soil_test_report" id="soilTestReport" onchange="toggleSoilReportFields()">
                        <label for="soilTestReport">Yes</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Report ID</label>
                    <input type="text" class="form-control" name="report_id" id="reportId" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Upload Report</label>
                    <input type="file" class="form-control" name="report_upload" id="reportUpload" accept=".pdf,.jpg,.png" disabled>
                </div>
                <div class="col-12">
                    <label class="form-label">Key values like pH, etc.</label>
                    <textarea class="form-control" name="soil_key_values" id="soilKeyValues" rows="2" disabled></textarea>
                </div>
            </div>

            <!-- LAND PREPARATION -->
            <h5 class="section-title mt-4"><i class="fas fa-tools me-2"></i> LAND PREPARATION</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Land Preparation</label>
                    <div class="checkbox-container">
                        <input type="checkbox" name="land_preparation" id="landPreparation" onchange="toggleLandPrepFields()">
                        <label for="landPreparation">Yes</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="preparation_date" id="preparationDate" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Photo Upload</label>
                    <input type="file" class="form-control" name="preparation_photo" id="preparationPhoto" accept="image/*" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Verification Remark</label>
                    <input type="text" class="form-control" name="preparation_remark" id="preparationRemark" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Irrigation</label>
                    <div class="checkbox-container">
                        <input type="checkbox" name="irrigation" id="irrigation" onchange="toggleIrrigationFields()">
                        <label for="irrigation">Yes</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="irrigation_date" id="irrigationDate" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Photo Upload</label>
                    <input type="file" class="form-control" name="irrigation_photo" id="irrigationPhoto" accept="image/*" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Verification Remark</label>
                    <input type="text" class="form-control" name="irrigation_remark" id="irrigationRemark" disabled>
                </div>
            </div>

            <!-- DISTRIBUTION TO FARMER -->
            <h5 class="section-title mt-4"><i class="fas fa-truck me-2"></i> DISTRIBUTION TO FARMER</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Planting Material</label>
                    <div class="checkbox-container">
                        <input type="checkbox" name="planting_material" id="plantingMaterial" onchange="togglePlantingMaterialFields()">
                        <label for="plantingMaterial">Yes</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type (Seedling/Rhizome)</label>
                    <input type="text" class="form-control" name="material_type" id="materialType" disabled>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Qty in Kg</label>
                    <input type="number" step="0.01" class="form-control" name="material_qty" id="materialQty" disabled>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="distribution_date" id="distributionDate" disabled>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Photo Upload</label>
                    <input type="file" class="form-control" name="distribution_photo" id="distributionPhoto" accept="image/*" disabled>
                </div>
                <div class="col-12">
                    <label class="form-label">Field-level record</label>
                    <textarea class="form-control" name="field_record" id="fieldRecord" rows="2" disabled></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Voucher Upload</label>
                    <input type="file" class="form-control" name="voucher_upload" id="voucherUpload" accept=".pdf,.jpg,.png" disabled>
                </div>
            </div>

            <!-- PRODUCTION DETAILS -->
            <h5 class="section-title mt-4"><i class="fas fa-seedling me-2"></i> PRODUCTION DETAILS</h5>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Sowing/Transplanting</label>
                    <div class="checkbox-container">
                        <input type="checkbox" name="sowing" id="sowing" onchange="toggleSowingFields()">
                        <label for="sowing">Yes</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Crop Name</label>
                    <input type="text" class="form-control" name="crop_name" id="cropName" disabled>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Variety</label>
                    <input type="text" class="form-control" name="crop_variety" id="cropVariety" disabled>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="sowing_date" id="sowingDate" disabled>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Add Geotag</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="sowing_geotag" id="sowingGeotag" placeholder="Latitude, Longitude" disabled>
                        <button type="button" class="btn geotag-btn" onclick="captureGeolocation('sowingGeotag')" disabled>
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Photo</label>
                    <input type="file" class="form-control" name="sowing_photo" id="sowingPhoto" accept="image/*" disabled>
                </div>
                <div class="col-12">
                    <label class="form-label">Remark</label>
                    <textarea class="form-control" name="sowing_remark" id="sowingRemark" rows="2" disabled></textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Video</label>
                    <input type="file" class="form-control" name="sowing_video" id="sowingVideo" accept="video/*" disabled>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Training Provided</label>
                    <select class="form-select" name="training_topic" id="trainingTopic">
                        <option value="">Select Topic</option>
                        <option>Sowing Techniques</option>
                        <option>Irrigation Management</option>
                        <option>Pest Control</option>
                        <option>Soil Health</option>
                        <option>Harvesting Methods</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mode</label>
                    <select class="form-select" name="training_mode" id="trainingMode">
                        <option value="">Select Mode</option>
                        <option>Offline</option>
                        <option>Online</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Conducted by</label>
                    <select class="form-select" name="training_conducted_by" id="trainingConductedBy">
                        <option value="">Select</option>
                        <option>PGP</option>
                        <option>Experts</option>
                    </select>
                </div>
            </div>

            <!-- MONITORING VISITS -->
            <h5 class="section-title mt-4"><i class="fas fa-clipboard-check me-2"></i> MONITORING VISITS</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Monitoring Visit – 1</label>
                    <input type="text" class="form-control" name="monitoring_officer_1" placeholder="Monitoring Officer">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="monitoring_date_1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Add Geotag Photo</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="monitoring_geotag_1" placeholder="Latitude, Longitude">
                        <button type="button" class="btn geotag-btn" onclick="captureGeolocation('monitoring_geotag_1')">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notes</label>
                    <input type="text" class="form-control" name="monitoring_notes_1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Monitoring Visit – 2</label>
                    <input type="text" class="form-control" name="monitoring_officer_2" placeholder="Monitoring Officer">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="monitoring_date_2">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Add Geotag Photo</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="monitoring_geotag_2" placeholder="Latitude, Longitude">
                        <button type="button" class="btn geotag-btn" onclick="captureGeolocation('monitoring_geotag_2')">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Notes</label>
                    <input type="text" class="form-control" name="monitoring_notes_2">
                </div>
                <div class="col-12">
                    <label class="form-label">Pest/Nutrient observation</label>
                    <textarea class="form-control" name="pest_observation" rows="3"></textarea>
                </div>
            </div>

            <!-- HARVESTING DETAILS -->
            <h5 class="section-title mt-4"><i class="fas fa-hands me-2"></i> HARVESTING DETAILS</h5>
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Harvesting</label>
                    <div class="checkbox-container">
                        <input type="checkbox" name="harvesting" id="harvesting" onchange="toggleHarvestingFields()">
                        <label for="harvesting">Yes</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="harvesting_date" id="harvestingDate" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Add Geotag Photo</label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="harvesting_geotag" id="harvestingGeotag" placeholder="Latitude, Longitude" disabled>
                        <button type="button" class="btn geotag-btn" onclick="captureGeolocation('harvestingGeotag')" disabled>
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Crop Details</label>
                    <input type="text" class="form-control" name="crop_details" id="cropDetails" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total Quantity</label>
                    <input type="number" step="0.01" class="form-control" name="total_quantity" id="totalQuantity" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Yield</label>
                    <input type="number" step="0.01" class="form-control" name="yield" id="yield" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Add Geotag Photo</label>
                    <input type="file" class="form-control" name="yield_photo" id="yieldPhoto" accept="image/*" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status/Condition</label>
                    <input type="text" class="form-control" name="crop_status" id="cropStatus" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Training Provided</label>
                    <select class="form-select" name="harvest_training_topic" id="harvestTrainingTopic">
                        <option value="">Select Topic</option>
                        <option>Harvesting Techniques</option>
                        <option>Post-Harvest Management</option>
                        <option>Storage Methods</option>
                        <option>Market Linkages</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mode</label>
                    <select class="form-select" name="harvest_training_mode" id="harvestTrainingMode">
                        <option value="">Select Mode</option>
                        <option>Offline</option>
                        <option>Online</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Conducted by</label>
                    <select class="form-select" name="harvest_training_conducted_by" id="harvestTrainingConductedBy">
                        <option value="">Select</option>
                        <option>PGP</option>
                        <option>Experts</option>
                    </select>
                </div>
            </div>

            <!-- SUBMIT BUTTONS -->
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success btn-lg px-5">
                    <i class="fas fa-save me-2"></i> Save Cultivation Data
                </button>
                <button type="reset" class="btn btn-outline-secondary btn-lg px-5 ms-3">
                    <i class="fas fa-undo me-2"></i> Reset
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
        // Capture geolocation
        function captureGeolocation(fieldId) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        document.getElementById(fieldId).value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    },
                    function(error) {
                        alert('Unable to get your location: ' + error.message);
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }
        
        // Toggle soil sample fields
        function toggleSoilSampleFields() {
            const isChecked = document.getElementById('soilSampleCollected').checked;
            document.getElementById('sampleCodeId').disabled = !isChecked;
            document.getElementById('collectionDate').disabled = !isChecked;
            document.getElementById('soilGeotag').disabled = !isChecked;
            document.getElementById('collectedBy').disabled = !isChecked;
            document.getElementById('collectionPhoto').disabled = !isChecked;
            document.getElementById('collectionVideo').disabled = !isChecked;
            
            // Enable geotag button
            const geotagBtn = document.querySelector('button[onclick="captureGeolocation(\'soilGeotag\')"]');
            geotagBtn.disabled = !isChecked;
        }
        
        // Toggle soil testing fields
        function toggleSoilTestingFields() {
            const isChecked = document.getElementById('soilTesting').checked;
            document.getElementById('testingDate').disabled = !isChecked;
            document.getElementById('labName').disabled = !isChecked;
        }
        
        // Toggle soil report fields
        function toggleSoilReportFields() {
            const isChecked = document.getElementById('soilTestReport').checked;
            document.getElementById('reportId').disabled = !isChecked;
            document.getElementById('reportUpload').disabled = !isChecked;
            document.getElementById('soilKeyValues').disabled = !isChecked;
        }
        
        // Toggle land preparation fields
        function toggleLandPrepFields() {
            const isChecked = document.getElementById('landPreparation').checked;
            document.getElementById('preparationDate').disabled = !isChecked;
            document.getElementById('preparationPhoto').disabled = !isChecked;
            document.getElementById('preparationRemark').disabled = !isChecked;
        }
        
        // Toggle irrigation fields
        function toggleIrrigationFields() {
            const isChecked = document.getElementById('irrigation').checked;
            document.getElementById('irrigationDate').disabled = !isChecked;
            document.getElementById('irrigationPhoto').disabled = !isChecked;
            document.getElementById('irrigationRemark').disabled = !isChecked;
        }
        
        // Toggle planting material fields
        function togglePlantingMaterialFields() {
            const isChecked = document.getElementById('plantingMaterial').checked;
            document.getElementById('materialType').disabled = !isChecked;
            document.getElementById('materialQty').disabled = !isChecked;
            document.getElementById('distributionDate').disabled = !isChecked;
            document.getElementById('distributionPhoto').disabled = !isChecked;
            document.getElementById('fieldRecord').disabled = !isChecked;
            document.getElementById('voucherUpload').disabled = !isChecked;
        }
        
        // Toggle sowing fields
        function toggleSowingFields() {
            const isChecked = document.getElementById('sowing').checked;
            document.getElementById('cropName').disabled = !isChecked;
            document.getElementById('cropVariety').disabled = !isChecked;
            document.getElementById('sowingDate').disabled = !isChecked;
            document.getElementById('sowingGeotag').disabled = !isChecked;
            document.getElementById('sowingPhoto').disabled = !isChecked;
            document.getElementById('sowingRemark').disabled = !isChecked;
            document.getElementById('sowingVideo').disabled = !isChecked;
            
            // Enable geotag button
            const geotagBtn = document.querySelector('button[onclick="captureGeolocation(\'sowingGeotag\')"]');
            geotagBtn.disabled = !isChecked;
        }
        
        // Toggle harvesting fields
        function toggleHarvestingFields() {
            const isChecked = document.getElementById('harvesting').checked;
            document.getElementById('harvestingDate').disabled = !isChecked;
            document.getElementById('harvestingGeotag').disabled = !isChecked;
            document.getElementById('cropDetails').disabled = !isChecked;
            document.getElementById('totalQuantity').disabled = !isChecked;
            document.getElementById('yield').disabled = !isChecked;
            document.getElementById('yieldPhoto').disabled = !isChecked;
            document.getElementById('cropStatus').disabled = !isChecked;
            
            // Enable geotag button
            const geotagBtn = document.querySelector('button[onclick="captureGeolocation(\'harvestingGeotag\')"]');
            geotagBtn.disabled = !isChecked;
        }
    </script>
</body>
</html>