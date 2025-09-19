<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin using your Auth class
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $auth->redirect('../login.php');
}

$database = new Database();
$conn = $database->getConnection();

// Get counts for dashboard with error handling
$counts = [
    'farmers' => 0,
    'states' => 0,
    'districts' => 0,
    'users' => 0
];

$missing_tables = [];
$tables_to_check = ['farmers', 'states', 'districts', 'users'];

try {
    // Check which tables exist and get counts
    foreach ($tables_to_check as $table) {
        try {
            $query = "SELECT COUNT(*) as count FROM $table";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $counts[$table] = $result['count'] ?? 0;
        } catch (PDOException $e) {
            // Table doesn't exist
            $missing_tables[] = $table;
            $counts[$table] = 0;
        }
    }
    
} catch (PDOException $e) {
    $error = 'Database connection error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PGP Farmer Traceability</title>
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
        
        /* Dashboard Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 25px;
            text-align: center;
        }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 16px;
        }
        
        /* Quick Actions */
        .quick-actions {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 25px;
        }
        
        .quick-actions h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 22px;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: var(--light-color);
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            padding: 20px;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .action-btn i {
            font-size: 30px;
            margin-bottom: 10px;
        }
        
        .action-btn span {
            font-weight: 600;
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
            
            .action-buttons {
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="manage_states.php"><i class="fas fa-map-marked"></i> Manage States</a></li>
                <li><a href="manage_districts.php"><i class="fas fa-map-marker-alt"></i> Manage Districts</a></li>
                <li><a href="view_farmers.php"><i class="fas fa-user-tag"></i> View Farmers</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Admin Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
            
            <!-- Dashboard Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-tag"></i>
                    </div>
                    <div class="stat-number"><?php echo $counts['farmers']; ?></div>
                    <div class="stat-label">Total Farmers</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-map-marked"></i>
                    </div>
                    <div class="stat-number"><?php echo $counts['states']; ?></div>
                    <div class="stat-label">States</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo $counts['districts']; ?></div>
                    <div class="stat-label">Districts</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo $counts['users']; ?></div>
                    <div class="stat-label">System Users</div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="manage_users.php" class="action-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>Add User</span>
                    </a>
                    
                    <a href="manage_states.php" class="action-btn">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add State</span>
                    </a>
                    
                    <a href="manage_districts.php" class="action-btn">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add District</span>
                    </a>
                    
                    <a href="view_farmers.php" class="action-btn">
                        <i class="fas fa-list"></i>
                        <span>View Farmers</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>