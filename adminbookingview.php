<?php
// manage_bookings.php
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

// Handle POST actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_status':
            $booking_id = intval($_POST['booking_id']);
            $status = $conn->real_escape_string($_POST['status']);
            
            $update_query = "UPDATE bookings SET status = '$status' WHERE booking_id = $booking_id";
            
            if ($conn->query($update_query)) {
                $message = "Booking status updated successfully!";
                $messageType = 'success';
            } else {
                $message = "Error updating booking status: " . $conn->error;
                $messageType = 'error';
            }
            break;
            
        case 'assign_mechanic':
            $booking_id = intval($_POST['booking_id']);
            $mechanic_id = intval($_POST['mechanic_id']);
            
            // Get mechanic name
            $mechanic_query = "SELECT name FROM mechanics WHERE mechanic_id = $mechanic_id";
            $mechanic_result = $conn->query($mechanic_query);
            $mechanic_name = '';
            if ($mechanic_result && $mechanic_row = $mechanic_result->fetch_assoc()) {
                $mechanic_name = $mechanic_row['name'];
            }
            
            $update_query = "UPDATE bookings SET mechanic_id = $mechanic_id, mechanic = '$mechanic_name' WHERE booking_id = $booking_id";
            
            if ($conn->query($update_query)) {
                // Update mechanic status to assigned
                $conn->query("UPDATE mechanics SET status = 'assigned' WHERE mechanic_id = $mechanic_id");
                $message = "Mechanic assigned successfully!";
                $messageType = 'success';
            } else {
                $message = "Error assigning mechanic: " . $conn->error;
                $messageType = 'error';
            }
            break;
            
        case 'delete_booking':
            $booking_id = intval($_POST['booking_id']);
            
            // Get mechanic ID before deleting to free them up
            $get_mechanic = "SELECT mechanic_id FROM bookings WHERE booking_id = $booking_id";
            $mechanic_result = $conn->query($get_mechanic);
            if ($mechanic_result && $mechanic_row = $mechanic_result->fetch_assoc()) {
                if ($mechanic_row['mechanic_id']) {
                    $conn->query("UPDATE mechanics SET status = 'free' WHERE mechanic_id = " . $mechanic_row['mechanic_id']);
                }
            }
            
            // Delete related booking services first
            $conn->query("DELETE FROM booking_services WHERE booking_id = $booking_id");
            
            $delete_query = "DELETE FROM bookings WHERE booking_id = $booking_id";
            
            if ($conn->query($delete_query)) {
                $message = "Booking deleted successfully!";
                $messageType = 'success';
            } else {
                $message = "Error deleting booking: " . $conn->error;
                $messageType = 'error';
            }
            break;
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$mechanic_filter = $_GET['mechanic'] ?? '';

// Build WHERE clause for filters
$where_conditions = [];
if ($status_filter && $status_filter !== 'all') {
    $where_conditions[] = "b.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($date_filter) {
    $where_conditions[] = "DATE(b.appointment_date) = '" . $conn->real_escape_string($date_filter) . "'";
}
if ($mechanic_filter && $mechanic_filter !== 'all') {
    $where_conditions[] = "b.mechanic_id = " . intval($mechanic_filter);
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Fetch all bookings with related information
$bookings_query = "
    SELECT 
        b.*,
        u.name as user_name,
        u.email as user_email,
        u.phonenumber as user_phone,
        v.vehicle_type,
        v.brand,
        v.model,
        v.registration_no,
        m.name as mechanic_name,
        GROUP_CONCAT(s.service_name SEPARATOR ', ') as services,
        SUM(bs.service_price) as total_amount
    FROM bookings b
    LEFT JOIN register u ON b.user_id = u.user_id
    LEFT JOIN vehicles v ON b.vehicle_id = v.vehicle_id
    LEFT JOIN mechanics m ON b.mechanic_id = m.mechanic_id
    LEFT JOIN booking_services bs ON b.booking_id = bs.booking_id
    LEFT JOIN services s ON bs.service_id = s.service_id
    $where_clause
    GROUP BY b.booking_id
    ORDER BY b.booking_datetime DESC
";

$bookings_result = $conn->query($bookings_query);

// Get available mechanics for assignment
$mechanics_query = "SELECT mechanic_id, name FROM mechanics WHERE status = 'free' ORDER BY name";
$mechanics_result = $conn->query($mechanics_query);

// Get booking statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_bookings,
        SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as inprogress_bookings,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_bookings,
        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
    FROM bookings
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get mechanics for filter dropdown
$filter_mechanics_query = "SELECT DISTINCT m.mechanic_id, m.name FROM mechanics m 
                          INNER JOIN bookings b ON m.mechanic_id = b.mechanic_id 
                          ORDER BY m.name";
$filter_mechanics_result = $conn->query($filter_mechanics_query);

// Calculate total profit from all bookings
$profit_query = "SELECT SUM(bs.service_price) as total_profit FROM booking_services bs";
$profit_result = $conn->query($profit_query);
$total_profit = 0;
if ($profit_result && $profit_row = $profit_result->fetch_assoc()) {
    $total_profit = $profit_row['total_profit'] ?? 0;
}

// Format profit for display (e.g., 1L for values over 100,000)
function formatProfit($amount) {
    if ($amount >= 100000) {
        $lakhs = floor($amount / 100000);
        $remainder = $amount % 100000;
        if ($remainder > 0) {
            return $lakhs . 'L ' . number_format($remainder, 0);
        } else {
            return $lakhs . 'L';
        }
    } else {
        return '₹' . number_format($amount, 0);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Manage Bookings - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
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
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .stat-card.total::before { background: linear-gradient(90deg, #667eea, #764ba2); }
        .stat-card.pending::before { background: linear-gradient(90deg, #feca57, #ff9f43); }
        .stat-card.confirmed::before { background: linear-gradient(90deg, #48c9b0, #1dd1a1); }
        .stat-card.inprogress::before { background: linear-gradient(90deg, #74b9ff, #0984e3); }
        .stat-card.completed::before { background: linear-gradient(90deg, #00b894, #00a085); }
        .stat-card.cancelled::before { background: linear-gradient(90deg, #ff6b6b, #ee5a52); }
        .stat-card.profit::before { background: linear-gradient(90deg, #2ecc71, #27ae60); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 1rem;
            font-weight: 600;
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
        
        /* Filters */
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Button Styles */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
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
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        .bookings-table th,
        .bookings-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .bookings-table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        .bookings-table tr:hover {
            background: #f8f9fa;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-in-progress { background: #cce7ff; color: #004085; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        /* Booking Details */
        .booking-details {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .user-info {
            margin-bottom: 5px;
        }
        
        .user-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .vehicle-info {
            color: #667eea;
            font-weight: 500;
        }
        
        .services-list {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .amount {
            font-weight: 700;
            color: #28a745;
            font-size: 1.1rem;
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .content-header {
                flex-direction: column;
                gap: 20px;
                align-items: stretch;
            }
            .filters {
                flex-direction: column;
            }
            .bookings-table {
                font-size: 0.8rem;
            }
            .bookings-table th,
            .bookings-table td {
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
                    <a href="manage_bookings.php" class="nav-link active">
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
                    <a href="manage_staff.php" class="nav-link">
                        <i class="fas fa-user-tie"></i>
                        <span>Manage Staff</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="manage_services.php" class="nav-link">
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
                <h1>Manage Bookings</h1>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="alert <?php echo ($messageType === 'success') ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number"><?php echo $stats['pending_bookings']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card confirmed">
                <div class="stat-number"><?php echo $stats['confirmed_bookings']; ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card inprogress">
                <div class="stat-number"><?php echo $stats['inprogress_bookings']; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card completed">
                <div class="stat-number"><?php echo $stats['completed_bookings']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card cancelled">
                <div class="stat-number"><?php echo $stats['cancelled_bookings']; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
            <div class="stat-card profit">
                <div class="stat-number"><?php echo formatProfit($total_profit); ?></div>
                <div class="stat-label">Total Profit</div>
            </div>
        </div>

        <!-- Bookings List -->
        <div class="content-card">
            <div class="content-header">
                <h2>All Bookings</h2>
            </div>

            <!-- Filters -->
            <form method="GET" class="filters">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="all" <?php echo ($status_filter === 'all' || !$status_filter) ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="Pending" <?php echo ($status_filter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Confirmed" <?php echo ($status_filter === 'Confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="In Progress" <?php echo ($status_filter === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?php echo ($status_filter === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo ($status_filter === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                </div>
                <div class="filter-group">
                    <label>Mechanic</label>
                    <select name="mechanic">
                        <option value="all" <?php echo ($mechanic_filter === 'all' || !$mechanic_filter) ? 'selected' : ''; ?>>All Mechanics</option>
                        <?php 
                        if ($filter_mechanics_result && $filter_mechanics_result->num_rows > 0):
                            while ($mechanic = $filter_mechanics_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $mechanic['mechanic_id']; ?>" 
                                    <?php echo ($mechanic_filter == $mechanic['mechanic_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($mechanic['name']); ?>
                            </option>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </select>
                </div>
                <div class="filter-group" style="justify-content: end;">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                <div class="filter-group" style="justify-content: end;">
                    <label>&nbsp;</label>
                    <a href="manage_bookings.php" class="btn btn-info">Clear</a>
                </div>
            </form>

            <!-- Bookings Table -->
            <?php if ($bookings_result && $bookings_result->num_rows > 0): ?>
                <div class="table-container">
                    <table class="bookings-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Services</th>
                                <th>Date & Time</th>
                                <th>Mechanic</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo str_pad($booking['booking_id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td>
                                        <div class="booking-details">
                                            <div class="user-info">
                                                <span class="user-name"><?php echo htmlspecialchars($booking['user_name'] ?? 'Unknown'); ?></span>
                                                <br>
                                                <small>ID: <?php echo $booking['user_id']; ?></small>
                                            </div>
                                            <div style="font-size: 0.8rem; color: #6c757d;">
                                                <?php echo htmlspecialchars($booking['user_email']); ?><br>
                                                <?php echo htmlspecialchars($booking['user_phone'] ?? 'No phone'); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="vehicle-info">
                                            <strong><?php echo htmlspecialchars($booking['brand'] ?? 'Unknown'); ?> 
                                            <?php echo htmlspecialchars($booking['model'] ?? ''); ?></strong><br>
                                            <small><?php echo htmlspecialchars($booking['vehicle_type'] ?? ''); ?></small><br>
                                            <small><?php echo htmlspecialchars($booking['registration_no'] ?? 'No reg'); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="services-list" title="<?php echo htmlspecialchars($booking['services'] ?? 'No services'); ?>">
                                            <?php echo htmlspecialchars($booking['services'] ?? 'No services'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo date('M d, Y', strtotime($booking['appointment_date'])); ?></strong><br>
                                            <small><?php echo htmlspecialchars($booking['time_slot']); ?></small><br>
                                            <small style="color: #6c757d;">
                                                Booked: <?php echo date('M d, Y H:i', strtotime($booking['booking_datetime'])); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($booking['mechanic_name']): ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($booking['mechanic_name']); ?></strong><br>
                                                <small>ID: <?php echo $booking['mechanic_id']; ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #ffc107; font-weight: 600;">Not Assigned</span><br>
                                            <button class="btn btn-sm btn-primary" onclick="assignMechanic(<?php echo $booking['booking_id']; ?>)">
                                                Assign
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $booking['status'])); ?>">
                                            <?php echo htmlspecialchars($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="amount">
                                            $<?php echo number_format($booking['total_amount'] ?? 0, 2); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <button class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $booking['booking_id']; ?>, '<?php echo htmlspecialchars($booking['status']); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-info" onclick="viewDetails(<?php echo $booking['booking_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this booking?')">
                                                <input type="hidden" name="action" value="delete_booking" />
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>" />
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: #6c757d;">
                    <i class="fas fa-calendar-times fa-3x" style="margin-bottom: 20px; color: #dee2e6;"></i>
                    <h3>No Bookings Found</h3>
                    <p>There are no bookings matching your current filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Update Status Modal -->
    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('updateStatusModal')">&times;</span>
            <h2>Update Booking Status</h2>
            <form method="POST" action="" id="updateStatusForm">
                <input type="hidden" name="action" value="update_status" />
                <input type="hidden" name="booking_id" id="status_booking_id" />
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="booking_status" style="display: block; margin-bottom: 10px; font-weight: 600;">New Status:</label>
                    <select name="status" id="booking_status" required style="width: 100%; padding: 12px 15px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 1rem;">
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('updateStatusModal')" style="margin-right: 10px; background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Mechanic Modal -->
    <div id="assignMechanicModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('assignMechanicModal')">&times;</span>
            <h2>Assign Mechanic</h2>
            <form method="POST" action="" id="assignMechanicForm">
                <input type="hidden" name="action" value="assign_mechanic" />
                <input type="hidden" name="booking_id" id="mechanic_booking_id" />
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="mechanic_select" style="display: block; margin-bottom: 10px; font-weight: 600;">Select Mechanic:</label>
                    <select name="mechanic_id" id="mechanic_select" required style="width: 100%; padding: 12px 15px; border: 2px solid #e9ecef; border-radius: 10px; font-size: 1rem;">
                        <option value="">Choose a mechanic...</option>
                        <?php 
                        if ($mechanics_result && $mechanics_result->num_rows > 0):
                            while ($mechanic = $mechanics_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $mechanic['mechanic_id']; ?>">
                                <?php echo htmlspecialchars($mechanic['name']); ?> (ID: <?php echo $mechanic['mechanic_id']; ?>)
                            </option>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </select>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('assignMechanicModal')" style="margin-right: 10px; background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Mechanic</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div id="bookingDetailsModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeModal('bookingDetailsModal')">&times;</span>
            <h2>Booking Details</h2>
            <div id="bookingDetailsContent">
                <!-- Content will be populated by JavaScript -->
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

        // Update status functionality
        function updateStatus(bookingId, currentStatus) {
            document.getElementById('status_booking_id').value = bookingId;
            document.getElementById('booking_status').value = currentStatus;
            document.getElementById('updateStatusModal').style.display = 'block';
        }

        // Assign mechanic functionality
        function assignMechanic(bookingId) {
            document.getElementById('mechanic_booking_id').value = bookingId;
            document.getElementById('assignMechanicModal').style.display = 'block';
        }

        // View booking details
        function viewDetails(bookingId) {
            // In a real implementation, you'd fetch details via AJAX
            // For now, we'll show the modal with basic info
            const content = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-info-circle fa-3x" style="color: #667eea; margin-bottom: 20px;"></i>
                    <h3>Booking #${bookingId.toString().padStart(4, '0')}</h3>
                    <p>Detailed view functionality can be implemented here.</p>
                    <p>This would typically show complete booking information, service history, and customer communication.</p>
                </div>
            `;
            document.getElementById('bookingDetailsContent').innerHTML = content;
            document.getElementById('bookingDetailsModal').style.display = 'block';
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });

        // Responsive sidebar for mobile
        if (window.innerWidth <= 768) {
            document.getElementById('sidebar').classList.add('collapsed');
            document.getElementById('mainContent').classList.add('expanded');
        }

        // Auto refresh every 30 seconds for real-time updates
        setInterval(function() {
            // Only refresh if no modals are open
            const openModals = document.querySelectorAll('.modal[style*="block"]');
            if (openModals.length === 0) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>

<?php
$conn->close();
?>