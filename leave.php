<?php
session_start();

// 1) Database connection
$conn = new mysqli('localhost', 'root', '', 'login');
try {
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Database connection failed.');
}

// 2) Auth check (staff only)
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? '') !== 'staff') {
    header('Location: login.php');
    exit();
}

// 3) Cache headers (reduce back-button resubmit prompts)
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 4) One-time token (created on GET, required on POST)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_SESSION['leave_form_token'])) {
        $_SESSION['leave_form_token'] = bin2hex(random_bytes(16));
    }
}

// 5) Handle POST with PRG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    // CSRF/idempotency token check
    $postedToken = $_POST['form_token'] ?? '';
    $sessionToken = $_SESSION['leave_form_token'] ?? '';
    
    if (!$postedToken || !$sessionToken || !hash_equals($sessionToken, $postedToken)) {
        $_SESSION['flash_error'] = 'Invalid or expired form submission. Please try again.';
        header('Location: leave.php');
        exit();
    }

    // One-time use token
    unset($_SESSION['leave_form_token']);

    // Fetch the current staff_id/name by email
    $staff_email = $_SESSION['email'];
    $stmt = $conn->prepare('SELECT staff_id, staffname FROM staff WHERE email = ?');
    $stmt->bind_param('s', $staff_email);
    $stmt->execute();
    $staff_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$staff_data) {
        $_SESSION['flash_error'] = 'Staff information not found.';
        header('Location: leave.php');
        exit();
    }

    $staff_id = (int)$staff_data['staff_id'];
    $leave_reason = trim((string)($_POST['leave_reason'] ?? ''));
    $leave_days = (int)($_POST['leave_days'] ?? 0);
    $for_when = date('Y-m-d', strtotime('+1 day')); // Always next day's date
    $till_when = '';
    
    // Calculate till_when based on number of days
    if ($leave_days > 0) {
        $till_when = date('Y-m-d', strtotime($for_when . ' +' . ($leave_days - 1) . ' days'));
    }

    // Validations
    if ($leave_reason === '' || $leave_days <= 0) {
        $_SESSION['flash_error'] = 'Please select a leave reason and enter valid number of days.';
        header('Location: leave.php');
        exit();
    }

    if ($leave_days > 30) {
        $_SESSION['flash_error'] = 'Maximum leave days allowed is 30.';
        header('Location: leave.php');
        exit();
    }

    try {
        $stmt = $conn->prepare('INSERT INTO leave_applications (staff_id, leave_reason, for_when, till_when) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isss', $staff_id, $leave_reason, $for_when, $till_when);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash_ok'] = 'Leave application submitted successfully!';
        // PRG redirect (prevents resubmission on refresh)
        header('Location: leave.php?submitted=1');
        exit();

    } catch (mysqli_sql_exception $e) {
        // 1062 = duplicate key (if unique index exists)
        if ((int)$e->getCode() === 1062) {
            $_SESSION['flash_error'] = 'A leave request for these dates already exists.';
        } else {
            $_SESSION['flash_error'] = 'Error submitting leave application. Please try again.';
        }
        header('Location: leave.php');
        exit();
    }
}

// 6) Fetch staffname for greeting
$staff_email = $_SESSION['email'];
$stmt = $conn->prepare('SELECT staffname FROM staff WHERE email = ?');
$stmt->bind_param('s', $staff_email);
$stmt->execute();
$staff_info = $stmt->get_result()->fetch_assoc();
$stmt->close();
$staffname = $staff_info['staffname'] ?? '';

// 7) Fetch recent leave applications for this staff (latest 5)
$stmt = $conn->prepare('
    SELECT l.*, s.staffname 
    FROM leave_applications l 
    JOIN staff s ON l.staff_id = s.staff_id 
    WHERE s.email = ? 
    ORDER BY l.created_at DESC 
    LIMIT 5
');
$stmt->bind_param('s', $staff_email);
$stmt->execute();
$leave_result = $stmt->get_result();
$my_leaves = $leave_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function h($s) { 
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Application - AutoCare Pro</title>
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

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(255, 140, 66, 0.15);
            border: 1px solid rgba(255, 140, 66, 0.1);
            margin-bottom: 30px;
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

        .welcome {
            background: linear-gradient(135deg, #e8f4fd 0%, #d1ecf1 100%);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #2196F3;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.1);
        }

        .welcome h1 {
            color: #ff8c42;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .welcome p {
            color: #2196F3;
            font-weight: 500;
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }

        .alert-success {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
        }

        .alert-error {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
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

        .form-section {
            background: linear-gradient(135deg, #fff8f0 0%, #ffe4d1 100%);
            padding: 25px;
            border-radius: 12px;
            border: 2px solid #ff8c42;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.1);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #ff8c42;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        input[type="text"], input[type="date"], input[type="number"], textarea, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ffe4d1;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 4px rgba(255, 140, 66, 0.05);
        }

        input[type="text"]:focus, input[type="date"]:focus, input[type="number"]:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
            transform: translateY(-1px);
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        .date-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .date-input {
            background: linear-gradient(135deg, #ffffff 0%, #fff8f0 100%);
            border: 2px solid #ffe4d1;
            color: #ff8c42;
            font-weight: 500;
        }

        .btn {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            width: 100%;
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.08);
        }

        th, td {
            border: 1px solid #ffe4d1;
            padding: 15px;
            text-align: left;
        }

        th {
            background: linear-gradient(45deg, #ff8c42, #ff7b25);
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background: #fff8f0;
        }

        .status {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: linear-gradient(45deg, #f39c12, #e67e22);
            color: white;
        }

        .status-approved {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
        }

        .status-rejected {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
        }

        .no-leaves {
            text-align: center;
            padding: 60px 20px;
            color: #ff7b25;
        }

        .no-leaves-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.7;
            color: #ff8c42;
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

            .date-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .dashboard-container {
                padding: 10px;
            }

            .main-content {
                padding: 20px;
            }

            .form-section {
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
                <a href="booking_edit.php" class="nav-link">
                    <i class="fas fa-calendar-alt nav-icon"></i>
                    <span class="nav-text">Bookings</span>
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
                <a href="leave.php" class="nav-link active">
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
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <div>
                            <div>Leave Application</div>
                            <div class="breadcrumb">Home > Leave Management</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="main-content">
                <div class="welcome">
                    <h1><i class="fas fa-user-clock"></i> Leave Application System</h1>
                    <p>Welcome, <strong><?= h($staffname) ?></strong>. Manage your leave requests efficiently.</p>
                </div>

                <?php
                // Display flash messages
                if (isset($_SESSION['flash_ok'])) {
                    echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i>' . h($_SESSION['flash_ok']) . '</div>';
                    unset($_SESSION['flash_ok']);
                }
                if (isset($_SESSION['flash_error'])) {
                    echo '<div class="alert alert-error"><i class="fas fa-times-circle"></i>' . h($_SESSION['flash_error']) . '</div>';
                    unset($_SESSION['flash_error']);
                }
                ?>

                <!-- Leave Application Form -->
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="section-title">Apply for Leave</div>
                </div>

                <div class="form-section">
                    <form method="POST" action="leave.php">
                        <input type="hidden" name="form_token" value="<?= h($_SESSION['leave_form_token'] ?? '') ?>">
                        
                        <div class="form-group">
                            <label for="leave_reason">
                                <i class="fas fa-list-alt"></i>
                                Leave Reason *
                            </label>
                            <select name="leave_reason" id="leave_reason" required>
                                <option value="">Select leave reason...</option>
                                <option value="Sick Leave">Sick Leave</option>
                                <option value="Personal Leave">Personal Leave</option>
                                <option value="Emergency Leave">Emergency Leave</option>
                                <option value="Annual Leave">Annual Leave</option>
                                <option value="Medical Leave">Medical Leave</option>
                                <option value="Family Emergency">Family Emergency</option>
                                <option value="Bereavement Leave">Bereavement Leave</option>
                                <option value="Study Leave">Study Leave</option>
                                <option value="Maternity/Paternity Leave">Maternity/Paternity Leave</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="date-row">
                            <div class="form-group">
                                <label for="from_date_display">
                                    <i class="fas fa-calendar-plus"></i>
                                    From Date
                                </label>
                                <input type="text" id="from_date_display" value="<?= date('M d, Y', strtotime('+1 day')) ?>" readonly class="date-input">
                            </div>

                            <div class="form-group">
                                <label for="leave_days">
                                    <i class="fas fa-calendar-day"></i>
                                    Number of Days *
                                </label>
                                <input type="number" name="leave_days" id="leave_days" min="1" max="30" placeholder="Enter number of days" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="till_date_display">
                                <i class="fas fa-calendar-minus"></i>
                                Till Date
                            </label>
                            <input type="text" id="till_date_display" value="Select number of days" readonly class="date-input">
                        </div>

                        <button type="submit" name="apply_leave" class="btn">
                            <i class="fas fa-paper-plane"></i>
                            Submit Leave Application
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Leave Applications -->
            <div class="main-content">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="section-title">My Recent Leave Applications</div>
                </div>

                <?php if (empty($my_leaves)): ?>
                    <div class="no-leaves">
                        <div class="no-leaves-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3>No Leave Applications</h3>
                        <p>You haven't submitted any leave applications yet.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar"></i> Date Applied</th>
                                <th><i class="fas fa-play"></i> From</th>
                                <th><i class="fas fa-stop"></i> Till</th>
                                <th><i class="fas fa-comment"></i> Reason</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_leaves as $leave): ?>
                                <tr>
                                    <td><?= h(date('M d, Y', strtotime($leave['created_at']))) ?></td>
                                    <td><?= h(date('M d, Y', strtotime($leave['for_when']))) ?></td>
                                    <td><?= h(date('M d, Y', strtotime($leave['till_when']))) ?></td>
                                    <td><?= h($leave['leave_reason']) ?></td>
                                    <td>
                                        <?php 
                                        $status = $leave['status'] ?? 'Pending';
                                        $statusClass = 'status-' . strtolower($status);
                                        ?>
                                        <span class="status <?= h($statusClass) ?>"><?= h($status) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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

        // Calculate till date based on number of days
        document.getElementById('leave_days').addEventListener('input', function() {
            const days = parseInt(this.value);
            const tillDateDisplay = document.getElementById('till_date_display');
            
            if (days > 0 && days <= 30) {
                const fromDate = new Date();
                fromDate.setDate(fromDate.getDate() + 1); // Start from next day
                const tillDate = new Date(fromDate);
                tillDate.setDate(fromDate.getDate() + days - 1);
                
                const options = { year: 'numeric', month: 'short', day: 'numeric' };
                tillDateDisplay.value = tillDate.toLocaleDateString('en-US', options);
            } else if (days > 30) {
                tillDateDisplay.value = 'Maximum 30 days allowed';
            } else {
                tillDateDisplay.value = 'Select number of days';
            }
        });
        
        // Validate number of days
        document.getElementById('leave_days').addEventListener('blur', function() {
            const days = parseInt(this.value);
            if (days > 30) {
                alert('Maximum leave days allowed is 30.');
                this.value = 30;
                this.dispatchEvent(new Event('input'));
            }
        });

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

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>