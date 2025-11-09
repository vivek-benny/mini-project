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

// Handle WhatsApp message sending - DIRECT REDIRECT APPROACH
if (isset($_POST['send_whatsapp'])) {
    $booking_id = $_POST['booking_id'];
    $message_type = $_POST['message_type'] ?? 'update';
    
    // Get booking details
    $query = "SELECT 
                b.booking_id, b.appointment_date, b.time_slot, b.status,
                r.name as customer_name, r.phonenumber as customer_phone,
                m.name as mechanic_name, m.profession as mechanic_profession, m.phone_number as mechanic_phone,
                v.brand, v.model, v.registration_no
              FROM bookings b
              JOIN register r ON b.user_id = r.user_id
              LEFT JOIN mechanics m ON b.mechanic_id = m.mechanic_id
              JOIN vehicles v ON b.vehicle_id = v.vehicle_id
              WHERE b.booking_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    if ($booking && !empty($booking['customer_phone'])) {
        // Format phone number
        $customer_phone = preg_replace('/[^0-9]/', '', $booking['customer_phone']);
        if (strlen($customer_phone) == 10) {
            $formatted_phone = '91' . $customer_phone;
        } elseif (strlen($customer_phone) == 12 && substr($customer_phone, 0, 2) == '91') {
            $formatted_phone = $customer_phone;
        } else {
            $msg = "❌ Invalid phone number format";
            goto skip_whatsapp;
        }
        
        // Get services
        $services_query = "SELECT s.service_name, bs.service_price 
                          FROM booking_services bs 
                          JOIN services s ON bs.service_id = s.service_id 
                          WHERE bs.booking_id = ?";
        $services_stmt = $conn->prepare($services_query);
        $services_stmt->bind_param("i", $booking_id);
        $services_stmt->execute();
        $services_result = $services_stmt->get_result();
        
        $services_list = "";
        $total_cost = 0;
        while($service = $services_result->fetch_assoc()) {
            $services_list .= "• " . $service['service_name'] . " - ₹" . number_format($service['service_price']) . "\n";
            $total_cost += $service['service_price'];
        }
        
        // Create message
        if ($message_type === 'status_update') {
            $header_message = "🔔 BOOKING STATUS UPDATE";
            $greeting_message = "Hi " . $booking['customer_name'] . "! We have an update for your booking.";
        } else {
            $header_message = "🚗 GARAGE BOOKING CONFIRMATION";
            $greeting_message = "Hello " . $booking['customer_name'] . "! Thank you for choosing GARAGE.";
        }
        
        // Build message
        $whatsapp_message = $header_message . "\n\n";
        $whatsapp_message .= $greeting_message . "\n\n";
        $whatsapp_message .= "📋 BOOKING DETAILS\n";
        $whatsapp_message .= "Booking ID: #" . $booking['booking_id'] . "\n";
        $whatsapp_message .= "Status: " . $booking['status'] . "\n";
        $whatsapp_message .= "Vehicle: " . $booking['brand'] . " " . $booking['model'] . "\n";
        $whatsapp_message .= "Registration: " . $booking['registration_no'] . "\n\n";
        
        if (!empty($services_list)) {
            $whatsapp_message .= "🔧 SERVICES:\n" . $services_list . "\n";
            $whatsapp_message .= "💰 Total Cost: ₹" . number_format($total_cost) . "\n\n";
        }
        
        if ($booking['mechanic_name']) {
            $whatsapp_message .= "👨‍🔧 Mechanic: " . $booking['mechanic_name'] . " (" . $booking['mechanic_profession'] . ")\n\n";
        }
        
        if ($booking['appointment_date'] && $booking['appointment_date'] !== '0000-00-00') {
            $whatsapp_message .= "📅 Appointment: " . date('M d, Y', strtotime($booking['appointment_date'])) . " at " . $booking['time_slot'] . "\n\n";
        }
        
        $whatsapp_message .= "📍 Location: Garage Auto Services, Main Road, Idukki, Kerala\n";
        $whatsapp_message .= "📞 Contact: +91-8590844281\n\n";
        $whatsapp_message .= "Thank you for choosing GARAGE! 🚗";
        
        // Create WhatsApp URL and redirect immediately
        $whatsapp_url = "https://wa.me/" . $formatted_phone . "?text=" . urlencode($whatsapp_message);
        
        // Set success message for after redirect
        $_SESSION['whatsapp_success'] = "✅ WhatsApp message sent successfully to " . $booking['customer_name'];
        
        // Use JavaScript redirect to open in new tab/window
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Opening WhatsApp...</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
                .loading { display: inline-block; margin: 20px 0; }
                .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #25D366; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto; }
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                .message { color: #25D366; font-size: 18px; margin: 20px 0; }
                .back-btn { background: #ff8c42; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='loading'>
                <div class='spinner'></div>
            </div>
            <div class='message'>Opening WhatsApp...</div>
            <p>If WhatsApp doesn't open automatically, <a href='{$whatsapp_url}' target='_blank' style='color: #25D366;'>click here</a></p>
            <a href='bookingview.php' class='back-btn'>← Back to Bookings</a>
            
            <script>
                // Try to open WhatsApp in a new window/tab
                var whatsappWindow = window.open('{$whatsapp_url}', '_blank');
                
                // Redirect back to bookings page after a short delay
                setTimeout(function() {
                    window.location.href = 'bookingview.php?whatsapp_sent=1';
                }, 2000);
                
                // If popup was blocked, redirect immediately
                if (!whatsappWindow) {
                    window.location.href = '{$whatsapp_url}';
                }
            </script>
        </body>
        </html>";
        exit();
    } else {
        $msg = "❌ Customer phone number not found or invalid";
    }
    
    skip_whatsapp:
}

// Check for success message
if (isset($_SESSION['whatsapp_success'])) {
    $msg = $_SESSION['whatsapp_success'];
    unset($_SESSION['whatsapp_success']);
} elseif (isset($_GET['whatsapp_sent'])) {
    $msg = "✅ WhatsApp opened successfully!";
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(r.name LIKE ? OR v.brand LIKE ? OR v.model LIKE ? OR v.registration_no LIKE ? OR b.booking_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
    $param_types .= 'sssss';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get all bookings with pagination
$page = $_GET['page'] ?? 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$query = "SELECT b.*, r.name as customer_name, r.phonenumber as customer_phone, 
                 v.brand, v.model, v.registration_no,
                 m.name as mechanic_name, m.profession as mechanic_profession
          FROM bookings b
          JOIN register r ON b.user_id = r.user_id
          JOIN vehicles v ON b.vehicle_id = v.vehicle_id
          LEFT JOIN mechanics m ON b.mechanic_id = m.mechanic_id
          $where_clause
          ORDER BY b.booking_datetime DESC
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $params[] = $per_page;
    $params[] = $offset;
    $param_types .= 'ii';
    $stmt->bind_param($param_types, ...$params);
} else {
    $stmt->bind_param('ii', $per_page, $offset);
}
$stmt->execute();
$bookings_result = $stmt->get_result();

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM bookings b
                JOIN register r ON b.user_id = r.user_id
                JOIN vehicles v ON b.vehicle_id = v.vehicle_id
                LEFT JOIN mechanics m ON b.mechanic_id = m.mechanic_id
                $where_clause";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_params = array_slice($params, 0, -2); // Remove limit and offset
    $count_param_types = substr($param_types, 0, -2);
    if (!empty($count_params)) {
        $count_stmt->bind_param($count_param_types, ...$count_params);
    }
    $count_stmt->execute();
    $total_bookings = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_bookings = $conn->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_bookings / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garage - All Bookings</title>
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

        .filters {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(255, 140, 66, 0.15);
            border: 1px solid rgba(255, 140, 66, 0.1);
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px;
            align-items: end;
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
        }

        .filter-btn {
            background: linear-gradient(45deg, #ff8c42, #ff7b25);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s ease;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(255, 140, 66, 0.15);
            border: 1px solid rgba(255, 140, 66, 0.1);
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

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            text-transform: capitalize;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-assigned { background: #d1ecf1; color: #0c5460; }
        .status-in-progress { background: #cce5ff; color: #004085; }
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

        .service-payment-status {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 600;
            margin-left: 10px;
            text-transform: uppercase;
        }

        .service-payment-status.payment-paid {
            background: #d4edda;
            color: #155724;
        }

        .service-payment-status.payment-unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        /* Payment Status Section Styles */
        .payment-status-section {
            background: #ffffff;
            border: 1px solid #ffe4d1;
            border-radius: 12px;
            margin: 15px 0;
            overflow: hidden;
        }

        .payment-summary {
            padding: 20px;
        }

        .payment-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .payment-stat {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .payment-stat-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payment-stat-value {
            font-size: 18px;
            font-weight: 700;
        }

        .payment-stat-value.total {
            color: #ff8c42;
        }

        .payment-stat-value.paid {
            color: #2ecc71;
        }

        .payment-stat-value.unpaid {
            color: #e74c3c;
        }

        .payment-progress {
            margin-bottom: 20px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #f1f1f1;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-text {
            text-align: center;
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }

        .payment-status-badge {
            text-align: center;
        }

        .status-fully-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-partially-paid {
            background: #fff3cd;
            color: #856404;
        }

        .status-unpaid {
            background: #f8d7da;
            color: #721c24;
        }

        .no-payment-info {
            padding: 20px;
            text-align: center;
            color: #666;
            font-style: italic;
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

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        /* Feedback Section Styles */
        .feedback-section {
            background: #ffffff;
            border: 1px solid #ffe4d1;
            border-radius: 12px;
            margin: 15px 0;
            overflow: hidden;
        }

        .feedback-summary {
            padding: 20px;
        }

        .feedback-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .rating-stars-display {
            display: flex;
            gap: 3px;
        }

        .star-display {
            font-size: 24px;
            color: #ddd;
        }

        .star-display.filled {
            color: #ff6b35;
        }

        .whatsapp-btn {
            background: linear-gradient(45deg, #25D366, #128C7E);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .whatsapp-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .page-btn {
            background: linear-gradient(45deg, #ff8c42, #ff7b25);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.2s ease;
        }

        .page-btn:hover, .page-btn.active {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
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

            .filter-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .booking-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
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
                    <?php
                    $pending_count = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'Pending'")->fetch_assoc()['count'];
                    if ($pending_count > 0): ?>
                        <span class="nav-badge"><?= $pending_count ?></span>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="bookingview.php" class="nav-link active">
                    <i class="fas fa-list nav-icon"></i>
                    <span class="nav-text">All Bookings</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="replay.php" class="nav-link">
                    <i class="fas fa-envelope nav-icon"></i>
                    <span class="nav-text">Enquires</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="leave.php" class="nav-link">
                    <i class="fas fa-users-cog nav-icon"></i>
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
                            <i class="fas fa-list"></i>
                        </div>
                        <div>
                            <div>All Bookings</div>
                            <div class="breadcrumb">Home > All Bookings</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <form method="GET" class="filter-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-filter"></i>
                            Filter by Status
                        </label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Assigned" <?= $status_filter === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                            <option value="In Progress" <?= $status_filter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-search"></i>
                            Search
                        </label>
                        <input type="text" name="search" class="form-input" placeholder="Search by customer, vehicle, booking ID..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                </form>
            </div>

            <!-- Main Content -->
            <div class="main-content">
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

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="color: #ff8c42; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-calendar-alt"></i>
                        All Bookings (<?= $total_bookings ?> total)
                    </h2>
                </div>

                <?php if ($bookings_result->num_rows > 0): ?>
                    <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                        <?php
                        $booking_id = $booking['booking_id'];
                        
                        // Get services for this booking
                        $services = $conn->query("SELECT bs.service_id, s.service_name, bs.service_price, bs.payment_status 
                                                  FROM booking_services bs 
                                                  JOIN services s ON bs.service_id = s.service_id 
                                                  WHERE bs.booking_id = $booking_id");
                        
                        // Get payment status summary
                        $payment_summary = $conn->query("SELECT 
                                                            COUNT(*) as total_services,
                                                            SUM(CASE WHEN bs.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_services,
                                                            SUM(CASE WHEN bs.payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_services,
                                                            SUM(bs.service_price) as total_amount,
                                                            SUM(CASE WHEN bs.payment_status = 'paid' THEN bs.service_price ELSE 0 END) as paid_amount,
                                                            SUM(CASE WHEN bs.payment_status = 'unpaid' THEN bs.service_price ELSE 0 END) as unpaid_amount
                                                         FROM booking_services bs 
                                                         WHERE bs.booking_id = $booking_id")->fetch_assoc();
                        
                        $total = 0;
                        ?>
                        <div class="booking-card">
                            <div class="booking-header">
                                <div style="display: flex; gap: 15px; align-items: center;">
                                    <div class="booking-id">Booking #<?= $booking_id ?></div>
                                    <div class="status-badge status-<?= strtolower(str_replace(' ', '-', $booking['status'])) ?>">
                                        <?= $booking['status'] ?>
                                    </div>
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
                                            Customer Details
                                        </div>
                                        <div class="info-value">
                                            <strong><?= htmlspecialchars($booking['customer_name']) ?></strong><br>
                                            <small style="color: #666;">
                                                <?php if (!empty($booking['customer_phone'])): ?>
                                                    <i class="fas fa-phone"></i> <?= htmlspecialchars($booking['customer_phone']) ?>
                                                <?php else: ?>
                                                    <i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i> No phone number
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="info-section">
                                        <div class="info-label">
                                            <i class="fas fa-car"></i>
                                            Vehicle Details
                                        </div>
                                        <div class="info-value">
                                            <?= htmlspecialchars($booking['brand'] . ' ' . $booking['model']) ?><br>
                                            <small style="color: #666;"><?= htmlspecialchars($booking['registration_no']) ?></small>
                                        </div>
                                    </div>

                                    <?php if ($booking['mechanic_name']): ?>
                                    <div class="info-section">
                                        <div class="info-label">
                                            <i class="fas fa-user-cog"></i>
                                            Assigned Mechanic
                                        </div>
                                        <div class="info-value">
                                            <strong><?= htmlspecialchars($booking['mechanic_name']) ?></strong><br>
                                            <small style="color: #666;"><?= htmlspecialchars($booking['mechanic_profession']) ?></small>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($booking['appointment_date'] && $booking['appointment_date'] !== '0000-00-00'): ?>
                                    <div class="info-section">
                                        <div class="info-label">
                                            <i class="fas fa-calendar-check"></i>
                                            Appointment
                                        </div>
                                        <div class="info-value">
                                            <strong><?= date('M d, Y', strtotime($booking['appointment_date'])) ?></strong><br>
                                            <small style="color: #666;">at <?= htmlspecialchars($booking['time_slot']) ?></small>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Payment Status Section -->
                                <div class="info-label" style="margin-top: 20px;">
                                    <i class="fas fa-credit-card"></i>
                                    Payment Status
                                </div>
                                <div class="payment-status-section">
                                    <?php if ($payment_summary['total_services'] > 0): ?>
                                        <div class="payment-summary">
                                            <div class="payment-overview">
                                                <div class="payment-stat">
                                                    <div class="payment-stat-label">Total Amount</div>
                                                    <div class="payment-stat-value total">₹<?= number_format($payment_summary['total_amount']) ?></div>
                                                </div>
                                                <div class="payment-stat">
                                                    <div class="payment-stat-label">Paid Amount</div>
                                                    <div class="payment-stat-value paid">₹<?= number_format($payment_summary['paid_amount']) ?></div>
                                                </div>
                                                <div class="payment-stat">
                                                    <div class="payment-stat-label">Unpaid Amount</div>
                                                    <div class="payment-stat-value unpaid">₹<?= number_format($payment_summary['unpaid_amount']) ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="payment-progress">
                                                <?php 
                                                $payment_percentage = $payment_summary['total_amount'] > 0 ? 
                                                    round(($payment_summary['paid_amount'] / $payment_summary['total_amount']) * 100) : 0;
                                                ?>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?= $payment_percentage ?>%"></div>
                                                </div>
                                                <div class="progress-text"><?= $payment_percentage ?>% Paid</div>
                                            </div>
                                            
                                            <div class="payment-status-badge">
                                                <?php if ($payment_summary['unpaid_services'] == 0): ?>
                                                    <span class="status-badge status-fully-paid">
                                                        <i class="fas fa-check-circle"></i> Fully Paid
                                                    </span>
                                                <?php elseif ($payment_summary['paid_services'] > 0): ?>
                                                    <span class="status-badge status-partially-paid">
                                                        <i class="fas fa-clock"></i> Partially Paid
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-badge status-unpaid">
                                                        <i class="fas fa-exclamation-circle"></i> Unpaid
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-payment-info">
                                            <i class="fas fa-info-circle"></i>
                                            No payment information available
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="info-label" style="margin-top: 20px;">
                                    <i class="fas fa-wrench"></i>
                                    Services Booked
                                </div>
                                <div class="services-list">
                                    <?php if ($services->num_rows > 0): ?>
                                        <?php 
                                        // Reset the result pointer
                                        $services->data_seek(0);
                                        while ($svc = $services->fetch_assoc()): 
                                            $total += $svc['service_price']; ?>
                                            <div class="service-item">
                                                <div class="service-name">
                                                    <i class="fas fa-tools"></i>
                                                    <?= htmlspecialchars($svc['service_name']) ?>
                                                    <span class="service-payment-status payment-<?= $svc['payment_status'] ?>">
                                                        <?= $svc['payment_status'] == 'paid' ? 'PAID' : 'UNPAID' ?>
                                                    </span>
                                                </div>
                                                <div class="service-price">₹<?= number_format($svc['service_price']) ?></div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="service-item">
                                            <div class="service-name">No services found</div>
                                            <div class="service-price">₹0</div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Feedback Section -->
                                <?php 
                                // Get feedback for this booking
                                $feedback_query = "SELECT cf.rating, cf.comments, cf.submitted_at, r.name as customer_name 
                                                  FROM customer_feedback cf 
                                                  JOIN register r ON cf.user_id = r.user_id 
                                                  WHERE cf.booking_id = ?";
                                $feedback_stmt = $conn->prepare($feedback_query);
                                $feedback_stmt->bind_param("i", $booking_id);
                                $feedback_stmt->execute();
                                $feedback_result = $feedback_stmt->get_result();
                                
                                if ($feedback_result->num_rows > 0): 
                                    $feedback = $feedback_result->fetch_assoc();
                                ?>
                                <div class="info-label" style="margin-top: 20px;">
                                    <i class="fas fa-star"></i>
                                    Customer Feedback
                                </div>
                                <div class="payment-status-section">
                                    <div class="payment-summary">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                            <div>
                                                <strong>Rating:</strong>
                                                <div style="margin-top: 5px;">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= $feedback['rating']): ?>
                                                            <span style="color: #ff6b35; font-size: 24px;">&#9733;</span>
                                                        <?php else: ?>
                                                            <span style="color: #ddd; font-size: 24px;">&#9733;</span>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                    <span style="margin-left: 10px; font-weight: 600; color: #ff8c42;">(<?= $feedback['rating'] ?>/5)</span>
                                                </div>
                                            </div>
                                            <div style="text-align: right;">
                                                <div style="font-size: 14px; color: #666;">Submitted on</div>
                                                <div style="font-weight: 600;">
                                                    <?= date('M d, Y', strtotime($feedback['submitted_at'])) ?><br>
                                                    <small><?= date('g:i A', strtotime($feedback['submitted_at'])) ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($feedback['comments'])): ?>
                                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ffe4d1;">
                                            <strong>Comments:</strong>
                                            <div style="margin-top: 8px; padding: 12px; background: #f8f9fa; border-radius: 8px; border-left: 3px solid #ff8c42;">
                                                <?= htmlspecialchars($feedback['comments']) ?>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ffe4d1; font-style: italic; color: #666;">
                                            No additional comments provided.
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="total-section">
                                    <div style="font-size: 16px; margin-bottom: 8px;">Total Service Cost</div>
                                    <div style="font-size: 28px; font-weight: 700;">₹<?= number_format($total) ?></div>
                                </div>

                                <?php if (!empty($booking['customer_phone'])): ?>
                                <div class="actions">
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                                        <input type="hidden" name="message_type" value="update">
                                        <button type="submit" name="send_whatsapp" class="whatsapp-btn">
                                            <i class="fab fa-whatsapp"></i>
                                            Send WhatsApp Message
                                        </button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <div style="background: linear-gradient(45deg, #ff6b6b, #ee5a52); color: white; padding: 15px; border-radius: 8px; text-align: center; margin: 20px 0;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Customer phone number missing - WhatsApp message cannot be sent
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page-1 ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>" class="page-btn">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                <a href="?page=<?= $i ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>" 
                                   class="page-btn <?= $i == $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page+1 ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>" class="page-btn">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="no-bookings">
                        <div class="no-bookings-icon">
                            <i class="fas fa-calendar-times"></i>
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

        // Auto-hide success/error messages after 8 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.success-message, .error-message');
            messages.forEach(msg => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            });
        }, 8000);

        // Add smooth scrolling to pagination
        document.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>