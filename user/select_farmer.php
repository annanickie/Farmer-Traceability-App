<?php
// select_farmer.php
include '../includes/config.php';
include '../includes/database.php';

// Initialize variables
$farmers = [];
$error = '';

try {
    // Create database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Fetch all farmers from database
    $query = "SELECT id, farmer_code, farmer_name, village, district, state FROM farmers ORDER BY farmer_name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $farmers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Farmer - PGP Farmer Traceability</title>
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
        
        .farmer-card {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .farmer-card:hover {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }
        
        .farmer-code {
            background-color: #e8f5e9;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box .form-control {
            padding-left: 40px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #6c757d;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
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
                <i class="fas fa-users me-2"></i> Select Farmer
            </h2>
            <a href="../index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h5 class="section-title"><i class="fas fa-search me-2"></i> Search Farmers</h5>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search by name, code, or village...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="districtFilter">
                        <option value="">All Districts</option>
                        <?php
                        // Get unique districts
                        $districts = [];
                        foreach ($farmers as $farmer) {
                            if (!in_array($farmer['district'], $districts)) {
                                $districts[] = $farmer['district'];
                                echo '<option value="' . htmlspecialchars($farmer['district']) . '">' . htmlspecialchars($farmer['district']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="stateFilter">
                        <option value="">All States</option>
                        <?php
                        // Get unique states
                        $states = [];
                        foreach ($farmers as $farmer) {
                            if (!in_array($farmer['state'], $states)) {
                                $states[] = $farmer['state'];
                                echo '<option value="' . htmlspecialchars($farmer['state']) . '">' . htmlspecialchars($farmer['state']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <h5 class="section-title"><i class="fas fa-user-circle me-2"></i> Farmers List</h5>
            
            <?php if (count($farmers) > 0): ?>
                <div id="farmersList">
                    <?php foreach ($farmers as $farmer): ?>
                        <div class="farmer-card" data-name="<?php echo htmlspecialchars(strtolower($farmer['farmer_name'])); ?>" data-code="<?php echo htmlspecialchars(strtolower($farmer['farmer_code'])); ?>" data-village="<?php echo htmlspecialchars(strtolower($farmer['village'])); ?>" data-district="<?php echo htmlspecialchars($farmer['district']); ?>" data-state="<?php echo htmlspecialchars($farmer['state']); ?>">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($farmer['farmer_name']); ?></h5>
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <span class="farmer-code"><?php echo htmlspecialchars($farmer['farmer_code']); ?></span>
                                        <span class="text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($farmer['village']); ?>, <?php echo htmlspecialchars($farmer['district']); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <span class="badge bg-light text-dark"><i class="fas fa-globe me-1"></i> <?php echo htmlspecialchars($farmer['state']); ?></span>
                                </div>
                                <div class="col-md-2 text-end">
                                    <a href="cultivation_form.php?farmer_id=<?php echo $farmer['id']; ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-seedling me-1"></i> Select
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert-info">
                    <i class="fas fa-info-circle me-2"></i> No farmers found in the database. Please <a href="farmer_registration.php">register a farmer</a> first.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Filter farmers based on search input and filters
        function filterFarmers() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const districtFilter = document.getElementById('districtFilter').value;
            const stateFilter = document.getElementById('stateFilter').value;
            
            const farmerCards = document.querySelectorAll('.farmer-card');
            
            farmerCards.forEach(card => {
                const name = card.getAttribute('data-name');
                const code = card.getAttribute('data-code');
                const village = card.getAttribute('data-village');
                const district = card.getAttribute('data-district');
                const state = card.getAttribute('data-state');
                
                const matchesSearch = name.includes(searchText) || code.includes(searchText) || village.includes(searchText);
                const matchesDistrict = districtFilter === '' || district === districtFilter;
                const matchesState = stateFilter === '' || state === stateFilter;
                
                if (matchesSearch && matchesDistrict && matchesState) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Add event listeners for filtering
        document.getElementById('searchInput').addEventListener('input', filterFarmers);
        document.getElementById('districtFilter').addEventListener('change', filterFarmers);
        document.getElementById('stateFilter').addEventListener('change', filterFarmers);
        
        // Initial filter
        filterFarmers();
    </script>
</body>
</html>