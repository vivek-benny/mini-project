<?php
function sendBookingConfirmationEmail($conn, $booking_id) {
    // Get all booking details with user, mechanic, vehicle, and services info
    $query = "SELECT 
                b.booking_id, b.appointment_date, b.time_slot, b.status,
                r.name as customer_name, r.email as customer_email,
                m.name as mechanic_name, m.profession as mechanic_profession, m.phone_number as mechanic_phone,
                v.brand, v.model, v.registration_no
              FROM bookings b
              JOIN register r ON b.user_id = r.user_id
              JOIN mechanics m ON b.mechanic_id = m.mechanic_id
              JOIN vehicles v ON b.vehicle_id = v.vehicle_id
              WHERE b.booking_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    if (!$booking) return false;
    
    // Get services for this booking
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
        $services_list .= "• " . $service['service_name'] . " - ₹" . $service['service_price'] . "\n";
        $total_cost += $service['service_price'];
    }
    
    // Create email content
    $to = $booking['customer_email'];
    $subject = "🚗 Booking Confirmed - AutoCare Pro";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(45deg, #ff8c42, #ff7b25); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
            .info-box { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #ff8c42; }
            .mechanic-box { background: #e8f5e8; padding: 15px; margin: 15px 0; border-radius: 5px; }
            .appointment-box { background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 15px 0; }
            .important { color: #d63384; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🚗 AutoCare Pro</h1>
                <h2>Booking Confirmation</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>{$booking['customer_name']}</strong>,</p>
                
                <p>Great news! Your vehicle service booking has been <strong>confirmed and assigned</strong>.</p>
                
                <div class='info-box'>
                    <h3>📋 Booking Details</h3>
                    <p><strong>Booking ID:</strong> #{$booking['booking_id']}</p>
                    <p><strong>Vehicle:</strong> {$booking['brand']} {$booking['model']}</p>
                    <p><strong>Registration No:</strong> {$booking['registration_no']}</p>
                    <p><strong>Status:</strong> <span style='color: #28a745; font-weight: bold;'>{$booking['status']}</span></p>
                </div>
                
                <div class='info-box'>
                    <h3>🔧 Services Booked</h3>
                    <pre>{$services_list}</pre>
                    <p><strong>Total Cost: ₹{$total_cost}</strong></p>
                </div>
                
                <div class='mechanic-box'>
                    <h3>👨‍🔧 Your Assigned Mechanic</h3>
                    <p><strong>Name:</strong> {$booking['mechanic_name']}</p>
                    <p><strong>Specialization:</strong> {$booking['mechanic_profession']}</p>
                    <p><strong>Contact:</strong> {$booking['mechanic_phone']}</p>
                </div>
                
                <div class='appointment-box'>
                    <h3>📅 Appointment Details</h3>
                    <p class='important'>Please bring your vehicle on:</p>
                    <p><strong>📅 Date:</strong> " . date('l, F d, Y', strtotime($booking['appointment_date'])) . "</p>
                    <p><strong>⏰ Time:</strong> {$booking['time_slot']}</p>
                </div>
                
                <div class='info-box'>
                    <h3>📝 What to Bring:</h3>
                    <ul>
                        <li>Vehicle registration documents</li>
                        <li>Previous service records (if any)</li>
                        <li>Vehicle keys and spare keys</li>
                        <li>Valid ID proof</li>
                    </ul>
                </div>
                
                <p><strong>Important Notes:</strong></p>
                <ul>
                    <li>Please arrive <strong>10 minutes before</strong> your scheduled time</li>
                    <li>If you need to reschedule, contact us at least <strong>2 hours in advance</strong></li>
                    <li>Keep your vehicle fuel tank at least 1/4 full</li>
                </ul>
                
                <div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>📞 Need Help?</strong></p>
                    <p style='margin: 5px 0 0 0;'>Contact us: <strong>+91-XXXXXXXXXX</strong> | Email: <strong>support@autocarepro.com</strong></p>
                </div>
                
                <p>Thank you for choosing <strong>AutoCare Pro</strong>!</p>
                
                <hr>
                <p><small>This is an automated message. Please do not reply to this email.</small></p>
            </div>
        </div>
    </body>
    </html>";
    
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: AutoCare Pro <noreply@autocarepro.com>" . "\r\n";
    
    // Send email
    return mail($to, $subject, $message, $headers);
}
?>
