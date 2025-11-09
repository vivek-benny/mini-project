<?php
// settingsadmin.php - Admin Settings Page
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

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/admin/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Get current admin details
$current_admin_email = $_SESSION['email'];
$admin_query = "SELECT * FROM admins WHERE email = ?";
$admin_stmt = $conn->prepare($admin_query);
$admin_stmt->bind_param("s", $current_admin_email);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
$current_admin = $admin_result->fetch_assoc();

// Check if admin was found
if (!$current_admin) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Handle POST actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            $username = $conn->real_escape_string($_POST['username']);
            $email = $conn->real_escape_string($_POST['email']);
            $admin_id = intval($_POST['admin_id']);
            
            // Validate username (only letters and spaces)
            if (!preg_match("/^[a-zA-Z\s]+$/", $username)) {
                $message = "Username should only contain letters and spaces!";
                $messageType = 'error';
            }
            // Validate email
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "Please enter a valid email address!";
                $messageType = 'error';
            } else {
                // Check if email already exists (excluding current admin)
                $check_email_query = "SELECT admin_id FROM admins WHERE email = ? AND admin_id != ?";
                $check_stmt = $conn->prepare($check_email_query);
                $check_stmt->bind_param("si", $email, $admin_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $message = "Email already exists!";
                    $messageType = 'error';
                } else {
                    $update_query = "UPDATE admins SET username = ?, email = ? WHERE admin_id = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("ssi", $username, $email, $admin_id);
                    
                    if ($update_stmt->execute()) {
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        $message = "Profile updated successfully!";
                        $messageType = 'success';
                        
                        // Refresh current admin data
                        $admin_stmt->execute();
                        $admin_result = $admin_stmt->get_result();
                        $current_admin = $admin_result->fetch_assoc();
                    } else {
                        $message = "Error updating profile: " . $conn->error;
                        $messageType = 'error';
                    }
                }
            }
            break;
            
        case 'change_password':
            $admin_id = intval($_POST['admin_id']);
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Verify current password
            if (($current_admin['password'] ?? '') !== $current_password) {
                $message = "Current password is incorrect!";
                $messageType = 'error';
            } elseif ($new_password !== $confirm_password) {
                $message = "New passwords do not match!";
                $messageType = 'error';
            } elseif (strlen($new_password) < 6) {
                $message = "New password must be at least 6 characters long!";
                $messageType = 'error';
            } else {
                $update_password_query = "UPDATE admins SET password = ? WHERE admin_id = ?";
                $password_stmt = $conn->prepare($update_password_query);
                $password_stmt->bind_param("si", $new_password, $admin_id);
                
                if ($password_stmt->execute()) {
                    $message = "Password changed successfully!";
                    $messageType = 'success';
                    
                    // Refresh current admin data
                    $admin_stmt->execute();
                    $admin_result = $admin_stmt->get_result();
                    $current_admin = $admin_result->fetch_assoc();
                } else {
                    $message = "Error changing password: " . $conn->error;
                    $messageType = 'error';
                }
            }
            break;
            
        case 'upload_profile_picture':
            $admin_id = intval($_POST['admin_id']);
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = $_FILES['profile_picture']['type'];
                $file_size = $_FILES['profile_picture']['size'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file_type, $allowed_types)) {
                    $message = "Error: Only JPEG, PNG, GIF, and WebP images are allowed.";
                    $messageType = 'error';
                } elseif ($file_size > $max_size) {
                    $message = "Error: File size must be less than 5MB.";
                    $messageType = 'error';
                } else {
                    // Get current profile picture to delete it
                    if (!empty($current_admin['profile_picture']) && file_exists($current_admin['profile_picture'])) {
                        unlink($current_admin['profile_picture']);
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                        $update_image_query = "UPDATE admins SET profile_picture = ? WHERE admin_id = ?";
                        $image_stmt = $conn->prepare($update_image_query);
                        $image_stmt->bind_param("si", $upload_path, $admin_id);
                        
                        if ($image_stmt->execute()) {
                            $message = "Profile picture updated successfully!";
                            $messageType = 'success';
                            
                            // Refresh current admin data
                            $admin_stmt->execute();
                            $admin_result = $admin_stmt->get_result();
                            $current_admin = $admin_result->fetch_assoc();
                        } else {
                            $message = "Error updating database: " . $conn->error;
                            $messageType = 'error';
                            unlink($upload_path);
                        }
                    } else {
                        $message = "Error uploading image.";
                        $messageType = 'error';
                    }
                }
            } else {
                $message = "Error: Please select an image file.";
                $messageType = 'error';
            }
            break;
            
        case 'remove_profile_picture':
            $admin_id = intval($_POST['admin_id']);
            
            // Get the image path
            if (!empty($current_admin['profile_picture']) && file_exists($current_admin['profile_picture'])) {
                unlink($current_admin['profile_picture']);
            }
            
            $remove_image_query = "UPDATE admins SET profile_picture = NULL WHERE admin_id = ?";
            $remove_stmt = $conn->prepare($remove_image_query);
            $remove_stmt->bind_param("i", $admin_id);
            
            if ($remove_stmt->execute()) {
                $message = "Profile picture removed successfully!";
                $messageType = 'success';
                
                // Refresh current admin data
                $admin_stmt->execute();
                $admin_result = $admin_stmt->get_result();
                $current_admin = $admin_result->fetch_assoc();
            } else {
                $message = "Error removing profile picture: " . $conn->error;
                $messageType = 'error';
            }
            break;
            
        case 'add_admin':
            $username = $conn->real_escape_string($_POST['new_username']);
            $email = $conn->real_escape_string($_POST['new_email']);
            $password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_new_password'];
            
            // Validate username (only letters and spaces)
            if (!preg_match("/^[a-zA-Z\s]+$/", $username)) {
                $message = "Username should only contain letters and spaces!";
                $messageType = 'error';
            }
            // Validate email
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = "Please enter a valid email address!";
                $messageType = 'error';
            }
            // Validate password
            elseif ($password !== $confirm_password) {
                $message = "Passwords do not match!";
                $messageType = 'error';
            } elseif (strlen($password) < 6) {
                $message = "Password must be at least 6 characters long!";
                $messageType = 'error';
            } else {
                // Check if email already exists
                $check_email_query = "SELECT admin_id FROM admins WHERE email = ?";
                $check_stmt = $conn->prepare($check_email_query);
                $check_stmt->bind_param("s", $email);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $message = "Email already exists!";
                    $messageType = 'error';
                } else {
                    $insert_admin_query = "INSERT INTO admins (username, email, password, role) VALUES (?, ?, ?, 'admin')";
                    $insert_stmt = $conn->prepare($insert_admin_query);
                    $insert_stmt->bind_param("sss", $username, $email, $password);
                    
                    if ($insert_stmt->execute()) {
                        $message = "New admin added successfully!";
                        $messageType = 'success';
                    } else {
                        $message = "Error adding admin: " . $conn->error;
                        $messageType = 'error';
                    }
                }
            }
            break;
            
        case 'delete_admin':
            $admin_id = intval($_POST['delete_admin_id']);
            
            // Prevent deleting current admin
            if ($admin_id == ($current_admin['admin_id'] ?? 0)) {
                $message = "You cannot delete your own account!";
                $messageType = 'error';
            } else {
                // Get profile picture to delete
                $delete_admin_query = "SELECT profile_picture FROM admins WHERE admin_id = ?";
                $delete_stmt = $conn->prepare($delete_admin_query);
                $delete_stmt->bind_param("i", $admin_id);
                $delete_stmt->execute();
                $delete_result = $delete_stmt->get_result();
                $admin_to_delete = $delete_result->fetch_assoc();
                
                if ($admin_to_delete && !empty($admin_to_delete['profile_picture']) && file_exists($admin_to_delete['profile_picture'])) {
                    unlink($admin_to_delete['profile_picture']);
                }
                
                $delete_query = "DELETE FROM admins WHERE admin_id = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("i", $admin_id);
                
                if ($delete_stmt->execute()) {
                    $message = "Admin deleted successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error deleting admin: " . $conn->error;
                    $messageType = 'error';
                }
            }
            break;
    }
}

// Fetch all admins
$all_admins_query = "SELECT * FROM admins ORDER BY username";
$all_admins_result = $conn->query($all_admins_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Settings - Admin Dashboard</title>
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
        
        /* Tab Navigation */
        .tab-navigation {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .tab-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .tab-button {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #e9ecef;
            color: #495057;
        }
        
        .tab-button.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .tab-button:hover:not(.active) {
            background: #dee2e6;
            transform: translateY(-2px);
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 0.85rem;
        }
        
        /* Profile Section */
        .profile-section {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 15px;
            border-left: 4px solid #667eea;
        }
        
        .profile-picture {
            position: relative;
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #667eea;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .no-profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid #667eea;
            color: #6c757d;
            font-size: 2rem;
        }
        
        .profile-info h3 {
            color: #2c3e50;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .profile-info p {
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .profile-info .role-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            margin-top: 10px;
        }
        
        /* Admin Grid */
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .admin-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .admin-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
        }
        
        .admin-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .admin-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
        }
        
        .no-admin-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #667eea;
            color: #6c757d;
        }
        
        .admin-info h4 {
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .admin-info p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        /* File Input Styles */
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            padding: 12px 25px;
            background: #e9ecef;
            color: #495057;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .file-input-label:hover {
            background: #dee2e6;
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
            .admin-grid {
                grid-template-columns: 1fr;
            }
            .profile-section {
                flex-direction: column;
                text-align: center;
            }
            .tab-buttons {
                flex-direction: column;
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
                    <a href="settingsadmin.php" class="nav-link active">
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
                <h1>Settings</h1>
            </div>
        </div>

        <!-- Message Alert -->
        <?php if ($message): ?>
            <div class="alert <?php echo ($messageType === 'success') ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <div class="tab-buttons">
                <button class="tab-button active" data-tab="profile-tab">My Profile</button>
                <button class="tab-button" data-tab="security-tab">Security</button>
                <button class="tab-button" data-tab="admins-tab">Manage Admins</button>
            </div>
        </div>

        <!-- Tab Content: My Profile -->
        <div class="tab-content active" id="profile-tab">
            <!-- Current Profile Display -->
            <div class="content-card">
                <div class="profile-section">
                    <div class="profile-picture">
                        <?php if (!empty($current_admin['profile_picture']) && file_exists($current_admin['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($current_admin['profile_picture']); ?>" alt="Profile Picture" class="profile-img" />
                        <?php else: ?>
                            <div class="no-profile-img">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h3><?php echo htmlspecialchars($current_admin['username'] ?? 'Unknown'); ?></h3>
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($current_admin['email'] ?? ''); ?></p>
                        <p><i class="fas fa-calendar"></i> Admin since: Always</p>
                        <span class="role-badge"><?php echo htmlspecialchars(strtoupper($current_admin['role'] ?? 'ADMIN')); ?></span>
                    </div>
                </div>
            </div>

            <!-- Update Profile Form -->
            <div class="content-card">
                <h2>Update Profile Information</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile" />
                    <input type="hidden" name="admin_id" value="<?php echo ($current_admin['admin_id'] ?? 0); ?>" />
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($current_admin['username'] ?? ''); ?>" required pattern="[a-zA-Z\s]+" title="Username should only contain letters and spaces" />
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($current_admin['email'] ?? ''); ?>" required />
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <!-- Profile Picture Management -->
            <div class="content-card">
                <h2>Profile Picture</h2>
                <p style="margin-bottom: 20px; color: #6c757d;">Upload a profile picture. Supported formats: JPEG, PNG, GIF, WebP (Max size: 5MB)</p>
                
                <!-- Upload Form -->
                <form method="POST" action="" enctype="multipart/form-data" style="margin-bottom: 20px;">
                    <input type="hidden" name="action" value="upload_profile_picture" />
                    <input type="hidden" name="admin_id" value="<?php echo ($current_admin['admin_id'] ?? 0); ?>" />
                    
                    <div style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                        <div class="form-group" style="flex: 1; min-width: 250px;">
                            <label>Select Image</label>
                            <div class="file-input-wrapper">
                                <input type="file" name="profile_picture" id="profile_picture" accept="image/*" required />
                                <label for="profile_picture" class="file-input-label">
                                    <i class="fas fa-upload"></i>
                                    Choose Image
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i>
                            <?php echo !empty($current_admin['profile_picture']) ? 'Replace Picture' : 'Upload Picture'; ?>
                        </button>
                    </div>
                </form>

                <!-- Remove Picture Button -->
                <?php if (!empty($current_admin['profile_picture'])): ?>
                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove your profile picture?')">
                        <input type="hidden" name="action" value="remove_profile_picture" />
                        <input type="hidden" name="admin_id" value="<?php echo ($current_admin['admin_id'] ?? 0); ?>" />
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Remove Picture
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Content: Security -->
        <div class="tab-content" id="security-tab">
            <div class="content-card">
                <h2>Change Password</h2>
                <p style="margin-bottom: 30px; color: #6c757d;">Update your account password. Make sure to use a strong password with at least 6 characters.</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password" />
                    <input type="hidden" name="admin_id" value="<?php echo ($current_admin['admin_id'] ?? 0); ?>" />
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" name="current_password" id="current_password" required />
                        </div>
                        <div class="form-group">
                            <label for="new_password_security">New Password</label>
                            <input type="password" name="new_password" id="new_password_security" minlength="6" required title="Password must be at least 6 characters long" />
                        </div>
                        <div class="form-group">
                            <label for="confirm_password_security">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password_security" minlength="6" required title="Password must be at least 6 characters long" />
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-lock"></i> Change Password
                    </button>
                </form>
            </div>

            <!-- Security Information -->
            <div class="content-card">
                <h2>Security Information</h2>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #17a2b8;">
                    <h4 style="color: #2c3e50; margin-bottom: 15px;">
                        <i class="fas fa-info-circle" style="color: #17a2b8; margin-right: 10px;"></i>
                        Password Security Tips
                    </h4>
                    <ul style="color: #6c757d; line-height: 1.6;">
                        <li>Use at least 8 characters with a mix of letters, numbers, and symbols</li>
                        <li>Avoid using personal information like your name or birthdate</li>
                        <li>Don't reuse passwords from other accounts</li>
                        <li>Consider using a password manager</li>
                        <li>Change your password regularly</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tab Content: Manage Admins -->
        <div class="tab-content" id="admins-tab">
            <!-- Add New Admin Form -->
            <div class="content-card">
                <h2>Add New Admin</h2>
                <p style="margin-bottom: 30px; color: #6c757d;">Create a new administrator account. The new admin will have full access to the system.</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_admin" />
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="new_username">Username</label>
                            <input type="text" name="new_username" id="new_username" required pattern="[a-zA-Z\s]+" title="Username should only contain letters and spaces" />
                        </div>
                        <div class="form-group">
                            <label for="new_email">Email Address</label>
                            <input type="email" name="new_email" id="new_email" required />
                        </div>
                        <div class="form-group">
                            <label for="new_password_admin">Password</label>
                            <input type="password" name="new_password" id="new_password_admin" minlength="6" required title="Password must be at least 6 characters long" />
                        </div>
                        <div class="form-group">
                            <label for="confirm_new_password_admin">Confirm Password</label>
                            <input type="password" name="confirm_new_password" id="confirm_new_password_admin" minlength="6" required title="Password must be at least 6 characters long" />
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Add Admin
                    </button>
                </form>
            </div>

            <!-- Existing Admins -->
            <div class="content-card">
                <h2>Existing Administrators</h2>
                <?php if ($all_admins_result && $all_admins_result->num_rows > 0): ?>
                    <div class="admin-grid">
                        <?php while ($admin = $all_admins_result->fetch_assoc()): ?>
                            <div class="admin-card">
                                <div class="admin-header">
                                    <?php if (!empty($admin['profile_picture']) && file_exists($admin['profile_picture'])): ?>
                                        <img src="<?php echo htmlspecialchars($admin['profile_picture']); ?>" alt="Admin Avatar" class="admin-avatar" />
                                    <?php else: ?>
                                        <div class="no-admin-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="admin-info">
                                        <h4><?php echo htmlspecialchars($admin['username'] ?? 'Unknown'); ?></h4>
                                        <p><?php echo htmlspecialchars($admin['email'] ?? ''); ?></p>
                                        <?php if (($admin['admin_id'] ?? 0) == ($current_admin['admin_id'] ?? 0)): ?>
                                            <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem;">YOU</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div style="margin-bottom: 20px; color: #6c757d; font-size: 0.9rem;">
                                    <i class="fas fa-shield-alt"></i> Administrator Role
                                </div>
                                
                                <?php if (($admin['admin_id'] ?? 0) != ($current_admin['admin_id'] ?? 0)): ?>
                                    <div class="admin-actions">
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this admin account? This action cannot be undone.')">
                                            <input type="hidden" name="action" value="delete_admin" />
                                            <input type="hidden" name="delete_admin_id" value="<?php echo ($admin['admin_id'] ?? 0); ?>" />
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div style="text-align: center; color: #6c757d; font-style: italic; font-size: 0.9rem;">
                                        Current Account
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No administrators found.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');
                    
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    document.getElementById(targetTab).classList.add('active');
                });
            });
        });

        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        // File input change handler
        document.addEventListener('DOMContentLoaded', function() {
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const label = this.nextElementSibling;
                    const fileName = this.files[0] ? this.files[0].name : 'Choose Image';
                    label.innerHTML = `<i class="fas fa-upload"></i> ${fileName}`;
                });
            });
        });

        // Password confirmation validation
        document.addEventListener('DOMContentLoaded', function() {
            // Security password validation
            const newPasswordInput = document.getElementById('new_password_security');
            const confirmPasswordInput = document.getElementById('confirm_password_security');
            
            // Add admin password validation
            const newAdminPasswordInput = document.getElementById('new_password_admin');
            const confirmNewAdminPasswordInput = document.getElementById('confirm_new_password_admin');
            
            function validatePasswords(password1, password2) {
                if (password1 && password2) {
                    if (password1.value !== password2.value) {
                        password2.setCustomValidity('Passwords do not match');
                    } else {
                        password2.setCustomValidity('');
                    }
                }
            }
            
            if (newPasswordInput && confirmPasswordInput) {
                newPasswordInput.addEventListener('input', function() {
                    validatePasswords(newPasswordInput, confirmPasswordInput);
                });
                confirmPasswordInput.addEventListener('input', function() {
                    validatePasswords(newPasswordInput, confirmPasswordInput);
                });
            }
            
            if (newAdminPasswordInput && confirmNewAdminPasswordInput) {
                newAdminPasswordInput.addEventListener('input', function() {
                    validatePasswords(newAdminPasswordInput, confirmNewAdminPasswordInput);
                });
                confirmNewAdminPasswordInput.addEventListener('input', function() {
                    validatePasswords(newAdminPasswordInput, confirmNewAdminPasswordInput);
                });
            }
        });

        // Custom form validation
        document.addEventListener('DOMContentLoaded', function() {
            // Profile form validation
            const profileForms = document.querySelectorAll('input[name="action"][value="update_profile"]');
            profileForms.forEach(function(input) {
                const form = input.closest('form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const username = form.querySelector('input[name="username"]');
                        const email = form.querySelector('input[name="email"]');
                        
                        // Validate username (only letters and spaces)
                        if (username && !/^[a-zA-Z\s]+$/.test(username.value)) {
                            alert('Username should only contain letters and spaces!');
                            e.preventDefault();
                            username.focus();
                            return;
                        }
                        
                        // Validate email
                        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                            alert('Please enter a valid email address!');
                            e.preventDefault();
                            email.focus();
                            return;
                        }
                    });
                }
            });
            
            // Security form validation
            const securityForms = document.querySelectorAll('input[name="action"][value="change_password"]');
            securityForms.forEach(function(input) {
                const form = input.closest('form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const newPassword = form.querySelector('input[name="new_password"]');
                        const confirmPassword = form.querySelector('input[name="confirm_password"]');
                        
                        // Check password length
                        if (newPassword && newPassword.value.length < 6) {
                            alert('Password must be at least 6 characters long!');
                            e.preventDefault();
                            newPassword.focus();
                            return;
                        }
                        
                        // Check password match
                        if (newPassword && confirmPassword && newPassword.value !== confirmPassword.value) {
                            alert('Passwords do not match!');
                            e.preventDefault();
                            confirmPassword.focus();
                            return;
                        }
                    });
                }
            });
            
            // Add admin form validation
            const addAdminForms = document.querySelectorAll('input[name="action"][value="add_admin"]');
            addAdminForms.forEach(function(input) {
                const form = input.closest('form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const newUsername = form.querySelector('input[name="new_username"]');
                        const newEmail = form.querySelector('input[name="new_email"]');
                        const newPassword = form.querySelector('input[name="new_password"]');
                        const confirmNewPassword = form.querySelector('input[name="confirm_new_password"]');
                        
                        // Validate username (only letters and spaces)
                        if (newUsername && !/^[a-zA-Z\s]+$/.test(newUsername.value)) {
                            alert('Username should only contain letters and spaces!');
                            e.preventDefault();
                            newUsername.focus();
                            return;
                        }
                        
                        // Validate email
                        if (newEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newEmail.value)) {
                            alert('Please enter a valid email address!');
                            e.preventDefault();
                            newEmail.focus();
                            return;
                        }
                        
                        // Check password length
                        if (newPassword && newPassword.value.length < 6) {
                            alert('Password must be at least 6 characters long!');
                            e.preventDefault();
                            newPassword.focus();
                            return;
                        }
                        
                        // Check password match
                        if (newPassword && confirmNewPassword && newPassword.value !== confirmNewPassword.value) {
                            alert('Passwords do not match!');
                            e.preventDefault();
                            confirmNewPassword.focus();
                            return;
                        }
                    });
                }
            });
        });

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
    </script>
</body>
</html>

<?php
$conn->close();
?>