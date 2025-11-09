<?php
// fetch_user_bookings.php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "login";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id > 0) {
    // Get user info
    $user_query = "SELECT name FROM register WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_info = $user_result->fetch_assoc();
    
    // Get user's bookings with full details
    $bookings_query = "
        SELECT 
            b.*,
            v.vehicle_type,
            v.brand,
            v.model,
            v.registration_no,
            m.name as mechanic_name,
            GROUP_CONCAT(s.service_name SEPARATOR ', ') as services,
            SUM(bs.service_price) as total_price
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.vehicle_id
        LEFT JOIN mechanics m ON b.mechanic_id = m.mechanic_id
        LEFT JOIN booking_services bs ON b.booking_id = bs.booking_id
        LEFT JOIN services s ON bs.service_id = s.service_id
        WHERE b.user_id = ?
        GROUP BY b.booking_id
        ORDER BY b.appointment_date DESC
    ";
    
    $stmt = $conn->prepare($bookings_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user_info) {
        echo "<h3 style='color: #2c3e50; margin-bottom: 20px;'>Bookings for " . htmlspecialchars($user_info['name']) . "</h3>";
    }
    
    if ($result->num_rows > 0) {
        while ($booking = $result->fetch_assoc()) {
            echo "<div class='booking-card'>";
            echo "<div class='booking-header'>";
            echo "<div class='booking-id'>Booking #" . $booking['booking_id'] . "</div>";
            echo "<div class='booking-status status-" . strtolower($booking['status']) . "'>" . htmlspecialchars($booking['status']) . "</div>";
            echo "</div>";
            
            echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;'>";
            echo "<div class='detail-item'><i class='fas fa-calendar'></i> " . date('M d, Y', strtotime($booking['appointment_date'])) . "</div>";
            
            if ($booking['brand'] && $booking['model']) {
                echo "<div class='detail-item'><i class='fas fa-car'></i> " . htmlspecialchars($booking['brand'] . ' ' . $booking['model']) . "</div>";
            }
            
            if ($booking['registration_no']) {
                echo "<div class='detail-item'><i class='fas fa-id-card'></i> " . htmlspecialchars($booking['registration_no']) . "</div>";
            }
            
            if ($booking['total_price']) {
                echo "<div class='detail-item'><i class='fas fa-dollar-sign'></i> $" . number_format($booking['total_price'], 2) . "</div>";
            }
            echo "</div>";
            
            if ($booking['services']) {
                echo "<div class='detail-item' style='margin-bottom: 10px;'><i class='fas fa-wrench'></i> Services: " . htmlspecialchars($booking['services']) . "</div>";
            } else {
                echo "<div class='detail-item' style='margin-bottom: 10px;'><i class='fas fa-info-circle'></i> No services specified</div>";
            }
            
            if (!empty($booking['mechanic_name'])) {
                echo "<div class='detail-item' style='margin-bottom: 10px;'><i class='fas fa-user-cog'></i> Mechanic: " . htmlspecialchars($booking['mechanic_name']) . "</div>";
            }
            
            if ($booking['time_slot']) {
                echo "<div class='detail-item' style='margin-bottom: 10px;'><i class='fas fa-clock'></i> Time Slot: " . htmlspecialchars($booking['time_slot']) . "</div>";
            }
            
            echo "</div>"; // Close booking-card
        }
    } else {
        echo "<div class='alert alert-info' style='text-align: center; padding: 20px;'>";
        echo "<i class='fas fa-info-circle' style='font-size: 2rem; margin-bottom: 10px; display: block;'></i>";
        echo "<h4>No Bookings Found</h4>";
        echo "<p>This user has not made any bookings yet.</p>";
        echo "</div>";
    }
    
    // Get user vehicles
    $vehicles_query = "SELECT * FROM vehicles WHERE user_id = ?";
    $vehicles_stmt = $conn->prepare($vehicles_query);
    $vehicles_stmt->bind_param("i", $user_id);
    $vehicles_stmt->execute();
    $vehicles_result = $vehicles_stmt->get_result();
    
    if ($vehicles_result->num_rows > 0) {
        echo "<h4 style='color: #2c3e50; margin: 30px 0 15px 0;'><i class='fas fa-car'></i> User Vehicles</h4>";
        while ($vehicle = $vehicles_result->fetch_assoc()) {
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 10px; border-left: 4px solid #28a745;'>";
            echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;'>";
            echo "<div class='detail-item'><i class='fas fa-car'></i> " . htmlspecialchars($vehicle['vehicle_type']) . "</div>";
            echo "<div class='detail-item'><i class='fas fa-industry'></i> " . htmlspecialchars($vehicle['brand']) . "</div>";
            echo "<div class='detail-item'><i class='fas fa-tag'></i> " . htmlspecialchars($vehicle['model']) . "</div>";
            echo "<div class='detail-item'><i class='fas fa-id-card'></i> " . htmlspecialchars($vehicle['registration_no']) . "</div>";
            echo "<div class='detail-item'><i class='fas fa-calendar-alt'></i> " . intval($vehicle['year']) . "</div>";
            echo "</div>";
            echo "</div>";
        }
    }
    
} else {
    echo "<div class='alert alert-error' style='text-align: center; padding: 20px;'>";
    echo "<i class='fas fa-exclamation-triangle' style='font-size: 2rem; margin-bottom: 10px; display: block;'></i>";
    echo "<h4>Invalid Request</h4>";
    echo "<p>User ID not provided or invalid.</p>";
    echo "</div>";
}

$conn->close();
?>
