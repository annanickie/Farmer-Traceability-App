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

// Get counts for dashboard with detailed data
$stats = [
    'farmers' => 0,
    'states' => 0,
    'districts' => 0,
    'users' => 0
];

$detailedData = [
    'farmers' => [],
    'states' => [],
    'districts' => [],
    'users' => []
];

try {
    // Get farmers count with details
    $query = "SELECT COUNT(*) as count FROM farmers";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['farmers'] = $result['count'] ?? 0;
    
    // Get farmers by category
    $query = "SELECT farmer_category, COUNT(*) as count FROM farmers GROUP BY farmer_category";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $detailedData['farmers']['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent farmers (last 7 days)
    $query = "SELECT COUNT(*) as count FROM farmers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $detailedData['farmers']['recent'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get states count with details
    $query = "SELECT COUNT(*) as count FROM states";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['states'] = $result['count'] ?? 0;
    
    // Get active states
    $query = "SELECT state_name, state_code FROM states WHERE is_active = 1 ORDER BY state_name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $detailedData['states']['list'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get districts count with details
    $query = "SELECT COUNT(*) as count FROM districts";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['districts'] = $result['count'] ?? 0;
    
    // Get districts by state
    $query = "SELECT s.state_name, COUNT(d.id) as district_count 
              FROM districts d 
              JOIN states s ON d.state_id = s.id 
              GROUP BY s.state_name 
              ORDER BY district_count DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $detailedData['districts']['by_state'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get users count with details
    $query = "SELECT COUNT(*) as count FROM users";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['users'] = $result['count'] ?? 0;
    
    // Get users by role
    $query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $detailedData['users']['by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'Database connection error: ' . $e->getMessage();
    error_log("Dashboard count error: " . $e->getMessage());
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
        
        /* Dashboard Stats - ORIGINAL STYLING */
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
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
        }
        
        .stat-card.active {
            border-color: var(--secondary-color);
            background-color: var(--light-color);
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
        
        .stat-trend {
            margin-top: 10px;
            font-size: 12px;
            color: var(--secondary-color);
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
        
        /* Details Panel */
        .details-panel {
            display: none;
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }
        
        .details-panel.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .panel-header h3 {
            color: var(--primary-color);
            margin: 0;
        }
        
        .close-panel {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }
        
        .close-panel:hover {
            color: var(--dark-color);
        }
        
        .details-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .detail-section h4 {
            color: var(--secondary-color);
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .progress-bar {
            height: 6px;
            background-color: #e0e0e0;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 3px;
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
            
            .details-content {
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
                <div class="stat-card" onclick="showDetails('farmers')" id="farmers-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-tag"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['farmers']; ?></div>
                    <div class="stat-label">Total Farmers</div>
                    <?php if ($detailedData['farmers']['recent'] > 0): ?>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i>
                            <?php echo $detailedData['farmers']['recent']; ?> new this week
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="stat-card" onclick="showDetails('states')" id="states-card">
                    <div class="stat-icon">
                        <i class="fas fa-map-marked"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['states']; ?></div>
                    <div class="stat-label">States</div>
                </div>
                
                <div class="stat-card" onclick="showDetails('districts')" id="districts-card">
                    <div class="stat-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['districts']; ?></div>
                    <div class="stat-label">Districts</div>
                </div>
                
                <div class="stat-card" onclick="showDetails('users')" id="users-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats['users']; ?></div>
                    <div class="stat-label">System Users</div>
                </div>
            </div>

            <!-- Details Panel -->
            <div class="details-panel" id="details-panel">
                <div class="panel-header">
                    <h3 id="details-title"></h3>
                    <button class="close-panel" onclick="hideDetails()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="details-content" id="details-content">
                    <!-- Content will be loaded here -->
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

    <script>
        let currentDetail = null;
        
        function showDetails(type) {
            // Hide current details if any
            hideDetails();
            
            // Remove active class from all cards
            document.querySelectorAll('.stat-card').forEach(card => {
                card.classList.remove('active');
            });
            
            // Add active class to current card
            document.getElementById(type + '-card').classList.add('active');
            
            // Show details panel
            const panel = document.getElementById('details-panel');
            const title = document.getElementById('details-title');
            const content = document.getElementById('details-content');
            
            // Set title and content based on type
            switch(type) {
                case 'farmers':
                    title.innerHTML = '<i class="fas fa-user-tag me-2"></i> Farmers Details';
                    content.innerHTML = getFarmersDetails();
                    break;
                case 'states':
                    title.innerHTML = '<i class="fas fa-map-marked me-2"></i> States Details';
                    content.innerHTML = getStatesDetails();
                    break;
                case 'districts':
                    title.innerHTML = '<i class="fas fa-map-marker-alt me-2"></i> Districts Details';
                    content.innerHTML = getDistrictsDetails();
                    break;
                case 'users':
                    title.innerHTML = '<i class="fas fa-users me-2"></i> Users Details';
                    content.innerHTML = getUsersDetails();
                    break;
            }
            
            panel.classList.add('show');
            currentDetail = type;
            
            // Scroll to details panel
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        function hideDetails() {
            const panel = document.getElementById('details-panel');
            panel.classList.remove('show');
            
            // Remove active class from all cards
            document.querySelectorAll('.stat-card').forEach(card => {
                card.classList.remove('active');
            });
            
            currentDetail = null;
        }
        
        function getFarmersDetails() {
            return `
                <div class="detail-section">
                    <h4>Farmers by Category</h4>
                    <?php foreach ($detailedData['farmers']['by_category'] as $category): ?>
                        <div class="detail-item">
                            <span><?php echo htmlspecialchars($category['farmer_category']); ?></span>
                            <span class="fw-bold"><?php echo $category['count']; ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo ($category['count'] / $stats['farmers']) * 100; ?>%"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="detail-section">
                    <h4>Summary</h4>
                    <div class="detail-item">
                        <span>Total Farmers</span>
                        <span class="fw-bold"><?php echo $stats['farmers']; ?></span>
                    </div>
                    <div class="detail-item">
                        <span>New Farmers (Last 7 days)</span>
                        <span class="fw-bold text-success"><?php echo $detailedData['farmers']['recent']; ?></span>
                    </div>
                    <div class="detail-item">
                        <a href="view_farmers.php" class="logout-btn" style="display: inline-block; padding: 8px 15px;">
                            <i class="fas fa-eye me-1"></i> View All Farmers
                        </a>
                    </div>
                </div>
            `;
        }
        
        function getStatesDetails() {
            return `
                <div class="detail-section">
                    <h4>Active States</h4>
                    <?php if (!empty($detailedData['states']['list'])): ?>
                        <?php foreach ($detailedData['states']['list'] as $state): ?>
                            <div class="detail-item">
                                <span><?php echo htmlspecialchars($state['state_name']); ?></span>
                                <span class="badge" style="background: var(--primary-color); color: white; padding: 4px 8px; border-radius: 4px;">
                                    <?php echo htmlspecialchars($state['state_code']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="detail-item">
                            <span class="text-muted">No states found</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="detail-section">
                    <h4>Summary</h4>
                    <div class="detail-item">
                        <span>Total States</span>
                        <span class="fw-bold"><?php echo $stats['states']; ?></span>
                    </div>
                    <div class="detail-item">
                        <a href="manage_states.php" class="logout-btn" style="display: inline-block; padding: 8px 15px;">
                            <i class="fas fa-cog me-1"></i> Manage States
                        </a>
                    </div>
                </div>
            `;
        }
        
        function getDistrictsDetails() {
            return `
                <div class="detail-section">
                    <h4>Districts by State</h4>
                    <?php if (!empty($detailedData['districts']['by_state'])): ?>
                        <?php foreach ($detailedData['districts']['by_state'] as $district): ?>
                            <div class="detail-item">
                                <span><?php echo htmlspecialchars($district['state_name']); ?></span>
                                <span class="fw-bold"><?php echo $district['district_count']; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo ($district['district_count'] / $stats['districts']) * 100; ?>%"></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="detail-item">
                            <span class="text-muted">No districts found</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="detail-section">
                    <h4>Summary</h4>
                    <div class="detail-item">
                        <span>Total Districts</span>
                        <span class="fw-bold"><?php echo $stats['districts']; ?></span>
                    </div>
                    <div class="detail-item">
                        <a href="manage_districts.php" class="logout-btn" style="display: inline-block; padding: 8px 15px;">
                            <i class="fas fa-cog me-1"></i> Manage Districts
                        </a>
                    </div>
                </div>
            `;
        }
        
        function getUsersDetails() {
            return `
                <div class="detail-section">
                    <h4>Users by Role</h4>
                    <?php foreach ($detailedData['users']['by_role'] as $user): ?>
                        <div class="detail-item">
                            <span><?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
                            <span class="fw-bold"><?php echo $user['count']; ?></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo ($user['count'] / $stats['users']) * 100; ?>%"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="detail-section">
                    <h4>Summary</h4>
                    <div class="detail-item">
                        <span>Total Users</span>
                        <span class="fw-bold"><?php echo $stats['users']; ?></span>
                    </div>
                    <div class="detail-item">
                        <a href="manage_users.php" class="logout-btn" style="display: inline-block; padding: 8px 15px;">
                            <i class="fas fa-cog me-1"></i> Manage Users
                        </a>
                    </div>
                </div>
            `;
        }
        
        // Close details when clicking outside
        document.addEventListener('click', function(event) {
            const panel = document.getElementById('details-panel');
            const cards = document.querySelectorAll('.stat-card');
            
            if (currentDetail && !panel.contains(event.target)) {
                let isCardClick = false;
                cards.forEach(card => {
                    if (card.contains(event.target)) {
                        isCardClick = true;
                    }
                });
                
                if (!isCardClick) {
                    hideDetails();
                }
            }
        });
    </script>
</body>
</html>