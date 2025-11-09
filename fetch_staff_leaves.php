<?php
// fetch_staff_leaves.php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "login";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;

if ($staff_id > 0) {
    // Get staff info
    $staff_query = "SELECT staffname, email, phone FROM staff WHERE staff_id = ?";
    $staff_stmt = $conn->prepare($staff_query);
    $staff_stmt->bind_param("i", $staff_id);
    $staff_stmt->execute();
    $staff_result = $staff_stmt->get_result();
    $staff_info = $staff_result->fetch_assoc();
    
    // Get staff's leave applications
    $leaves_query = "SELECT * FROM leave_applications WHERE staff_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($leaves_query);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($staff_info) {
        echo "<h3 style='color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center;'>";
        echo "<i class='fas fa-calendar-times' style='margin-right: 10px; color: #667eea;'></i>";
        echo "Leave Applications for " . htmlspecialchars($staff_info['staffname']);
        echo "</h3>";
        
        // Display staff info
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #17a2b8;'>";
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;'>";
        echo "<div style='display: flex; align-items: center; gap: 8px;'>";
        echo "<i class='fas fa-envelope' style='color: #17a2b8;'></i>";
        echo "<span>" . htmlspecialchars($staff_info['email']) . "</span>";
        echo "</div>";
        if ($staff_info['phone']) {
            echo "<div style='display: flex; align-items: center; gap: 8px;'>";
            echo "<i class='fas fa-phone' style='color: #17a2b8;'></i>";
            echo "<span>" . htmlspecialchars($staff_info['phone']) . "</span>";
            echo "</div>";
        }
        echo "</div>";
        echo "</div>";
    }
    
    if ($result->num_rows > 0) {
        while ($leave = $result->fetch_assoc()) {
            // Calculate duration
            $start_date = new DateTime($leave['for_when']);
            $end_date = new DateTime($leave['till_when']);
            $interval = $start_date->diff($end_date);
            $duration = $interval->days + 1;
            
            // Determine leave timing status
            $today = new DateTime();
            $timing_status = 'past';
            $timing_text = 'Completed';
            $timing_color = '#6c757d';
            
            if ($today >= $start_date && $today <= $end_date) {
                $timing_status = 'active';
                $timing_text = 'Currently On Leave';
                $timing_color = '#28a745';
            } elseif ($today < $start_date) {
                $timing_status = 'upcoming';
                $timing_text = 'Upcoming';
                $timing_color = '#ffc107';
            }
            
            // Determine approval status
            $approval_status = $leave['status'] ?: 'pending';
            $approval_color = '#ffc107';
            $approval_text = 'Pending Approval';
            $approval_icon = 'fas fa-clock';
            
            if ($approval_status === 'approved') {
                $approval_color = '#28a745';
                $approval_text = 'Approved';
                $approval_icon = 'fas fa-check-circle';
            } elseif ($approval_status === 'rejected') {
                $approval_color = '#dc3545';
                $approval_text = 'Rejected';
                $approval_icon = 'fas fa-times-circle';
            }
            
            echo "<div style='background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid {$timing_color};'>";
            
            // Leave header
            echo "<div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;'>";
            echo "<div>";
            echo "<h4 style='color: #2c3e50; margin: 0;'>Leave Application #" . $leave['id'] . "</h4>";
            echo "<small style='color: #6c757d;'>Applied on " . date('M d, Y H:i', strtotime($leave['created_at'])) . "</small>";
            echo "</div>";
            echo "<div style='text-align: right;'>";
            echo "<div style='margin-bottom: 8px;'>";
            echo "<span style='background: {$timing_color}; color: white; padding: 4px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 600;'>{$timing_text}</span>";
            echo "</div>";
            echo "<div style='margin-bottom: 8px;'>";
            echo "<span style='background: {$approval_color}; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;'>";
            echo "<i class='{$approval_icon}' style='margin-right: 5px;'></i>{$approval_text}";
            echo "</span>";
            echo "</div>";
            echo "<div style='color: #667eea; font-weight: 600; font-size: 0.9rem;'>{$duration} day(s)</div>";
            echo "</div>";
            echo "</div>";
            
            // Leave details
            echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;'>";
            echo "<div style='display: flex; align-items: center; gap: 8px; color: #6c757d;'>";
            echo "<i class='fas fa-calendar-alt' style='color: #667eea; width: 16px;'></i>";
            echo "<span>From: " . date('M d, Y', strtotime($leave['for_when'])) . "</span>";
            echo "</div>";
            echo "<div style='display: flex; align-items: center; gap: 8px; color: #6c757d;'>";
            echo "<i class='fas fa-calendar-check' style='color: #667eea; width: 16px;'></i>";
            echo "<span>To: " . date('M d, Y', strtotime($leave['till_when'])) . "</span>";
            echo "</div>";
            echo "</div>";
            
            // Leave reason
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 10px; border-left: 4px solid #667eea; margin-bottom: 15px;'>";
            echo "<h5 style='color: #2c3e50; margin: 0 0 8px 0; display: flex; align-items: center;'>";
            echo "<i class='fas fa-comment-dots' style='margin-right: 8px; color: #667eea;'></i>";
            echo "Leave Reason";
            echo "</h5>";
            echo "<p style='margin: 0; color: #6c757d; line-height: 1.5;'>" . nl2br(htmlspecialchars($leave['leave_reason'])) . "</p>";
            echo "</div>";
            
            // Admin actions (only show if status is pending)
            if ($approval_status === 'pending') {
                echo "<div style='display: flex; gap: 10px; justify-content: center;'>";
                echo "<button onclick='updateLeaveStatus(" . $leave['id'] . ", \"approved\")' style='background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: transform 0.2s;' onmouseover='this.style.transform=\"translateY(-2px)\"' onmouseout='this.style.transform=\"translateY(0)\"'>";
                echo "<i class='fas fa-check' style='margin-right: 5px;'></i>Approve";
                echo "</button>";
                echo "<button onclick='updateLeaveStatus(" . $leave['id'] . ", \"rejected\")' style='background: linear-gradient(135deg, #dc3545, #e74c3c); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: transform 0.2s;' onmouseover='this.style.transform=\"translateY(-2px)\"' onmouseout='this.style.transform=\"translateY(0)\"'>";
                echo "<i class='fas fa-times' style='margin-right: 5px;'></i>Reject";
                echo "</button>";
                echo "</div>";
            }
            
            echo "</div>"; // Close leave card
        }
    } else {
        echo "<div style='text-align: center; padding: 40px; background: #f8f9fa; border-radius: 15px; border: 2px dashed #dee2e6;'>";
        echo "<i class='fas fa-calendar-times' style='font-size: 3rem; color: #dee2e6; margin-bottom: 15px; display: block;'></i>";
        echo "<h4 style='color: #6c757d; margin-bottom: 10px;'>No Leave Applications</h4>";
        echo "<p style='color: #6c757d; margin: 0;'>This staff member has not applied for any leaves yet.</p>";
        echo "</div>";
    }
    
} else {
    echo "<div style='text-align: center; padding: 40px; background: #f8d7da; border-radius: 15px; border: 1px solid #f5c6cb;'>";
    echo "<i class='fas fa-exclamation-triangle' style='font-size: 3rem; color: #721c24; margin-bottom: 15px; display: block;'></i>";
    echo "<h4 style='color: #721c24; margin-bottom: 10px;'>Invalid Request</h4>";
    echo "<p style='color: #721c24; margin: 0;'>Staff ID not provided or invalid.</p>";
    echo "</div>";
}

$conn->close();
?>
