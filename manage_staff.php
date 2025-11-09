<?php
// manage_staff.php
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

// Handle staff operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_staff':
                $staffname = trim($_POST['staffname']);
                $email = $_POST['email'];
                $password = $_POST['password'];
                $phone = trim($_POST['phone']);
                
                // Validate staff name (letters only)
                if (!preg_match('/^[a-zA-Z\s]+$/', $staffname)) {
                    $message = "Staff name should contain only letters and spaces!";
                    $messageType = "error";
                    break;
                }
                
                // Validate phone number (exactly 10 digits)
                if (!preg_match('/^[0-9]{10}$/', $phone)) {
                    $message = "Phone number should contain exactly 10 digits!";
                    $messageType = "error";
                    break;
                }
                
                // Check if email already exists
                $check_email = "SELECT * FROM staff WHERE email = ?";
                $check_stmt = $conn->prepare($check_email);
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $message = "Email already exists!";
                    $messageType = "error";
                } else {
                    $sql = "INSERT INTO staff (staffname, email, password, phone) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $staffname, $email, $password, $phone);
                    
                    if ($stmt->execute()) {
                        $message = "Staff member added successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error adding staff: " . $conn->error;
                        $messageType = "error";
                    }
                }
                break;
                
            case 'update_staff':
                $staff_id = $_POST['staff_id'];
                $staffname = trim($_POST['staffname']);
                $email = $_POST['email'];
                $password = $_POST['password'];
                $phone = trim($_POST['phone']);
                
                // Validate staff name (letters only)
                if (!preg_match('/^[a-zA-Z\s]+$/', $staffname)) {
                    $message = "Staff name should contain only letters and spaces!";
                    $messageType = "error";
                    break;
                }
                
                // Validate phone number (exactly 10 digits)
                if (!preg_match('/^[0-9]{10}$/', $phone)) {
                    $message = "Phone number should contain exactly 10 digits!";
                    $messageType = "error";
                    break;
                }
                
                // Check if email exists for other staff
                $check_email = "SELECT * FROM staff WHERE email = ? AND staff_id != ?";
                $check_stmt = $conn->prepare($check_email);
                $check_stmt->bind_param("si", $email, $staff_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $message = "Email already exists for another staff member!";
                    $messageType = "error";
                } else {
                    if (!empty($password)) {
                        $sql = "UPDATE staff SET staffname = ?, email = ?, password = ?, phone = ? WHERE staff_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssssi", $staffname, $email, $password, $phone, $staff_id);
                    } else {
                        $sql = "UPDATE staff SET staffname = ?, email = ?, phone = ? WHERE staff_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sssi", $staffname, $email, $phone, $staff_id);
                    }
                    
                    if ($stmt->execute()) {
                        $message = "Staff member updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error updating staff: " . $conn->error;
                        $messageType = "error";
                    }
                }
                break;
                
            case 'delete_staff':
                $staff_id = $_POST['staff_id'];
                $sql = "DELETE FROM staff WHERE staff_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $staff_id);
                
                if ($stmt->execute()) {
                    $message = "Staff member deleted successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error deleting staff: " . $conn->error;
                    $messageType = "error";
                }
                break;
        }
    }
}

// Fetch all staff
$staff_query = "SELECT * FROM staff ORDER BY created_at DESC";
$staff_result = $conn->query($staff_query);

// Get staff statistics
$total_staff_query = "SELECT COUNT(*) as total FROM staff";
$total_staff_result = $conn->query($total_staff_query);
$total_staff = $total_staff_result->fetch_assoc()['total'];

// Get staff with leave applications
$staff_with_leaves_query = "SELECT COUNT(DISTINCT staff_id) as count FROM leave_applications";
$staff_with_leaves_result = $conn->query($staff_with_leaves_query);
$staff_with_leaves = $staff_with_leaves_result->fetch_assoc()['count'];

// Get staff joined this month
$this_month_query = "SELECT COUNT(*) as count FROM staff WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
$this_month_result = $conn->query($this_month_query);
$this_month_staff = $this_month_result->fetch_assoc()['count'];

// Get staff joined today
$today_query = "SELECT COUNT(*) as count FROM staff WHERE DATE(created_at) = CURDATE()";
$today_result = $conn->query($today_query);
$today_staff = $today_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - Admin Dashboard</title>
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
        
        .stat-item.staff {
            background: linear-gradient(135deg, #48c9b0, #1dd1a1);
        }
        
        .stat-item.leaves {
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
        
        /* Staff Table */
        .staff-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .staff-table th,
        .staff-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .staff-table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
        }
        
        .staff-table tr:hover {
            background: #f8f9fa;
        }
        
        .staff-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* Staff Card */
        .staff-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease;
            border-left: 4px solid #667eea;
        }
        
        .staff-card:hover {
            transform: translateY(-5px);
        }
        
        .staff-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .staff-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .staff-email {
            color: #667eea;
            font-size: 1rem;
        }
        
        .staff-details {
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
        
        /* Leave Card */
        .leave-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
        }
        
        .leave-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .leave-id {
            font-weight: 700;
            color: #2c3e50;
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
            
            .staff-actions {
                justify-content: center;
            }
            
            .staff-table {
                font-size: 0.9rem;
            }
            
            .staff-table th,
            .staff-table td {
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
                    <a href="manage_users.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_staff.php" class="nav-link active">
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
                <h1>Manage Staff</h1>
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
                <h2><i class="fas fa-chart-line"></i> Staff Statistics</h2>
            </div>
            <div class="stats-grid">
                <div class="stat-item staff">
                    <span class="stat-number"><?php echo $total_staff; ?></span>
                    <span class="stat-label">Total Staff</span>
                </div>
                <div class="stat-item leaves">
                    <span class="stat-number"><?php echo $staff_with_leaves; ?></span>
                    <span class="stat-label">Staff with Leaves</span>
                </div>
                <div class="stat-item month">
                    <span class="stat-number"><?php echo $this_month_staff; ?></span>
                    <span class="stat-label">This Month</span>
                </div>
                <div class="stat-item today">
                    <span class="stat-number"><?php echo $today_staff; ?></span>
                    <span class="stat-label">Today</span>
                </div>
            </div>
        </div>

        <!-- Add New Staff -->
        <div class="content-card">
            <div class="content-header">
                <h2><i class="fas fa-user-plus"></i> Add New Staff</h2>
            </div>
            <form method="POST" action="manage_staff.php">
                <input type="hidden" name="action" value="add_staff">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="staffname">Staff Name</label>
                        <input type="text" name="staffname" id="staffname" pattern="[a-zA-Z\s]+" title="Staff name should contain only letters and spaces" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" name="phone" id="phone" pattern="[0-9]{10}" maxlength="10" title="Phone number should contain exactly 10 digits" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add Staff
                </button>
            </form>
        </div>

        <!-- Staff List -->
        <div class="content-card">
            <div class="content-header">
                <h2><i class="fas fa-user-tie"></i> All Staff Members</h2>
                <div>
                    <button class="btn btn-info" onclick="toggleView()">
                        <i class="fas fa-table"></i> <span id="viewToggleText">Table View</span>
                    </button>
                </div>
            </div>

            <!-- Card View -->
            <div id="cardView">
                <?php if ($staff_result && $staff_result->num_rows > 0): ?>
                    <?php while ($staff = $staff_result->fetch_assoc()): ?>
                        <div class="staff-card">
                            <div class="staff-header">
                                <div>
                                    <div class="staff-name"><?php echo htmlspecialchars($staff['staffname']); ?></div>
                                    <div class="staff-email"><?php echo htmlspecialchars($staff['email']); ?></div>
                                </div>
                                <div>
                                    <span class="stat-number" style="font-size: 1rem; color: #667eea;">
                                        ID: <?php echo $staff['staff_id']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="staff-details">
                                <div class="detail-item">
                                    <i class="fas fa-phone"></i>
                                    <?php echo htmlspecialchars($staff['phone']); ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-user-tag"></i>
                                    <?php echo htmlspecialchars($staff['role']); ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($staff['created_at'])); ?>
                                </div>
                            </div>
                            <div class="staff-actions">
                                <button class="btn btn-info btn-sm" onclick="viewStaffLeaves(<?php echo $staff['staff_id']; ?>, '<?php echo htmlspecialchars($staff['staffname']); ?>')">
                                    <i class="fas fa-calendar-times"></i> View Leaves
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="editStaff(
                                    <?php echo $staff['staff_id']; ?>,
                                    '<?php echo htmlspecialchars(addslashes($staff['staffname'])); ?>',
                                    '<?php echo htmlspecialchars(addslashes($staff['email'])); ?>',
                                    '<?php echo htmlspecialchars(addslashes($staff['phone'])); ?>'
                                )">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" action="manage_staff.php" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this staff member? This will also delete all their leave applications.')">
                                    <input type="hidden" name="action" value="delete_staff">
                                    <input type="hidden" name="staff_id" value="<?php echo $staff['staff_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    <?php $staff_result->data_seek(0); // Reset result pointer ?>
                <?php else: ?>
                    <p>No staff members found. Add a new staff member above.</p>
                <?php endif; ?>
            </div>

            <!-- Table View -->
            <div id="tableView" style="display: none;">
                <?php if ($staff_result && $staff_result->num_rows > 0): ?>
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($staff = $staff_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $staff['staff_id']; ?></td>
                                    <td><?php echo htmlspecialchars($staff['staffname']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['phone']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($staff['role'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($staff['created_at'])); ?></td>
                                    <td>
                                        <div class="staff-actions">
                                            <button class="btn btn-info btn-sm" onclick="viewStaffLeaves(<?php echo $staff['staff_id']; ?>, '<?php echo htmlspecialchars($staff['staffname']); ?>')">
                                                <i class="fas fa-calendar-times"></i>
                                            </button>
                                            <button class="btn btn-warning btn-sm" onclick="editStaff(
                                                <?php echo $staff['staff_id']; ?>,
                                                '<?php echo htmlspecialchars(addslashes($staff['staffname'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($staff['email'])); ?>',
                                                '<?php echo htmlspecialchars(addslashes($staff['phone'])); ?>'
                                            )">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="manage_staff.php" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this staff member?')">
                                                <input type="hidden" name="action" value="delete_staff">
                                                <input type="hidden" name="staff_id" value="<?php echo $staff['staff_id']; ?>">
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
                    <p>No staff members found. Add a new staff member above.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Edit Staff Modal -->
    <div id="editStaffModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditStaffModal()">&times;</span>
            <h2><i class="fas fa-user-edit"></i> Edit Staff</h2>
            <form method="POST" action="manage_staff.php">
                <input type="hidden" name="action" value="update_staff">
                <input type="hidden" name="staff_id" id="edit_staff_id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_staffname">Staff Name</label>
                        <input type="text" name="staffname" id="edit_staffname" pattern="[a-zA-Z\s]+" title="Staff name should contain only letters and spaces" required>
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
                        <label for="edit_phone">Phone Number</label>
                        <input type="tel" name="phone" id="edit_phone" pattern="[0-9]{10}" maxlength="10" title="Phone number should contain exactly 10 digits" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Staff
                </button>
            </form>
        </div>
    </div>

    <!-- Staff Leaves Modal -->
    <div id="staffLeavesModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeStaffLeavesModal()">&times;</span>
            <h2><i class="fas fa-calendar-times"></i> Staff Leave Applications</h2>
            <div id="staffLeavesContent">
                <!-- Leaves will be loaded here -->
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

        // Edit Staff Modal
        const editStaffModal = document.getElementById('editStaffModal');
        
        function editStaff(id, name, email, phone) {
            document.getElementById('edit_staff_id').value = id;
            document.getElementById('edit_staffname').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_password').value = '';
            editStaffModal.style.display = 'block';
        }
        
        function closeEditStaffModal() {
            editStaffModal.style.display = 'none';
        }

        // Staff Leaves Modal
        const staffLeavesModal = document.getElementById('staffLeavesModal');
        
        function viewStaffLeaves(staffId, staffName) {
            document.getElementById('staffLeavesContent').innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i><br>Loading leave applications...</div>';
            staffLeavesModal.style.display = 'block';
            
            // Fetch staff leaves via AJAX
            fetch('fetch_staff_leaves.php?staff_id=' + staffId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('staffLeavesContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('staffLeavesContent').innerHTML = '<div class="alert alert-error">Error loading leave applications: ' + error + '</div>';
                });
        }
        
        function closeStaffLeavesModal() {
            staffLeavesModal.style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target === editStaffModal) {
                closeEditStaffModal();
            }
            if (event.target === staffLeavesModal) {
                closeStaffLeavesModal();
            }
        }

        // Staff name validation (letters and spaces only)
        document.getElementById('staffname').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
        });
        
        document.getElementById('edit_staffname').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
        });
        
        // Phone number validation (exactly 10 digits)
        document.getElementById('phone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });
        
        document.getElementById('edit_phone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });
        
        // Form validation before submission
        function validateStaffForm(form) {
            const staffName = form.querySelector('input[name="staffname"]').value.trim();
            const phone = form.querySelector('input[name="phone"]').value.trim();
            
            // Validate staff name
            if (!/^[a-zA-Z\s]+$/.test(staffName)) {
                alert('Staff name should contain only letters and spaces!');
                return false;
            }
            
            // Validate phone number
            if (!/^[0-9]{10}$/.test(phone)) {
                alert('Phone number should contain exactly 10 digits!');
                return false;
            }
            
            return true;
        }
        
        // Add form validation to add staff form
        document.querySelector('form[action="manage_staff.php"]').addEventListener('submit', function(e) {
            if (this.querySelector('input[name="action"]').value === 'add_staff') {
                if (!validateStaffForm(this)) {
                    e.preventDefault();
                }
            }
        });
        
        // Add form validation to edit staff form
        document.querySelector('#editStaffModal form').addEventListener('submit', function(e) {
            if (!validateStaffForm(this)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
