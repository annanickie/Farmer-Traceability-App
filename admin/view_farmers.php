<?php
// admin/view_farmers.php
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

// Initialize variables
$farmers = [];
$error = '';
$success = '';
$search = '';
$district_filter = '';
$state_filter = '';
$category_filter = '';

// Handle farmer deletion
if (isset($_POST['delete_farmer'])) {
    try {
        $farmer_id = $_POST['farmer_id'];
        
        // Begin transaction
        $conn->beginTransaction();
        
        // First delete related records
        $query = "DELETE FROM cultivation_records WHERE farmer_id = :farmer_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':farmer_id', $farmer_id);
        $stmt->execute();
        
        // Then delete the farmer
        $query = "DELETE FROM farmers WHERE id = :farmer_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':farmer_id', $farmer_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $success = "Farmer and all related records deleted successfully.";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Error deleting farmer: " . $e->getMessage();
    }
}

try {
    // Build query with filters
    $query = "SELECT * FROM farmers WHERE 1=1";
    $params = [];
    
    // Search filter
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $_GET['search'];
        $search_term = "%$search%";
        $query .= " AND (farmer_name LIKE :search OR farmer_code LIKE :search OR village LIKE :search OR mobile LIKE :search)";
        $params[':search'] = $search_term;
    }
    
    // District filter
    if (isset($_GET['district']) && !empty($_GET['district'])) {
        $district_filter = $_GET['district'];
        $query .= " AND district = :district";
        $params[':district'] = $district_filter;
    }
    
    // State filter
    if (isset($_GET['state']) && !empty($_GET['state'])) {
        $state_filter = $_GET['state'];
        $query .= " AND state = :state";
        $params[':state'] = $state_filter;
    }
    
    // Category filter
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $category_filter = $_GET['category'];
        $query .= " AND farmer_category = :category";
        $params[':category'] = $category_filter;
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    $farmers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique districts and states for filters
    $districtQuery = "SELECT DISTINCT district FROM farmers WHERE district IS NOT NULL ORDER BY district";
    $stateQuery = "SELECT DISTINCT state FROM farmers WHERE state IS NOT NULL ORDER BY state";
    
    $districtStmt = $conn->query($districtQuery);
    $stateStmt = $conn->query($stateQuery);
    
    $districts = $districtStmt->fetchAll(PDO::FETCH_COLUMN);
    $states = $stateStmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Farmers - Admin Panel</title>
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
        
        /* Header Styles */
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
            margin: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 15px;
            color: #666;
        }
        
        .btn-success {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-success:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        /* Filter Section */
        .filter-section {
            background-color: white;
            box-shadow: var(--shadow);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .filter-section h5 {
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        /* Table Styles */
        .table-container {
            background-color: white;
            box-shadow: var(--shadow);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(10, 110, 56, 0.05);
        }
        
        .badge-success {
            background-color: var(--primary-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 10px 0;
            }
            
            .sidebar-nav {
                display: flex;
                overflow-x: auto;
                padding: 10px 0;
            }
            
            .sidebar-nav li {
                margin-bottom: 0;
                margin-right: 5px;
            }
            
            .sidebar-nav a {
                padding: 10px 15px;
                border-radius: 4px;
                white-space: nowrap;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .user-info {
                margin-top: 15px;
                flex-direction: column;
            }
            
            .user-info span {
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="https://pgpindia.co/wp-content/uploads/2021/10/WhatsApp_Image_2025-05-29_at_14.03.45_96bf6913-removebg-preview-e1749970605607.png" alt="PGP India Logo">
                <h3>PGP Admin</h3>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="manage_states.php"><i class="fas fa-map-marked"></i> Manage States</a></li>
                <li><a href="manage_districts.php"><i class="fas fa-map-marker-alt"></i> Manage Districts</a></li>
                <li><a href="view_farmers.php" class="active"><i class="fas fa-user-tag"></i> View Farmers</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1><i class="fas fa-user-tag me-2"></i> Farmers Management</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    <a href="../logout.php" class="btn btn-sm btn-danger">Logout</a>
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
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($farmers); ?></div>
                    <div class="stat-label">Total Farmers</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo count(array_filter($farmers, function($farmer) {
                            return $farmer['farmer_category'] === 'Small Farmer';
                        })); ?>
                    </div>
                    <div class="stat-label">Small Farmers</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo count(array_unique(array_column($farmers, 'district'))); ?>
                    </div>
                    <div class="stat-label">Districts Covered</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo count(array_unique(array_column($farmers, 'state'))); ?>
                    </div>
                    <div class="stat-label">States Covered</div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <h5><i class="fas fa-filter me-2"></i> Filter Farmers</h5>
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" placeholder="Search by name, code, village..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="district">
                                <option value="">All Districts</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?php echo htmlspecialchars($district); ?>" <?php echo $district_filter === $district ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($district); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="state">
                                <option value="">All States</option>
                                <?php foreach ($states as $state): ?>
                                    <option value="<?php echo htmlspecialchars($state); ?>" <?php echo $state_filter === $state ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($state); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <option value="Big Farmer" <?php echo $category_filter === 'Big Farmer' ? 'selected' : ''; ?>>Big Farmer</option>
                                <option value="Marginal Farmer" <?php echo $category_filter === 'Marginal Farmer' ? 'selected' : ''; ?>>Marginal Farmer</option>
                                <option value="Small Farmer" <?php echo $category_filter === 'Small Farmer' ? 'selected' : ''; ?>>Small Farmer</option>
                                <option value="Others" <?php echo $category_filter === 'Others' ? 'selected' : ''; ?>>Others</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-success me-2"><i class="fas fa-search me-1"></i> Apply Filters</button>
                            <a href="view_farmers.php" class="btn btn-outline-secondary"><i class="fas fa-sync me-1"></i> Reset</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Farmers Table -->
            <div class="table-container">
                <div class="table-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> Farmers List</h5>
                    <a href="add_farmer.php" class="btn btn-sm btn-light">
                        <i class="fas fa-plus me-1"></i> Add New Farmer
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Farmer Code</th>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th>Category</th>
                                <th>Land Area</th>
                                <th>Registered On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($farmers) > 0): ?>
                                <?php foreach ($farmers as $farmer): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-success"><?php echo htmlspecialchars($farmer['farmer_code']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($farmer['farmer_name']); ?></td>
                                        <td>
                                            <?php if (!empty($farmer['mobile'])): ?>
                                                <div><?php echo htmlspecialchars($farmer['mobile']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($farmer['email'])): ?>
                                                <div class="small text-muted"><?php echo htmlspecialchars($farmer['email']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($farmer['village']); ?></div>
                                            <div class="small text-muted">
                                                <?php echo htmlspecialchars($farmer['district']); ?>, <?php echo htmlspecialchars($farmer['state']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($farmer['farmer_category']); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($farmer['land_area'])): ?>
                                                <?php echo htmlspecialchars($farmer['land_area']); ?> <?php echo htmlspecialchars($farmer['area_unit']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($farmer['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view_farmer_details.php?id=<?php echo $farmer['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_farmer.php?id=<?php echo $farmer['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="farmer_id" value="<?php echo $farmer['id']; ?>">
                                                    <button type="submit" name="delete_farmer" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this farmer and all their records?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-users fa-2x text-muted mb-3"></i>
                                        <h5>No farmers found</h5>
                                        <p class="text-muted">No farmers match your search criteria.</p>
                                        <a href="add_farmer.php" class="btn btn-success">
                                            <i class="fas fa-plus me-1"></i> Add First Farmer
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>