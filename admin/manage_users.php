<?php
// admin/manage_users.php
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
$users = [];
$error = '';
$success = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $full_name = trim($_POST['full_name']);
        $role = $_POST['role'];
        
        try {
            // Check if username or email already exists
            $checkQuery = "SELECT id FROM users WHERE username = :username OR email = :email";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bindParam(':username', $username);
            $checkStmt->bindParam(':email', $email);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                $error = "Username or email already exists!";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Check if full_name column exists
                $columnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'full_name'");
                $hasFullName = $columnCheck->rowCount() > 0;
                
                if ($hasFullName) {
                    $insertQuery = "INSERT INTO users (username, email, password, full_name, role) 
                                   VALUES (:username, :email, :password, :full_name, :role)";
                    $insertStmt = $conn->prepare($insertQuery);
                    $insertStmt->bindParam(':full_name', $full_name);
                } else {
                    $insertQuery = "INSERT INTO users (username, email, password, role) 
                                   VALUES (:username, :email, :password, :role)";
                    $insertStmt = $conn->prepare($insertQuery);
                }
                
                $insertStmt->bindParam(':username', $username);
                $insertStmt->bindParam(':email', $email);
                $insertStmt->bindParam(':password', $hashedPassword);
                $insertStmt->bindParam(':role', $role);
                $insertStmt->execute();
                
                $success = "User added successfully!";
            }
        } catch (Exception $e) {
            $error = "Error adding user: " . $e->getMessage();
        }
    }
    
    // Update user status
    if (isset($_POST['update_status'])) {
        $user_id = $_POST['user_id'];
        $status = $_POST['status'];
        
        try {
            // Check if status column exists
            $columnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
            $hasStatus = $columnCheck->rowCount() > 0;
            
            if ($hasStatus) {
                $query = "UPDATE users SET status = :status WHERE id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                $success = "User status updated successfully!";
            } else {
                $error = "Status column does not exist in users table.";
            }
        } catch (Exception $e) {
            $error = "Error updating user status: " . $e->getMessage();
        }
    }
    
    // Delete user
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        
        try {
            // Prevent deletion of own account
            if ($user_id == $_SESSION['user_id']) {
                $error = "You cannot delete your own account!";
            } else {
                $query = "DELETE FROM users WHERE id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                $success = "User deleted successfully!";
            }
        } catch (Exception $e) {
            $error = "Error deleting user: " . $e->getMessage();
        }
    }
    
    // Reset password
    if (isset($_POST['reset_password'])) {
        $user_id = $_POST['user_id'];
        $new_password = $_POST['new_password'];
        
        try {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            
            $query = "UPDATE users SET password = :password WHERE id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $success = "Password reset successfully!";
        } catch (Exception $e) {
            $error = "Error resetting password: " . $e->getMessage();
        }
    }
}

// Get all users
try {
    $query = "SELECT * FROM users ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching users: " . $e->getMessage();
}

// Check if columns exist
$hasFullName = false;
$hasStatus = false;
$hasLastLogin = false;

try {
    $result = $conn->query("SHOW COLUMNS FROM users");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    $hasFullName = in_array('full_name', $columns);
    $hasStatus = in_array('status', $columns);
    $hasLastLogin = in_array('last_login', $columns);
} catch (Exception $e) {
    // If we can't check columns, we'll handle it in the display
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
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
        
        /* Card Styles */
        .card {
            background-color: white;
            box-shadow: var(--shadow);
            border-radius: 8px;
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
            font-weight: 600;
            border-radius: 8px 8px 0 0 !important;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Form Styles */
        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
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
        
        .badge-danger {
            background-color: #dc3545;
        }
        
        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .badge-info {
            background-color: #17a2b8;
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
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        /* Modal Styles */
        .modal-content {
            border-radius: 8px;
            border: none;
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px 8px 0 0;
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
            
            .action-buttons {
                flex-direction: column;
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
                <li><a href="manage_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a></li>
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
                <h1><i class="fas fa-users me-2"></i> User Management</h1>
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
            
            <?php if (!$hasFullName || !$hasStatus): ?>
                <div class="alert alert-warning">
                    <strong>Notice:</strong> Some database columns are missing. 
                    <a href="setup_database.php" class="alert-link">Run database setup</a> to add missing columns.
                </div>
            <?php endif; ?>
            
            <!-- Add User Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i> Add New User</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" <?php echo $hasFullName ? 'required' : 'disabled'; ?>>
                                <?php if (!$hasFullName): ?>
                                    <small class="text-muted">Full Name column not available in database</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" name="add_user" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i> Add User
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="table-container">
                <div class="table-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> System Users</h5>
                    <span class="badge bg-light text-dark"><?php echo count($users); ?> Users</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <?php if ($hasFullName): ?>
                                    <th>Name</th>
                                <?php endif; ?>
                                <th>Email</th>
                                <th>Role</th>
                                <?php if ($hasStatus): ?>
                                    <th>Status</th>
                                <?php endif; ?>
                                <?php if ($hasLastLogin): ?>
                                    <th>Last Login</th>
                                <?php endif; ?>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-warning">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($hasFullName): ?>
                                            <td><?php echo !empty($user['full_name']) ? htmlspecialchars($user['full_name']) : '<span class="text-muted">N/A</span>'; ?></td>
                                        <?php endif; ?>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-success' : 'bg-info'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                            </span>
                                        </td>
                                        <?php if ($hasStatus): ?>
                                            <td>
                                                <span class="badge <?php echo ($user['status'] ?? 'active') === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($user['status'] ?? 'active')); ?>
                                                </span>
                                            </td>
                                        <?php endif; ?>
                                        <?php if ($hasLastLogin): ?>
                                            <td>
                                                <?php if (!empty($user['last_login'])): ?>
                                                    <?php echo date('M j, Y g:i A', strtotime($user['last_login'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Never</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <!-- Status Toggle -->
                                                <?php if ($hasStatus): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="status" value="<?php echo ($user['status'] ?? 'active') === 'active' ? 'inactive' : 'active'; ?>">
                                                        <button type="submit" name="update_status" class="btn btn-sm <?php echo ($user['status'] ?? 'active') === 'active' ? 'btn-warning' : 'btn-success'; ?>" title="<?php echo ($user['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="fas <?php echo ($user['status'] ?? 'active') === 'active' ? 'fa-times' : 'fa-check'; ?>"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <!-- Password Reset -->
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#passwordModal<?php echo $user['id']; ?>" title="Reset Password">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                
                                                <!-- Delete User -->
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="delete_user" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Password Reset Modal -->
                                            <div class="modal fade" id="passwordModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reset Password for <?php echo htmlspecialchars($user['username']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="new_password<?php echo $user['id']; ?>" class="form-label">New Password</label>
                                                                    <input type="password" class="form-control" id="new_password<?php echo $user['id']; ?>" name="new_password" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="reset_password" class="btn btn-success">Reset Password</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo ($hasFullName ? 7 : 6) + ($hasStatus ? 1 : 0) + ($hasLastLogin ? 1 : 0); ?>" class="text-center py-4">
                                        <i class="fas fa-users fa-2x text-muted mb-3"></i>
                                        <h5>No users found</h5>
                                        <p class="text-muted">No users have been created yet.</p>
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