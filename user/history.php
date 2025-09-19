<?php
// history.php
include '../includes/config.php';
include '../includes/database.php';

// Initialize variables
$farmer = null;
$cultivationRecords = [];
$error = '';

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if farmer_id is provided
    if (isset($_GET['farmer_id']) && !empty($_GET['farmer_id'])) {
        $farmer_id = $_GET['farmer_id'];
        
        // Fetch farmer details
        $query = "SELECT * FROM farmers WHERE id = :farmer_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':farmer_id', $farmer_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $farmer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Fetch cultivation records for this farmer
            $query = "SELECT * FROM cultivation_records WHERE farmer_id = :farmer_id ORDER BY created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':farmer_id', $farmer_id);
            $stmt->execute();
            
            $cultivationRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Farmer History - PGP Farmer Traceability</title>
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
        
        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .farmer-info-card {
            background-color: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .record-card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .record-card:hover {
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .record-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: 500;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
            margin-left: 10px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: var(--primary-color);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--primary-color);
            border: 2px solid white;
        }
        
        .collapse-btn {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .collapse-btn:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-success">
                <i class="fas fa-history me-2"></i> Farmer History
            </h2>
            <div>
                <a href="select_farmer.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Select Another Farmer
                </a>
                <a href="cultivation_form.php?farmer_id=<?php echo $farmer['id'] ?? ''; ?>" class="btn btn-success ms-2">
                    <i class="fas fa-plus me-2"></i> New Cultivation Record
                </a>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($farmer): ?>
        <!-- FARMER INFORMATION -->
        <div class="form-container">
            <h5 class="section-title"><i class="fas fa-user-circle me-2"></i> FARMER INFORMATION</h5>
            
            <div class="farmer-info-card">
                <div class="row">
                    <div class="col-md-6">
                        <h4><?php echo htmlspecialchars($farmer['farmer_name']); ?></h4>
                        <div class="d-flex align-items-center mt-2">
                            <span class="badge bg-success me-2"><?php echo htmlspecialchars($farmer['farmer_code']); ?></span>
                            <span class="text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($farmer['village']); ?>, <?php echo htmlspecialchars($farmer['district']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Category</small>
                                <p class="mb-0"><?php echo htmlspecialchars($farmer['farmer_category']); ?></p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Social Category</small>
                                <p class="mb-0"><?php echo htmlspecialchars($farmer['social_category']); ?></p>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-muted">Land Area</small>
                                <p class="mb-0"><?php echo htmlspecialchars($farmer['land_area']); ?> <?php echo htmlspecialchars($farmer['area_unit']); ?></p>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-muted">Contact</small>
                                <p class="mb-0"><?php echo htmlspecialchars($farmer['mobile']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CULTIVATION HISTORY -->
            <h5 class="section-title"><i class="fas fa-seedling me-2"></i> CULTIVATION HISTORY</h5>
            
            <?php if (count($cultivationRecords) > 0): ?>
                <div id="cultivationHistory">
                    <?php foreach ($cultivationRecords as $record): ?>
                        <?php
                        // Determine status based on harvesting date
                        $status = 'Active';
                        $statusClass = 'status-active';
                        if ($record['harvesting'] === 'Yes' && !empty($record['harvesting_date'])) {
                            $status = 'Completed';
                            $statusClass = 'status-completed';
                        }
                        ?>
                        
                        <div class="record-card">
                            <div class="record-header">
                                <div>
                                    <h5 class="mb-0">
                                        <?php echo !empty($record['crop_name']) ? htmlspecialchars($record['crop_name']) : 'Cultivation Record'; ?>
                                        <?php if (!empty($record['crop_variety'])): ?>
                                            <small class="text-muted">(<?php echo htmlspecialchars($record['crop_variety']); ?>)</small>
                                        <?php endif; ?>
                                    </h5>
                                    <div class="record-date">
                                        <i class="fas fa-calendar me-1"></i> 
                                        Created: <?php echo date('M j, Y', strtotime($record['created_at'])); ?>
                                        <?php if (!empty($record['sowing_date'])): ?>
                                            | Sowing: <?php echo date('M j, Y', strtotime($record['sowing_date'])); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($record['harvesting_date'])): ?>
                                            | Harvest: <?php echo date('M j, Y', strtotime($record['harvesting_date'])); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span>
                            </div>
                            
                            <div class="info-grid">
                                <?php if (!empty($record['land_area'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Land Area</span>
                                        <span class="info-value"><?php echo htmlspecialchars($record['land_area']); ?> <?php echo htmlspecialchars($record['area_unit']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($record['soil_testing'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Soil Testing</span>
                                        <span class="info-value"><?php echo htmlspecialchars($record['soil_testing']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($record['land_preparation'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Land Preparation</span>
                                        <span class="info-value"><?php echo htmlspecialchars($record['land_preparation']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($record['irrigation'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Irrigation</span>
                                        <span class="info-value"><?php echo htmlspecialchars($record['irrigation']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($record['planting_material'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Planting Material</span>
                                        <span class="info-value"><?php echo htmlspecialchars($record['planting_material']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($record['total_quantity'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Total Quantity</span>
                                        <span class="info-value"><?php echo htmlspecialchars($record['total_quantity']); ?> kg</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($record['yield'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">Yield</span>
                                        <span class="info-value"><?php echo htmlspecialchars($record['yield']); ?> kg/hectare</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Timeline of activities -->
                            <h6 class="mt-4 mb-3">Activity Timeline</h6>
                            <div class="timeline">
                                <?php if (!empty($record['soil_sample_collected']) && $record['soil_sample_collected'] === 'Yes'): ?>
                                    <div class="timeline-item">
                                        <strong>Soil Sample Collected</strong>
                                        <?php if (!empty($record['collection_date'])): ?>
                                            <div class="text-muted small"><?php echo date('M j, Y', strtotime($record['collection_date'])); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($record['collected_by'])): ?>
                                            <div class="small">By: <?php echo htmlspecialchars($record['collected_by']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($record['land_preparation']) && $record['land_preparation'] === 'Yes'): ?>
                                    <div class="timeline-item">
                                        <strong>Land Preparation</strong>
                                        <?php if (!empty($record['preparation_date'])): ?>
                                            <div class="text-muted small"><?php echo date('M j, Y', strtotime($record['preparation_date'])); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($record['sowing']) && $record['sowing'] === 'Yes'): ?>
                                    <div class="timeline-item">
                                        <strong>Sowing/Transplanting</strong>
                                        <?php if (!empty($record['sowing_date'])): ?>
                                            <div class="text-muted small"><?php echo date('M j, Y', strtotime($record['sowing_date'])); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($record['crop_name'])): ?>
                                            <div class="small">Crop: <?php echo htmlspecialchars($record['crop_name']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($record['monitoring_date_1'])): ?>
                                    <div class="timeline-item">
                                        <strong>Monitoring Visit 1</strong>
                                        <div class="text-muted small"><?php echo date('M j, Y', strtotime($record['monitoring_date_1'])); ?></div>
                                        <?php if (!empty($record['monitoring_officer_1'])): ?>
                                            <div class="small">By: <?php echo htmlspecialchars($record['monitoring_officer_1']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($record['monitoring_date_2'])): ?>
                                    <div class="timeline-item">
                                        <strong>Monitoring Visit 2</strong>
                                        <div class="text-muted small"><?php echo date('M j, Y', strtotime($record['monitoring_date_2'])); ?></div>
                                        <?php if (!empty($record['monitoring_officer_2'])): ?>
                                            <div class="small">By: <?php echo htmlspecialchars($record['monitoring_officer_2']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($record['harvesting']) && $record['harvesting'] === 'Yes'): ?>
                                    <div class="timeline-item">
                                        <strong>Harvesting</strong>
                                        <?php if (!empty($record['harvesting_date'])): ?>
                                            <div class="text-muted small"><?php echo date('M j, Y', strtotime($record['harvesting_date'])); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($record['total_quantity'])): ?>
                                            <div class="small">Quantity: <?php echo htmlspecialchars($record['total_quantity']); ?> kg</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Additional details toggle -->
                            <div class="mt-3">
                                <a class="collapse-btn" data-bs-toggle="collapse" href="#details-<?php echo $record['id']; ?>" role="button">
                                    <i class="fas fa-chevron-down me-1"></i> Show more details
                                </a>
                                
                                <div class="collapse mt-2" id="details-<?php echo $record['id']; ?>">
                                    <div class="card card-body">
                                        <div class="row">
                                            <?php if (!empty($record['pest_observation'])): ?>
                                                <div class="col-md-6">
                                                    <h6>Pest/Nutrient Observations</h6>
                                                    <p><?php echo htmlspecialchars($record['pest_observation']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($record['soil_key_values'])): ?>
                                                <div class="col-md-6">
                                                    <h6>Soil Test Results</h6>
                                                    <p><?php echo htmlspecialchars($record['soil_key_values']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($record['training_topic'])): ?>
                                            <h6>Training Provided</h6>
                                            <p>
                                                <strong>Topic:</strong> <?php echo htmlspecialchars($record['training_topic']); ?>
                                                <?php if (!empty($record['training_mode'])): ?>
                                                    | <strong>Mode:</strong> <?php echo htmlspecialchars($record['training_mode']); ?>
                                                <?php endif; ?>
                                                <?php if (!empty($record['training_conducted_by'])): ?>
                                                    | <strong>By:</strong> <?php echo htmlspecialchars($record['training_conducted_by']); ?>
                                                <?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h4>No cultivation records found</h4>
                    <p>This farmer doesn't have any cultivation records yet.</p>
                    <a href="cultivation_form.php?farmer_id=<?php echo $farmer['id']; ?>" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i> Add First Record
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>