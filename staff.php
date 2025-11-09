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

// Get current staff ID from session email
$staff_id = null;
$staff_query = $conn->prepare("SELECT staff_id FROM staff WHERE email = ?");
$staff_query->bind_param("s", $_SESSION['email']);
$staff_query->execute();
$staff_result = $staff_query->get_result();
if ($staff = $staff_result->fetch_assoc()) {
    $staff_id = $staff['staff_id'];
}
$staff_query->close();

// Fetch all mechanics for dropdown with actual availability status
$available_mechanics = [];
$result = $conn->query("SELECT m.mechanic_id, m.name, m.profession FROM mechanics m");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Check if mechanic has any assigned bookings
        $booking_check = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE mechanic_id = ? AND status IN ('Assigned', 'In Progress')");
        $booking_check->bind_param("i", $row['mechanic_id']);
        $booking_check->execute();
        $booking_result = $booking_check->get_result();
        $booking_count = $booking_result->fetch_assoc()['count'];
        
        // Add availability info to mechanic data
        $row['is_available'] = $booking_count == 0;
        $available_mechanics[] = $row;
        $booking_check->close();
    }
}

// Handle update - UPDATED WITHOUT EMAIL FUNCTIONALITY
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $mechanic_id = $_POST['mechanic'];
    $time_slot = $_POST['time_slot'];
    $status = $_POST['status'];
    $appointment_date = $_POST['appointment_date'];

    // Validate appointment date - cannot be in the past
    if (strtotime($appointment_date) < strtotime('today')) {
        $msg = "❌ Error: Appointment date cannot be set to a past date. Please select today or a future date.";
    } else {
        // Check if the selected date already has 5 bookings
        $daily_limit_query = "SELECT COUNT(*) as booking_count FROM bookings WHERE appointment_date = ? AND status IN ('Assigned', 'In Progress', 'Confirmed')";
        $daily_limit_stmt = $conn->prepare($daily_limit_query);
        $daily_limit_stmt->bind_param("s", $appointment_date);
        $daily_limit_stmt->execute();
        $daily_limit_result = $daily_limit_stmt->get_result();
        $daily_count = $daily_limit_result->fetch_assoc()['booking_count'];
        
        if ($daily_count >= 5) {
            $msg = "❌ Error: Maximum booking limit (5) reached for the selected date. Please choose another date.";
        } else {
            // Check for time slot conflicts for the same mechanic on the same date
            $conflict_query = "SELECT COUNT(*) as conflict_count FROM bookings 
                              WHERE appointment_date = ? 
                              AND time_slot = ? 
                              AND mechanic_id = ? 
                              AND booking_id != ? 
                              AND status IN ('Assigned', 'In Progress', 'Confirmed')";
            $conflict_stmt = $conn->prepare($conflict_query);
            $conflict_stmt->bind_param("ssii", $appointment_date, $time_slot, $mechanic_id, $booking_id);
            $conflict_stmt->execute();
            $conflict_result = $conflict_stmt->get_result();
            $conflict_count = $conflict_result->fetch_assoc()['conflict_count'];
            
            if ($conflict_count > 0) {
                $msg = "❌ Error: The selected time slot is already booked for this mechanic on the same date. Please choose another time slot.";
            } else {
                // Update bookings table including mechanic's name and staff_id
                $stmt = $conn->prepare("UPDATE bookings SET status=?, mechanic_id=?, time_slot=?, appointment_date=?, staff_id=? WHERE booking_id=?");
                $stmt->bind_param("sisssi", $status, $mechanic_id, $time_slot, $appointment_date, $staff_id, $booking_id);
                
                if ($stmt->execute()) {
                    $msg = "✅ Booking updated successfully! Appointment scheduled for: " . date('M d, Y', strtotime($appointment_date));
                } else {
                    $msg = "❌ Error updating booking. Please try again.";
                }
                $stmt->close();
            }
            $conflict_stmt->close();
        }
        $daily_limit_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoCare Pro - Staff Dashboard</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(255, 140, 66, 0.15);
            transition: transform 0.3s ease;
            border: 1px solid rgba(255, 140, 66, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(255, 140, 66, 0.2);
        }

        .stat-icon {
            background: linear-gradient(45deg, #ff8c42, #ff7b25);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #ff8c42;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
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
            flex-wrap: wrap;
        }

        .preferred-date-badge {
            background: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 10px;
        }

        .date-validation-info {
            color: #666;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }

        .past-date-warning {
            color: #e74c3c;
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
                <a href="#" class="nav-link active" onclick="showSection('dashboard')">
                    <i class="fas fa-tachometer-alt nav-icon"></i>
                    <span class="nav-text">New bookings</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="booking_edit.php" class="nav-link" onclick="showSection('bookings')">
                    <i class="fas fa-calendar-alt nav-icon"></i>
                    <span class="nav-text">Bookings</span>
                    <?php
                    $pending_count = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'Pending'")->fetch_assoc()['count'];
                    if ($pending_count > 0): ?>
                        <span class="nav-badge"><?= $pending_count ?></span>
                    <?php endif; ?>
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
                    <i class="fas fa-list nav-icon"></i>
                    <span class="nav-text">Enquires</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="leave.php" class="nav-link" onclick="showSection('mechanics')">
                    <i class="fas fa-users-cog nav-icon"></i>
                    <span class="nav-text">leave</span>
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
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <div>
                            <div>Staff Dashboard</div>
                            <div class="breadcrumb">Home > Dashboard</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <?php
                $total_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
                $pending_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'Pending'")->fetch_assoc()['count'];
                $completed_bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'Completed'")->fetch_assoc()['count'];
                $in_progress = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'In Progress'")->fetch_assoc()['count'];
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-number"><?= $total_bookings ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?= $pending_bookings ?></div>
                    <div class="stat-label">Pending Bookings</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="stat-number"><?= $in_progress ?></div>
                    <div class="stat-label">In Progress</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $completed_bookings ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>

            <!-- Main Content Sections -->
            <div class="main-content" id="dashboard-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="section-title">Pending Bookings</div>
                </div>

                <?php if (isset($msg)): ?>
                    <?php if (strpos($msg, '✅') !== false): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
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
                // Fixed: Changed booking_date to booking_datetime
                $result = $conn->query("SELECT * FROM bookings WHERE status = 'Pending' ORDER BY booking_datetime DESC");
                if ($result->num_rows > 0):
                    while ($booking = $result->fetch_assoc()):
                        $booking_id = $booking['booking_id'];

                        // Get user info - Fixed: Using correct field name 'name' from register table
                        $user = $conn->query("SELECT name FROM register WHERE user_id = {$booking['user_id']}")->fetch_assoc();
                        $vehicle = $conn->query("SELECT * FROM vehicles WHERE vehicle_id = {$booking['vehicle_id']}")->fetch_assoc();

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
                                    <div class="info-value"><?= $user['name'] ?? 'N/A' ?></div>
                                </div>

                                <div class="info-section">
                                    <div class="info-label">
                                        <i class="fas fa-car"></i>
                                        Vehicle
                                    </div>
                                    <div class="info-value">
                                        <?= $vehicle['brand'] . ' ' . $vehicle['model'] ?><br>
                                        <small style="color: #999;"><?= $vehicle['registration_no'] ?></small>
                                    </div>
                                </div>

                                <?php if ($booking['prefereddate']): ?>
                                <div class="info-section">
                                    <div class="info-label">
                                        <i class="fas fa-heart"></i>
                                        Customer Preferred Date
                                    </div>
                                    <div class="info-value">
                                        <?= date('M d, Y', strtotime($booking['prefereddate'])) ?><br>
                                        <small style="color: #e67e22;"><?= date('l', strtotime($booking['prefereddate'])) ?></small>
                                    </div>
                                </div>
                                <?php endif; ?>
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
                                    Assign Booking Details
                                </div>
                                
                                <form method="post">
                                    <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-user-cog"></i>
                                                Assign Mechanic
                                            </label>
                                            <select name="mechanic" class="form-select" required>
                                                <option value="" disabled selected>Select Mechanic</option>
                                                <?php foreach ($available_mechanics as $mech): ?>
                                                    <option value="<?= $mech['mechanic_id'] ?>">
                                                        <?= htmlspecialchars($mech['name'] . ' (' . $mech['profession'] . ')') ?>
                                                        <?= $mech['is_available'] ? ' - Free' : ' - Busy' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-clock"></i>
                                                Time Slot
                                            </label>
                                            <input type="time" name="time_slot" class="form-input" required>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-tasks"></i>
                                                Status
                                            </label>
                                            <select name="status" class="form-select" required>
                                                <option value="Assigned">Assigned</option>
                                                <option value="In Progress">In Progress</option>
                                                <option value="Completed">Completed</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="date-section">
                                        <div class="date-section-title">
                                            <i class="fas fa-calendar-check"></i>
                                            Schedule Service Date
                                            <?php if ($booking['prefereddate']): ?>
                                                <small style="background: #17a2b8; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; margin-left: 10px;">
                                                    Customer prefers: <?= date('M d, Y', strtotime($booking['prefereddate'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-calendar-alt"></i>
                                                Appointment Date
                                                <small style="color: #e74c3c;">(Cannot be set to past dates)</small>
                                            </label>
                                            <input 
                                                type="date" 
                                                name="appointment_date" 
                                                class="form-input date-input" 
                                                min="<?= date('Y-m-d') ?>"
                                                <?php if ($booking['prefereddate']): ?>
                                                    value="<?= $booking['prefereddate'] ?>"
                                                <?php endif; ?>
                                                required
                                            >
                                            <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                                                <i class="fas fa-info-circle"></i>
                                                Minimum date: <?= date('M d, Y') ?> (Today)
                                                <?php if ($booking['prefereddate'] && strtotime($booking['prefereddate']) >= strtotime('today')): ?>
                                                    | Pre-filled with customer's preferred date
                                                <?php elseif ($booking['prefereddate']): ?>
                                                    | <span style="color: #e74c3c;">Customer's preferred date has passed</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>

                                    <button type="submit" name="update_status" class="update-btn">
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
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <h3>No Pending Bookings</h3>
                        <p>All bookings have been processed. Great work!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Additional Sections (Hidden by default) -->
            <div class="main-content" id="bookings-section" style="display: none;">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="section-title">All Bookings Management</div>
                </div>
                <div class="no-bookings">
                    <div class="no-bookings-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Bookings Management</h3>
                    <p>This section will contain comprehensive booking management features.</p>
                </div>
            </div>

            <div class="main-content" id="mechanics-section" style="display: none;">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div class="section-title">Mechanics Management</div>
                </div>
                <div class="no-bookings">
                    <div class="no-bookings-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h3>Mechanics Management</h3>
                    <p>Manage mechanic profiles, schedules, and assignments.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Date validation function
        function validateAppointmentDate(dateInput) {
            const selectedDate = new Date(dateInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time to beginning of day
            
            if (selectedDate < today) {
                alert('❌ Error: Appointment date cannot be set to a past date. Please select today or a future date.');
                dateInput.value = ''; // Clear the invalid date
                dateInput.focus();
                return false;
            }
            return true;
        }

        // Add event listeners to all date inputs when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const dateInputs = document.querySelectorAll('input[name="appointment_date"]');
            dateInputs.forEach(function(dateInput) {
                dateInput.addEventListener('change', function() {
                    validateAppointmentDate(this);
                });
                
                dateInput.addEventListener('blur', function() {
                    if (this.value) {
                        validateAppointmentDate(this);
                    }
                });
            });

            // Form submission validation
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    const dateInput = this.querySelector('input[name="appointment_date"]');
                    if (dateInput && !validateAppointmentDate(dateInput)) {
                        e.preventDefault();
                        return false;
                    }
                });
            });
        });

        // Show different sections
        function showSection(sectionName) {
            // Hide all sections
            const sections = ['dashboard', 'bookings', 'mechanics'];
            sections.forEach(section => {
                const element = document.getElementById(section + '-section');
                if (element) {
                    element.style.display = 'none';
                }
            });

            // Show selected section
            const targetSection = document.getElementById(sectionName + '-section');
            if (targetSection) {
                targetSection.style.display = 'block';
            }

            // Update active nav link
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => link.classList.remove('active'));
            event.target.closest('.nav-link').classList.add('active');

            // Update page title
            const pageTitle = document.querySelector('.page-title div div');
            const breadcrumb = document.querySelector('.breadcrumb');
            const pageIcon = document.querySelector('.page-icon i');

            switch(sectionName) {
                case 'dashboard':
                    pageTitle.textContent = 'Staff Dashboard';
                    breadcrumb.textContent = 'Home > Dashboard';
                    pageIcon.className = 'fas fa-tachometer-alt';
                    break;
                case 'bookings':
                    pageTitle.textContent = 'Bookings Management';
                    breadcrumb.textContent = 'Home > Bookings';
                    pageIcon.className = 'fas fa-calendar-alt';
                    break;
                case 'mechanics':
                    pageTitle.textContent = 'Mechanics Management';
                    breadcrumb.textContent = 'Home > Mechanics';
                    pageIcon.className = 'fas fa-users-cog';
                    break;
            }

            // Close sidebar on mobile after selection
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.remove('active');
            }
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
    </script>
</body>
</html>