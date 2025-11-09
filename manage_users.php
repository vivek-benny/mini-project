<?php
// manage_users.php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
$host = "localhost";
$user = "root";
$pass = "";
$db   = "login";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$messageType = '';

// Handle user operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_user':
                $user_id = $_POST['user_id'];
                $name = $_POST['name'];
                $email = $_POST['email'];
                $password = $_POST['password'];
                $phonenumber = $_POST['phonenumber'];
                
                // Check if email exists for other users
                $check_email = "SELECT * FROM register WHERE email = ? AND user_id != ?";
                $check_stmt = $conn->prepare($check_email);
                $check_stmt->bind_param("si", $email, $user_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $message = "Email already exists for another user!";
                    $messageType = "error";
                } else {
                    if (!empty($password)) {
                        $sql = "UPDATE register SET name = ?, email = ?, password = ?, phonenumber = ? WHERE user_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssssi", $name, $email, $password, $phonenumber, $user_id);
                    } else {
                        $sql = "UPDATE register SET name = ?, email = ?, phonenumber = ? WHERE user_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sssi", $name, $email, $phonenumber, $user_id);
                    }
                    
                    if ($stmt->execute()) {
                        $message = "User updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error updating user: " . $conn->error;
                        $messageType = "error";
                    }
                }
                break;
                
            case 'delete_user':
                $user_id = $_POST['user_id'];
                $sql = "DELETE FROM register WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $message = "User deleted successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error deleting user: " . $conn->error;
                    $messageType = "error";
                }
                break;
        }
    }
}

// Fetch all users
$users_query = "SELECT * FROM register ORDER BY created_at DESC";
$users_result = $conn->query($users_query);

// Get user statistics
$total_users_query = "SELECT COUNT(*) as total FROM register";
$total_users_result = $conn->query($total_users_query);
$total_users = $total_users_result->fetch_assoc()['total'];

// Get users with bookings
$users_with_bookings_query = "SELECT COUNT(DISTINCT user_id) as count FROM bookings";
$users_with_bookings_result = $conn->query($users_with_bookings_query);
$users_with_bookings = $users_with_bookings_result->fetch_assoc()['count'];

// Get users registered this month
$this_month_query = "SELECT COUNT(*) as count FROM register WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
$this_month_result = $conn->query($this_month_query);
$this_month_users = $this_month_result->fetch_assoc()['count'];

// Get users registered today
$today_query = "SELECT COUNT(*) as count FROM register WHERE DATE(created_at) = CURDATE()";
$today_result = $conn->query($today_query);
$today_users = $today_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('images/admin.jpg') center/cover no-repeat fixed;
            min-height: 100vh;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: -1;
        }
        
        /* Sidebar styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 20px;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar.collapsed {
            transform: translateX(-280px);
        }
        
        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 10px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .nav-link i {
            margin-right: 15px;
            font-size: 1.2rem;
            width: 20px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        /* Top Bar */
        .top-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .menu-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
            padding: 10px;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        
        .menu-toggle:hover {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .top-bar h1 {
            color: #2c3e50;
            font-size: 1.8rem;
        }
        
        /* Content Cards */
        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .content-header h2 {
            color: #2c3e50;
            font-size: 1.8rem;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
        }
        
        .stat-item.users {
            background: linear-gradient(135deg, #48c9b0, #1dd1a1);
        }
        
        .stat-item.bookings {
            background: linear-gradient(135deg, #feca57, #ff9f43);
        }
        
        .stat-item.month {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        }
        
        .stat-item.today {
            background: linear-gradient(135deg, #a8e6cf, #7fcdcd);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Button Styles */
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #48c9b0, #1dd1a1);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #feca57, #ff9f43);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            color: white;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 0.85rem;
        }
        
        /* Users Table */
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .users-table th,
        .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .users-table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
        }
        
        .users-table tr:hover {
            background: #f8f9fa;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* User Card */
        .user-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            border-left: 4px solid #667eea;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
        }
        
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .user-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .user-email {
            color: #667eea;
            font-size: 1rem;
        }
        
        .user-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .detail-item i {
            color: #667eea;
            width: 16px;
        }
        
        /* Booking Card */
        .booking-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .booking-id {
            font-weight: 700;
            color: #2c3e50;
        }
        
        .booking-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Alert Styles */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: white;
            margin: 2% auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .close:hover {
            color: #000;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-280px);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .content-header {
                flex-direction: column;
                gap: 20px;
                align-items: stretch;
            }
            
            .user-actions {
                justify-content: center;
            }
            
            .users-table {
                font-size: 0.9rem;
            }
            
            .users-table th,
            .users-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-cogs"></i> Admin Panel</h2>
            <p>Garage</p>
        </div>
        <nav>
            <ul class="nav-menu">
              
                <li class="nav-item">
                    <a href="adminbookingview.php" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span>Manage Bookings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_users.php" class="nav-link active">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_staff.php" class="nav-link">
                        <i class="fas fa-user-tie"></i>
                        <span>Manage Staff</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin.php" class="nav-link">
                        <i class="fas fa-wrench"></i>
                        <span>Manage Services</span>
                    </a>
                </li>
                 <li class="nav-item">
                    <a href="manage_mechanics.php" class="nav-link">
                        <i class="fas fa-user-cog"></i>
                        <span>Manage Mechanics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_leaves.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Manage Leave</span>
                    </a>
                </li>
                 <li class="nav-item">
                    <a href="manage_feedback.php" class="nav-link">
                        <i class="fas fa-star"></i>
                        <span>Manage Feedback</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settingsadmin.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <!-- Top Bar -->
        <div class="top-bar">
            <div style="display: flex; align-items: center; gap: 20px;">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Manage Users</h1>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="alert <?php echo ($messageType === 'success') ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="content-card">
            <div class="content-header">
                <h2><i class="fas fa-chart-line"></i> User Statistics</h2>
            </div>
            <div class="stats-grid">
                <div class="stat-item users">
                    <span class="stat-number"><?php echo $total_users; ?></span>
                    <span class="stat-label">Total Users</span>
                </div>
                <div class="stat-item bookings">
                    <span class="stat-number"><?php echo $users_with_bookings; ?></span>
                    <span class="stat-label">Users with Bookings</span>
                </div>
                <div class="stat-item month">
                    <span class="stat-number"><?php echo $this_month_users; ?></span>
                    <span class="stat-label">This Month</span>
                </div>
                <div class="stat-item today">
                    <span class="stat-number"><?php echo $today_users; ?></span>
                    <span class="stat-label">Today</span>
                </div>
            </div>
        </div>

        <!-- Users List -->
        <div class="content-card">
            <div class="content-header">
                <h2><i class="fas fa-users"></i> All Users</h2>
                <div>
                    <button class="btn btn-info" onclick="toggleView()">
                        <i class="fas fa-table"></i> <span id="viewToggleText">Table View</span>
                    </button>
                </div>
            </div>

            <!-- Card View -->
            <div id="cardView">
                <?php if ($users_result && $users_result->num_rows > 0): ?>
                    <?php while ($user = $users_result->fetch_assoc()): ?>
                        <div class="user-card">
                            <div class="user-header">
                                <div>
                                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                                    <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                                <div>
                                    <span class="stat-number" style="font-size: 1rem; color: #667eea;">
                                        ID: <?php echo $user['user_id']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="user-details">
                                <div class="detail-item">
                                    <i class="fas fa-phone"></i>
                                    <?php echo htmlspecialchars($user['phonenumber']); ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </div>
                            </div>
                            <div class="user-actions">
                                <button class="btn btn-info btn-sm" onclick="viewUserBookings(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                    <i class="fas fa-calendar-check"></i> View Bookings
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="editUser(
                                    <?php echo $user['user_id']; ?>,
                                    '<?php echo htmlspecialchars(addslashes($user['name'])); ?>',
                                    '<?php echo htmlspecialchars(addslashes($user['email'])); ?>',
                                    '<?php echo htmlspecialchars(addslashes($user['phonenumber'])); ?>'
                                )">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" action="manage_users.php" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this user? This will also delete all their bookings and vehicles.')">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php $users_result->data_seek(0); // Reset result pointer ?>
                <?php else: ?>
                    <p>No users found.</p>
                <?php endif; ?>
            </div>

            <!-- Table View -->
            <div id="tableView" style="display: none;">
                <?php if ($users_result && $users_result->num_rows > 0): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phonenumber']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="user-actions">
                                            <button class="btn btn-info btn-sm" onclick="viewUserBookings(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                                <i class="fas fa-calendar-check"></i>
                                            </button>
                                            <button class="btn btn-warning btn-sm" onclick="editUser(
                                                <?php echo $user['user_id']; ?>,
                                                '<?php echo htmlspecialchars(addslashes($user['name'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($user['email'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($user['phonenumber'])); ?>'
                                            )">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="manage_users.php" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No users found.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditUserModal()">&times;</span>
            <h2><i class="fas fa-user-edit"></i> Edit User</h2>
            <form method="POST" action="manage_users.php">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_name">Full Name</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email Address</label>
                        <input type="email" name="email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_password">New Password (leave empty to keep current)</label>
                        <input type="password" name="password" id="edit_password">
                    </div>
                    <div class="form-group">
                        <label for="edit_phonenumber">Phone Number</label>
                        <input type="tel" name="phonenumber" id="edit_phonenumber" maxlength="10" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update User
                </button>
            </form>
        </div>
    </div>

    <!-- User Bookings Modal -->
    <div id="userBookingsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeUserBookingsModal()">&times;</span>
            <h2><i class="fas fa-calendar-check"></i> User Bookings</h2>
            <div id="userBookingsContent">
                <!-- Bookings will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        // View toggle
        function toggleView() {
            const cardView = document.getElementById('cardView');
            const tableView = document.getElementById('tableView');
            const toggleText = document.getElementById('viewToggleText');
            
            if (cardView.style.display === 'none') {
                cardView.style.display = 'block';
                tableView.style.display = 'none';
                toggleText.textContent = 'Table View';
            } else {
                cardView.style.display = 'none';
                tableView.style.display = 'block';
                toggleText.textContent = 'Card View';
            }
        }

        // Edit User Modal
        const editUserModal = document.getElementById('editUserModal');
        
        function editUser(id, name, email, phone) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phonenumber').value = phone;
            document.getElementById('edit_password').value = '';
            editUserModal.style.display = 'block';
        }
        
        function closeEditUserModal() {
            editUserModal.style.display = 'none';
        }

        // User Bookings Modal
        const userBookingsModal = document.getElementById('userBookingsModal');
        
        function viewUserBookings(userId, userName) {
            document.getElementById('userBookingsContent').innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i><br>Loading bookings...</div>';
            userBookingsModal.style.display = 'block';
            
            // Fetch user bookings via AJAX
            fetch('fetch_user_bookings.php?user_id=' + userId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('userBookingsContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('userBookingsContent').innerHTML = '<div class="alert alert-error">Error loading bookings: ' + error + '</div>';
                });
        }
        
        function closeUserBookingsModal() {
            userBookingsModal.style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target === editUserModal) {
                closeEditUserModal();
            }
            if (event.target === userBookingsModal) {
                closeUserBookingsModal();
            }
        }

        // Phone number validation
        document.getElementById('edit_phonenumber').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
        });
    </script>
</body>
</html>
