<?php
// user/dashboard.php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Check if user is logged in
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    $auth->redirect('../login.php');
}

// Get user profile information
$userProfile = $auth->getUserProfile($_SESSION['user_id']);

// Get statistics for dashboard
$database = new Database();
$conn = $database->getConnection();

// Initialize variables
$totalCultivations = $totalCrops = $totalArea = $activeCultivations = 0;
$recentCultivations = [];

try {
    // Total cultivations
    $query = "SELECT COUNT(*) as total_cultivations FROM cultivation WHERE farmer_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $totalCultivations = $stmt->fetch(PDO::FETCH_ASSOC)['total_cultivations'];

    // Total different crops
    $query = "SELECT COUNT(DISTINCT crop_type) as total_crops FROM cultivation WHERE farmer_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $totalCrops = $stmt->fetch(PDO::FETCH_ASSOC)['total_crops'];

    // Total cultivation area
    $query = "SELECT COALESCE(SUM(cultivation_area), 0) as total_area FROM cultivation WHERE farmer_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $totalArea = $stmt->fetch(PDO::FETCH_ASSOC)['total_area'];

    // Active cultivations (planted but not harvested yet)
    $query = "SELECT COUNT(*) as active_cultivations FROM cultivation 
              WHERE farmer_id = :user_id 
              AND planting_date IS NOT NULL 
              AND (expected_harvest_date IS NULL OR expected_harvest_date > CURDATE())";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $activeCultivations = $stmt->fetch(PDO::FETCH_ASSOC)['active_cultivations'];

    // Recent cultivations
    $query = "SELECT crop_type, cultivation_area, planting_date, expected_harvest_date 
              FROM cultivation 
              WHERE farmer_id = :user_id 
              ORDER BY created_at DESC 
              LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $recentCultivations = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error in user dashboard: " . $e->getMessage());
}

// If no cultivations exist yet, show welcome message
$isNewFarmer = ($totalCultivations == 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - PGP Farmer Traceability</title>
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
        
        /* Dashboard Layout */
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
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 15px;
            color: var(--dark-color);
        }
        
        .logout-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: var(--secondary-color);
        }
        
        /* Dashboard Content */
        .dashboard-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 20px;
        }
        
        .card-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .card-header h3 {
            color: var(--primary-color);
            margin-left: 10px;
        }
        
        .card-header i {
            color: var(--primary-color);
            font-size: 20px;
        }
        
        .welcome-card {
            grid-column: 1 / -1;
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .welcome-card h2 {
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .welcome-card p {
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .welcome-card .btn {
            background-color: white;
            color: var(--primary-color);
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .welcome-card .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stats-card {
            text-align: center;
            transition: transform 0.3s;
            cursor: pointer;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary-color);
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 20px;
            text-decoration: none;
            color: var(--dark-color);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .action-btn i {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .action-btn span {
            font-weight: 600;
        }
        
        /* Recent Activity */
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary-color);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .activity-time {
            font-size: 12px;
            color: #888;
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
        }
        
        tr:hover {
            background-color: #f9f9f9;
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
            
            .dashboard-content {
                grid-template-columns: 1fr;
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
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="https://pgpindia.co/wp-content/uploads/2021/10/WhatsApp_Image_2025-05-29_at_14.03.45_96bf6913-removebg-preview-e1749970605607.png" alt="PGP India Logo">
                <h3>PGP Farmer</h3>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="farmer_registration.php"><i class="fas fa-user-plus"></i> Farmer Registration</a></li>
                <li><a href="cultivation_form.php"><i class="fas fa-seedling"></i> Cultivation Form</a></li>
                <li><a href="vendor_registration.php"><i class="fas fa-history"></i> Vendor Registration</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Farmer Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($userProfile['first_name'] . ' ' . $userProfile['last_name']); ?></span>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
            
            <!-- Dashboard Content -->
            <?php if ($isNewFarmer): ?>
                <!-- Welcome message for new farmers -->
                <div class="welcome-card">
                    <h2>Welcome to PGP Farmer Traceability!</h2>
                    <p>Get started by registering your farm and adding your first cultivation record</p>
                    <a href="farmer_registration.php" class="btn">Get Started</a>
                </div>
            <?php else: ?>
                <!-- Stats Cards -->
                <div class="dashboard-content">
                    <div class="card stats-card" onclick="location.href='cultivation_form.php'">
                        <div class="card-header">
                            <i class="fas fa-tractor"></i>
                            <h3>Total Cultivations</h3>
                        </div>
                        <div class="stat-number"><?php echo $totalCultivations; ?></div>
                        <div class="stat-label">Cultivation Records</div>
                    </div>
                    
                    <div class="card stats-card" onclick="location.href='cultivation_form.php'">
                        <div class="card-header">
                            <i class="fas fa-seedling"></i>
                            <h3>Crop Varieties</h3>
                        </div>
                        <div class="stat-number"><?php echo $totalCrops; ?></div>
                        <div class="stat-label">Different Crops</div>
                    </div>
                    
                    <div class="card stats-card" onclick="location.href='cultivation_form.php'">
                        <div class="card-header">
                            <i class="fas fa-map-marked-alt"></i>
                            <h3>Total Area</h3>
                        </div>
                        <div class="stat-number"><?php echo $totalArea; ?></div>
                        <div class="stat-label">Hectares Cultivated</div>
                    </div>
                    
                    <div class="card stats-card" onclick="location.href='cultivation_form.php'">
                        <div class="card-header">
                            <i class="fas fa-spa"></i>
                            <h3>Active Cultivations</h3>
                        </div>
                        <div class="stat-number"><?php echo $activeCultivations; ?></div>
                        <div class="stat-label">Currently Growing</div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bolt"></i>
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="quick-actions">
                        <a href="farmer_registration.php" class="action-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Register Farmer</span>
                        </a>
                        <a href="cultivation_form.php" class="action-btn">
                            <i class="fas fa-seedling"></i>
                            <span>Add Cultivation</span>
                        </a>
                        <a href="#" class="action-btn">
                            <i class="fas fa-history"></i>
                            <span>View History</span>
                        </a>
                        <a href="#" class="action-btn">
                            <i class="fas fa-download"></i>
                            <span>Download Reports</span>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Cultivations -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-history"></i>
                        <h3>Recent Cultivations</h3>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Crop Type</th>
                                    <th>Area (Hectares)</th>
                                    <th>Planting Date</th>
                                    <th>Expected Harvest</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recentCultivations) > 0): ?>
                                    <?php foreach ($recentCultivations as $cultivation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cultivation['crop_type']); ?></td>
                                            <td><?php echo htmlspecialchars($cultivation['cultivation_area']); ?></td>
                                            <td><?php echo $cultivation['planting_date'] ? date('M j, Y', strtotime($cultivation['planting_date'])) : 'Not planted'; ?></td>
                                            <td><?php echo $cultivation['expected_harvest_date'] ? date('M j, Y', strtotime($cultivation['expected_harvest_date'])) : 'Not set'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="empty-state">
                                            <i class="fas fa-seedling"></i>
                                            <p>No cultivation records found</p>
                                            <a href="cultivation_form.php" class="btn">Add Your First Cultivation</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- System Information -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i>
                    <h3>Account Information</h3>
                </div>
                <div class="table-container">
                    <table>
                        <tr>
                            <th>Farmer ID</th>
                            <td><?php echo htmlspecialchars($userProfile['username']); ?></td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td><?php echo htmlspecialchars($userProfile['first_name'] . ' ' . $userProfile['last_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($userProfile['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?php echo htmlspecialchars($userProfile['phone'] ?? 'Not provided'); ?></td>
                        </tr>
                        <tr>
                            <th>Member Since</th>
                            <td><?php echo date('M j, Y', strtotime($userProfile['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dashboard functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Update the current time
            function updateClock() {
                const now = new Date();
                const timeElement = document.getElementById('current-time');
                if (timeElement) {
                    timeElement.textContent = now.toLocaleTimeString();
                }
            }
            
            // Update clock every second
            setInterval(updateClock, 1000);
            updateClock();
            
            // Simulate loading data
            setTimeout(() => {
                console.log('Dashboard data loaded');
            }, 1000);
        });
    </script>
</body>
</html>