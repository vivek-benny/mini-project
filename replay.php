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

// SMS sending function (you'll need to integrate with SMS API like Twilio, TextLocal, etc.)
function sendSMS($phone_number, $message) {
    // Example using a generic SMS API - replace with your SMS provider
    // This is a placeholder - you need to implement actual SMS API integration
    
    // For TextLocal (Indian SMS provider) example:
    /*
    $api_key = 'YOUR_API_KEY';
    $sender = 'TXTLCL'; // 6 characters or less
    
    $data = array(
        'apikey' => $api_key,
        'numbers' => $phone_number,
        'sender' => $sender,
        'message' => $message
    );
    
    $ch = curl_init('https://api.textlocal.in/send/');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
    */
    
    // For testing purposes, we'll simulate SMS sending
    // In production, replace this with actual SMS API call
    return array('status' => 'success', 'message' => 'SMS sent successfully (simulated)');
}

$message_sent = '';
$error_message = '';

// Handle reply submission
if (isset($_POST['send_reply'])) {
    $message_id = $_POST['message_id'];
    $reply_text = trim($_POST['reply_message']);
    $staff_id = $_SESSION['staff_id'] ?? 1; // Get from session or default
    $send_via = $_POST['send_via'] ?? 'whatsapp'; // Default to WhatsApp
    
    if (!empty($reply_text)) {
        // Get user phone number and message details
        $query = "SELECT m.user_id, m.message, r.phonenumber, r.name 
                  FROM messages m 
                  JOIN register r ON m.user_id = r.user_id 
                  WHERE m.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        
        if ($user_data && $user_data['phonenumber']) {
            // Update the message with reply
            $update_query = "UPDATE messages SET response = ?, staff_id = ?, responded_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sii", $reply_text, $staff_id, $message_id);
            
            if ($update_stmt->execute()) {
                // Handle WhatsApp message sending
                $customer_phone = preg_replace('/[^0-9]/', '', $user_data['phonenumber']);
                if (strlen($customer_phone) == 10) {
                    $formatted_phone = '91' . $customer_phone;
                } elseif (strlen($customer_phone) == 12 && substr($customer_phone, 0, 2) == '91') {
                    $formatted_phone = $customer_phone;
                } else {
                    $error_message = "❌ Invalid phone number format";
                    goto skip_whatsapp;
                }
                
                // Create a more comforting WhatsApp message format
                $whatsapp_message = " Hello " . $user_data['name'] . ",\n\n";
                $whatsapp_message .= "Thank you for reaching out to Garage Auto Services! 🙏\n\n";
                $whatsapp_message .= "We're happy to assist you with your enquiry. Here's our response:\n\n";
                $whatsapp_message .= "💬 " . $reply_text . "\n\n";
                $whatsapp_message .= "We truly value your trust in our services and are committed to providing you with the best experience possible.\n\n";
                $whatsapp_message .= "If you have any more questions or need further assistance, please don't hesitate to reach out. We're here to help! 😊\n\n";
                $whatsapp_message .= "Warm regards,\n";
                $whatsapp_message .= "The Garage Auto Services Team 🚗\n";
                $whatsapp_message .= "📞 Contact: +91-8590844281\n";
                $whatsapp_message .= "📍 Location: Main Road, Idukki, Kerala\n\n";
                $whatsapp_message .= "Thank you for choosing Garage! 🙌";
                
                // Create WhatsApp URL and redirect immediately
                $whatsapp_url = "https://wa.me/" . $formatted_phone . "?text=" . urlencode($whatsapp_message);
                
                // Set success message for after redirect
                $_SESSION['whatsapp_success'] = "✅ WhatsApp message sent successfully to " . $user_data['name'];
                
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
                    <a href='replay.php' class='back-btn'>← Back to Messages</a>
                    
                    <script>
                        // Try to open WhatsApp in a new window/tab
                        var whatsappWindow = window.open('{$whatsapp_url}', '_blank');
                        
                        // Redirect back to messages page after a short delay
                        setTimeout(function() {
                            window.location.href = 'replay.php?whatsapp_sent=1';
                        }, 2000);
                        
                        // If popup was blocked, redirect immediately
                        if (!whatsappWindow) {
                            window.location.href = '{$whatsapp_url}';
                        }
                    </script>
                </body>
                </html>";
                exit();
                
                skip_whatsapp:
            } else {
                $error_message = "❌ Error saving reply. Please try again.";
            }
        } else {
            $error_message = "❌ User phone number not found. Cannot send message.";
        }
    } else {
        $error_message = "❌ Please enter a reply message.";
    }
}

// Check for success message
if (isset($_SESSION['whatsapp_success'])) {
    $message_sent = $_SESSION['whatsapp_success'];
    unset($_SESSION['whatsapp_success']);
} elseif (isset($_GET['whatsapp_sent'])) {
    $message_sent = "✅ WhatsApp opened successfully!";
}

// Get all messages with user details
$messages_query = "SELECT m.*, r.name as user_name, r.phonenumber, r.email as user_email 
                   FROM messages m 
                   JOIN register r ON m.user_id = r.user_id 
                   ORDER BY m.created_at DESC";
$messages_result = $conn->query($messages_query);

// Get message statistics
$total_messages = $conn->query("SELECT COUNT(*) as count FROM messages")->fetch_assoc()['count'];
$pending_messages = $conn->query("SELECT COUNT(*) as count FROM messages WHERE response IS NULL")->fetch_assoc()['count'];
$replied_messages = $conn->query("SELECT COUNT(*) as count FROM messages WHERE response IS NOT NULL")->fetch_assoc()['count'];
$today_messages = $conn->query("SELECT COUNT(*) as count FROM messages WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoCare Pro - Messages Management</title>
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

        .message-card {
            background: #ffffff;
            border: 2px solid rgba(255, 140, 66, 0.1);
            border-radius: 12px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.08);
            transition: all 0.3s ease;
        }

        .message-card:hover {
            box-shadow: 0 8px 25px rgba(255, 140, 66, 0.15);
            transform: translateY(-2px);
            border-color: rgba(255, 140, 66, 0.2);
        }

        .message-header {
            background: linear-gradient(45deg, #fff8f0, #ffe4d1);
            padding: 20px;
            border-bottom: 1px solid rgba(255, 140, 66, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            background: linear-gradient(45deg, #ff8c42, #ff7b25);
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 4px 15px rgba(255, 140, 66, 0.3);
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name-msg {
            font-weight: 700;
            color: #ff8c42;
            font-size: 16px;
        }

        .user-contact {
            color: #666;
            font-size: 12px;
        }

        .message-date {
            color: #ff7b25;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .message-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }

        .status-pending {
            background: linear-gradient(45deg, #f39c12, #e67e22);
            color: white;
        }

        .status-replied {
            background: linear-gradient(45deg, #2ecc71, #27ae60);
            color: white;
        }

        .message-body {
            padding: 25px;
        }

        .message-content {
            background: #fff8f0;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ff8c42;
            margin-bottom: 20px;
        }

        .message-text {
            color: #333;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .message-meta {
            color: #666;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .reply-section {
            background: linear-gradient(135deg, #e8f5e8 0%, #d1ecf1 100%);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .reply-header {
            color: #2ecc71;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reply-text {
            background: white;
            color: #333;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 1px solid #d1ecf1;
            line-height: 1.6;
        }

        .reply-meta {
            color: #666;
            font-size: 12px;
        }

        .reply-form {
            background: linear-gradient(135deg, #fff8f0 0%, #ffe4d1 100%);
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #ff8c42;
            margin-top: 20px;
        }

        .reply-form-header {
            color: #ff8c42;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
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

        .form-textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #ffe4d1;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 4px rgba(255, 140, 66, 0.05);
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        .form-textarea:focus {
            outline: none;
            border-color: #ff8c42;
            box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.1);
            transform: translateY(-1px);
        }

        .send-btn {
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
            box-shadow: 0 4px 15px rgba(46, 204, 113, 0.2);
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.3);
        }

        .send-btn:active {
            transform: translateY(0);
        }

        .phone-info {
            background: #d1ecf1;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2c5aa0;
            font-size: 14px;
        }

        .no-messages {
            text-align: center;
            padding: 60px 20px;
            color: #ff7b25;
        }

        .no-messages-icon {
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

            .message-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .dashboard-container {
                padding: 10px;
            }

            .main-content {
                padding: 20px;
            }

            .message-body {
                padding: 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
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
                <a href="bookingview.php" class="nav-link">
                    <i class="fas fa-list nav-icon"></i>
                    <span class="nav-text">All Bookings</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="messages.php" class="nav-link active">
                    <i class="fas fa-comments nav-icon"></i>
                    <span class="nav-text">Enquires</span>
                    <?php if ($pending_messages > 0): ?>
                        <span class="nav-badge"><?= $pending_messages ?></span>
                    <?php endif; ?>
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
                            <i class="fas fa-comments"></i>
                        </div>
                        <div>
                            <div>Messages Management</div>
                            <div class="breadcrumb">Home > Messages</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-number"><?= $total_messages ?></div>
                    <div class="stat-label">Total Messages</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?= $pending_messages ?></div>
                    <div class="stat-label">Pending Replies</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $replied_messages ?></div>
                    <div class="stat-label">Replied</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-number"><?= $today_messages ?></div>
                    <div class="stat-label">Today's Messages</div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="section-title">Customer Messages</div>
                </div>

                <?php if (!empty($message_sent)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?= $message_sent ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="error-message">
                        <i class="fas fa-times-circle"></i>
                        <?= $error_message ?>
                    </div>
                <?php endif; ?>

                <?php if ($messages_result->num_rows > 0): ?>
                    <?php while ($message = $messages_result->fetch_assoc()): ?>
                        <div class="message-card">
                            <div class="message-header">
                                <div class="message-user">
                                    <div class="user-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="user-info">
                                        <div class="user-name-msg"><?= htmlspecialchars($message['user_name']) ?></div>
                                        <div class="user-contact">
                                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($message['user_email']) ?> |
                                            <i class="fas fa-phone"></i> <?= htmlspecialchars($message['phonenumber']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="message-date">
                                        <i class="fas fa-calendar"></i>
                                        <?= date('M d, Y - H:i', strtotime($message['created_at'])) ?>
                                    </div>
                                    <div class="message-status <?= $message['response'] ? 'status-replied' : 'status-pending' ?>">
                                        <?= $message['response'] ? 'Replied' : 'Pending' ?>
                                    </div>
                                </div>
                            </div>

                            <div class="message-body">
                                <div class="message-content">
                                    <div class="message-text">
                                        <?= nl2br(htmlspecialchars($message['message'])) ?>
                                    </div>
                                    <div class="message-meta">
                                        <span><i class="fas fa-user"></i> Message from <?= htmlspecialchars($message['user_name']) ?></span>
                                        <span><i class="fas fa-clock"></i> <?= date('M d, Y at H:i', strtotime($message['created_at'])) ?></span>
                                    </div>
                                </div>

                                <?php if ($message['response']): ?>
                                    <div class="reply-section">
                                        <div class="reply-header">
                                            <i class="fas fa-reply"></i>
                                            Your Reply (Sent via WhatsApp)
                                        </div>
                                        <div class="reply-text">
                                            <?= nl2br(htmlspecialchars($message['response'])) ?>
                                        </div>
                                        <div class="reply-meta">
                                            <i class="fas fa-clock"></i> Replied on <?= date('M d, Y at H:i', strtotime($message['responded_at'])) ?>
                                            | <i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp message sent to <?= htmlspecialchars($message['phonenumber']) ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="reply-form">
                                        <div class="reply-form-header">
                                            <i class="fas fa-pen"></i>
                                            Send Reply
                                        </div>
                                        
                                        <div class="phone-info">
                                            <i class="fas fa-mobile-alt"></i>
                                            Message will be sent to: <strong><?= htmlspecialchars($message['phonenumber']) ?></strong>
                                        </div>
                                        
                                        <form method="post">
                                            <input type="hidden" name="message_id" value="<?= $message['id'] ?>">
                                            
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-comment-alt"></i>
                                                    Your Reply Message
                                                </label>
                                                <textarea name="reply_message" class="form-textarea" placeholder="Type your reply message here. This will be sent to the customer..." required></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="form-label">
                                                    <i class="fas fa-paper-plane"></i>
                                                    Send Via
                                                </label>
                                                <div style="display: flex; gap: 15px; margin-top: 10px;">
                                                    <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                                        <input type="radio" name="send_via" value="whatsapp" checked>
                                                        <i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp
                                                    </label>
                                                </div>
                                            </div>

                                            <button type="submit" name="send_reply" class="send-btn">
                                                <i class="fas fa-paper-plane"></i>
                                                Send Reply
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <div class="no-messages-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <h3>No Messages Yet</h3>
                        <p>When customers send messages, they will appear here for you to respond.</p>
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

        // Auto-resize textarea
        document.querySelectorAll('.form-textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.max(120, this.scrollHeight) + 'px';
            });
        });

        // Confirmation before sending SMS
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (this.querySelector('button[name="send_reply"]')) {
                    const phoneNumber = this.closest('.message-card').querySelector('.phone-info strong').textContent;
                    const message = this.querySelector('textarea').value;
                    
                    if (message.trim().length === 0) {
                        e.preventDefault();
                        alert('Please enter a reply message.');
                        return;
                    }
                    
                    const confirmed = confirm(
                        `Are you sure you want to send this SMS reply to ${phoneNumber}?\n\n` +
                        `Message: "${message.substring(0, 100)}${message.length > 100 ? '...' : ''}"`
                    );
                    
                    if (!confirmed) {
                        e.preventDefault();
                    }
                }
            });
        });

        // Mark messages as read when viewed (optional enhancement)
        function markAsRead(messageId) {
            // This could send an AJAX request to mark messages as read
            // Implement if you want to track read/unread status
        }

        // Auto-refresh page every 30 seconds to check for new messages
        setInterval(function() {
            // Only refresh if no forms are being filled out
            const activeElement = document.activeElement;
            if (!activeElement || activeElement.tagName !== 'TEXTAREA') {
                const urlParams = new URLSearchParams(window.location.search);
                if (!urlParams.has('no_refresh')) {
                    // Add a parameter to prevent infinite refresh loops
                    window.location.href = window.location.pathname + '?auto_refresh=1';
                }
            }
        }, 30000); // 30 seconds

        // Remove auto_refresh parameter after page load
        if (window.location.search.includes('auto_refresh=1')) {
            const url = window.location.pathname;
            window.history.replaceState({}, document.title, url);
        }
    </script>
</body>
</html>