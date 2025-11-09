<?php
session_start();

// Debug: Log when the page is loaded
error_log("Profile page loaded by user: " . ($_SESSION['email'] ?? 'unknown'));

// Debug: Log any POST data
if (!empty($_POST)) {
    error_log("POST data received: " . print_r($_POST, true));
}

// ✅ Check if user is logged in - Fixed session check
if (!isset($_SESSION['email']) || ($_SESSION['role'] ?? 'user') !== 'user') {
    header("Location: login.php");
    exit();
}

// ✅ Database connection
$conn = new mysqli("localhost", "root", "", "login");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Handle payment confirmation
if (isset($_POST['confirm_payment']) && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    
    // ✅ Verify that this booking belongs to the current user
    $verify_stmt = $conn->prepare("
        SELECT b.booking_id, b.status 
        FROM bookings b 
        JOIN register r ON b.user_id = r.user_id 
        WHERE b.booking_id = ? AND r.email = ?
    ");
    $verify_stmt->bind_param("is", $booking_id, $_SESSION['email']);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update payment status to 'paid' for all services in this booking
        $update_payment = $conn->prepare("UPDATE booking_services SET payment_status = 'paid' WHERE booking_id = ?");
        $update_payment->bind_param("i", $booking_id);
        
        if ($update_payment->execute()) {
            // Redirect to feedback page after successful payment
            header("Location: feedback.php?booking_id=" . $booking_id);
            exit();
        } else {
            echo "<script>alert('Error confirming payment. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('Booking not found or unauthorized access.');</script>";
    }
}

// ✅ Handle booking deletion (from database - only for pending bookings)
if (isset($_POST['delete_booking']) && isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);
    
    // ✅ Verify that this booking belongs to the current user and is pending
    $verify_stmt = $conn->prepare("
        SELECT b.booking_id, b.status 
        FROM bookings b 
        JOIN register r ON b.user_id = r.user_id 
        WHERE b.booking_id = ? AND r.email = ?
    ");
    $verify_stmt->bind_param("is", $booking_id, $_SESSION['email']);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking_data = $result->fetch_assoc();
        
        // ✅ Only allow deletion of pending bookings from database
        if (strtolower($booking_data['status']) === 'pending') {
            // Delete from booking_services first (foreign key constraint)
            $delete_services = $conn->prepare("DELETE FROM booking_services WHERE booking_id = ?");
            $delete_services->bind_param("i", $booking_id);
            $delete_services->execute();
            
            // Then delete the booking
            $delete_booking = $conn->prepare("DELETE FROM bookings WHERE booking_id = ?");
            $delete_booking->bind_param("i", $booking_id);
            
            if ($delete_booking->execute()) {
                echo "<script>alert('Booking cancelled successfully!'); window.location.href='profile.php';</script>";
            } else {
                echo "<script>alert('Error cancelling booking. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('Only pending bookings can be cancelled.');</script>";
        }
    } else {
        echo "<script>alert('Booking not found or unauthorized access.');</script>";
    }
}

// ✅ Handle vehicle deletion
if (isset($_POST['delete_vehicle']) && isset($_POST['vehicle_id'])) {
    error_log("Delete vehicle form submitted");
    $vehicle_id = intval($_POST['vehicle_id']);
    
    // Debug: Log the vehicle ID being deleted
    error_log("Attempting to delete vehicle ID: " . $vehicle_id);
    
    // ✅ Verify that this vehicle belongs to the current user
    $verify_stmt = $conn->prepare("
        SELECT vehicle_id 
        FROM vehicles 
        WHERE vehicle_id = ? AND user_id = (SELECT user_id FROM register WHERE email = ?)
    ");
    $verify_stmt->bind_param("is", $vehicle_id, $_SESSION['email']);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    error_log("Vehicle verification result count: " . $result->num_rows);
    
    if ($result->num_rows > 0) {
        // Delete the vehicle - database will handle cascade deletion of associated bookings
        $delete_vehicle = $conn->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
        $delete_vehicle->bind_param("i", $vehicle_id);
        
        if ($delete_vehicle->execute()) {
            error_log("Vehicle ID " . $vehicle_id . " deleted successfully");
            echo "<script>alert('Vehicle deleted successfully!'); window.location.href='profile.php#vehicles-tab';</script>";
        } else {
            error_log("Error deleting vehicle ID " . $vehicle_id . ": " . $conn->error);
            echo "<script>alert('Error deleting vehicle. Please try again.'); window.location.href='profile.php#vehicles-tab';</script>";
        }
    } else {
        error_log("Vehicle ID " . $vehicle_id . " not found or unauthorized access");
        echo "<script>alert('Vehicle not found or unauthorized access.'); window.location.href='profile.php#vehicles-tab';</script>";
    }
    exit(); // Stop further processing after handling the delete request
}

// ✅ Handle vehicle update with validation
if (isset($_POST['update_vehicle']) && isset($_POST['vehicle_id'])) {
    $vehicle_id = intval($_POST['vehicle_id']);
    $vehicle_type = trim($_POST['vehicle_type']);
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $registration_no = strtoupper(trim($_POST['registration_no']));
    $year = intval($_POST['year']);
    
    // ✅ Verify that this vehicle belongs to the current user
    $verify_stmt = $conn->prepare("
        SELECT vehicle_id 
        FROM vehicles 
        WHERE vehicle_id = ? AND user_id = (SELECT user_id FROM register WHERE email = ?)
    ");
    $verify_stmt->bind_param("is", $vehicle_id, $_SESSION['email']);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Validation
        $errors = [];
        $current_year = date('Y');
        $min_year = $current_year - 30; // 30 years old maximum
        
        // Validate year
        if ($year > $current_year) {
            $errors[] = "Vehicle year cannot be in the future.";
        }
        if ($year < $min_year) {
            $errors[] = "Vehicle cannot be older than 30 years.";
        }
        
        // Validate registration number format (Kerala format)
        if (!preg_match('/^KL\s(0[1-9]|1[0-4])\s[A-Z]{1,2}\s[0-9]{4}$/', $registration_no)) {
            $errors[] = "Invalid registration format. Use: KL XX Y YYYY (XX: 01-14, Y: 1-2 letters, YYYY: 0001-9999)";
        }
        
        // Check if registration number already exists for another vehicle
        $check_reg = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE registration_no = ? AND vehicle_id != ?");
        $check_reg->bind_param("si", $registration_no, $vehicle_id);
        $check_reg->execute();
        $reg_result = $check_reg->get_result();
        
        if ($reg_result->num_rows > 0) {
            $errors[] = "This registration number is already registered with another vehicle.";
        }
        
        // Validate required fields
        if (empty($vehicle_type) || empty($brand) || empty($model) || empty($registration_no) || empty($year)) {
            $errors[] = "All fields are required.";
        }
        
        if (empty($errors)) {
            // Update the vehicle
            $update_vehicle = $conn->prepare("UPDATE vehicles SET vehicle_type = ?, brand = ?, model = ?, registration_no = ?, year = ? WHERE vehicle_id = ?");
            $update_vehicle->bind_param("ssssii", $vehicle_type, $brand, $model, $registration_no, $year, $vehicle_id);
            
            if ($update_vehicle->execute()) {
                echo "<script>alert('Vehicle updated successfully!'); window.location.href='profile.php#vehicles-tab';</script>";
            } else {
                echo "<script>alert('Error updating vehicle. Please try again.'); window.location.href='profile.php#vehicles-tab';</script>";
            }
        } else {
            $error_message = implode("\n", $errors);
            echo "<script>alert('Validation Error:\n" . $error_message . "'); window.location.href='profile.php#vehicles-tab';</script>";
        }
    } else {
        echo "<script>alert('Vehicle not found or unauthorized access.'); window.location.href='profile.php#vehicles-tab';</script>";
    }
}

// ✅ Upload profile picture
if (isset($_POST['upload_picture'])) {
    $target_dir = "uploads/profile_pictures/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . "_" . $_SESSION['email'] . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    $uploadOk = 1;
    
    // ✅ Check if image file is actual image
    $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
    if ($check === false) {
        echo "<script>alert('File is not an image.');</script>";
        $uploadOk = 0;
    }
    
    // ✅ Check file size (5MB limit)
    if ($_FILES["profile_picture"]["size"] > 5000000) {
        echo "<script>alert('File is too large. Maximum size is 5MB.');</script>";
        $uploadOk = 0;
    }
    
    // ✅ Allow certain file formats
    if (!in_array($file_extension, ["jpg", "jpeg", "png", "gif"])) {
        echo "<script>alert('Only JPG, JPEG, PNG & GIF files are allowed.');</script>";
        $uploadOk = 0;
    }
    
    if ($uploadOk) {
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            // Delete old profile picture if it exists
            $old_pic_stmt = $conn->prepare("SELECT profile_picture FROM register WHERE email = ?");
            $old_pic_stmt->bind_param("s", $_SESSION['email']);
            $old_pic_stmt->execute();
            $old_pic_result = $old_pic_stmt->get_result();
            $old_pic_data = $old_pic_result->fetch_assoc();
            
            if ($old_pic_data['profile_picture'] && file_exists($target_dir . $old_pic_data['profile_picture'])) {
                unlink($target_dir . $old_pic_data['profile_picture']);
            }
            
            $stmt = $conn->prepare("UPDATE register SET profile_picture = ? WHERE email = ?");
            $stmt->bind_param("ss", $new_filename, $_SESSION['email']);
            if ($stmt->execute()) {
                echo "<script>alert('Profile picture updated successfully!'); window.location.href='profile.php';</script>";
            } else {
                echo "<script>alert('Error updating profile picture in database.');</script>";
            }
        } else {
            echo "<script>alert('Error uploading file.');</script>";
        }
    }
}

// ✅ Update profile info
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phonenumber = trim($_POST['phonenumber']);
    
    if (!empty($name) && !empty($phonenumber)) {
        // Validate phone number (basic validation)
        if (!preg_match("/^[0-9]{10}$/", $phonenumber)) {
            echo "<script>alert('Please enter a valid 10-digit phone number.');</script>";
        } else {
            $stmt = $conn->prepare("UPDATE register SET name = ?, phonenumber = ? WHERE email = ?");
            $stmt->bind_param("sss", $name, $phonenumber, $_SESSION['email']);
            
            if ($stmt->execute()) {
                // Update session username if it exists
                if (isset($_SESSION['username'])) {
                    $_SESSION['username'] = $name;
                }
                echo "<script>alert('Profile updated successfully!'); window.location.href='profile.php';</script>";
            } else {
                echo "<script>alert('Error updating profile. Please try again.');</script>";
            }
        }
    } else {
        echo "<script>alert('Please fill in all fields.');</script>";
    }
}

// ✅ Change password
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate new password (minimum 6 characters, at least one letter and one number)
    if (strlen($new_password) < 6) {
        echo "<script>alert('New password must be at least 6 characters long.');</script>";
    } elseif (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
        echo "<script>alert('New password must contain at least one letter and one number.');</script>";
    } elseif ($new_password !== $confirm_password) {
        echo "<script>alert('New passwords do not match.');</script>";
    } else {
        // Verify current password using password_verify for hashed passwords
        $verify_stmt = $conn->prepare("SELECT password FROM register WHERE email = ?");
        $verify_stmt->bind_param("s", $_SESSION['email']);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();
        $user_data = $result->fetch_assoc();
        
        if ($user_data && password_verify($current_password, $user_data['password'])) {
            // Hash the new password before storing
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE register SET password = ? WHERE email = ?");
            $update_stmt->bind_param("ss", $hashed_new_password, $_SESSION['email']);
            
            if ($update_stmt->execute()) {
                echo "<script>alert('Password changed successfully!');</script>";
            } else {
                echo "<script>alert('Error changing password. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('Current password is incorrect.');</script>";
        }
    }
}

// ✅ Fetch user data
$stmt = $conn->prepare("SELECT name, email, phonenumber, profile_picture, created_at FROM register WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "<script>alert('User not found. Please login again.'); window.location.href='login.php';</script>";
    exit();
}

$profile_picture = $user['profile_picture'] ?? 'default-avatar.png';

// Display feedback success message if set
if (isset($_SESSION['feedback_success'])) {
    echo "<script>alert('" . $_SESSION['feedback_success'] . "'); window.location.hash = '#bookings-tab';</script>";
    unset($_SESSION['feedback_success']);
}

// ✅ Fetch user's bookings with payment status information
$bookings_stmt = $conn->prepare("
SELECT 
    b.booking_id, 
    b.booking_datetime, 
    b.status, 
    b.time_slot,
    b.appointment_date,
    COALESCE(m.name, 'Not assigned') AS mechanic_name,       -- use mechanics table
    COALESCE(s.staffname, 'Not assigned') AS staff_name,    -- use staff table
    v.vehicle_type, 
    v.brand, 
    v.model, 
    v.registration_no,
    v.year,
    m.phone_number AS mechanic_phone,
    m.profession AS mechanic_profession,
    GROUP_CONCAT(DISTINCT serv.service_name SEPARATOR ', ') AS services,
    COALESCE(SUM(bs.service_price), 0) AS total_price,
    COUNT(DISTINCT bs.service_id) AS service_count,
    GROUP_CONCAT(DISTINCT bs.payment_status SEPARATOR ',') AS payment_statuses,
    COALESCE(SUM(CASE WHEN bs.payment_status = 'paid' THEN bs.service_price ELSE 0 END), 0) AS paid_amount,
    COALESCE(SUM(CASE WHEN bs.payment_status = 'unpaid' THEN bs.service_price ELSE 0 END), 0) AS unpaid_amount
FROM bookings b 
JOIN register r        ON b.user_id    = r.user_id 
LEFT JOIN vehicles v   ON b.vehicle_id = v.vehicle_id 
LEFT JOIN mechanics m  ON b.mechanic_id = m.mechanic_id
LEFT JOIN staff s      ON b.staff_id = s.staff_id           -- join with staff table
LEFT JOIN booking_services bs ON b.booking_id = bs.booking_id 
LEFT JOIN services serv ON bs.service_id = serv.service_id 
WHERE r.email = ?
GROUP BY b.booking_id
ORDER BY b.booking_datetime DESC;

");
$bookings_stmt->bind_param("s", $_SESSION['email']);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
$bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);

// ✅ Fetch user's vehicles
$vehicles_stmt = $conn->prepare("
    SELECT vehicle_id, vehicle_type, brand, model, registration_no, year 
    FROM vehicles 
    WHERE user_id = (SELECT user_id FROM register WHERE email = ?)
    ORDER BY year DESC
");
$vehicles_stmt->bind_param("s", $_SESSION['email']);
$vehicles_stmt->execute();
$vehicles_result = $vehicles_stmt->get_result();
$vehicles = $vehicles_result->fetch_all(MYSQLI_ASSOC);

// ✅ Calculate statistics
$total_bookings = count($bookings);
$pending_bookings = count(array_filter($bookings, function($b) { return strtolower($b['status']) === 'pending'; }));
$completed_bookings = count(array_filter($bookings, function($b) { return strtolower($b['status']) === 'completed'; }));
$assigned_bookings = count(array_filter($bookings, function($b) { return strtolower($b['status']) === 'assigned'; }));
$total_spent = array_sum(array_column($bookings, 'paid_amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - GARAGE</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f7f7f7;
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        .main-title {
            font-size: 2.5rem;
            color: #333;
            margin: 40px auto 15px;
            text-align: center;
        }

        .main-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin: 0 auto 40px;
            text-align: center;
            max-width: 600px;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }

        .profile-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #ff4b2b;
        }

        .profile-info {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 30px;
            align-items: center;
        }

        .profile-picture-container {
            position: relative;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ff4b2b;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .user-details h2 {
            color: #222;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .user-details p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-details p i {
            color: #ff4b2b;
            width: 16px;
        }

        .user-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            text-align: center;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ff4b2b;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #ff4b2b;
        }

        .card h3 {
            color: #222;
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h3 i {
            color: #ff4b2b;
        }

        /* Tabs */
        .tab-container {
            margin-bottom: 30px;
        }

        .tab-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 12px 20px;
            background: #f8f9fa;
            color: #495057;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .tab-button.active {
            background: #ff4b2b;
            color: white;
        }

        .tab-button:hover:not(.active) {
            background: #e9ecef;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #222;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-group input, .form-group select, .form-group textarea {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #ff4b2b;
        }

        /* Buttons */
        .btn {
            background: #ff4b2b;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            background: #e63946;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        /* Booking Cards */
        .booking-card {
            background: #f8f9fa;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .booking-id {
            font-weight: 700;
            color: #222;
            font-size: 1.2rem;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-assigned { background: #cce5ff; color: #0066cc; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .payment-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 10px;
        }

        .payment-paid { background: #d4edda; color: #155724; }
        .payment-unpaid { background: #f8d7da; color: #721c24; }
        .payment-partial { background: #fff3cd; color: #856404; }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-item i {
            color: #ff4b2b;
            width: 16px;
        }

        .booking-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 15px;
        }

        /* Payment Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: none;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .modal-header {
            background: #ff4b2b;
            color: white;
            padding: 25px;
            text-align: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .modal-body {
            padding: 25px;
        }

        .payment-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .payment-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.2rem;
            color: #ff4b2b;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #ff4b2b;
        }

        .garage-info {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 75, 43, 0.1);
            border-radius: 8px;
        }

        .garage-info h3 {
            color: #222;
            margin-bottom: 5px;
        }

        .close {
            color: rgba(255,255,255,0.8);
            float: right;
            font-size: 28px;
            font-weight: bold;
            line-height: 1;
            margin: -10px -10px 0 0;
        }

        .close:hover,
        .close:focus {
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        /* Vehicle Cards */
        .vehicle-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .vehicle-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .vehicle-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .vehicle-detail {
            text-align: center;
        }

        .vehicle-detail strong {
            display: block;
            color: #ff4b2b;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .vehicle-edit-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            margin-top: 5px;
        }

        .vehicle-actions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .edit-mode .vehicle-display {
            display: none;
        }

        .edit-mode .vehicle-edit-input {
            display: block;
        }

        /* No data states */
        .no-bookings, .no-vehicles {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-bookings i, .no-vehicles i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 15px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-title {
                font-size: 2rem;
                padding: 12px 20px;
                margin: 30px auto 10px;
            }

            .main-subtitle {
                font-size: 1rem;
                margin: 0 auto 30px;
            }

            .profile-info {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 20px;
            }
            
            .user-stats {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .booking-details {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 0 15px 30px;
            }

            .tab-buttons {
                flex-direction: column;
            }

            .booking-actions {
                flex-direction: column;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
            
            .profile-header, .card {
                padding: 20px;
            }
            
            .tab-button {
                width: 100%;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .main-title {
                font-size: 1.8rem;
                padding: 10px 15px;
            }
            
            .vehicle-info {
                grid-template-columns: 1fr;
            }
            
            .booking-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">GARAGE</div>
        <button class="menu-toggle" aria-label="Toggle menu">&#9776;</button>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="service.php">Services</a></li>
            <li><a href="booking.php">Booking</a></li>
            <li><a href="contact_staff.php">Contact</a></li>
            <li><a href="profile.php" class="active">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <h1 class="main-title">My Profile</h1>
    <p class="main-subtitle">Manage your account settings and view your bookings</p>

    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-info">
                <div class="profile-picture-container">
                    <img src="<?php echo file_exists("uploads/profile_pictures/" . $profile_picture) ? "uploads/profile_pictures/" . htmlspecialchars($profile_picture) : 'images/default-avatar.png'; ?>" 
                         alt="Profile Picture" class="profile-picture">
                </div>
                <div class="user-details">
                    <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phonenumber'] ?? 'Not provided'); ?></p>
                    <p><i class="fas fa-calendar"></i> Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                </div>
                <div class="user-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_bookings; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $pending_bookings; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $assigned_bookings; ?></div>
                        <div class="stat-label">Assigned</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">₹<?php echo number_format($total_spent, 0); ?></div>
                        <div class="stat-label">Total Paid</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Container -->
        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-button active" onclick="showTab('profile-tab')">
                    <i class="fas fa-user-edit"></i> Profile Settings
                </button>
                <button class="tab-button" onclick="showTab('bookings-tab')">
                    <i class="fas fa-calendar-check"></i> My Bookings
                </button>
                <button class="tab-button" onclick="showTab('vehicles-tab')">
                    <i class="fas fa-car"></i> My Vehicles
                </button>
                <button class="tab-button" onclick="showTab('security-tab')">
                    <i class="fas fa-shield-alt"></i> Security
                </button>
            </div>

            <!-- Profile Settings Tab -->
            <div id="profile-tab" class="tab-content active">
                <div class="card">
                    <h3><i class="fas fa-user-edit"></i> Update Profile Information</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phonenumber" value="<?php echo htmlspecialchars($user['phonenumber'] ?? ''); ?>" pattern="[0-9]{10}" title="Please enter a 10-digit phone number" required>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>

                <div class="card">
                    <h3><i class="fas fa-camera"></i> Update Profile Picture</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Choose Profile Picture (Max 5MB - JPG, PNG, GIF)</label>
                            <input type="file" name="profile_picture" accept="image/*" required>
                        </div>
                        <button type="submit" name="upload_picture" class="btn">
                            <i class="fas fa-upload"></i> Upload Picture
                        </button>
                    </form>
                </div>
            </div>

            <!-- My Bookings Tab -->
            <div id="bookings-tab" class="tab-content">
                <div class="card">
                    <h3><i class="fas fa-calendar-check"></i> My Bookings</h3>
                    
                    <?php if (empty($bookings)): ?>
                        <div class="no-bookings">
                            <i class="fas fa-calendar-times"></i>
                            <h4>No bookings yet</h4>
                            <p>You haven't made any bookings. <a href="booking.php" style="color: #ff4b2b;">Book a service</a> to get started!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <?php
                            // Determine payment status
                            $payment_statuses = explode(',', $booking['payment_statuses']);
                            $unique_statuses = array_unique($payment_statuses);
                            
                            if (count($unique_statuses) == 1 && $unique_statuses[0] == 'paid') {
                                $overall_payment_status = 'paid';
                                $payment_class = 'payment-paid';
                                $payment_text = 'Fully Paid';
                            } elseif (count($unique_statuses) == 1 && $unique_statuses[0] == 'unpaid') {
                                $overall_payment_status = 'unpaid';
                                $payment_class = 'payment-unpaid';
                                $payment_text = 'Unpaid';
                            } else {
                                $overall_payment_status = 'partial';
                                $payment_class = 'payment-partial';
                                $payment_text = 'Partially Paid';
                            }
                            ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <div class="booking-id">Booking #<?php echo htmlspecialchars($booking['booking_id']); ?></div>
                                    <div>
                                        <div class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                            <?php echo htmlspecialchars($booking['status']); ?>
                                        </div>
                                        <div class="payment-badge <?php echo $payment_class; ?>">
                                            <?php echo $payment_text; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="booking-details">
                                    <div class="detail-item">
                                        <i class="fas fa-car"></i>
                                        <span><?php echo htmlspecialchars($booking['vehicle_type'] . ' - ' . $booking['brand'] . ' ' . $booking['model']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-hashtag"></i>
                                        <span><?php echo htmlspecialchars($booking['registration_no']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <span>Appointment: <?php echo $booking['appointment_date'] ? date('M d, Y', strtotime($booking['appointment_date'])) : 'Not scheduled'; ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span>Time: <?php echo htmlspecialchars($booking['time_slot'] ?? 'Not scheduled'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-calendar-plus"></i>
                                        <span>Booked: <?php echo date('M d, Y g:i A', strtotime($booking['booking_datetime'])); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-wrench"></i>
                                        <span><?php echo htmlspecialchars($booking['services'] ?? 'No services'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-user-tie"></i>
                                        <span>Mechanic: <?php 
                                            $mechanic_name = $booking['mechanic_name'] ?? $booking['mechanic_name_direct'] ?? 'Not assigned';
                                            echo htmlspecialchars($mechanic_name);
                                        ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-user-circle"></i>
                                        <span>Assigned Staff: <?php 
                                            $staff_name = $booking['staff_name'] ?? 'Not assigned';
                                            echo htmlspecialchars($staff_name);
                                        ?></span>
                                    </div>
                                    <?php if ($booking['mechanic_phone']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($booking['mechanic_phone']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="detail-item">
                                        <i class="fas fa-dollar-sign"></i>
                                        <span>Total: ₹<?php echo number_format($booking['total_price'] ?? 0, 2); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-credit-card"></i>
                                        <span>Paid: ₹<?php echo number_format($booking['paid_amount'] ?? 0, 2); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>Pending: ₹<?php echo number_format($booking['unpaid_amount'] ?? 0, 2); ?></span>
                                    </div>
                                </div>
                                
                                <div class="booking-actions">
                                    <?php if ($overall_payment_status !== 'paid' && $booking['unpaid_amount'] > 0): ?>
                                        <button onclick="openPaymentModal(<?php echo $booking['booking_id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>', '<?php echo htmlspecialchars($booking['services']); ?>', <?php echo $booking['unpaid_amount']; ?>)" class="btn btn-success btn-small">
                                            <i class="fas fa-credit-card"></i> Pay Now (₹<?php echo number_format($booking['unpaid_amount'], 2); ?>)
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (strtolower($booking['status']) === 'pending'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this booking?')">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                            <button type="submit" name="delete_booking" class="btn btn-danger btn-small">
                                                <i class="fas fa-times"></i> Cancel Booking
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Check if feedback exists for this booking
                                    $feedback_check = $conn->prepare("SELECT feedback_id FROM customer_feedback WHERE booking_id = ?");
                                    $feedback_check->bind_param("i", $booking['booking_id']);
                                    $feedback_check->execute();
                                    $feedback_result = $feedback_check->get_result();
                                    
                                    // Show feedback button only if payment is complete and no feedback exists
                                    if ($overall_payment_status === 'paid' && $feedback_result->num_rows == 0): ?>
                                        <a href="feedback.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-success btn-small">
                                            <i class="fas fa-star"></i> Give Feedback
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- My Vehicles Tab -->
            <div id="vehicles-tab" class="tab-content">
                <div class="card">
                    <h3><i class="fas fa-car"></i> My Vehicles</h3>
                    
                    <?php if (empty($vehicles)): ?>
                        <div class="no-vehicles">
                            <i class="fas fa-car"></i>
                            <h4>No vehicles registered</h4>
                            <p>You haven't registered any vehicles. <a href="booking.php" style="color: #ff4b2b;">Register a vehicle</a> to get started!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <div class="vehicle-card">
                                <div class="vehicle-info">
                                    <div class="vehicle-detail">
                                        <strong>Type</strong>
                                        <span class="vehicle-display" id="type-display-<?php echo $vehicle['vehicle_id']; ?>"><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></span>
                                        <input type="text" class="vehicle-edit-input" id="type-input-<?php echo $vehicle['vehicle_id']; ?>" value="<?php echo htmlspecialchars($vehicle['vehicle_type']); ?>" style="display: none;" required>
                                    </div>
                                    <div class="vehicle-detail">
                                        <strong>Brand</strong>
                                        <span class="vehicle-display" id="brand-display-<?php echo $vehicle['vehicle_id']; ?>"><?php echo htmlspecialchars($vehicle['brand']); ?></span>
                                        <input type="text" class="vehicle-edit-input" id="brand-input-<?php echo $vehicle['vehicle_id']; ?>" value="<?php echo htmlspecialchars($vehicle['brand']); ?>" style="display: none;" required>
                                    </div>
                                    <div class="vehicle-detail">
                                        <strong>Model</strong>
                                        <span class="vehicle-display" id="model-display-<?php echo $vehicle['vehicle_id']; ?>"><?php echo htmlspecialchars($vehicle['model']); ?></span>
                                        <input type="text" class="vehicle-edit-input" id="model-input-<?php echo $vehicle['vehicle_id']; ?>" value="<?php echo htmlspecialchars($vehicle['model']); ?>" style="display: none;" required>
                                    </div>
                                    <div class="vehicle-detail">
                                        <strong>Registration</strong>
                                        <span class="vehicle-display" id="reg-display-<?php echo $vehicle['vehicle_id']; ?>"><?php echo htmlspecialchars($vehicle['registration_no']); ?></span>
                                        <input type="text" class="vehicle-edit-input" id="reg-input-<?php echo $vehicle['vehicle_id']; ?>" value="<?php echo htmlspecialchars($vehicle['registration_no']); ?>" style="display: none;" pattern="^KL\s(0[1-9]|1[0-4])\s[A-Z]{1,2}\s[0-9]{4}$" maxlength="14" required>
                                    </div>
                                    <div class="vehicle-detail">
                                        <strong>Year</strong>
                                        <span class="vehicle-display" id="year-display-<?php echo $vehicle['vehicle_id']; ?>"><?php echo htmlspecialchars($vehicle['year']); ?></span>
                                        <input type="number" class="vehicle-edit-input" id="year-input-<?php echo $vehicle['vehicle_id']; ?>" value="<?php echo htmlspecialchars($vehicle['year']); ?>" min="1900" max="<?php echo date('Y'); ?>" style="display: none;" required>
                                    </div>
                                </div>
                                <div class="vehicle-actions">
                                    <button class="btn btn-secondary btn-small edit-vehicle-btn" data-vehicle-id="<?php echo $vehicle['vehicle_id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-small save-vehicle-btn" data-vehicle-id="<?php echo $vehicle['vehicle_id']; ?>" style="display: none;">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                    <button class="btn btn-secondary btn-small cancel-vehicle-btn" data-vehicle-id="<?php echo $vehicle['vehicle_id']; ?>" style="display: none;">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                    <form id="delete-vehicle-<?php echo $vehicle['vehicle_id']; ?>" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')">
                                        <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['vehicle_id']; ?>">
                                        <button type="submit" name="delete_vehicle" class="btn btn-danger btn-small">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Security Tab -->
            <div id="security-tab" class="tab-content">
                <div class="card">
                    <h3><i class="fas fa-shield-alt"></i> Change Password</h3>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label>New Password (min 6 characters)</label>
                                <input type="password" name="new_password" minlength="6" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" minlength="6" required>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closePaymentModal()">&times;</span>
                <h2><i class="fas fa-credit-card"></i> Confirm Payment</h2>
            </div>
            <div class="modal-body">
                <div class="garage-info">
                    <h3><i class="fas fa-wrench"></i> GARAGE Auto Service Center</h3>
                    <p>Professional Car Service & Maintenance</p>
                </div>
                
                <div class="payment-summary">
                    <h4 style="margin-bottom: 1rem; color: #222;">Payment Details</h4>
                    <div class="payment-row">
                        <span><strong>Customer Name:</strong></span>
                        <span id="customerName"></span>
                    </div>
                    <div class="payment-row">
                        <span><strong>Booking ID:</strong></span>
                        <span id="bookingIdDisplay"></span>
                    </div>
                    <div class="payment-row">
                        <span><strong>Services:</strong></span>
                        <span id="servicesList"></span>
                    </div>
                    <div class="payment-row">
                        <span><strong>Amount to Pay:</strong></span>
                        <span id="totalAmount"></span>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <form method="POST" id="paymentForm" style="display: inline;">
                        <input type="hidden" name="booking_id" id="paymentBookingId">
                        <button type="submit" name="confirm_payment" class="btn btn-success" style="font-size: 1.2rem; padding: 1rem 2rem;">
                            <i class="fas fa-check-circle"></i> Confirm Payment
                        </button>
                    </form>
                    <button onclick="closePaymentModal()" class="btn btn-secondary" style="font-size: 1.2rem; padding: 1rem 2rem; margin-left: 1rem;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
                
                <div style="margin-top: 1.5rem; padding: 1rem; background: #e7f3ff; border-radius: 8px; border-left: 4px solid #0066cc;">
                    <p style="margin: 0; color: #0066cc; font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Note:</strong> This is a demo payment system. In production, integrate with actual payment gateways like Razorpay, Stripe, or PayPal.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.querySelector('.menu-toggle');
            const navLinks = document.querySelector('nav ul');
            
            if (menuToggle && navLinks) {
                menuToggle.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                });
            }
        });

        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }

        function openPaymentModal(bookingId, customerName, services, amount) {
            document.getElementById('customerName').textContent = customerName;
            document.getElementById('bookingIdDisplay').textContent = '#' + bookingId;
            document.getElementById('servicesList').textContent = services;
            document.getElementById('totalAmount').textContent = '₹' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2});
            document.getElementById('paymentBookingId').value = bookingId;
            document.getElementById('paymentModal').style.display = 'block';
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }

        // Vehicle edit functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Edit button click handler
            document.querySelectorAll('.edit-vehicle-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const vehicleId = this.getAttribute('data-vehicle-id');
                    enableEdit(vehicleId);
                });
            });

            // Save button click handler
            document.querySelectorAll('.save-vehicle-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const vehicleId = this.getAttribute('data-vehicle-id');
                    saveVehicle(vehicleId);
                });
            });

            // Cancel button click handler
            document.querySelectorAll('.cancel-vehicle-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const vehicleId = this.getAttribute('data-vehicle-id');
                    cancelEdit(vehicleId);
                });
            });

            // Also handle Enter key in input fields to save
            document.querySelectorAll('.vehicle-edit-input').forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        const vehicleId = this.id.split('-').pop();
                        saveVehicle(vehicleId);
                    }
                });
            });
            
            // Add real-time validation for registration number
            document.querySelectorAll('[id^="reg-input-"]').forEach(input => {
                input.addEventListener('input', function(e) {
                    const cursorPosition = e.target.selectionStart;
                    let value = e.target.value.toUpperCase();
                    let newValue = '';
                    let newCursorPosition = cursorPosition;
                    
                    // Remove any characters that are not allowed
                    value = value.replace(/[^A-Z0-9\s]/g, '');
                    
                    // Process character by character according to KL XX Y YYYY format
                    for (let i = 0; i < value.length && newValue.length < 14; i++) { // Max length: "KL XX YZ YYYY" = 13 chars
                        const char = value[i];
                        const currentLength = newValue.length;
                        
                        // Position 0-1: Must be 'KL'
                        if (currentLength === 0) {
                            if (char === 'K') {
                                newValue += char;
                            } else if (char === 'L' && newValue === '') {
                                newValue = 'KL'; // Auto-complete KL if user types L first
                                newCursorPosition++;
                            } else if (char === ' ') {
                                newValue = 'KL '; // Auto-complete KL if user types space first
                                newCursorPosition += 2;
                            } else if (/[0-9]/.test(char)) {
                                newValue = 'KL ' + char; // Auto-complete KL if user types number first
                                newCursorPosition += 3;
                            }
                        } else if (currentLength === 1) {
                            if (char === 'L') {
                                newValue += char;
                            } else if (char === ' ') {
                                newValue += 'L '; // Auto-complete L if user types space
                                newCursorPosition++;
                            } else if (/[0-9]/.test(char)) {
                                newValue += 'L ' + char; // Auto-complete L and add space if user types number
                                newCursorPosition += 2;
                            }
                        }
                        // Position 2: Must be space
                        else if (currentLength === 2) {
                            if (char === ' ') {
                                newValue += char;
                            } else if (/[0-9]/.test(char)) {
                                newValue += ' ' + char; // Auto-add space if user types number
                                newCursorPosition++;
                            }
                        }
                        // Position 3-4: Must be numbers (district code 01-14)
                        else if (currentLength >= 3 && currentLength <= 4) {
                            if (/[0-9]/.test(char)) {
                                // Validate district code as we build it
                                const currentDistrict = newValue.substring(3) + char;
                                if (currentLength === 3) {
                                    // First digit: must be 0 or 1
                                    if (char === '0' || char === '1') {
                                        newValue += char;
                                    }
                                } else if (currentLength === 4) {
                                    // Second digit: validate complete district code
                                    const districtCode = parseInt(currentDistrict);
                                    if (districtCode >= 1 && districtCode <= 14) {
                                        newValue += char;
                                    }
                                }
                            } else if (char === ' ' && currentLength === 4) {
                                newValue += char;
                            } else if (/[A-Z]/.test(char) && currentLength === 4) {
                                newValue += ' ' + char; // Auto-add space if user types letter after district code
                                newCursorPosition++;
                            }
                        }
                        // Position 5: Must be space
                        else if (currentLength === 5) {
                            if (char === ' ') {
                                newValue += char;
                            } else if (/[A-Z]/.test(char)) {
                                newValue += ' ' + char; // Auto-add space if user types letter
                                newCursorPosition++;
                            }
                        }
                        // Position 6-7: Must be letters (series code, 1-2 letters)
                        else if (currentLength >= 6 && currentLength <= 7) {
                            if (/[A-Z]/.test(char)) {
                                newValue += char;
                            } else if (char === ' ') {
                                newValue += char;
                            } else if (/[0-9]/.test(char)) {
                                newValue += ' ' + char; // Auto-add space if user types number after series
                                newCursorPosition++;
                            }
                        }
                        // Handle space or number after series (flexible for 1 or 2 letter series)
                        else if (currentLength === 8) {
                            if (char === ' ') {
                                newValue += char;
                            } else if (/[0-9]/.test(char)) {
                                // Check if position 7 is a letter (2-letter series) or space (1-letter series)
                                if (/[A-Z]/.test(newValue.charAt(7))) {
                                    // This is a 2-letter series, add space before number
                                    newValue += ' ' + char;
                                    newCursorPosition++;
                                } else {
                                    // This is continuation after 1-letter series + space
                                    newValue += char;
                                }
                            }
                        }
                        // Handle final digits (registration number)
                        else if (currentLength >= 9) {
                            if (/[0-9]/.test(char)) {
                                // Count existing digits in final section to ensure max 4 digits
                                const parts = newValue.split(' ');
                                const finalSection = parts[3] || '';
                                if (finalSection.length < 4) {
                                    newValue += char;
                                }
                            }
                        }
                    }
                    
                    e.target.value = newValue;
                    
                    // Restore cursor position, but not beyond the new value length
                    const finalPosition = Math.min(newCursorPosition, newValue.length);
                    setTimeout(() => {
                        e.target.setSelectionRange(finalPosition, finalPosition);
                    }, 0);
                });
                
                // Add blur event for validation feedback
                input.addEventListener('blur', function(e) {
                    const regPattern = /^KL\s(0[1-9]|1[0-4])\s[A-Z]{1,2}\s\d{4}$/;
                    const value = e.target.value.trim();
                    
                    if (value && !regPattern.test(value)) {
                        e.target.setCustomValidity('Invalid format. Use: KL XX Y YYYY (XX: 01-14, Y: 1-2 letters, YYYY: 0001-9999)');
                    } else {
                        e.target.setCustomValidity('');
                    }
                });
                
                // Clear custom validity on focus
                input.addEventListener('focus', function(e) {
                    e.target.setCustomValidity('');
                });
            });
            
            // Add real-time validation for year
            document.querySelectorAll('[id^="year-input-"]').forEach(input => {
                input.addEventListener('input', function(e) {
                    const currentYear = new Date().getFullYear();
                    const minYear = currentYear - 30;
                    const year = parseInt(e.target.value);
                    
                    // Remove any existing custom validity
                    e.target.setCustomValidity('');
                    
                    if (e.target.value && !isNaN(year)) {
                        if (year > currentYear) {
                            e.target.setCustomValidity('Year cannot be in the future (current year: ' + currentYear + ')');
                        } else if (year < minYear && year > 1900) {
                            e.target.setCustomValidity('Vehicle cannot be older than 30 years (minimum year: ' + minYear + ')');
                        }
                    }
                });
                
                // Clear custom validity on focus
                input.addEventListener('focus', function(e) {
                    e.target.setCustomValidity('');
                });
            });
        });

        function enableEdit(vehicleId) {
            // Hide display elements and show input fields
            document.getElementById('type-display-' + vehicleId).style.display = 'none';
            document.getElementById('type-input-' + vehicleId).style.display = 'block';
            
            document.getElementById('brand-display-' + vehicleId).style.display = 'none';
            document.getElementById('brand-input-' + vehicleId).style.display = 'block';
            
            document.getElementById('model-display-' + vehicleId).style.display = 'none';
            document.getElementById('model-input-' + vehicleId).style.display = 'block';
            
            document.getElementById('reg-display-' + vehicleId).style.display = 'none';
            document.getElementById('reg-input-' + vehicleId).style.display = 'block';
            
            document.getElementById('year-display-' + vehicleId).style.display = 'none';
            document.getElementById('year-input-' + vehicleId).style.display = 'block';
            
            // Hide edit button and show save/cancel buttons
            document.querySelector('.edit-vehicle-btn[data-vehicle-id="' + vehicleId + '"]').style.display = 'none';
            document.querySelector('.save-vehicle-btn[data-vehicle-id="' + vehicleId + '"]').style.display = 'inline-block';
            document.querySelector('.cancel-vehicle-btn[data-vehicle-id="' + vehicleId + '"]').style.display = 'inline-block';
        }

        function saveVehicle(vehicleId) {
            // Get values from input fields
            const vehicleType = document.getElementById('type-input-' + vehicleId).value.trim();
            const brand = document.getElementById('brand-input-' + vehicleId).value.trim();
            const model = document.getElementById('model-input-' + vehicleId).value.trim();
            const regNo = document.getElementById('reg-input-' + vehicleId).value.trim().toUpperCase();
            const year = parseInt(document.getElementById('year-input-' + vehicleId).value.trim());
            
            // Basic validation
            if (!vehicleType || !brand || !model || !regNo || !year) {
                alert('GARAGE Service Alert:\n\nPlease fill in all fields.');
                return;
            }
            
            // Year validation
            const currentYear = new Date().getFullYear();
            const minYear = currentYear - 30;
            
            if (isNaN(year) || year < 1900) {
                alert('GARAGE Service Alert:\n\nPlease enter a valid year.');
                return;
            }
            
            if (year > currentYear) {
                alert('GARAGE Service Alert:\n\nVehicle year cannot be in the future!\n\nCurrent year: ' + currentYear + '\nPlease enter a year between ' + minYear + ' and ' + currentYear);
                return;
            }
            
            if (year < minYear) {
                alert('GARAGE Service Alert:\n\nVehicle is too old for our service!\n\nWe service vehicles that are maximum 30 years old.\nMinimum year accepted: ' + minYear + '\nPlease enter a year between ' + minYear + ' and ' + currentYear);
                return;
            }
            
            // Registration number validation (Kerala format)
            const regPattern = /^KL\s(0[1-9]|1[0-4])\s[A-Z]{1,2}\s\d{4}$/;
            
            if (!regPattern.test(regNo)) {
                alert('GARAGE Service Alert:\n\nInvalid registration format!\n\nCorrect format: KL XX Y YYYY\n- KL: Kerala state code (fixed)\n- XX: District code (01 to 14 only)\n- Y: Series code (1-2 letters A-Z)\n- YYYY: Four digits (0001-9999)\n\nExample: KL 01 A 1234');
                return;
            }
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const vehicleIdInput = document.createElement('input');
            vehicleIdInput.type = 'hidden';
            vehicleIdInput.name = 'vehicle_id';
            vehicleIdInput.value = vehicleId;
            form.appendChild(vehicleIdInput);
            
            const typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'vehicle_type';
            typeInput.value = vehicleType;
            form.appendChild(typeInput);
            
            const brandInput = document.createElement('input');
            brandInput.type = 'hidden';
            brandInput.name = 'brand';
            brandInput.value = brand;
            form.appendChild(brandInput);
            
            const modelInput = document.createElement('input');
            modelInput.type = 'hidden';
            modelInput.name = 'model';
            modelInput.value = model;
            form.appendChild(modelInput);
            
            const regInput = document.createElement('input');
            regInput.type = 'hidden';
            regInput.name = 'registration_no';
            regInput.value = regNo;
            form.appendChild(regInput);
            
            const yearInput = document.createElement('input');
            yearInput.type = 'hidden';
            yearInput.name = 'year';
            yearInput.value = year;
            form.appendChild(yearInput);
            
            const updateInput = document.createElement('input');
            updateInput.type = 'hidden';
            updateInput.name = 'update_vehicle';
            updateInput.value = '1';
            form.appendChild(updateInput);
            
            document.body.appendChild(form);
            form.submit();
        }

        function cancelEdit(vehicleId) {
            // Reset input fields to original values
            document.getElementById('type-input-' + vehicleId).value = document.getElementById('type-display-' + vehicleId).textContent;
            document.getElementById('brand-input-' + vehicleId).value = document.getElementById('brand-display-' + vehicleId).textContent;
            document.getElementById('model-input-' + vehicleId).value = document.getElementById('model-display-' + vehicleId).textContent;
            document.getElementById('reg-input-' + vehicleId).value = document.getElementById('reg-display-' + vehicleId).textContent;
            document.getElementById('year-input-' + vehicleId).value = document.getElementById('year-display-' + vehicleId).textContent;
            
            // Hide input fields and show display elements
            document.getElementById('type-input-' + vehicleId).style.display = 'none';
            document.getElementById('type-display-' + vehicleId).style.display = 'block';
            
            document.getElementById('brand-input-' + vehicleId).style.display = 'none';
            document.getElementById('brand-display-' + vehicleId).style.display = 'block';
            
            document.getElementById('model-input-' + vehicleId).style.display = 'none';
            document.getElementById('model-display-' + vehicleId).style.display = 'block';
            
            document.getElementById('reg-input-' + vehicleId).style.display = 'none';
            document.getElementById('reg-display-' + vehicleId).style.display = 'block';
            
            document.getElementById('year-input-' + vehicleId).style.display = 'none';
            document.getElementById('year-display-' + vehicleId).style.display = 'block';
            
            // Hide save/cancel buttons and show edit button
            document.querySelector('.edit-vehicle-btn[data-vehicle-id="' + vehicleId + '"]').style.display = 'inline-block';
            document.querySelector('.save-vehicle-btn[data-vehicle-id="' + vehicleId + '"]').style.display = 'none';
            document.querySelector('.cancel-vehicle-btn[data-vehicle-id="' + vehicleId + '"]').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('paymentModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Password confirmation validation
        document.querySelector('input[name="confirm_password"]').addEventListener('input', function() {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // New password validation (minimum 6 characters, at least one letter and one number)
        const newPasswordField = document.querySelector('input[name="new_password"]');
        if (newPasswordField) {
            newPasswordField.addEventListener('input', function() {
                const password = this.value;
                
                if (password.length < 6) {
                    this.setCustomValidity('Password must be at least 6 characters long');
                } else if (!/[A-Za-z]/.test(password) || !/[0-9]/.test(password)) {
                    this.setCustomValidity('Password must contain at least one letter and one number');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>