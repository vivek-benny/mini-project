<?php
function createNotification($conn, $user_name, $title, $message, $type = 'general', $related_id = null) {
    $query = $conn->prepare("INSERT INTO notifications (user_name, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)");
    $query->bind_param('ssssi', $user_name, $title, $message, $type, $related_id);
    return $query->execute();
}

// When staff assigns a booking
function notifyBookingAssigned($conn, $booking_id) {
    // Get booking details
    $booking_query = $conn->prepare("
        SELECT b.*, r.name as user_name, m.name as mechanic_name 
        FROM bookings b 
        JOIN register r ON b.user_id = r.user_id 
        LEFT JOIN mechanics m ON b.mechanic_id = m.mechanic_id 
        WHERE b.booking_id = ?
    ");
    $booking_query->bind_param('i', $booking_id);
    $booking_query->execute();
    $booking = $booking_query->get_result()->fetch_assoc();
    
    if ($booking) {
        $mechanic_name = $booking['mechanic_name'] ?? 'a mechanic';
        $appointment_date = $booking['appointment_date'] != '0000-00-00' ? $booking['appointment_date'] : 'soon';
        $time_slot = !empty($booking['time_slot']) ? $booking['time_slot'] : 'during business hours';
        
        $title = "Booking Assigned - #{$booking_id}";
        $message = "Your booking has been assigned to {$mechanic_name} on {$appointment_date} at {$time_slot}.";
        
        createNotification($conn, $booking['user_name'], $title, $message, 'booking_assigned', $booking_id);
    }
}

// When admin adds a new service
function notifyNewService($conn, $service_name) {
    // Notify all users
    $users_query = $conn->query("SELECT DISTINCT name FROM register");
    
    while ($user = $users_query->fetch_assoc()) {
        $title = "New Service Available!";
        $message = "We've added a new service: {$service_name}. Check it out and book now!";
        
        createNotification($conn, $user['name'], $title, $message, 'service_added');
    }
}

// When booking status changes
function notifyBookingStatusChange($conn, $booking_id, $new_status) {
    $booking_query = $conn->prepare("SELECT b.*, r.name as user_name FROM bookings b JOIN register r ON b.user_id = r.user_id WHERE b.booking_id = ?");
    $booking_query->bind_param('i', $booking_id);
    $booking_query->execute();
    $booking = $booking_query->get_result()->fetch_assoc();
    
    if ($booking) {
        $status_messages = [
            'Assigned' => 'Your booking has been assigned and confirmed.',
            'In Progress' => 'Work on your vehicle has started.',
            'Completed' => 'Your service has been completed! You can pick up your vehicle.',
            'Cancelled' => 'Your booking has been cancelled. Please contact us for details.'
        ];
        
        $title = "Booking Status Update - #{$booking_id}";
        $message = $status_messages[$new_status] ?? "Your booking status has been updated to: {$new_status}";
        
        createNotification($conn, $booking['user_name'], $title, $message, 'booking_status', $booking_id);
    }
}

// Test function to create a sample notification (for testing purposes)
function createTestNotification($conn, $user_name) {
    $title = "Welcome to AutoCare!";
    $message = "Thank you for choosing our services. We're here to take care of your vehicle!";
    
    createNotification($conn, $user_name, $title, $message, 'general');
}
?>
