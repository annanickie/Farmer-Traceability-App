<?php
// admin/manage_states.php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Check if user is logged in and is admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $auth->redirect('../login.php');
}

$database = new Database();
$conn = $database->getConnection();
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_state'])) {
        $stateCode = strtoupper(trim($_POST['state_code']));
        $stateName = trim($_POST['state_name']);
        
        if (empty($stateCode) || empty($stateName)) {
            $error = 'Please fill in all fields.';
        } else {
            try {
                $query = "INSERT INTO states (state_code, state_name, created_by) 
                         VALUES (:state_code, :state_name, :created_by)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':state_code', $stateCode);
                $stmt->bindParam(':state_name', $stateName);
                $stmt->bindParam(':created_by', $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success = 'State added successfully!';
                }
            } catch (PDOException $e) {
                $error = 'Error adding state: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['toggle_state'])) {
        $stateId = $_POST['state_id'];
        $isActive = $_POST['is_active'] ? 0 : 1;
        
        $query = "UPDATE states SET is_active = :is_active WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':is_active', $isActive);
        $stmt->bindParam(':id', $stateId);
        $stmt->execute();
    }
}

// Get all states
$query = "SELECT * FROM states ORDER BY state_name";
$stmt = $conn->prepare($query);
$stmt->execute();
$states = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage States - PGP Farmer Traceability</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reuse the same styles from dashboard.php */
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
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
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
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 20px;
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
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--secondary-color);
        }
        
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
        }
        
        .status-toggle {
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
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
                <li><a href="manage_states.php" class="active"><i class="fas fa-map-marked"></i> Manage States</a></li>
                <li><a href="manage_districts.php"><i class="fas fa-map-marker-alt"></i> Manage Districts</a></li>
                <li><a href="view_farmers.php"><i class="fas fa-user-tag"></i> View Farmers</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>Manage States</h1>
                <div class="user-info">
                    <span>Welcome, Admin</span>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <!-- Add State Form -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Add New State</h3>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label for="state_code">State Code (2 letters)</label>
                        <input type="text" id="state_code" name="state_code" maxlength="2" required 
                               placeholder="e.g., MH for Maharashtra">
                    </div>
                    <div class="form-group">
                        <label for="state_name">State Name</label>
                        <input type="text" id="state_name" name="state_name" required 
                               placeholder="e.g., Maharashtra">
                    </div>
                    <button type="submit" name="add_state" class="btn">Add State</button>
                </form>
            </div>
            
            <!-- States List -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i>
                    <h3>All States</h3>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>State Code</th>
                                <th>State Name</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($states) > 0): ?>
                                <?php foreach ($states as $state): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($state['state_code']); ?></td>
                                        <td><?php echo htmlspecialchars($state['state_name']); ?></td>
                                        <td>
                                            <span class="status-toggle <?php echo $state['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $state['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="state_id" value="<?php echo $state['id']; ?>">
                                                <input type="hidden" name="is_active" value="<?php echo $state['is_active']; ?>">
                                                <button type="submit" name="toggle_state" class="btn">
                                                    <?php echo $state['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No states found. Please add states.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>