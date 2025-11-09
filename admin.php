<?php
// admin.php (complete version with services management only)
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
$upload_dir = 'uploads/services/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle POST actions (services only)
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $service_name = $conn->real_escape_string($_POST['service_name']);
            $description = $conn->real_escape_string($_POST['description']);
            $price = floatval($_POST['price']);
            $duration = intval($_POST['duration']);
            $category = $conn->real_escape_string($_POST['category']);
            
            // Server-side validation for service name and category
            if (!preg_match("/^[a-zA-Z\s]+$/", $service_name)) {
                $message = "Service name should only contain letters and spaces!";
                $messageType = 'error';
            } elseif (!preg_match("/^[a-zA-Z\s]+$/", $category)) {
                $message = "Category should only contain letters and spaces!";
                $messageType = 'error';
            } else {
                $insert_query = "INSERT INTO services (service_name, description, price, duration_minutes, category, status) 
                               VALUES ('$service_name', '$description', $price, $duration, '$category', 'active')";
                
                if ($conn->query($insert_query)) {
                    $message = "Service added successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error adding service: " . $conn->error;
                    $messageType = 'error';
                }
            }
            break;
            
        case 'update':
            $service_id = intval($_POST['service_id']);
            $service_name = $conn->real_escape_string($_POST['service_name']);
            $description = $conn->real_escape_string($_POST['description']);
            $price = floatval($_POST['price']);
            $duration = intval($_POST['duration']);
            $category = $conn->real_escape_string($_POST['category']);
            
            // Server-side validation for service name and category
            if (!preg_match("/^[a-zA-Z\s]+$/", $service_name)) {
                $message = "Service name should only contain letters and spaces!";
                $messageType = 'error';
            } elseif (!preg_match("/^[a-zA-Z\s]+$/", $category)) {
                $message = "Category should only contain letters and spaces!";
                $messageType = 'error';
            } else {
                $update_query = "UPDATE services SET 
                               service_name = '$service_name',
                               description = '$description',
                               price = $price,
                               duration_minutes = $duration,
                               category = '$category'
                               WHERE service_id = $service_id";
                
                if ($conn->query($update_query)) {
                    $message = "Service updated successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error updating service: " . $conn->error;
                    $messageType = 'error';
                }
            }
            break;
            
        case 'toggle_status':
            $service_id = intval($_POST['service_id']);
            $status = $conn->real_escape_string($_POST['status']);
            
            $toggle_query = "UPDATE services SET status = '$status' WHERE service_id = $service_id";
            
            if ($conn->query($toggle_query)) {
                $message = "Service status updated successfully!";
                $messageType = 'success';
            } else {
                $message = "Error updating service status: " . $conn->error;
                $messageType = 'error';
            }
            break;
            
        case 'delete':
            $service_id = intval($_POST['service_id']);
            
            // Get the image path before deleting
            $image_query = "SELECT image FROM services WHERE service_id = $service_id";
            $image_result = $conn->query($image_query);
            if ($image_result && $image_row = $image_result->fetch_assoc()) {
                if ($image_row['image'] && file_exists($image_row['image'])) {
                    unlink($image_row['image']); // Delete the image file
                }
            }
            
            // First delete related records
            $conn->query("DELETE FROM service_includes WHERE service_id = $service_id");
            $conn->query("DELETE FROM service_details WHERE service_id = $service_id");
            $delete_query = "DELETE FROM services WHERE service_id = $service_id";
            
            if ($conn->query($delete_query)) {
                $message = "Service deleted successfully!";
                $messageType = 'success';
            } else {
                $message = "Error deleting service: " . $conn->error;
                $messageType = 'error';
            }
            break;
            
        case 'upload_image':
            $service_id = intval($_POST['service_id']);
            
            if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = $_FILES['service_image']['type'];
                $file_size = $_FILES['service_image']['size'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file_type, $allowed_types)) {
                    $message = "Error: Only JPEG, PNG, GIF, and WebP images are allowed.";
                    $messageType = 'error';
                } elseif ($file_size > $max_size) {
                    $message = "Error: File size must be less than 5MB.";
                    $messageType = 'error';
                } else {
                    // Get current image to delete it
                    $current_image_query = "SELECT image FROM services WHERE service_id = $service_id";
                    $current_image_result = $conn->query($current_image_query);
                    if ($current_image_result && $current_row = $current_image_result->fetch_assoc()) {
                        if ($current_row['image'] && file_exists($current_row['image'])) {
                            unlink($current_row['image']);
                        }
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($_FILES['service_image']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'service_' . $service_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['service_image']['tmp_name'], $upload_path)) {
                        $update_image_query = "UPDATE services SET image = '$upload_path' WHERE service_id = $service_id";
                        if ($conn->query($update_image_query)) {
                            $message = "Image uploaded successfully!";
                            $messageType = 'success';
                        } else {
                            $message = "Error updating database: " . $conn->error;
                            $messageType = 'error';
                            unlink($upload_path); // Delete the uploaded file if database update fails
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
            
        case 'remove_image':
            $service_id = intval($_POST['service_id']);
            
            // Get the image path
            $image_query = "SELECT image FROM services WHERE service_id = $service_id";
            $image_result = $conn->query($image_query);
            if ($image_result && $image_row = $image_result->fetch_assoc()) {
                if ($image_row['image'] && file_exists($image_row['image'])) {
                    unlink($image_row['image']); // Delete the image file
                }
            }
            
            $remove_image_query = "UPDATE services SET image = NULL WHERE service_id = $service_id";
            if ($conn->query($remove_image_query)) {
                $message = "Image removed successfully!";
                $messageType = 'success';
            } else {
                $message = "Error removing image: " . $conn->error;
                $messageType = 'error';
            }
            break;
            
        case 'add_service_includes':
            $service_id = intval($_POST['service_id']);
            $included_item = $conn->real_escape_string($_POST['included_item']);
            
            $insert_includes_query = "INSERT INTO service_includes (service_id, included_item) 
                                    VALUES ($service_id, '$included_item')";
            
            if ($conn->query($insert_includes_query)) {
                $message = "Service include added successfully!";
                $messageType = 'success';
            } else {
                $message = "Error adding service include: " . $conn->error;
                $messageType = 'error';
            }
            break;
            
        case 'delete_service_includes':
            $include_id = intval($_POST['include_id']);
            $delete_include_query = "DELETE FROM service_includes WHERE id = $include_id";
            
            if ($conn->query($delete_include_query)) {
                $message = "Service include deleted successfully!";
                $messageType = 'success';
            } else {
                $message = "Error deleting service include: " . $conn->error;
                $messageType = 'error';
            }
            break;
            
        case 'add_service_details':
            $service_id = intval($_POST['service_id']);
            $why_choose = $conn->real_escape_string($_POST['why_choose']);
            
            // Check if service details already exist for this service
            $check_query = "SELECT id FROM service_details WHERE service_id = $service_id";
            $check_result = $conn->query($check_query);
            
            if ($check_result && $check_result->num_rows > 0) {
                // Update existing
                $update_details_query = "UPDATE service_details SET why_choose = '$why_choose' WHERE service_id = $service_id";
                if ($conn->query($update_details_query)) {
                    $message = "Service details updated successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error updating service details: " . $conn->error;
                    $messageType = 'error';
                }
            } else {
                // Insert new
                $insert_details_query = "INSERT INTO service_details (service_id, why_choose) 
                                       VALUES ($service_id, '$why_choose')";
                
                if ($conn->query($insert_details_query)) {
                    $message = "Service details added successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error adding service details: " . $conn->error;
                    $messageType = 'error';
                }
            }
            break;
            
        case 'delete_service_details':
            $detail_id = intval($_POST['detail_id']);
            $delete_detail_query = "DELETE FROM service_details WHERE id = $detail_id";
            
            if ($conn->query($delete_detail_query)) {
                $message = "Service details deleted successfully!";
                $messageType = 'success';
            } else {
                $message = "Error deleting service details: " . $conn->error;
                $messageType = 'error';
            }
            break;
    }
}

// Fetch all services
$services_query = "SELECT * FROM services ORDER BY category, service_name";
$services_result = $conn->query($services_query);

// Get service categories for stats
$categories_query = "SELECT category, COUNT(*) as count FROM services GROUP BY category";
$categories_result = $conn->query($categories_query);

// Fetch all service details with service names
$service_details_query = "SELECT sd.*, s.service_name 
                         FROM service_details sd 
                         LEFT JOIN services s ON sd.service_id = s.service_id 
                         ORDER BY s.service_name";
$service_details_result = $conn->query($service_details_query);

// Get services for dropdown in service details
$services_dropdown_query = "SELECT service_id, service_name FROM services ORDER BY service_name";
$services_dropdown_result = $conn->query($services_dropdown_query);

// Get all service includes grouped by service
$service_includes_query = "SELECT si.*, s.service_name 
                          FROM service_includes si 
                          LEFT JOIN services s ON si.service_id = s.service_id 
                          ORDER BY s.service_name, si.included_item";
$service_includes_result = $conn->query($service_includes_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Services Management - Admin Dashboard</title>
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
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-hint {
            color: #6c757d;
            font-size: 0.8rem;
            margin-top: 5px;
            display: block;
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
        
        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .service-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .service-card:hover {
            transform: translateY(-5px);
        }
        
        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .service-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .service-category {
            background: #e9ecef;
            color: #495057;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .service-description {
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .service-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .detail-item i {
            color: #667eea;
            width: 16px;
        }
        
        .service-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .service-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        /* Service Image Styles */
        .service-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .no-image {
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        /* Image Management Styles */
        .image-management {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .image-upload-form {
            display: flex;
            gap: 15px;
            align-items: end;
            margin-bottom: 20px;
        }
        
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
        
        .image-preview {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .image-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .image-item img {
            width: 150px;
            height: 150px;
            object-fit: cover;
        }
        
        .image-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }
        
        /* Status Badge */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
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
        
        /* Service Details Styles */
        .details-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .details-service-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .details-section {
            margin-bottom: 15px;
        }
        
        .details-section h4 {
            color: #667eea;
            margin-bottom: 8px;
            font-size: 1rem;
        }
        
        .details-content {
            color: #6c757d;
            line-height: 1.6;
        }
        
        .includes-list {
            list-style: none;
            padding: 0;
        }
        
        .includes-list li {
            padding: 8px 15px;
            margin-bottom: 5px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            .services-grid {
                grid-template-columns: 1fr;
            }
            .content-header {
                flex-direction: column;
                gap: 20px;
                align-items: stretch;
            }
            .service-details {
                grid-template-columns: 1fr;
            }
            .service-actions {
                justify-content: center;
            }
            .tab-buttons {
                flex-direction: column;
            }
            .image-upload-form {
                flex-direction: column;
                align-items: stretch;
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
                    <a href="manage_services.php" class="nav-link active">
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
                <h1>Manage Services</h1>
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
                <button class="tab-button active" data-tab="services-tab">Services</button>
                <button class="tab-button" data-tab="includes-tab">Service Includes</button>
                <button class="tab-button" data-tab="details-tab">Service Details</button>
                <button class="tab-button" data-tab="images-tab">Service Images</button>
            </div>
        </div>

        <!-- Tab Content: Services -->
        <div class="tab-content active" id="services-tab">
            <!-- Add Service Form -->
            <div class="content-card">
                <h2>Add New Service</h2>
                <form method="POST" action="" id="addServiceForm">
                    <input type="hidden" name="action" value="add" />
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="service_name">Service Name</label>
                            <input type="text" name="service_name" id="service_name" required pattern="[a-zA-Z\s]+" title="Service name should only contain letters and spaces" />
                            <small class="form-hint">Only letters and spaces are allowed</small>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="price">Price (₹)</label>
                            <input type="number" name="price" id="price" step="0.01" min="0" required />
                        </div>
                        <div class="form-group">
                            <label for="duration">Duration (minutes)</label>
                            <input type="number" name="duration" id="duration" min="1" required />
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <input type="text" name="category" id="category" required pattern="[a-zA-Z\s]+" title="Category should only contain letters and spaces" />
                            <small class="form-hint">Only letters and spaces are allowed</small>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Service</button>
                </form>
            </div>

            <!-- Services List -->
            <div class="content-card">
                <h2>Existing Services</h2>
                <?php if ($services_result && $services_result->num_rows > 0): ?>
                    <div class="services-grid">
                        <?php while ($service = $services_result->fetch_assoc()): ?>
                            <div class="service-card">
                                <!-- Service Image -->
                                <?php if (!empty($service['image']) && file_exists($service['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['service_name']); ?>" class="service-image" />
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-image fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="service-header">
                                    <div>
                                        <div class="service-title"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                        <div class="service-category"><?php echo htmlspecialchars($service['category'] ?? 'General'); ?></div>
                                    </div>
                                    <div>
                                        <span class="status-badge <?php echo ($service['status'] === 'active') ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo htmlspecialchars(strtoupper($service['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="service-description">
                                    <?php echo htmlspecialchars($service['description']); ?>
                                </div>
                                
                                <div class="service-details">
                                    <div class="detail-item">
                                        <i class="fas fa-dollar-sign"></i>
                                        <span>$<?php echo number_format($service['price'], 2); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo htmlspecialchars($service['duration_minutes'] ?? $service['estimated_time'] ?? 'N/A'); ?> mins</span>
                                    </div>
                                </div>
                                
                                <div class="service-actions">
                                    <button class="btn btn-warning btn-sm" onclick="editService(<?php echo $service['service_id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                                        <input type="hidden" name="action" value="toggle_status" />
                                        <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>" />
                                        <input type="hidden" name="status" value="<?php echo ($service['status'] === 'active') ? 'inactive' : 'active'; ?>" />
                                        <button type="submit" class="btn <?php echo ($service['status'] === 'active') ? 'btn-warning' : 'btn-success'; ?> btn-sm">
                                            <i class="fas fa-<?php echo ($service['status'] === 'active') ? 'pause' : 'play'; ?>"></i>
                                            <?php echo ($service['status'] === 'active') ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this service?')">
                                        <input type="hidden" name="action" value="delete" />
                                        <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>" />
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No services found. Add a new service above.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Content: Service Includes -->
        <div class="tab-content" id="includes-tab">
            <!-- Add Service Includes Form -->
            <div class="content-card">
                <h2>Add Service Includes</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_service_includes" />
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="service_id_includes">Select Service</label>
                            <select name="service_id" id="service_id_includes" required>
                                <option value="">Choose a service...</option>
                                <?php 
                                $services_dropdown_result->data_seek(0);
                                while ($service = $services_dropdown_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $service['service_id']; ?>">
                                        <?php echo htmlspecialchars($service['service_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="included_item">What's Included</label>
                            <input type="text" name="included_item" id="included_item" placeholder="e.g., Foam wash, Interior cleaning" required />
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Include Item</button>
                </form>
            </div>

            <!-- Service Includes List -->
            <div class="content-card">
                <h2>Existing Service Includes</h2>
                <?php if ($service_includes_result && $service_includes_result->num_rows > 0): ?>
                    <?php 
                    $current_service = '';
                    while ($include = $service_includes_result->fetch_assoc()): 
                        if ($current_service !== $include['service_name']) {
                            if ($current_service !== '') echo '</ul></div>';
                            $current_service = $include['service_name'];
                            echo '<div class="details-card">';
                            echo '<div class="details-service-title">' . htmlspecialchars($current_service) . '</div>';
                            echo '<ul class="includes-list">';
                        }
                    ?>
                        <li>
                            <span><?php echo htmlspecialchars($include['included_item']); ?></span>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                                <input type="hidden" name="action" value="delete_service_includes" />
                                <input type="hidden" name="include_id" value="<?php echo $include['id']; ?>" />
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                    </ul></div>
                <?php else: ?>
                    <p>No service includes found. Add some above.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Content: Service Details -->
        <div class="tab-content" id="details-tab">
            <!-- Add Service Details Form -->
            <div class="content-card">
                <h2>Add Service Details</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add_service_details" />
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="service_id_details">Select Service</label>
                            <select name="service_id" id="service_id_details" required>
                                <option value="">Choose a service...</option>
                                <?php 
                                $services_dropdown_result->data_seek(0);
                                while ($service = $services_dropdown_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $service['service_id']; ?>">
                                        <?php echo htmlspecialchars($service['service_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="why_choose">Why Choose This Service</label>
                            <textarea name="why_choose" id="why_choose" placeholder="Explain why customers should choose this service..." required></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Service Details</button>
                </form>
            </div>

            <!-- Service Details List -->
            <div class="content-card">
                <h2>Existing Service Details</h2>
                <?php if ($service_details_result && $service_details_result->num_rows > 0): ?>
                    <?php while ($detail = $service_details_result->fetch_assoc()): ?>
                        <div class="details-card">
                            <div class="details-service-title">
                                <?php echo htmlspecialchars($detail['service_name']); ?>
                            </div>
                            <div class="details-section">
                                <h4>Why Choose</h4>
                                <div class="details-content">
                                    <?php echo htmlspecialchars($detail['why_choose']); ?>
                                </div>
                            </div>
                            <div style="margin-top: 15px;">
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                                    <input type="hidden" name="action" value="delete_service_details" />
                                    <input type="hidden" name="detail_id" value="<?php echo $detail['id']; ?>" />
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No service details found. Add some above.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Content: Service Images -->
        <div class="tab-content" id="images-tab">
            <div class="content-card">
                <h2>Service Images Management</h2>
                <p style="margin-bottom: 30px; color: #6c757d;">Upload and manage images for your services. Supported formats: JPEG, PNG, GIF, WebP (Max size: 5MB)</p>
                
                <?php 
                // Reset services result for images tab
                $services_result->data_seek(0);
                while ($service = $services_result->fetch_assoc()): 
                ?>
                    <div class="image-management">
                        <h3 style="color: #2c3e50; margin-bottom: 20px;">
                            <i class="fas fa-wrench" style="color: #667eea; margin-right: 10px;"></i>
                            <?php echo htmlspecialchars($service['service_name']); ?>
                        </h3>
                        
                        <!-- Current Image Display -->
                        <div class="image-preview" style="margin-bottom: 20px;">
                            <?php if (!empty($service['image']) && file_exists($service['image'])): ?>
                                <div class="image-item">
                                    <img src="<?php echo htmlspecialchars($service['image']); ?>" alt="<?php echo htmlspecialchars($service['service_name']); ?>" />
                                    <div class="image-actions">
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this image?')">
                                            <input type="hidden" name="action" value="remove_image" />
                                            <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>" />
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="no-image" style="width: 150px; height: 150px;">
                                    <i class="fas fa-image fa-2x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Image Upload Form -->
                        <form method="POST" action="" enctype="multipart/form-data" class="image-upload-form">
                            <input type="hidden" name="action" value="upload_image" />
                            <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>" />
                            
                            <div class="form-group" style="flex: 1;">
                                <div class="file-input-wrapper">
                                    <input type="file" name="service_image" id="image_<?php echo $service['service_id']; ?>" accept="image/*" required />
                                    <label for="image_<?php echo $service['service_id']; ?>" class="file-input-label">
                                        <i class="fas fa-upload"></i>
                                        Choose Image
                                    </label>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i>
                                <?php echo !empty($service['image']) ? 'Replace Image' : 'Upload Image'; ?>
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

    <!-- Edit Service Modal -->
    <div id="editServiceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Service</h2>
            <form method="POST" action="" id="editServiceForm">
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="service_id" id="edit_service_id" />
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit_service_name">Service Name</label>
                        <input type="text" name="service_name" id="edit_service_name" required pattern="[a-zA-Z\s]+" title="Service name should only contain letters and spaces" />
                        <small class="form-hint">Only letters and spaces are allowed</small>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea name="description" id="edit_description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_price">Price ($)</label>
                        <input type="number" name="price" id="edit_price" step="0.01" min="0" required />
                    </div>
                    <div class="form-group">
                        <label for="edit_duration">Duration (minutes)</label>
                        <input type="number" name="duration" id="edit_duration" min="1" required />
                    </div>
                    <div class="form-group">
                        <label for="edit_category">Category</label>
                        <input type="text" name="category" id="edit_category" required pattern="[a-zA-Z\s]+" title="Category should only contain letters and spaces" />
                        <small class="form-hint">Only letters and spaces are allowed</small>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal()" style="margin-right: 10px; background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Service</button>
                </div>
            </form>
        </div>
    </div>

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

        // Edit service functionality
        function editService(serviceId) {
            // Find the service data from the page
            const serviceCard = event.target.closest('.service-card');
            const serviceName = serviceCard.querySelector('.service-title').textContent;
            const serviceDescription = serviceCard.querySelector('.service-description').textContent;
            const servicePrice = serviceCard.querySelector('.detail-item:first-child span').textContent.replace('$', '').replace(',', '');
            const serviceDuration = serviceCard.querySelector('.detail-item:last-child span').textContent.replace(' mins', '');
            const serviceCategory = serviceCard.querySelector('.service-category').textContent;

            // Populate modal fields
            document.getElementById('edit_service_id').value = serviceId;
            document.getElementById('edit_service_name').value = serviceName;
            document.getElementById('edit_description').value = serviceDescription;
            document.getElementById('edit_price').value = servicePrice;
            document.getElementById('edit_duration').value = serviceDuration;
            document.getElementById('edit_category').value = serviceCategory;

            // Show modal
            document.getElementById('editServiceModal').style.display = 'block';
        }

        // Close modal
        function closeModal() {
            document.getElementById('editServiceModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editServiceModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
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
            
            // Add validation for service name and category fields in add form
            const serviceNameInput = document.getElementById('service_name');
            const categoryInput = document.getElementById('category');
            const addServiceForm = document.getElementById('addServiceForm');
            
            // Add validation for service name and category fields in edit form
            const editServiceNameInput = document.getElementById('edit_service_name');
            const editCategoryInput = document.getElementById('edit_category');
            const editServiceForm = document.getElementById('editServiceForm');
            
            if (serviceNameInput) {
                serviceNameInput.addEventListener('input', function() {
                    const serviceNameRegex = /^[a-zA-Z\s]*$/;
                    if (!serviceNameRegex.test(this.value)) {
                        this.setCustomValidity('Service name should only contain letters and spaces');
                        this.style.borderColor = 'red';
                    } else {
                        this.setCustomValidity('');
                        this.style.borderColor = '';
                    }
                });
            }
            
            if (categoryInput) {
                categoryInput.addEventListener('input', function() {
                    const categoryRegex = /^[a-zA-Z\s]*$/;
                    if (!categoryRegex.test(this.value)) {
                        this.setCustomValidity('Category should only contain letters and spaces');
                        this.style.borderColor = 'red';
                    } else {
                        this.setCustomValidity('');
                        this.style.borderColor = '';
                    }
                });
            }
            
            if (editServiceNameInput) {
                editServiceNameInput.addEventListener('input', function() {
                    const serviceNameRegex = /^[a-zA-Z\s]*$/;
                    if (!serviceNameRegex.test(this.value)) {
                        this.setCustomValidity('Service name should only contain letters and spaces');
                        this.style.borderColor = 'red';
                    } else {
                        this.setCustomValidity('');
                        this.style.borderColor = '';
                    }
                });
            }
            
            if (editCategoryInput) {
                editCategoryInput.addEventListener('input', function() {
                    const categoryRegex = /^[a-zA-Z\s]*$/;
                    if (!categoryRegex.test(this.value)) {
                        this.setCustomValidity('Category should only contain letters and spaces');
                        this.style.borderColor = 'red';
                    } else {
                        this.setCustomValidity('');
                        this.style.borderColor = '';
                    }
                });
            }
            
            if (addServiceForm) {
                addServiceForm.addEventListener('submit', function(e) {
                    const serviceName = document.getElementById('service_name');
                    const category = document.getElementById('category');
                    const serviceNameRegex = /^[a-zA-Z\s]+$/;
                    const categoryRegex = /^[a-zA-Z\s]+$/;
                    
                    if (serviceName && !serviceNameRegex.test(serviceName.value)) {
                        e.preventDefault();
                        alert('Service name should only contain letters and spaces');
                        serviceName.focus();
                        return false;
                    }
                    
                    if (category && !categoryRegex.test(category.value)) {
                        e.preventDefault();
                        alert('Category should only contain letters and spaces');
                        category.focus();
                        return false;
                    }
                });
            }
            
            if (editServiceForm) {
                editServiceForm.addEventListener('submit', function(e) {
                    const serviceName = document.getElementById('edit_service_name');
                    const category = document.getElementById('edit_category');
                    const serviceNameRegex = /^[a-zA-Z\s]+$/;
                    const categoryRegex = /^[a-zA-Z\s]+$/;
                    
                    if (serviceName && !serviceNameRegex.test(serviceName.value)) {
                        e.preventDefault();
                        alert('Service name should only contain letters and spaces');
                        serviceName.focus();
                        return false;
                    }
                    
                    if (category && !categoryRegex.test(category.value)) {
                        e.preventDefault();
                        alert('Category should only contain letters and spaces');
                        category.focus();
                        return false;
                    }
                });
            }
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
