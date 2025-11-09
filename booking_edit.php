<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Session check
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit();
}

// Prevent browser back after logout
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");



// Fetch all mechanics for dropdown (regardless of availability)
$available_mechanics = [];
$result = $conn->query("SELECT mechanic_id, name, profession FROM mechanics");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $available_mechanics[] = $row;
    }
}

// Handle update booking
if (isset($_POST['update_booking'])) {
    $booking_id = $_POST['booking_id'];
    $mechanic_id = $_POST['mechanic'];
    $time_slot = $_POST['time_slot'];
    $status = $_POST['status'];
    $appointment_date = $_POST['appointment_date'];

    // Now update bookings table including mechanic's name
    $stmt = $conn->prepare("UPDATE bookings SET status=?, mechanic_id=?, time_slot=?, appointment_date=? WHERE booking_id=?");
    $stmt->bind_param("sisss", $status, $mechanic_id, $time_slot, $appointment_date, $booking_id);

    if ($stmt->execute()) {
        $msg = "✅ Booking updated successfully! Status changed to: $status.";
    } else {
        $msg = "❌ Error updating booking. Please try again.";
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoCare Pro - Edit Bookings</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: url('images/staffdashboard.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            min-height: 100vh;
            color: #333;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 248, 240, 0.85);
            z-index: -1;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #ff8c42 0%, #ff7b25 100%);
            box-shadow: 4px 0 20px rgba(255, 140, 66, 0.3);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(0, 0, 0, 0.1);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
        }

        .sidebar-logo-icon {
            background: linear-gradient(45deg, #fff, #ffe4d1);
            color: #ff8c42;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
        }

        .sidebar-logo-text {
            font-size: 22px;
            font-weight: 700;
            color: white;
        }

        .sidebar-subtitle {
            font-size: 12px;
            color: #ffe4d1;
            margin-top: 2px;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-item {
            margin: 5px 15px;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: #ffe4d1;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #fff 0%, #ffe4d1 100%);
            color: #ff8c42;
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .nav-text {
            font-weight: 500;
            font-size: 15px;
        }

        .nav-badge {
            background: #e74c3c;
            color: white;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
            margin-left: auto;
            font-weight: 600;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
        }

        .user-profile-sidebar {
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 15px;
            backdrop-filter: blur(10px);
        }

        .user-info-sidebar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .user-avatar-sidebar {
            background: linear-gradient(45deg, #fff, #ffe4d1);
            color: #ff8c42;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .user-role {
            color: #ffe4d1;
            font-size: 12px;
        }

        .logout-btn-sidebar {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
        }

        .logout-btn-sidebar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .logout-btn-sidebar a {
            color: white;
            text-decoration: none;
        }

        /* Mobile menu toggle */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: rgba(255, 140, 66, 0.95);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
        }

        /* Main Content */
        .main-wrapper {
            margin-left: 280px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(255, 140, 66, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 140, 66, 0.1);
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #ff8c42;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-icon {
            background: linear-gradient(45deg, #ff8c42, #ff7b25);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
        }

        .breadcrumb {
            color: #ff7b25;
            font-size: 14px;
            margin-top: 5px;
        }

        .filters-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(255, 140, 66, 0.15);
            border: 1px solid rgba(255, 140, 66, 0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-weight: 600;
            color: #ff8c42;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .filter-select, .filter-input {
            padding: 10px;
            border: 2px solid #ffe4d1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
        }

        .filter-btn {
            background: linear-gradient(45deg, #ff8c42, #ff7b25);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(255, 140, 66, 0.15);
            border: 1px solid rgba(255, 140, 66, 0.1);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ffe4d1;
        }

        .section-icon {
            background: linear-gradient(45deg, #ff8c42, #ff7b25);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: #ff8c42;
        }

        .success-message {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }

        .warning-message {
            background: linear-gradient(45deg, #f39c12, #e67e22);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }

        .error-message {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .booking-card {
            background: #ffffff;
            border: 2px solid rgba(255, 140, 66, 0.1);
            border-radius: 12px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.08);
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            box-shadow: 0 8px 25px rgba(255, 140, 66, 0.15);
            transform: translateY(-2px);
            border-color: rgba(255, 140, 66, 0.2);
        }

        .booking-header {
            background: linear-gradient(45deg, #fff8f0, #ffe4d1);
            padding: 20px;
            border-bottom: 1px solid rgba(255, 140, 66, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .booking-id {
            background: linear-gradient(45deg, #ff8c42, #ff7b25);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .booking-status {
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-assigned { background: #cce5ff; color: #0066cc; }
        .status-in-progress { background: #fff3cd; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }

        .booking-date {
            color: #ff7b25;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .booking-body {
            padding: 25px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-section {
            background: #fff8f0;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ff8c42;
        }

        .info-label {
            font-weight: 600;
            color: #ff8c42;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-value {
            color: #333;
            font-size: 16px;
        }

        .services-list {
            background: #ffffff;
            border: 1px solid #ffe4d1;
            border-radius: 8px;
            margin: 15px 0;
        }

        .service-item {
            padding: 15px 20px;
            border-bottom: 1px solid #ffe4d1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .service-item:last-child {
            border-bottom: none;
        }

        .service-name {
            font-weight: 600;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .service-price {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
        }

        .total-section {
            background: linear-gradient(45deg, #ff8c42, #ff7b25);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
        }

        .total-label {
            font-size: 16px;
            margin-bottom: 8px;
        }

        .total-amount {
            font-size: 28px;
            font-weight: 700;
        }

        .update-form {
            background: linear-gradient(135deg, #fff8f0 0%, #ffe4d1 100%);
            padding: 25px;
            border-radius: 12px;
            border: 2px solid #ff8c42;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.1);
        }

        .form-section-title {
            color: #ff8c42;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ff8c42;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .date-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            color: #ff8c42;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .form-input, .form-select {
            padding: 12px 15px;
            border: 2px solid #ffe4d1;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 4px rgba(255, 140, 66, 0.05);
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
            transform: translateY(-1px);
        }

        .date-input {
            background: linear-gradient(135deg, #ffffff 0%, #fff8f0 100%);
            border: 2px solid #ffe4d1;
            color: #ff8c42;
            font-weight: 500;
        }

        .date-input:focus {
            background: white;
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
        }

        .update-btn {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            width: 100%;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.2);
        }

        .update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.3);
        }

        .update-btn:active {
            transform: translateY(0);
        }

        .no-bookings {
            text-align: center;
            padding: 60px 20px;
            color: #ff7b25;
        }

        .no-bookings-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.7;
            color: #ff8c42;
        }

        .date-section {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #f39c12;
        }

        .date-section-title {
            color: #e67e22;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-wrapper {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .date-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .dashboard-container {
                padding: 10px;
            }

            .main-content {
                padding: 20px;
            }

            .booking-body {
                padding: 15px;
            }

            .update-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="fas fa-car"></i>
                </div>
                <div>
                    <div class="sidebar-logo-text">Garage</div>
                    <div class="sidebar-subtitle">Staff Dashboard</div>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="staff.php" class="nav-link">
                    <i class="fas fa-tachometer-alt nav-icon"></i>
                    <span class="nav-text">New bookings</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="booking_edit.php" class="nav-link active">
                    <i class="fas fa-edit nav-icon"></i>
                    <span class="nav-text">Edit Bookings</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="bookingview.php" class="nav-link">
                    <i class="fas fa-list nav-icon"></i>
                    <span class="nav-text">All Bookings</span>
                </a>
            </div>
               <div class="nav-item">
                <a href="replay.php" class="nav-link">
                    <i class="fas fa-envelope nav-icon"></i>
                    <span class="nav-text">Enquiries</span>
                </a>
            </div>
            
            
            <div class="nav-item">
                <a href="leave.php" class="nav-link">
                    <i class="fas fa-calendar-times nav-icon"></i>
                    <span class="nav-text">Leave</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile-sidebar">
                <div class="user-info-sidebar">
                    <div class="user-avatar-sidebar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?= explode('@', $_SESSION['email'])[0] ?></div>
                        <div class="user-role">Staff Member</div>
                    </div>
                </div>
                <form action="logout.php" method="post">
                    <button class="logout-btn-sidebar" type="submit">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-wrapper">
        <div class="dashboard-container">
            <!-- Header -->
            <div class="header">
                <div>
                    <div class="page-title">
                        <div class="page-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div>
                            <div>Edit Bookings</div>
                            <div class="breadcrumb">Home > Edit Bookings</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <form method="GET" class="filters-grid">
                    <div class="filter-group">
                        <label class="filter-label">Filter by Status</label>
                        <select name="status" class="filter-select">
                            <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Bookings</option>
                            <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Assigned" <?= $status_filter == 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                            <option value="In Progress" <?= $status_filter == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Completed" <?= $status_filter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label">Search by Booking ID</label>
                        <input type="text" name="search" class="filter-input" placeholder="Enter Booking ID" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="filter-btn">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="section-title">Booking Management</div>
                </div>

                <?php if (isset($msg)): ?>
                    <?php if (strpos($msg, '✅') !== false): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <?= $msg ?>
                        </div>
                    <?php elseif (strpos($msg, '⚠️') !== false): ?>
                        <div class="warning-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?= $msg ?>
                        </div>
                    <?php else: ?>
                        <div class="error-message">
                            <i class="fas fa-times-circle"></i>
                            <?= $msg ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php
                // Build query based on filters
                $where_clause = "WHERE 1=1";
                $params = [];
                $types = "";

                if ($status_filter != 'all') {
                    $where_clause .= " AND b.status = ?";
                    $params[] = $status_filter;
                    $types .= "s";
                }

                if (!empty($search)) {
                    $where_clause .= " AND b.booking_id = ?";
                    $params[] = $search;
                    $types .= "i";
                }

                $query = "SELECT b.*, r.name as customer_name, v.brand, v.model, v.registration_no, m.name as mechanic_name 
                         FROM bookings b 
                         JOIN register r ON b.user_id = r.user_id 
                         JOIN vehicles v ON b.vehicle_id = v.vehicle_id 
                         LEFT JOIN mechanics m ON b.mechanic_id = m.mechanic_id 
                         $where_clause 
                         ORDER BY b.booking_datetime DESC";

                $stmt = $conn->prepare($query);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0):
                    while ($booking = $result->fetch_assoc()):
                        $booking_id = $booking['booking_id'];

                        // Get services booked
                        $services = $conn->query("SELECT bs.service_id, s.service_name, bs.service_price 
                                                  FROM booking_services bs 
                                                  JOIN services s ON bs.service_id = s.service_id 
                                                  WHERE bs.booking_id = $booking_id");

                        $total = 0;
                ?>
                    <div class="booking-card">
                        <div class="booking-header">
                            <div class="booking-id">Booking #<?= $booking_id ?></div>
                            <div class="booking-status status-<?= strtolower(str_replace(' ', '-', $booking['status'])) ?>">
                                <?= $booking['status'] ?>
                            </div>
                            <div class="booking-date">
                                <i class="fas fa-calendar"></i>
                                <?= date('M d, Y', strtotime($booking['booking_datetime'])) ?>
                            </div>
                        </div>

                        <div class="booking-body">
                            <div class="info-grid">
                                <div class="info-section">
                                    <div class="info-label">
                                        <i class="fas fa-user"></i>
                                        Customer
                                    </div>
                                    <div class="info-value"><?= $booking['customer_name'] ?></div>
                                </div>

                                <div class="info-section">
                                    <div class="info-label">
                                        <i class="fas fa-car"></i>
                                        Vehicle
                                    </div>
                                    <div class="info-value">
                                        <?= $booking['brand'] . ' ' . $booking['model'] ?><br>
                                        <small style="color: #999;"><?= $booking['registration_no'] ?></small>
                                    </div>
                                </div>

                                <div class="info-section">
                                    <div class="info-label">
                                        <i class="fas fa-user-cog"></i>
                                        Assigned Mechanic
                                    </div>
                                    <div class="info-value"><?= $booking['mechanic_name'] ?? 'Not assigned' ?></div>
                                </div>

                                <div class="info-section">
                                    <div class="info-label">
                                        <i class="fas fa-calendar-check"></i>
                                        Appointment
                                    </div>
                                    <div class="info-value">
                                        <?= $booking['appointment_date'] ? date('M d, Y', strtotime($booking['appointment_date'])) : 'Not scheduled' ?><br>
                                        <small style="color: #999;"><?= $booking['time_slot'] ?? 'No time set' ?></small>
                                    </div>
                                </div>
                            </div>

                            <div class="info-label">
                                <i class="fas fa-wrench"></i>
                                Services Requested
                            </div>
                            <div class="services-list">
                                <?php while ($svc = $services->fetch_assoc()):
                                    $total += $svc['service_price']; ?>
                                    <div class="service-item">
                                        <div class="service-name">
                                            <i class="fas fa-tools"></i>
                                            <?= $svc['service_name'] ?>
                                        </div>
                                        <div class="service-price">₹<?= $svc['service_price'] ?></div>
                                    </div>
                                <?php endwhile; ?>
                            </div>

                            <div class="total-section">
                                <div class="total-label">Total Service Cost</div>
                                <div class="total-amount">₹<?= $total ?></div>
                            </div>

                            <div class="update-form">
                                <div class="form-section-title">
                                    <i class="fas fa-edit"></i>
                                    Update Booking Details
                                </div>
                                
                                <form method="post">
                                    <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-user-cog"></i>
                                                Assign Mechanic
                                            </label>
                                            <select name="mechanic" class="form-select">
                                                <option value="">Select Mechanic</option>
                                                <?php foreach ($available_mechanics as $mech): ?>
                                                    <option value="<?= $mech['mechanic_id'] ?>" <?= $booking['mechanic_id'] == $mech['mechanic_id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($mech['name'] . ' (' . $mech['profession'] . ')') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-clock"></i>
                                                Time Slot
                                            </label>
                                            <input type="time" name="time_slot" class="form-input" value="<?= $booking['time_slot'] ?>">
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-tasks"></i>
                                                Status
                                            </label>
                                            <select name="status" class="form-select" required>
                                                <option value="Pending" <?= $booking['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="Assigned" <?= $booking['status'] == 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                                                <option value="In Progress" <?= $booking['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                <option value="Completed" <?= $booking['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="date-section">
                                        <div class="date-section-title">
                                            <i class="fas fa-calendar-check"></i>
                                            Schedule Service Date
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-calendar-alt"></i>
                                                Appointment Date
                                            </label>
                                            <input type="date" name="appointment_date" class="form-input date-input" value="<?= $booking['appointment_date'] ?>">
                                        </div>
                                    </div>

                                    <button type="submit" name="update_booking" class="update-btn">
                                        <i class="fas fa-save"></i>
                                        Update Booking
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div class="no-bookings">
                        <div class="no-bookings-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>No Bookings Found</h3>
                        <p>No bookings match your current filters. Try adjusting your search criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
            }
        });

        // Auto-dismiss messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.success-message, .warning-message, .error-message');
            messages.forEach(function(msg) {
                msg.style.opacity = '0';
                msg.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    msg.remove();
                }, 300);
            });
        }, 5000);
    </script>
</body>
</html>
